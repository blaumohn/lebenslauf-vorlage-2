<?php

use App\Env\Env;
use App\Http\AppBuilder;

$rootPath = dirname(__DIR__, 2);
$env = new Env($rootPath);

return AppBuilder::build($env);
