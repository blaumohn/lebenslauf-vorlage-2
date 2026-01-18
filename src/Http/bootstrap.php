<?php

use App\Http\EnvCompiled;
use App\Http\AppBuilder;

$rootPath = dirname(__DIR__, 2);
$env = new EnvCompiled($rootPath);

return AppBuilder::build($env);
