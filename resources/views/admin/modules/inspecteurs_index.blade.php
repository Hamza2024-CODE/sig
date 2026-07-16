@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<div class="animate__animated animate__fadeIn">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-user-shield text-primary me-2"></i> سجل المفتشين والزيارات التفتيشية / Inspecteurs
            </h3>
            <p class="text-muted mb-0 small">متابعة المفتشين البيداغوجيين، مقاطعاتهم الإدارية، وإحصائيات الزيارات التفتيشية المنجزة</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('evaluation.gestion') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i> الرجوع إلى لجان التقييم
            </a>
        </div>
    </div>

    <!-- Table of Inspectors -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users-gear text-primary me-2"></i> دليل المفتشين والخبراء البيداغوجيين المعتمدين</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('inspectorsTable', 'dossiers_inspecteurs.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="inspectorsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4" style="width: 25%;">اسم المفتش الكامل</th>
                            <th style="width: 25%;">الرتبة والصفة</th>
                            <th style="width: 25%;">المقاطعة الإدارية (الولايات المغطاة)</th>
                            <th class="text-center" style="width: 12%;">إجمالي التفتيشات</th>
                            <th class="text-center" style="width: 13%;">التقييم البيداغوجي العام</th>
                            <th class="pe-4 text-end" style="width: 10%;">الخيارات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($list))
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-regular fa-folder-open fs-3 mb-2 d-block"></i>
                                    لم يتم العثور على مفتشين بيداغوجيين مسجلين حالياً.
                                </td>
                            </tr>
                        @else
                            @foreach($list as $item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); font-size: 0.9rem;">
                                                {{ mb_substr($item['name'], 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ htmlspecialchars($item['name']) }}</div>
                                                <div class="text-muted small">المعرف: INS-{{ 1000 + $loop->index }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark px-2.5 py-1.5 fw-semibold" style="font-size:0.78rem;">
                                            <i class="fa-solid fa-circle-chevron-left text-primary me-1"></i> {{ htmlspecialchars($item['rank'] ?: 'مفتش المقاطعة') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-muted small" style="font-weight: 500;">
                                            <i class="fa-solid fa-map-pin text-danger me-1"></i> {{ htmlspecialchars($item['wilayas'] ?: 'غير محدد') }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fs-5 fw-bold text-dark">{{ $item['count_inspections'] }}</div>
                                        <div class="text-muted small">زيارات ومحاضر</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fs-5 fw-bold text-success">{{ $item['average_grade'] }} / 20</div>
                                        <div class="text-muted small">متوسط الدفعات</div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('evaluation.inspecteurs.details', ['name' => $item['name']]) }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> تفاصيل الزيارات
                                        </a>
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
