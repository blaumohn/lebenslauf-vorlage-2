<?php

namespace App\Http\Actions;

use App\Http\AppContext;
use App\Http\ResponseHelper;
use App\View\PageViewBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContactFormAction
{
    private AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ipHash = $this->resolveIpHash($request);

        if ($this->isRateLimited($ipHash)) {
            return $this->renderError($response, 429);
        }

        return $this->renderForm($response, $ipHash, null);
    }

    private function resolveIpHash(ServerRequestInterface $request): string
    {
        $trustProxy = $this->context->config->getBool('TRUST_PROXY', false);
        $ip = $this->context->ipResolver->resolve($request, $trustProxy);
        return hash_hmac('sha256', $ip, (string) $this->context->config->get('IP_SALT', 'salt'));
    }

    private function isRateLimited(string $ipHash): bool
    {
        $window = $this->context->config->getInt('RATE_LIMIT_WINDOW_SECONDS', 600);
        $maxGet = $this->context->config->getInt('CAPTCHA_MAX_GET', 5);
        return !$this->context->rateLimiter->allow('contact_get_' . $ipHash, $maxGet, $window);
    }

    private function renderError(
        ResponseInterface $response,
        int $status
    ): ResponseInterface {
        $base = PageViewBuilder::base($this->context->config);
        $html = $this->context->twig->render('error.html.twig', [
            'title' => 'Zu viele Anfragen',
            'message' => 'Bitte spaeter erneut versuchen.',
        ] + $base);
        return ResponseHelper::html($response, $html, $status);
    }

    private function renderForm(
        ResponseInterface $response,
        string $ipHash,
        ?string $error
    ): ResponseInterface {
        $base = PageViewBuilder::base($this->context->config);
        $challenge = $this->context->captchaService->createChallenge($ipHash);
        $captchaId = $challenge['captcha_id'];
        $captchaUrl = '/captcha.png?id=' . urlencode($captchaId);
        $html = $this->context->twig->render('contact.html.twig', [
            'title' => 'Kontakt',
            'captcha_id' => $captchaId,
            'captcha_url' => $captchaUrl,
            'show_error' => $error !== null,
            'error_text' => $error ?? '',
            'form_values' => [
                'name' => '',
                'email' => '',
                'message' => '',
            ],
        ] + $base);
        return ResponseHelper::html($response, $html);
    }
}
