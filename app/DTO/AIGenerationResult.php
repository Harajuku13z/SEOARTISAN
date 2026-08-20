<?php

declare(strict_types=1);

namespace App\DTO;

final class AIGenerationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $rawContent,
        public readonly ?int $tokensUsed = null,
        public readonly ?string $errorMessage = null
    ) {
    }

    public static function ok(string $content, ?int $tokens = null): self
    {
        return new self(true, $content, $tokens, null);
    }

    public static function fail(string $error): self
    {
        return new self(false, '', null, $error);
    }
}
