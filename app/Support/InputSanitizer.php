<?php

namespace App\Support;

class InputSanitizer
{
    public static function digits(?string $value, ?int $limit = null): ?string
    {
        $clean = preg_replace('/\D/', '', (string) $value);

        if ($clean === '') {
            return null;
        }

        return $limit ? substr($clean, 0, $limit) : $clean;
    }

    public static function document(?string $value): ?string
    {
        return self::digits($value, 14);
    }

    public static function phone(?string $value): ?string
    {
        return self::digits($value, 20);
    }

    public static function postcode(?string $value): ?string
    {
        return self::digits($value, 8);
    }

    public static function uf(?string $value): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $value));

        return $clean !== '' ? substr($clean, 0, 2) : null;
    }

    public static function brazilianDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        return $value;
    }
}
