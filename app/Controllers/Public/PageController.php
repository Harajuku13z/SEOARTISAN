<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Models\BusinessCategory;
use App\Models\Company;
use App\Core\Request;
use App\Core\Response;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Testimonial;
use App\Services\Content\BlockRenderer;
use App\Services\Content\PublicViewData;
use App\Services\Content\WordPressBlogService;
use App\Services\SEO\SchemaOrgBuilder;

final class PageController
{
    public function __construct(
        private PublicViewData $viewData,
        private BlockRenderer $blocks,
        private SchemaOrgBuilder $schema,
        private WordPressBlogService $blog
    ) {
    }

    public function home(Request $request): Response
    {
        $page = Page::findBySlug('accueil');
        if ($page === null || !$this->isViewable($page)) {
            return $this->notFound();
        }

        $blocks = PageBlock::forPage((int) $page->id());
        $base = $this->viewData->base();
        $canonicalUrl = $this->canonicalUrl($page);
        try { $latestBlogPosts = $this->blog->posts(1, 3); } catch (\Throwable) { $latestBlogPosts = []; }

        $data = array_merge($base, [
            'page' => $page,
            'pageTitle' => null,
            'metaDescription' => $page->getAttribute('meta_description'),
            'canonicalUrl' => $canonicalUrl,
            'ogImageUrl' => $this->ogImageUrl($page, $base['company'] ?? null),
            'jsonLd' => $this->buildJsonLd($page, $base['company'] ?? null, $blocks, [], $canonicalUrl),
            'blocksHtml' => $this->blocks->renderAll($blocks),
            'latestBlogPosts' => $latestBlogPosts,
        ]);

        return Response::html(view_layout('public.layouts.main', 'public.pages.home_editorial', $data));
    }

    public function bySlug(Request $request, array $params): Response
    {
        $slug = (string) ($params['slug'] ?? '');
        $page = Page::findBySlug($slug);

        if ($page === null || !$this->isViewable($page) || $page->getAttribute('type') === 'home') {
            $redirect = \App\Models\Redirect::findActiveFor($request->path());
            if ($redirect !== null) {
                $redirect->setAttribute('hit_count', (int) $redirect->getAttribute('hit_count') + 1);
                $redirect->save();

                return Response::redirect((string) $redirect->getAttribute('to_path'), (int) $redirect->getAttribute('status_code'));
            }

            return $this->notFound();
        }

        $blocks = PageBlock::forPage((int) $page->id());
        $base = $this->viewData->base();
        $breadcrumbs = [
            ['label' => 'Accueil', 'url' => '/'],
            ['label' => $page->getAttribute('title') ?: $page->getAttribute('h1'), 'url' => null],
        ];

        $canonicalUrl = $this->canonicalUrl($page);

        $data = array_merge($base, [
            'page' => $page,
            'pageTitle' => $page->getAttribute('meta_title') ?: $page->getAttribute('title') ?: $page->getAttribute('h1'),
            'metaDescription' => $page->getAttribute('meta_description'),
            'canonicalUrl' => $canonicalUrl,
            'ogImageUrl' => $this->ogImageUrl($page, $base['company'] ?? null),
            'breadcrumbs' => $breadcrumbs,
            'jsonLd' => $this->buildJsonLd($page, $base['company'] ?? null, $blocks, $breadcrumbs, $canonicalUrl),
            'blocksHtml' => $this->blocks->renderAll($blocks),
        ]);

        $hasDomainServices = false;
        foreach ($blocks as $block) if ($block->getAttribute('type') === 'domain_services') { $hasDomainServices = true; break; }
        $isMenuGroup = false;
        foreach (\App\Services\Content\MenuService::tree() as $menuRoot) {
            if (($menuRoot['url'] ?? '') === '/' . ltrim($slug, '/') && !empty($menuRoot['children'])) {
                $isMenuGroup = true;
                break;
            }
        }
        $view = $slug === 'contact'
            ? 'public.pages.contact'
            : ($slug === 'realisations'
                ? 'public.pages.realisations'
                : ($slug === 'a-propos'
                    ? 'public.pages.about'
                    : (($hasDomainServices || $isMenuGroup) ? 'public.pages.category' : ($page->getAttribute('type') === 'service' ? 'public.pages.service' : 'public.pages.generic'))));
        return Response::html(view_layout('public.layouts.main', $view, $data));
    }

