<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;

class AuthTest extends TestCase
{
    /**
     * تحضير جداول الاختبار في SQLite :memory:
     * RefreshDatabase (من TestCase) يضمن نظافة كاملة قبل كل test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء جداول الاختبار في SQLite :memory: (لا تمس MySQL الإنتاجية)
        if (!Schema::hasTable('utilisateur')) {
            Schema::create('utilisateur', function ($table) {
                $table->integer('IDUtilisateur')->primary();
                $table->string('NomUser', 100)->unique();
                $table->string('Nom', 255)->nullable();
                $table->string('MotPass', 255)->nullable();
                $table->tinyInteger('admin')->default(0);
                $table->integer('IDNature')->default(0);
                $table->tinyInteger('activee')->default(0);
                $table->integer('IDBureau')->default(0);
                $table->integer('Code')->default(0);
                $table->integer('IDMode_gestion')->default(0);
            });
        }

        if (!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function ($table) {
                $table->bigIncrements('id');
                $table->string('username', 100)->nullable();
                $table->string('ip', 45)->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamp('last_attempt')->nullable();
            });
        }

        // إدراج مستخدم اختبار
        DB::table('utilisateur')->insert([
            'IDUtilisateur' => 1,
            'NomUser'       => 'admin',
            'Nom'           => 'Test Admin',
            'MotPass'       => password_hash('admin123', PASSWORD_BCRYPT),
            'admin'         => 1,
            'IDNature'      => 1,
            'activee'       => 0,
            'IDBureau'      => 0,
            'Code'          => 0,
            'IDMode_gestion'=> 0,
        ]);
    }

    /**
     * Test the login page renders successfully.
     */
    public function test_login_page_renders_successfully()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('تسجيل الدخول');
    }

    /**
     * Test user can authenticate with valid credentials.
     */
    public function test_user_can_authenticate_with_valid_credentials()
    {
        $response = $this->post('/login', [
            'username'   => 'admin',
            'password'   => 'admin123',
            'login_type' => 'direct',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('user.username', 'admin');
        $response->assertSessionHas('user.role_code', 'admin');
    }

    /**
     * Test user cannot authenticate with invalid credentials.
     */
    public function test_user_cannot_authenticate_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'username'   => 'admin',
            'password'   => 'wrongpassword',
            'login_type' => 'direct',
        ]);

        $response->assertStatus(200);
        $response->assertSee('غير صحيح');
    }
}
