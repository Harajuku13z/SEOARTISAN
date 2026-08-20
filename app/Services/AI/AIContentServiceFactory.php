<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiProvider;
use App\Services\Content\PromptLibrary;

/**
 * Builds an AIContentService wired to whichever provider is currently
 * active in the ai_providers table. Not container-auto-wired: the
 * provider choice is a runtime DB value, not a static binding.
 */
final class AIContentServiceFactory
{
    public static function fromActiveProvider(): AIContentService
    {
        $model = AiProvider::active();
        $provider = AIProviderFactory::fromModel($model, (string) config('app.key', ''));

        $aiConfig = [
            'model' => (string) ($model?->getAttribute('model') ?? ''),
            'temperature' => (float) ($model?->getAttribute('temperature') ?? config('ai.default_temperature')),
            'max_tokens' => (int) ($model?->getAttribute('max_tokens') ?? config('ai.default_max_tokens')),
            'language' => (string) ($model?->getAttribute('language') ?? config('ai.default_language')),
            'tone' => (string) ($model?->getAttribute('tone') ?? config('ai.default_tone')),
        ];

        return new AIContentService($provider, new PromptLibrary((array) config('ai')), $aiConfig);
    }
}
