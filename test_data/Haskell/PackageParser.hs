{-# LANGUAGE NoImplicitPrelude #-}
module Stack.Options.PackageParser where

import qualified Data.Map                          as Map
import           Options.Applicative
import           Options.Applicative.Types         (readerAsk)
import           Stack.Prelude
import           Stack.Types.Config.Build (ApplyCLIFlag (..))

-- | Parser for package:[-]flag
readFlag :: ReadM (Map ApplyCLIFlag (Map FlagName Bool))
readFlag = do
    s <- readerAsk
    case break (== ':') s of
        (pn, ':':mflag) -> do
            pn' <-
                case parsePackageName pn of
                    Nothing
                        | pn == "*" -> return ACFAllProjectPackages
                        | otherwise -> readerError $ "Invalid package name: " ++ pn
                    Just x -> return $ ACFByName x
            let (b, flagS) =
                    case mflag of
                        '-':x -> (False, x)
                        _ -> (True, mflag)
            flagN <-
                case parseFlagName flagS of
                    Nothing -> readerError $ "Invalid flag name: " ++ flagS
                    Just x -> return x
            return $ Map.singleton pn' $ Map.singleton flagN b
        _ -> readerError "Must have a colon"
