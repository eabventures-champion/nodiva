<?php

declare(strict_types=1);

namespace App\Domain\Support;

use Illuminate\Support\Facades\DB;

/**
 * Generates human-friendly, non-sequential references (e.g. RF-26-7K3M9Q) using an unambiguous alphabet.
 * Specimen identifiers include a check character so mis-keyed barcodes are detected.
 */
final class ReferenceGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ'; // no 0/O/1/I/L

    public static function make(string $prefix, string $table, string $column = 'reference', int $length = 6): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = sprintf('%s-%s-%s', $prefix, now()->format('y'), self::random($length));
            if (! DB::table($table)->where($column, $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException("Unable to generate a unique {$prefix} reference.");
    }

    public static function specimen(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $body = now()->format('y').self::random(7);
            $candidate = 'SP'.$body.self::checkChar($body);
            if (! DB::table('specimens')->where('identifier', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to generate a unique specimen identifier.');
    }

    public static function isValidSpecimen(string $identifier): bool
    {
        if (! preg_match('/^SP(\w{9})(\w)$/', $identifier, $m)) {
            return false;
        }

        return self::checkChar($m[1]) === $m[2];
    }

    private static function random(int $length): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    private static function checkChar(string $body): string
    {
        $alphabet = '0123456789'.self::ALPHABET;
        $sum = 0;
        foreach (str_split($body) as $i => $ch) {
            $sum += (strpos($alphabet, $ch) ?: 0) * ($i + 1);
        }

        return self::ALPHABET[$sum % strlen(self::ALPHABET)];
    }
}
