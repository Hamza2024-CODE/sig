<?php

namespace App\Domains\Reporting\Engines;

use App\Domains\Reporting\Contracts\ReportEngineInterface;

/**
 * PdfEngine
 *
 * Generates and streams printable PDF reports using mPDF.
 * Consumes the data Generator in chunks to keep memory usage at O(chunk_size).
 *
 * Arabic Support:
 *   - Auto script/lang to font matching (autoScriptToLang & autoLangToFont)
 *   - CSS layout with dir="rtl" and Arabic typography.
 */
class PdfEngine implements ReportEngineInterface
{
    private const CHUNK_ROWS = 100;

    /**
     * @inheritDoc
     */
    public function stream(\Generator $data, array $columns, array $meta): void
    {
        $filename = $this->buildFilename($meta);

        // ── HTTP & Buffer Cleaning ───────────────────────────────────────────
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        // ── mPDF Initialization ──────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'margin_right' => 15
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle($meta['title'] ?? 'تقرير SGFEP');

        // ── Render HTML Document Start ───────────────────────────────────────
        $htmlOpen = $this->renderDocumentOpen($meta, $columns);
        $mpdf->WriteHTML($htmlOpen);

        // ── Stream / Process Data Rows in Chunks ─────────────────────────────
        $rowCount        = 0;
        $rowBuffer       = [];
        $totalStagiaires = 0;
        $totalEquipped   = 0;

        foreach ($data as $row) {
            $rowBuffer[] = $row;
            $rowCount++;

            if (isset($row['nombre_stagiaires'])) {
                $totalStagiaires += (int)$row['nombre_stagiaires'];
            }
            if (isset($row['equipements']) && ($row['equipements'] === 'نعم' || $row['equipements'] === 1 || $row['equipements'] === '1')) {
                $totalEquipped++;
            }

            if (count($rowBuffer) >= self::CHUNK_ROWS) {
                $htmlRows = $this->renderRows($rowBuffer, $columns);
                $mpdf->WriteHTML($htmlRows);
                $rowBuffer = [];
                gc_collect_cycles(); // trigger garbage collection
            }
        }

        // Render remaining rows
        if (!empty($rowBuffer)) {
            $htmlRows = $this->renderRows($rowBuffer, $columns);
            $mpdf->WriteHTML($htmlRows);
        }

        // ── Render HTML Document End ─────────────────────────────────────────
        $htmlClose = $this->renderDocumentClose($rowCount, $meta, $totalStagiaires, $totalEquipped);
        $mpdf->WriteHTML($htmlClose);

        // ── Output directly as inline PDF ────────────────────────────────────
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    }

    // ─── HTML Template Helpers ────────────────────────────────────────────────

    private function renderDocumentOpen(array $meta, array $columns): string
    {
        $title       = htmlspecialchars($meta['title'] ?? 'تقرير SGFEP');
        $generatedBy = htmlspecialchars($meta['generated_by'] ?? '');
        $date        = date('Y-m-d H:i');
        $byLine      = $generatedBy !== '' ? ' &nbsp;|&nbsp; أعدّه: ' . $generatedBy : '';

        $headers = '';
        foreach ($columns as $label) {
            $headers .= '<th>' . htmlspecialchars($label) . '</th>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <style>
        body {
            font-family: 'dejavusans', 'Arial', sans-serif;
            font-size: 10pt;
            color: #1a1a2e;
            direction: rtl;
        }
        .report-header {
            text-align: center;
            border-bottom: 2px solid #1a1a2e;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .report-header h1 { font-size: 14pt; margin-bottom: 4px; font-weight: bold; }
        .report-header .meta { font-size: 8.5pt; color: #555; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-top: 10px;
        }
        thead th {
            background-color: #1a1a2e;
            color: #ffffff;
            padding: 6px 5px;
            text-align: center;
            border: 1px solid #333333;
            font-weight: bold;
        }
        tbody tr:nth-child(even) { background-color: #f4f6fb; }
        tbody td {
            padding: 5px;
            border: 1px solid #cccccc;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>{$title}</h1>
        <div class="meta">
            تاريخ الاستخراج: {$date}
            {$byLine}
        </div>
    </div>
    <table>
        <thead>
            <tr>{$headers}</tr>
        </thead>
        <tbody>
HTML;
    }

    private function renderRows(array $rows, array $columns): string
    {
        $html = '';
        $keys = array_keys($columns);

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($keys as $key) {
                $val = htmlspecialchars((string)($row[$key] ?? ''));
                $html .= "<td>{$val}</td>";
            }
            $html .= '</tr>' . "\n";
        }

        return $html;
    }

    private function renderDocumentClose(int $rowCount, array $meta, int $totalStagiaires = 0, int $totalEquipped = 0): string
    {
        $countLabel = number_format($rowCount);
        $title      = htmlspecialchars($meta['title'] ?? '');

        $statsHtml = '';
        if (($meta['report_type'] ?? '') === 'specialites_encours') {
            $totalStagiairesLabel = number_format($totalStagiaires);
            $totalEquippedLabel = number_format($totalEquipped);
            $statsHtml = <<<HTML
            <div style="margin-top: 15px; margin-bottom: 15px; padding: 12px 16px; border: 1.5px solid #1a1a2e; background-color: #f4f6fb; font-size: 10.5pt; text-align: right; border-radius: 6px; display: inline-block; direction: rtl; min-width: 400px;">
                <strong style="color: #1a1a2e; font-size: 11pt; display: block; border-bottom: 1px solid #1a1a2e; padding-bottom: 4px; margin-bottom: 6px;">📊 إحصائيات إجمالية للتقرير :</strong>
                <span style="display: inline-block; margin-left: 20px;">عدد التخصصات النشطة: <strong>{$countLabel}</strong></span>
                <span style="display: inline-block; margin-left: 20px;">إجمالي المتربصين: <strong>{$totalStagiairesLabel}</strong></span>
                <span style="display: inline-block;">التخصصات ذات العتاد المتوفر: <strong>{$totalEquippedLabel}</strong></span>
            </div>
HTML;
        }

        return <<<HTML
        </tbody>
    </table>
    {$statsHtml}
    <div style="margin-top: 15px; font-size: 8.5pt; color: #666; text-align: center; border-top: 1px solid #cccccc; padding-top: 6px;">
        إجمالي السجلات: <strong>{$countLabel}</strong> &nbsp;|&nbsp; {$title} &nbsp;|&nbsp; SGFEP / MFEP الجزائر
    </div>
</body>
</html>
HTML;
    }

    private function buildFilename(array $meta): string
    {
        $slug = preg_replace('/[^a-z0-9_-]/i', '_', $meta['report_type'] ?? 'export');
        return 'sgfep_' . $slug . '_' . date('Ymd_His') . '.pdf';
    }
}
