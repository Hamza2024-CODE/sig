<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\PermissionHelper;

class PermissionsTest extends TestCase
{
    /**
     * بناء جداول الاختبار في SQLite :memory:.
     * RefreshDatabase (من TestCase) يضمن أن الجداول نظيفة قبل كل test.
     * لا تُعدَّل أي جداول من قاعدة الإنتاج sgfep_windev.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ── إنشاء جداول الاختبار ──
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

        if (!Schema::hasTable('privelege')) {
            Schema::create('privelege', function ($table) {
                $table->integer('IDPrivelege')->primary();
                $table->integer('code')->default(0);
                $table->string('nomFr', 100)->nullable();
            });
        }

        if (!Schema::hasTable('privelege_utilisateur')) {
            Schema::create('privelege_utilisateur', function ($table) {
                $table->bigIncrements('id');
                $table->integer('IDUtilisateur')->default(0);
                $table->integer('IDPrivelege')->default(0);
                $table->tinyInteger('DroiAjout')->default(0);
                $table->tinyInteger('DroiModif')->default(0);
                $table->tinyInteger('DroitSuppr')->default(0);
                $table->tinyInteger('DroitTous')->default(0);
                $table->integer('IDBureau')->default(0);
                $table->integer('IDMode_formation')->default(0);
                $table->tinyInteger('activee')->default(0);
                $table->integer('Code')->default(0);
                $table->integer('IDMode_gestion')->default(0);
                $table->integer('IDNature')->default(0);
                $table->integer('IDPrivelege_Utilisateur')->default(0);
                $table->unique(['IDUtilisateur', 'IDPrivelege']);
            });
        }

        // ── إدراج بيانات الاختبار ──
        DB::table('utilisateur')->insert([
            [
                'IDUtilisateur'  => 1,
                'NomUser'        => 'admin_test',
                'Nom'            => 'Test Admin',
                'MotPass'        => password_hash('admin123', PASSWORD_BCRYPT),
                'admin'          => 1,
                'IDNature'       => 4,
                'activee'        => 0,
                'IDBureau'       => 0,
                'Code'           => 1,
                'IDMode_gestion' => 0,
            ],
            [
                'IDUtilisateur'  => 2,
                'NomUser'        => 'formateur_test',
                'Nom'            => 'Test Formateur',
                'MotPass'        => password_hash('formateur123', PASSWORD_BCRYPT),
                'admin'          => 0,
                'IDNature'       => 3,
                'activee'        => 0,
                'IDBureau'       => 0,
                'Code'           => 2,
                'IDMode_gestion' => 0,
            ],
        ]);

        $privileges = [
            ['IDPrivelege' => 1,  'code' => 5,   'nomFr' => 'BTN_2_OFFRE'],
            ['IDPrivelege' => 2,  'code' => 6,   'nomFr' => 'BTN_2_INSCRIPTION'],
            ['IDPrivelege' => 3,  'code' => 7,   'nomFr' => 'BTN_2_SECTION'],
            ['IDPrivelege' => 4,  'code' => 8,   'nomFr' => 'BTN_2_EFFECTIF'],
            ['IDPrivelege' => 6,  'code' => 10,  'nomFr' => 'BTN_2_EVAL_SEMEST'],
            ['IDPrivelege' => 7,  'code' => 11,  'nomFr' => 'BTN_2_EVAL_FINAL'],
            ['IDPrivelege' => 8,  'code' => 12,  'nomFr' => 'BTN_2_ATTESTATION'],
            ['IDPrivelege' => 70, 'code' => 102, 'nomFr' => 'BTN_2_SUIVI'],
            ['IDPrivelege' => 72, 'code' => 104, 'nomFr' => 'BTN_3_BOURSEPSALAIRE'],
        ];

        foreach ($privileges as $p) {
            DB::table('privelege')->insert($p);
        }
    }

    /**
     * Test admin can access permissions list.
     */
    public function test_admin_can_access_permissions_list()
    {
        $response = $this->withSession([
            'user' => [
                'id'       => 1,
                'NomUser'  => 'admin_test',
                'role_code'=> 'admin',
            ]
        ])->get('/dashboard/permissions');

        $response->assertStatus(200);
        $response->assertSee('تخصيص صلاحيات المستخدمين');
        $response->assertSee('formateur_test');
    }

    /**
     * Test non-admin is blocked from permissions list.
     */
    public function test_non_admin_is_blocked_from_permissions_list()
    {
        $response = $this->withSession([
            'user' => [
                'id'       => 2,
                'NomUser'  => 'formateur_test',
                'role_code'=> 'directeur',
            ]
        ])->get('/dashboard/permissions');

        $response->assertRedirect('/dashboard');
    }

    /**
     * Test admin can override user permissions (Grant/Deny).
     */
    public function test_admin_can_override_user_permissions()
    {
        $response = $this->withSession([
            'user' => [
                'id'       => 1,
                'NomUser'  => 'admin_test',
                'role_code'=> 'admin',
            ]
        ])->post('/dashboard/permissions/update', [
            'user_id'     => 2,
            'permissions' => [
                'offres'       => 1,
                'inscriptions' => 0,
                'discipline'   => 2,
            ]
        ]);

        $response->assertRedirect();

        // التحقق من السجلات في privelege_utilisateur (SQLite in-memory)
        $offresOverride = DB::table('privelege_utilisateur')
            ->where('IDUtilisateur', 2)
            ->where('IDPrivelege', 1)
            ->first();

        $this->assertNotNull($offresOverride);
        $this->assertEquals(1, $offresOverride->DroiAjout);
        $this->assertEquals(1, $offresOverride->DroiModif);

        $inscOverride = DB::table('privelege_utilisateur')
            ->where('IDUtilisateur', 2)
            ->where('IDPrivelege', 2)
            ->first();

        $this->assertNotNull($inscOverride);
        $this->assertEquals(0, $inscOverride->DroiAjout);
        $this->assertEquals(0, $inscOverride->DroiModif);

        // 'discipline' = Inherit (2) → لا يُنشأ أي سجل
        $discOverride = DB::table('privelege_utilisateur')
            ->where('IDUtilisateur', 2)
            ->where('IDPrivelege', 70)
            ->first();

        $this->assertNull($discOverride);
    }
}
