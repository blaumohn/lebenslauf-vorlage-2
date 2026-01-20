<?php

use App\Http\ConfigCompiled;
use App\Http\AppBuilder;

$rootPath = dirname(__DIR__, 2);
$config = new ConfigCompiled($rootPath);

return AppBuilder::build($config);
