@extends('layouts.main')
@section('title', $title ?? 'استيراد البيانات — SGFEP')
@section('content')
<?php
/**
 * @var string $title
 * @var array  $tables  [['name' => '...', 'row_count' => n], ...]
 */
$tables = $tables ?? [];
?>

<style>
/* ── Import Dashboard Styles ──────────────────────────────────── */
.import-hero {
    background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
    border-radius: 1.25rem;
    padding: 2rem 2.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}
.import-hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 280px; height: 280px;
    background: rgba(139,92,246,0.18);
    border-radius: 50%;
    filter: blur(60px);
}
.import-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -60px;
    width: 200px; height: 200px;
    background: rgba(59,130,246,0.15);
    border-radius: 50%;
    filter: blur(50px);
}
.hero-badge {
    display: inline-block;
    background: rgba(139,92,246,0.25);
    border: 1px solid rgba(139,92,246,0.45);
    color: #c4b5fd;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 0.28rem 0.85rem;
    border-radius: 100px;
    margin-bottom: 0.7rem;
}
.table-search-input {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.15);
    color: #e2e8f0;
    border-radius: 0.6rem;
    padding: 0.5rem 0.85rem;
    font-size: 0.82rem;
    width: 100%;
    outline: none;
    transition: border-color .2s;
}
.table-search-input::placeholder { color: #94a3b8; }
.table-search-input:focus { border-color: #8b5cf6; }
.table-list-wrap {
    max-height: 320px;
    overflow-y: auto;
    margin-top: 0.5rem;
    scrollbar-width: thin;
    scrollbar-color: #4c3490 transparent;
}
.table-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.55rem 0.85rem;
    border-radius: 0.55rem;
    cursor: pointer;
    transition: background .15s;
    font-size: 0.82rem;
    font-family: 'Fira Mono', monospace;
    color: #cbd5e1;
    border: 1.5px solid transparent;
    margin-bottom: 2px;
}
.table-item:hover { background: rgba(139,92,246,0.12); color: #fff; }
.table-item.selected {
    background: rgba(139,92,246,0.22);
    border-color: rgba(139,92,246,0.55);
    color: #e9d5ff;
    font-weight: 600;
}
.table-badge {
    font-size: 0.65rem;
    background: rgba(255,255,255,0.08);
    padding: 0.15rem 0.5rem;
    border-radius: 100px;
    color: #94a3b8;
    font-family: 'Cairo', sans-serif;
    font-weight: 600;
}
.schema-card {
    background: #0f172a;
    border-radius: 0.75rem;
    border: 1px solid #1e293b;
    overflow: hidden;
    font-size: 0.78rem;
}
.schema-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    padding: 0.38rem 0.85rem;
    border-bottom: 1px solid #1e293b;
    transition: background .12s;
}
.schema-row:last-child { border-bottom: none; }
.schema-row:hover { background: rgba(139,92,246,0.07); }
.schema-field { color: #93c5fd; font-family: 'Fira Mono', monospace; flex: 1; }
.schema-type  { color: #6ee7b7; font-family: 'Fira Mono', monospace; flex: 1; font-size: 0.72rem; }
.schema-key   { color: #fcd34d; font-size: 0.65rem; }
.dropzone-wrap {
    border: 2px dashed rgba(139,92,246,0.45);
    border-radius: 0.9rem;
    padding: 2rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all .2s ease;
    position: relative;
    background: rgba(139,92,246,0.04);
}
.dropzone-wrap:hover, .dropzone-wrap.over {
    border-color: #8b5cf6;
    background: rgba(139,92,246,0.10);
}
.dropzone-wrap input[type=file] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
.log-console {
    background: #060b18;
    border: 1px solid #1e293b;
    border-radius: 0.75rem;
    font-family: 'Fira Mono', monospace;
    font-size: 0.73rem;
    height: 220px;
    overflow-y: auto;
    padding: 0.85rem 1rem;
    scrollbar-width: thin;
    scrollbar-color: #312e81 transparent;
}
.log-console .log-sys     { color: #60a5fa; }
.log-console .log-ok      { color: #34d399; }
.log-console .log-warn    { color: #fbbf24; }
.log-console .log-err     { color: #f87171; }
.log-console .log-info    { color: #a78bfa; }
.mode-btn { cursor: pointer; }
.mode-btn input[type=radio]:checked + label {
    background: rgba(139,92,246,0.2);
    border-color: #8b5cf6 !important;
    color: #e9d5ff;
}
.mode-btn label {
    border: 1.5px solid rgba(255,255,255,0.1);
    border-radius: 0.55rem;
    padding: 0.45rem 0.9rem;
    font-size: 0.78rem;
    cursor: pointer;
    display: block;
    color: #94a3b8;
    transition: all .15s;
    text-align: center;
    line-height: 1.4;
}
.stat-box {
    border-radius: 0.85rem;
    padding: 1.1rem 1rem;
    text-align: center;
    flex: 1;
}
.btn-export {
    background: linear-gradient(135deg,#064e3b,#065f46);
    color: #6ee7b7;
    border: 1px solid rgba(110,231,183,0.25);
    border-radius: 0.6rem;
    padding: 0.38rem 1rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.btn-export:hover {
    background: linear-gradient(135deg,#065f46,#047857);
    color: #a7f3d0;
    text-decoration: none;
}
.btn-start {
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff;
    border: none;
    border-radius: 0.7rem;
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    transition: all .2s;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-start:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    background: #334155;
}
.btn-start:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(124,58,237,0.35);
}
.progress-track {
    height: 10px;
    background: #1e293b;
    border-radius: 100px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    border-radius: 100px;
    background: linear-gradient(90deg, #7c3aed, #4f46e5, #06b6d4);
    background-size: 200% 100%;
    transition: width .4s ease;
    animation: shimmer 2s linear infinite;
}
@keyframes shimmer {
    0%   { background-position: 200% center; }
    100% { background-position: -200% center; }
}
.card-panel {
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 1rem;
    overflow: hidden;
}
.card-panel-header {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid #1e293b;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(139,92,246,0.05);
}
.section-title {
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    color: #e2e8f0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.col-map-tag {
    display: inline-block;
    padding: 0.18rem 0.55rem;
    border-radius: 100px;
    font-size: 0.65rem;
    font-weight: 700;
    font-family: 'Fira Mono', monospace;
    margin: 2px;
}
.col-map-match { background: rgba(52,211,153,0.12); color: #34d399; border: 1px solid rgba(52,211,153,0.25); }
.col-map-miss  { background: rgba(248,113,113,0.10); color: #f87171; border: 1px solid rgba(248,113,113,0.20); }
</style>

<div class="container-fluid py-3 px-4" dir="rtl" style="max-width: 1600px; margin: 0 auto;">

    {{-- ── Hero ──────────────────────────────────────────────────────────── --}}
    <div class="import-hero">
        <div class="hero-badge"><i class="fa-solid fa-database me-1"></i> Database Import Center</div>
        <h1 class="fw-bold mb-1" style="font-family:'Cairo';font-size:1.7rem;">لوحة استيراد وتصدير قاعدة البيانات</h1>
        <p class="mb-0 opacity-75" style="font-size:0.88rem;">
            رفع ملفات CSV/Excel مباشرة إلى أي جدول بطريقة مجزأة موفرة للذاكرة — أو تصدير أي جدول إلى ملف CSV
        </p>
        <div class="mt-3 d-flex flex-wrap gap-3" style="position:relative;z-index:1;">
            <div style="background:rgba(255,255,255,0.07);border-radius:.65rem;padding:.5rem 1.1rem;font-size:.78rem;color:#e2e8f0;">
                <i class="fa-solid fa-table me-1 text-violet-400" style="color:#a78bfa;"></i>
                <span id="heroTableCount">{{ count($tables) }}</span> جدول متاح
            </div>
            <div style="background:rgba(255,255,255,0.07);border-radius:.65rem;padding:.5rem 1.1rem;font-size:.78rem;color:#e2e8f0;">
                <i class="fa-solid fa-shield-halved me-1" style="color:#34d399;"></i>
                تصفية تلقائية لجداول الصور والنظام
            </div>
            <div style="background:rgba(255,255,255,0.07);border-radius:.65rem;padding:.5rem 1.1rem;font-size:.78rem;color:#e2e8f0;">
                <i class="fa-solid fa-memory me-1" style="color:#60a5fa;"></i>
                ذاكرة ثابتة O(1) بمعالجة مجزأة
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── Column 1: Table Selector + Schema ──────────────────────────── --}}
        <div class="col-xl-3 col-lg-4">

            {{-- Table Picker --}}
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <span class="section-title">
                        <i class="fa-solid fa-table-list" style="color:#a78bfa;"></i>
                        اختر الجدول
                    </span>
                    <span class="badge" style="background:rgba(139,92,246,.2);color:#c4b5fd;font-size:.65rem;">
                        {{ count($tables) }} جدول
                    </span>
                </div>
                <div class="p-3">
                    <input type="text" id="tableSearch" class="table-search-input mb-1"
                           placeholder="🔍 ابحث عن جدول..." autocomplete="off">
                    <div class="table-list-wrap" id="tableList">
                        @foreach($tables as $t)
                        <div class="table-item" data-table="{{ $t['name'] }}"
                             onclick="selectTable('{{ $t['name'] }}')">
                            <span>{{ $t['name'] }}</span>
                            <span class="table-badge">{{ number_format($t['row_count']) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Schema Preview --}}
            <div class="card-panel" id="schemaPanel" style="display:none;">
                <div class="card-panel-header">
                    <span class="section-title">
                        <i class="fa-solid fa-code" style="color:#6ee7b7;"></i>
                        <span id="schemaPanelTitle">هيكل الجدول</span>
                    </span>
                    <a id="exportBtn" href="#" class="btn-export" target="_blank">
                        <i class="fa-solid fa-file-csv"></i> تصدير CSV
                    </a>
                </div>
                <div class="p-2">
                    <div id="schemaContent">
                        <div class="schema-card">
                            <div id="schemaRows"></div>
                        </div>
                    </div>
                    <div class="mt-2 text-center" style="font-size:.72rem;color:#64748b;">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <span id="schemaRowCount">-</span> صف في الجدول حالياً
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Column 2: Upload + Config ────────────────────────────────────── --}}
        <div class="col-xl-4 col-lg-4">
            <div class="card-panel h-100">
                <div class="card-panel-header">
                    <span class="section-title">
                        <i class="fa-solid fa-cloud-arrow-up" style="color:#60a5fa;"></i>
                        رفع ملف الاستيراد
                    </span>
                </div>
                <div class="p-4">

                    {{-- Selected Table Info --}}
                    <div id="selectedTableInfo" class="mb-4"
                         style="background:rgba(139,92,246,0.07);border:1px solid rgba(139,92,246,0.2);border-radius:.65rem;padding:.75rem 1rem;display:none;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-table text-violet-400" style="color:#a78bfa;"></i>
                            <span style="font-family:'Fira Mono',monospace;font-weight:700;color:#e9d5ff;font-size:.85rem;" id="selectedTableName">—</span>
                        </div>
                        <div id="colMatchPreview" class="mt-2" style="display:none;"></div>
                    </div>
                    <div id="noTableWarning" class="mb-4"
                         style="background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.2);border-radius:.65rem;padding:.75rem 1rem;font-size:.78rem;color:#fbbf24;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        يرجى اختيار جدول من القائمة أولاً قبل رفع الملف.
                    </div>

                    {{-- Dropzone --}}
                    <div class="dropzone-wrap mb-4" id="dropzone">
                        <input type="file" id="fileInput" accept=".csv,.xlsx,.xls">
                        <div style="font-size:2.5rem;color:#8b5cf6;margin-bottom:.5rem;">
                            <i class="fa-solid fa-file-arrow-up"></i>
                        </div>
                        <div id="fileLabel" style="font-family:'Cairo';font-weight:700;color:#cbd5e1;margin-bottom:.25rem;">
                            اسحب ملف CSV أو Excel هنا
                        </div>
                        <div style="font-size:.72rem;color:#64748b;">أو انقر للتصفح — حجم أقصى 50 ميجابايت</div>
                    </div>

                    {{-- Import Mode --}}
                    <div class="mb-4">
                        <label class="d-block mb-2" style="font-family:'Cairo';font-weight:700;color:#94a3b8;font-size:.78rem;">
                            <i class="fa-solid fa-sliders me-1"></i> طريقة الاستيراد:
                        </label>
                        <div class="d-flex gap-2">
                            <div class="mode-btn flex-1">
                                <input type="radio" name="import_mode" id="modeInsert" value="insert" class="d-none" checked>
                                <label for="modeInsert">
                                    <i class="fa-solid fa-plus-circle d-block mb-1 text-blue-400" style="color:#60a5fa;font-size:1.1rem;"></i>
                                    إدراج<br><small style="font-size:.65rem;opacity:.7;">تجاهل المكررات</small>
                                </label>
                            </div>
                            <div class="mode-btn flex-1">
                                <input type="radio" name="import_mode" id="modeUpsert" value="upsert" class="d-none">
                                <label for="modeUpsert">
                                    <i class="fa-solid fa-arrows-rotate d-block mb-1" style="color:#34d399;font-size:1.1rem;"></i>
                                    تحديث<br><small style="font-size:.65rem;opacity:.7;">INSERT … UPDATE</small>
                                </label>
                            </div>
                            <div class="mode-btn flex-1">
                                <input type="radio" name="import_mode" id="modeReplace" value="replace" class="d-none">
                                <label for="modeReplace">
                                    <i class="fa-solid fa-right-left d-block mb-1" style="color:#f97316;font-size:1.1rem;"></i>
                                    استبدال<br><small style="font-size:.65rem;opacity:.7;">REPLACE INTO</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Chunk Size --}}
                    <div class="mb-4">
                        <label class="d-block mb-1" style="font-family:'Cairo';font-weight:700;color:#94a3b8;font-size:.78rem;">
                            <i class="fa-solid fa-layer-group me-1"></i>
                            حجم الجزء (صفوف في كل دفعة):
                            <span id="chunkVal" style="color:#a78bfa;font-weight:800;">200</span>
                        </label>
                        <input type="range" id="chunkSize" min="50" max="1000" step="50" value="200"
                               class="form-range" style="accent-color:#8b5cf6;"
                               oninput="document.getElementById('chunkVal').textContent=this.value">
                        <div class="d-flex justify-content-between" style="font-size:.65rem;color:#475569;">
                            <span>50 (آمن)</span><span>500</span><span>1000 (سريع)</span>
                        </div>
                    </div>

                    {{-- Start Button --}}
                    <button class="btn-start" id="startBtn" disabled>
                        <i class="fa-solid fa-rocket"></i>
                        بدء الاستيراد
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Column 3: Progress + Log ─────────────────────────────────────── --}}
        <div class="col-xl-5 col-lg-4">
            <div class="card-panel h-100">
                <div class="card-panel-header">
                    <span class="section-title">
                        <i class="fa-solid fa-chart-line" style="color:#fb923c;"></i>
                        تقدم العملية
                    </span>
                    <span id="statusBadge"
                          style="font-size:.72rem;font-weight:700;padding:.28rem .85rem;border-radius:100px;background:rgba(100,116,139,.2);color:#94a3b8;">
                        في الانتظار
                    </span>
                </div>
                <div class="p-4">

                    {{-- Stats Row --}}
                    <div class="d-flex gap-3 mb-4">
                        <div class="stat-box" style="background:rgba(52,211,153,0.07);border:1px solid rgba(52,211,153,0.15);">
                            <div class="fw-bold" style="font-size:1.8rem;color:#34d399;font-family:'Cairo';" id="statSuccess">0</div>
                            <div style="font-size:.7rem;color:#64748b;font-weight:700;">
                                <i class="fa-solid fa-circle-check text-success me-1"></i>ناجح
                            </div>
                        </div>
                        <div class="stat-box" style="background:rgba(248,113,113,0.07);border:1px solid rgba(248,113,113,0.15);">
                            <div class="fw-bold" style="font-size:1.8rem;color:#f87171;font-family:'Cairo';" id="statError">0</div>
                            <div style="font-size:.7rem;color:#64748b;font-weight:700;">
                                <i class="fa-solid fa-circle-xmark text-danger me-1"></i>فاشل
                            </div>
                        </div>
                        <div class="stat-box" style="background:rgba(96,165,250,0.07);border:1px solid rgba(96,165,250,0.15);">
                            <div class="fw-bold" style="font-size:1.8rem;color:#60a5fa;font-family:'Cairo';" id="statTotal">0</div>
                            <div style="font-size:.7rem;color:#64748b;font-weight:700;">
                                <i class="fa-solid fa-table me-1" style="color:#60a5fa;"></i>إجمالي
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-1 d-flex justify-content-between align-items-center">
                        <span id="progressLabel" style="font-size:.78rem;color:#94a3b8;font-family:'Cairo';">لم تبدأ العملية</span>
                        <span id="progressPct" style="font-weight:800;color:#a78bfa;font-size:.85rem;">0%</span>
                    </div>
                    <div class="progress-track mb-4">
                        <div class="progress-fill" id="progressBar" style="width:0%;"></div>
                    </div>

                    {{-- ETA --}}
                    <div id="etaRow" class="mb-3" style="display:none;font-size:.72rem;color:#64748b;font-family:'Cairo';">
                        <i class="fa-regular fa-clock me-1"></i>
                        الوقت المتبقي المقدر: <span id="etaVal" style="color:#a78bfa;font-weight:700;">—</span>
                    </div>

                    {{-- Log Console --}}
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:.78rem;font-weight:700;color:#94a3b8;font-family:'Cairo';">
                            <i class="fa-solid fa-terminal me-1" style="color:#a78bfa;"></i>
                            سجل العمليات
                        </span>
                        <button onclick="clearLog()" style="background:none;border:none;font-size:.68rem;color:#475569;cursor:pointer;padding:0;">
                            <i class="fa-solid fa-trash-can me-1"></i>مسح
                        </button>
                    </div>
                    <div class="log-console" id="logConsole">
                        <div class="log-sys">[نظام] جاهز — اختر جدولاً وارفع ملفاً للبدء.</div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

{{-- ── Export-only section (all tables quick export) ── --}}
<div class="container-fluid px-4 mt-4 mb-5" dir="rtl" style="max-width:1600px;margin-left:auto;margin-right:auto;">
    <div class="card-panel">
        <div class="card-panel-header">
            <span class="section-title">
                <i class="fa-solid fa-file-export" style="color:#34d399;"></i>
                تصدير سريع لجميع الجداول
            </span>
            <span style="font-size:.7rem;color:#475569;">انقر على اسم الجدول لتنزيل CSV مباشرة</span>
        </div>
        <div class="p-4">
            <div class="d-flex flex-wrap gap-2" id="quickExportGrid">
                @foreach($tables as $t)
                <a href="/sig/dashboard/import/export?table={{ $t['name'] }}"
                   target="_blank"
                   class="btn-export"
                   title="{{ number_format($t['row_count']) }} صف">
                    <i class="fa-solid fa-file-arrow-down"></i>
                    {{ $t['name'] }}
                    <span style="opacity:.6;font-size:.65rem;">({{ number_format($t['row_count']) }})</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
// ── State ──────────────────────────────────────────────────────────────────
let selectedTable   = null;
let uploadedFileId  = null;
let uploadedHeaders = [];
let totalRows       = 0;
let isRunning       = false;
let startTime       = null;

const BASE = '/sig/dashboard/import';

// ── DOM refs ───────────────────────────────────────────────────────────────
const fileInput        = document.getElementById('fileInput');
const fileLabel        = document.getElementById('fileLabel');
const dropzone         = document.getElementById('dropzone');
const startBtn         = document.getElementById('startBtn');
const statusBadge      = document.getElementById('statusBadge');
const progressBar      = document.getElementById('progressBar');
const progressPct      = document.getElementById('progressPct');
const progressLabel    = document.getElementById('progressLabel');
const logConsole       = document.getElementById('logConsole');
const statSuccess      = document.getElementById('statSuccess');
const statError        = document.getElementById('statError');
const statTotal        = document.getElementById('statTotal');
const schemaPanel      = document.getElementById('schemaPanel');
const schemaRows       = document.getElementById('schemaRows');
const schemaPanelTitle = document.getElementById('schemaPanelTitle');
const schemaRowCount   = document.getElementById('schemaRowCount');
const exportBtn        = document.getElementById('exportBtn');
const selectedTableInfo= document.getElementById('selectedTableInfo');
const noTableWarning   = document.getElementById('noTableWarning');
const colMatchPreview  = document.getElementById('colMatchPreview');

// ── Table Search ───────────────────────────────────────────────────────────
document.getElementById('tableSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#tableList .table-item').forEach(el => {
        el.style.display = el.dataset.table.toLowerCase().includes(q) ? 'flex' : 'none';
    });
});

// ── Select Table ───────────────────────────────────────────────────────────
function selectTable(name) {
    selectedTable = name;

    // Update UI list
    document.querySelectorAll('#tableList .table-item').forEach(el => {
        el.classList.toggle('selected', el.dataset.table === name);
    });

    // Show info panel
    noTableWarning.style.display = 'none';
    selectedTableInfo.style.display = 'block';
    document.getElementById('selectedTableName').textContent = name;
    exportBtn.href = BASE + '/export?table=' + encodeURIComponent(name);
    schemaPanelTitle.textContent = name;

    // Load schema
    schemaRows.innerHTML = '<div style="padding:.7rem;color:#475569;font-size:.78rem;">جارٍ التحميل...</div>';
    schemaPanel.style.display = 'block';

    fetch(BASE + '/schema?table=' + encodeURIComponent(name))
        .then(r => r.json())
        .then(data => {
            if (!data.success) { schemaRows.innerHTML = '<div style="color:#f87171;padding:.7rem;">فشل تحميل الهيكل.</div>'; return; }
            schemaRowCount.textContent = data.row_count.toLocaleString('ar-DZ');

            let html = '';
            data.columns.forEach(col => {
                const keyIcon = col.key === 'PRI' ? '<span class="schema-key"><i class="fa-solid fa-key"></i></span>'
                              : col.key === 'MUL' ? '<span class="schema-key" style="color:#a78bfa;">FK</span>'
                              : '';
                html += `<div class="schema-row">
                    <span class="schema-field">${col.field}</span>
                    <span class="schema-type">${col.type}</span>
                    ${keyIcon}
                    <span style="font-size:.65rem;color:${col.null==='YES'?'#6ee7b7':'#f87171'};">${col.null==='YES'?'NULL':'NOT NULL'}</span>
                </div>`;
            });
            schemaRows.innerHTML = html;

            // If we already have file headers, show column match
            if (uploadedHeaders.length > 0) {
                showColumnMapping(data.columns.map(c => c.field), uploadedHeaders);
            }
        })
        .catch(() => { schemaRows.innerHTML = '<div style="color:#f87171;padding:.7rem;">خطأ في الشبكة.</div>'; });

    updateStartBtn();
}

// ── Column Matching Preview ─────────────────────────────────────────────────
function showColumnMapping(tableColumns, fileHeaders) {
    const lowerTableCols = tableColumns.map(c => c.toLowerCase());
    let matchedCount = 0;
    let html = '<div style="font-size:.68rem;color:#94a3b8;margin-bottom:.35rem;font-family:Cairo;">مطابقة أعمدة الملف مع الجدول:</div>';

    fileHeaders.forEach(h => {
        const matched = lowerTableCols.includes(h.toLowerCase().trim());
        if (matched) matchedCount++;
        html += `<span class="col-map-tag ${matched ? 'col-map-match' : 'col-map-miss'}">${h}</span>`;
    });
    html += `<div style="font-size:.68rem;color:#64748b;margin-top:.35rem;">${matchedCount}/${fileHeaders.length} عمود متطابق</div>`;

    colMatchPreview.innerHTML  = html;
    colMatchPreview.style.display = 'block';

    if (matchedCount === 0) {
        log('warn', `⚠ لم يُطابق أي عمود من الملف أعمدة الجدول "${selectedTable}". تحقق من الرؤوس.`);
    } else {
        log('ok', `✓ ${matchedCount} عمود متطابق مع الجدول "${selectedTable}".`);
    }
}

// ── File Input ─────────────────────────────────────────────────────────────
fileInput.addEventListener('change', handleFileSelected);

function handleFileSelected() {
    if (fileInput.files.length === 0) {
        fileLabel.textContent = 'اسحب ملف CSV أو Excel هنا';
        uploadedFileId  = null;
        uploadedHeaders = [];
        updateStartBtn();
        return;
    }
    const file = fileInput.files[0];
    fileLabel.textContent = `📄 ${file.name} (${formatBytes(file.size)})`;
    uploadedFileId  = null;
    uploadedHeaders = [];

    if (!selectedTable) {
        noTableWarning.style.display = 'block';
    }
    updateStartBtn();
}

// Drag & drop
['dragenter','dragover'].forEach(ev => {
    dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add('over'); });
});
['dragleave','drop'].forEach(ev => {
    dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove('over'); });
});
dropzone.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        fileInput.files = dt.files;
        handleFileSelected();
    }
});

