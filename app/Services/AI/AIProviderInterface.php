<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIGenerationResult;

/**
 * Common contract for every AI backend. Swapping providers never touches
 * calling code (AIContentService, installer, admin) - only which
 * implementation is bound.
 */
interface AIProviderInterface
{
    public function generate(string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): AIGenerationResult;

    /** @return array{ok:bool,message:string} */
    public function testConnection(): array;

    public function name(): string;
}
