<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AiGeneration extends Model
{
    protected static string $table = 'ai_generations';

    protected static array $fillable = [
        'provider', 'model', 'prompt_type', 'prompt', 'response', 'tokens_used',
        'estimated_cost', 'status', 'error_message', 'page_id', 'user_id',
    ];

    protected static array $casts = [
        'tokens_used' => 'int',
        'estimated_cost' => 'float',
    ];
}
