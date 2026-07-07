@php
    $limit = (int)($config['limit'] ?? 5);
    $themeColor = $config['theme_color'] ?? 'danger';

    // Lazy load the recent absences
    try {
        $absences = DB::select("
            SELECT ab.Date as date_absence, ab.heure, c.Nom as nom, c.Prenom as prenom, sec.Nom as section_nom
            FROM apprenant_absence ab
            JOIN apprenant_section_semstre ass ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
            JOIN apprenant ap ON ass.IDapprenant = ap.IDapprenant
            JOIN candidat c ON ap.IDCandidat = c.IDCandidat
            LEFT JOIN section sec ON ap.IDSection = sec.IDSection
            ORDER BY ab.Date DESC, ab.heure DESC
            LIMIT ?
        ", [$limit]);
    } catch (\Exception $e) {
        $absences = [];
        $error = $e->getMessage();
    }
@endphp

<div class="absences-widget-content">
    @if (!empty($error))
        <div class="alert alert-danger-glow p-2 rounded small">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> خطأ في تحميل الغيابات: {{ $error }}
        </div>
    @elseif (count($absences) > 0)
        <div class="table-responsive">
            <table class="table table-hover border-0 align-middle text-right mb-0" style="font-family:'Cairo'; font-size:0.8rem;">
                <thead>
                    <tr class="text-secondary small" style="border-bottom: 1px solid var(--border);">
                        <th class="border-0 px-2 py-1.5">المتربص</th>
                        <th class="border-0 px-2 py-1.5">الفوج / الفئة</th>
                        <th class="border-0 px-2 py-1.5">التاريخ والوقت</th>
                        <th class="border-0 px-2 py-1.5 text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($absences as $abs)
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.02);">
                            <td class="px-2 py-2 fw-bold text-dark">
                                <i class="fa-solid fa-user-graduate text-secondary me-1.5" style="font-size:0.75rem;"></i>
                                {{ $abs->nom }} {{ $abs->prenom }}
                            </td>
                            <td class="px-2 py-2 text-secondary">
                                {{ $abs->section_nom ?: 'غير محدد' }}
                            </td>
                            <td class="px-2 py-2 text-secondary" style="font-family: var(--font-mono); font-size:0.75rem;">
                                {{ date('d/m/Y', strtotime($abs->date_absence)) }} • {{ $abs->heure }}
                            </td>
                            <td class="px-2 py-2 text-center">
                                <span class="badge bg-{{ $themeColor }}-glow text-{{ $themeColor }} rounded-pill px-2.5 py-1" style="font-size:0.7rem; font-weight:700;">
                                    غائب
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-4 text-muted small">
            <i class="fa-solid fa-calendar-check fa-2x mb-2 text-light-glow"></i>
            <div>لا توجد غيابات مسجلة حديثاً في النظام.</div>
        </div>
    @endif
</div>
