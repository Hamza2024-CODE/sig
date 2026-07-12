@extends('layouts.main')

@section('title', 'تسيير الوسائل والممتلكات — SGFEP')

@section('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   PATRIMOINE MODULE — Premium UI Design System
   ═══════════════════════════════════════════════════════════════ */
.pat-hero {
    background: linear-gradient(135deg, #0e2a38 0%, #175d73 50%, #208ea3 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(32,142,163,0.3);
}
.pat-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events: none;
}
.pat-hero-title {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.3rem;
}
.pat-hero-sub {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.7);
    font-family: 'Cairo', sans-serif;
}

/* Stat Cards */
.pat-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.pat-stat-card {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--border, #e8edf5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}
.pat-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
.pat-stat-card::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--stat-color, #208ea3), transparent);
    border-radius: 0 16px 16px 0;
}
.pat-stat-label { font-size: 0.75rem; color: var(--tx-3, #8898b0); font-weight: 700; font-family:'Cairo',sans-serif; margin-bottom: 0.4rem; }
.pat-stat-value { font-size: 1.8rem; font-weight: 900; color: var(--tx-1, #0e2a38); font-family:'Cairo',sans-serif; line-height: 1; }
.pat-stat-icon  { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 2.2rem; opacity: 0.06; }

/* Tab Pills */
.pat-tabs {
    display: flex;
    gap: 0.5rem;
    background: var(--bg-surface, #f4f6fb);
    padding: 0.4rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.pat-tab {
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
.pat-tab:hover { background: rgba(32,142,163,0.08); color: #208ea3; }
.pat-tab.active {
    background: linear-gradient(135deg, #208ea3, #175d73);
    color: #fff !important;
    box-shadow: 0 4px 15px rgba(32,142,163,0.35);
}
.pat-tab-count {
    background: rgba(255,255,255,0.25);
    border-radius: 6px;
    padding: 1px 6px;
    font-size: 0.72rem;
}

/* Panel */
.pat-panel { display: none; animation: fadeUp 0.3s ease; }
.pat-panel.active { display: block; }
@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* Toolbar */
.pat-toolbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.pat-toolbar .pat-search {
    flex: 1; min-width: 220px;
    position: relative;
}
.pat-toolbar .pat-search input {
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
.pat-toolbar .pat-search input:focus { outline: none; border-color: #208ea3; box-shadow: 0 0 0 3px rgba(32,142,163,0.1); }
.pat-toolbar .pat-search i { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--tx-3); font-size: 0.85rem; }

/* Buttons */
.btn-pat-add {
    background: linear-gradient(135deg, #208ea3, #175d73);
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
    box-shadow: 0 4px 12px rgba(32,142,163,0.25);
    white-space: nowrap;
}
.btn-pat-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(32,142,163,0.35); }
.btn-pat-print {
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
.btn-pat-print:hover { border-color: #208ea3; color: #208ea3; background: rgba(32,142,163,0.06); }

/* Table */
.pat-table-wrap {
    background: var(--bg-surface-elevated);
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.pat-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Cairo', sans-serif;
    font-size: 0.83rem;
}
.pat-table thead tr {
    background: linear-gradient(135deg, #f0f7ff, #e6f3f7);
    border-bottom: 2px solid #d0e8f0;
}
.pat-table thead th {
    padding: 0.85rem 1rem;
    text-align: right;
    font-weight: 800;
    color: #174e5e;
    font-size: 0.78rem;
    white-space: nowrap;
}
.pat-table tbody tr {
    border-bottom: 1px solid var(--border, #eef1f7);
    transition: background 0.15s;
}
.pat-table tbody tr:hover { background: rgba(32,142,163,0.03); }
.pat-table tbody td {
    padding: 0.75rem 1rem;
    color: var(--tx-1);
    vertical-align: middle;
}

/* Modal Overlay */
.pat-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(10,20,40,0.6);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    overflow-y: auto;
}
.pat-modal-overlay.open { display: flex; }
.pat-modal {
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
    margin: auto;
}
.pat-modal form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}
@keyframes modalIn { from { opacity:0; transform:scale(0.94) translateY(-10px); } to { opacity:1; transform:scale(1) translateY(0); } }
.pat-modal-header {
    padding: 1.5rem 1.75rem 1rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.pat-modal-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--tx-1);
    font-family: 'Cairo', sans-serif;
}
.pat-modal-close {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: none;
    background: var(--bg-surface, #f4f6fb);
    color: var(--tx-2);
    cursor: pointer;
    font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s;
}
.pat-modal-close:hover { background: #dc3545; color: #fff; }
.pat-modal-body {
    padding: 1.5rem 1.75rem;
    flex: 1;
    overflow-y: auto;
}
.pat-modal-footer {
    padding: 1rem 1.75rem 1.5rem;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    border-top: 1px solid var(--border);
    flex-shrink: 0;
}

/* Premium Scrollbars */
.pat-modal-body::-webkit-scrollbar,
.det-body::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.pat-modal-body::-webkit-scrollbar-track,
.det-body::-webkit-scrollbar-track {
    background: transparent;
}
.pat-modal-body::-webkit-scrollbar-thumb,
.det-body::-webkit-scrollbar-thumb {
    background: rgba(32, 142, 163, 0.2);
    border-radius: 10px;
}
.pat-modal-body::-webkit-scrollbar-thumb:hover,
.det-body::-webkit-scrollbar-thumb:hover {
    background: rgba(32, 142, 163, 0.4);
}

/* Form Fields */
.pat-form-group { margin-bottom: 1rem; }
.pat-form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--tx-2);
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.4rem;
}
.pat-form-group input,
.pat-form-group select,
.pat-form-group textarea {
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
.pat-form-group input:focus,
.pat-form-group select:focus,
.pat-form-group textarea:focus {
    outline: none;
    border-color: #208ea3;
    box-shadow: 0 0 0 3px rgba(32,142,163,0.12);
}
.pat-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }

/* Toast */
.pat-toast {
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
.pat-toast.success { background: #1cb05f; color: #fff; }
.pat-toast.error   { background: #dc3545; color: #fff; }

/* Dark Mode Compat */
[data-theme="dark"] .pat-stat-card { background: var(--bg-surface-elevated, #1e2d4a); border-color: rgba(255,255,255,0.07); }
[data-theme="dark"] .pat-table-wrap { background: var(--bg-surface-elevated, #1e2d4a); }
[data-theme="dark"] .pat-modal { background: #1e2d4a; }
[data-theme="dark"] .pat-table thead tr { background: rgba(32,142,163,0.12); }

/* Badge Styles */
.fin-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 700;
    font-family: 'Cairo', sans-serif;
    line-height: 1;
}
.fin-badge-blue   { background: rgba(26,107,204,0.12);  color: #1a6bcc; }
.fin-badge-green  { background: rgba(28,176,95,0.12);   color: #1cb05f; }
.fin-badge-orange { background: rgba(255,127,17,0.12);  color: #e07b00; }
.fin-badge-red    { background: rgba(220,53,69,0.12);   color: #dc3545; }

/* ═══ Enhanced Add/Create Modals ═══ */
.pat-modal-header-gradient {
    background: linear-gradient(135deg, #0e2a38 0%, #175d73 50%, #208ea3 100%);
    padding: 1.25rem 1.75rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    overflow: hidden;
}
.pat-modal-header-gradient::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 50%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.pat-modal-header-icon {
    width: 46px;
    height: 46px;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #fff;
    flex-shrink: 0;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,0.2);
}
.pat-modal-header-text {
    flex: 1;
    text-align: right;
    direction: rtl;
}
.pat-modal-header-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin: 0 0 0.15rem;
}
.pat-modal-header-sub {
    font-size: 0.76rem;
    color: rgba(255,255,255,0.75);
    font-family: 'Cairo', sans-serif;
}
.pat-modal-header-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.9);
    cursor: pointer;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}
.pat-modal-header-close:hover {
    background: rgba(220,53,69,0.85);
    border-color: transparent;
    color: #fff;
    transform: rotate(90deg);
}

/* Custom pills/checkbox wraps */
.pat-pill-checkbox-group {
    display: flex;
    gap: 1rem;
    background: var(--bg-surface, #f4f6fb);
    padding: 0.85rem 1.2rem;
    border-radius: 12px;
    border: 1.5px solid var(--border, #dde3ef);
    flex-wrap: wrap;
    width: 100%;
}
.pat-pill-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--tx-2);
    font-family: 'Cairo', sans-serif;
    margin: 0;
    user-select: none;
    transition: color 0.2s;
}
.pat-pill-checkbox:hover {
    color: #208ea3;
}
.pat-pill-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    accent-color: #208ea3;
    cursor: pointer;
    margin: 0;
}

/* Form input focus updates */
.pat-form-group input:focus,
.pat-form-group select:focus,
.pat-form-group textarea:focus {
    border-color: #208ea3;
    box-shadow: 0 0 0 4px rgba(32,142,163,0.15);
}

/* Dark mode overrides */
[data-theme="dark"] .pat-modal-header-close {
    border-color: rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.05);
}
[data-theme="dark"] .pat-form-group input,
[data-theme="dark"] .pat-form-group select,
[data-theme="dark"] .pat-form-group textarea {
    border-color: rgba(255,255,255,0.08);
    background: #142036;
    color: #e2e8f0;
}
[data-theme="dark"] .pat-form-group input:focus,
[data-theme="dark"] .pat-form-group select:focus,
[data-theme="dark"] .pat-form-group textarea:focus {
    border-color: #208ea3;
}
[data-theme="dark"] .pat-pill-checkbox-group {
    background: rgba(10,20,40,0.3);
    border-color: rgba(255,255,255,0.08);
}

/* ═══ Enhanced Details Modal ═══ */
#modalDetails .pat-modal {
    max-width: 680px;
}
.det-header-banner {
    background: linear-gradient(135deg, #0e2a38 0%, #175d73 50%, #208ea3 100%);
    padding: 1.5rem 1.75rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    overflow: hidden;
}
.det-header-banner::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -10%;
    width: 50%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.07) 0%, transparent 70%);
    pointer-events: none;
}
.det-header-icon {
    width: 52px;
    height: 52px;
    background: rgba(255,255,255,0.15);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: #fff;
    flex-shrink: 0;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,0.2);
}
.det-header-text { flex: 1; }
.det-header-title {
    font-size: 1.05rem;
    font-weight: 900;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin: 0 0 0.15rem;
}
.det-header-sub {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.7);
    font-family: 'Cairo', sans-serif;
}
.det-etab-badge {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
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
.det-etab-badge small {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.65);
    font-weight: 600;
}
.det-close-btn {
    width: 36px; height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.9);
    cursor: pointer;
    font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s;
    flex-shrink: 0;
}
.det-close-btn:hover { background: rgba(220,53,69,0.7); border-color: transparent; }

.det-body {
    padding: 1.25rem 1.5rem 1rem;
    flex: 1;
    overflow-y: auto;
}
.det-section-title {
    font-size: 0.72rem;
    font-weight: 800;
    color: #208ea3;
    font-family: 'Cairo', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.6rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid rgba(32,142,163,0.15);
    display: flex;
    align-items: center;
    gap: 6px;
}
.det-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.6rem;
    margin-bottom: 1rem;
}
.det-grid.single { grid-template-columns: 1fr; }
.det-field {
    background: var(--bg-surface, #f4f6fb);
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    border: 1px solid var(--border, #e8edf5);
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.det-field-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--tx-3, #8898b0);
    font-family: 'Cairo', sans-serif;
}
.det-field-value {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--tx-1, #0e2a38);
    font-family: 'Cairo', sans-serif;
    word-break: break-word;
}
.det-obs-box {
    background: rgba(255, 193, 7, 0.07);
    border: 1px solid rgba(255, 193, 7, 0.25);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-family: 'Cairo', sans-serif;
    font-size: 0.85rem;
    color: var(--tx-1);
    line-height: 1.6;
    display: flex;
    gap: 8px;
    align-items: flex-start;
    margin-bottom: 1rem;
}
.det-obs-icon { color: #e5a000; font-size: 0.9rem; margin-top: 2px; flex-shrink: 0; }
[data-theme="dark"] .det-field { background: rgba(255,255,255,0.05); }
[data-theme="dark"] .det-obs-box { background: rgba(255,193,7,0.05); }
</style>
@endsection

@section('content')
<div class="pat-toast" id="patToast"></div>

{{-- Hero header --}}
<div class="pat-hero">
    <div class="row align-items-center">
        <div class="col">
            <div class="pat-hero-title">
                <i class="fa-solid fa-building me-2"></i>
                تسيير الوسائل والممتلكات
            </div>
            <div class="pat-hero-sub">
                التجهيزات البيداغوجية والتقنية · حظيرة السيارات والمركبات · السكنات والمقرات والمساحات العقارية
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
            @if(\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') === '1')
            <button class="btn-pat-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> طباعة
            </button>
            @endif
        </div>
    </div>
</div>

{{-- Stats cards --}}
<div class="pat-stat-grid">
    <div class="pat-stat-card" style="--stat-color:#208ea3">
        <div class="pat-stat-label">إجمالي التجهيزات</div>
        <div class="pat-stat-value">{{ $stats['total_equipements'] }}</div>
        <i class="fa-solid fa-desktop pat-stat-icon"></i>
    </div>
    <div class="pat-stat-card" style="--stat-color:#1cb05f">
        <div class="pat-stat-label">حظيرة المركبات</div>
        <div class="pat-stat-value">{{ $stats['total_vehicules'] }}</div>
        <i class="fa-solid fa-car pat-stat-icon"></i>
    </div>
    <div class="pat-stat-card" style="--stat-color:#e07b00">
        <div class="pat-stat-label">المقرات والقاعات</div>
        <div class="pat-stat-value">{{ $stats['total_locaux'] }}</div>
        <i class="fa-solid fa-door-open pat-stat-icon"></i>
    </div>
    <div class="pat-stat-card" style="--stat-color:#dc3545">
        <div class="pat-stat-label">السكنات الوظيفية</div>
        <div class="pat-stat-value">{{ $stats['total_logements'] }}</div>
        <i class="fa-solid fa-house pat-stat-icon"></i>
    </div>
</div>

{{-- Tab bar --}}
<div class="pat-tabs">
    <button class="pat-tab {{ $tab==='equipements' ? 'active' : '' }}" onclick="switchTab('equipements')">
        <i class="fa-solid fa-desktop"></i> التجهيزات التقنية
        <span class="pat-tab-count">{{ $stats['total_equipements'] }}</span>
    </button>
    <button class="pat-tab {{ $tab==='vehicules' ? 'active' : '' }}" onclick="switchTab('vehicules')">
        <i class="fa-solid fa-car"></i> حضيرة المركبات
        <span class="pat-tab-count">{{ $stats['total_vehicules'] }}</span>
    </button>
    <button class="pat-tab {{ $tab==='locaux' ? 'active' : '' }}" onclick="switchTab('locaux')">
        <i class="fa-solid fa-door-open"></i> المقرات البيداغوجية
        <span class="pat-tab-count">{{ $stats['total_locaux'] }}</span>
    </button>
    <button class="pat-tab {{ $tab==='proprietes' ? 'active' : '' }}" onclick="switchTab('proprietes')">
        <i class="fa-solid fa-map"></i> الممتلكات والفضاءات
        <span class="pat-tab-count">{{ count($proprietes) }}</span>
    </button>
    <button class="pat-tab {{ $tab==='logements' ? 'active' : '' }}" onclick="switchTab('logements')">
        <i class="fa-solid fa-house"></i> حضيرة السكنات
        <span class="pat-tab-count">{{ $stats['total_logements'] }}</span>
    </button>
</div>

{{-- Panels --}}

{{-- Equipments --}}
<div id="panel-equipements" class="pat-panel {{ $tab==='equipements' ? 'active' : '' }}">
    <div class="pat-toolbar">
        <div class="pat-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchEq" placeholder="بحث في التجهيزات..." onkeyup="filterTable('tblEq','searchEq',[0,1])">
        </div>
        <button class="btn-pat-add" onclick="openModal('modalEquipment')">
            <i class="fa-solid fa-plus"></i> إضافة تجهيز
        </button>
    </div>
    <div class="pat-table-wrap">
        <table class="pat-table" id="tblEq">
            <thead>
                <tr>
                    <th>تسمية التجهيز</th>
                    <th>Nom (Fr)</th>
                    <th>الرمز / الكود</th>
                    <th>تاريخ الاستلام</th>
                    <th>تاريخ الاستغلال</th>
                    <th>ملاحظات</th>
                    <th>حالة التحقق</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipements as $eq)
                <tr>
                    <td><strong>{{ $eq['designation'] }}</strong></td>
                    <td>{{ $eq['designation_fr'] }}</td>
                    <td><code>{{ $eq['code'] ?? '—' }}</code></td>
                    <td>{{ $eq['date_reception'] ?? '—' }}</td>
                    <td>{{ $eq['date_exploitation'] ?? '—' }}</td>
                    <td>{{ $eq['description'] ?? '—' }}</td>
                    <td>
                        <span class="fin-badge {{ $eq['is_validated'] ? 'fin-badge-green' : 'fin-badge-orange' }}">
                            {{ $eq['is_validated'] ? 'معتمد' : 'قيد المراجعة' }}
                        </span>
                    </td>
                    <td>
                        <button class="btn-pat-print" onclick="viewEquipmentDetails(<?= htmlspecialchars(json_encode($eq)) ?>)" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#208ea3; color:#208ea3; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-eye"></i> تفاصيل
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="fin-empty"><i class="fa-solid fa-inbox"></i><p>لا توجد تجهيزات مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Vehicles --}}
<div id="panel-vehicules" class="pat-panel {{ $tab==='vehicules' ? 'active' : '' }}">
    <div class="pat-toolbar">
        <div class="pat-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchVeh" placeholder="بحث في حظيرة السيارات..." onkeyup="filterTable('tblVeh','searchVeh',[0,2])">
        </div>
        <button class="btn-pat-add" onclick="openModal('modalVehicule')">
            <i class="fa-solid fa-plus"></i> إضافة مركبة
        </button>
    </div>
    <div class="pat-table-wrap">
        <table class="pat-table" id="tblVeh">
            <thead>
                <tr>
                    <th>رقم التسجيل (اللوحة)</th>
                    <th>الماركة / الموديل</th>
                    <th>سنة الصنع</th>
                    <th>رقم الشاسيه</th>
                    <th>عدد المقاعد</th>
                    <th>اللوحة السابقة</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicules as $v)
                <tr>
                    <td><strong>{{ $v['immatriculation'] }}</strong></td>
                    <td>{{ $v['marque'] }}</td>
                    <td>{{ $v['annee'] }}</td>
                    <td><code>{{ $v['chassis'] }}</code></td>
                    <td>{{ $v['places'] }} مقاعد</td>
                    <td>{{ $v['immatriculation_prec'] ?? '—' }}</td>
                    <td>
                        <span class="fin-badge {{ ($v['validation'] ?? null) ? 'fin-badge-green' : 'fin-badge-orange' }}">
                            {{ ($v['validation'] ?? null) ? 'نشطة' : 'تحت المراجعة' }}
                        </span>
                    </td>
                    <td>
                        <button class="btn-pat-print" onclick="viewVehiculeDetails(<?= htmlspecialchars(json_encode($v)) ?>)" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#208ea3; color:#208ea3; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-eye"></i> تفاصيل
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="fin-empty"><i class="fa-solid fa-car"></i><p>لا توجد مركبات مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Locaux --}}
<div id="panel-locaux" class="pat-panel {{ $tab==='locaux' ? 'active' : '' }}">
    <div class="pat-toolbar">
        <div class="pat-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchLoc" placeholder="بحث في المقرات..." onkeyup="filterTable('tblLoc','searchLoc',[0,1])">
        </div>
        <button class="btn-pat-add" onclick="openModal('modalLocal')">
            <i class="fa-solid fa-plus"></i> إضافة مقر
        </button>
    </div>
    <div class="pat-table-wrap">
        <table class="pat-table" id="tblLoc">
            <thead>
                <tr>
                    <th>اسم المقر البيداغوجي</th>
                    <th>نوع المقر</th>
                    <th>الطابق</th>
                    <th>عدد الطاولات والكراسي</th>
                    <th>الطاقة الاستيعابية</th>
                    <th>التجهيزات المتوفرة</th>
                    <th>الحالة الراهنة</th>
                    <th>ملاحظات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locaux as $l)
                <tr>
                    <td>
                        <strong>{{ $l['nom'] }}</strong>
                        @if($l['nom_fr'])
                            <br><small class="text-muted">{{ $l['nom_fr'] }}</small>
                        @endif
                    </td>
                    <td><span class="fin-badge fin-badge-blue">{{ $l['type_nom'] ?? 'غير محدد' }}</span></td>
                    <td>{{ $l['etage'] == 0 ? 'الأرضي' : 'الطابق ' . $l['etage'] }}</td>
                    <td>
                        <span class="text-muted">طاولات:</span> {{ $l['tables'] }} <br>
                        <span class="text-muted">كراسي:</span> {{ $l['chaises'] }}
                    </td>
                    <td><span class="badge bg-light text-dark">{{ $l['places'] }} مقعد</span></td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            @if($l['datashow'])
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.72rem; text-align:right;"><i class="fa-solid fa-video me-1"></i> جهاز عرض</span>
                            @endif
                            @if($l['tableaffichage'])
                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:0.72rem; text-align:right;"><i class="fa-solid fa-clipboard me-1"></i> سبورة عرض</span>
                            @endif
                            @if($l['climatiseur'])
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.72rem; text-align:right;"><i class="fa-solid fa-snowflake me-1"></i> مكيف هواء</span>
                            @endif
                            @if(!$l['datashow'] && !$l['tableaffichage'] && !$l['climatiseur'])
                                <span class="text-muted" style="font-size:0.72rem;">لا توجد تجهيزات</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="fin-badge {{ str_contains($l['etat_nom'] ?? '', 'جيدة') || str_contains($l['etat_nom'] ?? '', 'حسن') ? 'fin-badge-green' : 'fin-badge-orange' }}">
                            {{ $l['etat_nom'] ?? 'غير محدد' }}
                        </span>
                    </td>
                    <td>{{ $l['observation'] ?? '—' }}</td>
                    <td>
                        <button class="btn-pat-print" onclick="viewLocalDetails(<?= htmlspecialchars(json_encode($l)) ?>)" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#208ea3; color:#208ea3; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-eye"></i> تفاصيل
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="fin-empty"><i class="fa-solid fa-inbox"></i><p>لا توجد مقرات وقاعات مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Properties --}}
<div id="panel-proprietes" class="pat-panel {{ $tab==='proprietes' ? 'active' : '' }}">
    <div class="pat-toolbar">
        <div class="pat-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchProp" placeholder="بحث في الممتلكات..." onkeyup="filterTable('tblProp','searchProp',[0])">
        </div>
        <button class="btn-pat-add" onclick="openModal('modalPropriete')">
            <i class="fa-solid fa-plus"></i> إضافة فضاء/ملك عقاري
        </button>
    </div>
    <div class="pat-table-wrap">
        <table class="pat-table" id="tblProp">
            <thead>
                <tr>
                    <th>الممتلكات العقارية والفضاءات</th>
                    <th>Désignation (Français)</th>
                    <th>الحالة القانونية</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proprietes as $pr)
                <tr>
                    <td><strong>{{ $pr['nom'] }}</strong></td>
                    <td>{{ $pr['nom_fr'] }}</td>
                    <td><span class="fin-badge fin-badge-green">ملك ملكية تامة</span></td>
                    <td>
                        <button class="btn-pat-print" onclick="viewProprieteDetails(<?= htmlspecialchars(json_encode($pr)) ?>)" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#208ea3; color:#208ea3; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-eye"></i> تفاصيل
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3"><div class="fin-empty"><i class="fa-solid fa-map"></i><p>لا توجد ممتلكات عقارية مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Housing --}}
<div id="panel-logements" class="pat-panel {{ $tab==='logements' ? 'active' : '' }}">
    <div class="pat-toolbar">
        <div class="pat-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchLog" placeholder="بحث في السكنات..." onkeyup="filterTable('tblLog','searchLog',[0,4])">
        </div>
        <button class="btn-pat-add" onclick="openModal('modalLogement')">
            <i class="fa-solid fa-plus"></i> إضافة سكن وظيفي
        </button>
    </div>
    <div class="pat-table-wrap">
        <table class="pat-table" id="tblLog">
            <thead>
                <tr>
                    <th>موقع السكن (العنوان)</th>
                    <th>نوع السكن</th>
                    <th>طبيعة السكن</th>
                    <th>المساحة والطابق</th>
                    <th>شاغل السكن</th>
                    <th>الشبكات المتوفرة</th>
                    <th>الوضعية القانونية والاحتلال</th>
                    <th>ملاحظات</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logements as $log)
                <tr>
                    <td>
                        <strong>{{ $log['adresse'] }}</strong>
                        <br><small class="text-muted">{{ $log['interne_externe'] == 1 ? 'داخلي للمؤسسة' : 'خارجي' }}</small>
                    </td>
                    <td><span class="fin-badge fin-badge-blue">{{ $log['type_nom'] ?? 'غير محدد' }}</span></td>
                    <td><span class="fin-badge fin-badge-orange">{{ $log['nature_nom'] ?? 'غير محدد' }}</span></td>
                    <td>
                        {{ $log['surface'] }} م² <br>
                        <small class="text-muted">{{ $log['etage'] == 0 ? 'الأرضي' : 'الطابق ' . $log['etage'] }}</small>
                    </td>
                    <td>
                        @if($log['occupant_nom'])
                            <strong>{{ $log['occupant_nom'] }}</strong>
                        @else
                            <span class="text-muted">شاغر</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1" style="max-width:180px;">
                            @if($log['eau'])
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.72rem; text-align:right;"><i class="fa-solid fa-droplet me-1"></i> ماء</span>
                            @endif
                            @if($log['electricite'])
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.72rem; text-align:right;"><i class="fa-solid fa-bolt me-1"></i> كهرباء</span>
                            @endif
                            @if($log['gaz'])
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.72rem; text-align:right;"><i class="fa-solid fa-fire me-1"></i> غاز</span>
                            @endif
                            @if($log['structuree'])
                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:0.72rem; text-align:right;"><i class="fa-solid fa-network-wired me-1"></i> مهيأ</span>
                            @endif
                            @if(!$log['eau'] && !$log['electricite'] && !$log['gaz'] && !$log['structuree'])
                                <span class="text-muted" style="font-size:0.72rem;">لا توجد شبكات</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <span class="fin-badge {{ str_contains($log['occupe_nom'] ?? '', 'شاغر') ? 'fin-badge-orange' : 'fin-badge-green' }}">
                                <i class="fa-solid fa-user me-1"></i> {{ $log['occupe_nom'] ?? 'غير محدد' }}
                            </span>
                            @if($log['juridique_nom'])
                                <span class="fin-badge fin-badge-blue" style="font-size:0.72rem;">
                                    <i class="fa-solid fa-scale-balanced me-1"></i> {{ $log['juridique_nom'] }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $log['observation'] ?? '—' }}</td>
                    <td>
                        <button class="btn-pat-print" onclick="viewLogementDetails(<?= htmlspecialchars(json_encode($log)) ?>)" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#208ea3; color:#208ea3; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-eye"></i> تفاصيل
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="fin-empty"><i class="fa-solid fa-house"></i><p>لا توجد سكنات وظيفية مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     MODALS FOR CREATION
══════════════════════════════════════════════════════════ --}}

