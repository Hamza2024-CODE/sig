@extends('layouts.main')
@section('title', $title ?? 'طلب تحويل المتربصين')
@section('content')
<div class="animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1e293b; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-arrows-spin text-primary me-2"></i> {{ $title }}
            </h3>
            <p class="text-muted mb-0 small mt-1">متابعة ومعالجة طلبات تحويل المتربصين المستمرين بين المؤسسات التكوينية.</p>
        </div>
        <div>
            <a href="/dashboard/reconduits" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fa-solid fa-arrow-right me-2"></i> العودة لقائمة تعداد المستمرين
            </a>
        </div>
    </div>

    <!-- Session Feedback alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Main List Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-clipboard-list text-primary me-2"></i> سجل طلبات التحويل
            </h5>
        </div>
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4">المتربص</th>
                            <th>من مؤسسة (المرسلة)</th>
                            <th>إلى مؤسسة (المستقبلة)</th>
                            <th>التخصص / القسم الجديد</th>
                            <th>تاريخ الطلب</th>
                            <th>حالة الطلب الحالية</th>
                            <th class="pe-4 text-end">العمليات والموافقة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($transfers->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open mb-3" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                    <div class="fw-bold text-dark">لا توجد طلبات تحويل مسجلة حالياً</div>
                                    <div class="small mt-1">عند بدء تحويل متربص من تفاصيل المؤسسة سيظهر طلبه هنا لتتبع مسار الموافقة.</div>
                                </td>
                            </tr>
                        @else
                            @foreach($transfers as $t)
                                @php
                                    $apprenant = $t->apprenant;
                                    $candidat = $apprenant ? $apprenant->candidat : null;
                                    $studentName = $candidat ? ($candidat->Nom . ' ' . $candidat->Prenom) : 'متربص غير معروف';
                                    
                                    // Status styling
                                    $statusBadge = '';
                                    if ($t->status === 'pending_sender_dfep') {
                                        $statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-3 py-2 fw-semibold"><i class="fa-solid fa-clock me-1"></i> في انتظار موافقة مديرية الإرسال</span>';
                                    } elseif ($t->status === 'pending_receiver') {
                                        $statusBadge = '<span class="badge bg-info-subtle text-info border border-info rounded-pill px-3 py-2 fw-semibold"><i class="fa-solid fa-building me-1"></i> في انتظار موافقة المؤسسة المستقبلة</span>';
                                    } elseif ($t->status === 'pending_receiver_dfep') {
                                        $statusBadge = '<span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3 py-2 fw-semibold"><i class="fa-solid fa-clock me-1"></i> في انتظار موافقة مديرية المستقبلة</span>';
                                    } elseif ($t->status === 'approved') {
                                        $statusBadge = '<span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2 fw-semibold"><i class="fa-solid fa-circle-check me-1"></i> مقبول ومحول</span>';
                                    } elseif ($t->status === 'rejected') {
                                        $statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 py-2 fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض</span>';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $studentName }}</div>
                                        <div class="small text-muted">رقم التمدرس: #{{ $t->apprenant_id }}</div>
                                    </td>
                                    <td>
                                        <div class="text-secondary small fw-bold">{{ $t->fromEtablissement ? $t->fromEtablissement->Nom : 'غير محدد' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-secondary small fw-bold">{{ $t->toEtablissement ? $t->toEtablissement->Nom : 'غير محدد' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-dark small fw-bold text-truncate" style="max-width: 200px;" title="{{ $t->toSection && $t->toSection->offre && $t->toSection->offre->specialite ? $t->toSection->offre->specialite->Nom : 'تخصص غير محدد' }}">
                                            {{ $t->toSection && $t->toSection->offre && $t->toSection->offre->specialite ? $t->toSection->offre->specialite->Nom : 'تخصص غير محدد' }}
                                        </div>
                                        <div class="text-primary small fw-semibold mt-1">
                                            <i class="fa-solid fa-users-line me-1"></i> {{ $t->toSection ? $t->toSection->Nom : 'قسم غير محدد' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted small">{{ $t->created_at ? $t->created_at->format('Y-m-d H:i') : '' }}</div>
                                    </td>
                                    <td>
                                        {!! $statusBadge !!}
                                        @if($t->status === 'rejected' && $t->rejection_comment)
                                            <div class="alert alert-danger border-0 py-1 px-2 rounded-3 mt-2 mb-0 small text-start">
                                                <strong>سبب الرفض:</strong> {{ $t->rejection_comment }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-end">
                                        @php
                                            $canApprove = false;
                                            $approveLabel = 'موافقة';
                                            
                                            if ($role === 'admin') {
                                                $canApprove = in_array($t->status, ['pending_sender_dfep', 'pending_receiver', 'pending_receiver_dfep']);
                                                if ($t->status === 'pending_receiver') {
                                                    $approveLabel = 'موافقة المؤسسة المستقبلة';
                                                } elseif ($t->status === 'pending_receiver_dfep') {
                                                    $approveLabel = 'الموافقة النهائية للمديرية';
                                                }
                                            } elseif ($role === 'dfep') {
                                                if ($t->status === 'pending_sender_dfep' && (int)$t->fromEtablissement->IDDFEP === (int)$dfepId) {
                                                    $canApprove = true;
                                                } elseif ($t->status === 'pending_receiver_dfep' && (int)$t->toEtablissement->IDDFEP === (int)$dfepId) {
                                                    $canApprove = true;
                                                    $approveLabel = 'الموافقة النهائية';
                                                }
                                            } elseif (($role === 'etablissement' || $role === 'directeur') && (int)$t->to_etab_id === (int)$etabId) {
                                                if ($t->status === 'pending_receiver') {
                                                    $canApprove = true;
                                                    $approveLabel = 'قبول وإرسال للمديرية';
                                                }
                                            }
                                        @endphp

                                        @if($canApprove)
                                            <div class="d-flex justify-content-end gap-2">
                                                <form action="{{ route('modules.reconduits.transfers-action') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $t->id }}">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                                                        <i class="fa-solid fa-circle-check me-1"></i> {{ $approveLabel }}
                                                    </button>
                                                </form>
                                                
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" onclick="openRejectionModal({{ $t->id }})">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i> رفض
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted small">لا توجد إجراءات متاحة</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($transfers->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true" style="font-family: 'Cairo', sans-serif;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="{{ route('modules.reconduits.transfers-action') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="rejectionRequestId">
                <input type="hidden" name="action" value="reject">
                
                <div class="modal-header bg-danger-subtle text-danger-emphasis border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="rejectionModalLabel">
                        <i class="fa-solid fa-circle-xmark me-2"></i> رفض طلب تحويل المتربص
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label for="rejectionComment" class="form-label fw-bold text-secondary">سبب الرفض (إلزامي)</label>
                        <textarea name="comment" id="rejectionComment" class="form-control rounded-3 border-light bg-light" rows="4" placeholder="يرجى كتابة سبب رفض الطلب بالتفصيل هنا..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-circle-xmark me-1"></i> تأكيد الرفض
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRejectionModal(requestId) {
    document.getElementById('rejectionRequestId').value = requestId;
    document.getElementById('rejectionComment').value = '';
    
    const myModal = new bootstrap.Modal(document.getElementById('rejectionModal'));
    myModal.show();
}
</script>
@endsection
