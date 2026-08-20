<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private static string $directory;

    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];

    private static string $minLevel = 'info';

    public static function configure(string $directory, string $minLevel = 'info'): void
    {
        self::$directory = rtrim($directory, '/');
        self::$minLevel = $minLevel;
        if (!is_dir(self::$directory)) {
            mkdir(self::$directory, 0775, true);
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    private static function log(string $level, string $message, array $context): void
    {
        if ((self::LEVELS[$level] ?? 1) < (self::LEVELS[self::$minLevel] ?? 1)) {
            return;
        }

        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_UNICODE),
            PHP_EOL
        );

        $file = self::$directory . '/app-' . date('Y-m-d') . '.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
