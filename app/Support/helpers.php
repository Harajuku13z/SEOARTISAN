<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

if (!function_exists('e')) {
    /**
     * The one escaping helper every view MUST use for dynamic output.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return rtrim((string) config('app.paths.base'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return rtrim((string) config('app.paths.app'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return rtrim((string) config('app.paths.config'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return rtrim((string) config('app.paths.database'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return rtrim((string) config('app.paths.public'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('resource_path')) {
    function resource_path(string $path = ''): string
    {
        return rtrim((string) config('app.paths.resources'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return rtrim((string) config('app.paths.storage'), '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return rtrim((string) config('app.url'), '/') . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $old = Session::getFlash('_old_input', []);

        return e($old[$key] ?? $default);
    }
}

if (!function_exists('flash_errors')) {
    /** @return array<string,string> */
    function flash_errors(): array
    {
        return Session::getFlash('_errors', []);
    }
}

if (!function_exists('flash_message')) {
    function flash_message(string $key = 'success'): ?string
    {
        return Session::getFlash($key);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        return View::render($template, $data);
    }
}

if (!function_exists('view_layout')) {
    function view_layout(string $layout, string $template, array $data = []): string
    {
        return View::make($layout, $template, $data);
    }
}
