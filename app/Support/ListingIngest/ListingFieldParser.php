<?php

declare(strict_types=1);

namespace App\Support\ListingIngest;

final class ListingFieldParser
{
    public static function splitAddress(string $raw): array
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $raw));
        $normalized = (string) preg_replace('/\s*,\s*/u', ', ', $normalized);

        $parts = array_values(array_filter(array_map('trim', explode(',', $normalized)), static fn (string $p): bool => $p !== ''));

        $result = [
            'street' => null,
            'zip' => null,
            'city' => null,
            'state' => null,
            'region' => null,
            'country' => null,
            'original' => $raw,
        ];

        if (count($parts) === 0) {
            return $result;
        }

        $result['country'] = $parts[count($parts) - 1] ?? null;

        if (isset($parts[0]) && preg_match('/\d+\s*[a-zA-Z]?$/u', $parts[0]) === 1) {
            $result['street'] = $parts[0];
            array_shift($parts);
        }

        if (count($parts) > 1) {
            array_pop($parts);
        } elseif (count($parts) === 1 && $parts[0] === $result['country']) {
            $parts = [];
        }

        foreach ($parts as $index => $part) {
            if (preg_match('/\b(\d{5})\s+(.+)\b/u', $part, $m) === 1) {
                $result['zip'] = $m[1];
                $result['city'] = trim($m[2]);
                unset($parts[$index]);
                break;
            }
        }

        $parts = array_values($parts);
        if ($result['city'] === null) {
            if (isset($parts[0])) {
                $result['city'] = $parts[0];
            }
            if (isset($parts[1])) {
                $result['state'] = $parts[1];
            }
            if (isset($parts[2])) {
                $result['region'] = $parts[2];
            }
        } else {
            if (isset($parts[0])) {
                $result['state'] = $parts[0];
            }
            if (isset($parts[1])) {
                $result['region'] = $parts[1];
            }
        }

        return $result;
    }

    public static function priceToCents(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace(["\xc2\xa0", '€', ' '], '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    public static function percentToFloat(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim(str_replace('%', '', $value));
        $value = str_replace(',', '.', $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public static function decodeSubject(string $rawSubject): string
    {
        if ($rawSubject === '') {
            return '';
        }

        if (!function_exists('iconv_mime_decode')) {
            return $rawSubject;
        }

        $decoded = @iconv_mime_decode($rawSubject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        return is_string($decoded) ? $decoded : $rawSubject;
    }

    public static function extractDeclaredListingCount(string $subject): ?int
    {
        if (preg_match('/\b(\d+)\s+neue\s+Immobilie(?:\/n|n)?\b/i', $subject, $m) !== 1) {
            return null;
        }

        return (int) $m[1];
    }

    public static function computeHash(array $item): string
    {
        return hash(
            'sha256',
            strtolower(
                (string) ($item['title'] ?? '')
                . '|'
                . (string) ($item['address'] ?? '')
                . '|'
                . (string) ($item['price'] ?? '')
            )
        );
    }
}
