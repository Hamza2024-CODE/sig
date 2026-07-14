@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var array $stats
 * @var array $list
 * @var array $etablissements
 * @var int|null $selected_etab
 * @var int $page
 * @var int $limit
 * @var int $total_pages
 * @var int $total_count
 * @var string|null $error
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Flash Messages -->
    <?php if (session()->has('flash_success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-4 me-2"></i>
                <div><?= session('flash_success');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->has('flash_error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
                <div><?= session('flash_error');  ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-database fs-4 me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-user-plus text-primary me-2"></i> التسجيل والتوجيه / Inscriptions & Orientation
            </h3>
            <p class="text-muted mb-0 small">تسيير ملفات المترشحين وعمليات التوجيه التلقائي والشخصي للمؤسسات التكوينية</p>
        </div>
        <div class="d-flex gap-2">
            <div class="d-flex align-items-center gap-2">
                <label for="per_page_select" class="small fw-bold text-muted text-nowrap"><i class="fa-solid fa-list-ol me-1"></i> لكل صفحة:</label>
                <select id="per_page_select" class="form-select rounded-pill border-light-subtle shadow-sm px-3 small" style="width: 100px;" onchange="changePerPage(this.value)">
                    <?php foreach ([10, 25, 50, 100] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php 
            $isRestrictedRole = in_array($scope['role'] ?? '', ['etablissement', 'directeur', 'formateur', 'employee']);
            if (!$isRestrictedRole): ?>
                <div class="d-flex align-items-center gap-2">
                    <label for="wilaya_filter" class="small fw-bold text-muted text-nowrap"><i class="fa-solid fa-map-location-dot me-1"></i> الولاية:</label>
                    <select id="wilaya_filter" class="form-select rounded-pill border-light-subtle shadow-sm px-3 small" style="width: 180px;" onchange="filterWilaya(this.value)">
                        <option value="">كل الولايات</option>
                        <?php foreach ($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= ($wilayaId ?? '') == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($etablissements)): ?>
                    <div class="d-flex align-items-center gap-2 ms-2">
                        <label for="etab_filter" class="small fw-bold text-muted text-nowrap"><i class="fa-solid fa-hotel me-1"></i> المؤسسة:</label>
                        <select id="etab_filter" class="form-select rounded-pill border-light-subtle shadow-sm px-3 small" style="width: 250px;" onchange="filterEtab(this.value)">
                            <option value="">كل المؤسسات التكوينية</option>
                            <?php foreach ($etablissements as $etab): ?>
                                <option value="<?= $etab['id'] ?>" <?= $selected_etab == $etab['id'] ? 'selected' : '' ?>><?= htmlspecialchars($etab['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            <?php elseif ($isRestrictedRole && !empty($etablissements)): ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-2" style="background:linear-gradient(135deg,#482b8f,#643edb);font-size:0.82rem;">
                        <i class="fa-solid fa-lock me-1"></i>
                        <?= htmlspecialchars($etablissements[0]['nom_ar'] ?? 'مؤسستك') ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bento Grid Stats -->
    <!-- قاعدة الأعمال: المسجلون = الناشطون = المستمرون = تم توجيههم وقبولهم -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #482b8f 0%, #2e1c5b 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">إجمالي طلبات التسجيل</h6>
                    <h2 class="display-5 fw-bold my-2 text-warning"><?= number_format($stats['total_candidats']) ?></h2>
                    <span class="small"><i class="fa-solid fa-users text-warning"></i> طلبات مسجلة ومحفظة</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #0f7a55 0%, #17a773 100%); color: white;">
                <div class="card-body p-4">
                    <h6 class="text-white-50 fw-bold mb-1">تم توجيههم وقبولهم</h6>
                    <h2 class="display-5 fw-bold my-2"><?= number_format($stats['orientes']) ?></h2>
                    <span class="small text-white-50"><i class="fa-solid fa-circle-check me-1"></i> المسجلون = الناشطون = المستمرون</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-1">ملفات معلقة / قيد المراجعة</h6>
                    <h2 class="display-5 fw-bold my-2 <?= $stats['en_attente'] > 0 ? 'text-warning' : 'text-success' ?>">
                        <?= number_format($stats['en_attente']) ?>
                    </h2>
                    <span class="small text-muted">
                        <?php if ($stats['en_attente'] === 0): ?>
                            <i class="fa-solid fa-circle-check text-success"></i> لا توجد ملفات معلقة
                        <?php else: ?>
                            <i class="fa-solid fa-spinner fa-spin text-warning"></i> تتطلب معالجة بيداغوجية
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-gear text-primary me-2"></i> وضعية طلبات التوجيه والالتحاق</h5>
            <div class="d-flex gap-2 no-print">
                <button onclick="exportTableToExcel('inscriptionsTable', 'inscriptions.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="exportTableToCSV('inscriptionsTable', 'inscriptions.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                </button>
                <button onclick="window.print()" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="inscriptionsTable">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4" style="width: 45px;"><input type="checkbox" id="select_all_candidates" class="form-check-input"></th>
                            <th>المترشح (NIN)</th>
                            <th>المؤسسة المعنية</th>
                            <th>التخصص المرغوب</th>
                            <th class="text-center">تاريخ الطلب</th>
                            <th class="text-center">حالة الملف والتوجيه</th>
                            <th class="pe-4 text-end no-print no-export">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">لا توجد طلبات تسجيل مقيدة حالياً.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <input type="checkbox" class="form-check-input candidate-select" value="<?= $item['id'] ?>" data-offre-id="<?= $item['IDOffre'] ?>">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['nom_ar'] . ' ' . $item['prenom_ar']) ?></div>
                                        <div class="text-muted small" style="font-family:'Outfit';"><?= htmlspecialchars($item['nin'] ?? '173204928104829104') ?></div>
                                    </td>
                                    <td><span class="fw-semibold text-secondary small"><?= htmlspecialchars($item['etab_nom']) ?></span></td>
                                    <td>
                                        <div class="fw-bold text-primary small"><?= htmlspecialchars($item['spec_ar']) ?></div>
                                        <div class="text-muted small">رقم: <?= htmlspecialchars($item['numero_inscription'] ?? $item['number_inscription'] ?? '-') ?></div>
                                    </td>
                                    <td class="text-center text-muted"><?= htmlspecialchars(substr($item['date_inscription'], 0, 10)) ?></td>
                                    <td class="text-center">
                                        <?php if ($item['decision'] === 'مقبول'): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i> مقبول وموجه</span>
                                        <?php elseif ($item['decision'] === 'مرفوض'): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa-solid fa-xmark me-1"></i> ملف مرفوض</span>
                                        <?php elseif ($item['statut_dossier'] === 'en_attente'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-clock fa-spin me-1"></i> قيد الدراسة</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fa-solid fa-file-circle-exclamation me-1"></i> قيد المراجعة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end no-print no-export">
                                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3 orient-btn"
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= htmlspecialchars($item['nom_ar'] . ' ' . $item['prenom_ar']) ?>"
                                                data-decision="<?= htmlspecialchars($item['decision'] ?? 'قيد الدراسة') ?>"
                                                data-validation="<?= (int)$item['validation_code'] ?>"
                                                data-status="<?= htmlspecialchars($item['statut_dossier'] ?? 'en_attente') ?>"
                                                data-offre-id="<?= $item['IDOffre'] ?>"
                                                data-section-id="<?= $item['IDSection'] ?? '' ?>">
                                            <i class="fa-solid fa-arrows-turn-to-dots me-1"></i> توجيه ومعالجة
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination bar -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white border-0 px-4 pb-4 pt-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    عرض <?= number_format(($page - 1) * $limit + 1) ?> – <?= number_format(min($page * $limit, $total_count)) ?>
                    من أصل <?= number_format($total_count) ?> سجل
                </div>
                <nav aria-label="Pagination inscriptions">
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <!-- Previous -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill px-3" href="<?= buildPageUrl($page - 1, $limit, $selected_etab) ?>" aria-label="السابق">&laquo;</a>
                        </li>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage   = min($total_pages, $page + 2);
                        if ($startPage > 1): ?>
                            <li class="page-item"><a class="page-link rounded-pill px-3" href="<?= buildPageUrl(1, $limit, $selected_etab) ?>">1</a></li>
                            <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link rounded-pill px-3" href="<?= buildPageUrl($i, $limit, $selected_etab) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($endPage < $total_pages): ?>
                            <?php if ($endPage < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                            <li class="page-item"><a class="page-link rounded-pill px-3" href="<?= buildPageUrl($total_pages, $limit, $selected_etab) ?>"><?= $total_pages ?></a></li>
                        <?php endif; ?>
                        <!-- Next -->
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill px-3" href="<?= buildPageUrl($page + 1, $limit, $selected_etab) ?>" aria-label="التالي">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
function buildPageUrl(int $p, int $lim, $etabId = null): string {
    $url = new \stdClass();
    $qs = array_filter(['page' => $p, 'limit' => $lim, 'etab_id' => $etabId]);
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    return htmlspecialchars($base . ($qs ? '?' . http_build_query($qs) : ''));
}
?>

<style>
@keyframes slideUp {
    from { transform: translate(-50%, 100px); opacity: 0; }
    to { transform: translate(-50%, 0); opacity: 1; }
}
</style>

<!-- Bulk Actions Floating Bar -->
<div id="bulk_actions_bar" class="card border-0 shadow-lg rounded-4 p-3 bg-white no-print position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index: 1050; width: 90%; max-width: 600px; border: 1px solid #e2e8f0 !important; animation: slideUp 0.3s ease;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-bold text-dark"><i class="fa-solid fa-circle-check text-success me-1"></i> تم تحديد <span id="selected_count_badge" class="badge bg-primary rounded-pill">0</span> مترشحين</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" id="trigger_bulk_modal">
                <i class="fa-solid fa-arrows-turn-to-dots me-1"></i> توجيه جماعي
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="clear_selection">
                إلغاء التحديد
            </button>
        </div>
    </div>
</div>

<!-- Orienter Modal -->
<div class="modal fade" id="orienterModal" tabindex="-1" aria-labelledby="orienterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="orienterModalLabel"><i class="fa-solid fa-arrows-turn-to-dots text-primary me-2"></i> دراسة الملف وتوجيه المترشح</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/inscriptions/orienter" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" id="orient_id" name="id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">اسم المترشح</label>
                        <input type="text" class="form-control rounded-pill border-0 bg-light px-3 fw-bold text-dark" id="orient_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="orient_offre_id" class="form-label small fw-bold text-muted">التخصص وعرض التكوين الموجه إليه</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="orient_offre_id" name="offre_id" required>
                            <?php foreach ($offers as $of): ?>
                                <option value="<?= $of['id'] ?>"><?= htmlspecialchars($of['spec_ar'] . ' (' . $of['etab_ar'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="orient_section_id" class="form-label small fw-bold text-muted">القسم الموجه إليه *</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="orient_section_id" name="section_id" required>
                            <option value="">-- اختر القسم البيداغوجي --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="decision" class="form-label small fw-bold text-muted">قرار التوجيه والالتحاق</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="decision" name="validation" required>
                            <option value="0">قيد الدراسة والتحكيم</option>
                            <option value="1">مقبول وموجه رسمياً (Admis)</option>
                            <option value="2">مرفوض لعدم مطابقة الملف (Rejeté)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statut_dossier" class="form-label small fw-bold text-muted">حالة دراسة الملف الإداري</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="statut_dossier" name="statut_dossier" required>
                            <option value="en_attente">قيد الانتظار والدراسة (En attente)</option>
                            <option value="valide">ملف مقبول ومصادق عليه (Validé)</option>
                            <option value="rejete">ملف ناقص أو ملغى (Rejeté)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">حفظ قرار التوجيه</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Orienter Modal -->
<div class="modal fade" id="bulkOrienterModal" tabindex="-1" aria-labelledby="bulkOrienterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="bulkOrienterModalLabel"><i class="fa-solid fa-users-gear text-primary me-2"></i> معالجة وتوجيه جماعي للمترشحين</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/dashboard/inscriptions/orienter-bulk" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
                <input type="hidden" id="bulk_candidate_ids" name="candidate_ids">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">عدد المترشحين المحددين</label>
                        <input type="text" class="form-control rounded-pill border-0 bg-light px-3 fw-bold text-primary" id="bulk_candidates_count" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="bulk_section_id" class="form-label small fw-bold text-muted">القسم البيداغوجي الموجه إليه (الفرع) *</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="bulk_section_id" name="bulk_section_id" required>
                            <option value="" disabled selected>-- اختر القسم والفرع المستهدف --</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['spec_ar'] . ' - ' . $sec['nom_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="bulk_validation" class="form-label small fw-bold text-muted">قرار التوجيه والالتحاق الجماعي</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="bulk_validation" name="bulk_validation" required>
                            <option value="1" selected>مقبول وموجه رسمياً (Admis)</option>
                            <option value="0">قيد الدراسة والتحكيم</option>
                            <option value="2">مرفوض لعدم مطابقة الملف (Rejeté)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="bulk_statut_dossier" class="form-label small fw-bold text-muted">حالة دراسة الملف الإداري</label>
                        <select class="form-select rounded-pill border-light-subtle shadow-sm px-3" id="bulk_statut_dossier" name="bulk_statut_dossier" required>
                            <option value="valide" selected>ملف مقبول ومصادق عليه (Validé)</option>
                            <option value="en_attente">قيد الانتظار والدراسة (En attente)</option>
                            <option value="rejete">ملف ناقص أو ملغى (Rejeté)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #0f7a55 0%, #17a773 100%); border: none;">حفظ وتوجيه الكل دفعة واحدة</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function filterWilaya(val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set('wilaya_id', val);
    } else {
        url.searchParams.delete('wilaya_id');
    }
    url.searchParams.delete('etab_id'); // clear etab when wilaya changes to avoid mismatch
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function filterEtab(val) {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set('etab_id', val);
    } else {
        url.searchParams.delete('etab_id');
    }
    url.searchParams.set('page', '1'); // reset to first page on filter change
    window.location.href = url.toString();
}

function changePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('limit', val);
    url.searchParams.set('page', '1'); // reset to first page
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const orientButtons = document.querySelectorAll('.orient-btn');
    const modalEl = document.getElementById('orienterModal');
    const bulkModalEl = document.getElementById('bulkOrienterModal');

    const selectAllCheckbox = document.getElementById('select_all_candidates');
    const candidateSelects = document.querySelectorAll('.candidate-select');
    const bulkActionsBar = document.getElementById('bulk_actions_bar');
    const selectedCountBadge = document.getElementById('selected_count_badge');

    const orientOffreSelect = document.getElementById('orient_offre_id');
    const orientSectionSelect = document.getElementById('orient_section_id');

    // Dynamic section loading
    function loadSectionsForOffer(offreId, selectedSectionId = null) {
        if (!offreId) return;
        orientSectionSelect.innerHTML = '<option value="" disabled selected>جاري التحميل...</option>';
        fetch('/dashboard/inscriptions/ajax/sections-by-offre/' + offreId)
            .then(res => res.json())
            .then(sections => {
                orientSectionSelect.innerHTML = '';
                if (sections.length === 0) {
                    orientSectionSelect.innerHTML = '<option value="" disabled>لا توجد أقسام متوفرة لهذا العرض</option>';
                    return;
                }
                sections.forEach(sec => {
                    const opt = document.createElement('option');
                    opt.value = sec.id;
                    opt.textContent = sec.nom_ar;
                    if (selectedSectionId && sec.id == selectedSectionId) {
                        opt.selected = true;
                    }
                    orientSectionSelect.appendChild(opt);
                });
            })
            .catch(err => {
                orientSectionSelect.innerHTML = '<option value="" disabled>خطأ في جلب الأقسام</option>';
            });
    }

    orientOffreSelect.addEventListener('change', function() {
        loadSectionsForOffer(this.value);
    });

    // Single Orientation Button Handler
    orientButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id         = this.getAttribute('data-id');
            const name       = this.getAttribute('data-name');
            const validation = this.getAttribute('data-validation');
            const status     = this.getAttribute('data-status');
            const offreId    = this.getAttribute('data-offre-id');
            const sectionId  = this.getAttribute('data-section-id');

            document.getElementById('orient_id').value        = id;
            document.getElementById('orient_name').value      = name;
            document.getElementById('decision').value         = validation;
            document.getElementById('statut_dossier').value   = status;
            document.getElementById('orient_offre_id').value  = offreId;

            loadSectionsForOffer(offreId, sectionId);

            const existing = bootstrap.Modal.getInstance(modalEl);
            if (existing) {
                existing.dispose();
            }

            const orientModal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
            orientModal.show();
        });
    });

    // Bulk selection logic
    function updateBulkActionsBar() {
        const checked = document.querySelectorAll('.candidate-select:checked');
        if (checked.length > 0) {
            selectedCountBadge.textContent = checked.length;
            bulkActionsBar.classList.remove('d-none');
        } else {
            bulkActionsBar.classList.add('d-none');
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            candidateSelects.forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
            });
            updateBulkActionsBar();
        });
    }

    candidateSelects.forEach(cb => {
        cb.addEventListener('change', function() {
            updateBulkActionsBar();
            if (!this.checked) {
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
            } else {
                const totalChecked = document.querySelectorAll('.candidate-select:checked').length;
                if (totalChecked === candidateSelects.length && selectAllCheckbox) {
                    selectAllCheckbox.checked = true;
                }
            }
        });
    });

    document.getElementById('clear_selection').addEventListener('click', function() {
        candidateSelects.forEach(cb => cb.checked = false);
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        updateBulkActionsBar();
    });

    // Bulk Modal Trigger
    document.getElementById('trigger_bulk_modal').addEventListener('click', function() {
        const checked = document.querySelectorAll('.candidate-select:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        
        document.getElementById('bulk_candidate_ids').value = ids.join(',');
        document.getElementById('bulk_candidates_count').value = checked.length + ' مترشح(ين)';
        
        const existing = bootstrap.Modal.getInstance(bulkModalEl);
        if (existing) {
            existing.dispose();
        }

        const bulkModal = new bootstrap.Modal(bulkModalEl, { backdrop: true, keyboard: true });
        bulkModal.show();
    });

    // Cleanup backdrops
    modalEl.addEventListener('hidden.bs.modal', function () {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
    });
    bulkModalEl.addEventListener('hidden.bs.modal', function () {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
    });
});
</script>


@endsection
