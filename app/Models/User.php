<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected static string $table = 'users';

    protected static array $fillable = [
        'first_name', 'last_name', 'email', 'password_hash', 'role', 'is_active', 'last_login_at',
    ];

    protected static array $casts = ['is_active' => 'bool'];

    public function fullName(): string
    {
        return trim($this->getAttribute('first_name') . ' ' . $this->getAttribute('last_name'));
    }

    public function isSuperAdmin(): bool
    {
        return $this->getAttribute('role') === 'super_admin';
    }
}
