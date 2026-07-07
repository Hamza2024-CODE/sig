@php
    $themeColor = $config['theme_color'] ?? 'primary';

    // Lazy load the trainee statistics
    try {
        $stats = DB::selectOne("
            SELECT 
                COUNT(a.IDapprenant) as total,
                SUM(CASE WHEN c.sexe = 'F' THEN 1 ELSE 0 END) as female_count,
                SUM(CASE WHEN c.sexe = 'M' THEN 1 ELSE 0 END) as male_count
            FROM apprenant a
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
        ");
        
        $total = (int)($stats->total ?? 0);
        $female = (int)($stats->female_count ?? 0);
        $male = (int)($stats->male_count ?? 0);
        
        $femalePercent = $total > 0 ? round(($female / $total) * 100, 1) : 0;
        $malePercent = $total > 0 ? round(($male / $total) * 100, 1) : 0;
    } catch (\Exception $e) {
        $total = 0;
        $female = 0;
        $male = 0;
        $femalePercent = 0;
        $malePercent = 0;
        $error = $e->getMessage();
    }
@endphp

<div class="trainee-stats-widget-content">
    @if (!empty($error))
        <div class="alert alert-danger-glow p-2 rounded small">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> خطأ في تحميل الإحصائيات: {{ $error }}
        </div>
    @else
        <!-- Big Number -->
        <div class="text-center mb-4">
            <span class="text-secondary small d-block mb-1" style="font-family:'Cairo'; font-weight:700;">إجمالي تعداد المتربصين</span>
            <h2 class="display-6 fw-extrabold text-dark mb-0" style="font-family:'Outfit'; font-weight:900;">
                {{ number_format($total) }}
            </h2>
            <span class="badge bg-{{ $themeColor }}-glow text-{{ $themeColor }} rounded-pill px-3 py-1 mt-2" style="font-family:'Cairo'; font-size:0.75rem; font-weight:700;">
                <i class="fa-solid fa-chart-line me-1"></i> حالة التعداد: نشط مستمر
            </span>
        </div>

        <!-- Progress Bars -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-family:'Cairo'; font-size:0.8rem;">
                <span class="fw-bold text-primary"><i class="fa-solid fa-mars me-1"></i> ذكور (Masculin)</span>
                <span class="fw-bold text-dark">{{ $male }} ({{ $malePercent }}%)</span>
            </div>
            <div class="progress" style="height: 10px; border-radius: var(--r-full); background: rgba(0,0,0,0.04);">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $malePercent; ?>%; border-radius: var(--r-full);" aria-valuenow="{{ $malePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <div>
            <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-family:'Cairo'; font-size:0.8rem;">
                <span class="fw-bold text-danger"><i class="fa-solid fa-venus me-1"></i> إناث (Féminin)</span>
                <span class="fw-bold text-dark">{{ $female }} ({{ $femalePercent }}%)</span>
            </div>
            <div class="progress" style="height: 10px; border-radius: var(--r-full); background: rgba(0,0,0,0.04);">
                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $femalePercent; ?>%; border-radius: var(--r-full);" aria-valuenow="{{ $femalePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    @endif
</div>
