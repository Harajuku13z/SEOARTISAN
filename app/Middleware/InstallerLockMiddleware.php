<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Blocks /install/* once storage/installed.lock exists. To re-run the
 * installer in development, delete that file.
 */
final class InstallerLockMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (is_file(storage_path('installed.lock'))) {
            return Response::redirect('/');
        }

        return $next($request);
    }
}
