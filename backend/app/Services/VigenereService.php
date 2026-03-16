<?php

declare(strict_types=1);

namespace App\Services;

/**
 * VigenereService
 *
 * Implements the Vigenere cipher for alphanumeric characters.
 *
 * - Uppercase letters  → shifted within A-Z (26 chars)
 * - Lowercase letters  → shifted within a-z (26 chars)
 * - Digits             → shifted within 0-9 (10 chars)
 * - All other chars    → passed through unchanged (e.g. '|', '-')
 *
 * The key is cycled through only for chars that are transformed.
 */
class VigenereService
{
    /**
     * Encrypt a plain-text string using the Vigenere cipher.
     *
     * @param  string  $text  Plain text (e.g. a Sanctum token)
     * @param  string  $key   Cipher key (from config('app.vigenere_key'))
     */
    public function encrypt(string $text, string $key): string
    {
        return $this->process($text, $key, encrypt: true);
    }

    /**
     * Decrypt a Vigenere-encrypted string.
     *
     * @param  string  $text  Cipher text
     * @param  string  $key   Cipher key (from config('app.vigenere_key'))
     */
    public function decrypt(string $text, string $key): string
    {
        return $this->process($text, $key, encrypt: false);
    }

    // ──────────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * Core shift logic shared by encrypt() and decrypt().
     */
    private function process(string $text, string $key, bool $encrypt): string
    {
        if ($key === '') {
            return $text;
        }

        $keyLen    = mb_strlen($key);
        $keyIndex  = 0;  // Advances only when a char is actually transformed
        $result    = '';
        $textLen   = mb_strlen($text);

        for ($i = 0; $i < $textLen; $i++) {
            $char    = mb_substr($text, $i, 1);
            $keyChar = mb_strtolower(mb_substr($key, $keyIndex % $keyLen, 1));
            $shift   = $this->keyCharToShift($keyChar);

            if ($shift === null) {
                // Non-alphanumeric key character → skip it in the key cycle
                $keyIndex++;
                $result .= $char;
                continue;
            }

            if ($this->isUppercase($char)) {
                $base    = ord('A');
                $code    = ord($char) - $base;
                $shifted = $encrypt
                    ? ($code + $shift) % 26
                    : (($code - $shift + 26) % 26);
                $result .= chr($shifted + $base);
                $keyIndex++;
            } elseif ($this->isLowercase($char)) {
                $base    = ord('a');
                $code    = ord($char) - $base;
                $shifted = $encrypt
                    ? ($code + $shift) % 26
                    : (($code - $shift + 26) % 26);
                $result .= chr($shifted + $base);
                $keyIndex++;
            } elseif ($this->isDigit($char)) {
                // Digits shift within 0-9 (base-10 space)
                $base    = ord('0');
                $code    = ord($char) - $base;
                $digitShift = $shift % 10;
                $shifted = $encrypt
                    ? ($code + $digitShift) % 10
                    : (($code - $digitShift + 10) % 10);
                $result .= chr($shifted + $base);
                $keyIndex++;
            } else {
                // Non-alphanumeric text char → pass through, do NOT advance key
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Convert a (lowercase) key character to its numeric shift value.
     * Returns null if the key character itself is not a letter.
     */
    private function keyCharToShift(string $char): ?int
    {
        if ($char >= 'a' && $char <= 'z') {
            return ord($char) - ord('a');
        }

        return null;
    }

    private function isUppercase(string $char): bool
    {
        return $char >= 'A' && $char <= 'Z';
    }

    private function isLowercase(string $char): bool
    {
        return $char >= 'a' && $char <= 'z';
    }

    private function isDigit(string $char): bool
    {
        return $char >= '0' && $char <= '9';
    }
}
