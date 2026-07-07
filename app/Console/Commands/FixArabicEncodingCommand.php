<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixArabicEncodingCommand extends Command
{
    protected $signature = 'db:fix-arabic {--table= : Specific table to clean up} {--dry-run : Only show what would be fixed without updating the DB}';
    protected $description = 'كشف وإصلاح النصوص العربية ذات الترميز التالف (Double-Encoding / Mojibake) في قاعدة البيانات';

    public function handle()
    {
        $specificTable = $this->option('table');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️ تشغيل في وضع التجربة (Dry-run) - لن يتم تعديل أي بيانات في قاعدة البيانات.');
        }

        $this->info('بدء عملية كشف وإصلاح ترميز اللغة العربية...');

        // 1. Get database name
        $dbName = DB::connection()->getDatabaseName();
        $this->info("قاعدة البيانات الحالية: {$dbName}");

        // 2. Fetch all tables
        if ($specificTable) {
            $tables = [$specificTable];
        } else {
            $tables = array_map(function($t) {
                return current((array)$t);
            }, DB::select("SHOW TABLES"));
        }

        $totalFixedRows = 0;
        $totalCheckedRows = 0;

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                $this->error("الجدول '{$tableName}' غير موجود.");
                continue;
            }

            // Exclude system/temporary tables if any
            if (in_array($tableName, ['failed_jobs', 'migrations', 'personal_access_tokens', 'sessions', 'audit_logs'])) {
                continue;
            }

            // 3. Describe table to find string columns and primary key
            $columns = DB::select("DESCRIBE `$tableName`");
            $stringCols = [];
            $pkCol = null;

            foreach ($columns as $col) {
                $type = strtolower($col->Type);
                if (str_contains($type, 'char') || str_contains($type, 'text')) {
                    $stringCols[] = $col->Field;
                }
                if ($col->Key === 'PRI') {
                    $pkCol = $col->Field; // Usually the first primary key is fine
                }
            }

            if (empty($stringCols)) {
                continue;
            }

            $this->info("جاري فحص الجدول: {$tableName} (الأعمدة النصية: " . implode(', ', $stringCols) . ")...");

            // 4. Query and process rows
            // We use chunking for large tables if PK exists, otherwise fetch all
            $fixedInTable = 0;
            $checkedInTable = 0;

            $processRows = function($rows) use ($tableName, $stringCols, $pkCol, $dryRun, &$fixedInTable, &$checkedInTable) {
                foreach ($rows as $row) {
                    $checkedInTable++;
                    $updates = [];
                    $pkVal = $pkCol ? $row->{$pkCol} : null;

                    foreach ($stringCols as $col) {
                        $originalValue = $row->{$col};
                        if ($originalValue === null || trim($originalValue) === '') {
                            continue;
                        }

                        $fixedValue = $this->fixDoubleEncoding($originalValue);
                        if ($fixedValue !== $originalValue) {
                            $updates[$col] = $fixedValue;
                        }
                    }

                    if (!empty($updates)) {
                        $fixedInTable++;
                        if (!$dryRun) {
                            if ($pkCol && $pkVal !== null) {
                                DB::table($tableName)->where($pkCol, $pkVal)->update($updates);
                            } else {
                                // If no PK, try to match by all original row fields to avoid double updates
                                $query = DB::table($tableName);
                                foreach ((array)$row as $k => $v) {
                                    if ($v === null) {
                                        $query->whereNull($k);
                                    } else {
                                        $query->where($k, $v);
                                    }
                                }
                                $query->update($updates);
                            }
                        } else {
                            $this->line("  [تعديل] معرف السجل: " . ($pkVal ?? 'غير معروف') . " | التعديلات: " . json_encode($updates, JSON_UNESCAPED_UNICODE));
                        }
                    }
                }
            };

            try {
                if ($pkCol) {
                    DB::table($tableName)->orderBy($pkCol)->chunk(500, $processRows);
                } else {
                    $rows = DB::table($tableName)->get();
                    $processRows($rows);
                }

                if ($fixedInTable > 0) {
                    $this->info("✔️ تم إصلاح {$fixedInTable} سجل في الجدول '{$tableName}'.");
                }
                $totalFixedRows += $fixedInTable;
                $totalCheckedRows += $checkedInTable;

            } catch (\Throwable $e) {
                $this->error("حدث خطأ أثناء معالجة الجدول '{$tableName}': " . $e->getMessage());
            }
        }

        $this->info("========================================");
        $this->info("اكتمال العملية بنجاح!");
        $this->info("إجمالي السجلات التي تم فحصها: {$totalCheckedRows}");
        $this->info("إجمالي السجلات التي تم إصلاحها: {$totalFixedRows}");
        $this->info("========================================");

        return 0;
    }

    /**
     * Self-detects and repairs CP850/Windows-1252 double-encoding to UTF-8.
     * Supports both Type A (one-step fix) and Type B (two-step fix).
     */
    private function fixDoubleEncoding(string $str): string
    {
        $originalArabicCount = $this->countArabicChars($str);

        // Try to convert UTF-8 -> CP850 (reversing the double encoding)
        $bytes1 = @iconv('UTF-8', 'CP850//IGNORE', $str);
        if ($bytes1 !== false && $bytes1 !== '') {
            // 1. Check if one-step conversion works (Type A)
            if (mb_check_encoding($bytes1, 'UTF-8')) {
                $fixedArabicCount = $this->countArabicChars($bytes1);
                if ($fixedArabicCount > $originalArabicCount) {
                    return $bytes1;
                }
            }

            // 2. Check if two-step conversion works (Type B)
            $fixed2 = @iconv('Windows-1256', 'UTF-8//IGNORE', $bytes1);
            if ($fixed2 !== false && $fixed2 !== '' && mb_check_encoding($fixed2, 'UTF-8')) {
                $fixedArabicCount2 = $this->countArabicChars($fixed2);
                if ($fixedArabicCount2 > $originalArabicCount) {
                    return $fixed2;
                }
            }
        }

        return $str;
    }

    private function countArabicChars(string $str): int
    {
        return (int) preg_match_all('/\p{Arabic}/u', $str);
    }
}
