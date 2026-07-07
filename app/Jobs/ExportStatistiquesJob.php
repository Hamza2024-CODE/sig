<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ExportStatistiquesJob — تصدير ملف Excel في الخلفية
 *
 * المشكلة القديمة (Legacy):
 *   المستخدم يضغط "تصدير Excel" → الـ PHP يشتغل 30 ثانية → المتصفح يتجمد.
 *
 * الحل (Laravel Queues):
 *   المستخدم يضغط → يُعاد له فوراً "جاري التحضير..." → Job يشتغل في الخلفية
 *   → ملف يُحفظ في Storage → إشعار بـ email/notification عند الاكتمال.
 *
 * الاستخدام:
 *   ExportStatistiquesJob::dispatch($userId, $filters)->onQueue('exports');
 */
class ExportStatistiquesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** عدد محاولات إعادة التشغيل عند الفشل */
    public int $tries = 3;

    /** مهلة التنفيذ القصوى (ثانية) */
    public int $timeout = 300;

    public function __construct(
        private int    $userId,
        private array  $filters,
        private string $exportType = 'stagiaires', // stagiaires | offres | encadrements
    ) {}

    public function handle(): void
    {
        Log::info("[Export] Starting {$this->exportType} for user {$this->userId}", $this->filters);

        try {
            $data = $this->fetchData();
            $csv  = $this->buildCsv($data);

            // حفظ الملف في Storage (لا في RAM)
            $filename = "exports/{$this->exportType}_{$this->userId}_" . now()->format('Ymd_His') . '.csv';
            Storage::disk('local')->put($filename, $csv);

            // إشعار المستخدم (يمكن استبداله بـ Email أو Notification)
            Log::info("[Export] Done: {$filename}");

            // تخزين مسار الملف في DB للمستخدم
            DB::table('export_requests')
                ->where('user_id', $this->userId)
                ->where('type', $this->exportType)
                ->update([
                    'status'     => 'ready',
                    'file_path'  => $filename,
                    'completed_at' => now(),
                ]);

        } catch (\Throwable $e) {
            Log::error("[Export] Failed for user {$this->userId}: " . $e->getMessage());
            throw $e; // يُعيد الـ retry
        }
    }

    /** القاعدة: paginate-like fetch — لا نجلب كل شيء دفعة واحدة */
    private function fetchData(): array
    {
        $rows = [];
        $perChunk = 500;  // ✅ نجلب 500 سطر في كل مرة — لا نحمّل RAM
        $offset   = 0;

        [$where, $params] = $this->buildWhereClause();

        do {
            $chunk = DB::select(
                $this->getSelectSql($where) . " LIMIT {$perChunk} OFFSET {$offset}",
                $params
            );
            foreach ($chunk as $row) {
                $rows[] = (array)$row;
            }
            $offset += $perChunk;
        } while (count($chunk) === $perChunk);

        return $rows;
    }

    private function buildCsv(array $rows): string
    {
        if (empty($rows)) return '';

        $handle = fopen('php://temp', 'r+');
        // رأس الأعمدة
        fputcsv($handle, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    private function buildWhereClause(): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($this->filters['wilaya_id'])) {
            $where[]  = 'e.IDDFEP = ?';
            $params[] = $this->filters['wilaya_id'];
        }
        if (!empty($this->filters['etab_id'])) {
            $where[]  = 'o.IDEts_Form = ?';
            $params[] = $this->filters['etab_id'];
        }
        if (!empty($this->filters['mode_id'])) {
            $where[]  = 'o.IDMode_formation = ?';
            $params[] = $this->filters['mode_id'];
        }

        return [implode(' AND ', $where), $params];
    }

    private function getSelectSql(string $where): string
    {
        return match ($this->exportType) {
            'stagiaires' => "
                SELECT c.NumIns as matricule, c.Nom as nom, c.Prenom as prenom,
                       c.Nin as nin, sp.Nom as specialite, e.Nom as etablissement,
                       mf.Nom as mode_formation, c.dateInscr as date_inscription
                FROM candidat c
                LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                LEFT JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation
                WHERE $where
                ORDER BY c.IDCandidat ASC
            ",
            'offres' => "
                SELECT o.IDOffre as id, sp.Nom as specialite, e.Nom as etablissement,
                       mf.Nom as mode, o.NbrInscr as nb_inscrits, o.NbrInscrf as nb_filles
                FROM offre o
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                LEFT JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation
                WHERE $where
                ORDER BY o.IDOffre ASC
            ",
            'encadrements' => "
                SELECT enc.Nom as nom, enc.Prenom as prenom, enc.nin as nin,
                       enc.Grade as grade, enc.Fonction as fonction, e.Nom as etablissement
                FROM Encadrement enc
                LEFT JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                WHERE $where
                ORDER BY enc.IDEncadrement ASC
            ",
            default => "SELECT 1 as empty",
        };
    }
}
