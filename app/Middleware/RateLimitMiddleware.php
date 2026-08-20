<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Generic IP-based throttle for public endpoints (quote/contact forms).
 * Route usage: "App\Middleware\RateLimitMiddleware:devis,10,60"
 * (bucket name, max attempts, decay in seconds).
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $bucket = $params[0] ?? 'default';
        $max = (int) ($params[1] ?? 20);
        $decaySeconds = (int) ($params[2] ?? 60);

        $key = "rate_limit:{$bucket}:" . $request->ip();
        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= $max) {
            return Response::json(['message' => 'Trop de requetes, veuillez patienter.'], 429);
        }

        Cache::put($key, $attempts + 1, $decaySeconds);

        return $next($request);
    }
}
