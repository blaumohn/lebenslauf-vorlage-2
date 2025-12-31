<?php

use App\Http\AppBuilder;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../src/bootstrap.php';

$app = AppBuilder::build($config);
$app->run();
