<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

interface MiddlewareInterface
{
    /**
     * $params come from route registrations written as
     * "App\Middleware\RoleGuardMiddleware:super_admin,admin" - split on
     * the first ":" then exploded on ",". Middleware that take no
     * parameters simply ignore them.
     */
    public function handle(Request $request, Closure $next, string ...$params): Response;
}
