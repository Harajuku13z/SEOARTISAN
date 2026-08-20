<?php

declare(strict_types=1);

namespace App\Services\SEO;

use App\Models\BusinessCategory;
use App\Models\Company;
use App\Models\Page;

/**
 * Builds a JSON-LD @graph per page: Organization/LocalBusiness (subtype
 * chosen from the company's business_category.schema_org_type),
 * WebSite, WebPage, BreadcrumbList, and FAQPage/Service when relevant.
 * Never fed fake ratings/reviews - only real company/page data.
 */
final class SchemaOrgBuilder
{
    /**
     * $pageUrl is the already-resolved canonical URL (PageController owns
     * that logic - notably the home page is served at "/", not its
     * "accueil" slug - so it must not be recomputed here).
     *
     * @param array<int,array{label:string,url:?string}> $breadcrumbs
     * @param array<int,array{question:string,answer:string}> $faqItems
     * @return array<string,mixed>
     */
    public function forPage(
        Page $page,
        Company $company,
        ?BusinessCategory $category,
        string $pageUrl,
        array $breadcrumbs = [],
        array $faqItems = []
    ): array {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $orgId = $siteUrl . '/#organization';

        $graph = [
            $this->organization($company, $category, $orgId, $siteUrl),
            $this->website($company, $siteUrl, $orgId),
            $this->webPage($page, $pageUrl, $siteUrl, $orgId),
        ];

        if ($breadcrumbs !== []) {
            $graph[] = $this->breadcrumbList($breadcrumbs, $siteUrl);
        }

        if ($faqItems !== []) {
            $graph[] = $this->faqPage($faqItems, $pageUrl);
        }

        if ($page->getAttribute('type') === 'service') {
            $graph[] = $this->service($page, $company, $orgId, $pageUrl);
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /** @return array<string,mixed> */
    private function organization(Company $company, ?BusinessCategory $category, string $id, string $siteUrl): array
    {
        $type = $category?->getAttribute('schema_org_type') ?: 'HomeAndConstructionBusiness';

        $data = [
            '@type' => [$type, 'LocalBusiness'],
            '@id' => $id,
            'name' => $company->getAttribute('trade_name'),
            'url' => $siteUrl,
        ];

        if ($company->getAttribute('short_description')) {
            $data['description'] = $company->getAttribute('short_description');
        }
        if ($company->getAttribute('phone')) {
            $data['telephone'] = $company->getAttribute('phone');
            $data['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => $company->getAttribute('phone'),
                'contactType' => 'customer service',
            ];
        }
        if ($company->getAttribute('address') || $company->getAttribute('city')) {
            $data['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $company->getAttribute('address'),
                'postalCode' => $company->getAttribute('postal_code'),
                'addressLocality' => $company->getAttribute('city'),
                'addressRegion' => $company->getAttribute('region'),
                'addressCountry' => 'FR',
            ]);
        }
        if ($company->getAttribute('service_radius_km')) {
            $data['areaServed'] = [
                '@type' => 'GeoCircle',
                'geoRadius' => (string) ((int) $company->getAttribute('service_radius_km') * 1000),
            ];
        }

        return array_filter($data);
    }

    /** @return array<string,mixed> */
    private function website(Company $company, string $siteUrl, string $orgId): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $siteUrl . '/#website',
            'url' => $siteUrl,
            'name' => $company->getAttribute('trade_name'),
            'publisher' => ['@id' => $orgId],
        ];
    }

    /** @return array<string,mixed> */
    private function webPage(Page $page, string $pageUrl, string $siteUrl, string $orgId): array
    {
        return array_filter([
            '@type' => 'WebPage',
            '@id' => $pageUrl . '#webpage',
            'url' => $pageUrl,
            'name' => $page->getAttribute('title') ?: $page->getAttribute('h1'),
            'description' => $page->getAttribute('meta_description'),
            'isPartOf' => ['@id' => $siteUrl . '/#website'],
            'about' => ['@id' => $orgId],
        ]);
    }

    /**
     * @param array<int,array{label:string,url:?string}> $items
     * @return array<string,mixed>
     */
    private function breadcrumbList(array $items, string $siteUrl): array
    {
        $itemListElement = [];
        foreach ($items as $i => $item) {
            $itemListElement[] = array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'item' => !empty($item['url']) ? $siteUrl . $item['url'] : null,
            ]);
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElement,
        ];
    }

    /**
     * @param array<int,array{question:string,answer:string}> $items
     * @return array<string,mixed>
     */
    private function faqPage(array $items, string $pageUrl): array
    {
        return [
            '@type' => 'FAQPage',
            '@id' => $pageUrl . '#faq',
            'mainEntity' => array_map(static fn (array $item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ], $items),
        ];
    }

    /** @return array<string,mixed> */
    private function service(Page $page, Company $company, string $orgId, string $pageUrl): array
    {
        return array_filter([
            '@type' => 'Service',
            '@id' => $pageUrl . '#service',
            'name' => $page->getAttribute('title') ?: $page->getAttribute('h1'),
            'description' => $page->getAttribute('meta_description'),
            'provider' => ['@id' => $orgId],
            'areaServed' => $company->getAttribute('city'),
        ]);
    }
}
