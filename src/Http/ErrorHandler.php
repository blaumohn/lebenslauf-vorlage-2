<?php

namespace App\Http;

use App\Storage\StorageException;
use App\View\PageViewBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Throwable;

final class ErrorHandler
{
    private AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $message = 'Ein unerwarteter Fehler ist aufgetreten.';

        if ($exception instanceof StorageException) {
            $message = 'Datei konnte nicht gespeichert werden. Bitte Dateirechte pruefen.';
        }

        if ($displayErrorDetails) {
            $message .= ' ' . $exception->getMessage();
        }

        $base = PageViewBuilder::base($this->context->config);
        $html = $this->context->twig->render('error.html.twig', [
            'title' => 'Serverfehler',
            'message' => $message,
        ] + $base);

        return ResponseHelper::html(new Response(), $html, 500);
    }
}
