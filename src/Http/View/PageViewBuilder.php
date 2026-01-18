<?php

namespace App\Http\View;

use App\Content\ContentConfig;

final class PageViewBuilder
{
    public static function base(ContentConfig $content): array
    {
        $siteName = $content->siteName();
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
