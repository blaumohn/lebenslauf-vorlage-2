<?php

namespace App\Http\Actions;

use App\Http\AppContext;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HomeAction
{
    private AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $siteName = (string) $this->context->config->get('SITE_NAME', 'Lebenslauf');
        $html = $this->context->twig->render('home.html.twig', [
            'title' => 'Home',
            'site_name' => $siteName,
            'has_public_cv' => $this->context->cvStorage->hasPublic(),
        ]);

        return ResponseHelper::html($response, $html);
    }
}
