@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $list
 * @var array $wilayas
 * @var array $scope
 */
$isSuperAdmin = in_array($scope['role'] ?? '', ['admin', 'superadmin', 'central']);
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-users-rays text-primary me-2"></i> <?= htmlspecialchars($title) ?>
            </h3>
            <p class="text-muted mb-0 small">متابعة تعداد المتكونين المستمرين (Reconduits)، التوزيع حسب الجنس، والمؤشرات الديموغرافية للمؤسسات التكوينية</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">
                <i class="fa-solid fa-print me-2"></i> طباعة الإحصائيات
            </button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger rounded-4 mb-3">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>خطأ في قاعدة البيانات:</strong> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards Row 1: Totals -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي المستمرين (Reconduits)</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning" id="stat-total" data-orig-val="<?= $stats['total'] ?>"><?= number_format($stats['total']) ?></h2>
                    <span class="small"><i class="fa-solid fa-users"></i> متربص مستمر في التكوين عبر المؤسسات</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">الإناث (Féminin)</h6>
                    <h2 class="display-5 fw-bold my-2 text-danger" id="stat-femmes" data-orig-val="<?= $stats['femmes'] ?>"><?= number_format($stats['femmes']) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-venus text-danger"></i> إناث مستمرات في التكوين</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">الذكور (Masculin)</h6>
                    <h2 class="display-5 fw-bold my-2 text-dark" id="stat-hommes" data-orig-val="<?= $stats['hommes'] ?>"><?= number_format($stats['hommes']) ?></h2>
                    <span class="small text-muted"><i class="fa-solid fa-mars text-primary"></i> ذكور مستمرون في التكوين</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 2: Discipline + Public/Private -->
    <div class="row g-4 mb-4">
        <!-- Expelled -->
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white;">
                <div class="card-body p-3 text-center">
                    <i class="fa-solid fa-user-slash fa-lg mb-2 text-white-50"></i>
                    <h6 class="text-white-50 fw-bold mb-1 small">المفصولون</h6>
                    <h3 class="fw-bold my-1" id="stat-mafsouls"><?= number_format($stats['mafsouls'] ?? 0) ?></h3>
                    <span class="small opacity-75">متربص مفصول</span>
                </div>
            </div>
        </div>
        <!-- Failed -->
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #ea580c 0%, #9a3412 100%); color: white;">
                <div class="card-body p-3 text-center">
                    <i class="fa-solid fa-x fa-lg mb-2 text-white-50"></i>
                    <h6 class="text-white-50 fw-bold mb-1 small">الراسبون</h6>
                    <h3 class="fw-bold my-1" id="stat-rasiboun"><?= number_format($stats['rasiboun'] ?? 0) ?></h3>
                    <span class="small opacity-75">متربص راسب</span>
                </div>
            </div>
        </div>
        <!-- Remedial -->
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #0284c7 0%, #0c4a6e 100%); color: white;">
                <div class="card-body p-3 text-center">
                    <i class="fa-solid fa-rotate fa-lg mb-2 text-white-50"></i>
                    <h6 class="text-white-50 fw-bold mb-1 small">المستدركون</h6>
                    <h3 class="fw-bold my-1" id="stat-mostadraks"><?= number_format($stats['mostadraks'] ?? 0) ?></h3>
                    <span class="small opacity-75">متربص مستدرك</span>
                </div>
            </div>
        </div>
        <!-- Public Establishments -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #16a34a 0%, #14532d 100%); color: white;">
                <div class="card-body p-3 text-center">
                    <i class="fa-solid fa-building-columns fa-lg mb-2 text-white-50"></i>
                    <h6 class="text-white-50 fw-bold mb-1 small">مؤسسات عمومية</h6>
                    <h3 class="fw-bold my-1" id="stat-public"><?= number_format($stats['public_total'] ?? 0) ?></h3>
                    <span class="small opacity-75">متربص في القطاع العام</span>
                </div>
            </div>
        </div>
        <!-- Private Establishments -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%); color: white;">
                <div class="card-body p-3 text-center">
                    <i class="fa-solid fa-store fa-lg mb-2 text-white-50"></i>
                    <h6 class="text-white-50 fw-bold mb-1 small">مؤسسات خاصة</h6>
                    <h3 class="fw-bold my-1" id="stat-prive"><?= number_format($stats['prive_total'] ?? 0) ?></h3>
                    <span class="small opacity-75">متربص في القطاع الخاص</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8) !important;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-secondary" style="font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-filter text-primary me-2"></i> نظام الفلترة والبحث الذكي للمؤسسات
            </h6>
            <div class="row g-3">
                <!-- Search bar -->
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="etabSearch" class="form-control bg-light border-0 rounded-end-3" placeholder="ابحث باسم المؤسسة التكوينية..." onkeyup="applyEtabFilters()">
                    </div>
                </div>
                <!-- Wilaya filter (Only for SuperAdmin) -->
                <?php if ($isSuperAdmin): ?>
                <div class="col-md-2">
                    <select id="wilayaFilter" class="form-select bg-light border-0 rounded-3" onchange="applyEtabFilters()">
                        <option value="">كل الولايات</option>
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['code'] . ' - ' . $w['nom_ar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <!-- Secteur filter (public/private) -->
                <div class="col-md-2">
                    <select id="secteurFilter" class="form-select bg-light border-0 rounded-3" onchange="applyEtabFilters()">
                        <option value="">عام وخاص</option>
                        <option value="public">مؤسسات عمومية</option>
                        <option value="prive">مؤسسات خاصة</option>
                    </select>
                </div>
                <!-- Etablissement Type filter -->
                <div class="col-md-2">
                    <select id="typeFilter" class="form-select bg-light border-0 rounded-3" onchange="applyEtabFilters()">
                        <option value="">كل أنواع المؤسسات</option>
                        <option value="insfp">معاهد وطنية متخصصة (INSFP)</option>
                        <option value="cfpa">مراكز التكوين المهني (CFPA)</option>
                        <option value="annexe">ملحقات التكوين المهني</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
                <!-- Trainee Range filter -->
                <div class="col-md-2">
                    <select id="rangeFilter" class="form-select bg-light border-0 rounded-3" onchange="applyEtabFilters()">
                        <option value="">كل تعداد المستمرين</option>
                        <option value="has_trainees">مؤسسات بها مستمرون (> 0)</option>
                        <option value="no_trainees">مؤسسات بدون مستمرين (= 0)</option>
                        <option value="high">تعداد مرتفع (> 100 متربص)</option>
                    </select>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                <div class="small text-muted">
                    تم العثور على: <span id="filteredEtabCount" class="fw-bold text-primary">0</span> مؤسسات من أصل <span class="fw-bold text-secondary"><?= count($list) ?></span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-decoration-none text-danger fw-bold p-0" onclick="clearEtabFilters()">
                    <i class="fa-solid fa-eraser me-1"></i> إعادة تعيين الفلاتر
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-simple text-primary me-2"></i> توزيع المتربصين المستمرين حسب المؤسسات التكوينية</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('reconduitsTable', 'reconduits_etablissements.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="reconduitsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المؤسسة التكوينية</th>
                            <th class="text-center">القطاع</th>
                            <th class="text-center">إجمالي المستمرين</th>
                            <th class="text-center">إناث</th>
                            <th class="text-center">ذكور</th>
                            <th class="text-center" style="color: #dc2626;"><i class="fa-solid fa-user-slash me-1"></i>مفصولون</th>
                            <th class="text-center" style="color: #ea580c;"><i class="fa-solid fa-x me-1"></i>راسبون</th>
                            <th class="text-center" style="color: #0284c7;"><i class="fa-solid fa-rotate me-1"></i>مستدركون</th>
                            <th class="text-center pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                    <div class="fw-bold text-dark">لا توجد بيانات متاحة</div>
                                    <div class="small">لم يتم العثور على أرقام للمتربصين المستمرين للمؤسسات المحددة.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): 
                                $etabNom = $item['etab_nom'] ?? '';
                                $secteur = $item['secteur'] ?? 'public';
                                $etabType = 'other';
                                if (mb_stripos($etabNom, 'معهد') !== false || mb_stripos($etabNom, 'INSFP') !== false) {
                                    $etabType = 'insfp';
                                } elseif (mb_stripos($etabNom, 'مركز') !== false || mb_stripos($etabNom, 'CFPA') !== false) {
                                    $etabType = 'cfpa';
                                } elseif (mb_stripos($etabNom, 'ملحقة') !== false || mb_stripos($etabNom, 'annexe') !== false) {
                                    $etabType = 'annexe';
                                }
                                $mafsouls   = (int)($item['mafsouls']   ?? 0);
                                $rasiboun   = (int)($item['rasiboun']   ?? 0);
                                $mostadraks = (int)($item['mostadraks'] ?? 0);
                            ?>
                                <tr class="etab-row" 
                                    data-wilaya-id="<?= htmlspecialchars($item['id_wilaya'] ?? '') ?>" 
                                    data-etab-type="<?= htmlspecialchars($etabType) ?>"
                                    data-secteur="<?= htmlspecialchars($secteur) ?>"
                                    data-total="<?= (int)($item['total'] ?? 0) ?>"
                                    data-femmes="<?= (int)($item['femmes'] ?? 0) ?>"
                                    data-hommes="<?= (int)($item['hommes'] ?? 0) ?>"
                                    data-mafsouls="<?= $mafsouls ?>"
                                    data-rasiboun="<?= $rasiboun ?>"
                                    data-mostadraks="<?= $mostadraks ?>">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="fw-bold text-dark etab-name-text"><?= htmlspecialchars($item['etab_nom']) ?></div>
                                            <?php if ($etabType === 'insfp'): ?>
                                                <span class="badge rounded-pill px-2 py-1 small" style="font-size: 0.7rem; color: #6b21a8; background-color: #f3e8ff; border: 1px solid #e9d5ff;">INSFP</span>
                                            <?php elseif ($etabType === 'cfpa'): ?>
                                                <span class="badge rounded-pill px-2 py-1 small" style="font-size: 0.7rem; color: #1e3a8a; background-color: #dbeafe; border: 1px solid #bfdbfe;">CFPA</span>
                                            <?php elseif ($etabType === 'annexe'): ?>
                                                <span class="badge rounded-pill px-2 py-1 small" style="font-size: 0.7rem; color: #c2410c; background-color: #ffedd5; border: 1px solid #fed7aa;">ملحقة</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <i class="fa-solid fa-location-dot text-secondary me-1"></i>
                                            <?= htmlspecialchars($item['wilaya_nom'] ?? 'ولاية غير محددة') ?>
                                            <span class="text-muted mx-1">|</span>
                                            <span class="text-secondary"><?= htmlspecialchars($item['etab_fr'] ?? '') ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($secteur === 'prive'): ?>
                                            <span class="badge rounded-pill px-2 py-1" style="font-size: 0.7rem; background:#f3e8ff; color:#6b21a8; border:1px solid #e9d5ff;"><i class="fa-solid fa-store me-1"></i>خاص</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill px-2 py-1" style="font-size: 0.7rem; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;"><i class="fa-solid fa-building-columns me-1"></i>عام</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-primary" style="font-size: 1.1rem;"><?= number_format($item['total']) ?></td>
                                    <td class="text-center text-danger fw-bold"><?= number_format($item['femmes']) ?></td>
                                    <td class="text-center text-dark fw-bold"><?= number_format($item['hommes']) ?></td>
                                    <td class="text-center fw-bold" style="color: #dc2626;">
                                        <?php if ($mafsouls > 0): ?>
                                            <span class="badge rounded-pill px-2" style="background:#fee2e2; color:#dc2626; border:1px solid #fecaca;"><?= number_format($mafsouls) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold" style="color: #ea580c;">
                                        <?php if ($rasiboun > 0): ?>
                                            <span class="badge rounded-pill px-2" style="background:#ffedd5; color:#ea580c; border:1px solid #fed7aa;"><?= number_format($rasiboun) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold" style="color: #0284c7;">
                                        <?php if ($mostadraks > 0): ?>
                                            <span class="badge rounded-pill px-2" style="background:#e0f2fe; color:#0284c7; border:1px solid #bae6fd;"><?= number_format($mostadraks) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="/dashboard/reconduits/details/<?= $item['id_etab'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                            <i class="fa-solid fa-users-viewfinder me-1"></i> عرض المتربصين
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- No results row -->
                        <tr id="noEtabResultsRow" style="display: none;">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-face-frown mb-3 animate__animated animate__bounce" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <div class="fw-bold text-dark">لا توجد نتائج تطابق خيارات الفلترة المحددة</div>
                                <div class="small mt-1">يرجى تعديل حقل البحث أو خيارات الاختيار للوصول للنتائج المطلوبة.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    applyEtabFilters();
});

