<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Core;

use RuntimeException;

/**
 * Small at-rest secret wrapper for plugin credentials stored in wp_options.
 *
 * The encryption key is derived from WordPress salts, so rotating salts makes
 * existing encrypted values unreadable and requires re-entering credentials.
 */
final class SecretStore
{
    private const PREFIX = 'afe-secret-v1:';
    private const CIPHER = 'aes-256-gcm';

    public function available(): bool
    {
        return function_exists('openssl_encrypt')
            && function_exists('openssl_decrypt')
            && function_exists('openssl_cipher_iv_length');
    }

    public function encrypt(string $plainText): string
    {
        if ($plainText === '') return '';
        if (!$this->available()) {
            throw new RuntimeException('OpenSSL is required to securely store Alavi Form Engine credentials.');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if (!is_int($ivLength) || $ivLength <= 0) {
            throw new RuntimeException('Unable to determine the encryption IV length.');
        }

        $iv = random_bytes($ivLength);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );
        if (!is_string($cipherText) || $cipherText === '' || $tag === '') {
            throw new RuntimeException('Unable to encrypt Alavi Form Engine credentials.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipherText);
    }

    public function decrypt(string $storedValue): string
    {
        if ($storedValue === '') return '';

        // Backward compatibility for an old plaintext value, if one ever
        // existed before SecretStore was introduced. The next settings save
        // re-encrypts it when the administrator enters a replacement secret.
        if (!str_starts_with($storedValue, self::PREFIX)) {
            return $storedValue;
        }
        if (!$this->available()) return '';

        $encoded = substr($storedValue, strlen(self::PREFIX));
        $decoded = base64_decode($encoded, true);
        if (!is_string($decoded)) return '';

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if (!is_int($ivLength) || $ivLength <= 0 || strlen($decoded) <= ($ivLength + 16)) return '';

        $iv = substr($decoded, 0, $ivLength);
        $tag = substr($decoded, $ivLength, 16);
        $cipherText = substr($decoded, $ivLength + 16);
        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return is_string($plainText) ? $plainText : '';
    }

    public function isEncrypted(string $storedValue): bool
    {
        return str_starts_with($storedValue, self::PREFIX);
    }

    private function key(): string
    {
        return hash('sha256', wp_salt('secure_auth') . '|alavi-form-engine|credentials', true);
    }
}
