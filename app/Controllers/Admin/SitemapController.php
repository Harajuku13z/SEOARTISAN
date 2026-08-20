<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\CompanyService;
use App\Models\Page;
use App\Services\Auth\AuthService;
use App\Services\Content\WordPressBlogService;
use DOMDocument;
use RuntimeException;
use Throwable;

final class SitemapController extends AdminController
{
    public function __construct(
        protected AuthService $auth,
        private WordPressBlogService $blogService,
    ) {
        parent::__construct($auth);
    }

    public function show(Request $request): Response
    {
        $path = public_path('sitemap.xml');
        $siteUrl = $this->siteUrl();

        return $this->render('admin.pages.sitemap', [
            'pageTitle' => 'Sitemap XML',
            'exists' => is_file($path),
            'lastModified' => is_file($path) ? date('d/m/Y H:i:s', (int) filemtime($path)) : null,
            'sitemapUrl' => $siteUrl . '/sitemap.xml',
        ], 'sitemap');
    }

    public function generate(Request $request): Response
    {
        try {
            $entries = $this->entries();
            $this->write($entries);

            return Response::redirect('/admin/sitemap?success=' . urlencode(
                sprintf('Sitemap mis à jour : %d URL(s).', count($entries))
            ));
        } catch (Throwable $exception) {
            error_log('Sitemap generation failed: ' . $exception->getMessage());

            return Response::redirect('/admin/sitemap?error=' . urlencode(
                'Impossible de générer le sitemap. Vérifiez les droits du dossier public.'
            ));
        }
    }

    /** @return list<array{loc:string,lastmod:?string,priority:string}> */
    private function entries(): array
    {
        $siteUrl = $this->siteUrl();
        $entries = [];

        foreach (Page::where(['status' => 'published']) as $page) {
            if (!$page->getAttribute('indexable')) {
                continue;
            }

            $type = (string) $page->getAttribute('type');
            $slug = trim((string) $page->getAttribute('slug'), '/');
            $path = $type === 'home' || $slug === '' ? '/' : '/' . $slug;
            $this->add($entries, $siteUrl . $path, $page->getAttribute('updated_at'), $type === 'home' ? '1.0' : '0.8');
        }

        foreach (CompanyService::where(['is_active' => 1]) as $service) {
            $slug = trim((string) $service->getAttribute('slug'), '/');
            if ($slug !== '') {
                $this->add($entries, $siteUrl . '/' . $slug, $service->getAttribute('updated_at'), '0.8');
            }
        }

        try {
            $posts = $this->blogService->posts(1, 100);
            if ($posts !== []) {
                $this->add($entries, $siteUrl . '/blog', null, '0.7');
            }
            foreach ($posts as $post) {
                $slug = trim((string) ($post['slug'] ?? ''), '/');
                if ($slug !== '') {
                    $this->add($entries, $siteUrl . '/blog/' . $slug, $post['modified'] ?? $post['date'] ?? null, '0.6');
                }
            }
        } catch (Throwable) {
            // Le blog est facultatif : son indisponibilite ne bloque pas le sitemap principal.
        }

        ksort($entries);
        return array_values($entries);
    }

    /** @param array<string,array{loc:string,lastmod:?string,priority:string}> $entries */
    private function add(array &$entries, string $location, mixed $modified, string $priority): void
    {
        $lastmod = null;
        if (is_string($modified) && $modified !== '') {
            $timestamp = strtotime($modified);
            $lastmod = $timestamp === false ? null : date('Y-m-d', $timestamp);
        }
        $entries[$location] = ['loc' => $location, 'lastmod' => $lastmod, 'priority' => $priority];
    }

    /** @param list<array{loc:string,lastmod:?string,priority:string}> $entries */
    private function write(array $entries): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $document->appendChild($document->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="/sitemap.xsl"'));
        $urlset = $document->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $document->appendChild($urlset);

        foreach ($entries as $entry) {
            $url = $document->createElement('url');
            $url->appendChild($document->createElement('loc'))->appendChild($document->createTextNode($entry['loc']));
            if ($entry['lastmod'] !== null) {
                $url->appendChild($document->createElement('lastmod', $entry['lastmod']));
            }
            $url->appendChild($document->createElement('priority', $entry['priority']));
            $urlset->appendChild($url);
        }

        $xml = $document->saveXML();
        if ($xml === false || file_put_contents(public_path('sitemap.xml'), $xml, LOCK_EX) === false) {
            throw new RuntimeException('Ecriture du sitemap impossible.');
        }
    }

    private function siteUrl(): string
    {
        $url = rtrim((string) config('app.url', ''), '/');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('APP_URL doit contenir une URL absolue valide.');
        }
        return $url;
    }
}
