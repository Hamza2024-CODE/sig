@extends('layouts.main')
@section('title', 'استخراج حسابات ورموز ولوج المؤسسات - SGFEP')
@section('content')
<style>
    .credentials-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #ffffff;
    }
    .table-credentials th {
        background-color: #f8fafc;
        color: #1e293b;
        font-weight: 700;
        font-size: 0.9rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-credentials td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .badge-role {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
    }
    .secret-code-item {
        background-color: #f1f5f9;
        border-radius: 8px;
        padding: 4px 8px;
        margin-bottom: 4px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
    }
</style>

<div class="animate__animated animate__fadeIn">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #003870; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-key text-primary me-2" style="color: var(--color-gov-blue) !important;"></i> استخراج حسابات ورموز ولوج المؤسسات / Export Credentials
            </h3>
            <p class="text-muted mb-0 small">صفحة مؤقتة لاستخراج وتوزيع بيانات تسجيل الدخول والرموز السرية لكل ولاية ومؤسسة (الانطلاق الرسمي اليوم)</p>
        </div>
        <div>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center">
                <i class="fa-solid fa-file-excel me-2"></i> تحميل ملف Excel / CSV
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card credentials-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ request()->url() }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small">تصفية حسب الولاية</label>
                    <select name="wilaya_id" class="form-select rounded-3">
                        <option value="0">كل الولايات</option>
                        @foreach($wilayas as $w)
                            <option value="{{ $w->IDWilayaa }}" {{ (int)$selectedWilayaId === (int)$w->IDWilayaa ? 'selected' : '' }}>
                                {{ $w->Nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold text-muted small">بحث عن مؤسسة (الاسم، اسم المستخدم، أو الرمز)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="اكتب للبحث..." value="{{ $searchQuery }}">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 w-100 fw-bold">
                        <i class="fa-solid fa-filter me-1"></i> تصفية البحث
                    </button>
                    @if($selectedWilayaId > 0 || !empty($searchQuery))
                        <a href="{{ request()->url() }}" class="btn btn-outline-secondary rounded-3">
                            إعادة تعيين
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Credentials Table -->
    <div class="card credentials-card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold mb-0 text-dark">بيانات الحسابات والرموز السرية ({{ count($etabs) }} مؤسسة)</h5>
            <span class="text-muted small">يمكنك نسخ أي كلمة مرور أو رمز سري مباشرة عبر النقر عليه</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-credentials mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">الولاية</th>
                            <th>رمز المؤسسة</th>
                            <th>اسم المؤسسة</th>
                            <th>اسم المستخدم</th>
                            <th>كلمة المرور</th>
                            <th class="pe-4">الرموز السرية للحسابات الفرعية والمصالح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($etabs as $e)
                            <tr>
                                <td class="ps-4 fw-semibold text-primary">{{ $e->wilaya_nom }}</td>
                                <td><code class="text-dark fw-bold">{{ $e->etab_code }}</code></td>
                                <td class="fw-semibold">{{ $e->etab_nom }}</td>
                                <td><code class="bg-light text-secondary px-2 py-1 rounded">{{ $e->nomUser }}</code></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <code class="text-danger fw-bold" id="pass-{{ $e->IDetablissement }}">{{ $e->MotDePass }}</code>
                                        <button class="btn btn-link p-0 text-muted btn-copy" data-target="pass-{{ $e->IDetablissement }}" title="نسخ">
                                            <i class="fa-solid fa-copy small"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex flex-wrap gap-2">
                                        @php
                                            $assocUsers = $natureUsers[$e->nature_id] ?? [];
                                        @endphp
                                        @forelse($assocUsers as $au)
                                            <div class="secret-code-item shadow-sm">
                                                <span class="badge-role bg-primary text-white">{{ $au['name'] }}</span>
                                                <code>{{ $au['username'] }}</code>
                                                <span class="text-muted">|</span>
                                                <code class="text-success fw-bold" id="code-{{ $e->IDetablissement }}-{{ $au['username'] }}">{{ $au['secret_code'] }}</code>
                                                <button class="btn btn-link p-0 text-muted btn-copy" data-target="code-{{ $e->IDetablissement }}-{{ $au['username'] }}" title="نسخ">
                                                    <i class="fa-solid fa-copy style-small" style="font-size:0.75rem;"></i>
                                                </button>
                                            </div>
                                        @empty
                                            <span class="text-muted small">لا توجد حسابات فرعية لهذه الطبيعة.</span>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-info-circle fa-2x mb-3 text-secondary"></i>
                                    <p class="mb-0">لم يتم العثور على أي مؤسسات تطابق معايير البحث.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy to clipboard functionality
    const copyButtons = document.querySelectorAll('.btn-copy');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const element = document.getElementById(targetId);
            if (element) {
                const text = element.innerText || element.textContent;
                navigator.clipboard.writeText(text).then(() => {
                    const icon = this.querySelector('i');
                    const originalClass = icon.className;
                    icon.className = 'fa-solid fa-check text-success';
                    setTimeout(() => {
                        icon.className = originalClass;
                    }, 1500);
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            }
        });
    });
});
</script>
@endsection
