@extends('layouts.main')

@section('title', 'لوحة التحكم المخصصة — منصة تسيير')

@section('styles')
<style>
    .portal-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 10px;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
    }
    .portal-tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        color: var(--tx-2);
        font-family: 'Cairo', sans-serif;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        transition: all var(--d-fast) var(--ease);
        white-space: nowrap;
    }
    .portal-tab:hover {
        background: rgba(26, 107, 204, 0.05);
        color: var(--electric);
        transform: translateY(-2px);
    }
    .portal-tab.active {
        background: linear-gradient(135deg, var(--electric) 0%, var(--electric-dark) 100%);
        color: #fff;
        border-color: var(--electric-border);
        box-shadow: 0 4px 12px var(--electric-glow);
    }
    
    .widget-wrapper {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--r-xl);
        box-shadow: var(--shadow-sm);
        transition: all var(--d-normal) var(--ease);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .widget-wrapper:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    .widget-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0, 0, 0, 0.01);
    }
    .widget-title {
        font-family: 'Cairo', sans-serif;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--tx-1);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .widget-body {
        padding: 1.25rem;
        flex-grow: 1;
        overflow-y: auto;
    }
</style>
@endsection

@section('content')
<!-- Portal Header -->
<div class="profile-banner mb-4" style="background: linear-gradient(145deg, var(--navy-900) 0%, var(--navy-700) 50%, var(--primary-500) 100%); border-radius: var(--r-2xl); padding: var(--sp-6) var(--sp-8); color: #fff; position: relative; overflow: hidden; box-shadow: var(--shadow-xl);">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative" style="z-index: 2;">
        <div>
            <h1 class="profile-banner-title" style="font-size: var(--text-xl); font-weight: 900; margin-bottom: 4px;">
                <i class="fa-solid fa-compass-drafting me-2"></i>لوحة التحكم الرقمية المخصصة
            </h1>
            <p class="profile-banner-sub mb-0" style="font-size: var(--text-sm); opacity: .7; font-weight: 600;">تخطيط بوابات مخصص ومعين من قبل الإدارة المركزية</p>
        </div>
        @if (strtolower(session('user')['role_code'] ?? '') === 'admin')
            <a href="{{ url('dashboard/builder') }}" class="btn btn-light px-4 fw-bold shadow-sm" style="border-radius: var(--r-md); color: var(--electric); font-family: 'Cairo';">
                <i class="fa-solid fa-gears me-1"></i> صانع لوحة التحكم
            </a>
        @endif
    </div>
</div>

<!-- Portal Switcher Tabs (1 to 11) -->
<div class="portal-tabs">
    @for ($i = 1; $i <= 11; $i++)
        <a href="?portal={{ $i }}" class="portal-tab {{ $current_portal === $i ? 'active' : '' }}">
            <i class="fa-solid fa-window-restore"></i>
            <span>البوابة {{ $i }}</span>
        </a>
    @endfor
</div>

@if (count($portalWidgets) > 0)
    <!-- Dynamic CSS Grid Container -->
    <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 1.5rem;">
        @foreach($portalWidgets as $widget)
            @php
                $widgetConfig = json_decode($widget->config, true) ?? [];
                $themeColor = $widgetConfig['theme_color'] ?? 'primary';
            @endphp
            <div class="widget-wrapper" style="grid-column: span {{ $widget->grid_w }}; grid-row: span {{ $widget->grid_h }};">
                <div class="widget-header">
                    <h3 class="widget-title">
                        <span class="d-inline-block rounded-circle bg-{{ $themeColor }}-glow p-1.5" style="width: 8px; height: 8px;"></span>
                        {{ $widget->title ?: 'مكون لوحة تحكم' }}
                    </h3>
                    <div class="widget-actions">
                        <button class="btn btn-sm btn-link text-muted p-0" title="تحديث البيانات" onclick="location.reload();">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>
                <div class="widget-body">
                    @include('widgets.' . $widget->type, ['config' => $widgetConfig, 'widget' => $widget])
                </div>
            </div>
        @endforeach
    </div>
@else
    <!-- Empty State -->
    <div class="card p-5 text-center border-0 shadow-sm" style="border-radius: var(--r-xl); background: var(--bg-card);">
        <div class="text-muted mb-3">
            <i class="fa-solid fa-cubes-stacked fa-4x text-light-glow"></i>
        </div>
        <h3 class="fw-bold" style="font-family: 'Cairo'; font-size: 1.2rem; color: var(--tx-1);">البوابة {{ $current_portal }} فارغة</h3>
        <p class="text-secondary small max-w-md mx-auto" style="font-family: 'Cairo'; max-width: 420px;">
            لم يتم إعداد أو تخصيص أي مكونات (Widgets) لهذه البوابة من قبل مدير النظام. يرجى مراجعة إدارة المنصة لتعيين تخطيط مخصص لك.
        </p>
        <div class="mt-4">
            <a href="?portal=1" class="btn btn-secondary px-4 py-2" style="font-family: 'Cairo'; font-size: 0.85rem; border-radius: var(--r-md);">
                <i class="fa-solid fa-house me-1"></i> العودة للبوابة الرئيسية
            </a>
        </div>
    </div>
@endif
@endsection
