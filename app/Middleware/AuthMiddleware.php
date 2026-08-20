<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\Auth\AuthService;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (!$this->auth->check()) {
            if ($request->wantsJson()) {
                return Response::json(['message' => 'Non authentifie.'], 401);
            }

            return Response::redirect('/admin/login');
        }

        return $next($request);
    }
}
