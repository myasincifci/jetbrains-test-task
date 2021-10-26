module System.Permissions
  ( setScriptPerms
  , osIsWindows
  , setFileExecutable
  ) where

-- | True if using Windows OS.
osIsWindows :: Bool
osIsWindows = True

setScriptPerms :: Monad m => FilePath -> m ()
setScriptPerms _ = return ()

setFileExecutable :: Monad m => FilePath -> m ()
setFileExecutable _ = return ()
