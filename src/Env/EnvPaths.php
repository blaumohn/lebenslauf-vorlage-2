<?php

namespace App\Env;

use Symfony\Component\Filesystem\Path;

final class EnvPaths
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
    }

    public function defaultsFile(): string
    {
        return Path::join($this->rootPath, 'config', 'env-default.ini');
    }

    public function deployDefaultsFile(): string
    {
        return Path::join($this->rootPath, 'config', 'deploy-default.ini');
    }

    public function localDir(): string
    {
        return Path::join($this->rootPath, '.local');
    }

    public function localCommonFile(): string
    {
        return Path::join($this->localDir(), 'env-common.ini');
    }

    public function localProfileFile(string $profile): string
    {
        return Path::join($this->localDir(), 'env-' . $profile . '.ini');
    }

    public function demoFixtureFile(): string
    {
        return Path::join($this->rootPath, 'tests', 'fixtures', 'env-gueltig.ini');
    }

    public function resolvePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }
        if ($path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }
        if ((bool) preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }
        return Path::join($this->rootPath, $path);
    }
}
