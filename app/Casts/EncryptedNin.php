<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Cast: EncryptedNin
 *
 * يُشفِّر NIN تلقائياً عند الحفظ ويفكّه عند القراءة.
 * خلال فترة الانتقال يتعامل مع القيم القديمة (نص صريح).
 *
 * الاستخدام:
 *   protected $casts = ['nin' => EncryptedNin::class];
 *
 * @conformant ISO 27001 A.8.11 | RGPD Art. 9
 */
class EncryptedNin implements CastsAttributes
{
    public function get($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value); // مشفَّر → فكّ التشفير
        } catch (DecryptException) {
            return $value; // نص صريح (legacy) → إرجاع كما هو مؤقتاً
        }
    }

    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // لا تشفِّر مرتين
        try {
            Crypt::decryptString($value);
            return $value; // مشفَّر مسبقاً
        } catch (DecryptException) {
            return Crypt::encryptString($value); // تشفير الآن
        }
    }
}
