<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;

final class RobotsController
{
    public function index(Request $request): Response
    {
        $installed = is_file(storage_path('installed.lock'));

        $lines = [
            'User-agent: *',
        ];

        if (!$installed) {
            // Never let search engines index a site mid-installation.
            $lines[] = 'Disallow: /';

            return Response::text(implode("\n", $lines) . "\n");
        }

        $lines[] = 'Disallow: /admin';
        $lines[] = 'Disallow: /install';
        $siteUrl = rtrim((string) config('app.url', ''), '/');
        if (filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        }
        return Response::text(implode("\n", $lines) . "\n");
    }
}
