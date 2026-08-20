<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Media extends Model
{
    protected static string $table = 'media';

    protected static array $fillable = [
        'disk_path', 'url', 'mime_type', 'size_bytes', 'width', 'height', 'alt_text', 'type', 'uploaded_by',
    ];
}
