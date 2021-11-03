{-# LANGUAGE NoImplicitPrelude #-}
{-# LANGUAGE FlexibleInstances #-}

-- | Names for packages.

module Stack.Types.PackageName
    ( packageNameArgument
    ) where

import           Stack.Prelude
import qualified Options.Applicative as O


-- | An argument which accepts a template name of the format
-- @foo.hsfiles@.
packageNameArgument :: O.Mod O.ArgumentFields PackageName
                    -> O.Parser PackageName
packageNameArgument =
    O.argument
        (do s <- O.str
            either O.readerError return (p s))
  where
    p s =
        case parsePackageName s of
            Just x -> Right x
            Nothing -> Left $ unlines
                [ "Expected valid package name, but got: " ++ s
                , "Package names consist of one or more alphanumeric words separated by hyphens."
                , "To avoid ambiguity with version numbers, each of these words must contain at least one letter."
                ]
