<?php

namespace App\Http;

use App\Storage\StorageException;
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
        $siteName = (string) $this->context->config->get('SITE_NAME', 'Lebenslauf');
        $message = 'Ein unerwarteter Fehler ist aufgetreten.';

        if ($exception instanceof StorageException) {
            $message = 'Datei konnte nicht gespeichert werden. Bitte Dateirechte pruefen.';
        }

        if ($displayErrorDetails) {
            $message .= ' ' . $exception->getMessage();
        }

        $html = $this->context->twig->render('error.html.twig', [
            'title' => 'Serverfehler',
            'message' => $message,
            'site_name' => $siteName,
        ]);

        return ResponseHelper::html(new Response(), $html, 500);
    }
}
