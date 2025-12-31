<?php

namespace App\Http;

use App\Captcha\CaptchaService;
use App\Config;
use App\Contact\MailService;
use App\Cv\CvStorage;
use App\Security\RateLimiter;
use App\Security\TokenService;
use App\Storage\FileStorage;
use App\Templating\TwigFactory;
use Twig\Environment;

final class AppContext
{
    public Config $config;
    public Environment $twig;
    public CvStorage $cvStorage;
    public TokenService $tokenService;
    public CaptchaService $captchaService;
    public RateLimiter $rateLimiter;
    public MailService $mailService;
    public IpResolver $ipResolver;

    public static function fromConfig(Config $config): self
    {
        $rootPath = $config->rootPath();
        $storage = new FileStorage();

        $context = new self();
        $context->config = $config;
        $context->twig = TwigFactory::create($rootPath . '/templates');
        $context->cvStorage = new CvStorage($storage, $rootPath . '/var/cache/html');
        $context->tokenService = new TokenService($storage, $rootPath . '/var/state/tokens');
        $context->captchaService = new CaptchaService(
            $storage,
            $rootPath . '/var/tmp/captcha',
            $config->getInt('CAPTCHA_TTL_SECONDS', 600)
        );
        $context->rateLimiter = new RateLimiter($storage, $rootPath . '/var/tmp/ratelimit');
        $context->mailService = new MailService($config);
        $context->ipResolver = new IpResolver();

        return $context;
    }
}
