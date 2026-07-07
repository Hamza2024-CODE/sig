<?php
/**
 * Sovereign Design System - Generic Card Component
 * @var string $content
 * @var string|null $title
 * @var string|null $icon
 * @var string|null $class
 * @var string|null $header_actions
 * @var string|null $footer
 * @var bool|null $isGlass // If true, applies premium .glass-card style
 */
$cardClass = ($isGlass ?? false) ? 'glass-card' : 'bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/50 rounded-2xl shadow-sm transition-all duration-300';
?>
<div class="<?= $cardClass ?> <?= $class ?? '' ?>">
    <?php if (!empty($title) || !empty($icon) || !empty($header_actions)): ?>
        <div class="d-flex align-items-center justify-content-between p-4 border-b border-slate-100 dark:border-slate-700/50">
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($icon)): ?>
                    <span class="text-primary"><i class="<?= $icon ?> fs-5"></i></span>
                <?php endif; ?>
                <?php if (!empty($title)): ?>
                    <span class="fw-bold text-slate-800 dark:text-slate-100 font-sans" style="font-size: 1.1rem; font-family: 'Cairo', sans-serif;"><?= htmlspecialchars($title) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($header_actions)): ?>
                <div class="card-actions">
                    <?= $header_actions ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="p-4">
        <?= $content ?>
    </div>
    <?php if (!empty($footer)): ?>
        <div class="p-4 bg-transparent border-t border-slate-100 dark:border-slate-700/50 rounded-b-2xl">
            <?= $footer ?>
        </div>
    <?php endif; ?>
</div>
