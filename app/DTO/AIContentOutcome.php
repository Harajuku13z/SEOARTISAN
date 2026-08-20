<?php

declare(strict_types=1);

namespace App\DTO;

final class AIContentOutcome
{
    /** @param array<string,mixed>|null $data */
    public function __construct(
        public readonly bool $success,
        public readonly ?array $data,
        public readonly string $rawResponse,
        public readonly ?int $tokensUsed,
        public readonly ?float $estimatedCost,
        public readonly ?string $errorMessage,
        public readonly bool $wasRetried
    ) {
    }
}
