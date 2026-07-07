@extends('layouts.main')
@section('title', 'منشئ التقارير الديناميكية | SGFEP')

@section('styles')
<style>
/* ── Reports Page ───────────────────────────── */
.report-card {
    background: var(--bg-glass);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.4rem;
    position: relative;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s, border-color .2s;
    height: 100%;
}
.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,.15);
    border-color: var(--rc-color, var(--electric));
}
.report-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--rc-color, var(--electric));
    border-radius: 14px 14px 0 0;
}
.rc-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem;
    background: rgba(255,255,255,.05);
    color: var(--rc-color, var(--electric));
    margin-bottom: 1rem;
}
.rc-title { font-size: .95rem; font-weight: 800; font-family: 'Cairo'; color: var(--tx-1); margin-bottom: .35rem; }
.rc-desc  { font-size: .78rem; color: var(--tx-3); font-family: 'Cairo'; line-height: 1.5; margin-bottom: .85rem; }
.rc-count {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.05);
    border-radius: 20px; padding: .2rem .65rem;
    font-size: .76rem; font-weight: 800; font-family: 'Outfit';
    color: var(--rc-color, var(--electric));
    margin-bottom: .85rem;
}
.rc-actions { display: flex; gap: .4rem; flex-wrap: wrap; }
.rc-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .35rem .8rem;
    border-radius: 7px;
    font-size: .77rem; font-weight: 700; font-family: 'Cairo';
    text-decoration: none;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--tx-2);
    cursor: pointer;
    transition: all .18s;
}
.rc-btn:hover { border-color: var(--rc-color); color: var(--rc-color); background: rgba(255,255,255,.04); }
.rc-btn.primary { background: var(--rc-color); border-color: var(--rc-color); color: #fff; }
.rc-btn.primary:hover { filter: brightness(1.12); }

/* Quick Stat */
.qs-card {
    background: var(--bg-glass);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: .9rem 1.1rem;
    border-right: 3px solid var(--qs-color, var(--electric));
}
.qs-val { font-size: 1.5rem; font-weight: 800; font-family: 'Outfit'; color: var(--qs-color, var(--electric)); line-height: 1.1; }
.qs-lbl { font-size: .74rem; color: var(--tx-3); font-family: 'Cairo'; margin-top: .2rem; }

/* Filter nav */
.type-nav-item {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .85rem;
    border-radius: 20px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--tx-2);
    font-size: .8rem; font-weight: 700; font-family: 'Cairo';
    cursor: pointer;
    transition: all .18s;
}
.type-nav-item:hover, .type-nav-item.active {
    background: var(--electric);
    border-color: var(--electric);
    color: #fff;
}
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--electric),#6366f1);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-chart-bar text-white fs-5"></i>
        </div>
        <div>
            <h1 class="fw-black m-0" style="font-size:1.25rem;font-family:'Cairo';color:var(--tx-1);">منشئ التقارير الديناميكية</h1>
            <p class="text-muted mb-0" style="font-size:.78rem;font-weight:600;">اختر التقرير وصيغة التصدير — يُنتَج من البيانات الحية لحظياً</p>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="qs-card" style="--qs-color:#6366f1">
                <div class="qs-val">{{ number_format($quickStats['apprenants']) }}</div>
                <div class="qs-lbl"><i class="fa-solid fa-user-graduate me-1"></i> إجمالي المتربصين</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="qs-card" style="--qs-color:#0EA66E">
                <div class="qs-val">{{ number_format($quickStats['formateurs']) }}</div>
                <div class="qs-lbl"><i class="fa-solid fa-chalkboard-user me-1"></i> المكوّنون النشطون</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="qs-card" style="--qs-color:#F0A500">
                <div class="qs-val">{{ number_format($quickStats['offres']) }}</div>
                <div class="qs-lbl"><i class="fa-solid fa-briefcase me-1"></i> العروض التكوينية</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="qs-card" style="--qs-color:#3b82f6">
                <div class="qs-val">{{ number_format($quickStats['sections']) }}</div>
                <div class="qs-lbl"><i class="fa-solid fa-layer-group me-1"></i> الأقسام والأفواج</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="glass-panel p-3 mb-4 d-flex align-items-center gap-2 flex-wrap">
        <span style="font-size:.8rem;font-weight:800;color:var(--tx-3);font-family:'Cairo';">
            <i class="fa-solid fa-filter me-1"></i> تصفية:
        </span>
        <button class="type-nav-item active" onclick="filterReports('all', this)">
            <i class="fa-solid fa-grid"></i> الكل
        </button>
        <button class="type-nav-item" onclick="filterReports('تعليمية', this)">
            <i class="fa-solid fa-graduation-cap"></i> التعليمية
        </button>
        <button class="type-nav-item" onclick="filterReports('الموارد البشرية', this)">
            <i class="fa-solid fa-users"></i> الموارد البشرية
        </button>
        <button class="type-nav-item" onclick="filterReports('إدارية', this)">
            <i class="fa-solid fa-building"></i> الإدارية
        </button>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span style="font-size:.75rem;color:var(--tx-3);font-family:'Cairo';">
                <i class="fa-solid fa-circle-info me-1" style="color:var(--electric);"></i>
                التقارير تُنتَج من البيانات الحية
            </span>
        </div>
    </div>

    {{-- Report Cards Grid --}}
    <div class="row g-3" id="reportsGrid">
        @foreach($reportTypes as $report)
        <div class="col-12 col-md-6 col-xl-4 report-col" data-category="">
            <div class="report-card" style="--rc-color: {{ $report['color'] }}">
                <div class="rc-icon">
                    <i class="fa-solid {{ $report['icon'] }}"></i>
                </div>
                <div class="rc-title">{{ $report['title'] }}</div>
                <div class="rc-desc">{{ $report['description'] }}</div>

                @if($report['count'] !== null)
                <div class="rc-count">
                    <i class="fa-solid fa-database" style="font-size:.65rem;"></i>
                    {{ number_format($report['count']) }} {{ $report['label'] }}
                </div>
                @endif

                <div class="rc-actions">
                    <a href="{{ route('reports.export', $report['key']) }}" class="rc-btn primary" title="تحميل ملف CSV">
                        <i class="fa-solid fa-file-csv"></i> CSV
                    </a>
                    <a href="{{ route('reports.print', $report['key']) }}" target="_blank" class="rc-btn" title="طباعة التقرير">
                        <i class="fa-solid fa-print"></i> طباعة
                    </a>
                    <a href="{{ route('reports.pdf', $report['key']) }}" target="_blank" class="rc-btn" title="تحميل PDF">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>

<script>
function filterReports(category, btn) {
    // Update active button
    document.querySelectorAll('.type-nav-item').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    // Filter cards (client-side demo)
    document.querySelectorAll('.report-col').forEach(col => {
        const title = col.querySelector('.rc-title').textContent;
        col.style.display = (category === 'all' || title.includes(category)) ? '' : 'none';
    });
}
</script>
@endsection
