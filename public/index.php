<?php

use App\Captcha\CaptchaService;
use App\Config;
use App\Contact\MailService;
use App\Cv\CvStorage;
use App\Http\Request;
use App\Http\Response;
use App\Security\RateLimiter;
use App\Security\TokenService;
use App\Storage\FileStorage;
use App\Templating\TwigFactory;

$config = require __DIR__ . '/../src/bootstrap.php';

$rootPath = $config->rootPath();
$templatesPath = $rootPath . '/templates';
$storage = new FileStorage();

$twig = TwigFactory::create($templatesPath);
$cvStorage = new CvStorage($storage, $rootPath . '/var/cache/html');
$tokenService = new TokenService($storage, $rootPath . '/var/state/tokens');
$captchaService = new CaptchaService(
    $storage,
    $rootPath . '/var/tmp/captcha',
    $config->getInt('CAPTCHA_TTL_SECONDS', 600)
);
$rateLimiter = new RateLimiter($storage, $rootPath . '/var/tmp/ratelimit');
$mailService = new MailService($config);

$request = Request::fromGlobals();
$siteName = (string) $config->get('SITE_NAME', 'Lebenslauf');

$path = $request->path();
$method = $request->method();

if ($method === 'GET' && $path === '/') {
    $html = $twig->render('home.html.twig', [
        'title' => 'Home',
        'site_name' => $siteName,
        'has_public_cv' => $cvStorage->hasPublic(),
    ]);
    Response::html($html)->send();
    return;
}

if ($method === 'GET' && $path === '/cv') {
    $token = (string) $request->query('token', '');
    if ($token !== '') {
        $profile = (string) $request->query('profile', '');
        if ($profile === '') {
            $profile = $tokenService->findProfileForToken($token) ?? '';
        }

        if ($profile === '' || !$tokenService->verify($profile, $token)) {
            $html = $twig->render('error.html.twig', [
                'title' => 'Zugriff verweigert',
                'message' => 'Token ungueltig oder abgelaufen.',
                'site_name' => $siteName,
            ]);
            Response::html($html, 403)->send();
            return;
        }

        $privateHtml = $cvStorage->getPrivateHtml($profile);
        if ($privateHtml === null) {
            $html = $twig->render('error.html.twig', [
                'title' => 'Nicht gefunden',
                'message' => 'Privater Lebenslauf noch nicht vorhanden.',
                'site_name' => $siteName,
            ]);
            Response::html($html, 404)->send();
            return;
        }

        Response::html($privateHtml)->send();
        return;
    }

    $publicHtml = $cvStorage->getPublicHtml();
    if ($publicHtml === null) {
        $html = $twig->render('error.html.twig', [
            'title' => 'Nicht gefunden',
            'message' => 'Oeffentlicher Lebenslauf noch nicht vorhanden.',
            'site_name' => $siteName,
        ]);
        Response::html($html, 404)->send();
        return;
    }

    Response::html($publicHtml)->send();
    return;
}

if ($method === 'GET' && $path === '/contact') {
    $ipHash = hash_hmac('sha256', $request->clientIp($config->getBool('TRUST_PROXY')), (string) $config->get('IP_SALT', 'salt'));
    $window = $config->getInt('RATE_LIMIT_WINDOW_SECONDS', 600);
    $maxGet = $config->getInt('CAPTCHA_MAX_GET', 5);

    if (!$rateLimiter->allow('contact_get_' . $ipHash, $maxGet, $window)) {
        $html = $twig->render('error.html.twig', [
            'title' => 'Zu viele Anfragen',
            'message' => 'Bitte spaeter erneut versuchen.',
            'site_name' => $siteName,
        ]);
        Response::html($html, 429)->send();
        return;
    }

    $challenge = $captchaService->createChallenge($ipHash);
    $html = $twig->render('contact.html.twig', [
        'title' => 'Kontakt',
        'site_name' => $siteName,
        'captcha_id' => $challenge['captcha_id'],
        'error' => null,
    ]);
    Response::html($html)->send();
    return;
}

if ($method === 'GET' && $path === '/captcha.png') {
    $id = (string) $request->query('id', '');
    if ($id === '') {
        Response::text('Not found', 404)->send();
        return;
    }

    $challenge = $captchaService->getChallenge($id);
    if (!$challenge) {
        Response::text('Not found', 404)->send();
        return;
    }

    $png = $captchaService->renderPng((string) $challenge['solution_text']);
    if ($png === '') {
        Response::text('Captcha rendering failed', 500)->send();
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $response = new Response($png, 200, [
        'Content-Type' => 'image/png',
        'Content-Length' => (string) strlen($png),
        'Cache-Control' => 'no-store',
        'Pragma' => 'no-cache',
        'X-Content-Type-Options' => 'nosniff',
    ]);
    $response->send();
    return;
}

if ($method === 'POST' && $path === '/contact') {
    $ipHash = hash_hmac('sha256', $request->clientIp($config->getBool('TRUST_PROXY')), (string) $config->get('IP_SALT', 'salt'));
    $window = $config->getInt('RATE_LIMIT_WINDOW_SECONDS', 600);
    $maxPost = $config->getInt('CONTACT_MAX_POST', 3);


    if (!$rateLimiter->allow('contact_post_' . $ipHash, $maxPost, $window)) {
        $html = $twig->render('error.html.twig', [
            'title' => 'Zu viele Anfragen',
            'message' => 'Bitte spaeter erneut versuchen.',
            'site_name' => $siteName,
        ]);
        Response::html($html, 429)->send();
        return;
    }

    $name = trim((string) $request->post('name', ''));
    $email = trim((string) $request->post('email', ''));
    $message = trim((string) $request->post('message', ''));
    $captchaId = trim((string) $request->post('captcha_id', ''));
    $captchaAnswer = trim((string) $request->post('captcha_answer', ''));

    $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    $captchaOk = $captchaId !== '' && $captchaAnswer !== '' && $captchaService->verify($captchaId, $captchaAnswer, $ipHash);

    if ($name === '' || !$emailValid || $message === '' || !$captchaOk) {
        $challenge = $captchaService->createChallenge($ipHash);
        $html = $twig->render('contact.html.twig', [
            'title' => 'Kontakt',
            'site_name' => $siteName,
            'captcha_id' => $challenge['captcha_id'],
            'error' => 'Bitte Eingaben und CAPTCHA pruefen.',
        ]);
        Response::html($html, 403)->send();
        return;
    }

    $body = "Name: {$name}\nE-Mail: {$email}\n\nNachricht:\n{$message}\n";
    $sent = $mailService->send($name, $email, $body);
    if (!$sent) {
        $challenge = $captchaService->createChallenge($ipHash);
        $html = $twig->render('contact.html.twig', [
            'title' => 'Kontakt',
            'site_name' => $siteName,
            'captcha_id' => $challenge['captcha_id'],
            'error' => 'Versand fehlgeschlagen. Bitte spaeter erneut versuchen.',
        ]);
        Response::html($html, 500)->send();
        return;
    }

    $html = $twig->render('contact_ok.html.twig', [
        'title' => 'Kontakt',
        'site_name' => $siteName,
    ]);
    Response::html($html)->send();
    return;
}

$html = $twig->render('error.html.twig', [
    'title' => 'Nicht gefunden',
    'message' => 'Diese Seite existiert nicht.',
    'site_name' => $siteName,
]);
Response::html($html, 404)->send();
