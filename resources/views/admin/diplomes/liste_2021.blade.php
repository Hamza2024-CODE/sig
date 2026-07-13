@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array  $stagiaires
 * @var int    $total_count
 * @var int    $page
 * @var int    $total_pages
 * @var int    $per_page
 * @var string $search
 * @var int    $filter_etab
 * @var string $filter_status
 * @var array  $etablissements
 * @var string $role_code
 */
$from = $total_count > 0 ? ($page - 1) * $per_page + 1 : 0;
$to   = min($page * $per_page, $total_count);
$cursor    = request()->query('cursor', 0);
$filterQs  = http_build_query(array_filter([
    'search'        => $search,
    'filter_etab'   => $filter_etab ?: null,
    'filter_status' => $filter_status !== 'all' ? $filter_status : null,
    'filter_mode'   => $filter_mode ?: null,
    'filter_annee'  => $filter_annee ?: null,
    'filter_spec'   => $filter_spec ?: null,
    'filter_qualif' => $filter_qualif ?: null,
]));
?>
<div class="container-fluid animate__animated animate__fadeIn" style="font-family: 'Cairo', sans-serif;">

    <!-- Sovereign Title Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary border-opacity-10 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--color-gov-purple-dark, #4b154b);">سجل وشواهد خريجي الفترة (منذ 2021) / Registre des Diplômés (Depuis 2021)</h3>
            <p class="text-muted small mb-0">المداولات النهائية واستخراج الشهادات الرسمية لخريجي دفعات الفترة الممتدة من سنة 2021 إلى يومنا هذا</p>
        </div>
        <div class="badge bg-primary-subtle text-primary px-3 py-2 fw-bold" style="font-size:0.9rem; border-radius:30px;">
            <i class="fa-solid fa-graduation-cap me-1"></i> إجمالي الشهادات المطبوعة: {{ number_format($issuedCount) }} شهادة دولة
        </div>
    </div>

    <!-- Alert messages -->
    @if (session()->has('flash_success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #e6f7ed; color: #006233;">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('flash_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('flash_error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background: #fdf2f2; color: #9b1c1c;">
            <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('flash_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Advanced Filter Bar -->
    <div class="card border-0 shadow-sm p-4 mb-4 no-print" style="border-radius:16px; background:var(--card-bg, #ffffff); border:1px solid var(--card-border, #f1f5f9)!important;">
        <form method="GET" action="{{ url('dashboard/diplomes/liste-2021-present') }}" class="row g-3 align-items-end">

            <!-- Search input -->
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">بحث باسم المتربص أو رقم التسجيل</label>
                <div class="input-group">
                    <span class="input-group-text border-0" style="background: var(--input-bg, #f1f5f9);">
                        <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                           class="form-control border-0 rounded-end" placeholder="الاسم، اللقب، رقم التسجيل..."
                           style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                </div>
            </div>

            <!-- Etablissement dropdown -->
            @if(count($etablissements) > 1)
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية</label>
                <select name="filter_etab" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل المؤسسات</option>
                    @foreach($etablissements as $e)
                    <option value="{{ $e['id'] }}" {{ $filter_etab == $e['id'] ? 'selected' : '' }}>
                        {{ $e['nom_ar'] ?? $e['nom'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية</label>
                <input type="text" class="form-control border-0 rounded text-muted" readonly 
                       value="{{ !empty($etablissements) ? ($etablissements[0]['nom_ar'] ?? $etablissements[0]['nom'] ?? '') : 'المؤسسة الخاصة بك' }}"
                       style="background: var(--input-bg, #e2e8f0); font-size: 0.88rem;">
            </div>
            @endif

            <!-- Status dropdown -->
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">حالة الشهادة</label>
                <select name="filter_status" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="all" {{ $filter_status === 'all' ? 'selected' : '' }}>الكل</option>
                    <option value="issued" {{ $filter_status === 'issued' ? 'selected' : '' }}>جاهز للتسليم</option>
                    <option value="not_issued" {{ $filter_status === 'not_issued' ? 'selected' : '' }}>لم تحرر بعد</option>
                </select>
            </div>

            <!-- Mode of formation dropdown -->
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">نمط التكوين</label>
                <select name="filter_mode" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل الأنماط</option>
                    @foreach($modes as $m)
                    <option value="{{ $m['id'] }}" {{ $filter_mode == $m['id'] ? 'selected' : '' }}>
                        {{ $m['libelle_ar'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Year dropdown -->
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">السنة الدراسية</label>
                <select name="filter_annee" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل السنوات</option>
                    @foreach($annees as $an)
                    <option value="{{ $an['id'] }}" {{ $filter_annee == $an['id'] ? 'selected' : '' }}>
                        {{ $an['libelle_ar'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Specialty dropdown -->
            <div class="col-12 col-md-5">
                <label class="form-label small fw-bold text-muted mb-1">التخصص / الشهادة</label>
                <select name="filter_spec" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل التخصصات / الشهادات</option>
                    @foreach($specialites as $sp)
                    <option value="{{ $sp['id'] }}" {{ $filter_spec == $sp['id'] ? 'selected' : '' }}>
                        {{ $sp['code'] }} - {{ $sp['libelle_ar'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Qualification Type dropdown -->
            <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">نوع الشهادة</label>
                <select name="filter_qualif" class="form-select border-0 rounded"
                        style="background: var(--input-bg, #f1f5f9); font-size: 0.88rem;">
                    <option value="0">كل أنواع الشهادات</option>
                    @foreach($qualifications as $q)
                    <option value="{{ $q['id'] }}" {{ $filter_qualif == $q['id'] ? 'selected' : '' }}>
                        {{ $q['libelle_ar'] }} ({{ $q['code'] }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit and Reset Buttons -->
            <div class="col-12 col-md-3 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold flex-grow-1" style="font-size:0.88rem; background: var(--color-gov-purple-dark, #4a154b); border: none;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ url('dashboard/diplomes') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold" style="font-size:0.88rem;">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Final Deliberation Table -->
    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-gavel text-primary me-1"></i> كشف مداولات اللجنة والتقييم النهائي للمتربصين</h5>
            <div class="d-flex gap-2 no-print flex-wrap">
                <button onclick="exportTableToExcel('diplomesTable', 'diplomes_deliberations.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('diplomesTable', 'diplomes_deliberations.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
                <!-- ═══ BULK PRINT BUTTON ═══ -->
                <button id="btn-batch-print"
                        onclick="openBatchPrint()"
                        class="btn btn-sm rounded-pill px-3 fw-bold shadow-sm text-white"
                        style="background: linear-gradient(135deg,#006233 0%,#10b981 100%); border:none; display:none;"
                        title="طباعة الشهادات المحددة دفعة واحدة">
                    <i class="fa-solid fa-layer-group me-1"></i>
                    <span id="batch-count-label">طباعة المحدد</span>
                </button>
                <button onclick="openBatchPrintAll()"
                        class="btn btn-sm rounded-pill px-3 fw-bold shadow-sm text-white"
                        style="background: linear-gradient(135deg,#1e3a5f 0%,#3b82f6 100%); border:none;"
                        title="طباعة جميع الشهادات المحررة في الصفحة الحالية">
                    <i class="fa-solid fa-print me-1"></i> 🖨️ طباعة الكل
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0" id="diplomesTable">
                <thead class="table-light">
                    <tr class="fw-bold text-muted" style="font-size:0.85rem;">
                        <th class="no-print no-export" style="width:36px;">
                            <input type="checkbox" id="select-all-diplomas" title="تحديد الكل"
                                   style="width:16px;height:16px;cursor:pointer;"
                                   onchange="toggleSelectAll(this)">
                        </th>
                        <th>رقم التسجيل / Matricule</th>
                        <th class="text-right">المتكون / Trainee</th>
                        <th class="text-right">التخصص / Spécialité</th>
                        <th>المعدل التراكمي النهائي</th>
                        <th>قرار اللجنة</th>
                        <th>الشهادة الرسمية / Diplôme</th>
                        <th class="no-print no-export">العمليات الإدارية</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($stagiaires))
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 d-block mb-3 opacity-25"></i>
                            لا توجد نتائج مطابقة للبحث أو الفلترة.
                        </td>
                    </tr>
                    @else
                    @foreach ($stagiaires as $s)
                        <tr>
                            <td class="no-print no-export" style="width:36px;">
                                @if ($s['numero_diplome'])
                                <input type="checkbox" class="diploma-check" value="{{ $s['diplome_id'] }}"
                                       style="width:16px;height:16px;cursor:pointer;"
                                       onchange="updateBatchBtn()">
                                @endif
                            </td>
                            <td class="fw-bold text-dark" style="font-family:'Outfit';">{{ htmlspecialchars($s['numero_matricule']) }}</td>
                            <td class="text-right">
                                <strong class="d-block text-dark">{{ htmlspecialchars($s['nom_ar'] . ' ' . $s['prenom_ar']) }}</strong>
                                <small class="text-muted">{{ htmlspecialchars($s['prenom_fr'] . ' ' . $s['nom_fr']) }}</small>
                            </td>
                            <td class="text-right">
                                <span class="d-block text-dark small">{{ htmlspecialchars($s['spec_ar']) }}</span>
                                <small class="text-muted" style="font-size:0.75rem;">{{ htmlspecialchars($s['spec_fr']) }}</small>
                            </td>
                            <td class="fw-bold text-primary fs-5" style="font-family:'Outfit';">
                                {{ $s['moyenne_generale'] ? number_format($s['moyenne_generale'], 2) . ' / 20' : 'قيد الحساب...' }}
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success px-3 py-1.5" style="border-radius:20px;">ناجح مؤهل / Admis</span>
                            </td>
                            <td>
                                @if ($s['numero_diplome'])
                                    <span class="badge bg-primary px-3 py-1.5" style="border-radius:6px; font-family:'Outfit';">{{ $s['numero_diplome'] }}</span>
                                    <small class="d-block text-success fw-bold mt-1" style="font-size:0.75rem;"><i class="fa-solid fa-circle-check"></i> جاهز للتسليم</small>
                                @else
                                    <span class="badge bg-warning-subtle text-warning px-3 py-1.5" style="border-radius:20px;">لم تحرر بعد</span>
                                @endif
                            </td>
                            <td class="no-print no-export">
                                @if (!$s['numero_diplome'])
                                    <a href="{{ url('dashboard/diplomes/generate/' . $s['id']) }}" class="btn btn-warning btn-sm fw-bold px-3 py-2 text-dark" style="border-radius:8px;">
                                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> احتساب المداولة والتحرير
                                    </a>
                                @else
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ url('dashboard/diplomes/print/' . $s['diplome_id']) }}" target="_blank" class="btn btn-success btn-sm fw-bold px-2 py-1.5" style="border-radius:8px; background:linear-gradient(135deg, #006233 0%, #10b981 100%);" title="طباعة شهادة الدولة">
                                            <i class="fa-solid fa-print"></i> طباعة
                                        </a>
                                        <button class="btn btn-outline-primary btn-sm fw-bold px-2 py-1.5" style="border-radius:8px;" onclick="openEditDiplomaModal({{ $s['diplome_id'] }})" title="تعديل بيانات الشهادة">
                                            <i class="fa-solid fa-pen-to-square"></i> تعديل
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm fw-bold px-2 py-1.5" style="border-radius:8px;" onclick="confirmDeleteDiploma({{ $s['diplome_id'] }}, '{{ htmlspecialchars($s['nom_ar'] . ' ' . $s['prenom_ar']) }}')" title="إلغاء وحذف الشهادة">
                                            <i class="fa-solid fa-trash-can"></i> إلغاء
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Keyset Pagination (cursor-based — every page equally fast) -->
        <div class="p-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-top no-print mt-3"
             style="border-color: #f1f5f9 !important;">
            <small class="text-muted">
                @if ($total_count > 0)
                    إجمالي: <strong>{{ number_format($total_count) }}</strong> متربص
                    (صفحة {{ $page }})
                @else
                    <span class="text-warning"><i class="fa-solid fa-circle-info me-1"></i>جاري احتساب الإجمالي...</span>
                @endif
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0 gap-1">
                    <!-- Previous page (first page reset) -->
                    @if ($page > 1)
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="{{ url('dashboard/diplomes') }}?page=1&{{ $filterQs }}">
                            <i class="fa-solid fa-angles-right" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                    @endif

                    <!-- Current page indicator -->
                    <li class="page-item active">
                        <span class="page-link rounded-pill px-3 fw-bold">{{ $page }}</span>
                    </li>

                    <!-- Next page (cursor) -->
                    @if ($has_more)
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3 fw-bold"
                           href="{{ url('dashboard/diplomes') }}?page={{ $page + 1 }}&cursor={{ $next_cursor }}&{{ $filterQs }}">
                            <i class="fa-solid fa-chevron-left" style="font-size:0.7rem;"></i>
                        </a>
                    </li>
                    @else
                    <li class="page-item disabled">
                        <span class="page-link rounded-pill px-3">
                            <i class="fa-solid fa-chevron-left" style="font-size:0.7rem;"></i>
                        </span>
                    </li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>

</div>

<!-- Edit Diploma Modal -->
<div class="modal fade" id="editDiplomaModal" tabindex="-1" aria-labelledby="editDiplomaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between">
                <h5 class="modal-title fw-bold text-dark" id="editDiplomaModalLabel">
                    <i class="fa-solid fa-edit text-primary me-1"></i> تعديل بيانات شهادة التخرج
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div id="editSpinner" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="text-muted small mt-2">جاري جلب تفاصيل الشهادة من قاعدة البيانات...</p>
            </div>

            <form id="editDiplomaForm" action="{{ url('dashboard/diplomes/update') }}" method="POST">
                <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" id="edit_diplome_id">
                
                <div class="modal-body p-4 d-none" id="editFormFields">
                    <!-- Info Card -->
                    <div class="p-3 mb-3 border-0 bg-primary-subtle text-primary rounded" style="border-radius:12px;">
                        <span class="small d-block text-muted">المتربص / المتكون:</span>
                        <strong class="d-block" id="edit_trainee_name"></strong>
                    </div>

                    <div class="row g-3 text-right">
                        <!-- Numero Diplome -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">رقم الشهادة *</label>
                            <input type="text" name="numero_diplome" id="edit_numero_diplome" class="form-control border-0 bg-light" required style="border-radius:10px;">
                        </div>

                        <!-- Num Serie -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">رقم التسلسل (Série) *</label>
                            <input type="text" name="num_serie" id="edit_num_serie" class="form-control border-0 bg-light" required style="border-radius:10px;">
                        </div>

                        <!-- Moyenne Generale -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">المعدل التراكمي العام *</label>
                            <input type="number" step="0.01" min="0" max="20" name="moyenne_generale" id="edit_moyenne_generale" class="form-control border-0 bg-light" required style="border-radius:10px;">
                        </div>

                        <!-- Date Deliberation -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">تاريخ إصدار الشهادة *</label>
                            <input type="date" name="date_diplome" id="edit_date_diplome" class="form-control border-0 bg-light" required style="border-radius:10px;">
                        </div>

                        <!-- Num PV Fin -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">رقم محضر المداولة</label>
                            <input type="number" name="num_pv_fin" id="edit_num_pv_fin" class="form-control border-0 bg-light" style="border-radius:10px;">
                        </div>

                        <!-- Date PV Fin -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">تاريخ محضر المداولة</label>
                            <input type="date" name="date_pv_fin" id="edit_date_pv_fin" class="form-control border-0 bg-light" style="border-radius:10px;">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 pt-0 pb-4 px-4 bg-transparent d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background:var(--color-gov-purple-dark); border:none;">
                        <i class="fa-solid fa-save me-1"></i> حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ DELETE FORM (HIDDEN) ══ --}}
<form id="deleteDiplomaForm" action="" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="{{ csrf_token() }}">
</form>

<script>
function openEditDiplomaModal(id) {
    var modalEl = document.getElementById('editDiplomaModal');
    var modal = new bootstrap.Modal(modalEl);
    modal.show();

    document.getElementById('editSpinner').classList.remove('d-none');
    document.getElementById('editFormFields').classList.add('d-none');

    fetch('{{ url("dashboard/diplomes/show") }}/' + id)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            document.getElementById('edit_diplome_id').value = data.id;
            document.getElementById('edit_trainee_name').textContent = data.nom_ar + ' ' + data.prenom_ar + ' (' + data.prenom_fr + ' ' + data.nom_fr + ')';
            document.getElementById('edit_numero_diplome').value = data.numero_diplome || '';
            document.getElementById('edit_num_serie').value = data.num_serie || '';
            document.getElementById('edit_moyenne_generale').value = data.moyenne_generale || '';
            document.getElementById('edit_date_diplome').value = data.date_diplome || '';
            document.getElementById('edit_num_pv_fin').value = data.num_pv_fin || '';
            document.getElementById('edit_date_pv_fin').value = data.date_pv_fin || '';

            document.getElementById('editSpinner').classList.add('d-none');
            document.getElementById('editFormFields').classList.remove('d-none');
        })
        .catch(err => {
            alert('حدث خطأ أثناء تحميل بيانات الشهادة. يرجى المحاولة لاحقاً.');
            modal.hide();
        });
}

function confirmDeleteDiploma(id, name) {
    if (confirm("هل أنت متأكد من إلغاء شهادة التخرج للمتربص " + name + "؟ سيتم حذف سجل التوليد وتعديل حالته ليتمكن من توليد الشهادة من جديد.")) {
        var form = document.getElementById('deleteDiplomaForm');
        form.action = '{{ url("dashboard/diplomes/delete") }}/' + id;
        form.submit();
    }
}

// ══════════════════════════════════════════════════════════════
// Bulk Diploma Print — Select All / Batch Print
// ══════════════════════════════════════════════════════════════

function toggleSelectAll(masterCb) {
    document.querySelectorAll('.diploma-check').forEach(function(cb) {
        cb.checked = masterCb.checked;
    });
    updateBatchBtn();
}

function updateBatchBtn() {
    var checked = document.querySelectorAll('.diploma-check:checked');
    var btn = document.getElementById('btn-batch-print');
    var label = document.getElementById('batch-count-label');
    if (checked.length > 0) {
        btn.style.display = '';
        label.textContent = '🖨️ طباعة المحدد (' + checked.length + ')';
    } else {
        btn.style.display = 'none';
    }
    // sync master checkbox state
    var all = document.querySelectorAll('.diploma-check');
    var master = document.getElementById('select-all-diplomas');
    if (master) {
        master.indeterminate = (checked.length > 0 && checked.length < all.length);
        master.checked = (all.length > 0 && checked.length === all.length);
    }
}

function openBatchPrint() {
    var checked = document.querySelectorAll('.diploma-check:checked');
    if (checked.length === 0) {
        alert('يرجى تحديد شهادة واحدة على الأقل أولاً.');
        return;
    }
    var ids = Array.from(checked).map(function(cb) { return cb.value; }).join(',');
    var url = '{{ url("dashboard/diplomes/print-batch") }}?ids=' + ids;
    window.open(url, '_blank');
}

function openBatchPrintAll() {
    // Collect all visible issued diploma IDs from the current page
    var allChecks = document.querySelectorAll('.diploma-check');
    if (allChecks.length === 0) {
        alert('لا توجد شهادات محررة في هذه الصفحة.');
        return;
    }
    var ids = Array.from(allChecks).map(function(cb) { return cb.value; }).join(',');
    var url = '{{ url("dashboard/diplomes/print-batch") }}?ids=' + ids + '&noprint=1';
    window.open(url, '_blank');
}
</script>
@endsection
