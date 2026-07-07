@extends('layouts.main')
@section('title', 'مزامنة HFSQL ← MySQL — SGFEP')
@section('content')

<style>
/* ── Base ─────────────────────────────────────────────────────── */
.hfsql-hero {
    background: linear-gradient(135deg, #0d1f3c 0%, #1a3a6b 50%, #0d2b55 100%);
    border-radius: 1.25rem;
    padding: 2rem 2.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.hfsql-hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 300px; height: 300px;
    background: rgba(59,130,246,0.15);
    border-radius: 50%;
    filter: blur(60px);
    pointer-events: none;
}
.hfsql-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -60px;
    width: 200px; height: 200px;
    background: rgba(6,182,212,0.08);
    border-radius: 50%;
    filter: blur(50px);
    pointer-events: none;
}
.card-dark {
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 1rem;
    overflow: hidden;
}
.card-dark-header {
    padding: .9rem 1.4rem;
    border-bottom: 1px solid #1e293b;
    background: rgba(59,130,246,0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* ── Table List ───────────────────────────────────────────────── */
.tbl-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .38rem .75rem;
    border-radius: .45rem;
    cursor: pointer;
    transition: all .13s;
    margin: 1px 0;
    border: 1px solid transparent;
    gap: .5rem;
}
.tbl-item:hover  { background: rgba(59,130,246,.09); border-color: rgba(59,130,246,.18); }
.tbl-item.active { background: rgba(59,130,246,.2); border-color: #3b82f6; }
.tbl-item .tbl-name {
    font-family: 'Fira Mono', monospace;
    font-size: .75rem;
    color: #93c5fd;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.tbl-item.active .tbl-name { color: #dbeafe; font-weight: 700; }
.tbl-item .tbl-count {
    font-size: .65rem;
    font-family: 'Fira Mono', monospace;
    font-weight: 700;
    padding: .15rem .45rem;
    border-radius: 100px;
    white-space: nowrap;
}
.count-has  { background: rgba(52,211,153,.12); color: #34d399; }
.count-zero { background: rgba(51,65,85,.4);    color: #475569; }
.count-load { background: rgba(59,130,246,.1);  color: #60a5fa; }

/* ── Sync Log ─────────────────────────────────────────────────── */
.log-box {
    background: #060b18;
    border: 1px solid #1e293b;
    border-radius: .65rem;
    font-family: 'Fira Mono', monospace;
    font-size: .71rem;
    height: 240px;
    overflow-y: auto;
    padding: .8rem 1rem;
    scrollbar-width: thin;
    scrollbar-color: #1e3a8a transparent;
}
.log-ok   { color: #34d399; }
.log-err  { color: #f87171; }
.log-info { color: #60a5fa; }
.log-warn { color: #fbbf24; }

/* ── Progress ─────────────────────────────────────────────────── */
.progress-track {
    height: 8px;
    background: #1e293b;
    border-radius: 100px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    border-radius: 100px;
    background: linear-gradient(90deg, #1d4ed8, #3b82f6, #06b6d4);
    background-size: 200%;
    animation: shimmer 2s linear infinite;
    transition: width .4s;
}
@keyframes shimmer {
    0%   { background-position: 200% center }
    100% { background-position: -200% center }
}

/* ── Buttons ──────────────────────────────────────────────────── */
.btn-primary {
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #fff; border: none; border-radius: .6rem;
    padding: .5rem 1.1rem; font-size: .8rem; font-weight: 700;
    font-family: 'Cairo', sans-serif; cursor: pointer;
    transition: all .2s; display: inline-flex; align-items: center; gap: .4rem;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(29,78,216,.4); }
.btn-primary:disabled { opacity: .4; cursor: not-allowed; transform: none !important; }

.btn-success {
    background: rgba(5,150,105,.12); color: #34d399;
    border: 1px solid rgba(52,211,153,.25); border-radius: .5rem;
    padding: .4rem .9rem; font-size: .78rem; font-weight: 700;
    cursor: pointer; transition: all .15s;
    display: inline-flex; align-items: center; gap: .4rem;
    text-decoration: none;
}
.btn-success:hover { background: rgba(5,150,105,.22); color: #6ee7b7; text-decoration: none; }

.btn-danger {
    background: rgba(185,28,28,.12); color: #f87171;
    border: 1px solid rgba(248,113,113,.25); border-radius: .5rem;
    padding: .4rem .9rem; font-size: .78rem; font-weight: 700;
    cursor: pointer; transition: all .15s;
    display: inline-flex; align-items: center; gap: .4rem;
}
.btn-danger:hover:not(:disabled) { background: rgba(185,28,28,.22); }
.btn-danger:disabled { opacity: .4; cursor: not-allowed; }

.btn-ghost {
    background: none; border: 1px solid #1e293b; color: #64748b;
    border-radius: .5rem; padding: .4rem .9rem; font-size: .78rem;
    cursor: pointer; transition: all .15s;
    display: inline-flex; align-items: center; gap: .4rem;
}
.btn-ghost:hover { border-color: #334155; color: #94a3b8; }

/* ── Stats ────────────────────────────────────────────────────── */
.stat-card {
    flex: 1; min-width: 90px;
    text-align: center; padding: .85rem .6rem;
    border-radius: .7rem;
}

/* ── Spinners & badges ─────────────────────────────────────────── */
.spinner {
    display: inline-block; width: 12px; height: 12px;
    border: 2px solid rgba(96,165,250,.2);
    border-top-color: #60a5fa;
    border-radius: 50%;
    animation: spin .7s linear infinite; vertical-align: middle;
}
@keyframes spin { to { transform: rotate(360deg); } }

.conn-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .72rem; font-weight: 700;
    padding: .28rem .85rem; border-radius: 100px; transition: all .3s;
}
.conn-badge.connecting { background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.25); color: #fbbf24; }
.conn-badge.connected  { background: rgba(52,211,153,.1); border: 1px solid rgba(52,211,153,.25); color: #34d399; }
.conn-badge.failed     { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.25); color: #f87171; }

/* ── Search ───────────────────────────────────────────────────── */
.search-input {
    width: 100%;
    background: rgba(255,255,255,.04);
    border: 1px solid #1e293b;
    color: #e2e8f0;
    border-radius: .55rem;
    padding: .45rem .8rem .45rem 2rem;
    font-size: .78rem;
    outline: none;
    transition: border-color .15s;
}
.search-input:focus { border-color: #3b82f6; }
.search-wrap { position: relative; }
.search-wrap::before {
    content: '🔍';
    position: absolute;
    left: .55rem; top: 50%;
    transform: translateY(-50%);
    font-size: .75rem;
    pointer-events: none;
    opacity: .5;
}

/* ── Bulk Sync Banner ─────────────────────────────────────────── */
.bulk-banner {
    background: rgba(251,191,36,.05);
    border: 1px solid rgba(251,191,36,.2);
    border-radius: .75rem;
    padding: 1rem 1.25rem;
}

/* ── Quick-Export pills ────────────────────────────────────────── */
.qe-pill {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .28rem .7rem;
    background: rgba(52,211,153,.07);
    border: 1px solid rgba(52,211,153,.2);
    border-radius: 100px;
    font-family: 'Fira Mono', monospace;
    font-size: .7rem; color: #6ee7b7;
    text-decoration: none;
    transition: all .14s; margin: 2px;
}
.qe-pill:hover { background: rgba(52,211,153,.16); color: #a7f3d0; text-decoration: none; }
</style>

<div class="container-fluid py-3 px-4" dir="rtl" style="max-width:1700px;margin:0 auto;">

    {{-- ── Hero ──────────────────────────────────────────────────── --}}
    <div class="hfsql-hero mb-4">
        <div style="font-size:.67rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
                    color:#93c5fd;background:rgba(59,130,246,.18);border:1px solid rgba(59,130,246,.3);
                    border-radius:100px;display:inline-block;padding:.22rem .8rem;margin-bottom:.6rem;">
            <i class="fa-solid fa-arrows-rotate me-1"></i> HFSQL → MySQL Direct Sync
        </div>
        <h1 style="font-family:Cairo;font-size:1.55rem;font-weight:800;margin-bottom:.3rem;position:relative;z-index:1;">
            مركز مزامنة قاعدة البيانات HFSQL ← MySQL
        </h1>
        <p style="color:rgba(255,255,255,.6);font-size:.82rem;margin-bottom:1rem;position:relative;z-index:1;">
            نقل مباشر للبيانات من HFSQL إلى MySQL مع تحويل تلقائي
            <code style="color:#93c5fd;background:rgba(0,0,0,.3);padding:.1rem .4rem;border-radius:4px;">Windows-1256 → UTF-8</code>
        </p>
        <div class="d-flex flex-wrap gap-3 align-items-center" style="position:relative;z-index:1;">
            <div style="background:rgba(255,255,255,.07);border-radius:.55rem;padding:.38rem .9rem;font-size:.73rem;color:#e2e8f0;">
                <i class="fa-solid fa-server me-1" style="color:#60a5fa;"></i>
                <code style="color:#93c5fd;">197.112.101.166:4900</code>
            </div>
            <div id="connBadge" class="conn-badge connecting">
                <span class="spinner"></span>
                <span>جارٍ الاتصال...</span>
            </div>
            <div id="heroStats" style="display:none;gap:.6rem;" class="d-flex">
                <div style="background:rgba(255,255,255,.07);border-radius:.55rem;padding:.38rem .9rem;font-size:.72rem;color:#e2e8f0;">
                    <i class="fa-solid fa-table-list me-1" style="color:#a78bfa;"></i>
                    <span id="heroTableCount">0</span> جدول
                </div>
                <div style="background:rgba(255,255,255,.07);border-radius:.55rem;padding:.38rem .9rem;font-size:.72rem;color:#e2e8f0;">
                    <i class="fa-solid fa-database me-1" style="color:#34d399;"></i>
                    <span id="heroRowCount">0</span> صف HFSQL
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ── Column 1: Table List ───────────────────────────────── --}}
        <div class="col-xl-4 col-lg-5">

            {{-- Filter / Sort toolbar --}}
            <div class="d-flex gap-2 mb-2 align-items-center">
                <div class="search-wrap flex-grow-1">
                    <input type="text" id="tableSearch" class="search-input"
                           placeholder="ابحث عن جدول..." autocomplete="off"
                           oninput="filterTables(this.value)">
                </div>
                <select id="sortMode" onchange="applySortFilter()"
                        style="background:#0f172a;border:1px solid #1e293b;color:#94a3b8;
                               border-radius:.5rem;padding:.38rem .55rem;font-size:.71rem;
                               outline:none;cursor:pointer;">
                    <option value="alpha">أ-ي</option>
                    <option value="desc">الأكبر</option>
                    <option value="asc">الأصغر</option>
                    <option value="notempty">غير فارغ</option>
                </select>
            </div>

            <div class="card-dark">
                <div class="card-dark-header">
                    <span style="font-family:Cairo;font-weight:700;color:#e2e8f0;font-size:.85rem;">
                        <i class="fa-solid fa-table-list me-1" style="color:#60a5fa;"></i> جداول HFSQL
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <span id="tableCountBadge"
                              style="font-size:.67rem;background:rgba(59,130,246,.12);color:#93c5fd;
                                     padding:.18rem .55rem;border-radius:100px;font-weight:700;">
                            <span class="spinner"></span>
                        </span>
                        <button onclick="loadTables()" title="تحديث"
                                style="background:none;border:none;color:#334155;cursor:pointer;font-size:.75rem;padding:0 .2rem;">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </div>

                <div style="padding:.6rem .7rem;border-bottom:1px solid #0f172a;">
                    <div class="d-flex gap-1">
                        <button onclick="toggleAllFilter('all')" id="filterAll"
                                class="btn-ghost" style="font-size:.67rem;padding:.22rem .6rem;flex:1;
                                       background:rgba(59,130,246,.15);border-color:#3b82f6;color:#93c5fd;">
                            الكل
                        </button>
                        <button onclick="toggleAllFilter('notempty')" id="filterNonEmpty"
                                class="btn-ghost" style="font-size:.67rem;padding:.22rem .6rem;flex:1;">
                            غير فارغ
                        </button>
                        <button onclick="toggleAllFilter('large')" id="filterLarge"
                                class="btn-ghost" style="font-size:.67rem;padding:.22rem .6rem;flex:1;">
                            &gt;1000
                        </button>
                    </div>
                </div>

                <div id="tableList"
                     style="max-height:500px;overflow-y:auto;padding:.5rem .5rem;
                            scrollbar-width:thin;scrollbar-color:#1e3a8a transparent;">
                    <div style="color:#334155;font-size:.78rem;padding:.75rem;font-family:Cairo;text-align:center;">
                        <span class="spinner"></span> جارٍ تحميل الجداول...
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Column 2: Action Panel ─────────────────────────────── --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card-dark">
                <div class="card-dark-header">
                    <span style="font-family:Cairo;font-weight:700;color:#e2e8f0;font-size:.85rem;">
                        <i class="fa-solid fa-circle-nodes me-1" style="color:#34d399;"></i>
                        عمليات الجدول المختار
                    </span>
                    <code id="activeTableLabel"
                          style="font-weight:700;color:#60a5fa;font-size:.8rem;opacity:.45;">
                        — لم يُختَر جدول —
                    </code>
                </div>
                <div class="p-4">

                    {{-- Empty state --}}
                    <div id="noSelMsg" style="color:#334155;font-family:Cairo;font-size:.85rem;padding:2rem 0;text-align:center;">
                        <i class="fa-solid fa-arrow-right fa-lg me-2" style="color:#1e3a8a;"></i>
                        اختر جدولاً من القائمة اليسرى للبدء.
                    </div>

                    {{-- Action panel --}}
                    <div id="actionPanel" style="display:none;">

                        {{-- Stats row --}}
                        <div class="d-flex gap-2 mb-3 flex-wrap">
                            <div class="stat-card" style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.15);">
                                <div id="statHfsql" style="font-size:1.35rem;font-weight:800;color:#60a5fa;font-family:Cairo;">—</div>
                                <div style="font-size:.62rem;color:#475569;font-weight:700;margin-top:.1rem;">صفوف HFSQL</div>
                            </div>
                            <div class="stat-card" style="background:rgba(52,211,153,.07);border:1px solid rgba(52,211,153,.15);">
                                <div id="statMysql" style="font-size:1.35rem;font-weight:800;color:#34d399;font-family:Cairo;">—</div>
                                <div style="font-size:.62rem;color:#475569;font-weight:700;margin-top:.1rem;">صفوف MySQL</div>
                            </div>
                            <div class="stat-card" style="background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.15);">
                                <div id="statDiff" style="font-size:1.35rem;font-weight:800;color:#fbbf24;font-family:Cairo;">—</div>
                                <div style="font-size:.62rem;color:#475569;font-weight:700;margin-top:.1rem;">الفارق</div>
                            </div>
                            <div class="stat-card" style="background:rgba(167,139,250,.07);border:1px solid rgba(167,139,250,.15);">
                                <div id="statSynced" style="font-size:1.35rem;font-weight:800;color:#a78bfa;font-family:Cairo;">0</div>
                                <div style="font-size:.62rem;color:#475569;font-weight:700;margin-top:.1rem;">تمت مزامنته</div>
                            </div>
                            <div class="stat-card" style="background:rgba(248,113,113,.07);border:1px solid rgba(248,113,113,.15);">
                                <div id="statErrors" style="font-size:1.35rem;font-weight:800;color:#f87171;font-family:Cairo;">0</div>
                                <div style="font-size:.62rem;color:#475569;font-weight:700;margin-top:.1rem;">أخطاء</div>
                            </div>
                        </div>

                        {{-- Toolbar --}}
                        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                            <button class="btn-primary" id="syncBtn" onclick="startSync()" disabled>
                                <i class="fa-solid fa-arrows-rotate"></i> مزامنة إلى MySQL
                            </button>
                            <button class="btn-danger" id="stopBtn" onclick="stopSync()" disabled>
                                <i class="fa-solid fa-stop"></i> إيقاف
                            </button>
                            <a id="downloadBtn" href="#" class="btn-success" target="_blank">
                                <i class="fa-solid fa-file-csv"></i> تنزيل CSV
                            </a>
                            <div style="flex:1;"></div>
                            <select id="syncMode"
                                    style="background:#0f172a;border:1px solid #1e293b;color:#94a3b8;
                                           border-radius:.5rem;padding:.35rem .65rem;font-size:.72rem;
                                           font-family:Cairo;outline:none;">
                                <option value="upsert">Upsert (إدراج + تحديث)</option>
                                <option value="insert">Insert Ignore (تجاهل المكررات)</option>
                                <option value="replace">Replace (استبدال كامل)</option>
                            </select>
                            <select id="batchSize"
                                    style="background:#0f172a;border:1px solid #1e293b;color:#94a3b8;
                                           border-radius:.5rem;padding:.35rem .65rem;font-size:.72rem;outline:none;">
                                <option value="200">200 / دفعة</option>
                                <option value="500" selected>500 / دفعة</option>
                                <option value="1000">1000 / دفعة</option>
                                <option value="2000">2000 / دفعة</option>
                            </select>
                        </div>

                        {{-- Progress --}}
                        <div class="d-flex justify-content-between mb-1">
                            <span id="progressLabel" style="font-size:.72rem;color:#475569;font-family:Cairo;">جاهز</span>
                            <span id="progressPct" style="font-size:.73rem;font-weight:800;color:#60a5fa;">0%</span>
                        </div>
                        <div class="progress-track mb-3">
                            <div class="progress-fill" id="progressFill" style="width:0%;"></div>
                        </div>

                        {{-- Log --}}
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size:.72rem;color:#475569;font-family:Cairo;">
                                <i class="fa-solid fa-terminal me-1" style="color:#60a5fa;"></i> سجل المزامنة
                            </span>
                            <button onclick="clearLog()"
                                    style="background:none;border:none;color:#334155;font-size:.65rem;cursor:pointer;">
                                <i class="fa-solid fa-trash me-1"></i> مسح
                            </button>
                        </div>
                        <div class="log-box" id="logBox">
                            <div class="log-info">[نظام] جاهز — اختر جدولاً وانقر "مزامنة إلى MySQL".</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Bulk Sync ──────────────────────────────────────────────── --}}
    <div class="bulk-banner mt-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div style="flex:1;min-width:220px;">
                <div style="font-family:Cairo;font-weight:700;color:#fbbf24;font-size:.88rem;margin-bottom:.2rem;">
                    <i class="fa-solid fa-bolt me-1"></i> مزامنة جماعية — جميع الجداول
                </div>
                <div style="font-size:.72rem;color:#92400e;">
                    يُزامن كل الجداول تلقائياً واحداً تلو الآخر.
                    الجداول التي تفشل تُسجَّل وتُتخطى.
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="bulkMode"
                        style="background:#0f172a;border:1px solid rgba(251,191,36,.25);color:#94a3b8;
                               border-radius:.5rem;padding:.38rem .65rem;font-size:.72rem;
                               font-family:Cairo;outline:none;">
                    <option value="upsert">Upsert</option>
                    <option value="insert">Insert Ignore</option>
                    <option value="replace">Replace</option>
                </select>
                <select id="bulkBatch"
                        style="background:#0f172a;border:1px solid rgba(251,191,36,.25);color:#94a3b8;
                               border-radius:.5rem;padding:.38rem .65rem;font-size:.72rem;outline:none;">
                    <option value="500" selected>500 / دفعة</option>
                    <option value="1000">1000 / دفعة</option>
                </select>
                <button id="bulkSyncBtn" onclick="startBulkSync()"
                        class="btn-primary" style="background:linear-gradient(135deg,#92400e,#b45309);" disabled>
                    <i class="fa-solid fa-layer-group"></i> ابدأ المزامنة الجماعية
                </button>
                <button id="bulkStopBtn" onclick="stopBulkSync()" disabled
                        class="btn-danger">
                    <i class="fa-solid fa-stop"></i> إيقاف
                </button>
            </div>
        </div>
        <div id="bulkProgress" style="display:none;margin-top:.85rem;">
            <div class="d-flex justify-content-between mb-1">
                <span id="bulkLabel" style="font-size:.72rem;color:#92400e;font-family:Cairo;"></span>
                <span id="bulkPct"   style="font-size:.73rem;font-weight:800;color:#fbbf24;"></span>
            </div>
            <div class="progress-track">
                <div id="bulkFill" style="height:100%;border-radius:100px;
                     background:linear-gradient(90deg,#92400e,#f59e0b,#fcd34d);
                     background-size:200%;animation:shimmer 2s linear infinite;transition:width .4s;width:0%;"></div>
            </div>
            <div class="log-box mt-2" id="bulkLog" style="height:150px;">
                <div class="log-info">[مجمّع] جاهز.</div>
            </div>
        </div>
    </div>

    {{-- ── Quick CSV Download ─────────────────────────────────────── --}}
    <div class="card-dark mt-3 mb-5">
        <div class="card-dark-header">
            <span style="font-family:Cairo;font-weight:700;color:#e2e8f0;font-size:.85rem;">
                <i class="fa-solid fa-file-export me-1" style="color:#34d399;"></i>
                تنزيل CSV مباشر — جميع الجداول
            </span>
            <span style="font-size:.69rem;color:#334155;font-family:Cairo;">
                ترميز UTF-8 مع BOM (متوافق مع Excel)
            </span>
        </div>
        <div class="p-4">
            <div id="quickExportGrid" style="display:flex;flex-wrap:wrap;gap:4px;">
                <div style="color:#334155;font-size:.78rem;font-family:Cairo;">
                    <span class="spinner"></span> جارٍ التحميل...
                </div>
            </div>
        </div>
    </div>

</div>

<script>
/* ═══════════════════════════════════════════════════════════════
   HFSQL Export — JS Controller
   ════════════════════════════════════════════════════════════════ */
const BASE = '{{ str_starts_with(url()->current(), url("/sig")) ? "/sig" : "" }}/dashboard/hfsql-export';

let tableList     = [];   // {name, count} objects
let filteredList  = [];   // currently displayed subset
let selectedTable = null;
let hfsqlTotal    = 0;
let isRunning     = false;
let stopRequested = false;
let bulkRunning   = false;
let bulkStop      = false;
let activeFilter  = 'all';

/* ── Init ──────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => loadTables());

async function loadTables() {
    setConnStatus('connecting', 'جارٍ الاتصال...');
    document.getElementById('tableList').innerHTML =
        '<div style="color:#334155;font-size:.78rem;padding:.75rem;font-family:Cairo;text-align:center;">' +
        '<span class="spinner"></span> جارٍ تحميل قائمة الجداول من HFSQL...</div>';

    try {
        const r = await fetch(BASE + '/tables');
        const d = await r.json();

        if (!d.success) {
            setConnStatus('failed', 'فشل الاتصال');
            document.getElementById('tableList').innerHTML =
                `<div class="log-err" style="padding:.75rem;font-family:Cairo;font-size:.78rem;">✖ ${d.message}</div>`;
            return;
        }

        // Build table objects with placeholder counts
        tableList = (d.tables || []).map(t => ({ name: t, count: null }));
        setConnStatus('connected', 'متصل بـ HFSQL ✓');
        document.getElementById('tableCountBadge').textContent = tableList.length + ' جدول';
        document.getElementById('heroTableCount').textContent  = tableList.length;
        document.getElementById('heroStats').style.display = 'flex';
        document.getElementById('bulkSyncBtn').disabled = false;

        filteredList = [...tableList];
        renderTableList();
        renderQuickExport(tableList.map(t => t.name));

        // Load counts lazily in background (batches of 5)
        loadCountsLazy();

    } catch (e) {
        setConnStatus('failed', 'خطأ شبكة');
        document.getElementById('tableList').innerHTML =
            `<div class="log-err" style="padding:.75rem;font-family:Cairo;font-size:.78rem;">✖ ${e.message}</div>`;
    }
}

/* ── Lazy count loading ─────────────────────────────────────────── */
async function loadCountsLazy() {
    let totalRows = 0;
    const chunkSize = 20; // 20 tables at a time
    for (let i = 0; i < tableList.length; i += chunkSize) {
        const chunk = tableList.slice(i, i + chunkSize).map(t => t.name);
        try {
            const r = await fetch(BASE + '/bulk-counts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ tables: chunk })
            });
            const d = await r.json();
            if (d.success && d.counts) {
                for (const [tableName, count] of Object.entries(d.counts)) {
                    const tObj = tableList.find(t => t.name === tableName);
                    if (tObj) {
                        tObj.count = count;
                        if (count > 0) {
                            totalRows += count;
                        }
                        updateTablePillCount(tableName, count);
                    }
                }
                // Update hero row count
                document.getElementById('heroRowCount').textContent =
                    totalRows.toLocaleString('ar-DZ');
            }
        } catch (e) {
            console.error('Error fetching bulk counts:', e);
        }
    }
}

function updateTablePillCount(name, count) {
    const el = document.querySelector(`.tbl-item[data-table="${name}"] .tbl-count`);
    if (!el) return;
    el.textContent = count > 0 ? count.toLocaleString('ar-DZ') : '0';
    el.className = 'tbl-count ' + (count > 0 ? 'count-has' : 'count-zero');
}

/* ── Render table list ──────────────────────────────────────────── */
function renderTableList() {
    const container = document.getElementById('tableList');
    if (filteredList.length === 0) {
        container.innerHTML = '<div style="color:#334155;font-size:.78rem;font-family:Cairo;padding:.75rem;text-align:center;">لا توجد نتائج.</div>';
        return;
    }
    container.innerHTML = filteredList.map(t =>
        `<div class="tbl-item${selectedTable === t.name ? ' active' : ''}"
              data-table="${t.name}" onclick="selectTable('${t.name}')">
            <i class="fa-solid fa-table" style="font-size:.6rem;opacity:.4;flex-shrink:0;"></i>
            <span class="tbl-name">${t.name}</span>
            <span class="tbl-count ${t.count === null ? 'count-load' : (t.count > 0 ? 'count-has' : 'count-zero')}">
                ${t.count === null ? '<span class="spinner" style="width:8px;height:8px;"></span>' : t.count.toLocaleString('ar-DZ')}
            </span>
         </div>`
    ).join('');
}

function renderQuickExport(names) {
    document.getElementById('quickExportGrid').innerHTML = names.map(t =>
        `<a href="${BASE}/download?table=${encodeURIComponent(t)}" class="qe-pill" target="_blank" title="${t}">
            <i class="fa-solid fa-file-arrow-down" style="font-size:.6rem;"></i>${t}
         </a>`
    ).join('');
}

/* ── Filter / Sort ──────────────────────────────────────────────── */
function filterTables(q) {
    q = (q || '').toLowerCase().trim();
    let list = [...tableList];
    if (q) list = list.filter(t => t.name.toLowerCase().includes(q));
    list = applyActiveFilter(list);
    filteredList = list;
    renderTableList();
}

function applySortFilter() {
    const q = document.getElementById('tableSearch').value;
    filterTables(q);
}

function toggleAllFilter(mode) {
    activeFilter = mode;
    ['filterAll','filterNonEmpty','filterLarge'].forEach(id => {
        const el = document.getElementById(id);
        el.style.background = 'none';
        el.style.borderColor = '#1e293b';
        el.style.color = '#64748b';
    });
    const activeEl = mode === 'all' ? 'filterAll' : (mode === 'notempty' ? 'filterNonEmpty' : 'filterLarge');
    const el = document.getElementById(activeEl);
    el.style.background = 'rgba(59,130,246,.15)';
    el.style.borderColor = '#3b82f6';
    el.style.color = '#93c5fd';
    applySortFilter();
}

function applyActiveFilter(list) {
    const sort = document.getElementById('sortMode').value;
    if (activeFilter === 'notempty') list = list.filter(t => t.count === null || t.count > 0);
    if (activeFilter === 'large')    list = list.filter(t => t.count === null || t.count > 1000);
    if (sort === 'alpha') list.sort((a,b) => a.name.localeCompare(b.name));
    if (sort === 'desc')  list.sort((a,b) => (b.count ?? -1) - (a.count ?? -1));
    if (sort === 'asc')   list.sort((a,b) => (a.count ?? 999999) - (b.count ?? 999999));
    if (sort === 'notempty') { list = list.filter(t => t.count === null || t.count > 0); list.sort((a,b) => (b.count ?? -1) - (a.count ?? -1)); }
    return list;
}

/* ── Select table ───────────────────────────────────────────────── */
function selectTable(name) {
    selectedTable = name;
    hfsqlTotal    = 0;

    document.querySelectorAll('.tbl-item').forEach(el =>
        el.classList.toggle('active', el.dataset.table === name));

    document.getElementById('activeTableLabel').textContent = name;
    document.getElementById('activeTableLabel').style.opacity = '1';
    document.getElementById('noSelMsg').style.display    = 'none';
    document.getElementById('actionPanel').style.display = 'block';
    document.getElementById('downloadBtn').href = BASE + '/download?table=' + encodeURIComponent(name);
    document.getElementById('syncBtn').disabled = false;

    // Reset stats
    ['statHfsql','statMysql','statDiff','statSynced','statErrors'].forEach(id =>
        document.getElementById(id).textContent = '—');
    document.getElementById('statSynced').textContent = '0';
    document.getElementById('statErrors').textContent = '0';
    setProgress(0, 0, 0);

    // Fetch HFSQL count
    const tObj = tableList.find(t => t.name === name);
    if (tObj && tObj.count !== null) {
        hfsqlTotal = tObj.count;
        document.getElementById('statHfsql').textContent = tObj.count.toLocaleString('ar-DZ');
        updateDiff();
    } else {
        document.getElementById('statHfsql').innerHTML = '<span class="spinner"></span>';
        fetch(BASE + '/count?table=' + encodeURIComponent(name))
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    hfsqlTotal = d.total;
                    document.getElementById('statHfsql').textContent = d.total.toLocaleString('ar-DZ');
                    updateDiff();
                } else {
                    document.getElementById('statHfsql').textContent = '!';
                }
            }).catch(() => document.getElementById('statHfsql').textContent = '!');
    }

    // Fetch MySQL count
    document.getElementById('statMysql').innerHTML = '<span class="spinner"></span>';
    fetch('/{{ str_starts_with(url()->current(), url("/sig")) ? "sig/" : "" }}dashboard/import/schema?table=' + encodeURIComponent(name))
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                document.getElementById('statMysql').textContent = (d.row_count ?? 0).toLocaleString('ar-DZ');
                updateDiff();
            } else {
                document.getElementById('statMysql').textContent = '—';
            }
        }).catch(() => document.getElementById('statMysql').textContent = '—');
}

function updateDiff() {
    const hfsql = parseInt((document.getElementById('statHfsql').textContent || '').replace(/\D/g,'')) || 0;
    const mysql = parseInt((document.getElementById('statMysql').textContent || '').replace(/\D/g,'')) || 0;
    if (hfsql > 0) {
        const diff = hfsql - mysql;
        document.getElementById('statDiff').textContent = (diff >= 0 ? '+' : '') + diff.toLocaleString('ar-DZ');
        document.getElementById('statDiff').style.color = diff > 0 ? '#fbbf24' : '#34d399';
    }
}

/* ── Single table Sync ──────────────────────────────────────────── */
async function startSync() {
    if (!selectedTable || isRunning) return;
    isRunning     = true;
    stopRequested = false;
    document.getElementById('syncBtn').disabled  = true;
    document.getElementById('stopBtn').disabled  = false;
    document.getElementById('logBox').innerHTML  = '';
    document.getElementById('statSynced').textContent = '0';
    document.getElementById('statErrors').textContent = '0';

    const mode  = document.getElementById('syncMode').value;
    const batch = parseInt(document.getElementById('batchSize').value);
    let total   = hfsqlTotal;

    log('info', `► بدء مزامنة "${selectedTable}" → MySQL (وضع: ${mode}, دفعة: ${batch})...`);

    if (!total) {
        try {
            const cr = await fetch(BASE + '/count?table=' + encodeURIComponent(selectedTable));
            const cd = await cr.json();
            total = cd.total || 0;
            hfsqlTotal = total;
            document.getElementById('statHfsql').textContent = total.toLocaleString('ar-DZ');
        } catch(e) {
            log('err', '✖ فشل جلب العدد: ' + e.message);
            finishSync();
            return;
        }
    }

    log('info', `  إجمالي الصفوف في HFSQL: ${total.toLocaleString('ar-DZ')}`);

    let offset = 0, synced = 0, errors = 0;
    const t0 = Date.now();

    while (!stopRequested && (offset === 0 || offset < total)) {
        const fd = new FormData();
        fd.append('table',  selectedTable);
        fd.append('mode',   mode);
        fd.append('offset', offset);
        fd.append('limit',  batch);
        fd.append('total',  total);
        fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        try {
            const r = await fetch(BASE + '/sync-to-mysql', { method: 'POST', body: fd });
            const d = await r.json();

            if (!d.success) {
                log('err', `  ✖ خطأ (offset=${offset}): ${d.message}`);
                errors++;
                break;
            }

            synced += d.processed_count;
            document.getElementById('statSynced').textContent = synced.toLocaleString('ar-DZ');
            document.getElementById('statErrors').textContent = errors;

            const pct = total > 0 ? Math.round(Math.min(offset + d.processed_count, total) / total * 100) : 100;
            setProgress(pct, offset + d.processed_count, total);
            log('ok', `  ✓ offset=${offset}: ${d.processed_count} صف  [${pct}%]`);

            if (d.processed_count < batch) break;
            offset += batch;

        } catch(e) {
            log('err', `  ✖ استثناء (offset=${offset}): ${e.message}`);
            errors++;
            break;
        }
        await sleep(10);
    }

    if (stopRequested) log('warn', '  ⊘ أُوقفت المزامنة من قِبَل المستخدم.');
    const elapsed = ((Date.now() - t0)/1000).toFixed(1);
    log(errors === 0 ? 'ok' : 'warn',
        `══ اكتملت في ${elapsed}ث — مزامَن: ${synced.toLocaleString('ar-DZ')}، أخطاء: ${errors} ══`);

    setProgress(100, synced, total);

    // Refresh MySQL count
    fetch('/{{ str_starts_with(url()->current(), url("/sig")) ? "sig/" : "" }}dashboard/import/schema?table=' + encodeURIComponent(selectedTable))
        .then(r => r.json())
        .then(d => { if (d.success) { document.getElementById('statMysql').textContent = (d.row_count ?? 0).toLocaleString('ar-DZ'); updateDiff(); }}).catch(()=>{});

    finishSync();
}

function stopSync() {
    stopRequested = true;
    document.getElementById('stopBtn').disabled = true;
}
function finishSync() {
    isRunning = false;
    document.getElementById('syncBtn').disabled = false;
    document.getElementById('stopBtn').disabled = true;
}

/* ── Bulk Sync ──────────────────────────────────────────────────── */
async function startBulkSync() {
    if (bulkRunning || tableList.length === 0) return;
    bulkRunning  = true;
    bulkStop     = false;

    document.getElementById('bulkProgress').style.display = 'block';
    document.getElementById('bulkSyncBtn').disabled = true;
    document.getElementById('bulkStopBtn').disabled = false;
    document.getElementById('bulkLog').innerHTML = '';

    const mode  = document.getElementById('bulkMode').value;
    const batch = parseInt(document.getElementById('bulkBatch').value);
    const tables = tableList.map(t => t.name);

    bulkLog('info', `► بدء المزامنة الجماعية لـ ${tables.length} جدول (وضع: ${mode}, دفعة: ${batch})`);

    let done = 0, totalSynced = 0, totalErrors = 0;

    for (const tbl of tables) {
        if (bulkStop) { bulkLog('warn', '⊘ أُوقفت المزامنة الجماعية.'); break; }

        done++;
        const pct = Math.round(done / tables.length * 100);
        document.getElementById('bulkFill').style.width  = pct + '%';
        document.getElementById('bulkPct').textContent   = pct + '%';
        document.getElementById('bulkLabel').textContent = `(${done}/${tables.length}) ${tbl}`;
        bulkLog('info', `  → [${done}/${tables.length}] ${tbl}...`);

        // Get total
        let total = 0;
        try {
            const cr = await fetch(BASE + '/count?table=' + encodeURIComponent(tbl));
            const cd = await cr.json();
            total = cd.total || 0;
        } catch(e) { bulkLog('warn', `    ⚠ تعذّر جلب عدد ${tbl}: ${e.message}`); }

        if (total === 0) { bulkLog('warn', `    — ${tbl}: فارغ، تخطّي.`); continue; }

        let offset = 0, synced = 0;
        let failed = false;

        while (!bulkStop && (offset === 0 || offset < total)) {
            const fd = new FormData();
            fd.append('table', tbl); fd.append('mode', mode);
            fd.append('offset', offset); fd.append('limit', batch);
            fd.append('total', total);
            fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

            try {
                const r = await fetch(BASE + '/sync-to-mysql', { method: 'POST', body: fd });
                const d = await r.json();
                if (!d.success) { bulkLog('err', `    ✖ ${tbl} offset=${offset}: ${d.message}`); totalErrors++; failed = true; break; }
                synced += d.processed_count;
                if (d.processed_count < batch) break;
                offset += batch;
            } catch(e) {
                bulkLog('err', `    ✖ استثناء في ${tbl}: ${e.message}`);
                totalErrors++; failed = true; break;
            }
            await sleep(5);
        }

        if (!failed) {
            bulkLog('ok', `    ✓ ${tbl}: ${synced.toLocaleString('ar-DZ')} صف مزامَن`);
            totalSynced += synced;
        }
    }

    bulkLog(totalErrors === 0 ? 'ok' : 'warn',
        `══ الجماعية اكتملت — مزامَن: ${totalSynced.toLocaleString('ar-DZ')}، أخطاء: ${totalErrors} ══`);

    bulkRunning = false;
    document.getElementById('bulkSyncBtn').disabled = false;
    document.getElementById('bulkStopBtn').disabled = true;
}

function stopBulkSync() {
    bulkStop = true;
    document.getElementById('bulkStopBtn').disabled = true;
}

/* ── Helpers ────────────────────────────────────────────────────── */
function setConnStatus(state, text) {
    const el = document.getElementById('connBadge');
    el.className = 'conn-badge ' + state;
    const icon = state === 'connecting' ? '<span class="spinner"></span>'
               : state === 'connected'  ? '<i class="fa-solid fa-circle-check"></i>'
               :                          '<i class="fa-solid fa-circle-xmark"></i>';
    el.innerHTML = icon + ' <span>' + text + '</span>';
}

function log(type, msg) {
    const box = document.getElementById('logBox');
    const ts  = new Date().toLocaleTimeString('ar-DZ');
    box.innerHTML += `<div class="log-${type}">[${ts}] ${msg}</div>`;
    box.scrollTop  = box.scrollHeight;
}
function bulkLog(type, msg) {
    const box = document.getElementById('bulkLog');
    const ts  = new Date().toLocaleTimeString('ar-DZ');
    box.innerHTML += `<div class="log-${type}">[${ts}] ${msg}</div>`;
    box.scrollTop  = box.scrollHeight;
}
function clearLog() {
    document.getElementById('logBox').innerHTML = '<div class="log-info">[نظام] تم مسح السجل.</div>';
}
function setProgress(pct, done, total) {
    document.getElementById('progressFill').style.width = pct + '%';
    document.getElementById('progressPct').textContent  = pct + '%';
    document.getElementById('progressLabel').textContent =
        total > 0 ? `${done.toLocaleString('ar-DZ')} من ${total.toLocaleString('ar-DZ')} صف` : `${done} صف`;
}
function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }
</script>

@endsection
