<?php

use App\Config;

require_once __DIR__ . '/../vendor/autoload.php';

$rootPath = dirname(__DIR__);
$config = new Config($rootPath);

return $config;
