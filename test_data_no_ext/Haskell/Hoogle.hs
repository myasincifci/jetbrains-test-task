{-# LANGUAGE NoImplicitPrelude #-}
{-# LANGUAGE OverloadedStrings #-}
{-# LANGUAGE ScopedTypeVariables #-}

-- | A wrapper around hoogle.
module Stack.Hoogle
    ( hoogleCmd
    ) where

import           Stack.Prelude
import qualified Data.ByteString.Lazy.Char8 as BL8
import           Data.Char (isSpace)
import qualified Data.Text as T
import           Distribution.PackageDescription (packageDescription, package)
import           Distribution.Types.PackageName (mkPackageName)
import           Distribution.Version (mkVersion)
import           Lens.Micro ((?~))
import           Path (parseAbsFile)
import           Path.IO hiding (findExecutable)
import qualified Stack.Build
import           Stack.Build.Target (NeedTargets(NeedTargets))
import           Stack.Runners
import           Stack.Types.Config
import           Stack.Types.SourceMap
import qualified RIO.Map as Map
import           RIO.Process

-- | Helper type to duplicate log messages
data Muted = Muted | NotMuted

-- | Hoogle command.
hoogleCmd :: ([String],Bool,Bool,Bool) -> RIO Runner ()
hoogleCmd (args,setup,rebuild,startServer) =
  local (over globalOptsL modifyGO) $
  withConfig YesReexec $
  withDefaultEnvConfig $ do
    hooglePath <- ensureHoogleInPath
    generateDbIfNeeded hooglePath
    runHoogle hooglePath args'
  where
    modifyGO :: GlobalOpts -> GlobalOpts
    modifyGO = globalOptsBuildOptsMonoidL . buildOptsMonoidHaddockL ?~ True

    args' :: [String]
    args' = if startServer
                 then ["server", "--local", "--port", "8080"]
                 else []
            ++ args
    generateDbIfNeeded :: Path Abs File -> RIO EnvConfig ()
    generateDbIfNeeded hooglePath = do
        databaseExists <- checkDatabaseExists
        if databaseExists && not rebuild
            then return ()
            else if setup || rebuild
                     then do
                         logWarn
                             (if rebuild
                                  then "Rebuilding database ..."
                                  else "No Hoogle database yet. Automatically building haddocks and hoogle database (use --no-setup to disable) ...")
                         buildHaddocks
                         logInfo "Built docs."
                         generateDb hooglePath
                         logInfo "Generated DB."
                     else do
                         logError
                             "No Hoogle database. Not building one due to --no-setup"
                         bail
    generateDb :: Path Abs File -> RIO EnvConfig ()
    generateDb hooglePath = do
        do dir <- hoogleRoot
           createDirIfMissing True dir
           runHoogle hooglePath ["generate", "--local"]
    buildHaddocks :: RIO EnvConfig ()
    buildHaddocks = do
      config <- view configL
      runRIO config $ -- a bit weird that we have to drop down like this
        catch (withDefaultEnvConfig $ Stack.Build.build Nothing)
              (\(_ :: ExitCode) -> return ())
    hooglePackageName = mkPackageName "hoogle"
    hoogleMinVersion = mkVersion [5, 0]
    hoogleMinIdent =
        PackageIdentifier hooglePackageName hoogleMinVersion
    installHoogle :: RIO EnvConfig (Path Abs File)
    installHoogle = requiringHoogle Muted $ do
        Stack.Build.build Nothing
        mhooglePath' <- findExecutable "hoogle"
        case mhooglePath' of
            Right hooglePath -> parseAbsFile hooglePath
            Left _ -> do
                logWarn "Couldn't find hoogle in path after installing.  This shouldn't happen, may be a bug."
                bail
    requiringHoogle :: Muted -> RIO EnvConfig x -> RIO EnvConfig x
    requiringHoogle muted f = do
        hoogleTarget <- do
          sourceMap <- view $ sourceMapL . to smDeps
          case Map.lookup hooglePackageName sourceMap of
            Just hoogleDep ->
              case dpLocation hoogleDep of
                PLImmutable pli ->
                  T.pack . packageIdentifierString <$>
                      restrictMinHoogleVersion muted (packageLocationIdent pli)
                plm@(PLMutable _) -> do
                  T.pack . packageIdentifierString . package . packageDescription
                      <$> loadCabalFile plm
            Nothing -> do
              -- not muted because this should happen only once
              logWarn "No hoogle version was found, trying to install the latest version"
              mpir <- getLatestHackageVersion YesRequireHackageIndex hooglePackageName UsePreferredVersions
              let hoogleIdent = case mpir of
                      Nothing -> hoogleMinIdent
                      Just (PackageIdentifierRevision _ ver _) ->
                          PackageIdentifier hooglePackageName ver
              T.pack . packageIdentifierString <$>
                  restrictMinHoogleVersion muted hoogleIdent
        config <- view configL
        let boptsCLI = defaultBuildOptsCLI
                { boptsCLITargets =  [hoogleTarget]
                }
        runRIO config $ withEnvConfig NeedTargets boptsCLI f
    restrictMinHoogleVersion
      :: HasLogFunc env
      => Muted -> PackageIdentifier -> RIO env PackageIdentifier
    restrictMinHoogleVersion muted ident = do
      if ident < hoogleMinIdent
      then do
          muteableLog LevelWarn muted $
               "Minimum " <>
               fromString (packageIdentifierString hoogleMinIdent) <>
               " is not in your index. Installing the minimum version."
          pure hoogleMinIdent
      else do
          muteableLog LevelInfo muted $
              "Minimum version is " <>
              fromString (packageIdentifierString hoogleMinIdent) <>
              ". Found acceptable " <>
              fromString (packageIdentifierString ident) <>
              " in your index, requiring its installation."
          pure ident
    muteableLog :: HasLogFunc env => LogLevel -> Muted -> Utf8Builder -> RIO env ()
    muteableLog logLevel muted msg =
        case muted of
            Muted -> pure ()
            NotMuted -> logGeneric "" logLevel msg
    runHoogle :: Path Abs File -> [String] -> RIO EnvConfig ()
    runHoogle hooglePath hoogleArgs = do
        config <- view configL
        menv <- liftIO $ configProcessContextSettings config envSettings
        dbpath <- hoogleDatabasePath
        let databaseArg = ["--database=" ++ toFilePath dbpath]
        withProcessContext menv $ proc
          (toFilePath hooglePath)
          (hoogleArgs ++ databaseArg)
          runProcess_
    bail :: RIO EnvConfig a
    bail = exitWith (ExitFailure (-1))
    checkDatabaseExists = do
        path <- hoogleDatabasePath
        liftIO (doesFileExist path)
    ensureHoogleInPath :: RIO EnvConfig (Path Abs File)
    ensureHoogleInPath = do
        config <- view configL
        menv <- liftIO $ configProcessContextSettings config envSettings
        mhooglePath <- runRIO menv (findExecutable "hoogle") <>
          requiringHoogle NotMuted (findExecutable "hoogle")
        eres <- case mhooglePath of
            Left _ -> return $ Left "Hoogle isn't installed."
            Right hooglePath -> do
                result <- withProcessContext menv
                        $ proc hooglePath ["--numeric-version"]
                        $ tryAny . fmap fst . readProcess_
                let unexpectedResult got = Left $ T.concat
                        [ "'"
                        , T.pack hooglePath
                        , " --numeric-version' did not respond with expected value. Got: "
                        , got
                        ]
                return $ case result of
                    Left err -> unexpectedResult $ T.pack (show err)
                    Right bs -> case parseVersion (takeWhile (not . isSpace) (BL8.unpack bs)) of
                        Nothing -> unexpectedResult $ T.pack (BL8.unpack bs)
                        Just ver
                            | ver >= hoogleMinVersion -> Right hooglePath
                            | otherwise -> Left $ T.concat
                                [ "Installed Hoogle is too old, "
                                , T.pack hooglePath
                                , " is version "
                                , T.pack $ versionString ver
                                , " but >= 5.0 is required."
                                ]
        case eres of
            Right hooglePath -> parseAbsFile hooglePath
            Left err
                | setup -> do
                    logWarn $ display err <> " Automatically installing (use --no-setup to disable) ..."
                    installHoogle
                | otherwise -> do
                    logWarn $ display err <> " Not installing it due to --no-setup."
                    bail
    envSettings =
        EnvSettings
        { esIncludeLocals = True
        , esIncludeGhcPackagePath = True
        , esStackExe = True
        , esLocaleUtf8 = False
        , esKeepGhcRts = False
        }
