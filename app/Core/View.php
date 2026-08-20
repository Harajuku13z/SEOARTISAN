<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Plain-PHP template renderer. Dot notation maps to files under
 * resources/views (e.g. "public.pages.home" -> .../public/pages/home.php).
 *
 * Templates are just PHP files that receive $data as extracted variables
 * plus a $view instance for nested includes. There is no auto-escaping -
 * every dynamic value MUST be passed through the global e() helper.
 */
final class View
{
    private static string $basePath;

    public static function configure(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    /** @param array<string,mixed> $data */
    public static function render(string $template, array $data = []): string
    {
        $path = self::path($template);
        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$template} ({$path})");
        }

        $renderer = static function (string $__template, array $__data) use ($path) {
            extract($__data, EXTR_SKIP);
            ob_start();
            include $path;

            return (string) ob_get_clean();
        };

        return $renderer($template, $data);
    }

    /**
     * Renders $template, injects the result as $content into $layout, then
     * renders $layout. Covers the single-content-region case used by every
     * page in this app (header/nav -> content -> footer).
     *
     * @param array<string,mixed> $data
     */
    public static function make(string $layout, string $template, array $data = []): string
    {
        $content = self::render($template, $data);

        return self::render($layout, array_merge($data, ['content' => $content]));
    }

    public static function exists(string $template): bool
    {
        return is_file(self::path($template));
    }

    private static function path(string $template): string
    {
        return self::$basePath . '/' . str_replace('.', '/', $template) . '.php';
    }
}
