@extends('layouts.main')

@section('title', 'المدونة الوطنية للشعب والقطاعات المهنية (RNFC) — SGFEP')

@section('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   RNFC DIRECTORY PORTAL — Premium Glassmorphic Design
   ═══════════════════════════════════════════════════════════════ */
.rnfc-hero {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 45px rgba(13, 148, 136, 0.15);
}
.rnfc-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.06) 0%, transparent 70%);
    pointer-events: none;
}
.rnfc-hero-title {
    font-size: 1.6rem;
    font-weight: 900;
    color: #fff;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 0.3rem;
}
.rnfc-hero-sub {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.85);
    font-family: 'Cairo', sans-serif;
}

.rnfc-card {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--border, #e8edf5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.015);
    margin-bottom: 1.5rem;
}

/* Stats Widget */
.rnfc-stat-card {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid var(--border, #e8edf5);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
.rnfc-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.03);
}
.rnfc-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.rnfc-stat-number {
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1.2;
}
.rnfc-stat-label {
    font-size: 0.78rem;
    color: #718096;
}

/* Explorer Layout */
.explorer-container {
    display: grid;
    grid-template-columns: 1fr 1.2fr 1.4fr 1.6fr;
    gap: 1rem;
    min-height: 500px;
}
@media (max-width: 992px) {
    .explorer-container {
        grid-template-columns: 1fr;
    }
}
.explorer-column {
    background: var(--bg-surface-elevated, #f8fafc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.explorer-header {
    background: #e2e8f0;
    padding: 0.75rem 1rem;
    font-weight: 700;
    font-size: 0.85rem;
    color: #334155;
    border-bottom: 1px solid var(--border, #cbd5e1);
}
.explorer-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
    max-height: 450px;
}
.explorer-item {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 0.25rem;
    font-size: 0.82rem;
    transition: all 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border: 1px solid #f1f5f9;
}
.explorer-item:hover {
    background: #f1f5f9;
}
.explorer-item.active {
    background: #0d9488;
    color: #fff;
    border-color: #0d9488;
}
.explorer-item.active span {
    color: #fff;
}
.explorer-item.active .badge {
    background: rgba(255,255,255,0.2) !important;
    color: #fff !important;
}

.specialty-list-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.8rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #64748b;
}

/* Modals */
.modal-cairo {
    font-family: 'Cairo', sans-serif;
}
</style>
@endsection

