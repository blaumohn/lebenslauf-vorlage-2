<?php

namespace App\Http\Actions;

use App\Http\AppContext;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CvAction
{
    private AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $siteName = (string) $this->context->config->get('SITE_NAME', 'Lebenslauf');
        $params = $request->getQueryParams();
        $token = isset($params['token']) ? (string) $params['token'] : '';

        if ($token !== '') {
            $profile = isset($params['profile']) ? (string) $params['profile'] : '';
            if ($profile === '') {
                $profile = $this->context->tokenService->findProfileForToken($token) ?? '';
            }

            if ($profile === '' || !$this->context->tokenService->verify($profile, $token)) {
                $html = $this->context->twig->render('error.html.twig', [
                    'title' => 'Zugriff verweigert',
                    'message' => 'Token ungueltig oder abgelaufen.',
                    'site_name' => $siteName,
                ]);
                return ResponseHelper::html($response, $html, 403);
            }

            $privateHtml = $this->context->cvStorage->getPrivateHtml($profile);
            if ($privateHtml === null) {
                $html = $this->context->twig->render('error.html.twig', [
                    'title' => 'Nicht gefunden',
                    'message' => 'Privater Lebenslauf noch nicht vorhanden.',
                    'site_name' => $siteName,
                ]);
                return ResponseHelper::html($response, $html, 404);
            }

            return ResponseHelper::html($response, $privateHtml);
        }

        $publicHtml = $this->context->cvStorage->getPublicHtml();
        if ($publicHtml === null) {
            $html = $this->context->twig->render('error.html.twig', [
                'title' => 'Nicht gefunden',
                'message' => 'Oeffentlicher Lebenslauf noch nicht vorhanden.',
                'site_name' => $siteName,
            ]);
            return ResponseHelper::html($response, $html, 404);
        }

        return ResponseHelper::html($response, $publicHtml);
    }
}
