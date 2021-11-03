<?php

namespace Symfony\Component\Routing\Tests\Loader;

use Symfony\Component\Config\FileLocatorInterface;

class FileLocatorStub implements FileLocatorInterface
{
    public function locate(string $name, string $currentPath = null, bool $first = true)
    {
        if (str_starts_with($name, 'http')) {
            return $name;
        }

        return rtrim($currentPath, '/').'/'.$name;
    }
}
