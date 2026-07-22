<?php

namespace App\Services\Employee;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * EmployeePhotoService
 *
 * Single Responsibility: handles everything related to employee photo uploads.
 * Separated from the Controller to keep business logic isolated.
 */
class EmployeePhotoService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png'];
    private const MAX_BYTES      = 2 * 1024 * 1024; // 2 MB
    private const THUMB_SIZE     = 300;

    /**
     * Validate, resize, and store an employee profile photo.
     *
     * @throws \InvalidArgumentException on validation failure
     * @throws \RuntimeException on IO failure
     * @return string Web-accessible path e.g. /uploads/employees/emp_123_xxx.jpg
     */
    public function upload(UploadedFile $file, int $empId): string
    {
        // 1. MimeType check — extension alone can be spoofed
        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('نوع الملف غير مدعوم. المسموح به: JPG و PNG فقط.');
        }

        // 2. File size limit
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('حجم الصورة يتجاوز 2MB. يرجى ضغط الصورة أولاً.');
        }

        // 3. Ensure upload directory exists — 0755 not 0777
        $uploadDir = public_path('uploads/employees');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 4. Generate safe filename
        $filename = 'emp_' . $empId . '_' . time() . '.jpg';
        $destPath = $uploadDir . '/' . $filename;

        // 5. Resize and save
        $this->resizeAndSave($file->getRealPath(), $destPath, self::THUMB_SIZE, self::THUMB_SIZE);

        return '/uploads/employees/' . $filename;
    }

    /**
     * Resize image using GD and save as JPEG.
     *
     * @throws \RuntimeException
     */
    private function resizeAndSave(string $sourcePath, string $destPath, int $maxW, int $maxH): void
    {
        [$width, $height, $type] = getimagesize($sourcePath);

        $srcImg = null;
        if ($type === IMAGETYPE_JPEG) {
            $srcImg = imagecreatefromjpeg($sourcePath);
        } elseif ($type === IMAGETYPE_PNG) {
            $srcImg = imagecreatefrompng($sourcePath);
        } else {
            throw new \RuntimeException('نوع الصورة غير مدعوم داخلياً.');
        }

        if (!$srcImg) {
            throw new \RuntimeException('تعذر قراءة الصورة.');
        }

        $ratio     = min($maxW / $width, $maxH / $height, 1.0);
        $newWidth  = (int)($width  * $ratio);
        $newHeight = (int)($height * $ratio);

        $destImg = imagecreatetruecolor($newWidth, $newHeight);
        $white   = imagecolorallocate($destImg, 255, 255, 255);
        imagefill($destImg, 0, 0, $white);

        imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($destImg, $destPath, 85);

        imagedestroy($srcImg);
        imagedestroy($destImg);
    }
}
