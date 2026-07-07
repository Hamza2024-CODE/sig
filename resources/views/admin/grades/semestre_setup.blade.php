@extends('layouts.main')
@section('title', 'تهيئة وإعداد السداسي الموالي')
@section('content')

<div class="container-fluid py-4" style="direction: rtl; text-align: right; font-family: 'Cairo', sans-serif;">
    <div class="row">
        <div class="col-12 col-xl-10 mx-auto">
            
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="fa-solid fa-gears text-primary me-2"></i> تهيئة وإعداد السداسي الموالي
                    </h1>
                    <p class="text-muted mb-0">ضبط تواريخ بداية ونهاية السداسي الجديد وتعيين الطاقم البيداغوجي (المكونين) لكل مقياس.</p>
                </div>
                <a href="/dashboard/grades" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="fa-solid fa-arrow-left me-1"></i> العودة للمداولات
                </a>
            </div>

            <!-- Flash Messages -->
            @if(session('flash_success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> {{ session('flash_success') }}
                    <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('flash_error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('flash_error') }}
                    <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Info Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3" style="color: #38bdf8;">
                        <i class="fa-solid fa-circle-info me-1"></i> معلومات الدفعة والقسم المستهدف:
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">التخصص:</span>
                            <span class="fw-semibold text-white fs-5">{{ htmlspecialchars($offre['spec_ar']) }}</span>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">القسم / الفوج:</span>
                            <span class="fw-semibold text-white fs-5">{{ htmlspecialchars($offre['section_nom']) }}</span>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <span class="text-muted d-block small">المؤسسة التكوينية:</span>
                            <span class="fw-semibold text-white fs-5">{{ htmlspecialchars($offre['etab_nom']) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Setup Form -->
            <form action="{{ route('grades.semestre-setup.save') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="offre_id" value="{{ \App\Helpers\SecureIdHelper::encrypt($offre['id']) }}">
                <input type="hidden" name="semestre" value="{{ $semestre }}">

                <div class="row g-4">
                    
                    <!-- Right Column: Semester Dates & General Info -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-transparent border-0 pt-4 px-4">
                                <h5 class="fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-calendar-days text-primary me-1"></i> فترات السداسي الجديد
                                </h5>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="alert alert-light border-0 rounded-3 mb-4 py-2">
                                    <span class="fw-bold text-primary fs-5">السداسي {{ $semestre }}</span>
                                    <span class="text-muted d-block small">يرجى تحديد فترة التكوين الفعالة.</span>
                                </div>

                                <div class="mb-3">
                                    <label for="date_debut" class="form-label fw-bold text-secondary">تاريخ البداية (من):</label>
                                    <input type="date" name="date_debut" id="date_debut" class="form-control rounded-3" 
                                           value="{{ !empty($semestreDetails->date_debut) ? date('Y-m-d', strtotime($semestreDetails->date_debut)) : '' }}" required>
                                    <div class="invalid-feedback">يرجى إدخال تاريخ بداية صالح.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="date_fin" class="form-label fw-bold text-secondary">تاريخ النهاية (إلى):</label>
                                    <input type="date" name="date_fin" id="date_fin" class="form-control rounded-3" 
                                           value="{{ !empty($semestreDetails->date_fin) ? date('Y-m-d', strtotime($semestreDetails->date_fin)) : '' }}" required>
                                    <div class="invalid-feedback">يرجى إدخال تاريخ نهاية صالح.</div>
                                </div>

                                <div class="border-top pt-3 text-center">
                                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold fs-5 shadow-sm">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> حفظ وإعداد التوقيت
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Left Column: Teacher Assignments for Modules -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-chalkboard-user text-primary me-1"></i> إسناد المقياس للمكونين
                                </h5>
                                <span class="badge bg-primary rounded-pill px-3">{{ count($modules) }} مواد / مقاييس</span>
                            </div>
                            <div class="card-body p-4">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle border-0">
                                        <thead>
                                            <tr class="table-light">
                                                <th class="border-0 rounded-start-3" style="width: 50%;">اسم المادة / المقياس</th>
                                                <th class="border-0 text-center" style="width: 15%;">المعامل</th>
                                                <th class="border-0 rounded-end-3" style="width: 35%;">المكون المسند إليه</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(empty($modules))
                                                <tr>
                                                    <td colspan="3" class="text-center py-4 text-muted">
                                                        لا توجد مواد مسجلة لهذا السداسي.
                                                    </td>
                                                </tr>
                                            @else
                                                @foreach($modules as $mod)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold text-dark">{{ htmlspecialchars($mod->nom_ar) }}</div>
                                                            <span class="text-muted small" style="font-family: 'Outfit';">{{ htmlspecialchars($mod->nom_fr) }}</span>
                                                        </td>
                                                        <td class="text-center fw-bold fs-5 text-secondary">
                                                            {{ $mod->coef }}
                                                        </td>
                                                        <td>
                                                            <select name="teachers[{{ $mod->id }}]" class="form-select rounded-3 border-light bg-light fw-semibold" required>
                                                                <option value="0" {{ (int)$mod->IDEncadrement === 0 ? 'selected' : '' }}>-- لم يتم التعيين بعد --</option>
                                                                @foreach($teachers as $teacher)
                                                                    <option value="{{ $teacher->id }}" {{ (int)$mod->IDEncadrement === (int)$teacher->id ? 'selected' : '' }}>
                                                                        {{ htmlspecialchars($teacher->nom . ' ' . $teacher->prenom) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<script>
// Enable Bootstrap validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

@endsection
