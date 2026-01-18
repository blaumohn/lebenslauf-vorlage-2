<?php

namespace App\Content;

use Symfony\Component\Filesystem\Path;

final class ContentPaths
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
    }

    public function localContentFile(): string
    {
        return Path::join($this->rootPath, '.local', 'content.ini');
    }

    public function demoFixtureFile(): string
    {
        return Path::join($this->rootPath, 'tests', 'fixtures', 'content.ini');
    }
}
