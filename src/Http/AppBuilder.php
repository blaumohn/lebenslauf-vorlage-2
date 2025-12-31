<?php

namespace App\Http;

use App\Config;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class AppBuilder
{
    public static function build(Config $config): App
    {
        $context = AppContext::fromConfig($config);

        $app = SlimAppFactory::create();
        $app->addBodyParsingMiddleware();

        Routes::register($app, $context);

        $app->addRoutingMiddleware();

        $isDev = strtolower((string) $config->get('APP_ENV', 'prod')) === 'dev';
        $errorMiddleware = $app->addErrorMiddleware($isDev, true, true);
        $errorMiddleware->setDefaultErrorHandler(new ErrorHandler($context));

        return $app;
    }
}
