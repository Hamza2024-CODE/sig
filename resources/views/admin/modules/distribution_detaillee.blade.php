@extends('layouts.main')
@section('title', $title ?? 'التوزيع المفصل للشعب المهنية')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $list
 * @var array $wilayas
 * @var array $etablissements
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-chart-bar text-primary me-2"></i> التوزيع المفصل حسب المؤسسات والشعب المهنية
            </h3>
            <p class="text-muted mb-0 small">إحصائيات الشعب التكوينية والتخصصات المفتوحة موثقة حسب كل مؤسسة تكوينية</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm"><i class="fa-solid fa-print me-2"></i> طباعة التقرير</button>
            <a href="{{ url('dashboard/distribution-detaillee') }}?pdf=1" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm text-decoration-none" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; height: 38px;"><i class="fa-solid fa-chart-line"></i> تقرير التنوع المهني</a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">تصفية حسب الولاية</label>
                    <select name="filter_wilaya" class="form-select rounded-pill" onchange="this.form.submit()">
                        <option value="">-- كل الولايات --</option>
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= (request('filter_wilaya') == $w['id']) ? 'selected' : '' ?>><?= htmlspecialchars($w['nom_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted mb-1">المؤسسة التكوينية</label>
                    <select name="filter_etablissement" class="form-select rounded-pill" onchange="this.form.submit()">
                        <option value="">-- كل المؤسسات --</option>
                        <?php foreach ($etablissements as $et): ?>
                            <option value="<?= $et['id'] ?>" <?= (request('filter_etablissement') == $et['id']) ? 'selected' : '' ?>><?= htmlspecialchars($et['nom_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end pt-3">
                    <a href="{{ url('dashboard/distribution-detaillee') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold w-100">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي الشعب المهنية المفتوحة</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= $stats['filieres'] ?> شعبة</h2>
                    <span class="small"><i class="fa-solid fa-shapes"></i> تغطي مجالات تكنولوجية وخدماتية وفلاحية</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">التخصصات النشطة والمدونة</h6>
                    <h2 class="display-5 fw-bold my-2 text-primary"><?= $stats['specialites'] ?> تخصص</h2>
                    <span class="small text-muted"><i class="fa-solid fa-list-ol text-primary"></i> حسب المدونة الوطنية الرسمية للتخصصات</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">نسبة التغطية والتوجيه</h6>
                    <h2 class="display-5 fw-bold my-2 text-success"><?= $stats['taux_couverture'] ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-circle-check text-success"></i> نسبة كفاءة استغلال المقاعد المفتوحة</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-building text-primary me-2"></i> توزيع الشعب المهنية والاستقطاب الفعلي بالمؤسسات</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('distDetTable', 'distribution_detaillee.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('distDetTable', 'distribution_detaillee.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="distDetTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المؤسسة التكوينية</th>
                            <th>الشعبة المهنية (Filière)</th>
                            <th class="text-center">التخصصات المقترحة</th>
                            <th class="text-center">التعداد الفعلي</th>
                            <th class="text-center">إناث %</th>
                            <th class="pe-4 text-end">التوجيه والنمط</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-circle-info me-2"></i> لا توجد بيانات للمؤسسات حالياً وفقاً للفلاتر المحددة.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark" style="font-size: 0.85rem;">
                                        <i class="fa-solid fa-hotel text-primary me-2 small"></i>
                                        <?= htmlspecialchars($item['etab_nom']) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-secondary"><?= htmlspecialchars($item['filiere_nom']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($item['filiere_fr']) ?></div>
                                    </td>
                                    <td class="text-center fw-bold text-primary"><?= number_format($item['specialites_count']) ?> تخصص</td>
                                    <td class="text-center fw-bold"><?= number_format($item['total_stagiaires']) ?> متربص</td>
                                    <td class="text-center text-danger fw-bold">
                                        <?= $item['total_stagiaires'] > 0 ? number_format(($item['femmes'] / $item['total_stagiaires']) * 100, 1) : '0' ?>%
                                    </td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1.5">مفتوح ومسجل</span>
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

<script>
function exportTableToCSV(tableID, filename) {
    var csv = [];
    var rows = document.querySelectorAll("table#" + tableID + " tr");
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) 
            row.push('"' + cols[j].innerText.trim() + '"');
        csv.push(row.join(","));        
    }
    downloadCSV(csv.join("\n"), filename);
}

function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;
    csvFile = new Blob(["\uFEFF" + csv], {type: "text/csv;charset=utf-8;"});
    downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    filename = filename?filename:'excel_data.xls';
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        downloadLink.href = 'data:' + dataType + ', ' + '\ufeff' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}
</script>
@endsection
