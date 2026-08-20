<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Minimal full-page cache for public GET requests (prompt.md section 12:
 * "cache HTML"). Invalidation is manual (admin "purge cache" action) -
 * no per-page dependency tracking in this MVP, which is an accepted
 * trade-off for the scope, not an oversight.
 */
final class PageCacheMiddleware implements MiddlewareInterface
{
    private const TTL_SECONDS = 3600;

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        // Never cache in debug mode - a stale page is confusing enough
        // during development that it isn't worth exercising the cache path.
        if ($request->method() !== 'GET' || (bool) config('app.debug')) {
            return $next($request);
        }

        $key = 'page_cache:' . $request->path();
        $cached = Cache::get($key);
        if (is_string($cached)) {
            return Response::html($cached)->withHeader('X-Cache', 'HIT');
        }

        $response = $next($request);

        // A CSRF token belongs to the visitor's session. Caching HTML that
        // contains one would serve another visitor's token and make every
        // form submission fail with HTTP 419.
        $tokenName = (string) config('security.csrf.token_name', '_csrf_token');
        $containsSessionForm = str_contains($response->content(), 'name="' . $tokenName . '"');

        if ($response->status() === 200 && !$containsSessionForm) {
            Cache::put($key, $response->content(), self::TTL_SECONDS);
        }

        return $response;
    }
}
