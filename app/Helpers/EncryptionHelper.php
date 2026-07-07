<?php

namespace App\Helpers;

/**
 * EncryptionHelper
 *
 * Provides symmetric AES-256-CBC encryption/decryption using the APP_KEY
 * environment variable as the master secret.
 *
 * Usage:
 *   $encrypted = EncryptionHelper::encrypt('my-api-key');
 *   $plain     = EncryptionHelper::decrypt($encrypted);
 */
class EncryptionHelper
{
    private const CIPHER = 'AES-256-CBC';
    private const MAC_ALGO = 'sha256';

    // -------------------------------------------------------------------------
    // Derive a 32-byte key from APP_KEY (strips the 'base64:' prefix if present)
    // -------------------------------------------------------------------------
    private static function getMasterKey(): string
    {
        $appKey = $_ENV['APP_KEY'] ?? '';

        if (empty($appKey)) {
            throw new \RuntimeException(
                'APP_KEY is not set in environment. Cannot encrypt/decrypt data.'
            );
        }

        // Handle Laravel-style "base64:..." key format
        if (strpos($appKey, 'base64:') === 0) {
            $raw = base64_decode(substr($appKey, 7), true);
            if ($raw === false) {
                throw new \RuntimeException('APP_KEY has invalid base64 encoding.');
            }
            // Ensure exactly 32 bytes for AES-256
            return substr(str_pad($raw, 32, "\0"), 0, 32);
        }

        // Plain string key – derive 32 bytes via SHA-256
        return hash('sha256', $appKey, true);
    }

    // -------------------------------------------------------------------------
    // Encrypt a plain-text string.
    // Returns: base64( iv + ciphertext + hmac ) or throws on failure.
    // -------------------------------------------------------------------------
    public static function encrypt(string $plaintext): string
    {
        $key    = self::getMasterKey();
        $ivLen  = openssl_cipher_iv_length(self::CIPHER);
        $iv     = random_bytes($ivLen);

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        // HMAC over iv+ciphertext for integrity verification
        $mac = hash_hmac(self::MAC_ALGO, $iv . $ciphertext, $key, true);

        // Package as: base64( iv | ciphertext | mac )
        return base64_encode($iv . $ciphertext . $mac);
    }

    // -------------------------------------------------------------------------
    // Encrypt a plain-text string deterministically (using derived IV).
    // Returns: base64( iv + ciphertext + hmac ) or throws on failure.
    // -------------------------------------------------------------------------
    public static function encryptDeterministic(string $plaintext): string
    {
        $key    = self::getMasterKey();
        $ivLen  = openssl_cipher_iv_length(self::CIPHER);
        
        // Derive IV deterministically from the plaintext using hash_hmac
        $ivRaw  = hash_hmac('sha256', $plaintext, $key, true);
        $iv     = substr($ivRaw, 0, $ivLen);

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        // HMAC over iv+ciphertext for integrity verification
        $mac = hash_hmac(self::MAC_ALGO, $iv . $ciphertext, $key, true);

        // Package as: base64( iv | ciphertext | mac )
        return base64_encode($iv . $ciphertext . $mac);
    }

    // -------------------------------------------------------------------------
    // Decrypt a previously encrypted string.
    // Returns the original plaintext or null on failure/tampering.
    // -------------------------------------------------------------------------
    public static function decrypt(string $payload): ?string
    {
        try {
            $key   = self::getMasterKey();
            $raw   = base64_decode($payload, true);
            if ($raw === false) {
                return null;
            }

            $ivLen      = openssl_cipher_iv_length(self::CIPHER);
            $macLen     = 32; // sha256 produces 32 raw bytes
            $minLen     = $ivLen + $macLen + 1;

            if (strlen($raw) < $minLen) {
                return null; // Too short to be valid
            }

            $iv         = substr($raw, 0, $ivLen);
            $mac        = substr($raw, -$macLen);
            $ciphertext = substr($raw, $ivLen, -$macLen);

            // Verify HMAC before decrypting (prevents padding oracle attacks)
            $expectedMac = hash_hmac(self::MAC_ALGO, $iv . $ciphertext, $key, true);
            if (!hash_equals($expectedMac, $mac)) {
                error_log('[SECURITY] EncryptionHelper: HMAC mismatch – possible tampering detected.');
                return null;
            }

            $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            return ($plaintext === false) ? null : $plaintext;

        } catch (\Throwable $e) {
            error_log('[EncryptionHelper::decrypt] Error: ' . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Safe display helper – returns a masked version of the key for UI display.
    // e.g.  sgfep_live_1234...abcd
    // -------------------------------------------------------------------------
    public static function maskedKey(?string $plainKey): string
    {
        if (empty($plainKey)) {
            return '—';
        }
        $len = strlen($plainKey);
        if ($len <= 12) {
            return str_repeat('*', $len);
        }
        return substr($plainKey, 0, 12) . '...' . substr($plainKey, -4);
    }
}
