<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\DTO\AIContentOutcome;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Company;
use App\Support\Str;

/**
 * Turns an AIContentOutcome's structured JSON (title/h1/introduction/
 * sections/faq/cta) into a Page row + its page_blocks. On failure, builds
 * a clearly-flagged placeholder instead (prompt.md section 4, etape 11:
 * "creer un contenu temporaire clairement identifie").
 */
final class PageContentBuilder
{
    public function applyManual(Page $page, string $content, Company $company): void
    {
        $content = trim($content);
        $title = (string) ($page->getAttribute('title') ?: $page->getAttribute('h1'));
        $page->fill([
            'h1' => (string) ($page->getAttribute('h1') ?: $title),
            'meta_title' => (string) ($page->getAttribute('meta_title') ?: $title . ' - ' . $company->getAttribute('trade_name')),
            'meta_description' => (string) ($page->getAttribute('meta_description') ?: mb_substr(trim(strip_tags($content)), 0, 155)),
            'status' => 'draft',
            'content_is_placeholder' => $content === '',
            'last_generated_at' => null,
        ]);
        $page->save();

        $this->clearBlocks((int) $page->id());
        PageBlock::create([
            'page_id' => $page->id(),
            'type' => 'text',
            'position' => 0,
            'data' => [
                'content' => $content !== '' ? $content : 'Contenu manuel à compléter depuis l’administration.',
            ],
            'is_active' => true,
        ]);
    }

    public function applySuccess(Page $page, AIContentOutcome $outcome): void
    {
        $data = $outcome->data ?? [];

        $page->fill([
            'title' => (string) ($data['title'] ?? $page->getAttribute('title')),
            'h1' => (string) ($data['h1'] ?? ''),
            'meta_title' => (string) ($data['meta_title'] ?? ''),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
            'status' => 'draft',
            'content_is_placeholder' => false,
            'last_generated_at' => date('Y-m-d H:i:s'),
        ]);
        if (empty($page->getAttribute('slug')) && !empty($data['slug'])) {
            $page->setAttribute('slug', Str::slug((string) $data['slug']));
        }
        $page->save();

        $this->clearBlocks((int) $page->id());

        $position = 0;
        if (!empty($data['introduction'])) {
            PageBlock::create([
                'page_id' => $page->id(),
                'type' => 'text',
                'position' => $position++,
                'data' => ['content' => $data['introduction']],
                'is_active' => true,
            ]);
        }

        foreach ((array) ($data['sections'] ?? []) as $section) {
            if (empty($section['content'])) {
                continue;
            }
            PageBlock::create([
                'page_id' => $page->id(),
                'type' => 'text',
                'position' => $position++,
                'data' => ['heading' => $section['heading'] ?? '', 'content' => $section['content']],
                'is_active' => true,
            ]);
        }

        $faqItems = array_values(array_filter((array) ($data['faq'] ?? []), static fn ($f) => !empty($f['question']) && !empty($f['answer'])));
        if ($faqItems !== []) {
            PageBlock::create([
                'page_id' => $page->id(),
                'type' => 'faq',
                'position' => $position++,
                'data' => ['items' => $faqItems],
                'is_active' => true,
            ]);
        }

        if (!empty($data['cta_title']) || !empty($data['cta_text'])) {
            PageBlock::create([
                'page_id' => $page->id(),
                'type' => 'cta',
                'position' => $position++,
                'data' => ['title' => $data['cta_title'] ?? '', 'text' => $data['cta_text'] ?? ''],
                'is_active' => true,
            ]);
        }
    }

    public function applyPlaceholder(Page $page, string $reason): void
    {
        $page->fill([
            'status' => 'draft',
            'content_is_placeholder' => true,
            'last_generated_at' => date('Y-m-d H:i:s'),
        ]);
        $page->save();

        $this->clearBlocks((int) $page->id());

        PageBlock::create([
            'page_id' => $page->id(),
            'type' => 'text',
            'position' => 0,
            'data' => [
                'heading' => 'Contenu a rediger',
                'content' => "Ce contenu n'a pas pu etre genere automatiquement ({$reason}). "
                    . 'Redigez-le manuellement ou relancez la generation depuis l\'administration.',
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Appends blocks that render real data instead of AI prose (services
     * grid, zone chips, realizations, testimonials, quote form) after
     * whatever applySuccess/applyPlaceholder already produced. Used for
     * the home page, which needs both AI text and these real-data
     * sections (prompt.md section 5).
     *
     * @param array<int,string> $types
     */
    public function appendStructuralBlocks(Page $page, array $types): void
    {
        $existing = PageBlock::where(['page_id' => $page->id()]);
        $position = 1;
        foreach ($existing as $block) {
            $position = max($position, (int) $block->getAttribute('position') + 1);
        }

        foreach ($types as $type) {
            PageBlock::create([
                'page_id' => $page->id(),
                'type' => $type,
                'position' => $position++,
                'data' => [],
                'is_active' => true,
            ]);
        }
    }

    private function clearBlocks(int $pageId): void
    {
        foreach (PageBlock::where(['page_id' => $pageId]) as $block) {
            $block->delete();
        }
    }
}
