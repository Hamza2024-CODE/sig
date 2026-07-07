<?php

namespace App\Helpers;

class SecureIdHelper
{
    private static $key = 'SGFEP_Secure_Salt_2026';

    /**
     * Encrypt an integer ID into a short, URL-safe Base64 string.
     */
    public static function encrypt(int $id): string
    {
        $str = (string)$id;
        $result = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $result .= chr(ord($str[$i]) ^ ord(self::$key[$i % strlen(self::$key)]));
        }
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($result));
    }

    /**
     * Decrypt a short URL-safe Base64 string back into an integer ID.
     */
    public static function decrypt(string $hash): ?int
    {
        $data = base64_decode(str_replace(['-', '_'], ['+', '/'], $hash));
        if ($data === false) {
            return null;
        }
        $result = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $result .= chr(ord($data[$i]) ^ ord(self::$key[$i % strlen(self::$key)]));
        }
        return is_numeric($result) ? (int)$result : null;
    }
}
