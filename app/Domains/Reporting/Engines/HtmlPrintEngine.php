<?php

namespace App\Domains\Reporting\Engines;

use App\Domains\Reporting\Contracts\ReportEngineInterface;

/**
 * HtmlPrintEngine
 *
 * Renders a browser-printable HTML table directly to the HTTP response.
 * Uses CSS @media print for clean paper output — zero server-side PDF dependency.
 *
 * Memory profile: O(chunk_size) — data is buffered in small chunks of CHUNK_ROWS
 * rows to build the HTML table body, then flushed to the browser before the next chunk.
 *
 * Arabic support:
 *   - dir="rtl" on the document
 *   - Arabic font stack with Google Fonts (Noto Naskh Arabic)
 *   - Headers and metadata fully in Arabic
 */
class HtmlPrintEngine implements ReportEngineInterface
{
    /** Max rows to buffer in memory before flushing HTML to browser */
    private const CHUNK_ROWS = 100;

    /**
     * @inheritDoc
     */
    public function stream(\Generator $data, array $columns, array $meta): void
    {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-cache');

        // ── HTML Document Open ────────────────────────────────────────────────
        echo $this->renderDocumentOpen($meta, $columns);
        flush();

        // ── Data Rows — chunked streaming ─────────────────────────────────────
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
                echo $this->renderRows($rowBuffer, $columns);
                $rowBuffer = [];
                flush();
            }
        }

        // Flush remaining rows
        if (!empty($rowBuffer)) {
            echo $this->renderRows($rowBuffer, $columns);
            flush();
        }

        // ── HTML Document Close ───────────────────────────────────────────────
        echo $this->renderDocumentClose($rowCount, $meta, $totalStagiaires, $totalEquipped);
        flush();
    }

    // ─── HTML Rendering Helpers ───────────────────────────────────────────────

    private function renderDocumentOpen(array $meta, array $columns): string
    {
        $title      = htmlspecialchars($meta['title'] ?? 'تقرير SGFEP');
        $generatedBy = htmlspecialchars($meta['generated_by'] ?? '');
        $date        = date('Y-m-d H:i');
        // Pre-compute expression before heredoc (PHP doesn't allow ternary inside heredoc)
        $byLine = $generatedBy !== '' ? ' &nbsp;|&nbsp; أعدّه: ' . $generatedBy : '';

        $headers = '';
        foreach ($columns as $label) {
            $headers .= '<th>' . htmlspecialchars($label) . '</th>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Noto Naskh Arabic', 'Arial', sans-serif;
            font-size: 11pt;
            color: #1a1a2e;
            background: #fff;
            padding: 20px;
            direction: rtl;
        }
        .report-header {
            text-align: center;
            border-bottom: 3px solid #1a1a2e;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .report-header h1 { font-size: 16pt; margin-bottom: 4px; }
        .report-header .meta { font-size: 9pt; color: #555; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-top: 10px;
        }
        thead th {
            background: #1a1a2e;
            color: #fff;
            padding: 7px 6px;
            text-align: center;
            border: 1px solid #333;
            font-weight: bold;
        }
        tbody tr:nth-child(even) { background: #f4f6fb; }
        tbody td {
            padding: 5px 6px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }
        .report-footer {
            margin-top: 20px;
            font-size: 9pt;
            color: #666;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
        .no-print { display: block; margin-bottom: 12px; text-align: center; }
        .no-print button {
            background: #1a1a2e;
            color: white;
            border: none;
            padding: 10px 28px;
            font-size: 12pt;
            cursor: pointer;
            border-radius: 6px;
            font-family: inherit;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 5mm; font-size: 9pt; }
            thead th { background: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody tr:nth-child(even) { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ طباعة التقرير</button>
    </div>
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
            <div style="margin-top: 15px; margin-bottom: 15px; padding: 12px 16px; border: 2px solid #1a1a2e; background-color: #f4f6fb; font-size: 10.5pt; text-align: right; border-radius: 6px; display: inline-block; direction: rtl; min-width: 400px;">
                <strong style="color: #1a1a2e; font-size: 11.5pt; display: block; border-bottom: 1px solid #1a1a2e; padding-bottom: 6px; margin-bottom: 8px;">📊 إحصائيات إجمالية للتقرير :</strong>
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
    <div class="report-footer">
        إجمالي السجلات: <strong>{$countLabel}</strong> &nbsp;|&nbsp; {$title} &nbsp;|&nbsp; SGFEP / MFEP الجزائر
    </div>
</body>
</html>
HTML;
    }
}
