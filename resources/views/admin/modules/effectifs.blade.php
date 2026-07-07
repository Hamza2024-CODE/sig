@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $list
 * @var array $militaryTrainees
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-users text-primary me-2"></i> تسيير التعداد والتعداد الكلي للمتربصين / Effectifs
            </h3>
            <p class="text-muted mb-0 small">متابعة تعداد المتكونين، التوزيع حسب الجنس والأنماط، والمؤشرات الديموغرافية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ url('dashboard/effectifs') }}?pdf=1" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm text-decoration-none" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; height: 38px;"><i class="fa-solid fa-file-pdf"></i> استخراج الكشف الإحصائي</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <!-- 2024 Census -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">تعداد سنة 2024</h6>
                    <h2 class="display-6 fw-bold my-2 text-secondary"><?= number_format($stats['yr_2024'] ?? 0) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-calendar-day"></i> متربص مسجل</span>
                </div>
            </div>
        </div>
        <!-- 2025 Census -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">تعداد سنة 2025</h6>
                    <h2 class="display-6 fw-bold my-2 text-primary"><?= number_format($stats['yr_2025'] ?? 0) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-calendar-days"></i> متربص مسجل</span>
                </div>
            </div>
        </div>
        <!-- 2026 Census -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">تعداد سنة 2026</h6>
                    <h2 class="display-6 fw-bold my-2 text-success"><?= number_format($stats['yr_2026'] ?? 0) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-calendar-plus"></i> متربص مسجل</span>
                </div>
            </div>
        </div>
        <!-- Grand Total -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">التعداد الإجمالي الكلي</h6>
                    <h2 class="display-6 fw-bold my-2 text-warning"><?= number_format($stats['total'] ?? 0) ?></h2>
                    <span class="small"><i class="fa-solid fa-users"></i> المجموع الكلي للسنوات</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-pills mb-4 border-bottom pb-2 no-print gap-2" id="effectifTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="btn btn-outline-primary active rounded-pill px-4 fw-bold shadow-sm" id="general-tab" data-bs-toggle="pill" data-bs-target="#general-pane" type="button" role="tab" aria-controls="general-pane" aria-selected="true">
                <i class="fa-solid fa-chart-simple me-2"></i> توزيع التعداد العام
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm" id="military-tab" data-bs-toggle="pill" data-bs-target="#military-pane" type="button" role="tab" aria-controls="military-pane" aria-selected="false">
                <i class="fa-solid fa-person-military-rifle me-2"></i> ملف الخدمة العسكرية (الذكور)
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="effectifTabsContent">
        <!-- 1. General Census Pane -->
        <div class="tab-pane fade show active" id="general-pane" role="tabpanel" aria-labelledby="general-tab">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-simple text-primary me-2"></i> توزيع التعداد حسب المؤسسات الكبرى</h5>
                    <div class="d-flex gap-2 no-print">
                        <button onclick="exportTableToExcel('effectTable', 'effectifs_etablissements')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-file-excel me-1"></i> Excel
                        </button>
                        <button onclick="exportTableToCSV('effectTable', 'effectifs_etablissements.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-file-csv me-1"></i> CSV
                        </button>
                        <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-print me-1"></i> طباعة
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="effectTable">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-4">المؤسسة التكوينية</th>
                                    <th class="text-center">تعداد سنة 2024</th>
                                    <th class="text-center">تعداد سنة 2025</th>
                                    <th class="text-center">تعداد سنة 2026</th>
                                    <th class="pe-4 text-end">الإجمالي العام</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($list)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i> لا توجد بيانات تعداد حالياً.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($list as $item): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['etab_nom']) ?></div>
                                                <div class="text-muted small">مؤسسة معتمدة بالولاية</div>
                                            </td>
                                            <td class="text-center text-secondary fw-bold"><?= number_format($item['yr_2024']) ?></td>
                                            <td class="text-center text-primary fw-bold"><?= number_format($item['yr_2025']) ?></td>
                                            <td class="text-center text-success fw-bold"><?= number_format($item['yr_2026']) ?></td>
                                            <td class="pe-4 text-end fw-black text-dark" style="font-size: 1.05rem;">
                                                <?= number_format($item['total']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Military Service File Pane -->
        <div class="tab-pane fade" id="military-pane" role="tabpanel" aria-labelledby="military-tab">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-file-invoice text-primary me-2"></i> قوائم المتكونين المعنيين بالخدمة العسكرية (الذكور المزاولون لدراستهم)</h5>
                    <div class="d-flex gap-2 no-print">
                        <button onclick="exportTableToExcel('militaryTable', 'military_service_trainees')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-file-excel me-1"></i> Excel
                        </button>
                        <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-print me-1"></i> طباعة
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="militaryTable">
                            <thead class="bg-light text-muted small fw-bold">
                                <tr>
                                    <th class="ps-4">المتكون / الطالب</th>
                                    <th class="text-center">تاريخ الميلاد</th>
                                    <th class="text-center">المؤسسة التكوينية</th>
                                    <th class="text-center">التخصص</th>
                                    <th class="pe-4 text-end">القسم / الفوج</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($militaryTrainees)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i> لا توجد بيانات لذكور مزاولين لدراستهم حالياً.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($militaryTrainees as $trainee): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($trainee['nom_ar'] . ' ' . $trainee['prenom_ar']) ?></div>
                                                <div class="text-muted small">رقم التسجيل: <?= htmlspecialchars($trainee['numero_matricule'] ?? 'بدون رقم') ?></div>
                                            </td>
                                            <td class="text-center text-muted small"><?= htmlspecialchars($trainee['date_naissance'] ?? 'غير محدد') ?></td>
                                            <td class="text-center fw-semibold"><?= htmlspecialchars($trainee['etab_nom'] ?? '') ?></td>
                                            <td class="text-center text-primary small fw-semibold"><?= htmlspecialchars($trainee['spec_ar'] ?? '') ?></td>
                                            <td class="pe-4 text-end text-secondary small fw-bold"><?= htmlspecialchars($trainee['section_nom'] ?? 'غير محدد') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    filename = filename?filename+'.xls':'excel_data.xls';
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    } else {
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}
function exportTableToCSV(tableID, filename = '') {
    var csv = [];
    var rows = document.querySelectorAll("#" + tableID + " tr");
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) 
            row.push('"' + cols[j].innerText.trim() + '"');
        csv.push(row.join(","));        
    }
    var csvFile = new Blob(["\ufeff" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename ? filename : "export.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
@endsection
