<?php
/**
 * ══════════════════════════════════════════════════════════════════
 *  HFSQL → CSV Full Export Script
 *  يُصدّر جميع جداول HFSQL إلى ملفات CSV (باستثناء memo* و accesuser)
 *  الناتج: storage/hfsql_export/YYYY-MM-DD/  (ملف لكل جدول)
 * ══════════════════════════════════════════════════════════════════
 *  الاستخدام: افتح http://localhost/sig/hfsql_export.php في المتصفح
 * ══════════════════════════════════════════════════════════════════
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

// ── إعدادات الاتصال ──────────────────────────────────────────────
$DSN      = 'odbc:Driver={HFSQL};Server Name=197.112.101.166;Server Port=4900;Database=sig;IntegrityCheck=1';
$USERNAME = 'sig';
$PASSWORD = 'Sig@2023#2025';

// ── الجداول المستثناة دائماً ──────────────────────────────────────
$EXCLUDED = ['accesuser'];

// ── مجلد الخرج (داخل public/ حتى تكون قابلة للتنزيل مباشرة) ────
$outDir = __DIR__ . '/hfsql_export/' . date('Y-m-d_His') . '/';
$webBase = '/sig/hfsql_export/' . date('Y-m-d_His') . '/';

// ── دوال مساعدة ──────────────────────────────────────────────────
function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }
function isMemo(string $name): bool {
    $n = strtolower($name);
    return str_starts_with($n, 'memo') || str_starts_with($n, 'mmo');
}

// ── واجهة HTML ────────────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تصدير HFSQL إلى CSV</title>
<style>
  body { font-family: Cairo,sans-serif; background:#0f172a; color:#e2e8f0; padding:2rem; }
  h1   { color:#a78bfa; font-size:1.5rem; margin-bottom:1rem; }
  .log { background:#060b18; border:1px solid #1e293b; border-radius:.5rem; padding:1rem; font-family:monospace; font-size:.8rem; height:500px; overflow-y:auto; }
  .ok  { color:#34d399; } .err { color:#f87171; } .info { color:#60a5fa; } .warn { color:#fbbf24; }
  .stat{ background:#1e293b; border-radius:.5rem; padding:1rem; margin-top:1rem; display:flex; gap:2rem; flex-wrap:wrap; }
  .s   { text-align:center; }
  .s .n{ font-size:2rem; font-weight:800; color:#a78bfa; }
  .s .l{ font-size:.75rem; color:#64748b; }
  .dl  { margin-top:1rem; background:#1e293b; padding:1rem; border-radius:.5rem; }
  .dl a{ display:inline-block; margin:.25rem; padding:.35rem .8rem; background:#7c3aed; color:#fff; border-radius:.4rem; text-decoration:none; font-size:.75rem; }
  .dl a:hover { background:#6d28d9; }
  progress { width:100%; height:12px; margin-top:.5rem; accent-color:#7c3aed; }
</style>
</head>
<body>
<h1><span style="font-size:1.8rem;">📤</span> تصدير قاعدة بيانات HFSQL كاملة إلى CSV</h1>
<p style="color:#64748b;font-size:.85rem;">الخادم: <code style="color:#60a5fa;">197.112.101.166:4900</code> — قاعدة البيانات: <code style="color:#60a5fa;">sig</code></p>
<div class="log" id="log">';

ob_start();

// ── الاتصال بـ HFSQL ─────────────────────────────────────────────
function log_line(string $class, string $msg): void {
    echo "<div class=\"{$class}\">" . esc($msg) . "</div>\n";
    ob_flush(); flush();
}

log_line('info', '► جارٍ الاتصال بـ HFSQL...');

if (!extension_loaded('pdo_odbc')) {
    log_line('err', '✖ خطأ: امتداد pdo_odbc غير مُفعَّل في php.ini');
    echo '</div></body></html>';
    exit;
}

try {
    $pdo = new PDO($DSN, $USERNAME, $PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    log_line('ok', '✓ تم الاتصال بـ HFSQL بنجاح.');
} catch (Throwable $e) {
    log_line('err', '✖ فشل الاتصال: ' . $e->getMessage());
    echo '</div></body></html>';
    exit;
}

// ── الحصول على قائمة الجداول ─────────────────────────────────────
log_line('info', '► جارٍ جلب قائمة الجداول...');

try {
    // HFSQL uses SQLTables or INFORMATION_SCHEMA
    $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='TABLE' ORDER BY TABLE_NAME");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    // Fallback: use SQLTables via PDO
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e2) {
        log_line('err', '✖ فشل جلب الجداول: ' . $e->getMessage());
        echo '</div></body></html>';
        exit;
    }
}

// ── التصفية ──────────────────────────────────────────────────────
$tables   = [];
$skipped  = [];
foreach ($allTables as $t) {
    if (in_array(strtolower($t), array_map('strtolower', $EXCLUDED))) {
        $skipped[] = $t . ' (مستثنى يدوياً)';
        continue;
    }
    if (isMemo($t)) {
        $skipped[] = $t . ' (جدول memo/صور)';
        continue;
    }
    $tables[] = $t;
}

log_line('ok',   '✓ إجمالي الجداول في HFSQL: ' . count($allTables));
log_line('ok',   '✓ الجداول التي ستُصدَّر: ' . count($tables));
log_line('warn', '⚠ الجداول المستثناة: ' . count($skipped));
foreach ($skipped as $s) {
    log_line('warn', '  ⤷ ' . $s);
}
log_line('info', '');

// ── إنشاء مجلد الخرج ─────────────────────────────────────────────
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

// ── تصدير كل جدول ────────────────────────────────────────────────
$stats = ['tables' => 0, 'rows' => 0, 'errors' => 0, 'files' => []];

foreach ($tables as $table) {
    $csvPath = $outDir . $table . '.csv';
    $stats['tables']++;

    log_line('info', "► تصدير الجدول: {$table}...");

    try {
        // Get column list
        try {
            $colStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$table}' ORDER BY ORDINAL_POSITION");
            $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $ce) {
            // Fallback: read first row
            $columns = [];
        }

        // Open CSV file
        $fh = fopen($csvPath, 'w');
        fputs($fh, "\xEF\xBB\xBF"); // UTF-8 BOM

        $rowCount  = 0;
        $batchSize = 500;
        $offset    = 0;
        $headerWritten = false;

        // Stream in batches
        do {
            // HFSQL supports LIMIT/OFFSET
            $sql  = "SELECT * FROM {$table} LIMIT {$batchSize} OFFSET {$offset}";
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if (!$headerWritten && !empty($rows)) {
                $columns = array_keys($rows[0]);
                fputcsv($fh, $columns);
                $headerWritten = true;
            } elseif (!$headerWritten && !empty($columns)) {
                fputcsv($fh, $columns);
                $headerWritten = true;
            }

            foreach ($rows as $row) {
                // Clean binary/blob fields
                $cleaned = array_map(function($v) {
                    if (is_null($v)) return '';
                    if (is_string($v) && !mb_detect_encoding($v, 'UTF-8', true)) {
                        // Try CP1256 → UTF-8
                        $converted = @iconv('CP1256', 'UTF-8//IGNORE', $v);
                        return $converted !== false ? $converted : '';
                    }
                    return $v;
                }, $row);
                fputcsv($fh, $cleaned);
                $rowCount++;
            }

            $offset += $batchSize;
        } while (count($rows) === $batchSize);

        fclose($fh);

        $fileSize = round(filesize($csvPath) / 1024, 1);
        log_line('ok', "  ✓ {$table}: {$rowCount} صف → {$table}.csv ({$fileSize} KB)");

        $stats['rows'] += $rowCount;
        $stats['files'][] = ['name' => $table, 'rows' => $rowCount, 'size' => $fileSize];

    } catch (Throwable $e) {
        log_line('err', "  ✖ فشل تصدير {$table}: " . $e->getMessage());
        $stats['errors']++;
        if (file_exists($csvPath)) unlink($csvPath);
    }
}

// ── إنشاء ملف ZIP ────────────────────────────────────────────────
$zipPath = __DIR__ . '/hfsql_export/' . basename($outDir) . '.zip';
if (class_exists('ZipArchive')) {
    log_line('info', '');
    log_line('info', '► إنشاء ملف ZIP مضغوط...');
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
        foreach (glob($outDir . '*.csv') as $csvFile) {
            $zip->addFile($csvFile, basename($csvFile));
        }
        $zip->close();
        $zipSize = round(filesize($zipPath) / 1024 / 1024, 2);
        log_line('ok', "✓ تم إنشاء ZIP: " . basename($zipPath) . " ({$zipSize} MB)");
    } else {
        log_line('warn', '⚠ تعذّر إنشاء ZIP.');
        $zipPath = null;
    }
} else {
    log_line('warn', '⚠ امتداد ZipArchive غير متاح — الملفات CSV فقط.');
    $zipPath = null;
}

// ── ملخص النهاية ─────────────────────────────────────────────────
log_line('info', '');
log_line('ok', "══════════════════════════════════════════");
log_line('ok', " اكتمل التصدير بنجاح!");
log_line('ok', " الجداول: {$stats['tables']} | الصفوف: " . number_format($stats['rows']) . " | أخطاء: {$stats['errors']}");
log_line('ok', " المجلد: storage/hfsql_export/" . basename($outDir));
log_line('ok', "══════════════════════════════════════════");

echo '</div>'; // end log

// ── إحصائيات ─────────────────────────────────────────────────────
echo '<div class="stat">
  <div class="s"><div class="n">' . $stats['tables'] . '</div><div class="l">جدول مُصدَّر</div></div>
  <div class="s"><div class="n">' . number_format($stats['rows']) . '</div><div class="l">صف إجمالي</div></div>
  <div class="s"><div class="n">' . $stats['errors'] . '</div><div class="l">أخطاء</div></div>
  <div class="s"><div class="n">' . count($skipped) . '</div><div class="l">جداول مستثناة</div></div>
</div>';

// ── روابط التنزيل ────────────────────────────────────────────────
$folderName = basename($outDir);
$webFolder  = '/sig/hfsql_export/' . $folderName . '/';
$webZip     = '/sig/hfsql_export/' . $folderName . '.zip';

echo '<div class="dl">';
echo '<p style="color:#a78bfa;font-weight:700;margin-bottom:.5rem;">📥 تنزيل الملفات:</p>';

if ($zipPath && file_exists($zipPath)) {
    echo '<a href="' . $webZip . '" style="background:#065f46;font-size:.85rem;padding:.5rem 1.2rem;">⬇ تنزيل الكل (ZIP)</a><br><br>';
}

foreach ($stats['files'] as $f) {
    echo '<a href="' . $webFolder . esc($f['name']) . '.csv" download>' . esc($f['name']) . ' (' . $f['rows'] . ' صف)</a>';
}

echo '</div>';

// ── تعليمات الرفع ────────────────────────────────────────────────
echo '
<div style="margin-top:1.5rem;background:#1e293b;border-radius:.65rem;padding:1.25rem;border:1px solid rgba(139,92,246,.2);">
  <p style="color:#a78bfa;font-weight:700;font-size:.9rem;margin-bottom:.75rem;">🚀 الخطوة التالية — رفع ملفات CSV إلى MySQL:</p>
  <ol style="color:#94a3b8;font-size:.82rem;line-height:2;">
    <li>افتح صفحة <a href="/sig/dashboard/import" style="color:#a78bfa;">لوحة استيراد البيانات</a></li>
    <li>اختر الجدول المستهدف من القائمة الجانبية</li>
    <li>ارفع ملف CSV الخاص بهذا الجدول</li>
    <li>اختر وضع <b>Upsert</b> (إدراج + تحديث) أو <b>Insert</b></li>
    <li>اضغط "بدء الاستيراد" — يعالج تلقائياً بدون انتهاء الذاكرة</li>
  </ol>
</div>';

echo '</body></html>';
