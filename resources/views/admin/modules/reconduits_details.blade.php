@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var string $etabName
 * @var array $list
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-users-viewfinder text-primary me-2"></i> <?= htmlspecialchars($title) ?>
            </h3>
            <p class="text-muted mb-0 small mt-1">
                المؤسسة التكوينية المحددة: <span class="badge bg-primary-subtle text-primary ms-1 px-3 py-2 fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($etabName) ?></span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/dashboard/reconduits" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-arrow-right me-2"></i> رجوع
            </a>
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">
                <i class="fa-solid fa-print me-2"></i> طباعة القائمة
            </button>
        </div>
    </div>

    <!-- Smart Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8) !important;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-secondary" style="font-family: 'Cairo', sans-serif;">
                <i class="fa-solid fa-filter text-primary me-2"></i> نظام الفلترة والبحث الذكي
            </h6>
            
            <!-- Row 1 of Filters -->
            <div class="row g-3">
                <!-- Search bar -->
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="smartSearch" class="form-control bg-light border-0 rounded-end-3" placeholder="ابحث باسم المتربص، رقم التمدرس، أو التخصص..." onkeyup="applySmartFilters()">
                    </div>
                </div>
                <!-- Specialty filter -->
                <div class="col-md-3">
                    <select id="specialtyFilter" class="form-select bg-light border-0 rounded-3" onchange="applySmartFilters()">
                        <option value="">كل التخصصات</option>
                    </select>
                </div>
                <!-- Section filter -->
                <div class="col-md-2">
                    <select id="sectionFilter" class="form-select bg-light border-0 rounded-3" onchange="applySmartFilters()">
                        <option value="">كل الأقسام / الأفواج</option>
                    </select>
                </div>
                <!-- Gender filter -->
                <div class="col-md-2">
                    <select id="genderFilter" class="form-select bg-light border-0 rounded-3" onchange="applySmartFilters()">
                        <option value="">كل الجنسين</option>
                        <option value="ذكر">ذكر</option>
                        <option value="أنثى">أنثى</option>
                    </select>
                </div>
                <!-- Photo filter -->
                <div class="col-md-2">
                    <select id="photoFilter" class="form-select bg-light border-0 rounded-3" onchange="applySmartFilters()">
                        <option value="">كل الحالات (الصورة)</option>
                        <option value="1">بصورة شخصية</option>
                        <option value="0">بدون صورة</option>
                    </select>
                </div>
            </div>
            
            <!-- Row 2 of Filters -->
            <div class="row g-3 mt-2">
                <!-- Sort by -->
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fa-solid fa-arrow-down-a-z text-muted"></i></span>
                        <select id="sortBy" class="form-select bg-light border-0 rounded-end-3" onchange="applySmartFilters()">
                            <option value="">ترتيب افتراضي</option>
                            <option value="name_asc">الاسم واللقب (أ - ي)</option>
                            <option value="name_desc">الاسم واللقب (ي - أ)</option>
                            <option value="id_asc">رقم التمدرس (تصاعدي)</option>
                            <option value="id_desc">رقم التمدرس (تنازلي)</option>
                        </select>
                    </div>
                </div>
                <!-- Birth Year filter -->
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fa-solid fa-calendar-days text-muted"></i></span>
                        <select id="yearFilter" class="form-select bg-light border-0 rounded-end-3" onchange="applySmartFilters()">
                            <option value="">كل سنوات الميلاد</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                <div class="small text-muted">
                    تم العثور على: <span id="filteredCount" class="fw-bold text-primary">0</span> متربصين من أصل <span class="fw-bold text-secondary"><?= count($list) ?></span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-decoration-none text-danger fw-bold p-0" onclick="clearSmartFilters()">
                    <i class="fa-solid fa-eraser me-1"></i> إعادة تعيين الفلاتر
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i> 
                قائمة المتربصين المستمرين (النشطين حالياً)
            </h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('reconduitsDetailsTable', 'reconduits_details.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="reconduitsDetailsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">رقم التمدرس</th>
                            <th>الاسم واللقب</th>
                            <th>تاريخ ومكان الميلاد</th>
                            <th>الجنس</th>
                            <th>التخصص / الفوج (القسم)</th>
                            <th class="pe-4 text-end">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-slash mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                    <div class="fw-bold text-dark">لا يوجد متربصون مستمرون مسجلون في هذه المؤسسة</div>
                                    <div class="small mt-1">قد يكونون غير مسجلين في النظام أو الأقسام الخاصة بهم لا تحتوي على أعداد مستمرين.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr class="trainee-row"
                                    data-trainee-id="<?= htmlspecialchars($item['IDapprenant'] ?? '') ?>"
                                    data-birth-date="<?= htmlspecialchars($item['DateNais'] ?? '') ?>"
                                    data-has-photo="<?= !empty($item['photo']) ? '1' : '0' ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-secondary" style="font-family: monospace; font-size: 1rem;">#<?= htmlspecialchars($item['IDapprenant'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-wrapper shadow-sm rounded-circle border border-2 border-white overflow-hidden d-flex justify-content-center align-items-center" style="width: 45px; height: 45px; flex-shrink: 0; background-color: #f1f5f9;">
                                                <?php if (!empty($item['photo'])): ?>
                                                    <img src="<?= asset(ltrim($item['photo'], '/')) ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <?php if (in_array(strtolower($item['Civ'] ?? ''), ['f', 'أنثى', 'انثى', '2'])): ?>
                                                        <i class="fa-solid fa-venus fa-lg" style="color: #ec4899;"></i>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-mars fa-lg" style="color: #6366f1;"></i>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark trainee-name-ar"><?= htmlspecialchars(($item['Nom'] ?? '') . ' ' . ($item['Prenom'] ?? '')) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars(strtoupper(($item['NomFr'] ?? '') . ' ' . ($item['PrenomFr'] ?? ''))) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark"><i class="fa-regular fa-calendar me-1 text-muted"></i> <?= htmlspecialchars($item['DateNais'] ?? 'غير متوفر') ?></div>
                                        <div class="text-muted small">بـ <?= htmlspecialchars($item['LieuNais'] ?? 'غير متوفر') ?></div>
                                    </td>
                                    <td>
                                        <?php if (in_array(strtolower($item['Civ'] ?? ''), ['f', 'أنثى', 'انثى', '2'])): ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2"><i class="fa-solid fa-venus me-1"></i> أنثى</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2"><i class="fa-solid fa-mars me-1"></i> ذكر</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark text-truncate trainee-specialty" style="max-width: 250px;" title="<?= htmlspecialchars($item['specialite_nom'] ?? '') ?>">
                                            <?= htmlspecialchars($item['specialite_nom'] ?? 'تخصص غير محدد') ?>
                                        </div>
                                        <div class="text-primary small fw-bold mt-1 trainee-section">
                                            <i class="fa-solid fa-layer-group me-1"></i> 
                                            <?php
                                            $sn = $item['section_nom'] ?? '';
                                            $sp = $item['specialite_nom'] ?? '';
                                            if ($sn && $sn !== $sp) {
                                                echo htmlspecialchars($sn);
                                            } else {
                                                echo 'الفوج ' . htmlspecialchars((string)($item['IDapprenant'] ?? ''));
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="/dashboard/reconduits/edit/<?= $item['IDapprenant'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                                <i class="fa-solid fa-user-pen me-1"></i> التفاصيل والتعديل
                                            </a>
                                            <?php if (in_array(strtolower(session('user.role_code') ?? ''), ['admin', 'etablissement', 'directeur'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold btn-transfer" 
                                                        data-id="<?= $item['IDapprenant'] ?>" 
                                                        data-name="<?= htmlspecialchars(($item['Nom'] ?? '') . ' ' . ($item['Prenom'] ?? '')) ?>" 
                                                        data-specialty-id="<?= $item['specialite_id'] ?? '' ?>" 
                                                        data-specialty-name="<?= htmlspecialchars($item['specialite_nom'] ?? '') ?>" 
                                                        onclick="openTransferModal(this)">
                                                    <i class="fa-solid fa-arrows-spin me-1"></i> طلب تحويل
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- No results row -->
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="6" class="text-center py-5 text-muted">
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
    populateFilterDropdowns();
    applySmartFilters();
});

function populateFilterDropdowns() {
    const table = document.getElementById("reconduitsDetailsTable");
    if (!table) return;

    const rows = table.querySelectorAll("tbody tr.trainee-row");
    const specialties = new Set();
    const sections = new Set();
    const years = new Set();

    rows.forEach(row => {
        // Specialty
        const specDiv = row.querySelector(".trainee-specialty");
        if (specDiv) {
            specialties.add(specDiv.textContent.trim());
        }

        // Section
        const secDiv = row.querySelector(".trainee-section");
        if (secDiv) {
            sections.add(secDiv.textContent.trim());
        }

        // Birth Year
        const dateText = row.getAttribute("data-birth-date") || "";
        const match = dateText.match(/\b\d{4}\b/);
        if (match) {
            years.add(match[0]);
        }
    });

    const specSelect = document.getElementById("specialtyFilter");
    specialties.forEach(spec => {
        const opt = document.createElement("option");
        opt.value = spec;
        opt.textContent = spec;
        specSelect.appendChild(opt);
    });

    const secSelect = document.getElementById("sectionFilter");
    sections.forEach(sec => {
        const opt = document.createElement("option");
        opt.value = sec;
        opt.textContent = sec;
        secSelect.appendChild(opt);
    });

    const yearSelect = document.getElementById("yearFilter");
    const sortedYears = Array.from(years).sort((a, b) => b - a);
    sortedYears.forEach(y => {
        const opt = document.createElement("option");
        opt.value = y;
        opt.textContent = y;
        yearSelect.appendChild(opt);
    });
}

function normalizeArabic(text) {
    if (!text) return "";
    return text
        .replace(/[أإآ]/g, 'ا')
        .replace(/ة/g, 'ه')
        .replace(/ى/g, 'ي')
        .toLowerCase()
        .trim();
}

function sortTableRows(rowsArray, sortValue) {
    if (!sortValue) return rowsArray;

    return rowsArray.sort((a, b) => {
        if (sortValue === "name_asc" || sortValue === "name_desc") {
            const nameA = a.querySelector(".trainee-name-ar").textContent.trim();
            const nameB = b.querySelector(".trainee-name-ar").textContent.trim();
            const comp = nameA.localeCompare(nameB, 'ar');
            return sortValue === "name_asc" ? comp : -comp;
        }
        if (sortValue === "id_asc" || sortValue === "id_desc") {
            const idA = parseInt(a.getAttribute("data-trainee-id") || "0", 10);
            const idB = parseInt(b.getAttribute("data-trainee-id") || "0", 10);
            return sortValue === "id_asc" ? idA - idB : idB - idA;
        }
        return 0;
    });
}

function applySmartFilters() {
    const table = document.getElementById("reconduitsDetailsTable");
    if (!table) return;

    const searchVal = normalizeArabic(document.getElementById("smartSearch").value);
    const genderVal = document.getElementById("genderFilter").value;
    const specialtyVal = document.getElementById("specialtyFilter").value;
    const sectionVal = document.getElementById("sectionFilter").value;
    const photoVal = document.getElementById("photoFilter").value;
    const yearVal = document.getElementById("yearFilter").value;
    const sortByVal = document.getElementById("sortBy").value;

    const rows = table.querySelectorAll("tbody tr.trainee-row");
    let visibleCount = 0;

    rows.forEach(row => {
        const idText = row.getAttribute("data-trainee-id") || "";
        
        const nameAr = row.querySelector(".trainee-name-ar").textContent;
        const nameFr = row.querySelector(".text-muted").textContent;
        const nameNormalized = normalizeArabic(nameAr) + " " + nameFr.toLowerCase();

        const genderCell = row.cells[3];
        const genderText = genderCell ? genderCell.textContent.trim() : "";

        const specName = row.querySelector(".trainee-specialty").textContent.trim();
        const secName = row.querySelector(".trainee-section").textContent.trim();
        const specNormalized = normalizeArabic(specName) + " " + normalizeArabic(secName);

        const hasPhoto = row.getAttribute("data-has-photo");
        const birthDate = row.getAttribute("data-birth-date") || "";

        // Filters matching
        const matchesSearch = !searchVal || 
            idText.includes(searchVal) || 
            nameNormalized.includes(searchVal) || 
            specNormalized.includes(searchVal);

        const matchesGender = !genderVal || genderText.includes(genderVal);
        const matchesSpecialty = !specialtyVal || specName === specialtyVal;
        const matchesSection = !sectionVal || secName === sectionVal;
        const matchesPhoto = !photoVal || hasPhoto === photoVal;
        const matchesYear = !yearVal || birthDate.includes(yearVal);

        if (matchesSearch && matchesGender && matchesSpecialty && matchesSection && matchesPhoto && matchesYear) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    // Reorder and sort visible rows in the table
    const tbody = table.querySelector("tbody");
    const allRowsArray = Array.from(rows);
    const sortedRows = sortTableRows(allRowsArray, sortByVal);
    
    sortedRows.forEach(row => {
        tbody.appendChild(row);
    });

    const noResultsRow = document.getElementById("noResultsRow");
    if (noResultsRow) {
        noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? "" : "none";
    }

    document.getElementById("filteredCount").textContent = visibleCount;
}

function clearSmartFilters() {
    document.getElementById("smartSearch").value = "";
    document.getElementById("genderFilter").value = "";
    document.getElementById("specialtyFilter").value = "";
    document.getElementById("sectionFilter").value = "";
    document.getElementById("photoFilter").value = "";
    document.getElementById("yearFilter").value = "";
    document.getElementById("sortBy").value = "";
    applySmartFilters();
}
</script>

<!-- Modal طلب التحويل -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true" style="font-family: 'Cairo', sans-serif;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="{{ route('modules.reconduits.transfer') }}" method="POST" id="transferForm">
                @csrf
                <input type="hidden" name="apprenant_id" id="modalTraineeId">
                
                <div class="modal-header bg-warning-subtle text-warning-emphasis border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="transferModalLabel">
                        <i class="fa-solid fa-arrows-spin me-2"></i> طلب تحويل متربص إلى مؤسسة أخرى
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body px-4 py-3">
                    <!-- Trainee Info -->
                    <div class="alert alert-light border-0 shadow-sm rounded-3 mb-3">
                        <div class="small text-muted mb-1">اسم المتربص:</div>
                        <div class="fw-bold text-dark fs-5" id="modalTraineeName"></div>
                        <div class="small text-muted mt-2 mb-1">التخصص الحالي:</div>
                        <div class="fw-semibold text-secondary" id="modalTraineeSpecialty"></div>
                    </div>
                    
                    <!-- Wilaya Selection -->
                    <div class="mb-3">
                        <label for="targetWilaya" class="form-label fw-bold text-secondary">الولاية المستهدفة</label>
                        <select id="targetWilaya" class="form-select rounded-3 border-light bg-light" required onchange="loadTargetEtablissements(this.value)">
                            <option value="">اختر الولاية المستهدفة...</option>
                            <?php
                            $wilayas = \App\Services\ReferenceCache::wilayas();
                            foreach ($wilayas as $w) {
                                echo '<option value="' . (int)$w['id'] . '">' . htmlspecialchars($w['nom_ar']) . ' - ' . sprintf('%02d', (int)$w['code']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Etablissement Selection -->
                    <div class="mb-3">
                        <label for="targetEtab" class="form-label fw-bold text-secondary">المؤسسة المستهدفة</label>
                        <select name="to_etab_id" id="targetEtab" class="form-select rounded-3 border-light bg-light" required disabled onchange="loadTargetSections(this.value)">
                            <option value="">اختر المؤسسة التكوينية...</option>
                        </select>
                    </div>
                    
                    <!-- Section Selection -->
                    <div class="mb-3">
                        <label for="targetSection" class="form-label fw-bold text-secondary">القسم المستهدف (بنفس التخصص)</label>
                        <select name="to_section_id" id="targetSection" class="form-select rounded-3 border-light bg-light" required disabled>
                            <option value="">اختر الفوج / القسم المستهدف...</option>
                        </select>
                        <div class="form-text text-muted mt-1 small">يتم عرض الأقسام المتوافقة مع تخصص المتربص الحالي فقط.</div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" style="color: #000;">
                        <i class="fa-solid fa-paper-plane me-1"></i> إرسال الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentSpecialtyId = null;

function openTransferModal(btn) {
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name');
    const specialtyId = btn.getAttribute('data-specialty-id');
    const specialtyName = btn.getAttribute('data-specialty-name');
    
    document.getElementById('modalTraineeId').value = id;
    document.getElementById('modalTraineeName').textContent = name;
    document.getElementById('modalTraineeSpecialty').textContent = specialtyName;
    currentSpecialtyId = specialtyId;
    
    // Reset modal form
    document.getElementById('targetWilaya').value = "";
    
    const etabSelect = document.getElementById('targetEtab');
    etabSelect.innerHTML = '<option value="">اختر المؤسسة التكوينية...</option>';
    etabSelect.disabled = true;
    
    const sectionSelect = document.getElementById('targetSection');
    sectionSelect.innerHTML = '<option value="">اختر الفوج / القسم المستهدف...</option>';
    sectionSelect.disabled = true;
    
    const myModal = new bootstrap.Modal(document.getElementById('transferModal'));
    myModal.show();
}

function loadTargetEtablissements(wilayaId) {
    const etabSelect = document.getElementById('targetEtab');
    etabSelect.innerHTML = '<option value="">جاري التحميل...</option>';
    etabSelect.disabled = true;
    
    const sectionSelect = document.getElementById('targetSection');
    sectionSelect.innerHTML = '<option value="">اختر الفوج / القسم المستهدف...</option>';
    sectionSelect.disabled = true;
    
    if (!wilayaId) {
        etabSelect.innerHTML = '<option value="">اختر المؤسسة التكوينية...</option>';
        return;
    }
    
    fetch(`/dashboard/reconduits/ajax/etablissements?wilaya_id=${wilayaId}`)
        .then(res => res.json())
        .then(data => {
            etabSelect.innerHTML = '<option value="">اختر المؤسسة التكوينية...</option>';
            if (!data || data.length === 0) {
                etabSelect.innerHTML = '<option value="">لا توجد مؤسسات تكوينية في هذه الولاية</option>';
                etabSelect.disabled = true;
                const wilayaName = document.getElementById('targetWilaya').options[document.getElementById('targetWilaya').selectedIndex]?.text || 'الولاية المحددة';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'تنبيه عدم توفر مؤسسات',
                        html: `تنبيه: الولاية المستهدفة (<strong>${wilayaName}</strong>) لا تحتوي حالياً على مؤسسات تكوينية متاحة للتحويل.`,
                        confirmButtonText: 'موافق',
                        confirmButtonColor: '#f59e0b'
                    });
                }
            } else {
                data.forEach(e => {
                    etabSelect.innerHTML += `<option value="${e.id}">${e.nom}</option>`;
                });
                etabSelect.disabled = false;
            }
        })
        .catch(err => {
            console.error("Error loading etablissements", err);
            etabSelect.innerHTML = '<option value="">خطأ في تحميل البيانات</option>';
        });
}

function loadTargetSections(etabId) {
    const sectionSelect = document.getElementById('targetSection');
    sectionSelect.innerHTML = '<option value="">جاري التحميل...</option>';
    sectionSelect.disabled = true;
    
    if (!etabId || !currentSpecialtyId) {
        sectionSelect.innerHTML = '<option value="">اختر الفوج / القسم المستهدف...</option>';
        return;
    }
    
    fetch(`/dashboard/reconduits/ajax/sections?etab_id=${etabId}&specialty_id=${currentSpecialtyId}`)
        .then(res => res.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">اختر الفوج / القسم المستهدف...</option>';
            if (!data || data.length === 0) {
                sectionSelect.innerHTML = '<option value="">لا توجد أقسام متوفرة في هذه المؤسسة لهذا التخصص</option>';
                sectionSelect.disabled = true;
                
                const specialtyName = document.getElementById('modalTraineeSpecialty').textContent || 'هذا التخصص';
                const etabName = document.getElementById('targetEtab').options[document.getElementById('targetEtab').selectedIndex]?.text || 'المؤسسة المحددة';
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'تنبيه عدم توفر التخصص أو القسم',
                        html: `تنبيه: المؤسسة المستهدفة (<strong>${etabName}</strong>) لا تحتوي حالياً على قسم أو تخصص مفتوح مطابق لتخصص المتربص الحالي (<strong>${specialtyName}</strong>).`,
                        confirmButtonText: 'موافق',
                        confirmButtonColor: '#f59e0b'
                    });
                } else {
                    alert(`تنبيه: المؤسسة المستهدفة لا تحتوي حالياً على قسم مفتوح لنفس التخصص التكويني.`);
                }
            } else {
                data.forEach(s => {
                    sectionSelect.innerHTML += `<option value="${s.IDSection}">${s.Nom}</option>`;
                });
                sectionSelect.disabled = false;
            }
        })
        .catch(err => {
            console.error("Error loading sections", err);
            sectionSelect.innerHTML = '<option value="">خطأ في تحميل الأقسام</option>';
        });
}
</script>
@endsection
