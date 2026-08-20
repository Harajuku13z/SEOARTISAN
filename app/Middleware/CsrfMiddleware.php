<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (!in_array($request->method(), self::UNSAFE_METHODS, true)) {
            return $next($request);
        }

        $tokenName = (string) config('security.csrf.token_name', '_csrf_token');
        $token = $request->input($tokenName) ?? $request->header('X-CSRF-Token');

        if (!Csrf::verify(is_string($token) ? $token : null)) {
            if ($request->wantsJson()) {
                return Response::json(['message' => 'Jeton CSRF invalide ou expire.'], 419);
            }

            return Response::html('<h1>419</h1><p>Jeton de securite invalide ou expire. Veuillez recharger la page et reessayer.</p>', 419);
        }

        return $next($request);
    }
}
