@php
    $themeColor = $config['theme_color'] ?? 'success';

    // Lazy load grading stats
    try {
        $stats = DB::selectOne("
            SELECT 
                COUNT(*) as total,
                AVG(CASE WHEN NoteCs IS NOT NULL THEN NoteCs ELSE NULL END) as avg_cs,
                SUM(CASE WHEN NoteCs >= 10.0 THEN 1 ELSE 0 END) as pass_count,
                SUM(CASE WHEN NoteCs < 10.0 THEN 1 ELSE 0 END) as fail_count
            FROM apprenant_section_semstre_module
        ");
        
        $total = (int)($stats->total ?? 0);
        $avgScore = $stats->avg_cs !== null ? round((float)$stats->avg_cs, 2) : null;
        $passCount = (int)($stats->pass_count ?? 0);
        $failCount = (int)($stats->fail_count ?? 0);
        
        $passPercent = $total > 0 ? round(($passCount / $total) * 100, 1) : 0;
    } catch (\Exception $e) {
        $total = 0;
        $avgScore = null;
        $passCount = 0;
        $failCount = 0;
        $passPercent = 0;
        $error = $e->getMessage();
    }
@endphp

<div class="grades-overview-widget-content">
    @if (!empty($error))
        <div class="alert alert-danger-glow p-2 rounded small">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> خطأ في تحميل العلامات: {{ $error }}
        </div>
    @else
        <!-- Average Score Circle -->
        <div class="row align-items-center g-3">
            <div class="col-6 text-center border-start">
                <span class="text-secondary small d-block mb-1" style="font-family:'Cairo'; font-weight:700;">متوسط النقاط العام</span>
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $themeColor }}-glow border border-{{ $themeColor }}" style="width: 80px; height: 80px; border-width: 4px;">
                    <span class="h4 fw-extrabold text-dark mb-0" style="font-family:'Outfit'; font-weight:900;">
                        {{ $avgScore !== null ? $avgScore : '—' }}
                    </span>
                </div>
            </div>
            
            <div class="col-6">
                <div class="mb-2">
                    <span class="text-secondary small d-block" style="font-family:'Cairo';">إجمالي المقاييس المرصودة</span>
                    <strong class="h5 text-dark" style="font-family:'Outfit'; font-weight:800;">{{ number_format($total) }}</strong>
                </div>
                <div>
                    <span class="text-secondary small d-block" style="font-family:'Cairo';">نسبة النجاح العامة</span>
                    <strong class="h5 text-success" style="font-family:'Outfit'; font-weight:800;">{{ $passPercent }}%</strong>
                </div>
            </div>
        </div>

        <hr class="my-3 opacity-25">

        <!-- Grid breakdown -->
        <div class="row text-center g-2" style="font-family:'Cairo'; font-size:0.75rem;">
            <div class="col-6">
                <div class="p-2 rounded bg-success-glow">
                    <span class="text-success fw-bold d-block"><i class="fa-solid fa-circle-check me-1"></i> علامات مقبولة</span>
                    <strong class="text-dark" style="font-size:0.9rem;">{{ $passCount }}</strong>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 rounded bg-danger-glow">
                    <span class="text-danger fw-bold d-block"><i class="fa-solid fa-circle-xmark me-1"></i> دون المعدل</span>
                    <strong class="text-dark" style="font-size:0.9rem;">{{ $failCount }}</strong>
                </div>
            </div>
        </div>
    @endif
</div>
