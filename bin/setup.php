#!/usr/bin/env php
<?php

$root = dirname(__DIR__);
$args = array_slice($argv, 1);

$python = findPython();
if ($python === null) {
    fwrite(STDERR, "Python 3 not found (python3 or python).\n");
    exit(1);
}

$cmd = array_merge([$python, $root . '/bin/setup.py'], $args);
$escaped = array_map('escapeshellarg', $cmd);
passthru(implode(' ', $escaped), $exitCode);
exit($exitCode);

function findPython(): ?string
{
    $candidates = ['python3', 'python'];
    foreach ($candidates as $candidate) {
        $path = trim((string) shell_exec("command -v {$candidate}"));
        if ($path === '') {
            continue;
        }
        if (isPython3($candidate)) {
            return $candidate;
        }
    }
    return null;
}

function isPython3(string $binary): bool
{
    $cmd = escapeshellarg($binary) . " -c " . escapeshellarg("import sys; print(sys.version_info[0])");
    $output = trim((string) shell_exec($cmd));
    return $output === '3';
}
