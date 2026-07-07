<?php

namespace App\Domains\Reporting\Engines;

use App\Domains\Reporting\Contracts\ReportEngineInterface;

/**
 * CsvEngine
 *
 * Streams data directly to the HTTP response as a UTF-8 CSV file.
 *
 * Memory profile: O(1) — only one row occupies RAM at any moment.
 * The Generator is consumed lazily; each row is written to php://output
 * and immediately discarded before the next row is fetched from DB.
 *
 * Arabic Excel compatibility:
 *   - UTF-8 BOM (\xEF\xBB\xBF) prepended so Excel auto-detects encoding
 *   - Column separator: comma (standard CSV)
 *   - ob_flush() + flush() after each chunk ensures partial delivery to browser
 */
class CsvEngine implements ReportEngineInterface
{
    /** Number of rows between buffer flushes */
    private const FLUSH_EVERY = 50;

    /**
     * @inheritDoc
     */
    public function stream(\Generator $data, array $columns, array $meta): void
    {
        $filename = $this->buildFilename($meta);

        // ── HTTP Headers ──────────────────────────────────────────────────────
        // Disable any output buffering that could accumulate data in RAM
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        // ── Output Stream ─────────────────────────────────────────────────────
        $out = fopen('php://output', 'wb');

        // UTF-8 BOM — critical for Excel to open Arabic CSV without garbling
        fwrite($out, "\xEF\xBB\xBF");

        // ── Metadata rows (report header block) ───────────────────────────────
        fputcsv($out, ['#', 'SGFEP — ' . ($meta['title'] ?? 'تصدير البيانات')]);
        fputcsv($out, ['#', 'تاريخ الاستخراج: ' . date('Y-m-d H:i:s')]);
        if (!empty($meta['generated_by'])) {
            fputcsv($out, ['#', 'أعدّه: ' . $meta['generated_by']]);
        }
        fputcsv($out, []); // blank separator row

        // ── Column Header Row ─────────────────────────────────────────────────
        fputcsv($out, array_values($columns));

        // ── Data Rows — streamed via Generator ───────────────────────────────
        $rowCount        = 0;
        $totalStagiaires = 0;
        $totalEquipped   = 0;

        foreach ($data as $row) {
            $csvRow = [];
            foreach (array_keys($columns) as $key) {
                $csvRow[] = $row[$key] ?? '';
            }
            fputcsv($out, $csvRow);
            $rowCount++;

            if (isset($row['nombre_stagiaires'])) {
                $totalStagiaires += (int)$row['nombre_stagiaires'];
            }
            if (isset($row['equipements']) && ($row['equipements'] === 'نعم' || $row['equipements'] === 1 || $row['equipements'] === '1')) {
                $totalEquipped++;
            }

            // Periodic flush to avoid output buffer accumulation
            if ($rowCount % self::FLUSH_EVERY === 0) {
                fflush($out);
                flush();
            }
        }

        // Add summary rows at the bottom for specialites_encours
        if (($meta['report_type'] ?? '') === 'specialites_encours') {
            fputcsv($out, []);
            fputcsv($out, ['📊 إحصائيات إجمالية للتقرير']);
            fputcsv($out, ['عدد التخصصات النشطة', $rowCount]);
            fputcsv($out, ['إجمالي عدد المتربصين', $totalStagiaires]);
            fputcsv($out, ['التخصصات ذات العتاد المتوفر', $totalEquipped]);
        }

        // Final flush
        fflush($out);
        fclose($out);
        flush();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function buildFilename(array $meta): string
    {
        $slug = preg_replace('/[^a-z0-9_-]/i', '_', $meta['report_type'] ?? 'export');
        return 'sgfep_' . $slug . '_' . date('Ymd_His') . '.csv';
    }
}
