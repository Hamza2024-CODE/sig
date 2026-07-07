<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\KpiCache;

/**
 * RecalculerNotesJob — إعادة حساب المعدلات في الخلفية
 *
 * يُشغَّل عند:
 *   - رفع دفعة من النقاط (Import Excel)
 *   - تغيير معاملات (coeff) الوحدة التعليمية
 *   - ترقية الدرجة القادمة (passage au semestre suivant)
 *
 * القاعدة: المستخدم يُحمّل الملف → يُعاد له "جاري المعالجة..." →
 *          الحساب يتم في الخلفية دون تجميد المتصفح.
 */
class RecalculerNotesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 600; // 10 دقائق

    public function __construct(
        private int    $sectionSemestreId,
        private int    $etablissementId   = 0,
        private int    $dfepId            = 0,
        private string $triggeredBy        = 'system',
    ) {}

    public function handle(): void
    {
        Log::info("[RecalculerNotes] Starting for sectionSemestre={$this->sectionSemestreId}");

        try {
            // ── §1 جلب المتربصين المرتبطين بالسداسي (بـ Cursor — لا نحمّل الكل) ──
            $cursor = DB::table('apprenant_section_semstre as ass')
                ->join('section_semestre_module as ssm', 'ssm.IDSection_Semestre', '=', 'ass.IDSection_Semestre')
                ->join('apprenant_section_semstre_module as assm', function ($join) {
                    $join->on('assm.IDapprenant_Section_semstre', '=', 'ass.IDapprenant_Section_semstre')
                         ->on('assm.IDsection_semestre_Module', '=', 'ssm.IDsection_semestre_Module');
                })
                ->where('ass.IDSection_Semestre', $this->sectionSemestreId)
                ->select('ass.IDapprenant_Section_semstre', 'ssm.coef', 'assm.NoteCs', 'assm.NoteR')
                ->cursor(); // ✅ cursor() بدلاً من get() — يقرأ سطراً بسطر من DB

            $sumCoef   = 0.0;
            $sumPoints = 0.0;

            foreach ($cursor as $row) {
                $note   = $row->NoteR ?? $row->NoteCs ?? 0.0;
                $coef   = (float)($row->coef ?? 1);
                $sumCoef   += $coef;
                $sumPoints += $note * $coef;
            }

            $moyenneGenerale = $sumCoef > 0 ? round($sumPoints / $sumCoef, 2) : 0.0;
            $isAdmis         = $moyenneGenerale >= 10.0;

            // ── §2 حفظ النتيجة ──────────────────────────────────────────────
            DB::table('section_semestre_results')
                ->updateOrInsert(
                    ['IDSection_Semestre' => $this->sectionSemestreId],
                    [
                        'moyenne_generale' => $moyenneGenerale,
                        'is_admis_general' => $isAdmis ? 1 : 0,
                        'calculated_at'    => now(),
                        'triggered_by'     => $this->triggeredBy,
                    ]
                );

            // ── §3 إبطال كاش KPI المؤسسة والولاية ─────────────────────────
            if ($this->etablissementId > 0) {
                KpiCache::invalidateEtab($this->etablissementId, $this->dfepId);
            }

            Log::info("[RecalculerNotes] Done. Moyenne={$moyenneGenerale}, Admis=" . ($isAdmis ? 'OUI' : 'NON'));

        } catch (\Throwable $e) {
            Log::error("[RecalculerNotes] Failed: " . $e->getMessage());
            throw $e;
        }
    }
}
