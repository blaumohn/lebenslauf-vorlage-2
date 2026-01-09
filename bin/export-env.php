#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/Env/EnvLoader.php';

use App\Env\EnvLoader;

$path = $argv[1] ?? '';
$target = $argv[2] ?? '';

if ($path === '' || $target === '') {
    fwrite(STDERR, "Usage: export-env.php <mode> <target-path>\n");
    exit(1);
}

$loader = new EnvLoader();
if ($path === 'defaults') {
    $loader->exportDefaultsToFile(dirname(__DIR__), $target);
    exit(0);
}
if ($path === 'deploy-defaults') {
    $loader->exportDeployDefaultsToFile(dirname(__DIR__), $target);
    exit(0);
}

fwrite(STDERR, "Unknown mode: {$path}\n");
exit(1);
