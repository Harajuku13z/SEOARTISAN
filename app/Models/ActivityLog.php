<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ActivityLog extends Model
{
    protected static string $table = 'activity_logs';

    protected static array $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address'];

    /** @return array<int,self> */
    public static function recent(int $limit = 50): array
    {
        return self::where([], 'created_at DESC, id DESC', $limit);
    }
}
