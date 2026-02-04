<?php

namespace App\Http\Cv;

use Twig\Environment;

final class CvRenderer
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function renderPrivate(array $data, array $labels): string
    {
        return $this->twig->render('cv_private.html.twig', [
            'cv' => $data,
            'etiketten' => $labels,
            'title' => $labels['_'] ?? 'Lebenslauf',
        ]);
    }

    public function renderPublic(array $data, array $labels): string
    {
        return $this->twig->render('cv_public.html.twig', [
            'cv' => $data,
            'etiketten' => $labels,
            'title' => $labels['_'] ?? 'Lebenslauf',
        ]);
    }
}
