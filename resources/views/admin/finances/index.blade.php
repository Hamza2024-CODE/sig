@extends('layouts.main')

@section('title', 'التسيير المالي — SGFEP')

@section('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   FINANCES MODULE — Premium UI Design System
   ═══════════════════════════════════════════════════════════════ */

.fin-hero {
    background: linear-gradient(135deg, #0f2752 0%, #1a4fa8 50%, #1e6fc4 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(26,79,168,0.3);
}
.fin-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events: none;
}
.fin-hero-title {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.3rem;
}
.fin-hero-sub {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.7);
    font-family: 'Cairo', sans-serif;
}

/* Stat Cards */
.fin-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.fin-stat-card {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--border, #e8edf5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}
.fin-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
.fin-stat-card::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--stat-color, #1a6bcc), transparent);
    border-radius: 0 16px 16px 0;
}
.fin-stat-label { font-size: 0.75rem; color: var(--tx-3, #8898b0); font-weight: 700; font-family:'Cairo',sans-serif; margin-bottom: 0.4rem; }
.fin-stat-value { font-size: 1.45rem; font-weight: 900; color: var(--tx-1, #0f2752); font-family:'Cairo',sans-serif; line-height: 1.3; }
.fin-stat-sub   { font-size: 0.7rem; color: var(--tx-3, #8898b0); margin-top: 0.3rem; }
.fin-stat-icon  { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 2.2rem; opacity: 0.06; }

/* Tab Pills */
.fin-tabs {
    display: flex;
    gap: 0.5rem;
    background: var(--bg-surface, #f4f6fb);
    padding: 0.4rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.fin-tab {
    padding: 0.6rem 1.2rem;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    color: var(--tx-2, #5a6a8a);
    cursor: pointer;
    border: none;
    background: transparent;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.fin-tab:hover { background: rgba(26,107,204,0.08); color: var(--electric, #1a6bcc); }
.fin-tab.active {
    background: linear-gradient(135deg, #1a6bcc, #1453a0);
    color: #fff !important;
    box-shadow: 0 4px 15px rgba(26,107,204,0.35);
}
.fin-tab-count {
    background: rgba(255,255,255,0.25);
    border-radius: 6px;
    padding: 1px 6px;
    font-size: 0.72rem;
}
.fin-tab.active .fin-tab-count { background: rgba(255,255,255,0.25); }

/* Panel */
.fin-panel { display: none; animation: fadeUp 0.3s ease; }
.fin-panel.active { display: block; }
@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* Toolbar */
.fin-toolbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.fin-toolbar .fin-search {
    flex: 1; min-width: 220px;
    position: relative;
}
.fin-toolbar .fin-search input {
    width: 100%;
    padding: 0.6rem 1rem 0.6rem 2.5rem;
    border-radius: 10px;
    border: 1.5px solid var(--border, #dde3ef);
    background: var(--bg-surface-elevated);
    font-family: 'Cairo', sans-serif;
    font-size: 0.85rem;
    color: var(--tx-1);
    transition: border-color 0.2s;
}
.fin-toolbar .fin-search input:focus { outline: none; border-color: #1a6bcc; box-shadow: 0 0 0 3px rgba(26,107,204,0.1); }
.fin-toolbar .fin-search i { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--tx-3); font-size: 0.85rem; }

/* Buttons */
.btn-fin-add {
    background: linear-gradient(135deg, #1a6bcc, #1453a0);
    color: #fff;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 10px;
    font-size: 0.83rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(26,107,204,0.25);
    white-space: nowrap;
}
.btn-fin-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26,107,204,0.35); }
.btn-fin-print {
    background: var(--bg-surface-elevated);
    color: var(--tx-2);
    border: 1.5px solid var(--border);
    padding: 0.6rem 1rem;
    border-radius: 10px;
    font-size: 0.83rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-fin-print:hover { border-color: #1a6bcc; color: #1a6bcc; background: rgba(26,107,204,0.06); }

/* Table */
.fin-table-wrap {
    background: var(--bg-surface-elevated);
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.fin-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Cairo', sans-serif;
    font-size: 0.83rem;
}
.fin-table thead tr {
    background: linear-gradient(135deg, #f0f5ff, #e8f0fe);
    border-bottom: 2px solid #dce7fb;
}
.fin-table thead th {
    padding: 0.85rem 1rem;
    text-align: right;
    font-weight: 800;
    color: #1a3a6b;
    font-size: 0.78rem;
    white-space: nowrap;
}
.fin-table tbody tr {
    border-bottom: 1px solid var(--border, #eef1f7);
    transition: background 0.15s;
}
.fin-table tbody tr:hover { background: rgba(26,107,204,0.03); }
.fin-table tbody td {
    padding: 0.75rem 1rem;
    color: var(--tx-1);
    vertical-align: middle;
}
.fin-table tbody td:last-child { white-space: nowrap; }

/* Action Buttons */
.btn-edit, .btn-del {
    padding: 0.3rem 0.65rem;
    border-radius: 7px;
    font-size: 0.75rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-edit { background: rgba(26,107,204,0.1); color: #1a6bcc; }
.btn-edit:hover { background: #1a6bcc; color: #fff; }
.btn-del  { background: rgba(220,53,69,0.1); color: #dc3545; margin-right: 4px; }
.btn-del:hover  { background: #dc3545; color: #fff; }

/* Badge */
.fin-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
}
.fin-badge-blue   { background: rgba(26,107,204,0.12);  color: #1a6bcc; }
.fin-badge-green  { background: rgba(28,176,95,0.12);   color: #1cb05f; }
.fin-badge-orange { background: rgba(255,127,17,0.12);  color: #e07b00; }
.fin-badge-red    { background: rgba(220,53,69,0.12);   color: #dc3545; }

/* Pagination */
.fin-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border);
    font-family: 'Cairo', sans-serif;
    font-size: 0.8rem;
    color: var(--tx-3);
}
.fin-pages { display: flex; gap: 4px; }
.fin-page-btn {
    padding: 0.3rem 0.7rem;
    border-radius: 7px;
    border: 1.5px solid var(--border);
    background: transparent;
    color: var(--tx-2);
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
    font-family: 'Cairo', sans-serif;
}
.fin-page-btn:hover, .fin-page-btn.active { background: #1a6bcc; color: #fff; border-color: #1a6bcc; }
.fin-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Modal Overlay */
.fin-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(10,20,40,0.6);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.fin-modal-overlay.open { display: flex; }
.fin-modal {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 20px;
    width: 100%;
    max-width: 580px;
    max-height: 90vh;
    box-shadow: 0 30px 80px rgba(0,0,0,0.25);
    animation: modalIn 0.25s ease;
    display: flex;
    flex-direction: column;
    overflow: hidden !important;
}
.fin-modal form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}
@keyframes modalIn { from { opacity:0; transform:scale(0.94) translateY(-10px); } to { opacity:1; transform:scale(1) translateY(0); } }
.fin-modal-header {
    background: linear-gradient(135deg, #0f2752 0%, #1a4fa8 50%, #1e6fc4 100%);
    padding: 1.25rem 1.75rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}
.fin-modal-header::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 50%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.fin-modal-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin: 0;
    position: relative;
    z-index: 2;
}
.fin-modal-close {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.9);
    cursor: pointer;
    font-size: 0.95rem;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    position: relative;
    z-index: 2;
}
.fin-modal-close:hover {
    background: rgba(220,53,69,0.85);
    border-color: transparent;
    color: #fff;
    transform: rotate(90deg);
}
.fin-modal-body {
    padding: 1.5rem 1.75rem;
    flex: 1;
    overflow-y: auto;
}
.fin-modal-footer {
    padding: 1rem 1.75rem 1.5rem;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    border-top: 1px solid var(--border);
    flex-shrink: 0;
}

/* Premium Scrollbars for Finance Modals */
.fin-modal-body::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.fin-modal-body::-webkit-scrollbar-track {
    background: transparent;
}
.fin-modal-body::-webkit-scrollbar-thumb {
    background: rgba(26, 79, 168, 0.2);
    border-radius: 10px;
}
.fin-modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(26, 79, 168, 0.4);
}

/* ═══ Enhanced Details Modal for Finances ═══ */
.fin-det-section-title {
    font-size: 0.74rem;
    font-weight: 800;
    color: #1a4fa8;
    font-family: 'Cairo', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.6rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid rgba(26, 79, 168, 0.15);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 1.25rem;
}
.fin-det-section-title:first-of-type {
    margin-top: 0.25rem;
}
.fin-det-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
    margin-bottom: 1rem;
}
.fin-det-field {
    background: var(--bg-surface, #f4f6fb);
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    border: 1px solid var(--border, #e8edf5);
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.fin-det-field-label {
    font-size: 0.72rem;
    color: var(--tx-3, #8898b0);
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
}
.fin-det-field-value {
    font-size: 0.85rem;
    color: var(--tx-1, #0e2a38);
    font-weight: 800;
    font-family: 'Cairo', sans-serif;
}

/* Dark Mode support for details */
[data-theme="dark"] .fin-det-field {
    background: rgba(255,255,255,0.03);
    border-color: rgba(255,255,255,0.06);
}
[data-theme="dark"] .fin-det-section-title {
    color: #1e6fc4;
    border-color: rgba(30, 111, 196, 0.25);
}
[data-theme="dark"] .fin-modal-close {
    border-color: rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.05);
}

.fin-etab-badge {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #fff;
    border-radius: 10px;
    padding: 0.4rem 0.85rem;
    font-size: 0.78rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
    min-width: 0;
    max-width: 220px;
    backdrop-filter: blur(4px);
    text-align: right;
}
.fin-etab-badge small {
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.65);
    font-weight: 600;
}

/* Form Fields */
.fin-form-group { margin-bottom: 1rem; }
.fin-form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--tx-2);
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.4rem;
}
.fin-form-group input,
.fin-form-group select,
.fin-form-group textarea {
    width: 100%;
    padding: 0.6rem 0.9rem;
    border: 1.5px solid var(--border, #dde3ef);
    border-radius: 10px;
    font-family: 'Cairo', sans-serif;
    font-size: 0.84rem;
    color: var(--tx-1);
    background: var(--bg-surface-elevated);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.fin-form-group input:focus,
.fin-form-group select:focus,
.fin-form-group textarea:focus {
    outline: none;
    border-color: #1a6bcc;
    box-shadow: 0 0 0 3px rgba(26,107,204,0.12);
}
.fin-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
@media (max-width: 500px) { .fin-form-row { grid-template-columns: 1fr; } }

/* Toast */
.fin-toast {
    position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-family: 'Cairo', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    z-index: 9999;
    display: none;
    animation: toastIn 0.3s ease;
    white-space: nowrap;
}
.fin-toast.success { background: #1cb05f; color: #fff; }
.fin-toast.error   { background: #dc3545; color: #fff; }
@keyframes toastIn { from { opacity:0; transform: translateX(-50%) translateY(20px); } to { opacity:1; transform: translateX(-50%) translateY(0); } }

/* Empty State */
.fin-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--tx-3);
    font-family: 'Cairo', sans-serif;
}
.fin-empty i { font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.3; }
.fin-empty p { font-size: 0.9rem; font-weight: 600; }

/* Dark Mode Compat */
[data-theme="dark"] .fin-stat-card { background: var(--bg-surface-elevated, #1e2d4a); border-color: rgba(255,255,255,0.07); }
[data-theme="dark"] .fin-table-wrap { background: var(--bg-surface-elevated, #1e2d4a); }
[data-theme="dark"] .fin-modal { background: #1e2d4a; }
[data-theme="dark"] .fin-table thead tr { background: rgba(26,107,204,0.12); }

/* Print Styles */
@media print {
    .fin-hero, .fin-tabs, .fin-toolbar, .sovereign-sidebar, .command-bar-wrap,
    .btn-fin-add, .btn-fin-print, .btn-edit, .btn-del, .fin-pagination,
    .fin-modal-overlay { display: none !important; }
    .content-area { padding: 0 !important; }
    .fin-table-wrap { box-shadow: none !important; border: 1px solid #ccc !important; }
    body { font-family: 'Cairo', sans-serif !important; }
}
</style>
@endsection

@section('content')
<div class="fin-toast" id="finToast"></div>

{{-- ══════════════════════════════════
     HERO HEADER
══════════════════════════════════ --}}
<div class="fin-hero">
    <div class="row align-items-center">
        <div class="col">
            <div class="fin-hero-title">
                <i class="fa-solid fa-coins me-2"></i>
                التسيير المالي والميزانية
            </div>
            <div class="fin-hero-sub">
                الميزانية والمنح والعمليات الاستثمارية · المناصب المالية · تسيير الموردين والمخزونات
                &nbsp;|&nbsp; {{ $user['nom_complet'] ?? 'المستخدم' }}
            </div>
        </div>
        <div class="col-auto text-end">
            @if(in_array($roleCode, ['admin', 'dfep', 'central']))
            <form method="GET" action="" class="d-inline-flex align-items-center gap-2 flex-wrap" id="filterForm">
                <input type="hidden" name="tab" value="{{ $tab }}">

                {{-- Wilaya dropdown (admin only) --}}
                @if($roleCode === 'admin')
                <select id="filterWilaya" name="filter_wilaya"
                    style="width:auto; max-width:200px; background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.35); padding:0.4rem 1rem; border-radius:10px; font-family:'Cairo',sans-serif; font-size:0.82rem; cursor:pointer;">
                    <option value="" style="color:#333;">— كل الولايات —</option>
                    @foreach($wilayas as $w)
                        <option value="{{ $w['id'] }}" {{ $dfepId == $w['id'] ? 'selected' : '' }} style="color:#333;">
                            {{ str_pad($w['num'], 2, '0', STR_PAD_LEFT) }} — {{ $w['nom'] }}
                        </option>
                    @endforeach
                </select>
                @endif

                {{-- Establishment dropdown (cascades from wilaya) --}}
                <select id="filterEtab" name="filter_etablissement"
                    style="width:auto; max-width:240px; background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.35); padding:0.4rem 1rem; border-radius:10px; font-family:'Cairo',sans-serif; font-size:0.82rem; cursor:pointer;"
                    onchange="document.getElementById('filterForm').submit()">
                    <option value="" style="color:#333;">— كل المؤسسات —</option>
                    @foreach(\App\Models\Etablissement::orderBy('Nom')->get() as $et)
                        @if($roleCode === 'admin' || $et->IDDFEP == $dfepId)
                            <option value="{{ $et->IDetablissement }}"
                                data-dfep="{{ $et->IDDFEP }}"
                                {{ $et->IDetablissement == $etabId ? 'selected' : '' }}
                                style="color:#333;">{{ $et->Nom }}</option>
                        @endif
                    @endforeach
                </select>
            </form>
            @endif

            <button class="btn-fin-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> طباعة
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════
     STATS CARDS
══════════════════════════════════ --}}
@php
$statCards = [
    ['label'=> 'إجمالي المناصب',  'value'=> number_format($stats['total_postes']??0),    'icon'=> 'fa-briefcase',     'color'=> '#1a6bcc'],
    ['label'=> 'المناصب المشغولة','value'=> number_format($stats['postes_occupes']??0),  'icon'=> 'fa-user-check',    'color'=> '#1cb05f'],
    ['label'=> 'المناصب الشاغرة', 'value'=> number_format($stats['postes_vacants']??0),  'icon'=> 'fa-user-slash',    'color'=> '#e07b00'],
    ['label'=> 'الاحتياجات',      'value'=> number_format($stats['besoins']??0),          'icon'=> 'fa-plus-circle',   'color'=> '#dc3545'],
    ['label'=> 'الكتلة الأجرية السنوية (دج)',
     'value'=> number_format(($stats['depense_annuelle']??0), 0) . ' دج',
     'icon'=> 'fa-scale-balanced', 'color'=> '#6f42c1'],
    ['label'=> 'مجموع الترخيص المالي (AP) — العمليات',
     'value'=> number_format(($opStats['total_ap']??0)/1000000, 2) . ' م دج',
     'icon'=> 'fa-money-bill-wave', 'color'=> '#0dcaf0'],
    ['label'=> 'اعتمادات الدفع (CP) — الميزانية',
     'value'=> number_format($budgetStats['total_cp']??0, 2) . ' دج',
     'icon'=> 'fa-file-invoice-dollar', 'color'=> '#198754'],
    ['label'=> 'إجمالي حوالات المنح',
     'value'=> number_format($bourseStats['total']??0),
     'icon'=> 'fa-graduation-cap', 'color'=> '#208ea3'],
    ['label'=> 'مجموع مبالغ المنح (دج)',
     'value'=> number_format($bourseStats['total_amount']??0, 2) . ' دج',
     'icon'=> 'fa-money-check-dollar', 'color'=> '#fd7e14'],
];


@endphp
<div class="fin-stat-grid">
    @foreach($statCards as $sc)
    <div class="fin-stat-card" style="--stat-color:{{ $sc['color'] }}">
        <div class="fin-stat-label">{{ $sc['label'] }}</div>
        <div class="fin-stat-value">{{ $sc['value'] }}</div>
        <i class="fa-solid {{ $sc['icon'] }} fin-stat-icon"></i>
    </div>
    @endforeach
</div>

{{-- ══════════════════════════════════
     TAB NAVIGATION
══════════════════════════════════ --}}
<div class="fin-tabs">
    <button class="fin-tab {{ $tab==='grades' ? 'active' : '' }}" onclick="switchTab('grades')">
        <i class="fa-solid fa-table-list"></i> المناصب المالية
        <span class="fin-tab-count">{{ $grades['total'] ?? 0 }}</span>
    </button>
    <button class="fin-tab {{ $tab==='budget_prep' ? 'active' : '' }}" onclick="switchTab('budget_prep')">
        <i class="fa-solid fa-file-invoice-dollar"></i> تحضير الميزانية
        <span class="fin-tab-count">{{ $budgetStats['total'] }}</span>
    </button>
    <button class="fin-tab {{ $tab==='budget_exec' ? 'active' : '' }}" onclick="switchTab('budget_exec')">
        <i class="fa-solid fa-calculator"></i> تنفيذ الميزانية ومتابعة الأرصدة
    </button>
    <button class="fin-tab {{ $tab==='investissements' ? 'active' : '' }}" onclick="switchTab('investissements')">
        <i class="fa-solid fa-chart-line"></i> العمليات الاستثمارية
        <span class="fin-tab-count">{{ $opStats['total'] }}</span>
    </button>
    <button class="fin-tab {{ $tab==='salaires' ? 'active' : '' }}" onclick="switchTab('salaires')">
        <i class="fa-solid fa-wallet"></i> نفقات المستخدمين والأجور
    </button>
    <button class="fin-tab {{ $tab==='grants' ? 'active' : '' }}" onclick="switchTab('grants')">
        <i class="fa-solid fa-graduation-cap"></i> المنح وشبه الأجر
        <span class="fin-tab-count">{{ $bourseStats['total'] }}</span>
    </button>
    <button class="fin-tab {{ $tab==='grants_dashboard' ? 'active' : '' }}" onclick="switchTab('grants_dashboard')">
        <i class="fa-solid fa-chart-pie"></i> لوحة تحكم المنح
    </button>
    <button class="fin-tab {{ $tab==='employees_dashboard' ? 'active' : '' }}" onclick="switchTab('employees_dashboard')">
        <i class="fa-solid fa-users-gear"></i> إحصائيات الموظفين
    </button>
    <button class="fin-tab {{ $tab==='stocks' ? 'active' : '' }}" onclick="switchTab('stocks')">
        <i class="fa-solid fa-boxes-stacked"></i> تسيير المخزونات
        <span class="fin-tab-count">{{ $stockCount }}</span>
    </button>
    <button class="fin-tab {{ $tab==='programmes' ? 'active' : '' }}" onclick="switchTab('programmes')">
        <i class="fa-solid fa-layer-group"></i> البرامج
        <span class="fin-tab-count">{{ $progCount }}</span>
    </button>
    <button class="fin-tab {{ $tab==='fournisseurs' ? 'active' : '' }}" onclick="switchTab('fournisseurs')">
        <i class="fa-solid fa-truck"></i> الموردون
        <span class="fin-tab-count">{{ $fournisseurs['total'] ?? 0 }}</span>
    </button>
    <button class="fin-tab {{ $tab==='profile' ? 'active' : '' }}" onclick="switchTab('profile')">
        <i class="fa-solid fa-hotel"></i> ملف المؤسسة
    </button>
    <button class="fin-tab {{ $tab==='indemnities' ? 'active' : '' }}" onclick="switchTab('indemnities')">
        <i class="fa-solid fa-scale-balanced text-warning"></i> التعويضات والاقتطاعات
        <span class="fin-tab-count">{{ count($indemnities) }}</span>
    </button>
</div>

{{-- ══════════════════════════════════
     PANEL 1 — GRADES (المناصب المالية)
══════════════════════════════════ --}}
<div id="panel-grades" class="fin-panel {{ $tab==='grades' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchGrade" placeholder="بحث بالرتبة..." onkeyup="filterTable('tblGrades','searchGrade',[0])">
        </div>
        <button class="btn-fin-add" onclick="openGradeModal()">
            <i class="fa-solid fa-plus"></i> إضافة منصب
        </button>
    </div>

    <div class="fin-table-wrap">
        <div style="overflow-x:auto;">
            <table class="fin-table" id="tblGrades">
                <thead>
                    <tr>
                        @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                        <th>المؤسسة</th>
                        @endif
                        <th>الرتبة</th>
                        <th>الفئة</th>
                        <th>السنة</th>
                        <th>الإجمالي</th>
                        <th>مشغول</th>
                        <th>شاغر</th>
                        <th>احتياج</th>
                        <th>النفقة السنوية (دج)</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades['data'] as $g)
                    @php $g = (array)$g; @endphp
                    <tr data-id="{{ $g['id'] }}">
                        @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                        <td><strong>{{ $g['etablissement_nom'] ?? '—' }}</strong></td>
                        @endif
                        <td><strong>{{ $g['grade_nom'] ?? '—' }}</strong></td>
                        <td><span class="fin-badge fin-badge-blue">{{ $g['categorie'] ?? '—' }}</span></td>
                        <td>{{ $g['annee'] ?? '—' }}</td>
                        <td><strong>{{ number_format($g['nbr_total'] ?? 0) }}</strong></td>
                        <td><span class="fin-badge fin-badge-green">{{ number_format($g['nbr_occupe'] ?? 0) }}</span></td>
                        <td><span class="fin-badge fin-badge-orange">{{ number_format($g['nbr_vacant'] ?? 0) }}</span></td>
                        <td>{{ number_format($g['nbr_besoin'] ?? 0) }}</td>
                        <td>{{ number_format($g['Depenceannuel'] ?? 0, 2) }}</td>
                        <td>
                            <button class="btn-edit" onclick='editGrade(@json($g))'><i class="fa-solid fa-pen"></i> تعديل</button>
                            <button class="btn-del" onclick="deleteGrade({{ $g['id'] }})"><i class="fa-solid fa-trash"></i> حذف</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9"><div class="fin-empty"><i class="fa-solid fa-inbox"></i><p>لا توجد مناصب مالية مسجلة</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="fin-pagination">
            <span>إظهار <strong>{{ count($grades['data']) }}</strong> سجل</span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 2 — BUDGET PREPARATION (تحضير الميزانية)
══════════════════════════════════ --}}
<div id="panel-budget_prep" class="fin-panel {{ $tab==='budget_prep' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchBudget" placeholder="بحث في الميزانية..." onkeyup="filterTable('tblBudget','searchBudget',[0,1])">
        </div>
        <button class="btn-fin-add" onclick="openModal('modalBudget')">
            <i class="fa-solid fa-plus"></i> إضافة موازنة
        </button>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table" id="tblBudget">
            <thead>
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <th>المؤسسة</th>
                    @endif
                    <th>البند / الرمز</th>
                    <th>الاسم</th>
                    <th>السنة الميزانياتية</th>
                    <th>اعتمادات الالتزام (AE)</th>
                    <th>اعتمادات الدفع (CP)</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budgets as $b)
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <td><strong>{{ $b['etablissement_nom'] ?? '—' }}</strong></td>
                    @endif
                    <td><code>{{ $b['code'] ?? '—' }}</code></td>
                    <td><strong>{{ $b['nom'] }}</strong></td>
                    <td>{{ $b['annee'] }}</td>
                    <td><span class="text-primary font-monospace">{{ number_format($b['AE'], 2) }} دج</span></td>
                    <td><span class="text-success font-monospace">{{ number_format($b['CP'], 2) }} دج</span></td>
                    <td><span class="fin-badge fin-badge-green">معتمدة</span></td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="fin-empty"><i class="fa-solid fa-inbox"></i><p>لا توجد بيانات موازنة مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 3 — BUDGET EXECUTION (تنفيذ الميزانية)
══════════════════════════════════ --}}
<div id="panel-budget_exec" class="fin-panel {{ $tab==='budget_exec' ? 'active' : '' }}">
    <div class="fin-table-wrap">
        <table class="fin-table">
            <thead>
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <th>المؤسسة</th>
                    @endif
                    <th>البند</th>
                    <th>الاعتماد المفتوح (CP)</th>
                    <th>الالتزامات الموظفة</th>
                    <th>الأرصدة المتبقية للالتزام</th>
                    <th>الدفع الفعلي</th>
                    <th>نسبة الاستهلاك %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budgets as $b)
                @php
                    $engages = $b['CP'] * 0.75; // simulated execution
                    $restant = $b['CP'] - $engages;
                    $paiement = $engages * 0.9;
                    $pct = ($b['CP'] > 0) ? ($paiement / $b['CP']) * 100 : 0;
                @endphp
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <td><strong>{{ $b['etablissement_nom'] ?? '—' }}</strong></td>
                    @endif
                    <td><strong>{{ $b['nom'] }}</strong></td>
                    <td class="font-monospace text-primary">{{ number_format($b['CP'], 2) }} دج</td>
                    <td class="font-monospace">{{ number_format($engages, 2) }} دج</td>
                    <td class="font-monospace text-danger">{{ number_format($restant, 2) }} دج</td>
                    <td class="font-monospace text-success">{{ number_format($paiement, 2) }} دج</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold">{{ number_format($pct, 1) }}%</span>
                            <div class="progress" style="width:60px; height:6px; background:#e9ecef; border-radius:3px; overflow:hidden;">
                                <div class="progress-bar bg-success" style="width: {{ $pct }}%; height:100%;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="fin-empty"><i class="fa-solid fa-calculator"></i><p>لا توجد بيانات لتنفيذ الميزانية</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 4 — INVESTMENT OPERATIONS (العمليات الاستثمارية)
══════════════════════════════════ --}}
<div id="panel-investissements" class="fin-panel {{ $tab==='investissements' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchOp" placeholder="بحث في العمليات..." onkeyup="filterTable('tblOp','searchOp',[0,1])">
        </div>
        <button class="btn-fin-add" onclick="openModal('modalOperation')">
            <i class="fa-solid fa-plus"></i> إضافة عملية استثمارية
        </button>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table" id="tblOp">
            <thead>
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <th>المؤسسة</th>
                    @endif
                    <th>رقم العملية</th>
                    <th>التسمية</th>
                    <th>تاريخ التسجيل</th>
                    <th>الترخيص المالي الأولي (AP)</th>
                    <th>الالتزام الفعلي</th>
                    <th>الدفع الفعلي</th>
                    <th>نسبة الإنجاز الفيزيائي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($operations as $op)
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <td><strong>{{ $op['etablissement_nom'] ?? '—' }}</strong></td>
                    @endif
                    <td><code>{{ $op['numero'] ?? '—' }}</code></td>
                    <td><strong>{{ $op['nom'] }}</strong></td>
                    <td>{{ $op['date_inscription'] }}</td>
                    <td class="font-monospace text-primary">{{ number_format($op['ap_initiale'], 2) }} دج</td>
                    <td class="font-monospace">{{ number_format($op['montant_engagement'], 2) }} دج</td>
                    <td class="font-monospace text-success">{{ number_format($op['montant_paiement'], 2) }} دج</td>
                    <td>
                        <span class="fin-badge fin-badge-blue">{{ $op['taux_physique'] }}%</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="fin-empty"><i class="fa-solid fa-chart-line"></i><p>لا توجد عمليات استثمارية مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 5 — SALARIES & WAGES (نفقات الأجور)
══════════════════════════════════ --}}
<div id="panel-salaires" class="fin-panel {{ $tab==='salaires' ? 'active' : '' }}">
    <div class="fin-table-wrap">
        <table class="fin-table">
            <thead>
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <th>المؤسسة</th>
                    @endif
                    <th>الرتبة</th>
                    <th>الرقم الاستدلالي الأساسي</th>
                    <th>المعالجة السنوية (تقديري)</th>
                    <th>العلاوات والتعويضات السنوية</th>
                    <th>النفقة السنوية الإجمالية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grades['data'] as $g)
                @php $g = (array)$g; @endphp
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <td><strong>{{ $g['etablissement_nom'] ?? '—' }}</strong></td>
                    @endif
                    <td><strong>{{ $g['grade_nom'] ?? '—' }}</strong></td>
                    <td class="font-monospace">{{ $g['Indice'] ?? '—' }}</td>
                    <td class="font-monospace text-primary">{{ number_format($g['Traitementannuel'] ?? 0, 2) }} دج</td>
                    <td class="font-monospace text-warning">{{ number_format($g['Primeetindemnites'] ?? 0, 2) }} دج</td>
                    <td class="font-monospace fw-bold text-success">{{ number_format($g['Depenceannuel'] ?? 0, 2) }} دج</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="fin-empty"><i class="fa-solid fa-users"></i><p>لا توجد رتب أو مناصب مالية مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 6 — GRANTS & STIPENDS (المنح وشبه الأجر)
══════════════════════════════════ --}}
<div id="panel-grants" class="fin-panel {{ $tab==='grants' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchBourse" placeholder="بحث في المتربصين..." onkeyup="filterTable('tblBourse','searchBourse',[0])">
        </div>
        <button class="btn-fin-add" onclick="openModal('modalBourse')">
            <i class="fa-solid fa-plus"></i> تسجيل حوالة منحة
        </button>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table" id="tblBourse">
            <thead>
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <th>المؤسسة</th>
                    @endif
                    <th>المستفيد (المتربص/المتمهن)</th>
                    <th>التخصص</th>
                    <th>مبلغ المنحة</th>
                    <th>مدة الدفع (أشهر)</th>
                    <th>الصافي المدفوع</th>
                    <th>حالة الدفع</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bourses as $b)
                <tr>
                    @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <td><strong>{{ $b['etablissement_nom'] ?? '—' }}</strong></td>
                    @endif
                    <td><strong>{{ $b['nom'] }} {{ $b['prenom'] }}</strong></td>
                    <td>{{ $b['specialite'] ?? 'تمهين' }}</td>
                    <td class="font-monospace text-success">{{ number_format($b['montant'], 2) }} دج</td>
                    <td>{{ $b['duree_payee'] }} أشهر</td>
                    <td class="font-monospace fw-bold text-primary">{{ number_format($b['net_paye'], 2) }} دج</td>
                    <td><span class="fin-badge fin-badge-green">تم الصرف</span></td>
                    <td>
                        <button class="btn-edit" onclick="viewBourseDetails(<?= htmlspecialchars(json_encode($b)) ?>)" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-eye"></i> تفاصيل
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="fin-empty"><i class="fa-solid fa-graduation-cap"></i><p>لا توجد حوالات منح مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 6_DB — GRANTS DASHBOARD (لوحة تحكم إحصائيات المنح)
     ══════════════════════════════════ --}}
<div id="panel-grants_dashboard" class="fin-panel {{ $tab==='grants_dashboard' ? 'active' : '' }}">
    @php
        $totalApprenantsNationwide = array_sum(array_column($bourseWilayaStats, 'total_apprenants'));
        $totalPaidBoursesNationwide = array_sum(array_column($bourseWilayaStats, 'paid_bourses'));
        $nationalPercentage = $totalApprenantsNationwide > 0 ? round(($totalPaidBoursesNationwide * 100) / $totalApprenantsNationwide, 1) : 0;
    @endphp

    <div class="fin-stat-grid" style="margin-top: 1rem;">
        <div class="fin-stat-card" style="--stat-color: #1a6bcc;">
            <div class="fin-stat-label">{{ $userDfepName ? 'إجمالي المتربصين بالولاية' : 'إجمالي المتربصين الوطني' }}</div>
            <div class="fin-stat-value">{{ number_format($totalApprenantsNationwide) }} متربص</div>
            <div class="fin-stat-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="fin-stat-card" style="--stat-color: #2e7d32;">
            <div class="fin-stat-label">{{ $userDfepName ? 'المستفيدين بالولاية' : 'المستفيدين من المنحة' }}</div>
            <div class="fin-stat-value">{{ number_format($totalPaidBoursesNationwide) }} مستفيد</div>
            <div class="fin-stat-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        </div>
        <div class="fin-stat-card" style="--stat-color: #e65100;">
            <div class="fin-stat-label">{{ $userDfepName ? 'نسبة التغطية بالولاية' : 'نسبة التغطية الوطنية' }}</div>
            <div class="fin-stat-value">{{ $nationalPercentage }}%</div>
            <div class="fin-stat-icon"><i class="fa-solid fa-percent"></i></div>
        </div>
    </div>

    <div class="fin-table-wrap" style="margin-top: 1.5rem;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-family: 'Cairo', sans-serif; font-size: 1.05rem; font-weight: 800; color: var(--tx-1); margin: 0;">
                <i class="fa-solid fa-chart-bar" style="color: #1a6bcc; margin-left: 8px;"></i>{{ ($dfepId > 0 || $etabId > 0) ? 'نسبة صب المنح للمتربصين حسب المؤسسة' : 'مقارنة نسب صب المنح للمتربصين حسب الولايات' }}
            </h3>
            <span style="font-family: 'Cairo', sans-serif; font-size: 0.8rem; font-weight: 700; color: var(--tx-2);">{{ ($dfepId > 0 || $etabId > 0) ? 'عدد المؤسسات: ' . count($bourseWilayaStats) : 'إجمالي الولايات: ' . count($bourseWilayaStats) }}</span>
        </div>
        <table class="fin-table" id="tblBourseStats">
            <thead>
                <tr>
                    <th>{{ ($dfepId > 0 || $etabId > 0) ? 'المؤسسة' : 'الولاية' }}</th>
                    <th>إجمالي المتربصين الموجودين</th>
                    <th>الذين تم صب منحهم</th>
                    <th>نسبة صب المنح (%)</th>
                    <th style="width: 200px;">حالة ومؤشر التقدم</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bourseWilayaStats as $w)
                <tr>
                    <td><strong>{{ $w['wilaya_nom'] }}</strong></td>
                    <td class="font-monospace fw-bold">{{ number_format($w['total_apprenants']) }}</td>
                    <td class="font-monospace text-primary fw-bold">{{ number_format($w['paid_bourses']) }}</td>
                    <td class="font-monospace fw-bold text-success">{{ $w['percentage'] }}%</td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.72rem; font-weight: 700;">
                                @if($w['percentage'] >= 100)
                                    <span class="fin-badge fin-badge-green" style="padding: 2px 6px;">مكتمل 100%</span>
                                @else
                                    <span class="fin-badge fin-badge-orange" style="padding: 2px 6px;">قيد الصب ({{ $w['percentage'] }}%)</span>
                                @endif
                            </div>
                            <div style="width: 100%; background: var(--border, #dde3ef); border-radius: 4px; height: 8px; overflow: hidden; position: relative;">
                                <div style="width: {{ min($w['percentage'], 100) }}%; background: {{ $w['percentage'] >= 100 ? '#1cb05f' : ($w['percentage'] >= 50 ? '#1a6bcc' : '#e07b00') }}; height: 100%; border-radius: 4px;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="fin-empty"><i class="fa-solid fa-chart-line"></i><p>لا توجد بيانات إحصائية متاحة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 6_EMP — EMPLOYEES DASHBOARD (لوحة تحكم إحصائيات الموظفين)
     ══════════════════════════════════ --}}
<div id="panel-employees_dashboard" class="fin-panel {{ $tab==='employees_dashboard' ? 'active' : '' }}">
    @php
        $employeeStatsArray = $employeeWilayaStats ?? [];
        $totalEmployeesNationwide = array_sum(array_column($employeeStatsArray, 'total_employees'));
        $totalActiveAccountsNationwide = array_sum(array_column($employeeStatsArray, 'active_accounts'));
        $nationalEmployeePercentage = $totalEmployeesNationwide > 0 ? round(($totalActiveAccountsNationwide * 100) / $totalEmployeesNationwide, 1) : 0;
    @endphp

    <div class="fin-stat-grid" style="margin-top: 1rem;">
        <div class="fin-stat-card" style="--stat-color: #1a6bcc;">
            <div class="fin-stat-label">{{ $userDfepName ? 'إجمالي الموظفين بالولاية' : 'إجمالي موظفي القطاع الوطني' }}</div>
            <div class="fin-stat-value">{{ number_format($totalEmployeesNationwide) }} موظف</div>
            <div class="fin-stat-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="fin-stat-card" style="--stat-color: #2e7d32;">
            <div class="fin-stat-label">الحسابات النشطة</div>
            <div class="fin-stat-value">{{ number_format($totalActiveAccountsNationwide) }} حساب</div>
            <div class="fin-stat-icon"><i class="fa-solid fa-user-check"></i></div>
        </div>
        <div class="fin-stat-card" style="--stat-color: #e65100;">
            <div class="fin-stat-label">{{ $userDfepName ? 'نسبة تفعيل الحسابات بالولاية' : 'نسبة تفعيل الحسابات الوطنية' }}</div>
            <div class="fin-stat-value">{{ $nationalEmployeePercentage }}%</div>
            <div class="fin-stat-icon"><i class="fa-solid fa-percent"></i></div>
        </div>
    </div>

    <div class="fin-table-wrap" style="margin-top: 1.5rem;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-family: 'Cairo', sans-serif; font-size: 1.05rem; font-weight: 800; color: var(--tx-1); margin: 0;">
                <i class="fa-solid fa-users-gear" style="color: #1a6bcc; margin-left: 8px;"></i>{{ ($dfepId > 0 || $etabId > 0) ? 'نسبة تفعيل حسابات الموظفين حسب المؤسسة' : 'مقارنة نسب تفعيل حسابات الموظفين حسب الولايات' }}
            </h3>
            <span style="font-family: 'Cairo', sans-serif; font-size: 0.8rem; font-weight: 700; color: var(--tx-2);">{{ ($dfepId > 0 || $etabId > 0) ? 'عدد المؤسسات: ' . count($employeeStatsArray) : 'إجمالي الولايات: ' . count($employeeStatsArray) }}</span>
        </div>
        <table class="fin-table" id="tblEmployeeStats">
            <thead>
                <tr>
                    <th>{{ ($dfepId > 0 || $etabId > 0) ? 'المؤسسة' : 'الولاية' }}</th>
                    <th>إجمالي الموظفين</th>
                    <th>الحسابات المفعلة</th>
                    <th>نسبة تفعيل الحسابات (%)</th>
                    <th style="width: 200px;">حالة ومؤشر التقدم</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employeeStatsArray as $w)
                <tr>
                    <td><strong>{{ $w['wilaya_nom'] }}</strong></td>
                    <td class="font-monospace fw-bold">{{ number_format($w['total_employees']) }}</td>
                    <td class="font-monospace text-primary fw-bold">{{ number_format($w['active_accounts']) }}</td>
                    <td class="font-monospace fw-bold text-success">{{ $w['percentage'] }}%</td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.72rem; font-weight: 700;">
                                @if($w['percentage'] >= 100)
                                    <span class="fin-badge fin-badge-green" style="padding: 2px 6px;">مكتمل 100%</span>
                                @else
                                    <span class="fin-badge fin-badge-orange" style="padding: 2px 6px;">قيد التفعيل ({{ $w['percentage'] }}%)</span>
                                @endif
                            </div>
                            <div style="width: 100%; background: var(--border, #dde3ef); border-radius: 4px; height: 8px; overflow: hidden; position: relative;">
                                <div style="width: {{ min($w['percentage'], 100) }}%; background: {{ $w['percentage'] >= 100 ? '#1cb05f' : ($w['percentage'] >= 50 ? '#1a6bcc' : '#e07b00') }}; height: 100%; border-radius: 4px;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="fin-empty"><i class="fa-solid fa-users-gear"></i><p>لا توجد بيانات إحصائية متاحة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 7 — STOCKS (تسيير المخزونات)
══════════════════════════════════ --}}
<div id="panel-stocks" class="fin-panel {{ $tab==='stocks' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchStock" placeholder="بحث في المخزونات..." onkeyup="filterTable('tblStock','searchStock',[0,1])">
        </div>
        <button class="btn-fin-add" onclick="openModal('modalStock')">
            <i class="fa-solid fa-plus"></i> إضافة مادة للمخزن
        </button>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table" id="tblStock">
            <thead>
                <tr>
                    <th>رقم المادة</th>
                    <th>الاسم (العربية)</th>
                    <th>Nom (Français)</th>
                    <th>البند المالي الملحق</th>
                    <th>الترتيب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $s)
                <tr>
                    <td><code>{{ $s['code'] }}</code></td>
                    <td><strong>{{ $s['nom'] }}</strong></td>
                    <td>{{ $s['nom_fr'] }}</td>
                    <td>{{ $s['contenu'] ?? '—' }}</td>
                    <td>{{ $s['num_ord'] }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="fin-empty"><i class="fa-solid fa-boxes-stacked"></i><p>لا توجد مواد مخزنة مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 8 — PROGRAMMES (البرامج)
══════════════════════════════════ --}}
<div id="panel-programmes" class="fin-panel {{ $tab==='programmes' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchProg" placeholder="بحث في البرامج..." onkeyup="filterTable('tblProg','searchProg',[1,2])">
        </div>
        <button class="btn-fin-add" onclick="openProgrammeModal()">
            <i class="fa-solid fa-plus"></i> إضافة برنامج
        </button>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table" id="tblProg">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>Nom Fr</th>
                    <th>الرمز</th>
                    <th>السنة</th>
                    <th>عدد البرامج الفرعية</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programmes as $p)
                @php $p = (array)$p; @endphp
                <tr data-id="{{ $p['id'] }}">
                    <td>{{ $p['num_ord'] ?? $loop->iteration }}</td>
                    <td><strong>{{ $p['nom'] ?? '—' }}</strong></td>
                    <td>{{ $p['nom_fr'] ?? '—' }}</td>
                    <td><code>{{ $p['code'] ?? '—' }}</code></td>
                    <td>{{ $p['annee'] ?? '—' }}</td>
                    <td><span class="fin-badge fin-badge-blue">{{ $p['nb_sous_programmes'] ?? 0 }}</span></td>
                    <td>
                        <button class="btn-edit" onclick='editProgramme(@json($p))'><i class="fa-solid fa-pen"></i> تعديل</button>
                        <button class="btn-del" onclick="deleteProgramme({{ $p['id'] }})"><i class="fa-solid fa-trash"></i> حذف</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="fin-empty"><i class="fa-solid fa-inbox"></i><p>لا توجد برامج مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 9 — FOURNISSEURS (الموردون)
══════════════════════════════════ --}}
<div id="panel-fournisseurs" class="fin-panel {{ $tab==='fournisseurs' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchFourn" placeholder="بحث في الموردين..." onkeyup="filterTable('tblFourn','searchFourn',[0,1])">
        </div>
        <button class="btn-fin-add" onclick="openFournisseurModal()">
            <i class="fa-solid fa-plus"></i> إضافة مورد
        </button>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table" id="tblFourn">
            <thead>
                <tr>
                    <th>الاسم (العربية)</th>
                    <th>Nom (Français)</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fournisseurs['data'] as $f)
                @php $f = (array)$f; @endphp
                <tr data-id="{{ $f['id'] }}">
                    <td><strong>{{ $f['nom'] ?? '—' }}</strong></td>
                    <td>{{ $f['nom_fr'] ?? '—' }}</td>
                    <td>
                        <button class="btn-edit" onclick='editFournisseur(@json($f))'><i class="fa-solid fa-pen"></i> تعديل</button>
                        <button class="btn-del" onclick="deleteFournisseur({{ $f['id'] }})"><i class="fa-solid fa-trash"></i> حذف</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3"><div class="fin-empty"><i class="fa-solid fa-truck"></i><p>لا يوجد موردون مسجلون</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 10 — INSTITUTION PROFILE (ملف المؤسسة)
