<?php $__env->startSection('title', $title); ?>

<?php $__env->startSection('styles'); ?>
<style>
.portal-container {
    max-width: 1300px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
    position: relative;
}

/* Ambient glow blobs in portal background */
.portal-bg-glow-1 {
    position: absolute;
    top: 10%;
    right: 5%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.05) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
    z-index: 1;
    filter: blur(40px);
}
.portal-bg-glow-2 {
    position: absolute;
    bottom: 10%;
    left: 5%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.04) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
    z-index: 1;
    filter: blur(50px);
}

/* Mobile Pill Navigation */
.portal-mobile-nav {
    display: none;
    overflow-x: auto;
    white-space: nowrap;
    padding: 0.5rem 0 1rem 0;
    margin-bottom: 1.5rem;
    -webkit-overflow-scrolling: touch;
}
@supports (scrollbar-width: none) {
    .portal-mobile-nav {
        scrollbar-width: none; /* Firefox */
    }
}
.portal-mobile-nav::-webkit-scrollbar {
    display: none; /* Safari/Chrome */
}

.portal-mobile-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: var(--bg-glass-card);
    border: 1px solid var(--color-border);
    border-radius: 30px;
    color: var(--color-text-main);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 700;
    margin-left: 0.5rem;
    transition: var(--transition-smooth);
}
.portal-mobile-link i {
    color: var(--color-text-muted);
}
.portal-mobile-link.active {
    background: linear-gradient(135deg, var(--primary-400) 0%, var(--primary-500) 100%);
    color: #fff !important;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
}
.portal-mobile-link.active i {
    color: #fff !important;
}

/* Sidebar Nav (Desktop) */
.portal-sidebar-card {
    background: var(--bg-glass-card);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--border-glass);
    border-radius: 24px;
    box-shadow: var(--shadow-premium);
    padding: 1.5rem;
    position: sticky;
    top: 110px;
    z-index: 10;
}
.portal-sidebar-title {
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 1rem;
    display: block;
}
.portal-sidebar-sep {
    height: 1px;
    background: var(--color-border);
    margin: 0.75rem 0;
}
.portal-sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 1rem;
    border-radius: 14px;
    color: var(--color-text-main);
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    transition: var(--transition-smooth);
    border: 1px solid transparent;
}
.portal-sidebar-link i {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: rgba(37,99,235,0.06);
    color: var(--color-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
    transition: var(--transition-smooth);
}
.portal-sidebar-link:hover {
    background: rgba(37,99,235,0.05);
    color: var(--primary-400);
    border-color: rgba(37, 99, 235, 0.1);
    transform: translateX(-4px);
}
.portal-sidebar-link:hover i {
    background: rgba(37,99,235,0.1);
    color: var(--primary-400);
}
.portal-sidebar-link.active {
    background: linear-gradient(135deg, var(--primary-400) 0%, var(--primary-500) 100%);
    color: #fff !important;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
}
.portal-sidebar-link.active i {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

/* Content Container */
.portal-content-glass {
    background: var(--bg-glass-card);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1px solid var(--border-glass);
    border-radius: 24px;
    box-shadow: var(--shadow-premium);
    padding: 3rem;
    min-height: 600px;
    position: relative;
    z-index: 2;
}
.portal-page-header {
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--color-border);
}
.portal-title-gradient {
    font-size: 1.8rem;
    font-weight: 900;
    background: linear-gradient(135deg, var(--color-text-main), var(--color-gov-blue-light));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
}
.portal-title-gradient::before {
    content: '';
    display: block;
    width: 6px;
    height: 32px;
    background: linear-gradient(to bottom, var(--primary-400), var(--success));
    border-radius: 4px;
}

/* Custom styled content elements */
.portal-rich-text {
    color: var(--color-text-main);
    font-size: 1.05rem;
    line-height: 1.95;
    font-weight: 500;
}
.portal-feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin: 2.5rem 0;
}
.portal-feature-card {
    background: rgba(255,255,255,0.01);
    border: 1px solid var(--color-border);
    border-radius: 18px;
    padding: 2rem;
    transition: var(--transition-smooth);
    box-shadow: 0 4px 12px rgba(0,0,0,0.01);
}
.portal-feature-card:hover {
    transform: translateY(-5px);
    border-color: rgba(37,99,235,0.25);
    box-shadow: 0 15px 35px rgba(37,99,235,0.06);
    background: rgba(37,99,235,0.01);
}
.portal-feature-icon {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    background: rgba(37,99,235,0.07);
    color: var(--primary-400);
}
.portal-feature-card:nth-child(2) .portal-feature-icon {
    background: rgba(14, 166, 110, 0.07);
    color: var(--success);
}
.portal-feature-card:nth-child(3) .portal-feature-icon {
    background: rgba(240, 165, 0, 0.07);
    color: var(--color-gov-gold);
}
.portal-feature-card:nth-child(4) .portal-feature-icon {
    background: rgba(220, 38, 38, 0.07);
    color: #dc2626;
}

