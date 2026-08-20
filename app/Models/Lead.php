<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Lead extends Model
{
    protected static string $table = 'leads';

    protected static array $fillable = [
        'form_submission_id', 'name', 'phone', 'email', 'postal_code', 'city',
        'company_service_id', 'time_slot', 'message', 'source_page_id', 'status',
    ];

    public const STATUSES = ['new', 'abandoned', 'completed', 'to_contact', 'contacted', 'quoted', 'won', 'lost', 'spam'];
}