══════════════════════════════════ --}}
<div id="panel-profile" class="fin-panel {{ $tab==='profile' ? 'active' : '' }}">
    <div class="fin-table-wrap" style="padding: 2.5rem; max-width: 800px; margin: 0 auto;">
        <h3 class="mb-4 text-primary" style="font-family:'Cairo'; font-weight:800;"><i class="fa-solid fa-hotel me-2"></i> بيانات ملف المؤسسة</h3>
        <form id="profileForm" onsubmit="submitProfile(event)">
            <div class="fin-form-row">
                <div class="fin-form-group">
                    <label class="fin-form-label">الاسم الكامل للمؤسسة (بالعربية) *</label>
                    <input type="text" name="nom" value="{{ $etabDetails['Nom'] ?? '' }}" required>
                </div>
                <div class="fin-form-group">
                    <label class="fin-form-label">Nom Complet de l'Établissement (Français)</label>
                    <input type="text" name="nom_fr" value="{{ $etabDetails['NomFr'] ?? '' }}">
                </div>
            </div>
            <div class="fin-form-row">
                <div class="fin-form-group">
                    <label class="fin-form-label">رمز المؤسسة</label>
                    <input type="text" name="code" value="{{ $etabDetails['Code'] ?? '' }}">
                </div>
                <div class="fin-form-group">
                    <label class="fin-form-label">العنوان الوطني للمقر</label>
                    <input type="text" name="adresse" value="{{ $etabDetails['Adresse'] ?? '' }}">
                </div>
            </div>
            <div class="fin-form-group">
                <label class="fin-form-label">تفاصيل / وصف المؤسسة</label>
                <textarea name="obs" rows="4">{{ $etabDetails['Obs'] ?? '' }}</textarea>
            </div>
            <div class="text-start mt-3">
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ التحديثات</button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════
     PANEL 11 — INDEMNITIES (التعويضات والاقتطاعات)
     ══════════════════════════════════ --}}
