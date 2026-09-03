<?php

namespace App\Support;

/**
 * P3-01: TOTP RFC 6238 minimal tanpa dependensi baru (SHA1, 30 detik, 6 digit).
 * Secret base32 32 char (~160 bit). Verifikasi toleransi ±1 step.
 */
class Totp
{
    public const STEP = 30;
    public const DIGITS = 6;
    public const ALGO = 'sha1';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function provisioningUri(string $secret, string $account, string $issuer = 'E-Voting Sekolah'): string
    {
        $label = rawurlencode($issuer . ':' . $account);

        return 'otpauth://totp/' . $label
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    public static function code(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), self::STEP);
        $key = self::base32Decode($secret);
        // 64-bit big-endian counter
        $msg = pack('N*', 0, $counter);
        $hash = hash_hmac(self::ALGO, $msg, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $now + ($i * self::STEP)), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($data) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out .= $alphabet[bindec($chunk)];
        }

        return $out;
    }

    public static function base32Decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, "=\x20\t\n\r\0\x0B"));
        $bits = '';
        foreach (str_split($input) as $c) {
            $pos = strpos($alphabet, $c);
            if ($pos === false) {
                throw new \InvalidArgumentException('Invalid base32 character.');
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $out .= chr(bindec($byte));
        }

        return $out;
    }
}