// ── Start Button ───────────────────────────────────────────────────────────
function updateStartBtn() {
    startBtn.disabled = !(selectedTable && fileInput.files.length > 0 && !isRunning);
}

startBtn.addEventListener('click', startImport);

async function startImport() {
    if (!selectedTable || fileInput.files.length === 0 || isRunning) return;

    isRunning = true;
    startTime = Date.now();
    updateStartBtn();
    setStatus('uploading');
    resetStats();
    logConsole.innerHTML = '';
    log('info', `► بدء رفع الملف "${fileInput.files[0].name}" إلى الجدول "${selectedTable}"...`);

    const formData = new FormData();
    formData.append('import_file', fileInput.files[0]);

    try {
        const res  = await fetch(BASE + '/upload', { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        uploadedFileId  = data.file_id;
        uploadedHeaders = data.file_headers || [];
        totalRows       = data.total_rows;

        statTotal.textContent = totalRows.toLocaleString('ar-DZ');
        log('ok', `✓ تم رفع الملف. إجمالي الصفوف: ${totalRows.toLocaleString('ar-DZ')}`);

        // Show column mapping
        if (uploadedHeaders.length > 0 && schemaRows.querySelectorAll('.schema-row').length > 0) {
            const tableColEls = schemaRows.querySelectorAll('.schema-field');
            const tableCols   = Array.from(tableColEls).map(el => el.textContent.trim());
            showColumnMapping(tableCols, uploadedHeaders);
        }

        setStatus('processing');
        log('info', `► بدء المعالجة المجزأة...`);

        const mode  = document.querySelector('input[name="import_mode"]:checked').value;
        const chunk = parseInt(document.getElementById('chunkSize').value);

        await processAllChunks(uploadedFileId, selectedTable, mode, totalRows, chunk);

    } catch (err) {
        log('err', `✖ خطأ: ${err.message}`);
        setStatus('error');
        isRunning = false;
        updateStartBtn();
        Swal.fire({ icon: 'error', title: 'خطأ أثناء الاستيراد', text: err.message, background: '#0f172a', color: '#e2e8f0' });
    }
}

// ── Chunked Processing ─────────────────────────────────────────────────────
async function processAllChunks(fileId, table, mode, total, chunkSize) {
    let offset       = 0;
    let successCount = 0;
    let errorCount   = 0;

    while (offset < total) {
        const formData = new FormData();
        formData.append('file_id', fileId);
        formData.append('table',   table);
        formData.append('mode',    mode);
        formData.append('offset',  offset);
        formData.append('limit',   chunkSize);

        const res  = await fetch(BASE + '/process', { method: 'POST', body: formData });
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'فشل معالجة الجزء');

        successCount += data.processed_count;
        errorCount   += data.error_count;

        statSuccess.textContent = successCount.toLocaleString('ar-DZ');
        statError.textContent   = errorCount.toLocaleString('ar-DZ');

        (data.errors || []).forEach(err => log('warn', err));

        const processed = Math.min(offset + chunkSize, total);
        const pct       = total > 0 ? Math.round((processed / total) * 100) : 100;
        setProgress(pct, processed, total);
        updateEta(processed, total);

        offset += chunkSize;

        // tiny pause to let browser breathe
        await new Promise(r => setTimeout(r, 30));
    }

    // cleanup
    fetch(BASE + '/cleanup', { method: 'POST', body: (() => { const f = new FormData(); f.append('file_id', fileId); return f; })() })
        .catch(() => {});

    const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
    log('ok', `✓ اكتمل الاستيراد في ${elapsed} ثانية — ناجح: ${successCount}، فاشل: ${errorCount}`);
    setStatus('done');
    isRunning = false;
    updateStartBtn();

    Swal.fire({
        icon: errorCount === 0 ? 'success' : 'warning',
        title: 'اكتمل الاستيراد',
        html: `<div dir="rtl">
            <p>الجدول: <b style="color:#a78bfa">${table}</b></p>
            <p>✅ ناجح: <b style="color:#34d399">${successCount.toLocaleString('ar-DZ')}</b></p>
            ${errorCount > 0 ? `<p>⚠ فاشل: <b style="color:#f87171">${errorCount.toLocaleString('ar-DZ')}</b></p>` : ''}
            <p>⏱ الوقت: ${elapsed} ثانية</p>
        </div>`,
        background: '#0f172a',
        color: '#e2e8f0',
        confirmButtonColor: '#7c3aed',
    });
}

// ── UI Helpers ─────────────────────────────────────────────────────────────
function log(type, msg) {
    const classes = { sys:'log-sys', ok:'log-ok', warn:'log-warn', err:'log-err', info:'log-info' };
    const cls = classes[type] || 'log-sys';
    const ts  = new Date().toLocaleTimeString('ar-DZ');
    logConsole.innerHTML += `<div class="${cls}">[${ts}] ${msg}</div>`;
    logConsole.scrollTop  = logConsole.scrollHeight;
}

function clearLog() {
    logConsole.innerHTML = '<div class="log-sys">[نظام] تم مسح السجل.</div>';
}

function setProgress(pct, done, total) {
    progressBar.style.width    = pct + '%';
    progressPct.textContent    = pct + '%';
    progressLabel.textContent  = `تمت معالجة ${done.toLocaleString('ar-DZ')} من أصل ${total.toLocaleString('ar-DZ')} صف`;
}

function updateEta(done, total) {
    const etaRow = document.getElementById('etaRow');
    const etaVal  = document.getElementById('etaVal');
    if (done === 0 || !startTime) { etaRow.style.display = 'none'; return; }
    etaRow.style.display = 'block';
    const elapsed  = (Date.now() - startTime) / 1000;
    const rate     = done / elapsed;
    const remaining = total - done;
    const etaSecs  = rate > 0 ? remaining / rate : 0;
    etaVal.textContent = etaSecs < 60
        ? Math.round(etaSecs) + ' ثانية'
        : (etaSecs / 60).toFixed(1) + ' دقيقة';
}

function setStatus(state) {
    const map = {
        uploading:  { text: 'جارٍ الرفع...', bg: 'rgba(96,165,250,.2)',   color: '#60a5fa'  },
        processing: { text: 'جارٍ المعالجة', bg: 'rgba(167,139,250,.2)',  color: '#a78bfa'  },
        done:       { text: 'اكتملت بنجاح',  bg: 'rgba(52,211,153,.2)',   color: '#34d399'  },
        error:      { text: 'فشلت العملية',  bg: 'rgba(248,113,113,.2)',  color: '#f87171'  },
    };
    const s = map[state] || { text: 'في الانتظار', bg: 'rgba(100,116,139,.2)', color: '#94a3b8' };
    statusBadge.textContent         = s.text;
    statusBadge.style.background    = s.bg;
    statusBadge.style.color         = s.color;
}

function resetStats() {
    statSuccess.textContent = '0';
    statError.textContent   = '0';
    statTotal.textContent   = '0';
    progressBar.style.width = '0%';
    progressPct.textContent = '0%';
    progressLabel.textContent = 'لم تبدأ العملية';
    document.getElementById('etaRow').style.display = 'none';
}

function formatBytes(b) {
    if (b < 1024)       return b + ' B';
    if (b < 1048576)    return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(1) + ' MB';
}

// Init
noTableWarning.style.display = 'block';
</script>

@endsection
