<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'فضاء المتربص') — منصة SGFEP</title>
    <meta name="description" content="الفضاء الشخصي للمتربص — نتائج، وثائق، استعمال الزمن">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --green-500: #10b981;
            --green-600: #059669;
            --green-700: #047857;
            --green-100: rgba(16,185,129,.1);
            --blue-500: #3b82f6;
            --amber-500: #f59e0b;
            --red-500: #ef4444;
            --bg: #f0fdf4;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: rgba(0,0,0,.07);
            --shadow: 0 4px 24px rgba(0,0,0,.06);
            --sidebar-w: 260px;
        }
        [data-theme="dark"] {
            --bg: #0a1628;
            --card: #111827;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --border: rgba(255,255,255,.06);
            --green-100: rgba(16,185,129,.08);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .appr-sidebar {
            position: fixed; top:0; right:0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--card);
            border-left: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 200;
            box-shadow: -4px 0 24px rgba(0,0,0,.05);
            overflow-y: auto;
        }
        .appr-logo {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex; flex-direction: column; align-items: center; gap:.5rem;
        }
        .appr-logo .badge-role {
            background: var(--green-100);
            color: var(--green-600);
            font-size: .65rem; font-weight: 700;
            padding: .25rem .75rem; border-radius: 20px;
            letter-spacing: .5px;
        }
        .appr-logo img { height: 50px; }
        .appr-logo .title { font-size: .85rem; font-weight: 700; color: var(--muted); }

        /* Student card in sidebar */
        .student-card-side {
            margin: 1rem;
            background: linear-gradient(135deg, var(--green-500), var(--green-700));
            border-radius: 16px;
            padding: 1.25rem;
            color: white;
            position: relative; overflow: hidden;
        }
        .student-card-side::before {
            content: '';
            position: absolute; top: -20px; left: -20px;
            width: 100px; height: 100px;
            background: rgba(255,255,255,.1);
            border-radius: 50%;
        }
        .student-card-side .s-name { font-size: .9rem; font-weight: 700; margin-bottom:.25rem; position:relative; }
        .student-card-side .s-nin  { font-size: .65rem; opacity: .8; font-family: monospace; position:relative; }
        .student-card-side .s-section { font-size: .7rem; opacity:.85; margin-top:.5rem; position:relative; line-height:1.4; }

        /* Nav menu */
        .appr-nav { padding: .75rem 1rem; flex: 1; }
        .appr-nav-label {
            font-size: .65rem; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 1px;
            padding: 1rem .5rem .3rem;
        }
        .appr-nav a {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem .9rem;
            border-radius: 12px;
            color: var(--muted);
            font-size: .85rem; font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            margin-bottom: .15rem;
        }
        .appr-nav a:hover, .appr-nav a.active {
            background: var(--green-100);
            color: var(--green-600);
        }
        .appr-nav a i { width: 18px; text-align: center; font-size: .9rem; }

        /* Logout */
        .appr-logout {
            padding: 1rem;
            border-top: 1px solid var(--border);
        }
        .appr-logout a {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem .9rem; border-radius: 12px;
            color: #ef4444; font-size: .85rem; font-weight: 600;
            text-decoration: none;
            transition: background .2s;
        }
        .appr-logout a:hover { background: rgba(239,68,68,.08); }

        /* ── Main Content ── */
        .appr-main {
            margin-right: var(--sidebar-w);
            min-height: 100vh;
            padding: 2rem;
        }

        /* Page header */
        .appr-page-header {
            margin-bottom: 2rem;
        }
        .appr-page-header h1 {
            font-size: 1.6rem; font-weight: 900;
            background: linear-gradient(135deg, var(--green-500), var(--green-700));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .appr-page-header p { color: var(--muted); font-size: .875rem; margin-top:.25rem; }

        /* Cards grid */
        .appr-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .appr-stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: transform .2s, box-shadow .2s;
        }
        .appr-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 32px rgba(0,0,0,.1); }
        .appr-stat-card .icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin-bottom: .9rem;
        }
        .appr-stat-card .stat-val { font-size: 1.6rem; font-weight: 900; }
        .appr-stat-card .stat-lbl { font-size: .75rem; color: var(--muted); font-weight: 600; }

        /* Section titles */
        .appr-section-title {
            font-size: 1.05rem; font-weight: 800;
            display: flex; align-items: center; gap: .6rem;
            margin-bottom: 1.25rem; padding-bottom: .75rem;
            border-bottom: 2px solid var(--green-100);
            color: var(--text);
        }
        .appr-section-title i { color: var(--green-500); }

        /* Table */
        .appr-table {
            width: 100%; border-collapse: collapse;
            font-size: .83rem;
        }
        .appr-table th {
            background: var(--green-100);
            color: var(--green-700);
            font-weight: 700; padding: .75rem 1rem;
            text-align: right; border: none;
        }
        .appr-table td {
            padding: .65rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .appr-table tr:hover td { background: rgba(16,185,129,.03); }
        .appr-table th:first-child { border-radius: 0 12px 12px 0; }
        .appr-table th:last-child  { border-radius: 12px 0 0 12px; }

        /* Badges */
        .badge-success { background:rgba(16,185,129,.12); color:#059669; padding:.2rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }
        .badge-danger  { background:rgba(239,68,68,.1); color:#dc2626; padding:.2rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }
        .badge-warn    { background:rgba(245,158,11,.1); color:#d97706; padding:.2rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700; }

        /* Card containers */
        .appr-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }

        /* Emploi du temps grid */
        .edt-grid {
            display: grid;
            grid-template-columns: auto repeat(6, 1fr);
            gap: 2px;
            font-size: .75rem;
        }
        .edt-cell {
            background: var(--card);
            border: 1px solid var(--border);
            padding: .5rem;
            min-height: 50px;
            border-radius: 8px;
        }
        .edt-header {
            background: var(--green-100);
            color: var(--green-700);
            font-weight: 700; text-align: center;
        }
        .edt-module {
            background: linear-gradient(135deg, var(--green-100), rgba(59,130,246,.08));
            color: var(--green-700);
            font-weight: 600;
            padding: .4rem;
            border-radius: 6px;
            font-size: .7rem;
            line-height: 1.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .appr-sidebar { display: none; }
            .appr-main { margin-right: 0; padding: 1rem; }
        }

        /* Diploma banner */
        .diploma-banner {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 16px; padding: 1.25rem 1.75rem;
            display: flex; align-items: center; gap: 1.25rem;
            margin-bottom: 1.5rem; color: #fff;
        }
        .diploma-banner i { font-size: 2.5rem; opacity: .9; }
        .diploma-banner h3 { font-size: 1rem; font-weight: 800; margin-bottom: .2rem; }
        .diploma-banner p  { font-size: .8rem; opacity: .9; }

        /* Print btn */
        .btn-appr {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .6rem 1.25rem; border-radius: 12px;
            font-weight: 700; font-size: .8rem; cursor: pointer;
            border: none; text-decoration: none; transition: all .2s;
        }
        .btn-appr-primary { background: var(--green-500); color: white; }
        .btn-appr-primary:hover { background: var(--green-600); transform: translateY(-1px); }
        .btn-appr-outline { background: transparent; color: var(--green-600); border: 2px solid var(--green-500); }
        .btn-appr-outline:hover { background: var(--green-100); }
    </style>
    @yield('styles')
</head>
<body>

{{-- ── Sidebar ── --}}
<aside class="appr-sidebar">
    <div class="appr-logo">
        <img src="{{ asset('assets/images/logo.png') }}" alt="SGFEP" onerror="this.style.display='none'">
        <div class="title">وزارة التكوين المهني</div>
        <span class="badge-role">فضاء المتربص</span>
    </div>

    {{-- بطاقة المتربص المصغّرة --}}
    @if(isset($apprenant))
    <div class="student-card-side">
        <div class="s-name">{{ $apprenant->Nom ?? '' }} {{ $apprenant->Prenom ?? '' }}</div>
        <div class="s-nin">NIN: {{ $apprenant->Nin ?? '—' }}</div>
        <div class="s-section">{{ \Illuminate\Support\Str::limit($apprenant->section_nom ?? '—', 45) }}</div>
    </div>
    @endif

    <nav class="appr-nav">
        <div class="appr-nav-label">القائمة الرئيسية</div>
        <a href="{{ route('apprenant.dashboard') }}"
           class="{{ request()->routeIs('apprenant.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i> الصفحة الرئيسية
        </a>
        <a href="{{ route('apprenant.carte') }}"
           class="{{ request()->routeIs('apprenant.carte') ? 'active' : '' }}">
            <i class="fa-solid fa-id-card"></i> بطاقة المتكون
        </a>

        <div class="appr-nav-label">نتائجي ودراستي</div>
        <a href="{{ route('apprenant.dashboard') }}#notes"
           class="{{ request()->is('*#notes') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-line"></i> نتائجي
        </a>
        <a href="{{ route('apprenant.dashboard') }}#emploi">
            <i class="fa-solid fa-calendar-week"></i> استعمال الزمن
        </a>
        <a href="{{ route('apprenant.dashboard') }}#teachers">
            <i class="fa-solid fa-chalkboard-user"></i> الأساتذة
        </a>

        <div class="appr-nav-label">وثائقي</div>
        <a href="{{ route('apprenant.dashboard') }}#documents">
            <i class="fa-solid fa-folder-open"></i> وثائقي
        </a>

        @if(isset($estDiplome) && $estDiplome)
        <div class="appr-nav-label">شهادتي</div>
        <a href="{{ route('apprenant.dashboard') }}#diplome">
            <i class="fa-solid fa-graduation-cap" style="color:#f59e0b;"></i> الشهادة
        </a>
        @endif
    </nav>

    <div class="appr-logout">
        <a href="/logout">
            <i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج
        </a>
    </div>
</aside>

{{-- ── Main ── --}}
<main class="appr-main">
    @yield('content')
</main>

<script>
// Dark mode toggle
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', isDark ? 'light' : 'dark');
    localStorage.setItem('appr_theme', isDark ? 'light' : 'dark');
}
document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('appr_theme');
    if (saved) document.documentElement.setAttribute('data-theme', saved);
});
</script>
@yield('scripts')
</body>
</html>
