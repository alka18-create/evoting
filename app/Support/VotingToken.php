<?php

namespace App\Support;

/**
 * Helper terpusat untuk token voting.
 *
 * - generate(): token acak kuat (default 8 char alfanumerik, dari config evoting).
 * - hash(): HMAC-SHA256 dengan pepper APP_KEY agar bisa dicari via WHERE token_hash
 *   tanpa menyimpan plaintext (memenuhi SEC-07: DB bocor != token bocor).
 * - Plain token hanya ditampilkan sekali saat issue (flash session), tidak di-log.
 */
class VotingToken
{
    public static function length(): int
    {
        return max(8, (int) config('evoting.voting.credential.length', 8));
    }

    public static function alphabet(): string
    {
        $alphabet = (string) config('evoting.voting.credential.alphabet', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');

        return $alphabet !== '' ? $alphabet : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    }

    public static function generate(?int $length = null, ?string $alphabet = null): string
    {
        $length ??= static::length();
        $alphabet ??= static::alphabet();
        $max = strlen($alphabet) - 1;
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= $alphabet[random_int(0, $max)];
        }

        return $token;
    }

    /**
     * Pepper dari APP_KEY (strip prefix base64:).
     */
    public static function pepper(): string
    {
        $key = (string) config('app.key', '');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }

    public static function hash(string $plainToken): string
    {
        return hash_hmac('sha256', $plainToken, static::pepper());
    }

    /**
     * Generate token unik berdasarkan daftar hash yang sudah ada (in-memory)
     * untuk menghindari N+1 query saat bulk issue.
     *
     * @param  array<string,bool>  $existingHashes  [hash => true]
     */
    public static function generateUnique(array &$existingHashes, int $maxRetries = 50): array
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $plain = static::generate();
            $hash = static::hash($plain);

            if (! isset($existingHashes[$hash])) {
                $existingHashes[$hash] = true;

                return [$plain, $hash];
            }
        }

        // Fallback: tetap kembalikan token baru, pemanggil wajib handle
        // unique violation DB dengan retry.
        $plain = static::generate();
        $hash = static::hash($plain);
        $existingHashes[$hash] = true;

        return [$plain, $hash];
    }
}
