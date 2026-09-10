<?php

namespace App\Support;

class WaPhoneFormatter
{
    public static function toDigits(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value) ?? '';
    }

    public static function normalizeIndonesian(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '@c.us')) {
            $value = str_replace('@c.us', '', strtolower($value));
        }

        $digits = self::toDigits($value);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        if (strlen($digits) < 10 || strlen($digits) > 16) {
            return null;
        }

        return $digits;
    }

    public static function toE164(string $value): ?string
    {
        $normalized = self::normalizeIndonesian($value);

        return $normalized ? '+'.$normalized : null;
    }

    public static function toWebJid(string $value): ?string
    {
        $normalized = self::normalizeIndonesian($value);

        return $normalized ? $normalized.'@c.us' : null;
    }
}