@section('content')
<div class="container-fluid py-4 modal-cairo">
    
    <!-- Hero Header -->
    <div class="rnfc-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="rnfc-hero-title">
                    <i class="fa-solid fa-sitemap me-2 text-warning"></i>
                    المدونة الوطنية للشعب والقطاعات المهنية (RNFC)
                </h1>
                <p class="rnfc-hero-sub mb-0">
                    النظام المركزي الموحد لتصنيف الفئات والشعب والقطاعات المهنية لوزارة التكوين والتعليم المهنيين
                </p>
            </div>
            <div>
                @if(in_array($role, ['admin', 'central', 'high_admin']))
                    <a href="{{ url('dashboard/sync') }}" class="btn btn-outline-light btn-sm">
                        <i class="fa-solid fa-rotate me-1"></i> مزامنة الجداول من HFSQL
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-sm-6">
            <div class="rnfc-stat-card">
                <div class="rnfc-stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <div class="rnfc-stat-number text-primary">{{ $counts['classifications'] }}</div>
                    <div class="rnfc-stat-label">التصنيفات الرئيسية</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="rnfc-stat-card">
                <div class="rnfc-stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <div>
                    <div class="rnfc-stat-number text-success">{{ $counts['secteurs'] }}</div>
                    <div class="rnfc-stat-label">القطاعات المهنية</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="rnfc-stat-card">
                <div class="rnfc-stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <div>
                    <div class="rnfc-stat-number text-info">{{ $counts['domaines'] }}</div>
                    <div class="rnfc-stat-label">المجالات المهنية</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="rnfc-stat-card">
                <div class="rnfc-stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <div class="rnfc-stat-number text-warning">{{ $counts['sousdomaines'] }}</div>
                    <div class="rnfc-stat-label">الشعب المهنية الدقيقة</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="rnfc-stat-card">
                <div class="rnfc-stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <div class="rnfc-stat-number text-danger">{{ $counts['specialites'] }}</div>
                    <div class="rnfc-stat-label">التخصصات المرتبطة بالمدونة</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rnfc-card">
        <ul class="nav nav-pills mb-4 gap-2" id="rnfcTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="explorer-tab" data-bs-toggle="tab" data-bs-target="#explorer-pane" type="button" role="tab" aria-controls="explorer-pane" aria-selected="true">
                    <i class="fa-solid fa-cubes me-1"></i> المستكشف التفاعلي
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="class-tab" data-bs-toggle="tab" data-bs-target="#class-pane" type="button" role="tab" aria-controls="class-pane" aria-selected="false">
                    <i class="fa-solid fa-layer-group me-1"></i> التصنيفات الكبرى
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="secteurs-tab" data-bs-toggle="tab" data-bs-target="#secteurs-pane" type="button" role="tab" aria-controls="secteurs-pane" aria-selected="false">
                    <i class="fa-solid fa-briefcase me-1"></i> القطاعات المهنية
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="domaines-tab" data-bs-toggle="tab" data-bs-target="#domaines-pane" type="button" role="tab" aria-controls="domaines-pane" aria-selected="false">
                    <i class="fa-solid fa-folder-open me-1"></i> المجالات المهنية
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="sousdomaines-tab" data-bs-toggle="tab" data-bs-target="#sousdomaines-pane" type="button" role="tab" aria-controls="sousdomaines-pane" aria-selected="false">
                    <i class="fa-solid fa-sliders me-1"></i> الشعب المهنية الدقيقة
                </button>
            </li>
        </ul>

        <div class="tab-content" id="rnfcTabContent">
            
            <!-- tab 1: Interactive Explorer -->
            <div class="tab-pane fade show active" id="explorer-pane" role="tabpanel" aria-labelledby="explorer-tab" tabindex="0">
                <div class="mb-3">
                    <p class="text-muted small">انقر على أي تصنيف لعرض قطاعاته المهنية، ثم انقر على القطاع لعرض مجالاته، ثم انقر على المجال لعرض شعبه الدقيقة وتخصصاته المرتبطة.</p>
                </div>
                
                <div class="explorer-container">
                    <!-- Column 1: Classifications -->
                    <div class="explorer-column">
                        <div class="explorer-header d-flex justify-content-between align-items-center">
                            <span>1. التصنيف الكلي</span>
                            <span class="badge bg-secondary rounded-pill">{{ $classifications->count() }}</span>
                        </div>
                        <div class="explorer-list" id="explorer-classifications">
                            @foreach($classifications as $c)
                                <div class="explorer-item" onclick="selectClassification({{ $c->IDclassification_rnfc }}, this)">
                                    <span class="fw-bold">{{ $c->NomFr }}</span>
                                    <i class="fa-solid fa-chevron-left text-muted small"></i>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Column 2: Secteurs -->
                    <div class="explorer-column">
                        <div class="explorer-header d-flex justify-content-between align-items-center">
                            <span>2. القطاع المهني</span>
                            <span class="badge bg-secondary rounded-pill" id="badge-secteurs-count">0</span>
                        </div>
                        <div class="explorer-list" id="explorer-secteurs">
                            <div class="empty-state">اختر تصنيفاً رئيسياً لعرض القطاعات...</div>
                        </div>
                    </div>

                    <!-- Column 3: Domaines -->
                    <div class="explorer-column">
                        <div class="explorer-header d-flex justify-content-between align-items-center">
                            <span>3. المجال المهني</span>
                            <span class="badge bg-secondary rounded-pill" id="badge-domaines-count">0</span>
                        </div>
                        <div class="explorer-list" id="explorer-domaines">
                            <div class="empty-state">اختر قطاعاً مهنياً لعرض المجالات...</div>
                        </div>
                    </div>

                    <!-- Column 4: Sous-domaines & Specialties -->
                    <div class="explorer-column">
                        <div class="explorer-header d-flex justify-content-between align-items-center">
                            <span>4. الشعب الدقيقة والتخصصات</span>
                            <span class="badge bg-secondary rounded-pill" id="badge-sousdomaines-count">0</span>
                        </div>
                        <div class="explorer-list" id="explorer-sousdomaines">
                            <div class="empty-state">اختر مجالاً مهنياً لعرض الشعب والتخصصات...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Classifications Table -->
            <div class="tab-pane fade" id="class-pane" role="tabpanel" aria-labelledby="class-tab" tabindex="0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark">جدول التصنيفات الكبرى</h5>
                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                        <button type="button" class="btn btn-teal btn-sm" onclick="openAddClassificationModal()">
                            <i class="fa-solid fa-plus me-1"></i> إضافة تصنيف جديد
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>معرف التصنيف</th>
                                <th>الاسم بالعربية</th>
                                <th>الاسم بالفرنسية</th>
                                @if(in_array($role, ['admin', 'central', 'high_admin']))
                                    <th class="text-center">الإجراءات</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classifications as $c)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $c->IDclassification_rnfc }}</td>
                                    <td>{{ $c->Nom ?: '—' }}</td>
                                    <td>{{ $c->NomFr }}</td>
                                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                                        <td class="text-center">
                                            <div class="btn-group gap-1">
                                                <button class="btn btn-sm btn-outline-primary border-0" onclick="openEditClassificationModal({{ json_encode($c) }})">
                                                    <i class="fa-solid fa-pen-to-square"></i> تعديل
                                                </button>
                                                <form method="POST" action="{{ url('dashboard/rnfc/classification/delete/'.$c->IDclassification_rnfc) }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا التصنيف؟');" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                        <i class="fa-solid fa-trash"></i> حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Secteurs Table -->
            <div class="tab-pane fade" id="secteurs-pane" role="tabpanel" aria-labelledby="secteurs-tab" tabindex="0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark">جدول القطاعات المهنية</h5>
                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                        <button type="button" class="btn btn-teal btn-sm" onclick="openAddSecteurModal()">
                            <i class="fa-solid fa-plus me-1"></i> إضافة قطاع جديد
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>معرف القطاع</th>
                                <th>الرمز</th>
                                <th>الاسم بالعربية</th>
                                <th>الاسم بالفرنسية</th>
                                <th>التصنيف الرئيسي</th>
                                @if(in_array($role, ['admin', 'central', 'high_admin']))
                                    <th class="text-center">الإجراءات</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($secteurs as $s)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $s->IDSecteur_rnfc }}</td>
                                    <td class="font-monospace">{{ $s->code }}</td>
                                    <td>{{ $s->Nom ?: '—' }}</td>
                                    <td>{{ $s->NomFr }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $s->classificationRnfc->NomFr ?? 'غير مصنف' }}</span>
                                    </td>
                                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                                        <td class="text-center">
                                            <div class="btn-group gap-1">
                                                <button class="btn btn-sm btn-outline-primary border-0" onclick="openEditSecteurModal({{ json_encode($s) }})">
                                                    <i class="fa-solid fa-pen-to-square"></i> تعديل
                                                </button>
                                                <form method="POST" action="{{ url('dashboard/rnfc/secteur/delete/'.$s->IDSecteur_rnfc) }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا القطاع؟');" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                        <i class="fa-solid fa-trash"></i> حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">لا يوجد قطاعات مهنية مسجلة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 4: Domaines Table -->
            <div class="tab-pane fade" id="domaines-pane" role="tabpanel" aria-labelledby="domaines-tab" tabindex="0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark">جدول المجالات المهنية</h5>
                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                        <button type="button" class="btn btn-teal btn-sm" onclick="openAddDomaineModal()">
                            <i class="fa-solid fa-plus me-1"></i> إضافة مجال جديد
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>معرف المجال</th>
                                <th>الرمز</th>
                                <th>الاسم بالعربية</th>
                                <th>الاسم بالفرنسية</th>
                                <th>القطاع المهني</th>
                                @if(in_array($role, ['admin', 'central', 'high_admin']))
                                    <th class="text-center">الإجراءات</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($domaines as $d)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $d->IDdomaine_rnfc }}</td>
                                    <td class="font-monospace">{{ $d->code }}</td>
                                    <td>{{ $d->Nom ?: '—' }}</td>
                                    <td>{{ $d->NomFr }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $d->secteurRnfc->NomFr ?? 'غير محدد' }}</span>
                                    </td>
                                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                                        <td class="text-center">
                                            <div class="btn-group gap-1">
                                                <button class="btn btn-sm btn-outline-primary border-0" onclick="openEditDomaineModal({{ json_encode($d) }})">
                                                    <i class="fa-solid fa-pen-to-square"></i> تعديل
                                                </button>
                                                <form method="POST" action="{{ url('dashboard/rnfc/domaine/delete/'.$d->IDdomaine_rnfc) }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا المجال؟');" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                        <i class="fa-solid fa-trash"></i> حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">لا يوجد مجالات مهنية مسجلة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 5: Sous-domaines Table -->
            <div class="tab-pane fade" id="sousdomaines-pane" role="tabpanel" aria-labelledby="sousdomaines-tab" tabindex="0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark">جدول الشعب المهنية الدقيقة</h5>
                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                        <button type="button" class="btn btn-teal btn-sm" onclick="openAddSousdomaineModal()">
                            <i class="fa-solid fa-plus me-1"></i> إضافة شعبة دقيقة جديدة
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>معرف الشعبة</th>
                                <th>الرمز</th>
                                <th>الاسم بالعربية</th>
                                <th>الاسم بالفرنسية</th>
                                <th>المجال المهني</th>
                                @if(in_array($role, ['admin', 'central', 'high_admin']))
                                    <th class="text-center">الإجراءات</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sousdomaines as $sd)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $sd->IDsousdomaine_rnfc }}</td>
                                    <td class="font-monospace">{{ $sd->code }}</td>
                                    <td>{{ $sd->Nom ?: '—' }}</td>
                                    <td>{{ $sd->NomFr }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $sd->domaineRnfc->NomFr ?? 'غير محدد' }}</span>
                                    </td>
                                    @if(in_array($role, ['admin', 'central', 'high_admin']))
                                        <td class="text-center">
                                            <div class="btn-group gap-1">
                                                <button class="btn btn-sm btn-outline-primary border-0" onclick="openEditSousdomaineModal({{ json_encode($sd) }})">
                                                    <i class="fa-solid fa-pen-to-square"></i> تعديل
                                                </button>
                                                <form method="POST" action="{{ url('dashboard/rnfc/sousdomaine/delete/'.$sd->IDsousdomaine_rnfc) }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذه الشعبة الدقيقة؟');" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                        <i class="fa-solid fa-trash"></i> حذف
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">لا يوجد شعب دقيقة مسجلة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ==========================================
     MODALS FOR CLASSIFICATION CRUD
     ========================================== -->
