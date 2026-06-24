<?php

namespace App\Support;

class TextFormatter
{
    public static function title(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value));

        if ($value === '') {
            return $value;
        }

        return preg_replace_callback('/(^|[\s\-\/])(\p{L})/u', function (array $matches) {
            return $matches[1].mb_strtoupper($matches[2], 'UTF-8');
        }, $value);
    }

    public static function sentence(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        $parts = preg_split('/(\p{L}[\p{L}\p{M}]*)/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $value;
        }

        $result = '';
        $startsSentence = true;

        foreach ($parts as $index => $part) {
            if ($index % 2 === 1) {
                $letterCount = preg_match_all('/\p{L}/u', $part);
                $result .= ($startsSentence || $letterCount >= 3) ? self::uppercaseFirstLetter($part) : $part;
                $startsSentence = false;
                continue;
            }

            $result .= $part;

            if (preg_match('/[.\r\n]/u', $part)) {
                $startsSentence = true;
            }
        }

        return $result;
    }

    public static function paragraph(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        return preg_replace_callback('/(^|[.!?]\s+|\R+\s*)(\p{L})/u', function (array $matches) {
            return $matches[1].mb_strtoupper($matches[2], 'UTF-8');
        }, $value) ?? $value;
    }

    private static function uppercaseFirstLetter(string $value): string
    {
        return preg_replace_callback('/\p{L}/u', function (array $matches) {
            return mb_strtoupper($matches[0], 'UTF-8');
        }, $value, 1) ?? $value;
    }
}
