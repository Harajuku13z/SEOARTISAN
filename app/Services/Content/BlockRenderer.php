<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Core\View;
use App\Models\PageBlock;

/**
 * Dispatches a PageBlock to its resources/views/public/blocks/{type}.php
 * renderer. Unknown/inactive types render nothing rather than erroring -
 * a page must never break because of one bad block.
 */
final class BlockRenderer
{
    public function render(PageBlock $block): string
    {
        if (!$block->getAttribute('is_active')) {
            return '';
        }

        $viewName = 'public.blocks.' . $block->getAttribute('type');
        if (!View::exists($viewName)) {
            return '';
        }

        return View::render($viewName, ['data' => (array) ($block->getAttribute('data') ?? [])]);
    }

    /** @param array<int,PageBlock> $blocks */
    public function renderAll(array $blocks): string
    {
        return implode('', array_map([$this, 'render'], $blocks));
    }
}
