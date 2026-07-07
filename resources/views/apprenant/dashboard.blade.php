@extends('layouts.main')
@section('title', 'لوحة تحكم المتربص')

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- ── الترحيب ومعلومات المعهد ── --}}
    <div class="bento-grid mb-4">
        <div class="bento-12 glass-panel p-4" style="border-right: 6px solid var(--electric) !important; position: relative;">
            <div style="position: absolute; width: 300px; height: 150px; background: radial-gradient(circle, rgba(26,107,204,0.06) 0%, transparent 70%); top: 0; left: 0; pointer-events: none;"></div>
            <div class="row align-items-center" style="position: relative; z-index: 2;">
                <div class="col-md-9">
                    <h3 class="fw-bold text-dark mb-1" style="font-size: 1.6rem; font-family: 'Cairo';">
                        <i class="fa-solid fa-graduation-cap text-success me-2"></i>
                        مرحباً بك، {{ $apprenant->Prenom ?? '' }} {{ $apprenant->Nom ?? '' }}
                    </h3>
                    <p class="text-muted small mb-0 fw-bold" style="font-size: 0.88rem;">
                        {{ $apprenant->etab_nom ?? 'المعهد' }} &nbsp;—&nbsp; التخصص: <span class="text-primary">{{ $apprenant->specialite_nom ?? '' }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── بنر الشهادة (إن كان متخرجاً) ── --}}
    @if($estDiplome)
    <div class="alert alert-success border-0 shadow-sm p-4 mb-4 d-flex align-items-center gap-4 rounded-4" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: white;">
        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; flex-shrink:0;">
            <i class="fa-solid fa-award fs-3 text-success"></i>
        </div>
        <div style="flex:1;">
            <h4 class="fw-bold mb-1" style="font-size: 1.15rem; font-family: 'Cairo';">تهانينا! أنت حاصل على شهادة التكوين المهني</h4>
            <p class="mb-0 small opacity-90">يمكنك طلب شهادتك من مؤسستك التكوينية أو طباعة وثيقة الإتمام الرسمية من قائمة الوثائق أدناه.</p>
        </div>
    </div>
    @endif

    {{-- ── Stats Cards Grid ── --}}
    <div class="bento-grid mb-4">
        <div class="bento-3 glass-panel p-4 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-success-subtle text-success rounded-4" style="width:48px; height:48px; flex-shrink:0;">
                <i class="fa-solid fa-book-open fs-5"></i>
            </div>
            <div>
                <div class="fs-4 fw-black text-dark" style="line-height:1.2;">{{ count($notes) }}</div>
                <div class="text-muted small" style="font-size:0.75rem; font-weight:600;">مادة دراسية</div>
            </div>
        </div>

        <div class="bento-3 glass-panel p-4 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-4" style="width:48px; height:48px; flex-shrink:0;">
                <i class="fa-solid fa-layer-group fs-5"></i>
            </div>
            <div>
                <div class="fs-4 fw-black text-dark" style="line-height:1.2;">{{ count($semestres) }}</div>
                <div class="text-muted small" style="font-size:0.75rem; font-weight:600;">فصل دراسي</div>
            </div>
        </div>

        @php
            $moyGlobal = collect($semestres)->whereNotNull('moyenne_generale')->avg('moyenne_generale');
        @endphp

        <div class="bento-3 glass-panel p-4 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-4" style="width:48px; height:48px; flex-shrink:0;">
                <i class="fa-solid fa-star fs-5"></i>
            </div>
            <div>
                <div class="fs-4 fw-black" style="line-height:1.2; color:{{ $moyGlobal >= 10 ? '#10b981' : '#ef4444' }};">
                    {{ $moyGlobal ? number_format($moyGlobal, 2) : '—' }}
                </div>
                <div class="text-muted small" style="font-size:0.75rem; font-weight:600;">المعدل العام</div>
            </div>
        </div>

        <div class="bento-3 glass-panel p-4 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-info-subtle text-info rounded-4" style="width:48px; height:48px; flex-shrink:0;">
                <i class="fa-solid fa-calendar-week fs-5"></i>
            </div>
            <div>
                <div class="fs-4 fw-black text-dark" style="line-height:1.2;">{{ count($emploiTemps) }}</div>
                <div class="text-muted small" style="font-size:0.75rem; font-weight:600;">حصة أسبوعية</div>
            </div>
        </div>
    </div>

    {{-- ── البيانات الشخصية ── --}}
    <div class="glass-panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 2px solid var(--border);">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user-circle text-success me-2"></i> البيانات الشخصية</h5>
            <a href="{{ route('apprenant.carte') }}" class="btn btn-sm btn-outline-success rounded-3">
                <i class="fa-solid fa-id-card me-1.5"></i> عرض بطاقتي الرقمية
            </a>
        </div>
        <div class="row g-3">
            @php
            $infos = [
                ['label' => 'الاسم الكامل',     'val' => ($apprenant->Nom ?? '') . ' ' . ($apprenant->Prenom ?? ''), 'icon' => 'fa-user'],
                ['label' => 'الاسم (فرنسي)',    'val' => ($apprenant->NomFr ?? '') . ' ' . ($apprenant->PrenomFr ?? ''), 'icon' => 'fa-font'],
                ['label' => 'رقم التعريف NIN',  'val' => $apprenant->Nin ?? '—', 'icon' => 'fa-id-card'],
                ['label' => 'رقم NCCP',         'val' => $apprenant->Nccp ?? 'غير محدد', 'icon' => 'fa-hashtag'],
                ['label' => 'تاريخ الميلاد',    'val' => $apprenant->DateNais ?? '—', 'icon' => 'fa-cake-candles'],
                ['label' => 'مكان الميلاد',     'val' => $apprenant->LieuNais ?? '—', 'icon' => 'fa-location-dot'],
                ['label' => 'تخصص التكوين',     'val' => $apprenant->specialite_nom ?? '—', 'icon' => 'fa-wrench'],
                ['label' => 'المستوى الدراسي',  'val' => $apprenant->niveau_nom ?? '—', 'icon' => 'fa-layer-group'],
                ['label' => 'نمط التكوين',      'val' => $apprenant->mode_nom ?? '—', 'icon' => 'fa-graduation-cap'],
                ['label' => 'المؤسسة',          'val' => $apprenant->etab_nom ?? '—', 'icon' => 'fa-school'],
            ];
            @endphp
            @foreach($infos as $info)
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-4 border" style="border-color: var(--border) !important;">
                    <div class="text-muted small fw-bold mb-1" style="font-size: 0.72rem;">
                        <i class="fa-solid {{ $info['icon'] }} text-success me-1.5"></i>
                        {{ $info['label'] }}
                    </div>
                    <div class="text-dark fw-bold" style="font-size:0.9rem;">{{ $info['val'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── كشف النقاط الرسمي والنتائج ── --}}
    <div class="glass-panel p-4 mb-4" id="notes">
        <div class="mb-4 pb-2" style="border-bottom: 2px solid var(--border);">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-bar text-success me-2"></i> النتائج وكشوف النقاط</h5>
        </div>

        @if(count($semestres) > 0)
        <div class="d-flex gap-3 flex-wrap mb-4">
            @foreach($semestres as $sem)
            <div class="p-3 rounded-4 border text-center bg-light" style="min-width: 150px; border-color: var(--border) !important; flex: 1;">
                <div class="text-muted small fw-bold mb-2">{{ $sem->sem_nom ?? 'الفصل '.$loop->iteration }}</div>
                <div class="fs-3 fw-black" style="color: {{ ($sem->moyenne_generale ?? 0) >= 10 ? '#10b981' : '#ef4444' }};">
                    {{ $sem->moyenne_generale ? number_format($sem->moyenne_generale, 2) : '—' }}
                </div>
                <div class="mt-2">
                    @if($sem->is_admis_general)
                        <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.7rem;">ناجح</span>
                    @elseif($sem->moyenne_generale)
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.7rem;">راسب</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.7rem;">قيد الانتظار</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if(count($notes) > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle premium-table">
                <thead>
                    <tr class="table-light">
                        <th>المادة</th>
                        <th>المعامل</th>
                        <th>C1</th>
                        <th>C2</th>
                        <th>Cs</th>
                        <th>R</th>
                        <th>المعدل</th>
                        <th>القرار</th>
                        <th>الغياب</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notes as $note)
                    <tr>
                        <td class="fw-bold text-dark">{{ $note->module_nom }}</td>
                        <td>{{ $note->Coef ?? '—' }}</td>
                        <td>{{ $note->NoteC1 ?? '—' }}</td>
                        <td>{{ $note->NoteC2 ?? '—' }}</td>
                        <td>{{ $note->NoteCs ?? '—' }}</td>
                        <td>{{ $note->NoteR ?? '—' }}</td>
                        <td>
                            @php $moy = $note->MoyApr ?? $note->MoyAvr; @endphp
                            <span class="fw-black fs-6" style="color:{{ ($moy ?? 0) >= 10 ? '#10b981' : '#ef4444' }};">
                                {{ $moy ? number_format($moy, 2) : '—' }}
                            </span>
                        </td>
                        <td>
                            @if($note->decision)
                                <span class="badge {{ str_contains($note->decision,'ناجح') || str_contains($note->decision,'Admis') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill px-2.5 py-1 fw-bold" style="font-size:0.7rem;">
                                    {{ $note->decision }}
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1 fw-bold" style="font-size:0.7rem;">—</span>
                            @endif
                        </td>
                        <td>{{ ($note->absc1 ?? 0) + ($note->absc2 ?? 0) }} حصة</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center p-5 text-muted">
            <i class="fa-solid fa-clock-rotate-left fs-2 mb-3 opacity-50"></i>
            <p class="fw-bold mb-0">لا توجد نتائج بعد — سيتم تحديثها بعد إجراء التقييمات</p>
        </div>
        @endif
    </div>

    {{-- ── استعمال الزمن الأسبوعي ── --}}
    <div class="glass-panel p-4 mb-4" id="emploi">
        <div class="mb-4 pb-2" style="border-bottom: 2px solid var(--border);">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-calendar-week text-success me-2"></i> استعمال الزمن الأسبوعي</h5>
        </div>

        @if(count($emploiTemps) > 0)
        @php
            $jours = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
            $byDay = collect($emploiTemps)->groupBy('Jour');
        @endphp
        <div class="table-responsive">
            <table class="table table-bordered align-middle premium-table">
                <thead>
                    <tr class="table-light">
                        <th>اليوم</th>
                        <th>التوقيت</th>
                        <th>المادة</th>
                        <th>القاعة</th>
                        <th>المدة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byDay as $jourNum => $seances)
                    @foreach($seances as $i => $s)
                    <tr>
                        @if($i === 0)
                        <td rowspan="{{ $seances->count() }}" class="fw-bold text-center bg-success-subtle text-success" style="vertical-align: middle; width: 120px;">
                            {{ $jours[$jourNum] ?? 'اليوم '.$jourNum }}
                        </td>
                        @endif
                        <td style="font-family:monospace;">
                            {{ \Carbon\Carbon::parse($s->Heured)->format('H:i') }}
                            — {{ \Carbon\Carbon::parse($s->Heuref)->format('H:i') }}
                        </td>
                        <td class="fw-bold text-dark">{{ $s->module_nom }}</td>
                        <td>{{ $s->salle ?? '—' }}</td>
                        <td>{{ $s->Duree ?? '—' }} ساعة</td>
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center p-5 text-muted">
            <i class="fa-solid fa-calendar-xmark fs-2 mb-3 opacity-50"></i>
            <p class="fw-bold mb-0">لم يُوضع استعمال الزمن بعد</p>
        </div>
        @endif
    </div>

    {{-- ── الوثائق الرسمية ── --}}
    <div class="glass-panel p-4 mb-4" id="documents">
        <div class="mb-4 pb-2" style="border-bottom: 2px solid var(--border);">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-folder-open text-success me-2"></i> وثائقي الرسمية القابلة للاستخراج</h5>
        </div>
        <div class="row g-3">
            @php
            $isSig = request()->is('sig/*') || request()->is('sig');
            $printPrefix = $isSig ? 'sig/dashboard/documents/print/' : 'dashboard/documents/print/';
            $printDiplomaUrl = $isSig ? 'sig/dashboard/diplomes/print/' : 'dashboard/diplomes/print/';
            $docs = [
                ['nom' => 'بطاقة المتكون (الرقمية)', 'icon' => 'fa-id-card',         'color' => '#10b981', 'route' => route('apprenant.carte'),      'has' => true],
                ['nom' => 'وثيقة التسجيل الرسمية',  'icon' => 'fa-file-contract',   'color' => '#3b82f6', 'route' => url($printPrefix . \App\Helpers\SecureIdHelper::encrypt($apprenant->IDapprenant) . '?direct=1&type=stagiaire&doc=attestation_inscription'),   'has' => !is_null($apprenant->Nccp ?? null)],
                ['nom' => 'كشف النقاط الرسمي',      'icon' => 'fa-file-lines',      'color' => '#f59e0b', 'route' => url($printPrefix . \App\Helpers\SecureIdHelper::encrypt($apprenant->IDapprenant) . '?direct=1&type=stagiaire&doc=bulletin_notes'),   'has' => count($notes) > 0],
                ['nom' => 'شهادة التخرج للمتخرجين', 'icon' => 'fa-graduation-cap',  'color' => '#8b5cf6', 'route' => $estDiplome ? url($printDiplomaUrl . $apprenant->id_fin) : '#',   'has' => $estDiplome],
                ['nom' => 'شهادة متابعة التكوين',   'icon' => 'fa-certificate',     'color' => '#06b6d4', 'route' => url($printPrefix . \App\Helpers\SecureIdHelper::encrypt($apprenant->IDapprenant) . '?direct=1&type=stagiaire&doc=certificat_scolaire'),   'has' => true],
            ];
            @endphp
            @foreach($docs as $doc)
            <div class="col-lg col-md-4 col-sm-6">
                <div class="p-4 border rounded-4 text-center d-flex flex-column align-items-center gap-3 bg-light h-100" style="border-color: var(--border) !important; opacity:{{ $doc['has'] ? '1' : '0.45' }};">
                    <div class="d-flex align-items-center justify-content-center bg-white rounded-4 shadow-sm" style="width:52px; height:52px;">
                        <i class="fa-solid {{ $doc['icon'] }} fs-4" style="color: {{ $doc['has'] ? $doc['color'] : 'var(--muted)' }};"></i>
                    </div>
                    <div class="fw-bold text-dark small" style="font-size:0.83rem; min-height: 38px;">{{ $doc['nom'] }}</div>
                    @if($doc['has'])
                    <a href="{{ $doc['route'] }}" class="btn btn-sm btn-success w-100 rounded-3 mt-auto" target="_blank">
                        <i class="fa-solid fa-print me-1"></i> عرض / طباعة
                    </a>
                    @else
                    <span class="text-muted small mt-auto">غير متاح</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── الأساتذة المؤطرون ── --}}
    <div class="glass-panel p-4 mb-4" id="teachers">
        <div class="mb-4 pb-2" style="border-bottom: 2px solid var(--border);">
            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chalkboard-user text-success me-2"></i> الأساتذة المؤطرون</h5>
        </div>
        @if(count($teachers) > 0)
        <div class="row g-3">
            @foreach($teachers as $t)
            <div class="col-md-4 col-sm-6">
                <div class="p-3 border rounded-4 d-flex align-items-center gap-3 bg-light" style="border-color: var(--border) !important;">
                    <div class="d-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle fw-bold fs-5" style="width:48px; height:48px; flex-shrink:0;">
                        {{ mb_substr($t->Nom ?? 'أ', 0, 1) }}
                    </div>
                    <div style="flex:1;">
                        <div class="text-dark fw-bold" style="font-size:0.88rem;">الأستاذ: {{ $t->Nom }} {{ $t->Prenom }}</div>
                        <div class="text-muted small mb-2">مقياس: <strong>{{ $t->module_nom }}</strong></div>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($t->Tel)
                            <span class="badge bg-white border text-dark font-monospace" style="font-size:0.65rem;"><i class="fa-solid fa-phone text-success me-1"></i> {{ $t->Tel }}</span>
                            @endif
                            @if($t->Email)
                            <span class="badge bg-white border text-dark font-monospace" style="font-size:0.65rem;"><i class="fa-solid fa-envelope text-success me-1"></i> {{ $t->Email }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center p-5 text-muted">
            <i class="fa-solid fa-user-slash fs-2 mb-3 opacity-50"></i>
            <p class="fw-bold mb-0">لا يوجد أساتذة مسجلين لهذا القسم حالياً</p>
        </div>
        @endif
    </div>

</div>
@endsection
