<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class FormSubmission extends Model
{
    protected static string $table = 'form_submissions';

    protected static array $fillable = ['form_type', 'payload', 'ip_address', 'user_agent', 'is_spam', 'spam_score'];

    protected static array $casts = ['payload' => 'json', 'is_spam' => 'bool', 'spam_score' => 'int'];
}
