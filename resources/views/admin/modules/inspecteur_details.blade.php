@extends('layouts.main')
@section('title', $title ?? 'تفاصيل زيارات المفتش')
@section('content')
<div class="animate__animated animate__fadeIn">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-user-ninja text-primary me-2"></i> تفاصيل زيارات المفتش: {{ htmlspecialchars($info['name']) }}
            </h3>
            <p class="text-muted mb-0 small">سجل الزيارات الميدانية والمحاضر البيداغوجية الموقعة من طرف المفتش</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('evaluation.inspecteurs') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i> الرجوع لسجل المفتشين
            </a>
        </div>
    </div>

    <!-- Inspector Info Card -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center gap-4">
                    <div class="avatar-large bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); font-size: 1.8rem; font-family: 'Cairo'; shadow: 0 4px 15px rgba(72,43,143,0.2);">
                        {{ mb_substr($info['name'], 0, 1) }}
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-1">{{ htmlspecialchars($info['name']) }}</h4>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="badge bg-primary-subtle text-primary px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.85rem;">{{ htmlspecialchars($info['rank']) }}</span>
                            <span class="text-muted small"><i class="fa-solid fa-shield-halved"></i> معتمد لدى مديرية التكوين والتعليم المهنيين</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي التفتيشات والمحاضر</h6>
                    <h2 class="display-6 fw-bold my-1 text-warning">{{ $info['count'] }} زيارة</h2>
                    <span class="small opacity-75"><i class="fa-solid fa-map-location-dot"></i> في مختلف مراكز ومعاهد الولاية</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table of Visits -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-map-location text-primary me-2"></i> سجل الأقسام والمواقع التفتيشية المزارة (تفاصيل التفاصيل)</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('visitsTable', 'inspections_details_{{ $info['name'] }}.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="visitsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4" style="width: 25%;">القسم واللجنة البيداغوجية المشتركة</th>
                            <th style="width: 30%;">التخصص والمؤسسة التكوينية (موقع التفتيش)</th>
                            <th class="text-center" style="width: 15%;">الدورة التكوينية</th>
                            <th class="text-center" style="width: 12%;">المعدل العام للدفعة</th>
                            <th class="pe-4 text-end" style="width: 18%;">نسبة النجاح والتوصية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($visits))
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fa-regular fa-folder-open fs-3 mb-2 d-block"></i>
                                    لا توجد زيارات مسجلة لهذا المفتش حالياً.
                                </td>
                            </tr>
                        @else
                            @foreach($visits as $item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ htmlspecialchars($item['section_nom']) }}</div>
                                        <div class="text-muted small" style="font-size: 0.76rem;">{{ htmlspecialchars($item['other_members']) }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ htmlspecialchars($item['spec_ar']) }}</div>
                                        <div class="text-muted small" style="font-size: 0.78rem;"><i class="fa-solid fa-school text-primary-50 me-1"></i> {{ htmlspecialchars($item['etab_nom']) }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-primary px-2.5 py-1.5 fw-bold" style="font-size: 0.78rem;">
                                            <i class="fa-solid fa-calendar-check me-1"></i> {{ htmlspecialchars($item['session_nom']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="fs-5 fw-bold text-success">{{ $item['average_note'] }} / 20</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">تقييم بيداغوجي</div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <?php
                                        $pct = $item['total_students'] > 0 ? round(($item['admitted_students'] / $item['total_students']) * 100) : 0;
                                        ?>
                                        <span class="badge bg-success rounded-pill px-3 py-2" style="font-size: 0.8rem;">
                                            نسبة النجاح: {{ $pct }}% ({{ $item['admitted_students'] }}/{{ $item['total_students'] }} طالب)
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
