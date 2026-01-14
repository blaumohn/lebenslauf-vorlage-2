<?php

namespace App\Http;

use App\Env\Env;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class AppBuilder
{
    public static function build(Env $config): App
    {
        $context = AppContext::fromConfig($config);

        $app = SlimAppFactory::create();
        $app->addBodyParsingMiddleware();

        $basePath = $config->basePath();
        if ($basePath !== '') {
            $app->setBasePath($basePath);
        }

        Routes::register($app, $context);

        $app->addRoutingMiddleware();

        $isDev = strtolower((string) $config->get('APP_ENV', '')) === 'dev';
        $errorMiddleware = $app->addErrorMiddleware($isDev, true, true);
        $errorMiddleware->setDefaultErrorHandler(new ErrorHandler($context));

        return $app;
    }
}
