<?php

namespace App\Http\View;

final class PageViewBuilder
{
    public static function base(): array
    {
        return [
            'nav_items' => [
                ['href' => '/', 'label' => 'Home'],
                ['href' => '/cv', 'label' => 'Lebenslauf'],
                ['href' => '/contact', 'label' => 'Kontakt'],
            ],
        ];
    }
}