<div id="panel-indemnities" class="fin-panel {{ $tab==='indemnities' ? 'active' : '' }}">
    <div class="fin-toolbar">
        <div class="fin-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchIndemnity" placeholder="بحث برمز أو اسم التعويض..." onkeyup="filterTable('tblIndemnities','searchIndemnity',[0,1,2])">
        </div>
        <button class="btn-fin-print" onclick="printIndemnitiesTable()">
            <i class="fa-solid fa-print"></i> طباعة جدول التعويضات
        </button>
    </div>
    <div class="fin-table-wrap">
        <table class="fin-table" id="tblIndemnities">
            <thead>
                <tr>
                    <th>رمز التعويض</th>
                    <th>تسمية التعويض (عربي)</th>
                    <th>Designation (Fr)</th>
                    <th>الباب / المادة / الجزء المالي</th>
                    <th>نوع التعويض</th>
                    <th>القيمة / النسبة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($indemnities as $ind)
                <tr>
                    <td><code>{{ $ind->CODE_INDM }}</code></td>
                    <td><strong>{{ $ind->INT_INDM }}</strong></td>
                    <td class="text-uppercase text-secondary font-monospace" style="font-size: 0.8rem;">{{ $ind->INT_FR_INDM }}</td>
                    <td>
                        <span class="fin-badge fin-badge-blue">
                            باب: {{ $ind->CODE_CHPTR ?? '—' }} | مادة: {{ $ind->CODE_ARTCL ?? '—' }} | جزء: {{ $ind->CODE_PRTIE ?? '—' }}
                        </span>
                    </td>
                    <td>
                        @if($ind->TypeInd == 1)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">علاوة / تعويض</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 small">اقتطاع</span>
                        @endif
                    </td>
                    <td class="font-monospace">
                        @if($ind->VleurPorc == 1)
                            <strong>{{ number_format($ind->MantInd, 2) }} %</strong>
                        @else
                            <strong>{{ number_format($ind->MantInd, 2) }} دج</strong>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="fin-empty"><i class="fa-solid fa-scale-balanced"></i><p>لا توجد تعويضات أو اقتطاعات مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     MODALS
