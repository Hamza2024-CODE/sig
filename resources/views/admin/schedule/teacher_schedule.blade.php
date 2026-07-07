@extends('layouts.main')
@section('title', 'جدول توقيت الأستاذ: ' . htmlspecialchars($teacher->nom . ' ' . $teacher->prenom))
@section('content')

<?php
$dayNames = [
    1 => 'الأحد',
    2 => 'الاثنين',
    3 => 'الثلاثاء',
    4 => 'الأربعاء',
    5 => 'الخميس'
];

// Group slots by day
$slotsByDay = [];
foreach ($dayNames as $num => $name) {
    $slotsByDay[$num] = [];
}
foreach ($slots as $slot) {
    $dayNum = (int)$slot->jour_num;
    if (isset($slotsByDay[$dayNum])) {
        $slotsByDay[$dayNum][] = $slot;
    }
}
?>

<div class="container-fluid py-4" style="direction: rtl; text-align: right; font-family: 'Cairo', sans-serif;">
    <div class="row">
        <div class="col-12 col-xl-10 mx-auto">

            <!-- Print Controls (No Print) -->
            <div class="no-print d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="fa-solid fa-calendar-check text-primary me-2"></i> جدول استعمال الزمن الخاص بالأستاذ
                    </h1>
                    <p class="text-muted mb-0">عرض وطباعة الحصص الزمنية المسندة للأستاذ خلال الأسبوع الدراسي.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/dashboard/schedule" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fa-solid fa-arrow-left me-1"></i> العودة لجدول الحصص
                    </a>
                    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-print me-1"></i> طباعة جدول الأستاذ
                    </button>
                </div>
            </div>

            <!-- Schedule Paper (Print Wrapper) -->
            <div class="print-paper bg-white p-5 rounded-4 shadow-sm border border-light">
                
                <!-- Sovereign Header -->
                <div class="text-center pb-3 border-bottom border-3 border-double mb-4">
                    <h5 class="fw-bold text-dark mb-1">الجمهورية الجزائرية الديمقراطية الشعبية</h5>
                    <h6 class="text-muted mb-3" style="font-family: 'Outfit';">République Algérienne Démocratique et Populaire</h6>
                    <h5 class="fw-bold text-dark mb-1">وزارة التكوين والتعليم المهنيين</h5>
                    <h6 class="text-muted mb-4" style="font-family: 'Outfit';">Ministère de la Formation et de l'Enseignement Professionnels</h6>
                    
                    <div class="d-flex justify-content-between mt-3 text-start fs-6">
                        <div>
                            <strong>الأستاذ:</strong> <span class="fw-bold text-primary">{{ htmlspecialchars($teacher->nom . ' ' . $teacher->prenom) }}</span>
                        </div>
                        <div>
                            <strong>الرتبة:</strong> <span class="fw-bold text-secondary">{{ htmlspecialchars($teacher->grade_nom ?? 'مكوّن') }}</span>
                        </div>
                        <div>
                            <strong>المؤسسة:</strong> <span class="fw-bold text-dark">{{ htmlspecialchars($teacher->etab_nom) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Schedule Title -->
                <div class="bg-dark text-white rounded-3 p-3 text-center mb-4">
                    <h4 class="fw-bold mb-0">مخطط التوزيع الأسبوعي للحصص الزمنية للمكون</h4>
                    <span class="small" style="font-family: 'Outfit'; opacity: 0.85;">Emploi du Temps Hebdomadaire de l'Enseignant</span>
                </div>

                <!-- Schedule Table Grid -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center border-secondary">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;" class="fw-bold">اليوم</th>
                                <th style="width: 85%;" class="fw-bold">الحصص الزمنية المقررة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dayNames as $num => $name)
                                <tr>
                                    <td class="fw-bold table-light py-4 fs-5">{{ $name }}</td>
                                    <td>
                                        @if(empty($slotsByDay[$num]))
                                            <span class="text-muted italic small">لا توجد حصص مدرجة</span>
                                        @else
                                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                                @foreach($slotsByDay[$num] as $slot)
                                                    <div class="p-3 border rounded-3 bg-light text-start shadow-xs position-relative" style="min-width: 240px; font-size: 0.88rem;">
                                                        <div class="fw-bold text-primary mb-1">
                                                            <i class="fa-solid fa-book-open me-1"></i> {{ htmlspecialchars($slot->matiere_ar) }}
                                                        </div>
                                                        <div class="text-dark mb-1">
                                                            <i class="fa-solid fa-users me-1"></i> <strong>القسم:</strong> {{ htmlspecialchars($slot->section_nom) }}
                                                        </div>
                                                        <div class="text-secondary small mb-1">
                                                            <i class="fa-solid fa-layer-group me-1"></i> {{ htmlspecialchars($slot->spec_ar) }}
                                                        </div>
                                                        <div class="text-muted small mb-1">
                                                            <i class="fa-solid fa-clock me-1"></i> {{ date('H:i', strtotime($slot->heure_debut)) }} - {{ date('H:i', strtotime($slot->heure_fin)) }}
                                                        </div>
                                                        @if(!empty($slot->salle))
                                                            <div class="badge bg-secondary text-white mt-1">
                                                                <i class="fa-solid fa-location-dot me-1"></i> {{ htmlspecialchars($slot->salle) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Print Footer Stamp -->
                <div class="row mt-5 pt-4 text-center fs-6">
                    <div class="col-6">
                        <strong>إمضاء الأستاذ المكون</strong>
                        <div style="height: 80px;"></div>
                    </div>
                    <div class="col-6">
                        <strong>توقيع وختم المدير الفرعي للدراسات</strong>
                        <div style="height: 80px;"></div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<style>
/* Print Styles */
@media print {
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .no-print {
        display: none !important;
    }
    .print-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
    .table-bordered th, .table-bordered td {
        border-color: #000 !important;
    }
}
</style>

@endsection
