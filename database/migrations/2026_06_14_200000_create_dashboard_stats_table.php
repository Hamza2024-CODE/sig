<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_stats', function (Blueprint $table) {
            $table->string('stat_key', 120)->primary();
            $table->string('stat_value', 255)->default('0');
            $table->string('stat_group', 60)->default('global')
                  ->comment('global | wilaya_{id} | etab_{id}');
            $table->string('stat_label', 200)->nullable()
                  ->comment('وصف بشري للمفتاح');
            $table->timestamp('last_updated')->useCurrent()->useCurrentOnUpdate();
        });

        // ── Seed initial rows so the table is never empty ──
        $now = now();
        $rows = [
            // ── وطني (global) ────────────────────────────────────
            ['stat_key'=>'global.total_apprenants',    'stat_group'=>'global','stat_label'=>'إجمالي المتربصين'],
            ['stat_key'=>'global.total_filles',        'stat_group'=>'global','stat_label'=>'المتربصات (إناث)'],
            ['stat_key'=>'global.total_garcons',       'stat_group'=>'global','stat_label'=>'المتربصون (ذكور)'],
            ['stat_key'=>'global.total_offres',        'stat_group'=>'global','stat_label'=>'عدد العروض'],
            ['stat_key'=>'global.total_etablissements','stat_group'=>'global','stat_label'=>'عدد المؤسسات'],
            ['stat_key'=>'global.total_encadrements',  'stat_group'=>'global','stat_label'=>'عدد الإطارات'],
            ['stat_key'=>'global.total_specialites',   'stat_group'=>'global','stat_label'=>'عدد التخصصات'],
            ['stat_key'=>'global.total_candidats',     'stat_group'=>'global','stat_label'=>'إجمالي المترشحين'],
            ['stat_key'=>'global.total_sections',      'stat_group'=>'global','stat_label'=>'عدد الأقسام'],
            ['stat_key'=>'global.total_diplomes',      'stat_group'=>'global','stat_label'=>'الشهادات المسلمة'],
            ['stat_key'=>'global.taux_feminisation',   'stat_group'=>'global','stat_label'=>'نسبة التأنيث (‰×10)'],
            ['stat_key'=>'global.last_sync_ts',        'stat_group'=>'global','stat_label'=>'آخر تحديث (Unix timestamp)'],
        ];

        foreach ($rows as &$r) {
            $r['stat_value']   = 0;
            $r['last_updated'] = $now;
        }

        DB::table('dashboard_stats')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_stats');
    }
};
