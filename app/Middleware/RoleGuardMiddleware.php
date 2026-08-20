<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth\AuthService;
use Closure;

/**
 * Route usage: "App\Middleware\RoleGuardMiddleware:super_admin,admin"
 * Always pair with AuthMiddleware first in the group so a user is
 * guaranteed to be present here.
 */
final class RoleGuardMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $user = $this->auth->user();
        $allowedRoles = $params;

        if ($user === null || ($allowedRoles !== [] && !in_array($user->getAttribute('role'), $allowedRoles, true))) {
            if ($request->wantsJson()) {
                return Response::json(['message' => 'Acces refuse.'], 403);
            }

            return Response::html('<h1>403</h1><p>Acces refuse.</p>', 403);
        }

        return $next($request);
    }
}