{{-- Modal: Equipment --}}
<div class="pat-modal-overlay" id="modalEquipment">
    <div class="pat-modal" style="overflow: hidden;">
        <div class="pat-modal-header-gradient">
            <div class="pat-modal-header-icon"><i class="fa-solid fa-laptop-code"></i></div>
            <div class="pat-modal-header-text">
                <div class="pat-modal-header-title">إضافة تجهيز تقني وبيداغوجي</div>
                <div class="pat-modal-header-sub">إضافة جهاز أو عتاد بيداغوجي جديد للمؤسسة</div>
            </div>
            <button type="button" class="pat-modal-header-close" onclick="closeModal('modalEquipment')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="equipmentForm" onsubmit="submitEquipment(event)">
            <div class="pat-modal-body">
                <input type="hidden" name="etablissement_id" value="{{ $etabId }}">
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">تسمية الجهاز *</label>
                        <input type="text" name="designation" required placeholder="مثال: جهاز عرض ضوئي">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">Désignation en Français</label>
                        <input type="text" name="designation_fr">
                    </div>
                </div>
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">الرمز الكودي (Code) *</label>
                        <input type="text" name="code" required placeholder="EQ-2026-X">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">تاريخ الاستلام *</label>
                        <input type="date" name="date_reception" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">تاريخ بدء الاستغلال</label>
                        <input type="date" name="date_exploitation" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">حالة التجهيز *</label>
                        <select name="etat_id" required>
                            @foreach($etatTypes as $et)
                                <option value="{{ $et->IDIDequipement_etatType }}">{{ $et->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pat-form-group">
                    <label class="pat-form-label">ملاحظات وحالة الجهاز</label>
                    <textarea name="description" rows="3" placeholder="مستعمل، في حالة جيدة..."></textarea>
                </div>
            </div>
            <div class="pat-modal-footer">
                <button type="button" class="btn-pat-print" onclick="closeModal('modalEquipment')">إلغاء</button>
                <button type="submit" class="btn-pat-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Vehicle --}}
<div class="pat-modal-overlay" id="modalVehicule">
    <div class="pat-modal" style="overflow: hidden;">
        <div class="pat-modal-header-gradient">
            <div class="pat-modal-header-icon"><i class="fa-solid fa-car-side"></i></div>
            <div class="pat-modal-header-text">
                <div class="pat-modal-header-title">إضافة مركبة إلى الحظيرة</div>
                <div class="pat-modal-header-sub">إدخال مركبة جديدة لحساب حظيرة السيارات</div>
            </div>
            <button type="button" class="pat-modal-header-close" onclick="closeModal('modalVehicule')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="vehiculeForm" onsubmit="submitVehicule(event)">
            <div class="pat-modal-body">
                <input type="hidden" name="etab_id" value="{{ $etabId }}">
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">رقم اللوحة الترقيمية *</label>
                        <input type="text" name="immatriculation" required placeholder="مثال: 00192-116-20">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">الماركة والتصميم التجاري *</label>
                        <input type="text" name="marque" required placeholder="مثال: Toyota Hilux">
                    </div>
                </div>
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">سنة الصنع</label>
                        <input type="number" name="annee" value="{{ date('Y') }}">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">رقم الشاسيه (Châssis)</label>
                        <input type="text" name="chassis">
                    </div>
                </div>
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">عدد المقاعد</label>
                        <input type="number" name="places" value="5">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">اللوحة السابقة (إن وُجدت)</label>
                        <input type="text" name="immatriculation_prec">
                    </div>
                </div>
            </div>
            <div class="pat-modal-footer">
                <button type="button" class="btn-pat-print" onclick="closeModal('modalVehicule')">إلغاء</button>
                <button type="submit" class="btn-pat-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Local --}}
<div class="pat-modal-overlay" id="modalLocal">
    <div class="pat-modal" style="overflow: hidden;">
        <div class="pat-modal-header-gradient">
            <div class="pat-modal-header-icon"><i class="fa-solid fa-school"></i></div>
            <div class="pat-modal-header-text">
                <div class="pat-modal-header-title">إضافة مقر بيداغوجي</div>
                <div class="pat-modal-header-sub">إضافة قاعة، مخبر أو ورشة تطبيقية جديدة للمؤسسة</div>
            </div>
            <button type="button" class="pat-modal-header-close" onclick="closeModal('modalLocal')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="localForm" onsubmit="submitLocal(event)">
            <div class="pat-modal-body">
                <input type="hidden" name="etab_id" value="{{ $etabId }}">
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">اسم المقر (قاعة/مخبر/ورشة) *</label>
                        <input type="text" name="nom" required placeholder="مثال: ورشة الكهرباء 01">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">Nom Complet en Français</label>
                        <input type="text" name="nom_fr" placeholder="Nom en français">
                    </div>
                </div>
                
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">نوع المقر *</label>
                        <select name="type_id" required>
                            @foreach($localTypes as $lt)
                                <option value="{{ $lt->IDTypeLocal }}">{{ $lt->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">الحالة الراهنة للمقر *</label>
                        <select name="etat_id" required>
                            @foreach($localStates as $ls)
                                <option value="{{ $ls->EtatActual }}">{{ $ls->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">الطابق *</label>
                        <input type="number" name="etage" value="0" required placeholder="0 للأرضي، 1 للأول...">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">طاقة الاستيعاب القصوى (الأفراد) *</label>
                        <input type="number" name="places" value="30" required>
                    </div>
                </div>

                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">عدد الطاولات</label>
                        <input type="number" name="tables" value="15">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">عدد الكراسي</label>
                        <input type="number" name="chaises" value="30">
                    </div>
                </div>
                
                <div class="pat-form-group">
                    <label class="pat-form-label">التجهيزات المتوفرة بالمقر</label>
                    <div class="pat-pill-checkbox-group">
                        <label class="pat-pill-checkbox">
                            <input type="checkbox" name="datashow" value="1">
                            <span><i class="fa-solid fa-video" style="color: #208ea3; margin-left: 4px;"></i> جهاز عرض (Datashow)</span>
                        </label>
                        <label class="pat-pill-checkbox">
                            <input type="checkbox" name="tableaffichage" value="1">
                            <span><i class="fa-solid fa-chalkboard" style="color: #208ea3; margin-left: 4px;"></i> سبورة عرض</span>
                        </label>
                        <label class="pat-pill-checkbox">
                            <input type="checkbox" name="climatiseur" value="1">
                            <span><i class="fa-solid fa-wind" style="color: #208ea3; margin-left: 4px;"></i> مكيف هواء</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="pat-modal-footer">
                <button type="button" class="btn-pat-print" onclick="closeModal('modalLocal')">إلغاء</button>
                <button type="submit" class="btn-pat-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Housing --}}
<div class="pat-modal-overlay" id="modalLogement">
    <div class="pat-modal" style="overflow: hidden;">
        <div class="pat-modal-header-gradient">
            <div class="pat-modal-header-icon"><i class="fa-solid fa-house-chimney-user"></i></div>
            <div class="pat-modal-header-text">
                <div class="pat-modal-header-title">إضافة سكن وظيفي</div>
                <div class="pat-modal-header-sub">إدراج سكن وظيفي جديد شاغل أو شاغر تابع للمؤسسة</div>
            </div>
            <button type="button" class="pat-modal-header-close" onclick="closeModal('modalLogement')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="logementForm" onsubmit="submitLogement(event)">
            <div class="pat-modal-body">
                <input type="hidden" name="etab_id" value="{{ $etabId }}">
                
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">نوع السكن (عدد الغرف) *</label>
                        <select name="type_id" required>
                            @foreach($logementTypes as $lt)
                                <option value="{{ $lt->IDLogementType }}">{{ $lt->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">طبيعة السكن *</label>
                        <select name="nature_id" required>
                            @foreach($logementNatures as $ln)
                                <option value="{{ $ln->IDLogement_Nature }}">{{ $ln->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">حالة شغل السكن *</label>
                        <select name="occup_id" required>
                            @foreach($logementOccupations as $lo)
                                <option value="{{ $lo->Occupe }}">{{ $lo->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">سبب شغل السكن *</label>
                        <select name="cause_id" required>
                            @foreach($logementCauses as $lc)
                                <option value="{{ $lc->IDLogement_CauseOccup }}">{{ $lc->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">الوضعية القانونية لشغل السكن *</label>
                        <select name="juridique_id" required>
                            @foreach($logementJuridiques as $lj)
                                <option value="{{ $lj->SituationJur }}">{{ $lj->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">الطابق *</label>
                        <input type="number" name="etage" value="0" required>
                    </div>
                </div>

                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">اسم ولقب المستفيد (شاغل السكن)</label>
                        <input type="text" name="occupant_nom" placeholder="يترك فارغاً إذا كان شاغراً">
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">مساحة السكن العقاري (م²)</label>
                        <input type="number" name="surface" value="80" step="0.1">
                    </div>
                </div>

                <div class="pat-form-row">
                    <div class="pat-form-group">
                        <label class="pat-form-label">التموضع الوظيفي</label>
                        <select name="interne_externe">
                            <option value="1">داخلي للمحيط البيداغوجي</option>
                            <option value="2">خارجي</option>
                        </select>
                    </div>
                    <div class="pat-form-group">
                        <label class="pat-form-label">حالة صلاحية السكن</label>
                        <select name="etat">
                            <option value="1">صالح للسكن (جيد)</option>
                            <option value="2">يحتاج لترميم</option>
                            <option value="3">غير صالح للسكن</option>
                        </select>
                    </div>
                </div>

                <div class="pat-form-group">
                    <label class="pat-form-label">العنوان الوطني الكامل *</label>
                    <input type="text" name="adresse" required placeholder="العنوان بالتفصيل...">
                </div>

                <div class="pat-form-group">
                    <label class="pat-form-label">الشبكات المتوفرة بالسكن</label>
                    <div class="pat-pill-checkbox-group">
                        <label class="pat-pill-checkbox">
                            <input type="checkbox" name="eau" value="1">
                            <span><i class="fa-solid fa-droplet" style="color: #208ea3; margin-left: 4px;"></i> تزويد بالماء</span>
                        </label>
                        <label class="pat-pill-checkbox">
                            <input type="checkbox" name="electricite" value="1">
                            <span><i class="fa-solid fa-bolt" style="color: #208ea3; margin-left: 4px;"></i> تزويد بالكهرباء</span>
                        </label>
                        <label class="pat-pill-checkbox">
                            <input type="checkbox" name="gaz" value="1">
                            <span><i class="fa-solid fa-fire" style="color: #208ea3; margin-left: 4px;"></i> تزويد بالغاز</span>
                        </label>
                        <label class="pat-pill-checkbox">
                            <input type="checkbox" name="structuree" value="1">
                            <span><i class="fa-solid fa-screwdriver-wrench" style="color: #208ea3; margin-left: 4px;"></i> سكن مهيأ / مرمم</span>
                        </label>
                    </div>
                </div>

                <div class="pat-form-group">
                    <label class="pat-form-label">تفاصيل / ملاحظات</label>
                    <textarea name="observation" rows="2" placeholder="أي ملاحظات إضافية حول السكن..."></textarea>
                </div>
            </div>
            <div class="pat-modal-footer">
                <button type="button" class="btn-pat-print" onclick="closeModal('modalLogement')">إلغاء</button>
                <button type="submit" class="btn-pat-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Propriete --}}
<div class="pat-modal-overlay" id="modalPropriete">
    <div class="pat-modal" style="overflow: hidden;">
        <div class="pat-modal-header-gradient">
            <div class="pat-modal-header-icon"><i class="fa-solid fa-map-location-dot"></i></div>
            <div class="pat-modal-header-text">
                <div class="pat-modal-header-title">إضافة فضاء/ملك عقاري</div>
                <div class="pat-modal-header-sub">إضافة عقار، فضاء خارجي أو وعاء عقاري جديد للمؤسسة</div>
            </div>
            <button type="button" class="pat-modal-header-close" onclick="closeModal('modalPropriete')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="proprieteForm" onsubmit="submitPropriete(event)">
            <div class="pat-modal-body">
                <div class="pat-form-group">
                    <label class="pat-form-label">اسم الفضاء العقاري (العربية) *</label>
                    <input type="text" name="nom" required placeholder="مثال: قطعة أرضية للورشة المفتوحة">
                </div>
                <div class="pat-form-group">
                    <label class="pat-form-label">Nom (Français)</label>
                    <input type="text" name="nom_fr">
                </div>
            </div>
            <div class="pat-modal-footer">
                <button type="button" class="btn-pat-print" onclick="closeModal('modalPropriete')">إلغاء</button>
                <button type="submit" class="btn-pat-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Details --}}
<div class="pat-modal-overlay" id="modalDetails">
    <div class="pat-modal" style="max-width:680px; border-radius:20px;">
        <div class="det-header-banner" id="detHeaderBanner">
            <div class="det-header-icon" id="detHeaderIcon"><i class="fa-solid fa-info"></i></div>
            <div class="det-header-text">
                <div class="det-header-title" id="detailsModalTitle">تفاصيل العنصر</div>
                <div class="det-header-sub" id="detHeaderSub">بيانات تفصيلية</div>
            </div>
            <div class="det-etab-badge" id="detEtabBadge" style="display:none;">
                <span id="detEtabName">—</span>
                <small id="detWilayaName">—</small>
            </div>
            <button class="det-close-btn" onclick="closeModal('modalDetails')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="det-body">
            <div id="detailsModalFields" style="text-align: right; direction: rtl; font-family: 'Cairo', sans-serif;">
                <!-- Filled dynamically by JS -->
            </div>
        </div>
        <div class="pat-modal-footer" style="padding-top:0.75rem;">
            <button type="button" class="btn-pat-print" onclick="closeModal('modalDetails')"><i class="fa-solid fa-xmark"></i> إغلاق</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const BASE = '{{ asset("") }}';
const CSRF = '{{ csrf_token() }}';
const MEDIA_ACTIONS_ENABLED = {{ \App\Helpers\SovereignLicensingHelper::getSetting('patrimoine_media_actions_enabled', '1') === '1' ? 'true' : 'false' }};

function showPatDetails(title, sections, opts = {}) {
    document.getElementById('detailsModalTitle').textContent = title;
    document.getElementById('detHeaderSub').textContent = opts.sub || 'بيانات تفصيلية';
    
    // Header icon
    const iconEl = document.getElementById('detHeaderIcon');
    iconEl.innerHTML = `<i class="fa-solid fa-${opts.icon || 'info'}"></i>`;
    
    // Establishment badge
    const badge = document.getElementById('detEtabBadge');
    const etabEl = document.getElementById('detEtabName');
    const wilayaEl = document.getElementById('detWilayaName');
    if (opts.etab) {
        etabEl.textContent = opts.etab;
        wilayaEl.textContent = opts.wilaya ? '📍 ' + opts.wilaya : '';
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }

    const container = document.getElementById('detailsModalFields');
    container.innerHTML = '';
    
    // Photo box (if any)
    if (opts.type && opts.id && MEDIA_ACTIONS_ENABLED) {
        const mediaBox = document.createElement('div');
        mediaBox.className = 'text-center mb-4';
        
        let imgHtml = '';
        let btnHtml = '';
        
        if (opts.photo) {
            let cleanPath = opts.photo.replace(/^\//, '');
            let photoUrl = BASE.endsWith('/') ? `${BASE}${cleanPath}` : `${BASE}/${cleanPath}`;
            imgHtml = `
                <div class="position-relative d-inline-block shadow-sm rounded-3 overflow-hidden border" style="max-width: 100%; width: 280px; height: 185px; background: #f8fafc;">
                    <img src="${photoUrl}" id="detailPhotoImg" alt="Photo" onerror="if(!this.src.includes('/public/uploads/')){ this.src = this.src.replace('/uploads/', '/public/uploads/'); }" style="width: 100%; height: 100%; object-fit: cover;">
                    <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-60 text-white text-center py-1 small" style="font-family: 'Cairo'; font-size: 0.72rem;">
                        <i class="fa-solid fa-image me-1"></i> صورة مرافقة معتمدة
                    </div>
                </div>
            `;
            btnHtml = `
                <div class="mt-2 d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="triggerPhotoUpload()" style="font-size:0.75rem; font-family:'Cairo';">
                        <i class="fa-solid fa-pen-to-square me-1"></i> تغيير الصورة
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deletePhoto('${opts.type}', ${opts.id})" style="font-size:0.75rem; font-family:'Cairo';">
                        <i class="fa-solid fa-trash-can me-1"></i> حذف الصورة
                    </button>
                </div>
            `;
        } else {
            imgHtml = `
                <div class="d-inline-flex flex-column align-items-center justify-content-center shadow-sm rounded-3 border p-4" style="width: 280px; height: 140px; background: var(--bg-surface-elevated); border-style: dashed !important;">
                    <i class="fa-solid fa-image-portrait fs-1 text-muted mb-2"></i>
                    <span class="text-muted small" style="font-family:'Cairo';">لا توجد صورة مرافقة حالياً</span>
                </div>
            `;
            btnHtml = `
                <div class="mt-2 text-center">
                    <button class="btn btn-sm btn-primary" onclick="triggerPhotoUpload()" style="font-size:0.75rem; font-family:'Cairo';">
                        <i class="fa-solid fa-plus me-1"></i> إضافة صورة
                    </button>
                </div>
            `;
        }
        
        mediaBox.innerHTML = `
            ${imgHtml}
            ${btnHtml}
            <input type="file" id="detailPhotoInput" accept="image/*" style="display:none;" onchange="handlePhotoUpload('${opts.type}', ${opts.id})">
        `;
        container.appendChild(mediaBox);
    } else if (opts.photo) {
        const photoBox = document.createElement('div');
        photoBox.className = 'text-center mb-4';
        
        let cleanPath = opts.photo.replace(/^\//, '');
        let photoUrl = BASE.endsWith('/') ? `${BASE}${cleanPath}` : `${BASE}/${cleanPath}`;
        
        photoBox.innerHTML = `
            <div class="position-relative d-inline-block shadow-sm rounded-3 overflow-hidden border" style="max-width: 100%; width: 280px; height: 185px; background: #f8fafc;">
                <img src="${photoUrl}" alt="Photo" onerror="if(!this.src.includes('/public/uploads/')){ this.src = this.src.replace('/uploads/', '/public/uploads/'); }" style="width: 100%; height: 100%; object-fit: cover;">
                <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-60 text-white text-center py-1 small" style="font-family: 'Cairo'; font-size: 0.72rem;">
                    <i class="fa-solid fa-image me-1"></i> صورة مرافقة معتمدة
                </div>
            </div>
        `;
        container.appendChild(photoBox);
    }
    
    // Observation box (if any)
    if (opts.observation) {
        const obsBox = document.createElement('div');
        obsBox.className = 'det-obs-box';
        obsBox.innerHTML = `<i class="fa-solid fa-note-sticky det-obs-icon"></i><span>${opts.observation}</span>`;
        container.appendChild(obsBox);
    }
    
    sections.forEach(section => {
        if (section.title) {
            const t = document.createElement('div');
            t.className = 'det-section-title';
            t.innerHTML = `<i class="fa-solid fa-${section.icon || 'circle-dot'}"></i> ${section.title}`;
            container.appendChild(t);
        }
        const grid = document.createElement('div');
        grid.className = 'det-grid' + (section.single ? ' single' : '');
        section.fields.forEach(f => {
            if (f.value === null || f.value === undefined || f.value === '' || f.value === '—') {
                if (!f.showEmpty) return;
            }
            const div = document.createElement('div');
            div.className = 'det-field';
            div.innerHTML = `
                <span class="det-field-label">${f.label}</span>
                <span class="det-field-value">${f.value || '—'}</span>
            `;
            grid.appendChild(div);
        });
        if (grid.children.length > 0) container.appendChild(grid);
    });
    
    openModal('modalDetails');
}

function viewEquipmentDetails(eq) {
    showPatDetails('تفاصيل التجهيز البيداغوجي والتقني', [
        {
            title: 'معلومات التجهيز',
            icon: 'desktop',
            fields: [
                { label: 'تسمية التجهيز (عربي)', value: eq.designation, showEmpty: true },
                { label: 'Désignation (Français)', value: eq.designation_fr },
                { label: 'الرمز / الكود', value: eq.code },
                { label: 'حالة التحقق', value: eq.is_validated ? '✅ معتمد' : '⏳ قيد المراجعة', showEmpty: true },
            ]
        },
        {
            title: 'التواريخ',
            icon: 'calendar-days',
            fields: [
                { label: 'تاريخ الاستلام', value: eq.date_reception },
                { label: 'تاريخ بدء الاستغلال', value: eq.date_exploitation },
            ]
        }
    ], {
        icon: 'desktop',
        sub: 'التجهيزات البيداغوجية والتقنية',
        etab: eq.etablissement_nom || null,
        wilaya: eq.wilaya_nom || null,
        observation: eq.description || null,
        photo: eq.photo || null,
        type: 'equipment',
        id: eq.id
    });
}

function viewVehiculeDetails(v) {
    showPatDetails('تفاصيل المركبة', [
        {
            title: 'بيانات التسجيل',
            icon: 'car',
            fields: [
                { label: 'رقم التسجيل (اللوحة)', value: v.immatriculation, showEmpty: true },
                { label: 'اللوحة السابقة', value: v.immatriculation_prec },
                { label: 'الحالة', value: v.validation ? '✅ نشطة' : '⏳ تحت المراجعة', showEmpty: true },
            ]
        },
        {
            title: 'مواصفات المركبة',
            icon: 'gear',
            fields: [
                { label: 'الماركة / الموديل', value: v.marque, showEmpty: true },
                { label: 'سنة الصنع', value: v.annee, showEmpty: true },
                { label: 'رقم الشاسيه', value: v.chassis },
                { label: 'عدد المقاعد', value: v.places ? v.places + ' مقاعد' : null, showEmpty: true },
            ]
        }
    ], {
        icon: 'car',
        sub: 'حظيرة المركبات',
        etab: v.etablissement_nom || null,
        wilaya: v.wilaya_nom || null,
        photo: v.photo || null,
        type: 'vehicule',
        id: v.id
    });
}

function viewLocalDetails(l) {
    showPatDetails('تفاصيل المقر البيداغوجي', [
        {
            title: 'بيانات المقر',
            icon: 'door-open',
            fields: [
                { label: 'اسم المقر (عربي)', value: l.nom, showEmpty: true },
                { label: 'Nom (Français)', value: l.nom_fr },
                { label: 'نوع المقر', value: l.type_nom, showEmpty: true },
                { label: 'الحالة الراهنة', value: l.etat_nom, showEmpty: true },
            ]
        },
        {
            title: 'الطاقة والمقاعد',
            icon: 'chart-simple',
            fields: [
                { label: 'الطابق', value: l.etage == 0 ? 'الأرضي' : 'الطابق ' + l.etage, showEmpty: true },
                { label: 'الطاقة الاستيعابية', value: l.places ? l.places + ' مقعد' : null, showEmpty: true },
                { label: 'عدد الطاولات', value: l.tables, showEmpty: true },
                { label: 'عدد الكراسي', value: l.chaises, showEmpty: true },
            ]
        },
        {
            title: 'التجهيزات المتوفرة',
            icon: 'plug',
            fields: [
                { label: 'جهاز عرض (Datashow)', value: l.datashow ? '✅ متوفر' : '❌ غير متوفر', showEmpty: true },
                { label: 'سبورة عرض', value: l.tableaffichage ? '✅ متوفر' : '❌ غير متوفر', showEmpty: true },
                { label: 'مكيف هواء', value: l.climatiseur ? '✅ متوفر' : '❌ غير متوفر', showEmpty: true },
            ]
        }
    ], {
        icon: 'door-open',
        sub: 'المقرات والقاعات البيداغوجية',
        etab: l.etablissement_nom || null,
        wilaya: l.wilaya_nom || null,
        observation: l.observation || null
    });
}

function viewProprieteDetails(pr) {
    showPatDetails('تفاصيل الفضاء/الملك العقاري', [
        {
            title: 'بيانات الممتلك',
            icon: 'map',
            fields: [
                { label: 'التسمية (عربي)', value: pr.nom, showEmpty: true },
                { label: 'Désignation (Français)', value: pr.nom_fr },
                { label: 'الحالة القانونية', value: 'ملك ملكية تامة', showEmpty: true },
            ]
        }
    ], {
        icon: 'map',
        sub: 'الممتلكات والفضاءات العقارية',
        etab: pr.etablissement_nom || null,
        wilaya: pr.wilaya_nom || null
    });
}

function viewLogementDetails(log) {
    showPatDetails('تفاصيل السكن الوظيفي', [
        {
            title: 'بيانات السكن',
            icon: 'house',
            fields: [
                { label: 'العنوان', value: log.adresse, showEmpty: true },
                { label: 'التموضع', value: log.interne_externe == 1 ? 'داخلي للمؤسسة' : 'خارجي', showEmpty: true },
                { label: 'نوع السكن', value: log.type_nom, showEmpty: true },
                { label: 'طبيعة السكن', value: log.nature_nom, showEmpty: true },
            ]
        },
        {
            title: 'المساحة والطابق',
            icon: 'ruler-combined',
            fields: [
                { label: 'المساحة', value: log.surface ? log.surface + ' م²' : null, showEmpty: true },
                { label: 'الطابق', value: log.etage == 0 ? 'الأرضي' : 'الطابق ' + log.etage, showEmpty: true },
                { label: 'شاغل السكن', value: log.occupant_nom || 'شاغر', showEmpty: true },
            ]
        },
        {
            title: 'الشبكات والتهيئة',
            icon: 'network-wired',
            fields: [
                { label: 'تزويد بالماء', value: log.eau ? '✅ نعم' : '❌ لا', showEmpty: true },
                { label: 'تزويد بالكهرباء', value: log.electricite ? '✅ نعم' : '❌ لا', showEmpty: true },
                { label: 'تزويد بالغاز', value: log.gaz ? '✅ نعم' : '❌ لا', showEmpty: true },
                { label: 'حالة التهيئة', value: log.structuree ? '✅ مهيأ' : '❌ غير مهيأ', showEmpty: true },
            ]
        },
        {
            title: 'الوضعية القانونية',
            icon: 'scale-balanced',
            fields: [
                { label: 'حالة الاحتلال', value: log.occupe_nom },
                { label: 'الوضعية القانونية', value: log.juridique_nom },
            ]
        }
    ], {
        icon: 'house',
        sub: 'السكنات الوظيفية',
        etab: log.etablissement_nom || null,
        wilaya: log.wilaya_nom || null,
        observation: log.observation || null,
        photo: log.photo || null,
        type: 'logement',
        id: log.id
    });
}

// ─── Tab switching ──────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.pat-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.pat-panel').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
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
    const t = document.getElementById('patToast');
    t.textContent = msg;
    t.className = 'pat-toast ' + type;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

// ─── Modal helpers ──────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.pat-modal-overlay').forEach(m => {
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

function reloadTab(tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.location.href = url.toString();
}

// ─── SUBMISSIONS ────────────────────────────────────────
async function submitEquipment(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/patrimoine/equipment/store', data);
    if (res.success) { showToast(res.message); closeModal('modalEquipment'); setTimeout(()=>reloadTab('equipements'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitVehicule(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/patrimoine/vehicule/store', data);
    if (res.success) { showToast(res.message); closeModal('modalVehicule'); setTimeout(()=>reloadTab('vehicules'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitLocal(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/patrimoine/local/store', data);
    if (res.success) { showToast(res.message); closeModal('modalLocal'); setTimeout(()=>reloadTab('locaux'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitLogement(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/patrimoine/logement/store', data);
    if (res.success) { showToast(res.message); closeModal('modalLogement'); setTimeout(()=>reloadTab('logements'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitPropriete(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/patrimoine/propriete/store', data);
    if (res.success) { showToast(res.message); closeModal('modalPropriete'); setTimeout(()=>reloadTab('proprietes'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}
// ─── Wilaya → Etablissement cascade ───────────────────────
(function() {
    const wilyaSelect = document.getElementById('filterWilaya');
    const etabSelect  = document.getElementById('filterEtab');
    if (!wilyaSelect || !etabSelect) return;
    const allOptions = Array.from(etabSelect.options).map(o => ({
        value: o.value, text: o.text, dfep: o.dataset.dfep
    }));
    function cascadeEtabs(dfepId) {
        const sel = etabSelect.value;
        etabSelect.innerHTML = '<option value="" style="color:#333;">— كل المؤسسات —</option>';
        (dfepId ? allOptions.filter(o => o.dfep == dfepId) : allOptions).forEach(o => {
            if (!o.value) return;
            const opt = document.createElement('option');
            opt.value = o.value; opt.textContent = o.text;
            opt.dataset.dfep = o.dfep; opt.style.color = '#333';
            if (o.value === sel) opt.selected = true;
            etabSelect.appendChild(opt);
        });
    }
    if (wilyaSelect.value) cascadeEtabs(wilyaSelect.value);
    wilyaSelect.addEventListener('change', function() {
        cascadeEtabs(this.value);
        etabSelect.value = '';
        document.getElementById('filterForm').submit();
    });
})();

function triggerPhotoUpload() {
    document.getElementById('detailPhotoInput').click();
}

function handlePhotoUpload(type, id) {
    const input = document.getElementById('detailPhotoInput');
    if (!input.files || input.files.length === 0) return;
    
    const file = input.files[0];
    const formData = new FormData();
    formData.append('type', type);
    formData.append('id', id);
    formData.append('action', 'upload');
    formData.append('photo', file);
    formData.append('_token', CSRF);
    
    Swal.fire({
        title: 'جاري رفع وحفظ الصورة...',
        html: 'يرجى الانتظار...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`${BASE.replace(/\/$/, '')}/dashboard/patrimoine/media/update`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم بنجاح',
                text: data.message,
                confirmButtonText: 'حسناً'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: data.error || 'فشل تحديث الصورة',
                confirmButtonText: 'حسناً'
            });
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'خطأ في الاتصال',
            text: 'تعذر الاتصال بالخادم لرفع الصورة.',
            confirmButtonText: 'حسناً'
        });
    });
}

function deletePhoto(type, id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "هل تريد حذف الصورة المرفقة؟",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذفها',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('type', type);
            formData.append('id', id);
            formData.append('action', 'delete');
            formData.append('_token', CSRF);
            
            Swal.fire({
                title: 'جاري الحذف...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`${BASE.replace(/\/$/, '')}/dashboard/patrimoine/media/update`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: data.message,
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: data.error || 'فشل حذف الصورة',
                        confirmButtonText: 'حسناً'
                    });
                }
            })
            .catch(err => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في الاتصال',
                    text: 'تعذر الاتصال بالخادم لإتمام العملية.',
                    confirmButtonText: 'حسناً'
                });
            });
        }
    });
}
</script>
@endsection
