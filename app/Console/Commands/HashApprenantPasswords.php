<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command: security:hash-apprenant-passwords
 *
 * يُشفِّر كلمات مرور المتربصين في جدول apprenant باستخدام bcrypt
 * مع الحفاظ على إمكانية الدخول (Lazy Migration).
 *
 * المنطق:
 *  - يقرأ كلمة المرور النصية الحالية
 *  - يُشفِّرها بـ bcrypt (cost=12)
 *  - يُحدِّث الجدول
 *  - لا يمسّ كلمات المرور المُشفَّرة مسبقاً ($2y$)
 *
 * الاستخدام:
 *   php artisan security:hash-apprenant-passwords --dry-run
 *   php artisan security:hash-apprenant-passwords --chunk=500
 *
 * @conformant ISO 27001 A.8.24 | RGPD Art. 32
 */
class HashApprenantPasswords extends Command
{
    protected $signature = 'security:hash-apprenant-passwords
                            {--chunk=500         : عدد السجلات في كل دفعة}
                            {--dry-run           : تجريبي — بدون تعديل}
                            {--table=apprenant   : الجدول}
                            {--column=Motdepass  : عمود كلمة المرور}
                            {--pk=               : المفتاح الأساسي (اختياري — يُكتشف تلقائياً)}';

    protected $description = 'تشفير كلمات مرور المتربصين بـ bcrypt — ISO 27001 A.8.24';

    /** خريطة المفتاح الأساسي لكل جدول */
    private array $pkMap = [
        'apprenant'    => 'IDapprenant',
        'preinscrit'   => 'IDPreinscrit',
        'encadrement'  => 'IDEncadrement',
        'etablissement'=> 'IDetablissement',
        'utilisateur'  => 'IDUtilisateur',
        'candidat'     => 'IDCandidat',
    ];

    public function handle(): int
    {
        $table  = $this->option('table');
        $column = $this->option('column');
        $chunk  = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        // تحديد المفتاح الأساسي: خيار --pk أولاً، ثم الخريطة، ثم 'id'
        $pkOption = trim($this->option('pk') ?? '');
        $pk = !empty($pkOption)
            ? $pkOption
            : ($this->pkMap[$table] ?? 'id');

        $this->newLine();
        $this->info("🔐 تشفير كلمات مرور [{$table}.{$column}]" . ($dryRun ? ' — ⚠️ وضع تجريبي' : ''));
        $this->line('─────────────────────────────────────────────────');

        if ($dryRun) {
            $this->warn('  لن يُجرى أي تعديل على قاعدة البيانات.');
        }

        // عدّ السجلات المستهدفة (غير المُشفَّرة)
        $total = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->count();

        $alreadyHashed = DB::table($table)
            ->where($column, 'LIKE', '$2y$%')
            ->count();

        $toProcess = $total - $alreadyHashed;

        $this->table(
            ['إجمالي', 'مشفَّر مسبقاً', 'يحتاج تشفير'],
            [[$total, $alreadyHashed, $toProcess]]
        );

        if ($toProcess === 0) {
            $this->info('✅ جميع كلمات المرور مُشفَّرة بالفعل!');
            return Command::SUCCESS;
        }

        $bar      = $this->output->createProgressBar($toProcess);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %elapsed:6s%');
        $bar->start();

        $processed = 0;
        $skipped   = 0;
        $errors    = 0;

        DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->where($column, 'NOT LIKE', '$2y$%')  // ← تخطي المُشفَّرة مسبقاً
            ->orderBy($pk)
            ->chunk($chunk, function ($rows) use (
                $table, $pk, $column, $dryRun,
                &$processed, &$skipped, &$errors, &$bar
            ) {
                foreach ($rows as $row) {
                    $pass = $row->$column ?? '';

                    // تحقق مزدوج من أن كلمة المرور ليست مُشفَّرة
                    if (str_starts_with($pass, '$2y$') || str_starts_with($pass, '$argon')) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        try {
                            $hashed = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                            DB::table($table)
                                ->where($pk, $row->$pk)
                                ->update([$column => $hashed]);
                            $processed++;
                        } catch (\Exception $e) {
                            $errors++;
                            Log::error("[hash-passwords] Error {$table}#{$row->$pk}: " . $e->getMessage());
                        }
                    } else {
                        $processed++; // dry-run: compte mais ne modifie pas
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['تم تشفيره', 'مُتخطَّى', 'أخطاء'],
            [[$processed, $skipped, $errors]]
        );

        if ($errors > 0) {
            $this->error("❌ {$errors} أخطاء — راجع storage/logs/laravel.log");
        } else {
            $this->info($dryRun
                ? "✅ (تجريبي) — سيتم تشفير {$processed} كلمة مرور عند التطبيق الفعلي."
                : "✅ تم تشفير {$processed} كلمة مرور بنجاح!"
            );
        }

        if (!$dryRun) {
            $this->newLine();
            $this->warn('⚠️  تأكد من تحديث منطق التحقق ليستخدم password_verify() إذا كان هناك API للمتربصين.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