.portal-timeline {
    position: relative;
    padding-right: 2.5rem;
    margin: 2.5rem 0;
}
.portal-timeline::before {
    content: '';
    position: absolute;
    right: 12px;
    top: 5px;
    bottom: 5px;
    width: 2px;
    background: var(--color-border);
}
.portal-timeline-item {
    position: relative;
    margin-bottom: 2.5rem;
}
.portal-timeline-item:last-child {
    margin-bottom: 0;
}
.portal-timeline-badge {
    position: absolute;
    right: -38px;
    top: 2px;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: var(--bg-glass-card);
    border: 2px solid var(--primary-400);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--primary-400);
    z-index: 2;
}
.portal-timeline-item:nth-child(2) .portal-timeline-badge { border-color: var(--success); color: var(--success); }
.portal-timeline-item:nth-child(3) .portal-timeline-badge { border-color: var(--color-gov-gold); color: var(--color-gov-gold); }
.portal-timeline-item:nth-child(4) .portal-timeline-badge { border-color: #dc2626; color: #dc2626; }

.portal-premium-box {
    background: linear-gradient(135deg, rgba(26, 107, 204, 0.03) 0%, rgba(14, 166, 110, 0.03) 100%);
    border: 1px solid var(--color-border);
    border-radius: 18px;
    padding: 2rem;
    margin: 2rem 0;
    position: relative;
    overflow: hidden;
}
.portal-premium-box::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
    pointer-events: none;
}

.portal-list-styled {
    list-style: none;
    padding: 0;
    margin: 1.5rem 0;
}
.portal-list-styled li {
    position: relative;
    padding-right: 1.8rem;
    margin-bottom: 0.8rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--color-text-main);
}
.portal-list-styled li::before {
    content: "\f00c";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    position: absolute;
    right: 0;
    top: 2px;
    color: var(--success);
    font-size: 0.85rem;
}

@media (max-width: 991px) {
    .portal-mobile-nav {
        display: block;
    }
    .portal-content-glass {
        padding: 2rem;
    }
    .portal-container {
        padding: 1.5rem 1rem;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
$dbPages = \App\Helpers\PortalCMSHelper::getPages();
$navItems = [];
foreach ($dbPages as $p) {
    if (in_array($p->sort_order, [5, 8, 11])) {
        $navItems[] = null; // separator
    }
    $navItems[] = [
        'key'   => $p->slug,
        'icon'  => $p->icon ?: 'fa-file-lines',
        'label' => $p->title
    ];
}
?>

<div class="portal-container">
    <div class="portal-bg-glow-1"></div>
    <div class="portal-bg-glow-2"></div>
    
    <!-- Mobile Pill Navigation Bar (visible only on mobile) -->
    <div class="portal-mobile-nav">
        <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($item !== null): ?>
                <a href="<?php echo e(url('portal/' . $item['key'])); ?>"
                   class="portal-mobile-link <?php echo e($page === $item['key'] ? 'active' : ''); ?>">
                    <i class="fa-solid <?php echo e($item['icon']); ?>"></i>
                    <?php echo e($item['label']); ?>

                </a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="row g-4">
        <!-- Sidebar Navigation (Desktop only) -->
        <div class="col-lg-4 col-xl-3 d-none d-lg-block">
            <div class="portal-sidebar-card">
                <span class="portal-sidebar-title">تصفح البوابة الإلكترونية</span>
                <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($item === null): ?>
                        <div class="portal-sidebar-sep"></div>
                    <?php else: ?>
                        <a href="<?php echo e(url('portal/' . $item['key'])); ?>"
                           class="portal-sidebar-link <?php echo e($page === $item['key'] ? 'active' : ''); ?>">
                            <i class="fa-solid <?php echo e($item['icon']); ?>"></i>
                            <?php echo e($item['label']); ?>

                        </a>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <!-- Main Content Panel (Desktop + Mobile) -->
        <div class="col-lg-8 col-xl-9">
            <div class="portal-content-glass animate__animated animate__fadeIn">
                
                <div class="portal-page-header">
                    <h2 class="portal-title-gradient"><?php echo e($title); ?></h2>
                </div>

                <div class="portal-rich-text">
                    <?php if(isset($dfeps)): ?>
                        <script>
                            window.portalDfeps = <?php echo json_encode($dfeps, 15, 512) ?>;
                        </script>
                    <?php endif; ?>

                    <?php echo $portal_page->content; ?>

                </div>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sig\resources\views/portal/page.blade.php ENDPATH**/ ?>