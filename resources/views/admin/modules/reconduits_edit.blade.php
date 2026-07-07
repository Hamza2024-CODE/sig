@extends('layouts.main')
@section('title', $title ?? 'تعديل بيانات المتربص المستمر')
@section('content')
<?php
/**
 * @var array $apprenant
 * @var array $candidate
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-user-pen text-primary me-2"></i> <?= htmlspecialchars($title) ?>
            </h3>
            <p class="text-muted mb-0 small mt-1">
                تعديل ملف المتربص: <span class="badge bg-primary-subtle text-primary ms-1 px-3 py-2 fw-bold" style="font-size: 0.85rem;">#<?= htmlspecialchars($apprenant['IDapprenant']) ?></span>
                | القسم: <span class="badge bg-secondary-subtle text-secondary ms-1 px-3 py-2 fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($apprenant['section_nom'] ?? 'غير محدد') ?></span>
            </p>
        </div>
        <div>
            <a href="/dashboard/reconduits/details/<?= $apprenant['IDEts_Form'] ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-arrow-right me-2"></i> إلغاء ورجوع
            </a>
        </div>
    </div>

    <!-- Edit Form Card -->
    <form action="/dashboard/reconduits/update/<?= $apprenant['IDapprenant'] ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf
        <div class="row">
            <!-- Right Column: Personal & Contact Info -->
            <div class="col-lg-9 col-md-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="fa-solid fa-address-card text-primary me-2"></i> 
                            المعلومات الشخصية والمدنية للمتربص
                        </h5>
                        <hr class="mt-3 mb-0 text-muted">
                    </div>
                    <div class="card-body p-4">
                        <!-- Section 1: Names -->
                        <h6 class="fw-bold mb-3 text-primary" style="font-family:'Cairo', sans-serif;"><i class="fa-solid fa-signature me-1"></i> الاسم واللقب (بالعربية والفرنسية)</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="nom" class="form-label fw-bold text-secondary small">اللقب (بالعربية)</label>
                                <input type="text" class="form-control rounded-3" id="nom" name="nom" value="<?= htmlspecialchars($candidate['Nom'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="prenom" class="form-label fw-bold text-secondary small">الاسم (بالعربية)</label>
                                <input type="text" class="form-control rounded-3" id="prenom" name="prenom" value="<?= htmlspecialchars($candidate['Prenom'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="nom_fr" class="form-label fw-bold text-secondary small">Nom (en Français)</label>
                                <input type="text" class="form-control rounded-3 text-uppercase" id="nom_fr" name="nom_fr" value="<?= htmlspecialchars($candidate['NomFr'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="prenom_fr" class="form-label fw-bold text-secondary small">Prénom (en Français)</label>
                                <input type="text" class="form-control rounded-3" id="prenom_fr" name="prenom_fr" value="<?= htmlspecialchars($candidate['PrenomFr'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Section 2: Birth Details & Civil Status -->
                        <h6 class="fw-bold mb-3 text-primary" style="font-family:'Cairo', sans-serif;"><i class="fa-solid fa-baby me-1"></i> معلومات الميلاد والهوية</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="date_nais" class="form-label fw-bold text-secondary small">تاريخ الميلاد</label>
                                <input type="text" class="form-control rounded-3" id="date_nais" name="date_nais" placeholder="YYYY-MM-DD" value="<?= htmlspecialchars($candidate['DateNais'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="lieu_nais" class="form-label fw-bold text-secondary small">مكان الميلاد (بالعربية)</label>
                                <input type="text" class="form-control rounded-3" id="lieu_nais" name="lieu_nais" value="<?= htmlspecialchars($candidate['LieuNais'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="lieu_nais_fr" class="form-label fw-bold text-secondary small">Lieu de naissance (FR)</label>
                                <input type="text" class="form-control rounded-3" id="lieu_nais_fr" name="lieu_nais_fr" value="<?= htmlspecialchars($candidate['LieuNaisFr'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="num_acte_nais" class="form-label fw-bold text-secondary small">رقم عقد الميلاد</label>
                                <input type="text" class="form-control rounded-3" id="num_acte_nais" name="num_acte_nais" value="<?= htmlspecialchars($candidate['NumActeNais'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Section 3: Parents -->
                        <h6 class="fw-bold mb-3 text-primary" style="font-family:'Cairo', sans-serif;"><i class="fa-solid fa-people-roof me-1"></i> معلومات الحالة العائلية (الوالدين)</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="prenom_pere" class="form-label fw-bold text-secondary small">اسم الأب</label>
                                <input type="text" class="form-control rounded-3" id="prenom_pere" name="prenom_pere" value="<?= htmlspecialchars($candidate['PrenomPere'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="nom_mere" class="form-label fw-bold text-secondary small">لقب الأم</label>
                                <input type="text" class="form-control rounded-3" id="nom_mere" name="nom_mere" value="<?= htmlspecialchars($candidate['NomMere'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="prenom_mere" class="form-label fw-bold text-secondary small">اسم الأم</label>
                                <input type="text" class="form-control rounded-3" id="prenom_mere" name="prenom_mere" value="<?= htmlspecialchars($candidate['PrenomMere'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Section 4: Contact & Identification -->
                        <h6 class="fw-bold mb-3 text-primary" style="font-family:'Cairo', sans-serif;"><i class="fa-solid fa-id-card-clip me-1"></i> معلومات الهوية والاتصال</h6>
                        <div class="row g-3 mb-2">
                            <div class="col-md-3">
                                <label for="civ" class="form-label fw-bold text-secondary small">الجنس</label>
                                <select class="form-select rounded-3" id="civ" name="civ">
                                    <option value="1" <?= (int)($candidate['Civ'] ?? 1) === 1 ? 'selected' : '' ?>>ذكر / Homme</option>
                                    <option value="2" <?= (int)($candidate['Civ'] ?? 1) === 2 ? 'selected' : '' ?>>أنثى / Femme</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="nin" class="form-label fw-bold text-secondary small">رقم التعريف الوطني (NIN)</label>
                                <input type="text" class="form-control rounded-3" id="nin" name="nin" value="<?= htmlspecialchars($candidate['Nin'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="nss" class="form-label fw-bold text-secondary small">رقم الضمان الاجتماعي (NSS)</label>
                                <input type="text" class="form-control rounded-3" id="nss" name="nss" value="<?= htmlspecialchars($candidate['Nss'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tel" class="form-label fw-bold text-secondary small">رقم الهاتف</label>
                                <input type="text" class="form-control rounded-3" id="tel" name="tel" value="<?= htmlspecialchars($candidate['Tel'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="email" class="form-label fw-bold text-secondary small">البريد الإلكتروني</label>
                                <input type="email" class="form-control rounded-3" id="email" name="email" value="<?= htmlspecialchars($candidate['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="adres" class="form-label fw-bold text-secondary small">عنوان الإقامة الحالي</label>
                                <input type="text" class="form-control rounded-3" id="adres" name="adres" value="<?= htmlspecialchars($candidate['Adres'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light border-0 p-3 px-4 d-flex justify-content-end gap-2 rounded-bottom-4">
                        <a href="/dashboard/reconduits/details/<?= $apprenant['IDEts_Form'] ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">إلغاء</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" style="background: linear-gradient(135deg, #482b8f 0%, #643edb 100%); border: none;">
                            <i class="fa-solid fa-floppy-disk me-2"></i> حفظ التغييرات والتحيين
                        </button>
                    </div>
                </div>
            </div>

            <!-- Left Column: Photo & Action Summary -->
            <div class="col-lg-3 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4">
                    <h5 class="fw-bold text-dark mb-3">صورة المتربص</h5>
                    
                    <!-- Profile Photo Frame -->
                    <div class="position-relative d-inline-block mx-auto mb-3" style="width: 150px; height: 180px;">
                        <?php if (!empty($candidate['photo'])): ?>
                            <img id="photo-preview" src="/sig<?= htmlspecialchars($candidate['photo']) ?>" class="img-thumbnail w-100 h-100 object-fit-cover shadow-sm rounded-3" alt="Photo Trainee">
                        <?php else: ?>
                            <img id="photo-preview" src="https://ui-avatars.com/api/?name=Trainee&background=643edb&color=fff&size=150" class="img-thumbnail w-100 h-100 object-fit-cover shadow-sm rounded-3" alt="Placeholder">
                        <?php endif; ?>
                    </div>

                    <p class="text-muted small mb-3">الصيغ المقبولة: JPG, PNG. الحد الأقصى للملف: 2MB.</p>
                    
                    <!-- File Input wrapper -->
                    <div class="mb-3">
                        <label for="photo" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold w-100">
                            <i class="fa-solid fa-camera me-1"></i> تحميل صورة جديدة
                        </label>
                        <input type="file" id="photo" name="photo" accept="image/*" class="d-none" onchange="previewImage(this)">
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-0 rounded-3 text-start small">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        سيتم حفظ الصورة المرفوعة وربطها بملف المترشح فور حفظ الاستمارة.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo-preview').setAttribute('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

@endsection
