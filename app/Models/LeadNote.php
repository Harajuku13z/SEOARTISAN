<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class LeadNote extends Model
{
    protected static string $table = 'lead_notes';

    protected static array $fillable = ['lead_id', 'author_id', 'note'];

    /** @return array<int,self> */
    public static function forLead(int $leadId): array
    {
        return self::where(['lead_id' => $leadId], 'created_at DESC');
    }
}
