<?php

namespace App\Http\View;

use App\Env\Env;

final class PageViewBuilder
{
    public static function base(Env $config): array
    {
        $siteName = (string) $config->get('SITE_NAME', 'Lebenslauf');
        return [
            'site_name' => $siteName,
            'nav_items' => [
                ['href' => '/', 'label' => 'Home'],
                ['href' => '/cv', 'label' => 'Lebenslauf'],
                ['href' => '/contact', 'label' => 'Kontakt'],
            ],
        ];
    }
}
