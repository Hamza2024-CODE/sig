@extends('layouts.main')

@section('title', 'الموارد البشرية والإدارية — SGFEP')

@section('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   HR MODULE — Premium UI Design System
   ═══════════════════════════════════════════════════════════════ */
.rh-hero {
    background: linear-gradient(135deg, #181c3f 0%, #2f3475 50%, #464eb5 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(70,78,181,0.3);
}
.rh-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events: none;
}
.rh-hero-title {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.3rem;
}
.rh-hero-sub {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.7);
    font-family: 'Cairo', sans-serif;
}

/* Stat Cards */
.rh-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.rh-stat-card {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--border, #e8edf5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}
.rh-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
.rh-stat-card::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--stat-color, #464eb5), transparent);
    border-radius: 0 16px 16px 0;
}
.rh-stat-label { font-size: 0.75rem; color: var(--tx-3, #8898b0); font-weight: 700; font-family:'Cairo',sans-serif; margin-bottom: 0.4rem; }
.rh-stat-value { font-size: 1.8rem; font-weight: 900; color: var(--tx-1, #181c3f); font-family:'Cairo',sans-serif; line-height: 1; }
.rh-stat-icon  { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-size: 2.2rem; opacity: 0.06; }

/* Tab Pills */
.rh-tabs {
    display: flex;
    gap: 0.5rem;
    background: var(--bg-surface, #f4f6fb);
    padding: 0.4rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.rh-tab {
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
.rh-tab:hover { background: rgba(70,78,181,0.08); color: #464eb5; }
.rh-tab.active {
    background: linear-gradient(135deg, #464eb5, #2f3475);
    color: #fff !important;
    box-shadow: 0 4px 15px rgba(70,78,181,0.35);
}
.rh-tab-count {
    background: rgba(255,255,255,0.25);
    border-radius: 6px;
    padding: 1px 6px;
    font-size: 0.72rem;
}

/* Panel */
.rh-panel { display: none; animation: fadeUp 0.3s ease; }
.rh-panel.active { display: block; }
@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* Toolbar */
.rh-toolbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.rh-toolbar .rh-search {
    flex: 1; min-width: 220px;
    position: relative;
}
.rh-toolbar .rh-search input {
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
.rh-toolbar .rh-search input:focus { outline: none; border-color: #464eb5; box-shadow: 0 0 0 3px rgba(70,78,181,0.1); }
.rh-toolbar .rh-search i { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--tx-3); font-size: 0.85rem; }

/* Buttons */
.btn-rh-add {
    background: linear-gradient(135deg, #464eb5, #2f3475);
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
    box-shadow: 0 4px 12px rgba(70,78,181,0.25);
    white-space: nowrap;
}
.btn-rh-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(70,78,181,0.35); }
.btn-rh-print {
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
.btn-rh-print:hover { border-color: #464eb5; color: #464eb5; background: rgba(70,78,181,0.06); }

/* Table */
.rh-table-wrap {
    background: var(--bg-surface-elevated);
    border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.rh-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Cairo', sans-serif;
    font-size: 0.83rem;
}
.rh-table thead tr {
    background: linear-gradient(135deg, #f3f0ff, #eeeafc);
    border-bottom: 2px solid #e1dcfb;
}
.rh-table thead th {
    padding: 0.85rem 1rem;
    text-align: right;
    font-weight: 800;
    color: #382d6b;
    font-size: 0.78rem;
    white-space: nowrap;
}
.rh-table tbody tr {
    border-bottom: 1px solid var(--border, #eef1f7);
    transition: background 0.15s;
}
.rh-table tbody tr:hover { background: rgba(70,78,181,0.03); }
.rh-table tbody td {
    padding: 0.75rem 1rem;
    color: var(--tx-1);
    vertical-align: middle;
}

/* Modals */
.rh-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(10,20,40,0.6);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.rh-modal-overlay.open { display: flex; }
.rh-modal {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 20px;
    width: 100%;
    max-width: 580px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 30px 80px rgba(0,0,0,0.25);
    animation: modalIn 0.25s ease;
}
@keyframes modalIn { from { opacity:0; transform:scale(0.94) translateY(-10px); } to { opacity:1; transform:scale(1) translateY(0); } }
.rh-modal-header {
    padding: 1.5rem 1.75rem 1rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.rh-modal-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--tx-1);
    font-family: 'Cairo', sans-serif;
}
.rh-modal-close {
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
.rh-modal-close:hover { background: #dc3545; color: #fff; }
.rh-modal-body   { padding: 1.5rem 1.75rem; }
.rh-modal-footer { padding: 1rem 1.75rem 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end; border-top: 1px solid var(--border); }

/* Form Fields */
.rh-form-group { margin-bottom: 1rem; }
.rh-form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--tx-2);
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.4rem;
}
.rh-form-group input,
.rh-form-group select,
.rh-form-group textarea {
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
.rh-form-group input:focus,
.rh-form-group select:focus,
.rh-form-group textarea:focus {
    outline: none;
    border-color: #464eb5;
    box-shadow: 0 0 0 3px rgba(70,78,181,0.12);
}
.rh-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }

/* Toast */
.rh-toast {
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
.rh-toast.success { background: #1cb05f; color: #fff; }
.rh-toast.error   { background: #dc3545; color: #fff; }

/* Dark Mode Compat */
[data-theme="dark"] .rh-stat-card { background: var(--bg-surface-elevated, #1e2d4a); border-color: rgba(255,255,255,0.07); }
[data-theme="dark"] .rh-table-wrap { background: var(--bg-surface-elevated, #1e2d4a); }
[data-theme="dark"] .rh-modal { background: #1e2d4a; }
[data-theme="dark"] .rh-table thead tr { background: rgba(70,78,181,0.12); }
</style>
@endsection

@section('content')
<div class="rh-toast" id="rhToast"></div>

{{-- Hero header --}}
<div class="rh-hero">
    <div class="row align-items-center">
        <div class="col">
            <div class="rh-hero-title">
                <i class="fa-solid fa-users-gear me-2"></i>
                إدارة الموارد البشرية والنشاطات
            </div>
            <div class="rh-hero-sub">
                تسيير شؤون الموظفين والأساتذة · متابعة عمليات التوظيف والترقيات والدرجات · دورات الرسكلة والتكوين · رزنامة الأنشطة
                &nbsp;|&nbsp; {{ $user['nom_complet'] ?? 'المستخدم' }}
            </div>
        </div>
        <div class="col-auto text-end">
            @if(in_array($roleCode, ['admin', 'dfep', 'central']))
            <form method="GET" action="" class="d-inline-flex align-items-center gap-2 flex-wrap" id="filterForm">
                <input type="hidden" name="tab" value="{{ $tab }}">

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
            <button class="btn-rh-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> طباعة
            </button>
            @endif
        </div>
    </div>
</div>

{{-- Stats cards --}}
<div class="rh-stat-grid">
    <div class="rh-stat-card" style="--stat-color:#464eb5">
        <div class="rh-stat-label">إجمالي الموظفين والأساتذة</div>
        <div class="rh-stat-value">{{ number_format($stats['total_staff']) }}</div>
        <i class="fa-solid fa-users rh-stat-icon"></i>
    </div>
    <div class="rh-stat-card" style="--stat-color:#1a6bcc">
        <div class="rh-stat-label">عمليات التوظيف والترقية</div>
        <div class="rh-stat-value">{{ number_format($stats['total_recrutements']) }}</div>
        <i class="fa-solid fa-user-plus rh-stat-icon"></i>
    </div>
    <div class="rh-stat-card" style="--stat-color:#1cb05f">
        <div class="rh-stat-label">دورات التكوين المستمر</div>
        <div class="rh-stat-value">{{ $stats['total_formations'] }}</div>
        <i class="fa-solid fa-chalkboard-user rh-stat-icon"></i>
    </div>
    <div class="rh-stat-card" style="--stat-color:#e07b00">
        <div class="rh-stat-label">النشاطات المبرمجة</div>
        <div class="rh-stat-value">{{ $stats['total_activites'] }}</div>
        <i class="fa-solid fa-calendar-days rh-stat-icon"></i>
    </div>
    <div class="rh-stat-card" style="--stat-color:#7c3aed">
        <div class="rh-stat-label">إجمالي دليل المكونين</div>
        <div class="rh-stat-value">{{ number_format($stats['total_competances']) }}</div>
        <i class="fa-solid fa-id-card rh-stat-icon"></i>
    </div>
</div>

{{-- Tabs --}}
<div class="rh-tabs">
    <button class="rh-tab {{ $tab==='personnel' ? 'active' : '' }}" onclick="switchTab('personnel')">
        <i class="fa-solid fa-users"></i> متابعة الموارد البشرية
        <span class="rh-tab-count">{{ number_format($stats['total_staff']) }}</span>
    </button>
    <button class="rh-tab {{ $tab==='recrutement' ? 'active' : '' }}" onclick="switchTab('recrutement')">
        <i class="fa-solid fa-user-plus"></i> التوظيف والترقية
    </button>
    <button class="rh-tab {{ $tab==='formation' ? 'active' : '' }}" onclick="switchTab('formation')">
        <i class="fa-solid fa-graduation-cap"></i> التكوين وتحسين المستوى
        <span class="rh-tab-count">{{ $stats['total_formations'] }}</span>
    </button>
    <button class="rh-tab {{ $tab==='competance' ? 'active' : '' }}" onclick="switchTab('competance')">
        <i class="fa-solid fa-id-card"></i> دليل المكونين
        <span class="rh-tab-count">{{ number_format($stats['total_competances']) }}</span>
    </button>
    <button class="rh-tab {{ $tab==='calendrier' ? 'active' : '' }}" onclick="switchTab('calendrier')">
        <i class="fa-solid fa-calendar-days"></i> رزنامة النشاطات الأسبوعية
        <span class="rh-tab-count">{{ $stats['total_activites'] }}</span>
    </button>
</div>

{{-- Panels --}}

{{-- Personnel --}}
<div id="panel-personnel" class="rh-panel {{ $tab==='personnel' ? 'active' : '' }}">
    <div class="rh-toolbar">
        <div class="rh-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchStaff" placeholder="بحث في الموظفين والأساتذة..." onkeyup="filterTable('tblStaff','searchStaff',[0,1])">
        </div>
        <button class="btn-rh-add" onclick="openModal('modalPersonnel')">
            <i class="fa-solid fa-plus"></i> إضافة موظف
        </button>
    </div>
    <div class="rh-table-wrap">
        <table class="rh-table" id="tblStaff">
            <thead>
                <tr>
                    <th>الصورة</th>
                    <th>اللقب والاسم</th>
                    <th>الرتبة الوظيفية</th>
                    <th>التخصص البيداغوجي</th>
                    <th>تاريخ التعيين الأول</th>
                    <th>رقم التعريف الوطني (NIN)</th>
                    <th>الوظيفة الحالية</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personnel as $p)
                <tr>
                    <td>
                        @if($p['photo'])
                            <img src="{{ url($p['photo']) }}" alt="Avatar" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                        @else
                            <div style="width:36px; height:36px; border-radius:50%; background:#e9ecef; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-user text-secondary"></i></div>
                        @endif
                    </td>
                    <td><strong>{{ $p['nom'] }} {{ $p['prenom'] }}</strong></td>
                    <td><span class="fin-badge fin-badge-blue">{{ $p['grade'] ?? 'غير مصنف' }}</span></td>
                    <td>{{ $p['specialite'] ?? '—' }}</td>
                    <td>{{ $p['date_recrutement'] }}</td>
                    <td><code>{{ $p['nin'] ?? '—' }}</code></td>
                    <td><strong>{{ $p['fonction'] ?? 'إداري / أستاذ' }}</strong></td>
                    <td>
                        <button class="btn-rh-print" onclick="viewPersonnelDetails(<?= htmlspecialchars(json_encode($p)) ?>)" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#0ea5e9; color:#0ea5e9; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-eye"></i> تفاصيل
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="fin-empty"><i class="fa-solid fa-inbox"></i><p>لا يوجد موظفون مسجلون</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:0.75rem 1rem; font-family:'Cairo',sans-serif; font-size:0.78rem; color:var(--tx-3); border-top:1px solid var(--border); text-align:center;">
            <i class="fa-solid fa-info-circle me-1"></i>
            يُعرض أول 500 سجل — إجمالي: <strong>{{ number_format($stats['total_staff']) }}</strong> موظف
        </div>
    </div>
</div>

{{-- Recruitment & Promotion --}}
<div id="panel-recrutement" class="rh-panel {{ $tab==='recrutement' ? 'active' : '' }}">
    <div class="rh-table-wrap">
        <table class="rh-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الرتبة الحالية</th>
                    <th>تاريخ التعيين بالرتبة</th>
                    <th>نمط التوظيف / الترقية</th>
                    <th>الحالة الإدارية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recrutements as $rc)
                <tr>
                    <td><strong>{{ $rc['nom'] }} {{ $rc['prenom'] }}</strong></td>
                    <td><span class="fin-badge fin-badge-blue">{{ $rc['grade'] ?? 'بدون صنف' }}</span></td>
                    <td>{{ $rc['date_recrutement'] }}</td>
                    <td>{{ $rc['mode_recrutement'] ?? 'ترقية بالاختيار / امتحان مهني' }}</td>
                    <td><span class="fin-badge fin-badge-green">مثبت بالرتبة</span></td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="fin-empty"><i class="fa-solid fa-user-plus"></i><p>لا توجد بيانات ترقية وتوظيف</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Formations --}}
<div id="panel-formation" class="rh-panel {{ $tab==='formation' ? 'active' : '' }}">
    <div class="rh-toolbar">
        <div class="rh-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchForm" placeholder="بحث في دورات التكوين..." onkeyup="filterTable('tblForm','searchForm',[0,1])">
        </div>
        <button class="btn-rh-add" onclick="openModal('modalFormation')">
            <i class="fa-solid fa-plus"></i> تسجيل دورة تكوين لموظف
        </button>
    </div>
    <div class="rh-table-wrap">
        <table class="rh-table" id="tblForm">
            <thead>
                <tr>
                    <th>الموظف المستفيد</th>
                    <th>موضوع ومحور التكوين</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>مؤسسة التكوين والرسكلة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($formations as $f)
                <tr>
                    <td><strong>{{ $f['nom'] }} {{ $f['prenom'] }}</strong></td>
                    <td><strong>{{ $f['theme'] }}</strong></td>
                    <td>{{ $f['date_debut'] }}</td>
                    <td>{{ $f['date_fin'] }}</td>
                    <td>{{ $f['etablissement_formation'] }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="fin-empty"><i class="fa-solid fa-graduation-cap"></i><p>لا توجد دورات تكوينية مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Competance --}}
<div id="panel-competance" class="rh-panel {{ $tab==='competance' ? 'active' : '' }}">
    <div class="rh-toolbar">
        <div class="rh-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchCompetance" placeholder="بحث في دليل المكونين..." onkeyup="filterTable('tblCompetance','searchCompetance',[0,1,2,3,4])">
        </div>
        <button class="btn-rh-print" onclick="printCompetanceTable()">
            <i class="fa-solid fa-print"></i> طباعة الدليل
        </button>
        <button class="btn-rh-add" onclick="openAddCompetanceModal()">
            <i class="fa-solid fa-plus"></i> إضافة مكون جديد
        </button>
    </div>
    <div class="rh-table-wrap">
        <table class="rh-table" id="tblCompetance">
            <thead>
                <tr>
                    <th>الاسم واللقب</th>
                    <th>الرتبة الوظيفية</th>
                    <th>الشهادة</th>
                    <th>التخصص</th>
                    <th>المؤسسة التكوينية / الولاية</th>
                    <th>رقم الهاتف</th>
                    <th>تاريخ الميلاد</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($competances as $c)
                <tr>
                    <td><strong>{{ $c['nom_prenom'] }}</strong></td>
                    <td><span class="fin-badge fin-badge-blue">{{ $c['grade'] ?? '—' }}</span></td>
                    <td>{{ $c['diplome'] ?? '—' }}</td>
                    <td><strong>{{ $c['specialite'] ?? '—' }}</strong></td>
                    <td>
                        {{ $c['etablissemnt'] ?? '—' }}
                        @if($c['wilaya'])
                            ({{ $c['wilaya'] }})
                        @endif
                    </td>
                    <td><code>{{ $c['tel'] ?? '—' }}</code></td>
                    <td>
                        @if($c['date_naissance'])
                            {{ substr($c['date_naissance'], 6, 2) }}-{{ substr($c['date_naissance'], 4, 2) }}-{{ substr($c['date_naissance'], 0, 4) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button class="btn-rh-print" onclick="viewCompetanceDetails({{ json_encode($c) }})" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#0ea5e9; color:#0ea5e9; border-radius: 6px; cursor: pointer;">
                                <i class="fa-solid fa-eye"></i> تفاصيل
                            </button>
                            <button class="btn-rh-print" onclick="editCompetance({{ json_encode($c) }})" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#f59e0b; color:#f59e0b; border-radius: 6px; cursor: pointer;">
                                <i class="fa-solid fa-edit"></i> تعديل
                            </button>
                            <button class="btn-rh-print" onclick="deleteCompetance({{ $c['id'] }})" style="padding: 0.3rem 0.6rem; font-size: 0.72rem; border-color:#dc3545; color:#dc3545; border-radius: 6px; cursor: pointer;">
                                <i class="fa-solid fa-trash"></i> حذف
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="fin-empty"><i class="fa-solid fa-inbox"></i><p>لا توجد كفاءات مسجلة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:0.75rem 1rem; font-family:'Cairo',sans-serif; font-size:0.78rem; color:var(--tx-3); border-top:1px solid var(--border); text-align:center;">
            <i class="fa-solid fa-info-circle me-1"></i>
            يُعرض أول 500 سجل — إجمالي: <strong>{{ number_format($stats['total_competances']) }}</strong> مكون
        </div>
    </div>
</div>

{{-- Calendar Activity --}}
<div id="panel-calendrier" class="rh-panel {{ $tab==='calendrier' ? 'active' : '' }}">
    <div class="rh-toolbar">
        <div class="rh-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchAct" placeholder="بحث في النشاطات..." onkeyup="filterTable('tblAct','searchAct',[0])">
        </div>
        <button class="btn-rh-add" onclick="openModal('modalActivite')">
            <i class="fa-solid fa-plus"></i> إضافة نشاط أسبوعي
        </button>
    </div>
    <div class="rh-table-wrap">
        <table class="rh-table" id="tblAct">
            <thead>
                <tr>
                    <th>النشاط المبرمج</th>
                    <th>Nom (Fr)</th>
                    <th>تاريخ البداية</th>
                    <th>تاريخ النهاية</th>
                    <th>مكان الانعقاد</th>
                    <th>أهداف وتفاصيل النشاط</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activites as $act)
                <tr>
                    <td><strong>{{ $act['nom'] }}</strong></td>
                    <td>{{ $act['nom_fr'] }}</td>
                    <td>{{ $act['date_debut'] }}</td>
                    <td>{{ $act['date_fin'] }}</td>
                    <td><span class="fin-badge fin-badge-blue">{{ $act['lieu'] }}</span></td>
                    <td>{{ $act['description'] ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="fin-empty"><i class="fa-solid fa-calendar-days"></i><p>لا توجد نشاطات مبرمجة</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     MODALS FOR CREATION
══════════════════════════════════════════════════════════ --}}

{{-- Modal: Personnel --}}
<div class="rh-modal-overlay" id="modalPersonnel">
    <div class="rh-modal">
        <div class="rh-modal-header">
            <div class="rh-modal-title">إضافة موظف جديد للمؤسسة</div>
            <button class="rh-modal-close" onclick="closeModal('modalPersonnel')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="personnelForm" onsubmit="submitPersonnel(event)">
            <div class="rh-modal-body">
                @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <div class="rh-form-group">
                        <label class="rh-form-label">المؤسسة التكوينية *</label>
                        <select name="etab_id" required>
                            <option value="">— اختر المؤسسة —</option>
                            @foreach(\App\Models\Etablissement::orderBy('Nom')->get() as $et)
                                @if($roleCode === 'admin' || $et->IDDFEP == $dfepId)
                                    <option value="{{ $et->IDetablissement }}" {{ $et->IDetablissement == $etabId ? 'selected' : '' }}>
                                        {{ $et->Nom }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="etab_id" value="{{ $etabId }}">
                @endif
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">اللقب *</label>
                        <input type="text" name="nom" required placeholder="مثال: بن يحيى">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">الاسم *</label>
                        <input type="text" name="prenom" required placeholder="مثال: محمد">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">السلك الإداري والتربوي *</label>
                        <select id="personnel_corp_id" onchange="filterGradesByCorp()">
                            <option value="">— كل الأسلاك —</option>
                            @foreach($corpsTypes as $corp)
                                <option value="{{ $corp->IDNomenclatureCorp }}">{{ $corp->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">الرتبة الوظيفية *</label>
                        <select name="grade_id" id="personnel_grade_id" required>
                            <option value="">— اختر الرتبة —</option>
                            @foreach($allGrades as $gr)
                                <option value="{{ $gr['id'] }}" data-corp="{{ $gr['corp_id'] ?? '' }}">{{ $gr['nom'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">الوظيفة الأساسية *</label>
                        <select name="fonction_id" required>
                            <option value="">— اختر الوظيفة —</option>
                            @foreach($allFonctions as $fn)
                                <option value="{{ $fn['id'] }}">{{ $fn['nom'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">طريقة التوظيف</label>
                        <select name="mode_recrutement_id">
                            @foreach($modesRecr as $mr)
                                <option value="{{ $mr['id'] }}">{{ $mr['nom'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">المستوى الدراسي للمؤطرين *</label>
                        <select name="niveau_scol_id" required>
                            <option value="">— اختر المستوى الدراسي —</option>
                            @foreach($staffEducationLevels as $el)
                                <option value="{{ $el->IDNiveau_Scol_enca }}">{{ $el->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">التخصص البيداغوجي</label>
                        <input type="text" name="specialite" placeholder="مثال: هندسة الطرائق">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">تاريخ التعيين الأول</label>
                        <input type="date" name="date_recrutement" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">تاريخ التثبيت بالمنصب</label>
                        <input type="date" name="date_installation" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">الوضعية الإدارية *</label>
                        <select name="situation_id" required>
                            @foreach($situationTypes as $st)
                                <option value="{{ $st->IDIDSituationAdministrat_type }}">{{ $st->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">رقم التعريف الوطني (NIN) *</label>
                        <input type="text" name="nin" required placeholder="رقم التعريف الوطني (18 رقم)">
                    </div>
                </div>
            </div>
            <div class="rh-modal-footer">
                <button type="button" class="btn-rh-print" onclick="closeModal('modalPersonnel')">إلغاء</button>
                <button type="submit" class="btn-rh-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Formation --}}
<div class="rh-modal-overlay" id="modalFormation">
    <div class="rh-modal">
        <div class="rh-modal-header">
            <div class="rh-modal-title">تسجيل دورة تكوينية لموظف</div>
            <button class="rh-modal-close" onclick="closeModal('modalFormation')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="formationForm" onsubmit="submitFormation(event)">
            <div class="rh-modal-body">
                <div class="rh-form-group">
                    <label class="rh-form-label">الموظف المستفيد *</label>
                    <select name="encadrement_id" required>
                        <option value="">— اختر الموظف —</option>
                        @foreach($personnel as $pr)
                            <option value="{{ $pr['id'] }}">{{ $pr['nom'] }} {{ $pr['prenom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rh-form-group">
                    <label class="rh-form-label">موضوع التكوين أو الرسكلة *</label>
                    <input type="text" name="theme" required placeholder="مثال: هندسة الأنظمة البيداغوجية">
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">تاريخ البداية *</label>
                        <input type="date" name="date_debut" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">تاريخ النهاية *</label>
                        <input type="date" name="date_fin" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">مؤسسة التكوين والرسكلة</label>
                        <input type="text" name="etablissement_formation" placeholder="المعهد الوطني للتكوين المهني...">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">تاريخ منح الشهادة/الترخيص</label>
                        <input type="date" name="date_attestation" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
            </div>
            <div class="rh-modal-footer">
                <button type="button" class="btn-rh-print" onclick="closeModal('modalFormation')">إلغاء</button>
                <button type="submit" class="btn-rh-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Activite --}}
<div class="rh-modal-overlay" id="modalActivite">
    <div class="rh-modal">
        <div class="rh-modal-header">
            <div class="rh-modal-title">إضافة نشاط أسبوعي مبرمج</div>
            <button class="rh-modal-close" onclick="closeModal('modalActivite')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="activiteForm" onsubmit="submitActivite(event)">
            <div class="rh-modal-body">
                @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <div class="rh-form-group">
                        <label class="rh-form-label">المؤسسة التكوينية *</label>
                        <select name="etab_id" required>
                            <option value="">— اختر المؤسسة —</option>
                            @foreach(\App\Models\Etablissement::orderBy('Nom')->get() as $et)
                                @if($roleCode === 'admin' || $et->IDDFEP == $dfepId)
                                    <option value="{{ $et->IDetablissement }}" {{ $et->IDetablissement == $etabId ? 'selected' : '' }}>
                                        {{ $et->Nom }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="etab_id" value="{{ $etabId }}">
                @endif
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">تسمية النشاط *</label>
                        <input type="text" name="nom" required placeholder="مثال: ندوة بيداغوجية جهوية">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">Nom (Français)</label>
                        <input type="text" name="nom_fr">
                    </div>
                </div>
                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">تاريخ البداية *</label>
                        <input type="date" name="date_debut" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">تاريخ النهاية *</label>
                        <input type="date" name="date_fin" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="rh-form-group">
                    <label class="rh-form-label">مكان انعقاد النشاط *</label>
                    <input type="text" name="lieu" required placeholder="مثال: القاعة المتعددة الخدمات">
                </div>
                <div class="rh-form-group">
                    <label class="rh-form-label">تفاصيل وأهداف النشاط الأسبوعي</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="rh-modal-footer">
                <button type="button" class="btn-rh-print" onclick="closeModal('modalActivite')">إلغاء</button>
                <button type="submit" class="btn-rh-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Competance (Add / Edit) --}}
<div class="rh-modal-overlay" id="modalCompetance">
    <div class="rh-modal">
        <div class="rh-modal-header">
            <div class="rh-modal-title" id="competanceModalTitle">إضافة مكون جديد</div>
            <button class="rh-modal-close" onclick="closeModal('modalCompetance')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="competanceForm" onsubmit="submitCompetance(event)">
            <div class="rh-modal-body">
                <input type="hidden" name="id" id="comp_id" value="">
                @if(in_array($roleCode, ['admin', 'dfep', 'central']))
                    <div class="rh-form-group">
                        <label class="rh-form-label">المؤسسة التكوينية *</label>
                        <select name="etab_id" id="comp_etab_id" required>
                            <option value="">— اختر المؤسسة —</option>
                            @foreach(\App\Models\Etablissement::orderBy('Nom')->get() as $et)
                                @if($roleCode === 'admin' || $et->IDDFEP == $dfepId)
                                    <option value="{{ $et->IDetablissement }}" {{ $et->IDetablissement == $etabId ? 'selected' : '' }}>
                                        {{ $et->Nom }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="etab_id" id="comp_etab_id" value="{{ $etabId }}">
                @endif

                <div class="rh-form-group">
                    <label class="rh-form-label">الاسم الكامل *</label>
                    <input type="text" name="nom_prenom" id="comp_nom_prenom" required placeholder="مثال: بن يحيى محمد">
                </div>

                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">الرتبة الوظيفية</label>
                        <input type="text" name="grade" id="comp_grade" placeholder="مثال: أستاذ متخصص في التكوين المهني من الدرجة الأولى">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">الشهادة المحصل عليها</label>
                        <input type="text" name="diplome" id="comp_diplome" placeholder="مثال: مهندس دولة في الإعلام الآلي">
                    </div>
                </div>

                <div class="rh-form-row">
                    <div class="rh-form-group">
                        <label class="rh-form-label">التخصص الدقيق</label>
                        <input type="text" name="specialite" id="comp_specialite" placeholder="مثال: شبكات وأنظمة موزعة">
                    </div>
                    <div class="rh-form-group">
                        <label class="rh-form-label">رقم الهاتف</label>
                        <input type="text" name="tel" id="comp_tel" placeholder="مثال: 0661000000">
                    </div>
                </div>

                <div class="rh-form-group">
                    <label class="rh-form-label">تاريخ الميلاد</label>
                    <input type="text" name="date_naissance" id="comp_date_naissance" placeholder="مثال: 19890406 (سنة-شهر-يوم)">
                </div>
            </div>
            <div class="rh-modal-footer">
                <button type="button" class="btn-rh-print" onclick="closeModal('modalCompetance')">إلغاء</button>
                <button type="submit" class="btn-rh-add"><i class="fa-solid fa-save"></i> حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Details --}}
<div class="rh-modal-overlay" id="modalDetails">
    <div class="rh-modal">
        <div class="rh-modal-header">
            <div class="rh-modal-title" id="detailsModalTitle">تفاصيل الموظف</div>
            <button class="rh-modal-close" onclick="closeModal('modalDetails')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="rh-modal-body text-center">
            <!-- Image Preview -->
            <div style="margin-bottom: 1.5rem; text-align: center;">
                <img id="detail_photo" src="" alt="Photo" onerror="if(this.src && !this.src.includes('/public/uploads/') && this.src.includes('/uploads/')){ this.src = this.src.replace('/uploads/', '/public/uploads/'); }" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; display: none; margin: 0 auto;">
                <div id="detail_photo_fallback" style="width: 100px; height: 100px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2.5rem; color: #464eb5;">
                    <i class="fa-solid fa-user"></i>
                </div>
                
                {{-- Action Buttons --}}
                <div id="detail_photo_actions" style="margin-top: 0.75rem; display: none; justify-content: center; gap: 0.5rem;">
                    <button class="btn btn-xs btn-outline-primary py-1 px-2" onclick="triggerEmployeePhotoUpload()" style="font-size: 0.68rem; font-family: 'Cairo';">
                        <i class="fa-solid fa-camera"></i> <span id="detail_photo_action_label">إضافة/تغيير</span>
                    </button>
                    <button class="btn btn-xs btn-outline-danger py-1 px-2" id="detail_photo_delete_btn" onclick="deleteEmployeePhoto()" style="font-size: 0.68rem; font-family: 'Cairo'; display: none;">
                        <i class="fa-solid fa-trash-can"></i> حذف
                    </button>
                </div>
                <input type="file" id="detailEmployeePhotoInput" accept="image/*" style="display:none;" onchange="handleEmployeePhotoUpload()">
            </div>
            
            <div id="detailsModalFields" style="text-align: right; direction: rtl; font-family: 'Cairo', sans-serif;">
                <!-- Filled dynamically by JS -->
            </div>
        </div>
        <div class="rh-modal-footer">
            <button type="button" class="btn-rh-print" onclick="closeModal('modalDetails')">إغلاق</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
const BASE = '{{ asset("") }}';
const CSRF = '{{ csrf_token() }}';
const MEDIA_ACTIONS_ENABLED = {{ \App\Helpers\SovereignLicensingHelper::getSetting('patrimoine_media_actions_enabled', '1') === '1' ? 'true' : 'false' }};

let currentEmployeeId = null;

function viewPersonnelDetails(p) {
    const title = 'تفاصيل الموظف: ' + p.nom + ' ' + p.prenom;
    document.getElementById('detailsModalTitle').textContent = title;
    
    currentEmployeeId = p.id || p.IDEncadrement;
    
    // Set Photo
    const imgEl = document.getElementById('detail_photo');
    const fallbackEl = document.getElementById('detail_photo_fallback');
    
    if (p.photo) {
        imgEl.src = BASE.endsWith('/') ? `${BASE}${p.photo.replace(/^\/+/, '')}` : `${BASE}/${p.photo.replace(/^\/+/, '')}`;
        imgEl.style.display = 'block';
        fallbackEl.style.display = 'none';
    } else {
        imgEl.style.display = 'none';
        imgEl.src = '';
        fallbackEl.style.display = 'flex';
    }
    
    if (MEDIA_ACTIONS_ENABLED) {
        document.getElementById('detail_photo_actions').style.display = 'flex';
        if (p.photo) {
            document.getElementById('detail_photo_delete_btn').style.display = 'inline-block';
            document.getElementById('detail_photo_action_label').textContent = 'تغيير الصورة';
        } else {
            document.getElementById('detail_photo_delete_btn').style.display = 'none';
            document.getElementById('detail_photo_action_label').textContent = 'إضافة صورة';
        }
    } else {
        document.getElementById('detail_photo_actions').style.display = 'none';
    }
    
    // Fields
    const fields = [
        { label: 'الاسم واللقب', value: p.nom + ' ' + p.prenom },
        { label: 'الرتبة الوظيفية', value: p.grade || '—' },
        { label: 'التخصص البيداغوجي', value: p.specialite || '—' },
        { label: 'تاريخ التعيين الأول', value: p.date_recrutement || '—' },
        { label: 'تاريخ التثبيت', value: p.date_installation || '—' },
        { label: 'رقم التعريف الوطني', value: p.nin || '—' },
        { label: 'الوظيفة الحالية', value: p.fonction || '—' },
        { label: 'البريد الإلكتروني', value: p.email || '—' },
        { label: 'الهاتف', value: p.tel || '—' },
        { label: 'المؤسسة', value: p.etablissement_nom || p.etablissemnt || '—' }
    ];
    
    const container = document.getElementById('detailsModalFields');
    container.innerHTML = '';
    
    fields.forEach(f => {
        const div = document.createElement('div');
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.padding = '0.6rem 0';
        div.style.borderBottom = '1px solid var(--border, #eef1f7)';
        div.innerHTML = `
            <span style="color: var(--tx-2, #8898b0); font-weight: 700; font-size: 0.8rem;">${f.label}</span>
            <span style="color: var(--tx-1); font-weight: 700;">${f.value}</span>
        `;
        container.appendChild(div);
    });
    
    openModal('modalDetails');
}

function viewCompetanceDetails(c) {
    const title = 'تفاصيل المكون: ' + c.nom_prenom;
    document.getElementById('detailsModalTitle').textContent = title;
    
    // Set Photo fallback
    document.getElementById('detail_photo').style.display = 'none';
    document.getElementById('detail_photo_fallback').style.display = 'flex';
    
    // Fields
    const fields = [
        { label: 'الاسم الكامل', value: c.nom_prenom },
        { label: 'الرتبة الوظيفية', value: c.grade || '—' },
        { label: 'الشهادة المحصل عليها', value: c.diplome || '—' },
        { label: 'التخصص الدقيق', value: c.specialite || '—' },
        { label: 'المؤسسة / الولاية', value: c.etablissemnt + (c.wilaya ? ' (' + c.wilaya + ')' : '') },
        { label: 'رقم الهاتف', value: c.tel || '—' },
        { label: 'تاريخ الميلاد', value: c.date_naissance ? (c.date_naissance.substring(6,8)+'-'+c.date_naissance.substring(4,6)+'-'+c.date_naissance.substring(0,4)) : '—' }
    ];
    
    const container = document.getElementById('detailsModalFields');
    container.innerHTML = '';
    
    fields.forEach(f => {
        const div = document.createElement('div');
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.padding = '0.6rem 0';
        div.style.borderBottom = '1px solid var(--border, #eef1f7)';
        div.innerHTML = `
            <span style="color: var(--tx-2, #8898b0); font-weight: 700; font-size: 0.8rem;">${f.label}</span>
            <span style="color: var(--tx-1); font-weight: 700;">${f.value}</span>
        `;
        container.appendChild(div);
    });
    
    openModal('modalDetails');
}

// ─── Tab switching ──────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.rh-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.rh-panel').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

// ─── Filter Grades By Corps ──────────────────────────────
function filterGradesByCorp() {
    const corpId = document.getElementById('personnel_corp_id').value;
    const gradeSelect = document.getElementById('personnel_grade_id');
    const options = gradeSelect.querySelectorAll('option');
    
    options.forEach(opt => {
        if (!opt.value) return; // Skip placeholder
        const optCorpId = opt.getAttribute('data-corp');
        if (!corpId || optCorpId === corpId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    
    // Reset grade selection if currently selected one is hidden
    const selectedOpt = gradeSelect.options[gradeSelect.selectedIndex];
    if (selectedOpt && selectedOpt.value && selectedOpt.style.display === 'none') {
        gradeSelect.value = '';
    }
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
    const t = document.getElementById('rhToast');
    t.textContent = msg;
    t.className = 'rh-toast ' + type;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

// ─── Modal helpers ──────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.rh-modal-overlay').forEach(m => {
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
async function submitPersonnel(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/rh-gestion/personnel/store', data);
    if (res.success) { showToast(res.message); closeModal('modalPersonnel'); setTimeout(()=>reloadTab('personnel'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitFormation(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/rh-gestion/formation/store', data);
    if (res.success) { showToast(res.message); closeModal('modalFormation'); setTimeout(()=>reloadTab('formation'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

async function submitActivite(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await apiCall(BASE + '/dashboard/rh-gestion/activite/store', data);
    if (res.success) { showToast(res.message); closeModal('modalActivite'); setTimeout(()=>reloadTab('calendrier'), 800); }
    else showToast(res.error || 'حدث خطأ', 'error');
}

function openAddCompetanceModal() {
    document.getElementById('competanceForm').reset();
    document.getElementById('comp_id').value = '';
    document.getElementById('competanceModalTitle').textContent = 'إضافة مكون جديد';
    openModal('modalCompetance');
}

function editCompetance(c) {
    document.getElementById('comp_id').value = c.id;
    document.getElementById('comp_nom_prenom').value = c.nom_prenom || '';
    document.getElementById('comp_grade').value = c.grade || '';
    document.getElementById('comp_diplome').value = c.diplome || '';
    document.getElementById('comp_specialite').value = c.specialite || '';
    document.getElementById('comp_tel').value = c.tel || '';
    document.getElementById('comp_date_naissance').value = c.date_naissance || '';
    
    const etabSelect = document.getElementById('comp_etab_id');
    if (etabSelect && c.IDEtablissement) {
        etabSelect.value = c.IDEtablissement;
    }
    
    document.getElementById('competanceModalTitle').textContent = 'تعديل بيانات المكون';
    openModal('modalCompetance');
}

async function deleteCompetance(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المكون نهائياً؟')) return;
    const res = await apiCall(BASE + '/dashboard/rh-gestion/competance/delete/' + id, {}, 'POST');
    if (res.success) {
        showToast(res.message);
        setTimeout(() => reloadTab('competance'), 800);
    } else {
        showToast(res.error || 'حدث خطأ أثناء الحذف', 'error');
    }
}

async function submitCompetance(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const isEdit = data.id && data.id !== '';
    const url = isEdit ? '/dashboard/rh-gestion/competance/update' : '/dashboard/rh-gestion/competance/store';
    
    const res = await apiCall(BASE + url, data);
    if (res.success) {
        showToast(res.message);
        closeModal('modalCompetance');
        setTimeout(() => reloadTab('competance'), 800);
    } else {
        showToast(res.error || 'حدث خطأ أثناء الحفظ', 'error');
    }
}

function printCompetanceTable() {
    const printWindow = window.open('', '_blank');
    const tableHtml = document.getElementById('tblCompetance').outerHTML;
    printWindow.document.write(`
        <html>
        <head>
            <title>دليل المكونين والأساتذة</title>
            <style>
                body { font-family: 'Cairo', sans-serif; direction: rtl; padding: 20px; }
                h2 { text-align: center; color: #382d6b; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: right; }
                th { background-color: #f3f0ff; color: #382d6b; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                th:last-child, td:last-child { display: none; }
            </style>
        </head>
        <body>
            <h2>دليل المكونين والأساتذة - قطاع التكوين والتعليم المهنيين</h2>
            \${tableHtml}
            <script>
                window.onload = function() { window.print(); window.close(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
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

function triggerEmployeePhotoUpload() {
    document.getElementById('detailEmployeePhotoInput').click();
}

function handleEmployeePhotoUpload() {
    if (!currentEmployeeId) return;
    const input = document.getElementById('detailEmployeePhotoInput');
    if (!input.files || input.files.length === 0) return;
    
    const file = input.files[0];
    const formData = new FormData();
    formData.append('type', 'encadrement');
    formData.append('id', currentEmployeeId);
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

function deleteEmployeePhoto() {
    if (!currentEmployeeId) return;
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "هل تريد حذف الصورة المرفقة للموظف؟",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذفها',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('type', 'encadrement');
            formData.append('id', currentEmployeeId);
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
