<?php

declare(strict_types=1);

namespace App\Support;

final class Str
{
    private const ACCENTS = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ñ' => 'n',
        'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o', 'ø' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'œ' => 'oe', 'æ' => 'ae',
        'ß' => 'ss',
    ];

    public static function slug(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, self::ACCENTS);
        $value = (string) preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim($value, '-');

        return $value === '' ? 'n-a' : $value;
    }

    public static function limit(string $value, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $length) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $length, 'UTF-8')) . $suffix;
    }

    public static function random(int $length = 32): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }

    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Turns plain admin-authored text into safe HTML: blank lines start a
     * new paragraph, single line breaks become <br>, and a block where
     * every line starts with -/•/* becomes a <ul>. Every text fragment is
     * escaped - only the p/ul/li/br structure is real markup.
     */
    public static function richText(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return '';
        }

        $html = '';
        foreach (preg_split('/\n{2,}/', $text) ?: [] as $block) {
            $lines = array_values(array_filter(
                array_map('trim', explode("\n", trim($block))),
                static fn(string $line): bool => $line !== ''
            ));
            if ($lines === []) {
                continue;
            }

            $isList = true;
            foreach ($lines as $line) {
                if (!preg_match('/^[-•*]\s+/', $line)) {
                    $isList = false;
                    break;
                }
            }

            if ($isList) {
                $html .= '<ul>';
                foreach ($lines as $line) {
                    $html .= '<li>' . e(preg_replace('/^[-•*]\s+/', '', $line)) . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p>' . implode('<br>', array_map('e', $lines)) . '</p>';
            }
        }

        return $html;
    }
}
