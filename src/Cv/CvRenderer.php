<?php

namespace App\Cv;

use Twig\Environment;

final class CvRenderer
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function renderPrivate(array $data): string
    {
        return $this->twig->render('cv_private.html.twig', ['cv' => $data]);
    }

    public function renderPublic(array $data): string
    {
        return $this->twig->render('cv_public.html.twig', ['cv' => $data]);
    }
}
