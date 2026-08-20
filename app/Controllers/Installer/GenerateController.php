<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AiProvider;
use App\Models\Company;
use App\Models\Page;
use App\Models\User;
use App\Repositories\CompanyServiceRepository;
use App\Services\AI\AIContentServiceFactory;
use App\Services\Auth\AuthService;
use App\Services\Content\PageContentBuilder;
use App\Services\Content\StructuralPageBuilder;

final class GenerateController
{
    private const SESSION_QUEUE = '_install_generate_queue';

    private const SESSION_CURSOR = '_install_generate_cursor';

    public function __construct(
        private CompanyServiceRepository $companyServices,
        private PageContentBuilder $pageBuilder,
        private StructuralPageBuilder $structuralBuilder,
        private AuthService $auth
    ) {
    }

    public function show(Request $request): Response
    {
        $queue = $this->buildQueue();
        Session::put(self::SESSION_QUEUE, $queue);
        Session::put(self::SESSION_CURSOR, 0);

        return Response::html(view_layout('installer.layout', 'installer.generate', [
            'stepKey' => 'generate',
            'total' => count($queue),
        ]));
    }

    public function next(Request $request): Response
    {
        $queue = (array) Session::get(self::SESSION_QUEUE, []);
        $cursor = (int) Session::get(self::SESSION_CURSOR, 0);

        if ($cursor >= count($queue)) {
            return Response::json(['done' => true, 'total' => count($queue), 'current' => $cursor]);
        }

        $job = $queue[$cursor];
        $label = $this->processJob($job);

        $cursor++;
        Session::put(self::SESSION_CURSOR, $cursor);

        return Response::json([
            'done' => $cursor >= count($queue),
            'total' => count($queue),
            'current' => $cursor,
            'label' => $label,
        ]);
    }

    public function finish(Request $request): Response
    {
        if (!is_dir(storage_path())) {
            mkdir(storage_path(), 0775, true);
        }
        file_put_contents(storage_path('installed.lock'), date('c') . "\n");

        Session::forget(self::SESSION_QUEUE);
        Session::forget(self::SESSION_CURSOR);

        $admin = User::first(['role' => 'super_admin']);
        if ($admin !== null) {
            $this->auth->login($admin);
        }

        return Response::json(['redirect' => '/admin']);
    }

    /** @return array<int,array<string,mixed>> */
    private function buildQueue(): array
    {
        $company = Company::current();
        $queue = [
            ['kind' => 'ai_page', 'prompt_type' => 'home', 'page_type' => 'home', 'slug' => 'accueil', 'title' => 'Accueil'],
            ['kind' => 'ai_page', 'prompt_type' => 'about', 'page_type' => 'about', 'slug' => 'a-propos', 'title' => 'A propos'],
        ];

        if ($company !== null) {
            foreach ($this->companyServices->forCompany((int) $company->id(), true) as $service) {
                $queue[] = [
                    'kind' => ($service['content_mode'] ?? 'ai') === 'manual' ? 'manual_page' : 'ai_page',
                    'prompt_type' => 'service',
                    'page_type' => 'service',
                    'slug' => $service['slug'],
                    'title' => $service['public_name'],
                    'company_service_id' => (int) $service['id'],
                ];
            }
        }

        $queue[] = ['kind' => 'ai_faq', 'page_type' => 'faq', 'slug' => 'faq', 'title' => 'Questions frequentes'];
        $queue[] = ['kind' => 'structural', 'page_type' => 'contact', 'slug' => 'contact', 'title' => 'Contact'];
        $queue[] = ['kind' => 'structural', 'page_type' => 'realizations', 'slug' => 'realisations', 'title' => 'Realisations'];
        $queue[] = ['kind' => 'structural', 'page_type' => 'zones', 'slug' => 'zones-intervention', 'title' => "Zones d'intervention"];
        $queue[] = ['kind' => 'structural', 'page_type' => 'legal_mentions', 'slug' => 'mentions-legales', 'title' => 'Mentions legales'];
        $queue[] = ['kind' => 'structural', 'page_type' => 'privacy', 'slug' => 'politique-confidentialite', 'title' => 'Politique de confidentialite'];
        $queue[] = ['kind' => 'structural', 'page_type' => 'cookies', 'slug' => 'politique-cookies', 'title' => 'Politique cookies'];

        return $queue;
    }

