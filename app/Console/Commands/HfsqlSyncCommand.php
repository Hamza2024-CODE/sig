<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Core\HFSQLConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


/**
 * HfsqlSyncCommand
 * ════════════════════════════════════════════════════════════════════
 * يعمل في الخلفية (Background) — اتصال HFSQL واحد يعالج جميع السجلات
 * تقدم العملية يُكتب في ملف JSON تقرأه الواجهة الأمامية
 * ════════════════════════════════════════════════════════════════════
 */
class HfsqlSyncCommand extends Command
{
    protected $signature = 'hfsql:sync
                            {table   : اسم الجدول في HFSQL}
                            {column  : اسم العمود (photo/Pdf/Document...)}
                            {--etab-id=0 : تصفية حسب المؤسسة (اختياري)}
                            {--batch=50  : عدد السجلات لكل دفعة}
                            {--offset=0  : نقطة البداية (الأوفست) أو آخر ID معالج للجداول ذات cursor}';

    protected $description = 'مزامنة الملفات من HFSQL إلى التخزين المحلي (يعمل في الخلفية)';

    private const TABLES_CONFIG = [
        'candidat' => [
            'primary_key' => 'IDCandidat',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'encadrement' => [
            'primary_key' => 'IDEncadrement',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'candidat_memo' => [
            'primary_key' => 'IDCandidat_memo',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'encadremen_memo' => [
            'primary_key' => 'IDEncadremen_memo',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'etablissement_memo' => [
            'primary_key' => 'IDEtablissement_memo',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'equipement_memo' => [
            'primary_key' => 'IDEquipement_memo',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'logement_memo' => [
            'primary_key' => 'IDLogement_memo',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'vehicule_memo' => [
            'primary_key' => 'IDVehicule_memo',
            'columns' => ['photo' => ['type' => 'image', 'ext' => 'jpg']],
        ],
        'dercrte_memo' => [
            'primary_key' => 'IDDercrte_memo',
            'columns' => ['Pdf' => ['type' => 'pdf', 'ext' => 'pdf']],
        ],
        'candidat_document' => [
            'primary_key' => 'IDcandidat_document',
            'columns' => [
                'relevedenotes_doc'  => ['type' => 'pdf', 'ext' => 'pdf', 'mysql_col' => 'relevedenotes_url'],
                'enneexperience_doc' => ['type' => 'pdf', 'ext' => 'pdf', 'mysql_col' => 'enneexperience_url'],
                'exdiplome_doc'      => ['type' => 'pdf', 'ext' => 'pdf', 'mysql_col' => 'exdiplome_url'],
                'actn_doc'           => ['type' => 'pdf', 'ext' => 'pdf', 'mysql_col' => 'actn_url'],
            ],
        ],
        'dpca' => [
            'primary_key' => 'IDDpca',
            'columns' => [
                'Document'  => ['type' => 'pdf', 'ext' => 'pdf'],
                'Document1' => ['type' => 'pdf', 'ext' => 'pdf'],
            ],
        ],
        'candidat_contratapp' => [
            'primary_key' => 'IDCandidat_contratapp',
            'columns' => [
                'photo' => ['type' => 'image', 'ext' => 'jpg'],
            ],
        ],
    ];

    public function handle(): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        // ── Feature Flag Check ──────────────────────────────────────────
        $syncEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_background_sync_enabled', '1') === '1';
        if (!$syncEnabled) {
            $this->warn('⚠ المزامنة الخلفية معطّلة من لوحة التحكم — Background sync is DISABLED by admin.');
            $this->line('  → لتفعيلها: لوحة التحكم → الإعدادات → إدارة الميزات والاستعلامات');
            return 0;
        }

        $table     = $this->argument('table');
        $column    = $this->argument('column');
        $etabId      = (int) $this->option('etab-id');
        $batchSize   = (int) $this->option('batch');
        $startOffset = (int) $this->option('offset');

        $progressFile = storage_path("app/hfsql_sync_{$table}_{$column}.json");

        // تحقق من الإعداد
        $config = self::TABLES_CONFIG[$table] ?? null;
        if (!$config || !isset($config['columns'][$column])) {
            $this->writeProgress($progressFile, [
                'status'  => 'error',
                'error'   => "الجدول أو العمود غير مدعوم: {$table}.{$column}",
            ]);
            return 1;
        }

        $primaryKey  = $config['primary_key'];
        $hfsqlTable  = $config['hfsql_table'] ?? $table;
        $colConfig   = $config['columns'][$column];
        $ext         = $colConfig['ext'];
        $isMainTable = in_array($table, ['candidat', 'encadrement']);

        $this->writeProgress($progressFile, [
            'status'    => 'connecting',
            'processed' => 0,
            'extracted' => 0,
            'total'     => 0,
            'started_at' => now()->toISOString(),
        ]);

        $this->info("الاتصال بـ HFSQL عبر ODBC... قد يستغرق 60 ثانية.");

        // قراءة إعدادات الاتصال لبناء سلسلة DSN لـ odbc_connect
        $dsnConfig = config('security.hfsql.dsn', 'Driver={HFSQL};Server Name=127.0.0.1;Server Port=4900;Database=sig;IntegrityCheck=1');
        $user      = config('security.hfsql.username', 'sig');
        $pass      = config('security.hfsql.password');

        $rawDsn = preg_replace('/^odbc:/i', '', $dsnConfig);
        $rawDsn = rtrim(trim($rawDsn), ';');
        $connString = $rawDsn . ';UID=' . $user . ';PWD=' . $pass;

        // إعدادات ODBC لقراءة الـ LOB الكبيرة
        ini_set('odbc.defaultlrl', '10485760'); // 10MB
        ini_set('odbc.defaultbinmode', '1');

        $conn = @odbc_connect($connString, '', '');
        if (!$conn) {
            $err = odbc_errormsg();
            $this->writeProgress($progressFile, [
                'status' => 'error',
                'error'  => 'فشل الاتصال: ' . $err,
            ]);
            $this->error('فشل الاتصال: ' . $err);
            return 1;
        }

        $this->info("✓ متصل بنجاح!");

        // حساب الإجمالي
        $total = 0;
        try {
            $countRes = @odbc_exec($conn, "SELECT COUNT(*) AS cnt FROM {$hfsqlTable}");
            if ($countRes && odbc_fetch_row($countRes)) {
                $total = (int) odbc_result($countRes, 'cnt');
            }
        } catch (\Exception $e) {
            $this->warn('تعذّر حساب الإجمالي: ' . $e->getMessage());
        }

        $this->writeProgress($progressFile, [
            'status'     => 'running',
            'processed'  => 0,
            'extracted'  => 0,
            'total'      => $total,
            'started_at' => now()->toISOString(),
        ]);

        // إنشاء مجلد الحفظ
        $saveDirRelative = "uploads/hfsql_sync/{$table}/{$column}";
        $saveDirAbs = public_path($saveDirRelative);
        if (!is_dir($saveDirAbs)) {
            mkdir($saveDirAbs, 0755, true);
        }

        $mysqlColumns  = Schema::getColumnListing($table);
        $processed     = $startOffset;
        $extracted     = 0;
        $offset        = $startOffset;

        // ═══════════════════════════════════════════════════════
        // HFSQL لـ candidat_document يعاني من خلل في LIMIT/OFFSET
        // بعد ~18k سجل تعود الدفعة فارغة حتى لو يوجد بيانات.
        // الحل: استخدام Cursor-based pagination (WHERE id > last_id)
        // ═══════════════════════════════════════════════════════
        $useCursor = in_array($table, ['candidat_document', 'candidat_memo', 'encadremen_memo', 'candidat_contratapp']);
        $lastId    = $startOffset; // في وضع cursor يُمثّل آخر ID معالج

        while (true) {
            // ═══════════════════════════════════════════════════════
            // المرحلة 1: جلب المفاتيح والأعمدة النصية فقط (بدون blob)
            // ═══════════════════════════════════════════════════════
            $textCols = $this->getTextColumns($hfsqlTable, $column);
            $selectCols = $textCols ? "{$primaryKey}, {$textCols}" : $primaryKey;

            try {
                if ($useCursor) {
                    // Cursor-based: يتجنب مشكلة LIMIT/OFFSET في HFSQL
                    $sqlPhase1 = "SELECT {$selectCols}
                                  FROM {$hfsqlTable}
                                  WHERE {$primaryKey} > {$lastId}
                                  ORDER BY {$primaryKey}
                                  LIMIT {$batchSize}";
                } elseif ($table === 'candidat' && $etabId > 0) {
                    $sqlPhase1 = "SELECT c.{$primaryKey}
                                  FROM candidat c
                                  INNER JOIN offre o ON o.IDOffre = c.IDOffre
                                  WHERE o.IDEts_Form = {$etabId}
                                  ORDER BY c.{$primaryKey}
                                  LIMIT {$batchSize} OFFSET {$offset}";
                } elseif ($table === 'encadrement' && $etabId > 0) {
                    $sqlPhase1 = "SELECT {$primaryKey}
                                  FROM encadrement
                                  WHERE IDetablissement = {$etabId}
                                  ORDER BY {$primaryKey}
                                  LIMIT {$batchSize} OFFSET {$offset}";
                } else {
                    $sqlPhase1 = "SELECT {$selectCols}
                                  FROM {$hfsqlTable}
                                  ORDER BY {$primaryKey}
                                  LIMIT {$batchSize} OFFSET {$offset}";
                }

                $resPhase1 = @odbc_exec($conn, $sqlPhase1);
                $idRows = [];
                if ($resPhase1) {
                    while ($row = odbc_fetch_array($resPhase1)) {
                        $idRows[] = $row;
                    }
                }
            } catch (\Exception $e) {
                $this->error("خطأ المرحلة 1 (offset={$offset}/lastId={$lastId}): " . $e->getMessage());
                break;
            }

            if (empty($idRows)) {
                break; // انتهت السجلات
            }

            // تحديث cursor بآخر ID في الدفعة
            if ($useCursor) {
                $lastId = $idRows[count($idRows) - 1][$primaryKey];
            }

            // جلب المعرفات الموجودة مسبقاً في MySQL لتسريع التخطي دون الاستعلام عن الـ blob
            $idsInBatch = array_column($idRows, $primaryKey);
            $existingMysqlIds = [];
            try {
                $mysqlCol = $colConfig['mysql_col'] ?? $column;
                $existingMysqlIds = DB::table($table)
                    ->whereIn($primaryKey, $idsInBatch)
                    ->whereNotNull($mysqlCol)
                    ->pluck($primaryKey)
                    ->toArray();
            } catch (\Exception $dbEx) {
                // قد لا يكون الجدول موجوداً بعد في MySQL
            }

            // ═══════════════════════════════════════════════════════
            // المرحلة 2: جلب blob لكل سجل بمفرده بواسطة WHERE = ID
            // ═══════════════════════════════════════════════════════
            foreach ($idRows as $idRow) {
                $id = $idRow[$primaryKey];
                $processed++;

                // تجاوز السجلات المعالجة مسبقاً في MySQL
                if (in_array($id, $existingMysqlIds)) {
                    $extracted++;
                    continue;
                }

                // تجاوز السجلات المعالجة مسبقاً في نظام الملفات
                $fileGlob = glob("{$saveDirAbs}/{$id}.*");
                if (!empty($fileGlob)) {
                    $extracted++;
                    // نقوم أيضاً بتسجيله في MySQL لضمان عدم فحصه مستقبلاً
                    try {
                        $mysqlCol = $colConfig['mysql_col'] ?? $column;
                        $fileName = basename($fileGlob[0]);
                        $publicPath = "/uploads/hfsql_sync/{$table}/{$column}/{$fileName}";
                        $updateData = [$mysqlCol => $publicPath];
                        if (!$isMainTable) {
                            foreach ($idRow as $k => $v) {
                                if ($k !== $column && $k !== $mysqlCol && $k !== $primaryKey && in_array($k, $mysqlColumns)) {
                                    if (is_string($v)) {
                                        $v = trim(str_replace("\0", '', $v));
                                    }
                                    $updateData[$k] = $v;
                                }
                            }
                        }
                        DB::table($table)->updateOrInsert([$primaryKey => $id], $updateData);
                    } catch (\Exception $dbEx) {}
                    continue;
                }

                // جلب blob هذا السجل بمفرده
                try {
                    $sqlBlob = "SELECT {$column} FROM {$hfsqlTable} WHERE {$primaryKey} = {$id}";
                    $resBlob = @odbc_exec($conn, $sqlBlob);
                    $binaryData = null;
                    if ($resBlob && odbc_fetch_row($resBlob)) {
                        $binaryData = @odbc_result($resBlob, $column);
                    }
                } catch (\Exception $e) {
                    $this->warn("  ⚠ خطأ blob ID={$id}: " . substr($e->getMessage(), 0, 80));
                    continue;
                }

                if (!empty($binaryData) && strlen($binaryData) > 100) {
                    $detectedExt = $this->getExtensionFromBinary($binaryData, $ext);
                    $fileName    = "{$id}.{$detectedExt}";
                    $fileAbsPath = "{$saveDirAbs}/{$fileName}";
                    $publicPath  = "/uploads/hfsql_sync/{$table}/{$column}/{$fileName}";

                    file_put_contents($fileAbsPath, $binaryData);

                    // تحديث MySQL
                    $mysqlCol = $colConfig['mysql_col'] ?? $column;
                    $updateData = [$mysqlCol => $publicPath];

                    if (!$isMainTable) {
                        foreach ($idRow as $k => $v) {
                            if ($k !== $column && $k !== $mysqlCol && $k !== $primaryKey && in_array($k, $mysqlColumns)) {
                                if (is_string($v)) {
                                    $v = trim(str_replace("\0", '', $v));
                                }
                                $updateData[$k] = $v;
                            }
                        }
                    }

                    try {
                        DB::table($table)->updateOrInsert([$primaryKey => $id], $updateData);
                        
                        // تحديث جدول الآباء (candidat و encadrement) لضمان ظهور الصور في بطاقات المتكونين وملف الموظف
                        if ($table === 'candidat_memo') {
                            $candidatId = $idRow['IDCandidat'] ?? null;
                            if ($candidatId) {
                                DB::table('candidat')->where('IDCandidat', $candidatId)->update(['photo' => $publicPath]);
                            }
                        } elseif ($table === 'encadremen_memo') {
                            $encadrementId = $idRow['IDEncadrement'] ?? null;
                            if ($encadrementId) {
                                DB::table('encadrement')->where('IDEncadrement', $encadrementId)->update(['photo' => $publicPath]);
                            }
                        }

                        $extracted++;
                        $this->line("  ✓ [{$id}] {$fileName} — " . round(strlen($binaryData)/1024) . " KB");
                    } catch (\Exception $dbEx) {
                        // Foreign key constraint
                        $this->warn("  ⚠ DB skip [{$id}]: " . substr($dbEx->getMessage(), 0, 80));
                        
                        // حتى عند حدوث خطأ مفتاح أجنبي، نحاول تحديث جدول الآباء إذا تيسر
                        if ($table === 'candidat_memo') {
                            $candidatId = $idRow['IDCandidat'] ?? null;
                            if ($candidatId) {
                                DB::table('candidat')->where('IDCandidat', $candidatId)->update(['photo' => $publicPath]);
                            }
                        } elseif ($table === 'encadremen_memo') {
                            $encadrementId = $idRow['IDEncadrement'] ?? null;
                            if ($encadrementId) {
                                DB::table('encadrement')->where('IDEncadrement', $encadrementId)->update(['photo' => $publicPath]);
                            }
                        }

                        $extracted++;
                        $this->line("  ✓ [{$id}] {$fileName} — " . round(strlen($binaryData)/1024) . " KB (file only)");
                    }
                } else {
                    // السجل ليس لديه ملف/صورة، نتحقق مما إذا كان لديه رابط مستند نصي في HFSQL
                    $mysqlCol = $colConfig['mysql_col'] ?? $column;
                    $hfsqlUrlVal = isset($idRow[$mysqlCol]) ? trim(str_replace("\0", '', $idRow[$mysqlCol])) : '';
                    
                    if (!empty($hfsqlUrlVal) && !str_starts_with(strtolower($hfsqlUrlVal), 'empty')) {
                        // إذا كان المسار لا يبدأ بـ /uploads/ أو uploads/، نضيف البادئة
                        $pathVal = $hfsqlUrlVal;
                        if (!str_starts_with(strtolower($pathVal), 'http') && !str_starts_with($pathVal, '/') && !str_starts_with(strtolower($pathVal), 'uploads/')) {
                            $pathVal = "/uploads/{$pathVal}";
                        } elseif (str_starts_with(strtolower($pathVal), 'uploads/')) {
                            $pathVal = "/{$pathVal}";
                        }
                        $updateData = [$mysqlCol => $pathVal];
                    } else {
                        $updateData = [$mysqlCol => 'empty'];
                    }

                    if (!$isMainTable) {
                        foreach ($idRow as $k => $v) {
                            if ($k !== $column && $k !== $mysqlCol && $k !== $primaryKey && in_array($k, $mysqlColumns)) {
                                if (is_string($v)) {
                                    $v = trim(str_replace("\0", '', $v));
                                }
                                $updateData[$k] = $v;
                            }
                        }
                    }
                    try {
                        DB::table($table)->updateOrInsert([$primaryKey => $id], $updateData);
                    } catch (\Exception $dbEx) {
                        // في حال تعذر الإدراج (خطأ مفتاح أجنبي)، نتجاهله
                    }
                }
            }

            if (!$useCursor) {
                $offset += $batchSize;
            }

            // تحديث ملف التقدم
            $cursorInfo = $useCursor ? " (cursor={$lastId})" : "";
            $this->writeProgress($progressFile, [
                'status'     => 'running',
                'processed'  => $processed,
                'extracted'  => $extracted,
                'total'      => $total,
                'percent'    => $total > 0 ? round($processed * 100 / $total) : 0,
                'started_at' => now()->toISOString(),
                'last_id'    => $useCursor ? $lastId : null,
            ]);

            $this->line("  ✓ معالجة {$processed}/{$total} | استخراج {$extracted}{$cursorInfo}");

            if (count($idRows) < $batchSize) {
                break; // آخر دفعة
            }
        }

        // إغلاق الاتصال
        @odbc_close($conn);

        // اكتمال
        $this->writeProgress($progressFile, [
            'status'       => 'done',
            'processed'    => $processed,
            'extracted'    => $extracted,
            'total'        => $total,
            'percent'      => 100,
            'finished_at'  => now()->toISOString(),
        ]);

        $this->info("✓ اكتمل: {$extracted} ملف من أصل {$processed} سجل.");
        return 0;
    }

    private function writeProgress(string $file, array $data): void
    {
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function getExtensionFromBinary(string $data, string $default = 'jpg'): string
    {
        if (str_starts_with($data, '%PDF'))               return 'pdf';
        if (str_starts_with($data, "\xff\xd8\xff"))       return 'jpg';
        if (str_starts_with($data, "\x89PNG"))            return 'png';
        if (str_starts_with($data, "GIF8"))               return 'gif';
        if (str_starts_with($data, "PK\x03\x04"))         return 'docx';
        return $default;
    }

    /**
     * إرجاع أسماء الأعمدة النصية (غير Blob) لكل جدول
     * لتضمينها في SELECT بجانب عمود الملف دون جلب SELECT *
     */
    private function getTextColumns(string $table, string $column): string
    {
        $map = [
            'encadremen_memo'    => 'IDEncadrement',
            'candidat_memo'      => 'IDCandidat',
            'candidat_document'  => 'IDCandidat',
            'etablissement_memo' => 'IDEtablissement_memo, IDetablissement',
            'equipement_memo'    => 'IDEquipement_memo, IDEquipement',
            'logement_memo'      => 'IDLogement_memo, IDLogement',
            'vehicule_memo'      => 'IDVehicule_memo, IDVehicule',
            'dercrte_memo'       => 'IDDercrte_memo, IDDecretMfep',
            'dpca'               => 'IDDpca',
        ];
        $base = $map[$table] ?? '';
        if ($table === 'candidat_document') {
            $config = self::TABLES_CONFIG[$table];
            $mysqlCol = $config['columns'][$column]['mysql_col'] ?? null;
            if ($mysqlCol) {
                return $base ? "{$base}, {$mysqlCol}" : $mysqlCol;
            }
        }
        return $base;
    }
}

