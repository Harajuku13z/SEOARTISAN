<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Faq extends Model
{
    protected static string $table = 'faqs';

    protected static array $fillable = ['faqable_type', 'faqable_id', 'question', 'answer', 'sort_order'];

    /** @return array<int,self> */
    public static function forSubject(string $type, int $id): array
    {
        return self::where(['faqable_type' => $type, 'faqable_id' => $id], 'sort_order ASC');
    }
}
