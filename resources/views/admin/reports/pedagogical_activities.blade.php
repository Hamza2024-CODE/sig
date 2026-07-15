@extends('layouts.main')
@section('title', 'حصيلة النشاطات البيداغوجية')
@section('content')

<div class="animate__animated animate__fadeIn">
    <!-- Page Header -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#0284c7,#0f172a);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(2,132,199,0.15);">
            <i class="fa-solid fa-chart-pie text-white fs-5"></i>
        </div>
        <div>
            <h1 class="fw-black m-0" style="font-size:1.35rem;font-family:'Cairo';color:var(--tx-1);">حصيلة النشاطات البيداغوجية</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;font-weight:600;">تقرير ومتابعة تعداد المتربصين (الناشطين والجدد) بالتفصيل حسب التخصصات والفروع</p>
        </div>
        <div class="me-auto"></div>
        
        <!-- Action Buttons -->
        <div class="d-flex gap-2">
            <a href="{{ url('dashboard/pedagogical-activity-report/export?' . http_build_query(request()->all())) }}" class="btn btn-premium-excel d-inline-flex align-items-center gap-2 px-3.5 py-2 fw-bold text-white" style="background:#10b981;border-radius:30px;font-size:0.85rem;text-decoration:none;transition:all 0.2s;">
                <i class="fa-solid fa-file-excel"></i> تصدير كـ Excel
            </a>
            <button onclick="window.print()" class="btn btn-premium-print d-inline-flex align-items-center gap-2 px-3.5 py-2 fw-bold" style="background:#fff;border:1.5px solid #cbd5e1;border-radius:30px;font-size:0.85rem;color:#475569;transition:all 0.2s;">
                <i class="fa-solid fa-print"></i> طباعة التقرير
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card border-0 mb-4 p-4 no-print" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.02);background:#fff;border:1px solid rgba(226,232,240,0.8) !important;">
        <form method="GET" action="{{ url('dashboard/pedagogical-activity-report') }}" class="row g-3">
            @if(count($etablissements) > 0)
                <div class="col-12 col-md-3 text-right">
                    <label class="form-label fw-bold text-secondary small">المؤسسة التكوينية</label>
                    <select name="etab_id" class="form-select form-select-premium" onchange="this.form.submit()">
                        <option value="">-- كل المؤسسات --</option>
                        @foreach($etablissements as $e)
                            <option value="{{ $e->id }}" {{ request('etab_id') == $e->id ? 'selected':'' }}>{{ $e->nom }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-12 col-md-3 text-right">
                <label class="form-label fw-bold text-secondary small">الشعبة المهنية</label>
                <select name="branche_id" class="form-select form-select-premium" onchange="this.form.submit()">
                    <option value="">-- كل الشعب --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branche_id') == $b->id ? 'selected':'' }}>{{ $b->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2 text-right">
                <label class="form-label fw-bold text-secondary small">النمط</label>
                <select name="mode_id" class="form-select form-select-premium" onchange="this.form.submit()">
                    <option value="">-- كل الأنماط --</option>
                    @foreach($modes as $m)
                        <option value="{{ $m->id }}" {{ request('mode_id') == $m->id ? 'selected':'' }}>{{ $m->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2 text-right">
                <label class="form-label fw-bold text-secondary small">السداسي</label>
                <select name="semester" class="form-select form-select-premium" onchange="this.form.submit()">
                    <option value="">-- كل السداسيات --</option>
                    @for($s = 1; $s <= 5; $s++)
                        <option value="{{ $s }}" {{ request('semester') == $s ? 'selected':'' }}>السداسي {{ $s }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-12 col-md-2 text-right">
                <label class="form-label fw-bold text-secondary small">البحث السريع</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-premium" placeholder="اسم التخصص أو الفوج..." onkeyup="if(event.key==='Enter') this.form.submit()">
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-responsive-wrapper" style="border-radius: 16px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
        <table class="table table-hover align-middle m-0 text-right" style="font-size:0.82rem;font-family:'Cairo';">
            <thead class="text-white" style="background:#0f172a;">
                <tr>
                    <th class="py-3 px-3 text-center" style="width: 5%;">الرمز</th>
                    <th class="py-3 px-3 text-center" style="width: 8%;">رمز الاختصاص</th>
                    <th class="py-3 px-3">التسمية العربية</th>
                    <th class="py-3 px-3">Nom Français</th>
                    <th class="py-3 px-3 text-center" style="width: 6%;">رقم السداسي</th>
                    <th class="py-3 px-3 text-center" style="width: 8%;">الفوج / القسم</th>
                    <th class="py-3 px-3 text-center" style="width: 7%;">بداية التكوين</th>
                    <th class="py-3 px-3 text-center" style="width: 7%;">نهاية التكوين</th>
                    <th class="py-3 px-3 text-center" style="width: 6%;">العدد الكلي</th>
                    <th class="py-3 px-3 text-center" style="width: 5%;">منهم إناث</th>
                    <th class="py-3 px-3 text-center" style="width: 6%;">قيد التكوين</th>
                    <th class="py-3 px-3 text-center" style="width: 5%;">منهم إناث</th>
                    <th class="py-3 px-3 text-center" style="width: 8%;">النمط</th>
                    <th class="py-3 px-3">المؤسسة التكوينية</th>
                    <th class="py-3 px-3">الشعبة المهنية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $item)
                    <tr>
                        <td class="text-center fw-bold text-secondary"><code>{{ $item['id_offre'] }}</code></td>
                        <td class="text-center fw-bold"><code>{{ $item['code_specialite'] }}</code></td>
                        <td class="fw-bold text-dark">{{ $item['nom_specialite'] }}</td>
                        <td style="font-family:'Outfit';font-size:0.78rem;">{{ $item['nom_formation'] }}</td>
                        <td class="text-center fw-bold">
                            <span class="badge rounded-pill bg-light text-dark px-2.5 py-1.5 border" style="font-size:0.75rem;">
                                السداسي {{ $item['numero_semestre'] }}
                            </span>
                        </td>
                        <td class="text-center fw-bold text-primary">{{ $item['section_nom'] }}</td>
                        <td class="text-center text-muted" style="font-size:0.78rem;">{{ $item['date_debut'] ? date('Y/m/d', strtotime($item['date_debut'])) : '—' }}</td>
                        <td class="text-center text-muted" style="font-size:0.78rem;">{{ $item['date_fin'] ? date('Y/m/d', strtotime($item['date_fin'])) : '—' }}</td>
                        <td class="text-center fw-bold text-dark fs-6">{{ $item['total_inscrits'] }}</td>
                        <td class="text-center fw-bold text-secondary">{{ $item['femmes_inscrits'] }}</td>
                        <td class="text-center fw-bold text-success fs-6">{{ $item['total_actifs'] }}</td>
                        <td class="text-center fw-bold text-secondary">{{ $item['femmes_actifs'] }}</td>
                        <td class="text-center fw-semibold">{{ $item['nom_mode_formation'] }}</td>
                        <td class="text-muted fw-semibold" style="font-size:0.8rem;">{{ $item['nom_etablissement'] }}</td>
                        <td><span class="badge bg-secondary-light text-secondary rounded-pill px-2.5 py-1.5" style="background:rgba(71,85,105,0.08);">{{ $item['nom_branche'] }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 mb-3 d-block text-secondary opacity-40"></i>
                            لا توجد بيانات حصيلة متوفرة حالياً تطابق الفلاتر المحددة.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.form-select-premium, .form-control-premium {
    border-radius: 10px;
    border: 1.5px solid #cbd5e1;
    font-size: 0.85rem;
    padding: 0.5rem 0.8rem;
    font-weight: 600;
    transition: all 0.2s ease;
}
.form-select-premium:focus, .form-control-premium:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2,132,199,0.15);
}
.btn-premium-excel:hover {
    filter: brightness(1.05);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(16,185,129,0.25);
}
.btn-premium-print:hover {
    background: #f8fafc !important;
    border-color: #94a3b8 !important;
    transform: translateY(-2px);
}
@media print {
    body { background:#fff !important; }
    .no-print { display:none !important; }
    .table-responsive-wrapper { overflow:visible !important; border:none !important; box-shadow:none !important; }
    .table { width: 100% !important; font-size: 0.65rem !important; }
    .table th { background:#0f172a !important; color:#fff !important; -webkit-print-color-adjust: exact; }
}
</style>

@endsection