function normalizeArabicText(text) {
    if (!text) return "";
    return text
        .replace(/[أإآ]/g, 'ا')
        .replace(/ة/g, 'ه')
        .replace(/ى/g, 'ي')
        .toLowerCase()
        .trim();
}

function applyEtabFilters() {
    const searchVal = normalizeArabicText(document.getElementById("etabSearch").value);
    const typeVal = document.getElementById("typeFilter").value;
    const secteurVal = document.getElementById("secteurFilter").value;
    const rangeVal = document.getElementById("rangeFilter").value;
    const wilayaSelect = document.getElementById("wilayaFilter");
    const wilayaVal = wilayaSelect ? wilayaSelect.value : "";

    const rows = document.querySelectorAll("#reconduitsTable tbody tr.etab-row");
    let visibleEtabCount = 0;
    
    let totalSum = 0, femmesSum = 0, hommesSum = 0;
    let mafsoulsSum = 0, rasibounSum = 0, mostadrakSum = 0;
    let publicSum = 0, priveSum = 0;

    rows.forEach(row => {
        const nameText = row.querySelector(".etab-name-text").textContent;
        const nameTextFr = row.querySelector(".text-muted").textContent;
        const nameNormalized = normalizeArabicText(nameText) + " " + nameTextFr.toLowerCase();
        
        const rowWilaya = row.getAttribute("data-wilaya-id");
        const rowType = row.getAttribute("data-etab-type");
        const rowSecteur = row.getAttribute("data-secteur");
        const rowTotal = parseInt(row.getAttribute("data-total") || "0", 10);
        const rowFemmes = parseInt(row.getAttribute("data-femmes") || "0", 10);
        const rowHommes = parseInt(row.getAttribute("data-hommes") || "0", 10);
        const rowMafsouls = parseInt(row.getAttribute("data-mafsouls") || "0", 10);
        const rowRasiboun = parseInt(row.getAttribute("data-rasiboun") || "0", 10);
        const rowMostadraks = parseInt(row.getAttribute("data-mostadraks") || "0", 10);

        const matchesSearch = !searchVal || nameNormalized.includes(searchVal);
        const matchesWilaya = !wilayaVal || rowWilaya === wilayaVal;
        const matchesType = !typeVal || rowType === typeVal;
        const matchesSecteur = !secteurVal || rowSecteur === secteurVal;
        
        let matchesRange = true;
        if (rangeVal === "has_trainees") matchesRange = rowTotal > 0;
        else if (rangeVal === "no_trainees") matchesRange = rowTotal === 0;
        else if (rangeVal === "high") matchesRange = rowTotal > 100;

        if (matchesSearch && matchesWilaya && matchesType && matchesSecteur && matchesRange) {
            row.style.display = "";
            visibleEtabCount++;
            totalSum += rowTotal;
            femmesSum += rowFemmes;
            hommesSum += rowHommes;
            mafsoulsSum += rowMafsouls;
            rasibounSum += rowRasiboun;
            mostadrakSum += rowMostadraks;
            if (rowSecteur === 'prive') priveSum += rowTotal;
            else publicSum += rowTotal;
        } else {
            row.style.display = "none";
        }
    });

    document.getElementById("stat-total").textContent = totalSum.toLocaleString();
    document.getElementById("stat-femmes").textContent = femmesSum.toLocaleString();
    document.getElementById("stat-hommes").textContent = hommesSum.toLocaleString();
    document.getElementById("stat-mafsouls").textContent = mafsoulsSum.toLocaleString();
    document.getElementById("stat-rasiboun").textContent = rasibounSum.toLocaleString();
    document.getElementById("stat-mostadraks").textContent = mostadrakSum.toLocaleString();
    document.getElementById("stat-public").textContent = publicSum.toLocaleString();
    document.getElementById("stat-prive").textContent = priveSum.toLocaleString();

    const noResultsRow = document.getElementById("noEtabResultsRow");
    if (noResultsRow) {
        noResultsRow.style.display = (visibleEtabCount === 0 && rows.length > 0) ? "" : "none";
    }

    document.getElementById("filteredEtabCount").textContent = visibleEtabCount;
}

function clearEtabFilters() {
    document.getElementById("etabSearch").value = "";
    document.getElementById("typeFilter").value = "";
    document.getElementById("secteurFilter").value = "";
    document.getElementById("rangeFilter").value = "";
    const wilayaSelect = document.getElementById("wilayaFilter");
    if (wilayaSelect) wilayaSelect.value = "";
    applyEtabFilters();
}
</script>
@endsection
