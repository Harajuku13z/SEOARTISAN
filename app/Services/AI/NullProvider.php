<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIGenerationResult;

/**
 * "Aucun fournisseur" mode - no API call, ever. AIContentService treats
 * this the same as any other failed generation: draft content, flagged
 * as a placeholder, awaiting manual writing.
 */
final class NullProvider implements AIProviderInterface
{
    public function name(): string
    {
        return 'none';
    }

    public function generate(string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): AIGenerationResult
    {
        return AIGenerationResult::fail('Redaction manuelle : aucun fournisseur IA configure.');
    }

    public function testConnection(): array
    {
        return ['ok' => true, 'message' => 'Mode redaction manuelle - aucune connexion necessaire.'];
    }
}
