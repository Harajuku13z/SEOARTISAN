<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e) use ($debug) {
            self::handle($e, $debug);
        });

        register_shutdown_function(static function () use ($debug) {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                Logger::critical('Fatal error', $error);
                if (!headers_sent()) {
                    http_response_code(500);
                }
                echo self::renderFallback($debug, $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
            }
        });
    }

    public static function handle(Throwable $e, bool $debug): void
    {
        Logger::error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $debug ? $e->getTraceAsString() : null,
        ]);

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $detail = $debug ? ($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()) : null;
        $view = dirname(__DIR__, 2) . '/resources/views/public/errors/500.php';

        if (is_file($view)) {
            $debugDetail = $detail;
            $trace = $debug ? $e->getTraceAsString() : null;
            include $view;

            return;
        }

        echo self::renderFallback($debug, $detail);
    }

    private static function renderFallback(bool $debug, ?string $detail): string
    {
        $html = '<!doctype html><html lang="fr"><head><meta charset="utf-8">'
            . '<title>Erreur serveur</title></head><body style="font-family:sans-serif;padding:40px">'
            . '<h1>Une erreur est survenue</h1><p>Veuillez reessayer dans quelques instants.</p>';

        if ($debug && $detail !== null) {
            $html .= '<pre style="white-space:pre-wrap;background:#f5f5f5;padding:16px;border-radius:8px">'
                . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        return $html . '</body></html>';
    }
}
