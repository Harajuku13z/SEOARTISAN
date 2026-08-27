<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Models\Company;
use App\Models\CompanyService;
use App\Models\Page;
use App\Repositories\SettingsRepository;

final class RobotsController
{
    public function __construct(private SettingsRepository $settings) {}

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
        $lines[] = 'Disallow: /track/';

        $siteUrl = rtrim((string) config('app.url', ''), '/');
        if (filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        }

        // Moteurs de recherche : accès complet explicitement garanti.
        $searchEngines = ['Googlebot', 'Bingbot', 'DuckDuckBot', 'YandexBot', 'Baiduspider', 'Applebot', 'MojeekBot'];
        foreach ($searchEngines as $engine) {
            $lines[] = '';
            $lines[] = 'User-agent: ' . $engine;
            $lines[] = 'Allow: /';
        }

        // Crawlers d'IA : autorisation explicite (site « notifié » aux IA).
        if ((bool) $this->settings->get('visits.allow_ai_bots', true)) {
            $aiCrawlers = [
                'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-Web',
                'Anthropic-ai', 'PerplexityBot', 'Perplexity-User', 'Google-Extended',
                'Applebot-Extended', 'CCBot', 'Bytespider', 'Meta-ExternalAgent', 'Amazonbot',
            ];
            foreach ($aiCrawlers as $crawler) {
                $lines[] = '';
                $lines[] = 'User-agent: ' . $crawler;
                $lines[] = 'Allow: /';
                $lines[] = 'Disallow: /admin';
            }
            $lines[] = '';
            $lines[] = '# Fichier dédié aux assistants IA : ' . $siteUrl . '/llms.txt';
        }

        return Response::text(implode("\n", $lines) . "\n");
    }

    /**
     * llms.txt : fichier de notification destiné aux assistants IA
     * (OpenAI, Anthropic, Perplexity…). Généré depuis les données réelles.
     */
    public function llms(Request $request): Response
    {
        $installed = is_file(storage_path('installed.lock'));
        if (!$installed) {
            return Response::text("# Site en cours d'installation\n", 404);
        }

        $company = Company::current();
        $siteUrl = rtrim((string) config('app.url', ''), '/');
        $name = (string) ($company?->getAttribute('trade_name') ?: config('app.name', 'Artisan'));
        $phone = (string) ($company?->getAttribute('phone') ?? '');
        $email = (string) ($company?->getAttribute('public_email') ?? '');
        $city = (string) ($company?->getAttribute('city') ?? '');
        $dept = (string) ($company?->getAttribute('department') ?? '');
        $radius = (int) ($company?->getAttribute('service_radius_km') ?? 0);
        $description = (string) ($company?->getAttribute('short_description') ?: $company?->getAttribute('long_description') ?? '');

        $lines = ['# ' . $name, '', '> ' . ($description !== '' ? $description : 'Artisan local au service de votre habitat.') . ''];

        $lines[] = '## Entreprise';
        $lines[] = '- Nom : ' . $name;
        if ($city !== '') {
            $lines[] = '- Ville : ' . $city . ($dept !== '' ? ' (' . $dept . ')' : '');
        }
        if ($phone !== '') {
            $lines[] = '- Téléphone : ' . $phone;
        }
        if ($email !== '') {
            $lines[] = '- E-mail : ' . $email;
        }
        if ($radius > 0) {
            $lines[] = '- Zone d\'intervention : ' . $city . ' et alentours, rayon d\'environ ' . $radius . ' km';
        }
        $lines[] = '- Devis : gratuit et sans engagement';
        $lines[] = '';

        $services = array_filter(CompanyService::all('sort_order ASC'), static fn ($s) => (bool) $s->getAttribute('is_active'));
        if ($services !== []) {
            $lines[] = '## Services';
            foreach ($services as $service) {
                $slug = (string) $service->getAttribute('slug');
                $lines[] = '- [' . $service->getAttribute('public_name') . '](' . $siteUrl . '/' . $slug . ')' . ($service->getAttribute('description') ? ' : ' . $service->getAttribute('description') : '');
            }
            $lines[] = '';
        }

        $localPages = Page::where(['type' => 'local', 'status' => 'published', 'indexable' => 1], 'title ASC');
        if ($localPages !== []) {
            $lines[] = '## Zones d\'intervention';
            foreach ($localPages as $page) {
                $lines[] = '- [' . $page->getAttribute('title') . '](' . $siteUrl . '/' . $page->getAttribute('slug') . ')';
            }
            $lines[] = '';
        }

        foreach (['À propos' => '/a-propos', 'Réalisations' => '/realisations', 'Avis clients' => '/avis-clients', 'Contact' => '/contact'] as $label => $path) {
            $lines[] = '- [' . $label . '](' . $siteUrl . $path . ')';
        }
        $lines[] = '';
        $lines[] = '## Contact';
        $lines[] = 'Pour toute demande de devis, utiliser le formulaire en ligne ou appeler ' . ($phone !== '' ? $phone : 'le numéro affiché sur le site') . '.';
        $lines[] = '';

        return Response::text(implode("\n", $lines))->withHeader('X-Content-Type-Options', 'nosniff');
    }
}
