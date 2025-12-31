<?php

namespace App\Http\Actions;

use App\Http\AppContext;
use App\Http\ResponseHelper;
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
        $siteName = (string) $this->context->config->get('SITE_NAME', 'Lebenslauf');
        $trustProxy = $this->context->config->getBool('TRUST_PROXY', false);
        $ip = $this->context->ipResolver->resolve($request, $trustProxy);
        $ipHash = hash_hmac('sha256', $ip, (string) $this->context->config->get('IP_SALT', 'salt'));

        $window = $this->context->config->getInt('RATE_LIMIT_WINDOW_SECONDS', 600);
        $maxGet = $this->context->config->getInt('CAPTCHA_MAX_GET', 5);

        if (!$this->context->rateLimiter->allow('contact_get_' . $ipHash, $maxGet, $window)) {
            $html = $this->context->twig->render('error.html.twig', [
                'title' => 'Zu viele Anfragen',
                'message' => 'Bitte spaeter erneut versuchen.',
                'site_name' => $siteName,
            ]);
            return ResponseHelper::html($response, $html, 429);
        }

        $challenge = $this->context->captchaService->createChallenge($ipHash);
        $html = $this->context->twig->render('contact.html.twig', [
            'title' => 'Kontakt',
            'site_name' => $siteName,
            'captcha_id' => $challenge['captcha_id'],
            'error' => null,
        ]);

        return ResponseHelper::html($response, $html);
    }
}
