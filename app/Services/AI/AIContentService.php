<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIContentOutcome;
use App\Models\AiGeneration;
use App\Services\Content\PromptLibrary;

/**
 * Central content generation entry point (prompt.md section 10). Builds a
 * structured prompt, requests strict JSON, validates it, retries once on
 * invalid JSON, and always logs the attempt to ai_generations - a failure
 * here never throws back to the caller, it returns a failed outcome so
 * the caller can create a flagged draft instead.
 */
final class AIContentService
{
    public function __construct(
        private AIProviderInterface $provider,
        private PromptLibrary $prompts,
        private array $aiConfig
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function generate(string $promptType, array $context, ?int $userId = null, ?int $pageId = null): AIContentOutcome
    {
        $built = $this->prompts->build($promptType, $context);
        $temperature = (float) ($this->aiConfig['temperature'] ?? config('ai.default_temperature', 0.6));
        $maxTokens = (int) ($this->aiConfig['max_tokens'] ?? config('ai.default_max_tokens', 2000));

        $result = $this->provider->generate($built['system'], $built['user'], $temperature, $maxTokens);
        $wasRetried = false;

        $decoded = $result->success ? $this->tryDecode($result->rawContent, $built['expected_keys']) : null;

        if ($result->success && $decoded === null) {
            // Invalid JSON - one corrective retry, per prompt.md section 10.
            $wasRetried = true;
            $fixUserPrompt = $built['user'] . "\n\nTa reponse precedente n'etait pas un JSON valide ou complet. "
                . 'Corrige et reponds UNIQUEMENT avec le JSON demande, sans aucun texte autour.';
            $result = $this->provider->generate($built['system'], $fixUserPrompt, $temperature, $maxTokens);
            $decoded = $result->success ? $this->tryDecode($result->rawContent, $built['expected_keys']) : null;
        }

        $success = $result->success && $decoded !== null;
        $estimatedCost = $this->estimateCost($result->tokensUsed);

        AiGeneration::create([
            'provider' => $this->provider->name(),
            'model' => (string) ($this->aiConfig['model'] ?? ''),
            'prompt_type' => $promptType,
            'prompt' => $built['user'],
            'response' => $result->rawContent,
            'tokens_used' => $result->tokensUsed,
            'estimated_cost' => $estimatedCost,
            'status' => $success ? 'success' : ($wasRetried ? 'retried' : 'failed'),
            'error_message' => $success ? null : ($result->errorMessage ?? 'JSON invalide apres nouvelle tentative.'),
            'page_id' => $pageId,
            'user_id' => $userId,
        ]);

        return new AIContentOutcome(
            success: $success,
            data: $decoded,
            rawResponse: $result->rawContent,
            tokensUsed: $result->tokensUsed,
            estimatedCost: $estimatedCost,
            errorMessage: $success ? null : ($result->errorMessage ?? 'Reponse JSON invalide.'),
            wasRetried: $wasRetried
        );
    }

    public function retrySaved(AiGeneration $failed, ?int $userId = null): AIContentOutcome
    {
        $promptType = (string) $failed->getAttribute('prompt_type');
        $built = $this->prompts->build($promptType, []);
        $userPrompt = (string) $failed->getAttribute('prompt');
        $temperature = (float) ($this->aiConfig['temperature'] ?? config('ai.default_temperature', 0.6));
        $maxTokens = (int) ($this->aiConfig['max_tokens'] ?? config('ai.default_max_tokens', 2000));
        $result = $this->provider->generate($built['system'], $userPrompt, $temperature, $maxTokens);
        $decoded = $result->success ? $this->tryDecode($result->rawContent, $built['expected_keys']) : null;
        $success = $result->success && $decoded !== null;

        AiGeneration::create([
            'provider' => $this->provider->name(),
            'model' => (string) ($this->aiConfig['model'] ?? ''),
            'prompt_type' => $promptType,
            'prompt' => $userPrompt,
            'response' => $result->rawContent,
            'tokens_used' => $result->tokensUsed,
            'estimated_cost' => $this->estimateCost($result->tokensUsed),
            'status' => $success ? 'success' : 'failed',
            'error_message' => $success ? null : ($result->errorMessage ?? 'Réponse JSON invalide.'),
            'page_id' => $failed->getAttribute('page_id'),
            'user_id' => $userId,
        ]);

        if ($success) {
            $failed->setAttribute('status', 'retried')->save();
        }

        return new AIContentOutcome(
            success: $success,
            data: $decoded,
            rawResponse: $result->rawContent,
            tokensUsed: $result->tokensUsed,
            estimatedCost: $this->estimateCost($result->tokensUsed),
            errorMessage: $success ? null : ($result->errorMessage ?? 'Réponse JSON invalide.'),
            wasRetried: true
        );
    }

    /**
     * @param array<int,string> $expectedKeys
     * @return array<string,mixed>|null
     */
    private function tryDecode(string $raw, array $expectedKeys): ?array
    {
        $cleaned = trim($raw);
        // Some models wrap JSON in markdown fences despite instructions - strip them defensively.
        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```[a-zA-Z]*\n?|```$/m', '', $cleaned);
            $cleaned = trim((string) $cleaned);
        }

        $decoded = json_decode($cleaned, true);
        if (!is_array($decoded)) {
            return null;
        }

        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $decoded)) {
                return null;
            }
        }

        return $decoded;
    }

    private function estimateCost(?int $tokens): ?float
    {
        if ($tokens === null || $tokens <= 0) {
            return null;
        }

        $model = (string) ($this->aiConfig['model'] ?? '');
        $rates = config('ai.estimated_cost_per_million_tokens', []);
        if (!isset($rates[$model])) {
            return null;
        }

        // Rough 50/50 input/output split - this is a display estimate, not a billing figure.
        $avgRate = ($rates[$model]['input'] + $rates[$model]['output']) / 2;

        return round(($tokens / 1_000_000) * $avgRate, 4);
    }
}
