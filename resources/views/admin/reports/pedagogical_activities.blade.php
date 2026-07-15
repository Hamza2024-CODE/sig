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

    <!-- Error Alert if any -->
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm text-right" style="border-radius:12px;font-family:'Cairo';">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Filters Section -->
    <div class="card border-0 mb-4 p-4 no-print" style="border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.02);background:#fff;border:1px solid rgba(226,232,240,0.8) !important;">
        <form method="GET" action="{{ url('dashboard/pedagogical-activity-report') }}" class="row g-3">
            
            <div class="col-12 col-md-2 text-right">
                <label class="form-label fw-bold text-secondary small">الولاية</label>
                <select name="wilaya_id" id="filter_wilaya_id" class="form-select form-select-premium" onchange="filterEtablissements(); this.form.submit();">
                    <option value="">-- كل الولايات --</option>
                    @foreach($wilayas as $w)
                        <option value="{{ $w->id }}" {{ request('wilaya_id') == $w->id ? 'selected':'' }}>{{ $w->nom }}</option>
                    @endforeach
                </select>
            </div>

            @if(count($etablissements) > 0)
                <div class="col-12 col-md-3 text-right">
                    <label class="form-label fw-bold text-secondary small">المؤسسة التكوينية</label>
                    <select name="etab_id" id="filter_etab_id" class="form-select form-select-premium" onchange="this.form.submit()">
                        <option value="">-- كل المؤسسات --</option>
                        @foreach($etablissements as $e)
                            <option value="{{ $e->id }}" data-wilaya="{{ $e->wilaya_id }}" {{ request('etab_id') == $e->id ? 'selected':'' }}>{{ $e->nom }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-12 col-md-2 text-right">
                <label class="form-label fw-bold text-secondary small">الشعبة المهنية</label>
                <select name="branche_id" class="form-select form-select-premium" onchange="this.form.submit()">
                    <option value="">-- كل الشعب --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branche_id') == $b->id ? 'selected':'' }}>{{ $b->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-1.5 col-lg-2 text-right">
                <label class="form-label fw-bold text-secondary small">النمط</label>
                <select name="mode_id" class="form-select form-select-premium" onchange="this.form.submit()">
                    <option value="">-- كل الأنماط --</option>
                    @foreach($modes as $m)
                        <option value="{{ $m->id }}" {{ request('mode_id') == $m->id ? 'selected':'' }}>{{ $m->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-1.5 col-lg-1 text-right">
                <label class="form-label fw-bold text-secondary small">السداسي</label>
                <select name="semester" class="form-select form-select-premium" onchange="this.form.submit()">
                    <option value="">-- الكل --</option>
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
    <div class="table-responsive-wrapper" style="border-radius: 16px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow-x: auto;">
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
                    <th class="py-3 px-3 text-center no-print" style="width: 8%;">إجراءات</th>
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
                        <td class="text-center no-print">
                            <div class="dropdown">
                                <button class="btn btn-sm d-inline-flex align-items-center gap-1 px-3 py-1.5 fw-bold" style="font-size:0.78rem;border-radius:20px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    إجراءات <i class="fa-solid fa-chevron-down" style="font-size:0.6rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow text-right" style="border-radius:12px;font-size:0.82rem;min-width:180px;box-shadow:0 10px 30px rgba(0,0,0,0.08)!important;">
                                    <li>
                                        <button class="dropdown-item py-2.5 d-flex align-items-center gap-2" type="button" onclick="showTraineesModal({{ $item['section_id'] }}, '{{ addslashes($item['section_nom'] . ' - ' . $item['nom_specialite']) }}')">
                                            <i class="fa-solid fa-users text-primary"></i> <span>عرض المتربصين (التفاصيل)</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 mb-3 d-block text-secondary opacity-40"></i>
                            لا توجد بيانات حصيلة متوفرة حالياً تطابق الفلاتر المحددة.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Trainees Details Modal -->
<div class="modal fade" id="traineesModal" tabindex="-1" aria-labelledby="traineesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 text-right" style="border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,0.12);overflow:hidden;direction:rtl;">
            <div class="modal-header border-0 text-white" style="background:linear-gradient(135deg,#0284c7,#0f172a);padding:1.5rem 2rem;">
                <div class="w-100">
                    <h5 class="modal-title fw-black m-0" id="traineesModalLabel" style="font-family:'Cairo';">عرض تفاصيل المتربصين حسب ولاية الإقامة</h5>
                    <p class="mb-0 text-white-50 small mt-1 fw-semibold" id="traineesSectionTitle"></p>
                </div>
                <button type="button" class="btn-close btn-close-white ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background:#f8fafc;max-height:60vh;overflow-y:auto;" id="traineesModalBody">
                <!-- Content will be injected dynamically via JS -->
            </div>
            <div class="modal-footer border-0 bg-light px-4 py-3">
                <button type="button" class="btn fw-semibold px-4 py-2" style="border-radius:30px;background:#fff;border:1.5px solid #cbd5e1;color:#475569;" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
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
.wilaya-group-header {
    background: #e2e8f0;
    color: #1e293b;
    padding: 0.6rem 1rem;
    font-weight: 700;
    border-radius: 8px;
    margin-top: 1.5rem;
    margin-bottom: 0.8rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.88rem;
}
@media print {
    body { background:#fff !important; }
    .no-print { display:none !important; }
    .table-responsive-wrapper { overflow:visible !important; border:none !important; box-shadow:none !important; }
    .table { width: 100% !important; font-size: 0.65rem !important; }
    .table th { background:#0f172a !important; color:#fff !important; -webkit-print-color-adjust: exact; }
}
</style>

<script>
    // Execute on page load to filter institutions
    document.addEventListener("DOMContentLoaded", function() {
        filterEtablissements();
    });

    /**
     * Filters etablissements select box by selected wilaya_id
     */
    function filterEtablissements() {
        const wilayaId = document.getElementById('filter_wilaya_id').value;
        const etabSelect = document.getElementById('filter_etab_id');
        if (!etabSelect) return;
        
        const options = etabSelect.options;
        let selectedStillVisible = false;
        
        for (let i = 0; i < options.length; i++) {
            const opt = options[i];
            const optWilaya = opt.getAttribute('data-wilaya');
            
            if (!wilayaId || !optWilaya || optWilaya === wilayaId) {
                opt.style.display = '';
                if (opt.selected) selectedStillVisible = true;
            } else {
                opt.style.display = 'none';
                if (opt.selected) opt.selected = false;
            }
        }
        
        // If the selected option is now hidden, reset to default empty value
        if (!selectedStillVisible && etabSelect.value !== '') {
            etabSelect.value = '';
        }
    }

    /**
     * Show Modal and load trainees dynamically grouped by Wilaya
     */
    function showTraineesModal(sectionId, sectionName) {
        document.getElementById('traineesSectionTitle').innerText = sectionName;
        const body = document.getElementById('traineesModalBody');
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted fw-bold">جاري تحميل وتوزيع قائمة المتربصين...</p>
            </div>
        `;
        
        // Show Modal
        const myModal = new bootstrap.Modal(document.getElementById('traineesModal'));
        myModal.show();
        
        // Fetch via AJAX
        fetch(`{{ url('dashboard/pedagogical-activity-report/section-trainees') }}?section_id=${sectionId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    body.innerHTML = `<div class="alert alert-danger border-0">${data.message}</div>`;
                    return;
                }
                
                const trainees = data.trainees;
                if (trainees.length === 0) {
                    body.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users-slash fs-2 mb-3"></i>
                            <p class="fw-bold">لا يوجد أي متربصين مسجلين في هذا الفوج حالياً.</p>
                        </div>
                    `;
                    return;
                }
                
                // Group trainees by Wilaya
                const grouped = {};
                trainees.forEach(t => {
                    if (!grouped[t.wilaya_name]) {
                        grouped[t.wilaya_name] = [];
                    }
                    grouped[t.wilaya_name].push(t);
                });
                
                // Render HTML Grouped by Wilaya
                let html = '';
                for (const wilaya in grouped) {
                    const list = grouped[wilaya];
                    html += `
                        <div class="wilaya-group-header">
                            <span>📍 ${wilaya}</span>
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1.2">${list.length} متربصين</span>
                        </div>
                        <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                            <table class="table align-middle m-0 text-right" style="font-size:0.8rem;">
                                <thead style="background:#f1f5f9;color:#475569;">
                                    <tr>
                                        <th class="py-2.5 px-3" style="width:25%;">رقم التسجيل</th>
                                        <th class="py-2.5 px-3">الاسم واللقب</th>
                                        <th class="py-2.5 px-3 text-center" style="width:20%;">الجنس</th>
                                        <th class="py-2.5 px-3 text-center" style="width:20%;">حالة الانتساب</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    list.forEach(t => {
                        html += `
                            <tr>
                                <td class="px-3"><code>${t.matricule || '—'}</code></td>
                                <td class="px-3 fw-bold text-dark">${t.nom} ${t.prenom}</td>
                                <td class="px-3 text-center"><span class="badge bg-light text-dark border px-2.5 py-1.5">${t.sexe}</span></td>
                                <td class="px-3 text-center">
                                    <span class="badge rounded-pill px-2.5 py-1.5 ${t.statut === 'نشط' ? 'bg-success-light text-success' : 'bg-danger-light text-danger'}" style="background:${t.statut === 'نشط' ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)'};">
                                        ${t.statut}
                                    </span>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                }
                
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `<div class="alert alert-danger border-0">حدث خطأ اتصال: ${err.message}</div>`;
            });
    }
</script>

@endsection
