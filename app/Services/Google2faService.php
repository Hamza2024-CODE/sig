<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Google2faService
{
    /**
     * Generate a 16-character random Base32 secret key.
     */
    public function generateSecretKey(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generate OTPAuth URL for Google Authenticator.
     */
    public function getQrCodeUrl(string $companyName, string $companyEmail, string $secret): string
    {
        $label = rawurlencode($companyName . ':' . $companyEmail);
        $issuer = rawurlencode($companyName);
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Generate a base64 QR code image using chillerlan/php-qrcode.
     */
    public function getQrCodeImage(string $qrCodeUrl): string
    {
        try {
            return (new QRCode())->render($qrCodeUrl);
        } catch (\Throwable $e) {
            // Fallback: Return raw URL or empty string if library fails
            return '';
        }
    }

    /**
     * Verify a 6-digit TOTP code against a secret key.
     */
    public function verifyOtp(string $secret, string $otp): bool
    {
        if (strlen($otp) !== 6 || !is_numeric($otp)) {
            return false;
        }

        $timeWindow = 1; // Allow 30 seconds drift backward/forward
        $currentTime = floor(time() / 30);

        for ($i = -$timeWindow; $i <= $timeWindow; $i++) {
            if ($this->calculateCode($secret, $currentTime + $i) === (int)$otp) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate TOTP code for a specific counter step.
     */
    private function calculateCode(string $secret, int $counter): int
    {
        $key = $this->base32Decode($secret);
        if ($key === '') {
            return -1;
        }

        // Pack counter as 64-bit big-endian binary
        $binCounter = pack('N*', 0, $counter);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $binCounter, $key, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0xf;
        $tempCode = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        );

        return $tempCode % 1000000;
    }

    /**
     * Decode a Base32 string.
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        
        $buf = 0;
        $len = 0;
        $val = '';
        
        for ($i = 0; $i < strlen($base32); $i++) {
            $c = $base32[$i];
            $pos = strpos($chars, $c);
            if ($pos === false) {
                continue;
            }
            $buf = ($buf << 5) | $pos;
            $len += 5;
            if ($len >= 8) {
                $val .= chr(($buf >> ($len - 8)) & 0xff);
                $len -= 8;
            }
        }
        return $val;
    }

    /**
     * Generate 10 random recovery codes and hash them in DB.
     */
    public function generateRecoveryCodes(int $userId): array
    {
        $plainCodes = [];
        
        // Delete old recovery codes
        DB::table('user_recovery_codes')->where('user_id', $userId)->delete();

        for ($i = 0; $i < 10; $i++) {
            $code = strtoupper(Str::random(10));
            // Format as XXXXX-XXXXX
            $formattedCode = substr($code, 0, 5) . '-' . substr($code, 5, 5);
            $plainCodes[] = $formattedCode;

            DB::table('user_recovery_codes')->insert([
                'user_id'    => $userId,
                'code_hash'  => Hash::make($formattedCode),
                'created_at' => now(),
            ]);
        }

        return $plainCodes;
    }
}
