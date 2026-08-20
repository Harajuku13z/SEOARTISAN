<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiProvider;
use App\Support\Crypto;

final class AIProviderFactory
{
    public static function fromModel(?AiProvider $model, string $appKey): AIProviderInterface
    {
        if ($model === null) {
            return new NullProvider();
        }

        $provider = (string) $model->getAttribute('provider');
        $modelName = (string) ($model->getAttribute('model') ?? '');
        $baseUrl = (string) ($model->getAttribute('base_url') ?? '');
        $apiKey = self::decryptKey((string) ($model->getAttribute('api_key_encrypted') ?? ''), $appKey);

        return self::build($provider, $apiKey, $modelName, $baseUrl);
    }

    /**
     * Builds a provider directly from raw (not-yet-persisted) form values -
     * used by the installer/admin "test connection" action before the
     * config is saved.
     */
    public static function fromRaw(string $provider, string $apiKey, string $model, string $baseUrl): AIProviderInterface
    {
        return self::build($provider, $apiKey, $model, $baseUrl);
    }

    public static function normalizeProvider(string $provider, string $modelName, string $baseUrl): string
    {
        if (str_starts_with(strtolower($modelName), 'gemini-')
            || str_contains(strtolower($baseUrl), 'generativelanguage.googleapis.com')) {
            return 'gemini';
        }

        return $provider;
    }

    private static function build(string $provider, string $apiKey, string $modelName, string $baseUrl): AIProviderInterface
    {
        // Avoid sending a Gemini AI Studio key to OpenAI when a form kept the
        // previous provider selection while Gemini defaults were entered.
        $provider = self::normalizeProvider($provider, $modelName, $baseUrl);

        return match ($provider) {
            'openai' => new OpenAIProvider($apiKey, $modelName !== '' ? $modelName : 'gpt-4.1-mini', $baseUrl !== '' ? $baseUrl : 'https://api.openai.com/v1'),
            'anthropic' => new AnthropicProvider($apiKey, $modelName !== '' ? $modelName : 'claude-sonnet-5', $baseUrl !== '' ? $baseUrl : 'https://api.anthropic.com/v1'),
            'gemini' => new GeminiProvider($apiKey, $modelName !== '' ? $modelName : 'gemini-2.5-flash', $baseUrl !== '' ? $baseUrl : 'https://generativelanguage.googleapis.com/v1beta'),
            'compatible' => new CompatibleProvider($apiKey, $modelName, $baseUrl),
            default => new NullProvider(),
        };
    }

    private static function decryptKey(string $encrypted, string $appKey): string
    {
        if ($encrypted === '' || $appKey === '') {
            return '';
        }

        return Crypto::decrypt($encrypted, $appKey) ?? '';
    }
}
