@extends('layouts.main')
@section('title', $title ?? 'لجان مناقشة المذكرات والتخرج')
@section('content')
<div class="animate__animated animate__fadeIn">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-users text-primary me-2"></i> لجان مناقشة المذكرات والتخرج / Juries
            </h3>
            <p class="text-muted mb-0 small">متابعة لجان المناقشة النهائية، رؤساء اللجان، والمشرفين على مشاريع التخرج بقطاع التكوين المهني</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('evaluation.gestion') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i> الرجوع لصفحة التقييم
            </a>
        </div>
    </div>

    <!-- Table of Juries -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-graduation-cap text-primary me-2"></i> سجل الهيئات واللجان العلمية لمناقشة مذكرات التخرج</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('juriesTable', 'dossiers_juries.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="juriesTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4" style="width: 25%;">القسم / الدفعة</th>
                            <th style="width: 20%;">رئيس لجنة المناقشة</th>
                            <th style="width: 25%;">أعضاء لجنة المناقشة</th>
                            <th style="width: 20%;">التخصص والمؤسسة التكوينية</th>
                            <th class="pe-4 text-end" style="width: 10%;">إجمالي الخريجين</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($list))
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fa-regular fa-folder-open fs-3 mb-2 d-block"></i>
                                    لم يتم العثور على لجان مناقشة مسجلة حالياً.
                                </td>
                            </tr>
                        @else
                            @foreach($list as $item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ htmlspecialchars($item['section_nom']) }}</div>
                                        <div class="text-muted small">دورة: {{ htmlspecialchars($item['session_nom']) }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <i class="fa-solid fa-user-tie text-primary me-1"></i> {{ htmlspecialchars($item['president_name'] ?: 'مفتش بيداغوجي') }}
                                        </div>
                                        <div class="text-muted small" style="font-size:0.75rem;">رئيس لجنة المناقشة</div>
                                    </td>
                                    <td>
                                        <div class="text-dark small" style="font-weight: 500; max-height: 50px; overflow-y: auto;">
                                            <i class="fa-solid fa-users text-secondary me-1"></i> {{ htmlspecialchars($item['members_names'] ?: 'غير محدد') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ htmlspecialchars($item['spec_ar']) }}</div>
                                        <div class="text-muted small"><i class="fa-solid fa-school text-muted me-1"></i> {{ htmlspecialchars($item['etab_nom']) }}</div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-success rounded-pill px-3 py-2 fw-semibold" style="font-size:0.8rem;">
                                            {{ $item['admitted_students'] }} خريج ناجح
                                        </span>
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
@endsection