    public function reviews(Request $request): Response
    {
        $base = $this->viewData->base();
        $reviews = Testimonial::visible();
        $ratings = array_values(array_filter(array_map(static fn ($review) => (int)$review->getAttribute('rating'), $reviews)));
        $average = $ratings ? array_sum($ratings) / count($ratings) : null;
        $canonicalUrl = rtrim((string)config('app.url'), '/') . '/avis-clients';

        $data = array_merge($base, [
            'pageTitle' => 'Avis clients',
            'metaDescription' => 'Découvrez les avis de nos clients sur nos installations, notre accompagnement et notre service après-vente.',
            'canonicalUrl' => $canonicalUrl,
            'reviews' => $reviews,
            'average' => $average,
            'reviewCount' => count($reviews),
        ]);

        return Response::html(view_layout('public.layouts.main', 'public.pages.reviews', $data));
    }

    public function success(Request $request): Response
    {
        $base = $this->viewData->base();
        return Response::html(view_layout('public.layouts.main', 'public.pages.success', array_merge($base, [
            'pageTitle' => 'Demande envoyée',
            'metaDescription' => 'Votre demande a bien été transmise à notre équipe.',
            'canonicalUrl' => null,
            'jsonLd' => null,
        ])));
    }

    public function quoteSimulator(Request $request): Response
    {
        $base = $this->viewData->base();

        return Response::html(view_layout('public.layouts.main', 'public.pages.quote_simulator', array_merge($base, [
            'pageTitle' => 'Simulateur de devis',
            'metaDescription' => 'Décrivez précisément votre projet en quelques étapes et recevez une étude personnalisée.',
            'canonicalUrl' => rtrim((string) config('app.url'), '/') . '/simulateur-de-devis',
            'jsonLd' => null,
        ])));
    }

    public function aidesSimulator(Request $request): Response
    {
        $base = $this->viewData->base();

        return Response::html(view_layout('public.layouts.main', 'public.pages.aides_simulator', array_merge($base, [
            'pageTitle' => 'Simulateur d\'Aides Rénovation Énergétique',
            'metaDescription' => 'Simulez gratuitement et en 2 minutes le montant de vos aides (MaPrimeRénov\' et Prime CEE) pour votre projet de chauffage et de climatisation.',
            'canonicalUrl' => rtrim((string) config('app.url'), '/') . '/simulateur-aides',
            'jsonLd' => null,
        ])));
    }

    private function isViewable(Page $page): bool
    {
        if ($page->getAttribute('type') === 'local') {
            return $page->getAttribute('status') === 'published' && (bool) $page->getAttribute('indexable');
        }
        return $page->getAttribute('status') !== 'archived';
    }

    private function canonicalUrl(Page $page): string
    {
        $slug = (string) $page->getAttribute('slug');
        $siteUrl = rtrim((string) config('app.url'), '/');

        // The home page is served at "/", not "/accueil" - its internal
        // slug - even though that slug also resolves via findBySlug().
        if ($page->getAttribute('type') === 'home') {
            return $siteUrl . '/';
        }

        return $siteUrl . '/' . ltrim($slug, '/');
    }

    private function ogImageUrl(Page $page, ?Company $company): ?string
    {
        $mediaId = $page->getAttribute('og_media_id') ?: $company?->getAttribute('logo_main_media_id') ?: $company?->getAttribute('hero_media_id');
        if (!$mediaId) {
            return null;
        }

        $media = Media::find((int) $mediaId);
        $url = $media?->getAttribute('url');

        return $url ? rtrim((string) config('app.url'), '/') . $url : null;
    }

    /**
     * @param array<int,PageBlock> $blocks
     * @param array<int,array{label:string,url:?string}> $breadcrumbs
     * @return array<string,mixed>|null
     */
    private function buildJsonLd(Page $page, ?Company $company, array $blocks, array $breadcrumbs, string $pageUrl): ?array
    {
        if ($company === null) {
            return null;
        }

        $faqItems = [];
        foreach ($blocks as $block) {
            if ($block->getAttribute('type') === 'faq' && $block->getAttribute('is_active')) {
                foreach ((array) ($block->getAttribute('data')['items'] ?? []) as $item) {
                    if (!empty($item['question']) && !empty($item['answer'])) {
                        $faqItems[] = ['question' => $item['question'], 'answer' => $item['answer']];
                    }
                }
            }
        }

        $category = $company->getAttribute('business_category_id')
            ? BusinessCategory::find((int) $company->getAttribute('business_category_id'))
            : null;

        return $this->schema->forPage($page, $company, $category, $pageUrl, $breadcrumbs, $faqItems);
    }

    private function notFound(): Response
    {
        $data = $this->viewData->base();
        $data['pageTitle'] = 'Page introuvable';
        $data['metaDescription'] = '';

        $body = view_layout('public.layouts.main', 'public.pages.not_found', $data);

        return Response::html($body, 404);
    }
}
