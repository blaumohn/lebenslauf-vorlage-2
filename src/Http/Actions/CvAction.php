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
        $lang = isset($params['lang']) ? (string) $params['lang'] : '';
        $lang = $this->resolveLang($lang);
        $fallbackLang = $this->defaultLang();

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

            $privateHtml = $this->context->cvStorage->getPrivateHtmlForLang($profile, $lang);
            if ($privateHtml === null && $lang !== $fallbackLang) {
                $privateHtml = $this->context->cvStorage->getPrivateHtmlForLang($profile, $fallbackLang);
            }
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

        $publicHtml = $this->context->cvStorage->getPublicHtmlForLang($lang);
        if ($publicHtml === null && $lang !== $fallbackLang) {
            $publicHtml = $this->context->cvStorage->getPublicHtmlForLang($fallbackLang);
        }
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

    private function resolveLang(string $lang): string
    {
        $lang = strtolower(trim($lang));
        if ($lang === '') {
            return $this->defaultLang();
        }

        $supported = $this->supportedLangs();
        if ($supported === []) {
            return $lang;
        }

        return in_array($lang, $supported, true) ? $lang : $this->defaultLang();
    }

    private function defaultLang(): string
    {
        $supported = $this->supportedLangs();
        if ($supported !== []) {
            return $supported[0];
        }

        return (string) $this->context->config->get('APP_LANG', 'de');
    }

    private function supportedLangs(): array
    {
        $raw = (string) $this->context->config->get('APP_LANGS', '');
        if ($raw === '') {
            $fallback = (string) $this->context->config->get('APP_LANG', 'de');
            return $fallback !== '' ? [strtolower($fallback)] : [];
        }

        $parts = preg_split('/\s*,\s*/', $raw);
        if ($parts === false) {
            return [];
        }

        $langs = [];
        foreach ($parts as $part) {
            $value = strtolower(trim($part));
            if ($value !== '') {
                $langs[] = $value;
            }
        }

        return array_values(array_unique($langs));
    }
}
