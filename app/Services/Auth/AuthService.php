<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Cache;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\User;

/**
 * Session-based admin authentication with file-cache-backed login
 * throttling (no dedicated DB table needed for that - see Core\Cache).
 */
final class AuthService
{
    private const SESSION_KEY = 'auth_user_id';

    public function __construct(
        private array $securityConfig
    ) {
    }

    public function attempt(string $email, string $password, string $ip): bool
    {
        if ($this->isRateLimited($ip, $email)) {
            return false;
        }

        $user = User::first(['email' => $email]);

        if ($user === null || !$user->getAttribute('is_active')) {
            $this->registerFailedAttempt($ip, $email);

            return false;
        }

        if (!password_verify($password, (string) $user->getAttribute('password_hash'))) {
            $this->registerFailedAttempt($ip, $email);

            return false;
        }

        $this->clearRateLimit($ip, $email);
        $this->login($user);

        return true;
    }

    public function login(User $user): void
    {
        Session::regenerate();
        Session::put(self::SESSION_KEY, $user->id());

        $user->setAttribute('last_login_at', date('Y-m-d H:i:s'));
        $user->save();

        ActivityLog::create([
            'user_id' => $user->id(),
            'action' => 'login',
            'description' => 'Connexion administrateur',
        ]);
    }

    public function logout(): void
    {
        $userId = Session::get(self::SESSION_KEY);
        if ($userId !== null) {
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'logout',
                'description' => 'Deconnexion administrateur',
            ]);
        }

        Session::forget(self::SESSION_KEY);
        Session::regenerate();
    }

    public function user(): ?User
    {
        $id = Session::get(self::SESSION_KEY);

        return $id === null ? null : User::find($id);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    private function isRateLimited(string $ip, string $email): bool
    {
        $attempts = (int) Cache::get($this->rateLimitKey($ip, $email), 0);
        $maxAttempts = (int) ($this->securityConfig['login_rate_limit']['max_attempts'] ?? 5);

        return $attempts >= $maxAttempts;
    }

    private function registerFailedAttempt(string $ip, string $email): void
    {
        $key = $this->rateLimitKey($ip, $email);
        $attempts = (int) Cache::get($key, 0) + 1;
        $decayMinutes = (int) ($this->securityConfig['login_rate_limit']['decay_minutes'] ?? 15);

        Cache::put($key, $attempts, $decayMinutes * 60);
    }

    private function clearRateLimit(string $ip, string $email): void
    {
        Cache::forget($this->rateLimitKey($ip, $email));
    }

    private function rateLimitKey(string $ip, string $email): string
    {
        return 'login_attempts:' . $ip . ':' . strtolower($email);
    }
}