<div class="modal fade modal-cairo" id="classificationModal" tabindex="-1" aria-labelledby="classificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="classificationForm" class="modal-content">
            @csrf
            <input type="hidden" name="id" id="class-id">
            <div class="modal-header bg-teal text-white">
                <h5 class="modal-title fw-bold" id="classificationModalLabel">إضافة تصنيف جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالعربية</label>
                    <input type="text" name="Nom" id="class-nom" class="form-control" placeholder="مثال: قطاع الفلاحة والصيد البحري">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالفرنسية <span class="text-danger">*</span></label>
                    <input type="text" name="NomFr" id="class-nomfr" class="form-control" placeholder="مثال: Secteur Primaire" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-teal btn-sm">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODALS FOR SECTEUR CRUD
     ========================================== -->
<div class="modal fade modal-cairo" id="secteurModal" tabindex="-1" aria-labelledby="secteurModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="secteurForm" class="modal-content">
            @csrf
            <input type="hidden" name="id" id="secteur-id">
            <div class="modal-header bg-teal text-white">
                <h5 class="modal-title fw-bold" id="secteurModalLabel">إضافة قطاع جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالعربية</label>
                    <input type="text" name="Nom" id="secteur-nom" class="form-control" placeholder="مثال: الفلاحة والصيد">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالفرنسية <span class="text-danger">*</span></label>
                    <input type="text" name="NomFr" id="secteur-nomfr" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الرمز (code) <span class="text-danger">*</span></label>
                    <input type="number" name="code" id="secteur-code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">التصنيف الكلي <span class="text-danger">*</span></label>
                    <select name="IDclassification_rnfc" id="secteur-class" class="form-select" required>
                        <option value="">اختر التصنيف الكلي...</option>
                        @foreach($classifications as $c)
                            <option value="{{ $c->IDclassification_rnfc }}">{{ $c->NomFr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-teal btn-sm">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODALS FOR DOMAINE CRUD
     ========================================== -->
<div class="modal fade modal-cairo" id="domaineModal" tabindex="-1" aria-labelledby="domaineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="domaineForm" class="modal-content">
            @csrf
            <input type="hidden" name="id" id="domaine-id">
            <div class="modal-header bg-teal text-white">
                <h5 class="modal-title fw-bold" id="domaineModalLabel">إضافة مجال جديد</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالعربية</label>
                    <input type="text" name="Nom" id="domaine-nom" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالفرنسية <span class="text-danger">*</span></label>
                    <input type="text" name="NomFr" id="domaine-nomfr" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الرمز (code) <span class="text-danger">*</span></label>
                    <input type="number" name="code" id="domaine-code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">القطاع المهني <span class="text-danger">*</span></label>
                    <select name="IDSecteur_rnfc" id="domaine-secteur" class="form-select" required>
                        <option value="">اختر القطاع المهني...</option>
                        @foreach($secteurs as $s)
                            <option value="{{ $s->IDSecteur_rnfc }}">{{ $s->NomFr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-teal btn-sm">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODALS FOR SOUSDOMAINE CRUD
     ========================================== -->
<div class="modal fade modal-cairo" id="sousdomaineModal" tabindex="-1" aria-labelledby="sousdomaineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="sousdomaineForm" class="modal-content">
            @csrf
            <input type="hidden" name="id" id="sousdomaine-id">
            <div class="modal-header bg-teal text-white">
                <h5 class="modal-title fw-bold" id="sousdomaineModalLabel">إضافة شعبة دقيقة جديدة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالعربية</label>
                    <input type="text" name="Nom" id="sousdomaine-nom" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الاسم بالفرنسية <span class="text-danger">*</span></label>
                    <input type="text" name="NomFr" id="sousdomaine-nomfr" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">الرمز (code) <span class="text-danger">*</span></label>
                    <input type="number" name="code" id="sousdomaine-code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">المجال المهني <span class="text-danger">*</span></label>
                    <select name="IDdomaine_rnfc" id="sousdomaine-domaine" class="form-select" required>
                        <option value="">اختر المجال المهني...</option>
                        @foreach($domaines as $d)
                            <option value="{{ $d->IDdomaine_rnfc }}">{{ $d->NomFr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-teal btn-sm">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>

<script>
// Load preloaded datasets
const classifications = @json($classifications);
const secteurs = @json($secteurs);
const domaines = @json($domaines);
const sousdomaines = @json($sousdomaines);

// Track selected IDs in visual explorer
let activeClassId = null;
let activeSecteurId = null;
let activeDomaineId = null;

function selectClassification(id, element) {
    // Highlight element
    document.querySelectorAll('#explorer-classifications .explorer-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    
    activeClassId = id;
    activeSecteurId = null;
    activeDomaineId = null;
    
    // Clear sub-columns
    document.getElementById('explorer-domaines').innerHTML = '<div class="empty-state">اختر قطاعاً مهنياً لعرض المجالات...</div>';
    document.getElementById('explorer-sousdomaines').innerHTML = '<div class="empty-state">اختر مجالاً مهنياً لعرض الشعب والتخصصات...</div>';
    document.getElementById('badge-domaines-count').textContent = '0';
    document.getElementById('badge-sousdomaines-count').textContent = '0';
    
    // Load Secteurs
    const filtered = secteurs.filter(s => s.IDclassification_rnfc == id);
    document.getElementById('badge-secteurs-count').textContent = filtered.length;
    
    let html = '';
    if(filtered.length === 0) {
        html = '<div class="empty-state">لا يوجد قطاعات مرتبطة بهذا التصنيف.</div>';
    } else {
        filtered.forEach(s => {
            html += `
                <div class="explorer-item" onclick="selectSecteur(${s.IDSecteur_rnfc}, this)">
                    <span class="fw-bold">${s.NomFr}</span>
                    <i class="fa-solid fa-chevron-left text-muted small"></i>
                </div>
            `;
        });
    }
    document.getElementById('explorer-secteurs').innerHTML = html;
}

function selectSecteur(id, element) {
    document.querySelectorAll('#explorer-secteurs .explorer-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    
    activeSecteurId = id;
    activeDomaineId = null;
    
    document.getElementById('explorer-sousdomaines').innerHTML = '<div class="empty-state">اختر مجالاً مهنياً لعرض الشعب والتخصصات...</div>';
    document.getElementById('badge-sousdomaines-count').textContent = '0';
    
    const filtered = domaines.filter(d => d.IDSecteur_rnfc == id);
    document.getElementById('badge-domaines-count').textContent = filtered.length;
    
    let html = '';
    if(filtered.length === 0) {
        html = '<div class="empty-state">لا يوجد مجالات مرتبطة بهذا القطاع.</div>';
    } else {
        filtered.forEach(d => {
            html += `
                <div class="explorer-item" onclick="selectDomaine(${d.IDdomaine_rnfc}, this)">
                    <span class="fw-bold">${d.NomFr}</span>
                    <i class="fa-solid fa-chevron-left text-muted small"></i>
                </div>
            `;
        });
    }
    document.getElementById('explorer-domaines').innerHTML = html;
}

function selectDomaine(id, element) {
    document.querySelectorAll('#explorer-domaines .explorer-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    
    activeDomaineId = id;
    
    const filtered = sousdomaines.filter(sd => sd.IDdomaine_rnfc == id);
    document.getElementById('badge-sousdomaines-count').textContent = filtered.length;
    
    let html = '';
    if(filtered.length === 0) {
        html = '<div class="empty-state">لا يوجد شعب دقيقة مرتبطة بهذا المجال.</div>';
    } else {
        filtered.forEach(sd => {
            html += `
                <div class="explorer-item" onclick="selectSousdomaine(${sd.IDsousdomaine_rnfc}, this)">
                    <span class="fw-bold">${sd.NomFr}</span>
                    <i class="fa-solid fa-chevron-left text-muted small"></i>
                </div>
            `;
        });
    }
    document.getElementById('explorer-sousdomaines').innerHTML = html;
}

function selectSousdomaine(id, element) {
    document.querySelectorAll('#explorer-sousdomaines .explorer-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    
    // We can show the list of specialties mapped to this sub-domain
    // Let's call the API or fetch data
    const url = `{{ url('dashboard/settings/sovereign/search-targets') }}?q=`; // we can do custom search, or write local fetch
    
    // For now, let's load dynamically from database via AJAX or display the subdomain details
    const sd = sousdomaines.find(s => s.IDsousdomaine_rnfc == id);
    let html = `
        <div class="p-3 bg-light rounded-3 mb-3 border">
            <h6 class="fw-bold text-teal mb-1"><i class="fa-solid fa-circle-info"></i> معلومات الشعبة المهنية</h6>
            <div class="small mt-2"><strong>الرمز:</strong> <code class="font-monospace">${sd.code}</code></div>
            <div class="small mt-1"><strong>الاسم بالعربية:</strong> ${sd.Nom || '—'}</div>
            <div class="small mt-1"><strong>الاسم بالفرنسية:</strong> ${sd.NomFr}</div>
        </div>
        <div class="p-1">
            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-graduation-cap"></i> التخصصات المرتبطة حالياً:</h6>
            <div id="explorer-specialties-container">
                <div class="text-center py-3"><div class="spinner-border text-teal spinner-border-sm" role="status"></div></div>
            </div>
        </div>
    `;
    
    // Fetch specialities dynamically using our database query page/API
    // Let's build a quick inline fetch to search-targets or custom route
    fetch(`{{ url('dashboard/database/data') }}?table=specialite&where=IDsousdomaine_rnfc%3D${id}`)
        .then(res => res.json())
        .then(response => {
            const container = document.getElementById('explorer-specialties-container');
            const data = response.data || [];
            
            if(data.length === 0) {
                container.innerHTML = '<div class="alert alert-secondary small py-2"><i class="fa-solid fa-info-circle"></i> لا يوجد تخصصات مرتبطة بهذه الشعبة حالياً في جدول التخصصات.</div>';
            } else {
                let listHtml = '';
                data.forEach(spec => {
                    listHtml += `
                        <div class="specialty-list-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-teal">${spec.CodeSpec || '—'}</span>
                                <span class="badge bg-secondary font-monospace" style="font-size:0.65rem;">ID: ${spec.IDSpecialite}</span>
                            </div>
                            <div class="text-dark small fw-bold">${spec.Nom || '—'}</div>
                            <div class="text-muted font-monospace" style="font-size:0.75rem;">${spec.NomFr || '—'}</div>
                        </div>
                    `;
                });
                container.innerHTML = listHtml;
            }
        })
        .catch(err => {
            const container = document.getElementById('explorer-specialties-container');
            container.innerHTML = `<div class="alert alert-danger small py-2">خطأ أثناء تحميل التخصصات: ${err.message}</div>`;
        });
}

// --- Classification Modal Handlers ---
function openAddClassificationModal() {
    document.getElementById('classificationModalLabel').textContent = 'إضافة تصنيف جديد';
    document.getElementById('classificationForm').action = "{{ route('admin.rnfc.classification.store') }}";
    document.getElementById('class-id').value = '';
    document.getElementById('class-nom').value = '';
    document.getElementById('class-nomfr').value = '';
    new bootstrap.Modal(document.getElementById('classificationModal')).show();
}
function openEditClassificationModal(data) {
    document.getElementById('classificationModalLabel').textContent = 'تعديل التصنيف';
    document.getElementById('classificationForm').action = "{{ route('admin.rnfc.classification.update') }}";
    document.getElementById('class-id').value = data.IDclassification_rnfc;
    document.getElementById('class-nom').value = data.Nom || '';
    document.getElementById('class-nomfr').value = data.NomFr || '';
    new bootstrap.Modal(document.getElementById('classificationModal')).show();
}

// --- Secteur Modal Handlers ---
function openAddSecteurModal() {
    document.getElementById('secteurModalLabel').textContent = 'إضافة قطاع جديد';
    document.getElementById('secteurForm').action = "{{ route('admin.rnfc.secteur.store') }}";
    document.getElementById('secteur-id').value = '';
    document.getElementById('secteur-nom').value = '';
    document.getElementById('secteur-nomfr').value = '';
    document.getElementById('secteur-code').value = '';
    document.getElementById('secteur-class').value = '';
    new bootstrap.Modal(document.getElementById('secteurModal')).show();
}
function openEditSecteurModal(data) {
    document.getElementById('secteurModalLabel').textContent = 'تعديل القطاع المهني';
    document.getElementById('secteurForm').action = "{{ route('admin.rnfc.secteur.update') }}";
    document.getElementById('secteur-id').value = data.IDSecteur_rnfc;
    document.getElementById('secteur-nom').value = data.Nom || '';
    document.getElementById('secteur-nomfr').value = data.NomFr || '';
    document.getElementById('secteur-code').value = data.code || '';
    document.getElementById('secteur-class').value = data.IDclassification_rnfc || '';
    new bootstrap.Modal(document.getElementById('secteurModal')).show();
}

// --- Domaine Modal Handlers ---
function openAddDomaineModal() {
    document.getElementById('domaineModalLabel').textContent = 'إضافة مجال جديد';
    document.getElementById('domaineForm').action = "{{ route('admin.rnfc.domaine.store') }}";
    document.getElementById('domaine-id').value = '';
    document.getElementById('domaine-nom').value = '';
    document.getElementById('domaine-nomfr').value = '';
    document.getElementById('domaine-code').value = '';
    document.getElementById('domaine-secteur').value = '';
    new bootstrap.Modal(document.getElementById('domaineModal')).show();
}
function openEditDomaineModal(data) {
    document.getElementById('domaineModalLabel').textContent = 'تعديل المجال المهني';
    document.getElementById('domaineForm').action = "{{ route('admin.rnfc.domaine.update') }}";
    document.getElementById('domaine-id').value = data.IDdomaine_rnfc;
    document.getElementById('domaine-nom').value = data.Nom || '';
    document.getElementById('domaine-nomfr').value = data.NomFr || '';
    document.getElementById('domaine-code').value = data.code || '';
    document.getElementById('domaine-secteur').value = data.IDSecteur_rnfc || '';
    new bootstrap.Modal(document.getElementById('domaineModal')).show();
}

// --- Sousdomaine Modal Handlers ---
function openAddSousdomaineModal() {
    document.getElementById('sousdomaineModalLabel').textContent = 'إضافة شعبة دقيقة جديدة';
    document.getElementById('sousdomaineForm').action = "{{ route('admin.rnfc.sousdomaine.store') }}";
    document.getElementById('sousdomaine-id').value = '';
    document.getElementById('sousdomaine-nom').value = '';
    document.getElementById('sousdomaine-nomfr').value = '';
    document.getElementById('sousdomaine-code').value = '';
    document.getElementById('sousdomaine-domaine').value = '';
    new bootstrap.Modal(document.getElementById('sousdomaineModal')).show();
}
function openEditSousdomaineModal(data) {
    document.getElementById('sousdomaineModalLabel').textContent = 'تعديل الشعبة الدقيقة';
    document.getElementById('sousdomaineForm').action = "{{ route('admin.rnfc.sousdomaine.update') }}";
    document.getElementById('sousdomaine-id').value = data.IDsousdomaine_rnfc;
    document.getElementById('sousdomaine-nom').value = data.Nom || '';
    document.getElementById('sousdomaine-nomfr').value = data.NomFr || '';
    document.getElementById('sousdomaine-code').value = data.code || '';
    document.getElementById('sousdomaine-domaine').value = data.IDdomaine_rnfc || '';
    new bootstrap.Modal(document.getElementById('sousdomaineModal')).show();
}
</script>
@endsection
