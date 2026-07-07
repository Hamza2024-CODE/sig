<?php

namespace App\Services;

/**
 * FileValidator — Secure MIME + Extension + Size Validation
 *
 * Uses PHP's finfo extension to read the true binary signature of a file,
 * completely independent of the client-supplied filename or MIME hint.
 * This defeats extension-spoofing attacks (e.g. shell.php renamed to shell.jpg).
 */
class FileValidator
{
    /**
     * Allowed MIME types per profile category.
     */
    private static array $profiles = [
        'image' => [
            'allowed_mimes'      => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_bytes'          => 2 * 1024 * 1024, // 2 MB
        ],
        'document' => [
            'allowed_mimes'      => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'max_bytes'          => 10 * 1024 * 1024, // 10 MB
        ],
        'logo' => [
            'allowed_mimes'      => ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'],
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'webp'],
            'max_bytes'          => 5 * 1024 * 1024, // 5 MB
        ],
    ];

    /**
     * Validate an uploaded file against a named profile.
     *
     * @param  \Illuminate\Http\UploadedFile $file
     * @param  string                        $profile  'image' | 'document' | 'logo'
     * @return array{ok: bool, error: string|null}
     */
    public static function validate($file, string $profile = 'image'): array
    {
        $cfg = self::$profiles[$profile] ?? self::$profiles['image'];

        // 1. Basic validity (no upload errors)
        if (!$file->isValid()) {
            return ['ok' => false, 'error' => 'الملف المرفوع تالف أو لم يُرفع بشكل صحيح.'];
        }

        // 2. Size check
        if ($file->getSize() > $cfg['max_bytes']) {
            $mb = round($cfg['max_bytes'] / 1024 / 1024);
            return ['ok' => false, 'error' => "حجم الملف يتجاوز الحد المسموح ({$mb} ميغابايت)."];
        }

        // 3. Extension check (client-supplied, first line of defense)
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $cfg['allowed_extensions'], true)) {
            $allowed = implode(', ', $cfg['allowed_extensions']);
            return ['ok' => false, 'error' => "امتداد الملف غير مسموح به. الامتدادات المقبولة: {$allowed}."];
        }

        // 4. True MIME type check via finfo (reads file magic bytes — cannot be spoofed)
        $trueMime = self::detectRealMime($file->getRealPath());
        if ($trueMime === null) {
            return ['ok' => false, 'error' => 'تعذّر التحقق من نوع الملف الحقيقي.'];
        }

        if (!in_array($trueMime, $cfg['allowed_mimes'], true)) {
            return ['ok' => false, 'error' => "نوع الملف الحقيقي ({$trueMime}) غير مسموح به."];
        }

        // 5. Double-extension poison check  (e.g. "evil.php.jpg")
        $originalName = $file->getClientOriginalName();
        $parts = explode('.', $originalName);
        if (count($parts) > 2) {
            // Any intermediate segment that looks like a server-side script extension is blocked
            $dangerousExts = ['php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'cgi', 'pl', 'py', 'sh', 'rb', 'exe'];
            foreach (array_slice($parts, 1, -1) as $segment) {
                if (in_array(strtolower($segment), $dangerousExts, true)) {
                    return ['ok' => false, 'error' => 'اسم الملف يحتوي على امتداد خطير ومتعدد.'];
                }
            }
        }

        return ['ok' => true, 'error' => null];
    }

    /**
     * Detect the actual MIME type of a file by reading its magic bytes.
     */
    private static function detectRealMime(string $path): ?string
    {
        if (!function_exists('finfo_open')) {
            // Fallback: use mime_content_type() if finfo unavailable
            return mime_content_type($path) ?: null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime !== false ? $mime : null;
    }
}
