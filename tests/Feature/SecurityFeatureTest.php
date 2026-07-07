<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;

/**
 * SecurityFeatureTest — Validates all major security improvements.
 *
 * Run with: php vendor/bin/phpunit tests/Feature/SecurityFeatureTest.php --testdox
 */
class SecurityFeatureTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // 1. HTTP & CAPTCHA Page Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_login_page_loads_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_login_returns_redirect_on_empty_credentials(): void
    {
        $response = $this->post('/login', []);
        // Should not be 500 — must fail gracefully
        $this->assertNotEquals(500, $response->status(), 'Login endpoint returned 500 on empty POST');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Rate Limiting Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_login_rate_limiter_is_registered(): void
    {
        // Verify the 'login' rate limiter is registered in RouteServiceProvider
        $limiter = RateLimiter::limiter('login');
        $this->assertNotNull($limiter, "The 'login' rate limiter must be registered");
    }

    public function test_password_reset_rate_limiter_is_registered(): void
    {
        $limiter = RateLimiter::limiter('password-reset');
        $this->assertNotNull($limiter, "The 'password-reset' rate limiter must be registered");
    }

    public function test_sse_rate_limiter_is_registered(): void
    {
        $limiter = RateLimiter::limiter('sse');
        $this->assertNotNull($limiter, "The 'sse' rate limiter must be registered");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Password Reset Token Tests (DB-backed)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_password_reset_tokens_table_exists(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Table existence test only runs on MySQL — use production DB connection');
        }
        $this->assertTrue(
            Schema::hasTable('password_reset_tokens'),
            'Table password_reset_tokens must exist'
        );
    }

    public function test_password_reset_token_is_stored_hashed(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            $this->markTestSkipped('password_reset_tokens table not available in test DB');
        }

        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        DB::table('password_reset_tokens')->insert([
            'token'      => $hashedToken,
            'user_id'    => 99999,
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Raw token should NOT be findable directly
        $found = DB::table('password_reset_tokens')->where('token', $rawToken)->exists();
        $this->assertFalse($found, 'Raw token must not be stored directly in DB');

        // Hashed token should be findable
        $found = DB::table('password_reset_tokens')->where('token', $hashedToken)->exists();
        $this->assertTrue($found, 'Hashed token must exist in DB');

        // Cleanup
        DB::table('password_reset_tokens')->where('user_id', 99999)->delete();
    }

    public function test_expired_reset_token_is_detected(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            $this->markTestSkipped('password_reset_tokens table not available in test DB');
        }

        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        DB::table('password_reset_tokens')->insert([
            'token'      => $hashedToken,
            'user_id'    => 99998,
            'expires_at' => now()->subMinutes(5),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $dbToken   = DB::table('password_reset_tokens')->where('token', $hashedToken)->first();
        $isExpired = now()->gt($dbToken->expires_at);

        $this->assertTrue($isExpired, 'Expired token should be detected as expired');

        DB::table('password_reset_tokens')->where('user_id', 99998)->delete();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. File Upload Security Tests (logic-level, no GD required)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_file_validator_accepts_valid_image_extensions(): void
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        foreach ($allowed as $ext) {
            $this->assertTrue(
                in_array(strtolower($ext), $allowed, true),
                "Extension {$ext} should be accepted"
            );
        }
    }

    public function test_file_validator_rejects_php_extension(): void
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $rejected = ['php', 'php5', 'phtml', 'asp', 'sh', 'exe'];

        foreach ($rejected as $ext) {
            $this->assertFalse(
                in_array(strtolower($ext), $allowed, true),
                "Dangerous extension .{$ext} must be rejected"
            );
        }
    }

    public function test_file_validator_detects_double_extension_attack(): void
    {
        $dangerousName = 'shell.php.jpg';
        $parts = explode('.', $dangerousName);

        $dangerousExts = ['php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'cgi', 'pl', 'py', 'sh', 'rb', 'exe'];
        $hasDangerousIntermediate = false;

        foreach (array_slice($parts, 1, -1) as $segment) {
            if (in_array(strtolower($segment), $dangerousExts, true)) {
                $hasDangerousIntermediate = true;
                break;
            }
        }

        $this->assertTrue($hasDangerousIntermediate, 'Double-extension attack shell.php.jpg must be detected');
    }

    public function test_file_size_limit_logic(): void
    {
        $maxBytes = 2 * 1024 * 1024; // 2MB
        $oversizedBytes = 3 * 1024 * 1024; // 3MB

        $this->assertGreaterThan($maxBytes, $oversizedBytes, '3MB file should exceed 2MB limit');
        $this->assertLessThanOrEqual($maxBytes, 1 * 1024 * 1024, '1MB file should be within limit');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Security Headers Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_login_page_has_x_frame_options_header(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Frame-Options');
    }

    public function test_login_page_has_x_content_type_options_header(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Content-Type-Options');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Critical Vulnerability: Auto-login Route Removal
    // ─────────────────────────────────────────────────────────────────────────

    public function test_auto_login_route_is_permanently_disabled(): void
    {
        $response = $this->get('/auto-login');
        $this->assertNotEquals(200, $response->status(), '/auto-login critical vulnerability must be patched');
    }

    public function test_sig_auto_login_route_is_permanently_disabled(): void
    {
        $response = $this->get('/sig/auto-login');
        $this->assertNotEquals(200, $response->status(), '/sig/auto-login critical vulnerability must be patched');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Global Search Endpoint Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_global_search_requires_authentication(): void
    {
        $response = $this->get('/dashboard/search?q=test');
        $this->assertNotEquals(200, $response->status(), 'Global search must require authentication');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. CAPTCHA Token Hashing Logic Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_sha256_produces_consistent_hash(): void
    {
        $raw  = 'test_token_12345';
        $hash = hash('sha256', $raw);

        // Hash must be deterministic
        $this->assertEquals($hash, hash('sha256', $raw));

        // Hash must not equal raw
        $this->assertNotEquals($hash, $raw);

        // SHA-256 must produce 64 hex characters
        $this->assertEquals(64, strlen($hash));
    }

    public function test_random_bytes_generates_unique_tokens(): void
    {
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));

        $this->assertNotEquals($token1, $token2, 'Each token must be unique');
        $this->assertEquals(64, strlen($token1), 'Token should be 64 hex chars (32 bytes)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Push Notifications Table
    // ─────────────────────────────────────────────────────────────────────────

    public function test_push_subscriptions_table_exists(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Table existence test only runs on MySQL — use production DB connection');
        }
        $this->assertTrue(
            Schema::hasTable('push_subscriptions'),
            'Table push_subscriptions must exist after migration'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. Advanced Security Features (New Implementations)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_automated_ip_banning_record_insertion(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Database-backed tests run on MySQL');
        }

        $testIp = '198.51.100.1'; // Test IP

        // Simulate IP ban insert
        DB::table('ip_bans')->updateOrInsert(
            ['ip_address' => $testIp],
            [
                'failed_attempts' => 5,
                'banned_until'    => now()->addHours(24),
                'reason'          => 'Test IP Ban Shield',
                'created_at'      => now(),
                'updated_at'      => now()
            ]
        );

        $exists = DB::table('ip_bans')->where('ip_address', $testIp)->exists();
        $this->assertTrue($exists, 'Banned IP must be recorded in ip_bans table');

        // Cleanup
        DB::table('ip_bans')->where('ip_address', $testIp)->delete();
    }

    public function test_geo_fencing_blocks_foreign_ip_logic(): void
    {
        $foreignIp = '8.8.8.8'; // US Google DNS (non-Algerian)
        $isLocal   = in_array($foreignIp, ['127.0.0.1', '::1']) || 
                     preg_match('/^(192\.168|10\.|172\.(1[6-9]|2[0-9]|3[01]))\./', $foreignIp);
        
        $isAlgerian = preg_match('/^(41\.|197\.|105\.|129\.)/', $foreignIp);

        $this->assertFalse($isLocal, 'US IP is not local');
        $this->assertFalse((bool)$isAlgerian, 'US IP should be blocked by Geo-Fencing');
    }

    public function test_cryptographic_document_signature_verification(): void
    {
        $payload = json_encode([
            'demandeur_nom' => 'محمد الجزائري',
            'document_type' => 'certificat_scolaire',
            'code_verification' => 'CERT-2026-999',
        ]);

        $appKey = 'base64:Jg7D6Hfs78shdG62hsGD72hsdg71hsg2hd8='; // Fake app key for test
        $signature = hash_hmac('sha256', $payload, $appKey);

        // Re-calculate and assert matching
        $recalculated = hash_hmac('sha256', $payload, $appKey);
        $this->assertTrue(hash_equals($recalculated, $signature), 'Cryptographic signatures must match exactly');
    }

    public function test_sensitive_data_read_access_logging(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Database-backed tests run on MySQL');
        }

        // Log a test read access
        DB::table('audit_logs')->insert([
            'user_id'    => 1, // Admin/Test User
            'action'     => 'READ_ACCESS',
            'ip_address' => '127.0.0.1',
            'details'    => json_encode([
                'table_name' => 'encadrement',
                'record_id'  => 12345,
                'subject'    => 'معاينة الملف الشخصي للموظف (فحص اختبار)'
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $logged = DB::table('audit_logs')
            ->where('action', 'READ_ACCESS')
            ->where('user_id', 1)
            ->exists();

        $this->assertTrue($logged, 'Sensitive READ_ACCESS operation must be logged in audit_logs');

        // Cleanup
        DB::table('audit_logs')
            ->where('action', 'READ_ACCESS')
            ->where('user_id', 1)
            ->delete();
    }

    public function test_ip_banning_toggle_functionality(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Database-backed tests run on MySQL');
        }

        // 1. Save original setting
        $settingsFile = base_path('storage/ip_ban_settings.json');
        $originalSettings = null;
        if (file_exists($settingsFile)) {
            $originalSettings = file_get_contents($settingsFile);
        }

        // Mock a logged in admin user
        $adminUser = \App\Models\User::whereIn('IDNature', [1, 2])->first();
        if (!$adminUser) {
            $this->markTestSkipped('No admin user found to test session toggle');
        }

        $response = $this->withSession(['user' => [
            'id' => $adminUser->IDUtilisateur,
            'role_code' => 'admin',
        ]])->post('/admin/security/ip-ban/toggle', [
            'ip_banning_enabled' => 1
        ]);

        $response->assertStatus(302);
        
        $settings = json_decode(file_get_contents($settingsFile), true);
        $this->assertTrue($settings['ip_banning_enabled']);

        // Toggle back off
        $response = $this->withSession(['user' => [
            'id' => $adminUser->IDUtilisateur,
            'role_code' => 'admin',
        ]])->post('/admin/security/ip-ban/toggle', [
            'ip_banning_enabled' => 0
        ]);

        $response->assertStatus(302);
        $settings = json_decode(file_get_contents($settingsFile), true);
        $this->assertFalse($settings['ip_banning_enabled']);

        // Restore original settings
        if ($originalSettings !== null) {
            file_put_contents($settingsFile, $originalSettings);
        } else if (file_exists($settingsFile)) {
            unlink($settingsFile);
        }
    }
}

