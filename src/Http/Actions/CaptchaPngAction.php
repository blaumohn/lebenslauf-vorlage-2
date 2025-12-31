<?php

namespace App\Http\Actions;

use App\Http\AppContext;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CaptchaPngAction
{
    private AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $id = isset($params['id']) ? (string) $params['id'] : '';
        if ($id === '') {
            return ResponseHelper::text($response, 'Not found', 404);
        }

        $challenge = $this->context->captchaService->getChallenge($id);
        if (!$challenge) {
            return ResponseHelper::text($response, 'Not found', 404);
        }

        $png = $this->context->captchaService->renderPng((string) $challenge['solution_text']);
        if ($png === '') {
            return ResponseHelper::text($response, 'Captcha rendering failed', 500);
        }

        return ResponseHelper::png($response, $png);
    }
}