    /** @param array<string,mixed> $job */
    private function processJob(array $job): string
    {
        $company = Company::current();
        if ($company === null) {
            return (string) $job['title'];
        }

        if ($job['kind'] === 'structural') {
            $this->structuralBuilder->build((string) $job['page_type'], (string) $job['slug'], (string) $job['title'], $company);

            return (string) $job['title'];
        }

        $page = Page::findBySlug((string) $job['slug']) ?? new Page();
        $page->fill([
            'type' => $job['page_type'] ?? 'custom',
            'slug' => (string) $job['slug'],
            'title' => (string) $job['title'],
            'company_service_id' => $job['company_service_id'] ?? null,
        ]);
        $page->save();

        if ($job['kind'] === 'manual_page') {
            $service = \App\Models\CompanyService::find((int) $job['company_service_id']);
            $this->pageBuilder->applyManual(
                $page,
                (string) ($service?->getAttribute('manual_content') ?? ''),
                $company
            );

            return (string) $job['title'] . ' (texte manuel)';
        }

        $ai = AIContentServiceFactory::fromActiveProvider();
        $context = $this->buildContext($job, $company);

        if ($job['kind'] === 'ai_faq') {
            $outcome = $ai->generate('faq', $context, null, (int) $page->id());
            if ($outcome->success && !empty($outcome->data['faq'])) {
                $page->fill([
                    'h1' => (string) $job['title'],
                    'meta_title' => $job['title'] . ' - ' . $company->getAttribute('trade_name'),
                    'status' => 'draft',
                    'content_is_placeholder' => false,
                    'last_generated_at' => date('Y-m-d H:i:s'),
                ]);
                $page->save();
                foreach (\App\Models\PageBlock::where(['page_id' => $page->id()]) as $old) {
                    $old->delete();
                }
                \App\Models\PageBlock::create([
                    'page_id' => $page->id(),
                    'type' => 'faq',
                    'position' => 0,
                    'data' => ['items' => $outcome->data['faq']],
                    'is_active' => true,
                ]);
            } else {
                $this->pageBuilder->applyPlaceholder($page, $outcome->errorMessage ?? 'echec IA');
            }

            return (string) $job['title'];
        }

        $outcome = $ai->generate((string) $job['prompt_type'], $context, null, (int) $page->id());
        if ($outcome->success) {
            $this->pageBuilder->applySuccess($page, $outcome);
        } else {
            $this->pageBuilder->applyPlaceholder($page, $outcome->errorMessage ?? 'echec IA');
        }

        if (($job['page_type'] ?? null) === 'home') {
            // The AI JSON only covers prose (intro/sections/faq/cta) - the
            // homepage also needs the real-data sections from prompt.md
            // section 5 (services, zone, form, realizations, avis).
            $this->pageBuilder->appendStructuralBlocks($page, ['services', 'service_area', 'projects', 'testimonials', 'form']);
        }

        return (string) $job['title'];
    }

    /**
     * @param array<string,mixed> $job
     * @return array<string,mixed>
     */
    private function buildContext(array $job, Company $company): array
    {
        $aiProvider = AiProvider::active();

        $context = [
            'company' => $company->toArray(),
            'tone' => $aiProvider?->getAttribute('tone') ?? config('ai.default_tone'),
            'language' => $aiProvider?->getAttribute('language') ?? config('ai.default_language'),
            'keywords_primary' => array_filter([$company->getAttribute('trade_name')]),
        ];

        if (($job['kind'] ?? null) === 'ai_page' && ($job['prompt_type'] ?? null) === 'service') {
            $service = \App\Models\CompanyService::find((int) $job['company_service_id']);
            $context['service'] = $service?->toArray();
            $context['keywords_primary'] = array_filter([$service?->getAttribute('public_name')]);
        }

        return $context;
    }
}
