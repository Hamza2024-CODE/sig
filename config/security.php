<?php

/**
 * config/security.php
 *
 * إعدادات الأمان المركزية للمنصة
 * المعايير: ISO/IEC 27001 | OWASP
 *
 * ⚠️ قاعدة ذهبية:
 *   - لا تضع أي سر (Password/Key) بنص صريح هنا.
 *   - كل القيم الحساسة يجب أن تأتي من متغيرات البيئة (.env)
 *   - في الإنتاج: استخدم متغيرات بيئة النظام بدلاً من .env
 */
return [

    // ─── HFSQL Remote Connection ──────────────────────────────────────────────
    'hfsql' => [
        'dsn'      => env('HFSQL_DSN', 'Driver={HFSQL};Server Name=127.0.0.1;Server Port=4900;Database=sig;IntegrityCheck=1'),
        'username' => env('HFSQL_USERNAME', 'sig'),
        // ⚠️ PRODUCTION RECOMMENDATION:
        // Set HFSQL_PASSWORD via OS environment variable:
        //   Windows: setx HFSQL_PASSWORD "secret" /M
        //   Linux:   export HFSQL_PASSWORD="secret" in /etc/environment
        // DO NOT commit real passwords to .env or Git
        'password' => env('HFSQL_PASSWORD'),
    ],

    // ─── Rate Limiting ────────────────────────────────────────────────────────
    'rate_limiting' => [
        'max_login_attempts' => 5,
        'lockout_minutes'    => 15,
        'api_requests_pm'    => 60,  // per minute
    ],

    // ─── Session Security ─────────────────────────────────────────────────────
    'session' => [
        'timeout_minutes'      => env('SESSION_LIFETIME', 30),
        'enforce_single_session' => true,
        'regenerate_on_login'  => true,
    ],

    // ─── Password Policy ──────────────────────────────────────────────────────
    'password_policy' => [
        'min_length'           => 10,
        'require_uppercase'    => true,
        'require_lowercase'    => true,
        'require_digits'       => true,
        'require_special'      => true,
        'history_count'        => 5,    // لا إعادة استخدام آخر 5 كلمات مرور
        'expiry_days'          => 90,   // انتهاء صلاحية كل 90 يوماً
        'allow_nin_as_password' => false, // ⛔ مُحظر استخدام رقم الهوية ككلمة مرور
    ],

    // ─── IP Whitelist for Admin Access ────────────────────────────────────────
    // Leave empty to disable IP restriction
    'admin_ip_whitelist' => [],

    // ─── Content Security Policy ──────────────────────────────────────────────
    'csp' => [
        'enabled'     => true,
        'report_only' => env('APP_ENV') !== 'production',
        'allowed_cdns' => [
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'fonts.googleapis.com',
            'fonts.gstatic.com',
        ],
    ],

    // ─── Audit Settings ───────────────────────────────────────────────────────
    'audit' => [
        'enabled'        => true,
        'log_reads'      => false,  // لا تسجّل القراءات (أداء)
        'log_writes'     => true,   // سجّل كل الكتابات
        'log_deletes'    => true,   // سجّل كل الحذف
        'retention_days' => 365,    // احتفظ بالسجلات سنة كاملة
    ],

    // ─── Sensitive Fields (لا تُظهر في السجلات) ────────────────────────────
    'masked_fields' => [
        'password', 'mdp', 'motdepasse',
        'nin', 'national_id',
        'credit_card', 'carte_bancaire',
        'jwt_secret', 'hfsql_password',
        'totp_secret', 'recovery_code',
    ],
];
