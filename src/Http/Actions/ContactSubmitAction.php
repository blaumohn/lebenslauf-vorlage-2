<?php

namespace App\Http\Actions;

use App\Http\AppContext;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContactSubmitAction
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
        $maxPost = $this->context->config->getInt('CONTACT_MAX_POST', 3);

        if (!$this->context->rateLimiter->allow('contact_post_' . $ipHash, $maxPost, $window)) {
            $html = $this->context->twig->render('error.html.twig', [
                'title' => 'Zu viele Anfragen',
                'message' => 'Bitte spaeter erneut versuchen.',
                'site_name' => $siteName,
            ]);
            return ResponseHelper::html($response, $html, 429);
        }

        $data = $request->getParsedBody();
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $captchaId = trim((string) ($data['captcha_id'] ?? ''));
        $captchaAnswer = trim((string) ($data['captcha_answer'] ?? ''));

        $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $captchaOk = $captchaId !== '' && $captchaAnswer !== ''
            && $this->context->captchaService->verify($captchaId, $captchaAnswer, $ipHash);

        if ($name === '' || !$emailValid || $message === '' || !$captchaOk) {
            $challenge = $this->context->captchaService->createChallenge($ipHash);
            $html = $this->context->twig->render('contact.html.twig', [
                'title' => 'Kontakt',
                'site_name' => $siteName,
                'captcha_id' => $challenge['captcha_id'],
                'error' => 'Bitte Eingaben und CAPTCHA pruefen.',
            ]);
            return ResponseHelper::html($response, $html, 403);
        }

        $body = "Name: {$name}\nE-Mail: {$email}\n\nNachricht:\n{$message}\n";
        $sent = $this->context->mailService->send($name, $email, $body);
        if (!$sent) {
            $challenge = $this->context->captchaService->createChallenge($ipHash);
            $html = $this->context->twig->render('contact.html.twig', [
                'title' => 'Kontakt',
                'site_name' => $siteName,
                'captcha_id' => $challenge['captcha_id'],
                'error' => 'Versand fehlgeschlagen. Bitte spaeter erneut versuchen.',
            ]);
            return ResponseHelper::html($response, $html, 500);
        }

        $html = $this->context->twig->render('contact_ok.html.twig', [
            'title' => 'Kontakt',
            'site_name' => $siteName,
        ]);

        return ResponseHelper::html($response, $html);
    }
}