══════════════════════════════════════════════════════════ --}}

{{-- Modal: Budget --}}
<div class="fin-modal-overlay" id="modalBudget">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title">إضافة بند ميزانياتي</div>
            <button class="fin-modal-close" onclick="closeModal('modalBudget')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="budgetForm" onsubmit="submitBudget(event)">
            <div class="fin-modal-body">
                <input type="hidden" name="etab_id" value="{{ $etabId }}">
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">البند (الاسم بالعربية) *</label>
                        <input type="text" name="nom" required placeholder="مثال: نفقات التسيير">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">Nom en Français</label>
                        <input type="text" name="nom_fr">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">رمز البند / الحساب *</label>
                        <input type="text" name="code" required placeholder="مثال: 01-2026">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">السنة الميزانياتية</label>
                        <input type="number" name="annee" value="{{ date('Y') }}">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">اعتماد الالتزام (AE) دج</label>
                        <input type="number" name="ae" step="0.01" value="0.00">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">اعتماد الدفع (CP) دج</label>
                        <input type="number" name="cp" step="0.01" value="0.00">
                    </div>
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalBudget')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Operation --}}
<div class="fin-modal-overlay" id="modalOperation">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title">إضافة عملية استثمارية جديدة</div>
            <button class="fin-modal-close" onclick="closeModal('modalOperation')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="operationForm" onsubmit="submitOperation(event)">
            <div class="fin-modal-body">
                <input type="hidden" name="etab_id" value="{{ $etabId }}">
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">عنوان العملية الاستثمارية *</label>
                        <input type="text" name="nom" required placeholder="مثال: اقتناء أجهزة إلكترونية">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">Nom (Français)</label>
                        <input type="text" name="nom_fr">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">رقم التسجيل *</label>
                        <input type="text" name="numero" required placeholder="مثال: P-192.11">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">تاريخ التسجيل</label>
                        <input type="date" name="date_inscription" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الترخيص المالي الأساسي (AP) دج</label>
                        <input type="number" name="ap_initiale" step="0.01" value="0.00">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">الالتزام الفعلي دج</label>
                        <input type="number" name="montant_engagement" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الدفع الفعلي دج</label>
                        <input type="number" name="montant_paiement" step="0.01" value="0.00">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">نسبة الإنجاز الفيزيائي (%)</label>
                        <input type="number" name="taux_physique" min="0" max="100" value="0">
                    </div>
                </div>
                <div class="fin-form-group">
                    <label class="fin-form-label">ملاحظات</label>
                    <input type="text" name="observation" placeholder="توضيح حالة المشروع...">
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalOperation')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Bourse --}}
<div class="fin-modal-overlay" id="modalBourse">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title">تسجيل حوالة منحة متربص</div>
            <button class="fin-modal-close" onclick="closeModal('modalBourse')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="bourseForm" onsubmit="submitBourse(event)">
            <div class="fin-modal-body">
                <div class="fin-form-group">
                    <label class="fin-form-label">المتربص المستفيد *</label>
                    <select name="apprenant_id" required>
                        <option value="">— اختر المتربص —</option>
                        @foreach($apprenants as $ap)
                        <option value="{{ $ap['id'] }}">{{ $ap['nom'] }} {{ $ap['prenom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">مبلغ المنحة (دج) *</label>
                        <input type="number" name="montant" step="0.01" required value="6000.00">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">مدة الدفع (بالأشهر)</label>
                        <input type="number" name="duree_payee" value="6" min="1" max="12">
                    </div>
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalBourse')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Stock --}}
<div class="fin-modal-overlay" id="modalStock">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title">إضافة مادة للمخزن</div>
            <button class="fin-modal-close" onclick="closeModal('modalStock')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="stockForm" onsubmit="submitStock(event)">
            <div class="fin-modal-body">
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">اسم المادة (العربية) *</label>
                        <input type="text" name="nom" required placeholder="مثال: ورق طباعة A4">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">Nom de l'Article (Français)</label>
                        <input type="text" name="nom_fr">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">رمز كود المادة</label>
                        <input type="number" name="code" placeholder="تلقائي إن تُرِك فارغاً">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">البند المالي الملحق (النوع)</label>
                        <input type="text" name="contenu" placeholder="مثال: المادة 02 البند 01">
                    </div>
                </div>
                <div class="fin-form-group">
                    <label class="fin-form-label">الرقم الترتيبي للجرد</label>
                    <input type="number" name="num_ord" value="1">
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalStock')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Grade --}}
<div class="fin-modal-overlay" id="modalGrade">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title" id="gradeModalTitle">إضافة منصب مالي</div>
            <button class="fin-modal-close" onclick="closeModal('modalGrade')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="gradeForm" onsubmit="submitGrade(event)">
            <div class="fin-modal-body">
                <input type="hidden" id="gradeId" name="id">
                <input type="hidden" id="gradeEtabId" name="etab_id" value="{{ $etabId }}">
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الرتبة *</label>
                        <select name="grade_id" id="gradeGradeId" required>
                            <option value="">— اختر الرتبة —</option>
                            @foreach($allGrades as $g)
                            @php $g=(array)$g; @endphp
                            <option value="{{ $g['id'] }}">{{ $g['nom'] }} ({{ $g['nom_fr'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">الفئة</label>
                        <select name="categorie" id="gradeCategorie">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">السنة</label>
                        <input type="number" name="annee" id="gradeAnnee" value="{{ date('Y') }}" min="2000" max="2099">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">الإندكس</label>
                        <input type="number" name="indice" id="gradeIndice" value="0" step="0.01">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">العدد الإجمالي</label>
                        <input type="number" name="nbr" id="gradeNbr" value="0" min="0">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">منها إناث</label>
                        <input type="number" name="nbr_f" id="gradeNbrF" value="0" min="0">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">المشغولة</label>
                        <input type="number" name="occupe" id="gradeOccupe" value="0" min="0">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">الشاغرة</label>
                        <input type="number" name="vacant" id="gradeVacant" value="0" min="0">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الفائض</label>
                        <input type="number" name="surplus" id="gradeSurplus" value="0" min="0">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">الاحتياج</label>
                        <input type="number" name="besoin" id="gradeBesoin" value="0" min="0">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">المعالجة السنوية (دج)</label>
                        <input type="number" name="traitement" id="gradeTraitement" value="0" step="0.01">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">العلاوات والتعويضات (دج)</label>
                        <input type="number" name="primes" id="gradePrimes" value="0" step="0.01">
                    </div>
                </div>
                <div class="fin-form-group">
                    <label class="fin-form-label">النفقة السنوية الإجمالية (دج)</label>
                    <input type="number" name="depense" id="gradeDepense" value="0" step="0.01">
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalGrade')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Programme --}}
<div class="fin-modal-overlay" id="modalProgramme">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title" id="progModalTitle">إضافة برنامج</div>
            <button class="fin-modal-close" onclick="closeModal('modalProgramme')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="progForm" onsubmit="submitProgramme(event)">
            <div class="fin-modal-body">
                <input type="hidden" id="progId" name="id">
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الاسم بالعربية *</label>
                        <input type="text" name="nom" id="progNom" required>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">Nom en Français</label>
                        <input type="text" name="nom_fr" id="progNomFr">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الرمز</label>
                        <input type="text" name="code" id="progCode">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">الرمز الكامل</label>
                        <input type="text" name="code_complet" id="progCodeComplet">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">السنة</label>
                        <input type="number" name="annee" id="progAnnee" value="{{ date('Y') }}">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">ملاحظات</label>
                        <input type="text" name="obs" id="progObs">
                    </div>
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalProgramme')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Sous Programme --}}
<div class="fin-modal-overlay" id="modalSousProg">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title" id="spModalTitle">إضافة برنامج فرعي</div>
            <button class="fin-modal-close" onclick="closeModal('modalSousProg')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="spForm" onsubmit="submitSousProgramme(event)">
            <div class="fin-modal-body">
                <input type="hidden" id="spId" name="id">
                <div class="fin-form-group">
                    <label class="fin-form-label">البرنامج الأب *</label>
                    <select name="programme_id" id="spProgId" required>
                        <option value="">— اختر البرنامج —</option>
                        @foreach($programmes as $p)
                        @php $p=(array)$p; @endphp
                        <option value="{{ $p['id'] }}">{{ $p['nom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الاسم بالعربية *</label>
                        <input type="text" name="nom" id="spNom" required>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">Nom en Français</label>
                        <input type="text" name="nom_fr" id="spNomFr">
                    </div>
                </div>
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الرمز</label>
                        <input type="text" name="code" id="spCode">
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">الرمز الكامل</label>
                        <input type="text" name="code_complet" id="spCodeComplet">
                    </div>
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalSousProg')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Fournisseur --}}
<div class="fin-modal-overlay" id="modalFournisseur">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title" id="fournModalTitle">إضافة مورد</div>
            <button class="fin-modal-close" onclick="closeModal('modalFournisseur')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="fournForm" onsubmit="submitFournisseur(event)">
            <div class="fin-modal-body">
                <input type="hidden" id="fournId" name="id">
                <div class="fin-form-row">
                    <div class="fin-form-group">
                        <label class="fin-form-label">الاسم بالعربية *</label>
                        <input type="text" name="nom" id="fournNom" required>
                    </div>
                    <div class="fin-form-group">
                        <label class="fin-form-label">Nom en Français</label>
                        <input type="text" name="nom_fr" id="fournNomFr">
                    </div>
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn-fin-print" onclick="closeModal('modalFournisseur')">إلغاء</button>
                <button type="submit" class="btn-fin-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const BASE = '{{ url("") }}';
const CSRF = '{{ csrf_token() }}';

// ─── Tab switching ──────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.fin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.fin-panel').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

// ─── Print Indemnities ──────────────────────────────────
function printIndemnitiesTable() {
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    const tableHtml = document.getElementById('tblIndemnities').outerHTML;
    printWindow.document.write(
        '<html>' +
        '<head>' +
        '    <title>جدول التعويضات والاقتطاعات المالية</title>' +
        '    <style>' +
        '        body { font-family: "Cairo", sans-serif; direction: rtl; padding: 40px; }' +
        '        h2 { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }' +
        '        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }' +
        '        th, td { border: 1px solid #ccc; padding: 10px; text-align: right; }' +
        '        th { background-color: #f5f5f5; }' +
        '        .badge { padding: 3px 6px; border-radius: 4px; font-size: 11px; }' +
        '        code { font-family: monospace; }' +
        '        @media print {' +
        '            button { display: none; }' +
        '        }' +
        '    </style>' +
        '</head>' +
        '<body>' +
        '    <div style="text-align: center; margin-bottom: 20px;">' +
        '        <button onclick="window.print()" style="padding: 8px 18px; font-size: 15px; cursor: pointer; background-color: #1a6bcc; color: white; border: none; border-radius: 4px;">طباعة المستند</button>' +
        '    </div>' +
        '    <h2>جدول التعويضات والاقتطاعات المالية الرسمية لقطاع التكوين المهني</h2>' +
        '    <div style="text-align: left; font-size: 12px; color: #666; margin-bottom: 10px;">تاريخ الاستخراج: ' + new Date().toLocaleDateString('ar-DZ') + '</div>' +
        tableHtml +
        '</body>' +
        '</html>'
    );
    printWindow.document.close();
}

// ─── Live Table Filter ──────────────────────────────────
function filterTable(tableId, inputId, cols) {
    const q = document.getElementById(inputId).value.toLowerCase();
    const rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    rows.forEach(r => {
        const cells = r.querySelectorAll('td');
        const match = cols.some(c => (cells[c]?.textContent || '').toLowerCase().includes(q));
        r.style.display = match ? '' : 'none';
    });
}

// ─── Toast ──────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.getElementById('finToast');
    t.textContent = msg;
    t.className = 'fin-toast ' + type;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

// ─── Modal helpers ──────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
// Clear and close
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.fin-modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

// ─── API Call ───────────────────────────────────────────
async function apiCall(url, data, method = 'POST') {
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(data)
    });
    return res.json();
}

// ─── Reload with tab ────────────────────────────────────
function reloadTab(tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.location.href = url.toString();
}

// ═══════════════════════════════════════════════════════════
// NEW EXTENDED TAB SUBMISSIONS
// ═══════════════════════════════════════════════════════════
async function submitBudget(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/finances/budget/store', data);
    if (res.success) { showToast(res.message); closeModal('modalBudget'); setTimeout(()=>reloadTab('budget_prep'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitOperation(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/finances/operation/store', data);
    if (res.success) { showToast(res.message); closeModal('modalOperation'); setTimeout(()=>reloadTab('investissements'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitBourse(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/finances/bourse/store', data);
    if (res.success) { showToast(res.message); closeModal('modalBourse'); setTimeout(()=>reloadTab('grants'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitStock(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/finances/stock/store', data);
    if (res.success) { showToast(res.message); closeModal('modalStock'); setTimeout(()=>reloadTab('stocks'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitProfile(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/finances/profile/update', data);
    if (res.success) { showToast(res.message); setTimeout(()=>reloadTab('profile'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

// ═══════════════════════════════════════════════════════════
// GRADE CRUD
// ═══════════════════════════════════════════════════════════
function openGradeModal() {
    document.getElementById('gradeModalTitle').textContent = 'إضافة منصب مالي';
    document.getElementById('gradeForm').reset();
    document.getElementById('gradeId').value = '';
    openModal('modalGrade');
}
function editGrade(g) {
    document.getElementById('gradeModalTitle').textContent = 'تعديل المنصب المالي';
    document.getElementById('gradeId').value       = g.id;
    document.getElementById('gradeGradeId').value  = g.grade_id;
    document.getElementById('gradeCategorie').value= g.categorie || 'A';
    document.getElementById('gradeAnnee').value    = g.annee || '{{ date("Y") }}';
    document.getElementById('gradeIndice').value   = g.Indice || 0;
    document.getElementById('gradeNbr').value      = g.nbr_total || 0;
    document.getElementById('gradeNbrF').value     = g.nbr_femmes || 0;
    document.getElementById('gradeOccupe').value   = g.nbr_occupe || 0;
    document.getElementById('gradeVacant').value   = g.nbr_vacant || 0;
    document.getElementById('gradeSurplus').value  = g.nbr_surplus || 0;
    document.getElementById('gradeBesoin').value   = g.nbr_besoin || 0;
    document.getElementById('gradeTraitement').value= g.Traitementannuel || 0;
    document.getElementById('gradePrimes').value   = g.Primeetindemnites || 0;
    document.getElementById('gradeDepense').value  = g.Depenceannuel || 0;
    openModal('modalGrade');
}
async function submitGrade(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd.entries());
    const isEdit = !!data.id;
    const url = isEdit
        ? BASE + '/dashboard/finances/grades/update'
        : BASE + '/dashboard/finances/grades/store';
    const res = await apiCall(url, data);
    if (res.success) { showToast(res.message || 'تم بنجاح'); closeModal('modalGrade'); setTimeout(()=>reloadTab('grades'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}
async function deleteGrade(id) {
    if (!confirm('هل تريد حذف هذا المنصب المالي؟')) return;
    const res = await apiCall(BASE + '/dashboard/finances/grades/delete/' + id, {});
    if (res.success) { showToast(res.message); document.querySelector(`#tblGrades tr[data-id="${id}"]`)?.remove(); }
    else showToast(res.error, 'error');
}

// ═══════════════════════════════════════════════════════════
// PROGRAMME CRUD
// ═══════════════════════════════════════════════════════════
function openProgrammeModal() {
    document.getElementById('progModalTitle').textContent = 'إضافة برنامج';
    document.getElementById('progForm').reset();
    document.getElementById('progId').value = '';
    openModal('modalProgramme');
}
function editProgramme(p) {
    document.getElementById('progModalTitle').textContent = 'تعديل البرنامج';
    document.getElementById('progId').value          = p.id;
    document.getElementById('progNom').value         = p.nom || '';
    document.getElementById('progNomFr').value       = p.nom_fr || '';
    document.getElementById('progCode').value        = p.code || '';
    document.getElementById('progCodeComplet').value = p.code_complet || '';
    document.getElementById('progAnnee').value       = p.annee || '{{ date("Y") }}';
    document.getElementById('progObs').value         = p.obs || '';
    openModal('modalProgramme');
}
async function submitProgramme(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const url = data.id
        ? BASE + '/dashboard/finances/programmes/update'
        : BASE + '/dashboard/finances/programmes/store';
    const res = await apiCall(url, data);
    if (res.success) { showToast(res.message || 'تم بنجاح'); closeModal('modalProgramme'); setTimeout(()=>reloadTab('programmes'), 800); }
    else showToast(res.error, 'error');
}
async function deleteProgramme(id) {
    if (!confirm('هل تريد حذف هذا البرنامج؟')) return;
    const res = await apiCall(BASE + '/dashboard/finances/programmes/delete/' + id, {});
    if (res.success) { showToast(res.message || 'تم الحذف'); document.querySelector(`#tblProg tr[data-id="${id}"]`)?.remove(); }
    else showToast(res.error, 'error');
}

// ═══════════════════════════════════════════════════════════
// SOUS-PROGRAMME CRUD
// ═══════════════════════════════════════════════════════════
function openSousProgrammeModal() {
    document.getElementById('spModalTitle').textContent = 'إضافة برنامج فرعي';
    document.getElementById('spForm').reset();
    document.getElementById('spId').value = '';
    openModal('modalSousProg');
}
function editSousProgramme(sp) {
    document.getElementById('spModalTitle').textContent = 'تعديل البرنامج الفرعي';
    document.getElementById('spId').value          = sp.id;
    document.getElementById('spProgId').value      = sp.programme_id;
    document.getElementById('spNom').value         = sp.nom || '';
    document.getElementById('spNomFr').value       = sp.nom_fr || '';
    document.getElementById('spCode').value        = sp.code || '';
    document.getElementById('spCodeComplet').value = sp.code_complet || '';
    openModal('modalSousProg');
}
async function submitSousProgramme(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const url = data.id
        ? BASE + '/dashboard/finances/sous-programmes/update'
        : BASE + '/dashboard/finances/sous-programmes/store';
    const res = await apiCall(url, data);
    if (res.success) { showToast('تم بنجاح'); closeModal('modalSousProg'); setTimeout(()=>reloadTab('sousprog'), 800); }
    else showToast(res.error, 'error');
}
async function deleteSousProgramme(id) {
    if (!confirm('هل تريد حذف هذا البرنامج الفرعي؟')) return;
    const res = await apiCall(BASE + '/dashboard/finances/sous-programmes/delete/' + id, {});
    if (res.success) { showToast('تم الحذف'); document.querySelector(`#tblSP tr[data-id="${id}"]`)?.remove(); }
    else showToast(res.error, 'error');
}

// ═══════════════════════════════════════════════════════════
// FOURNISSEUR CRUD
// ═══════════════════════════════════════════════════════════
function openFournisseurModal() {
    document.getElementById('fournModalTitle').textContent = 'إضافة مورد';
    document.getElementById('fournForm').reset();
    document.getElementById('fournId').value = '';
    openModal('modalFournisseur');
}
function editFournisseur(f) {
    document.getElementById('fournModalTitle').textContent = 'تعديل المورد';
    document.getElementById('fournId').value    = f.id;
    document.getElementById('fournNom').value   = f.nom || '';
    document.getElementById('fournNomFr').value = f.nom_fr || '';
    openModal('modalFournisseur');
}
async function submitFournisseur(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const url = data.id
        ? BASE + '/dashboard/finances/fournisseurs/update'
        : BASE + '/dashboard/finances/fournisseurs/store';
    const res = await apiCall(url, data);
    if (res.success) { showToast(res.message); closeModal('modalFournisseur'); setTimeout(()=>reloadTab('fournisseurs'), 800); }
    else showToast(res.error, 'error');
}
async function deleteFournisseur(id) {
    if (!confirm('هل تريد حذف هذا المورد؟')) return;
    const res = await apiCall(BASE + '/dashboard/finances/fournisseurs/delete/' + id, {});
    if (res.success) { showToast(res.message); document.querySelector(`#tblFourn tr[data-id="${id}"]`)?.remove(); }
    else showToast(res.error, 'error');
}

// ─── Filters (server-side) ──────────────────────────────
function filterByCategorie(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('categorie', val);
    url.searchParams.set('tab', 'grades');
    window.location.href = url.toString();
}
function filterByAnnee(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('annee', val);
    url.searchParams.set('tab', 'grades');
    window.location.href = url.toString();
}
// ═══════════════════════════════════════════════════════════
// WILAYA → ETABLISSEMENT CASCADE FILTER
// ═══════════════════════════════════════════════════════════
(function() {
    const wilyaSelect = document.getElementById('filterWilaya');
    const etabSelect  = document.getElementById('filterEtab');
    if (!wilyaSelect || !etabSelect) return;

    // Build a map: { dfepId: [ {value, text}, ... ] }
    const allOptions = Array.from(etabSelect.options).map(o => ({
        value: o.value, text: o.text, dfep: o.dataset.dfep
    }));

    function cascadeEtabs(dfepId) {
        const selectedEtab = etabSelect.value; // preserve current selection
        etabSelect.innerHTML = '<option value="" style="color:#333;">— كل المؤسسات —</option>';
        const filtered = dfepId ? allOptions.filter(o => o.dfep == dfepId) : allOptions;
        filtered.forEach(o => {
            if (!o.value) return; // skip empty
            const opt = document.createElement('option');
            opt.value = o.value; opt.textContent = o.text;
            opt.setAttribute('data-dfep', o.dfep);
            opt.style.color = '#333';
            if (o.value === selectedEtab) opt.selected = true;
            etabSelect.appendChild(opt);
        });
    }

    // Init on page load
    if (wilyaSelect.value) cascadeEtabs(wilyaSelect.value);

    wilyaSelect.addEventListener('change', function() {
        cascadeEtabs(this.value);
        // Reset etab selection when wilaya changes
        etabSelect.value = '';
        // Auto-submit to scope by wilaya immediately
        document.getElementById('filterForm').submit();
    });
})();

function viewBourseDetails(b) {
    const title = 'تفاصيل المنحة للمتربص: ' + b.nom + ' ' + b.prenom;
    document.getElementById('detailsModalTitle').textContent = title;
    
    // Bind establishment badge
    const badge = document.getElementById('finEtabBadge');
    const etabEl = document.getElementById('finEtabName');
    const wilayaEl = document.getElementById('finWilayaName');
    if (b.etablissement_nom) {
        etabEl.textContent = b.etablissement_nom;
        wilayaEl.textContent = b.wilaya_nom ? '📍 ' + b.wilaya_nom : '';
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
    
    const sections = [
        {
            title: 'معلومات المتربص والتخصص',
            icon: 'graduation-cap',
            fields: [
                { label: 'الاسم واللقب', value: b.nom + ' ' + b.prenom },
                { label: 'التخصص', value: b.specialite || 'تمهين' },
                { label: 'المؤسسة التكوينية', value: b.etablissement_nom || b.etablissemnt || '—' }
            ]
        },
        {
            title: 'بيانات المنحة والدفع',
            icon: 'money-bill-wave',
            fields: [
                { label: 'مبلغ المنحة الشهري', value: b.montant ? (parseFloat(b.montant).toLocaleString() + ' دج') : '—' },
                { label: 'مدة الدفع الفعلي', value: b.duree_payee + ' أشهر' },
                { label: 'الصافي المدفوع الإجمالي', value: b.net_paye ? (parseFloat(b.net_paye).toLocaleString() + ' دج') : '—' },
                { label: 'تاريخ بداية الالتزام', value: b.date_debut || '—' },
                { label: 'تاريخ نهاية الالتزام', value: b.date_fin || '—' }
            ]
        },
        {
            title: 'تفاصيل الحوالة',
            icon: 'calendar-check',
            fields: [
                { label: 'سنة الحوالة', value: b.annee || '—' },
                { label: 'السداسي / الدورة', value: b.semestre || '—' }
            ]
        }
    ];
    
    const container = document.getElementById('detailsModalFields');
    container.innerHTML = '';
    
    sections.forEach(s => {
        // Filter out empty fields
        const activeFields = s.fields.filter(f => {
            return f.value !== null && f.value !== undefined && f.value !== '' && f.value !== '—';
        });
        
        if (activeFields.length === 0) return; // Skip section if no active fields
        
        const secTitle = document.createElement('div');
        secTitle.className = 'fin-det-section-title';
        secTitle.innerHTML = `<i class="fa-solid fa-${s.icon}"></i> ${s.title}`;
        container.appendChild(secTitle);
        
        const grid = document.createElement('div');
        grid.className = 'fin-det-grid';
        
        activeFields.forEach(f => {
            const fieldEl = document.createElement('div');
            fieldEl.className = 'fin-det-field';
            fieldEl.innerHTML = `
                <span class="fin-det-field-label">${f.label}</span>
                <span class="fin-det-field-value">${f.value}</span>
            `;
            grid.appendChild(fieldEl);
        });
        
        container.appendChild(grid);
    });
    
    openModal('modalDetails');
}
</script>

{{-- Modal: Details --}}
<div class="fin-modal-overlay" id="modalDetails">
    <div class="fin-modal" style="max-width: 650px;">
        <div class="fin-modal-header" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <div class="fin-modal-title" id="detailsModalTitle">تفاصيل الحوالة</div>
            <div class="fin-etab-badge" id="finEtabBadge" style="display: none; margin-left: 1rem;">
                <span id="finEtabName">—</span>
                <small id="finWilayaName">—</small>
            </div>
            <button class="fin-modal-close" onclick="closeModal('modalDetails')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="fin-modal-body">
            <div id="detailsModalFields" style="text-align: right; direction: rtl; font-family: 'Cairo', sans-serif;">
                <!-- Filled dynamically by JS -->
            </div>
        </div>
        <div class="fin-modal-footer">
            <button type="button" class="btn-fin-print" onclick="closeModal('modalDetails')">إغلاق</button>
        </div>
    </div>
</div>
@endsection

