<?php
/**
 * Sovereign Design System - Button Component
 * @var string $label
 * @var string|null $type // button, submit, reset (default: button)
 * @var string|null $style // primary, secondary, success, danger, outline, ghost (default: primary)
 * @var string|null $size // sm, md, lg (default: md)
 * @var string|null $icon // FontAwesome icon class
 * @var string|null $class // Custom classes
 * @var bool|null $disabled
 * @var bool|null $isLoading
 * @var string|null $id
 * @var string|null $onClick
 */

$type = $type ?? 'button';
$style = $style ?? 'primary';
$size = $size ?? 'md';

// Base classes
$btnClass = 'btn inline-flex items-center justify-center gap-2 font-bold transition-all duration-300 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed border rounded-xl';

// Style mapping
switch ($style) {
    case 'primary':
        $btnClass .= ' bg-primary-500 hover:bg-primary-600 text-white border-transparent shadow-sm hover:shadow-lg focus:ring-2 focus:ring-primary-500/20';
        break;
    case 'success':
        $btnClass .= ' bg-emerald-500 hover:bg-emerald-600 text-white border-transparent shadow-sm hover:shadow-lg focus:ring-2 focus:ring-emerald-500/20';
        break;
    case 'secondary':
        $btnClass .= ' bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-100 border-transparent focus:ring-2 focus:ring-slate-500/20';
        break;
    case 'danger':
        $btnClass .= ' bg-red-500 hover:bg-red-600 text-white border-transparent shadow-sm focus:ring-2 focus:ring-red-500/20';
        break;
    case 'outline':
        $btnClass .= ' bg-transparent border-slate-300 hover:bg-slate-50 text-slate-700 dark:border-slate-600 dark:hover:bg-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-slate-500/20';
        break;
    case 'ghost':
        $btnClass .= ' bg-transparent hover:bg-slate-100 text-slate-600 dark:hover:bg-slate-800 dark:text-slate-300 border-transparent';
        break;
}

// Size mapping
switch ($size) {
    case 'sm':
        $btnClass .= ' px-3 py-1.5 text-xs';
        break;
    case 'md':
        $btnClass .= ' px-4 py-2 text-sm';
        break;
    case 'lg':
        $btnClass .= ' px-5 py-2.5 text-base';
        break;
}

$disabledAttr = ($disabled ?? false) || ($isLoading ?? false) ? 'disabled' : '';
$idAttr = !empty($id) ? 'id="' . htmlspecialchars($id) . '"' : '';
$clickAttr = !empty($onClick) ? 'onclick="' . htmlspecialchars($onClick) . '"' : '';
?>
<button type="<?= htmlspecialchars($type) ?>" class="<?= $btnClass ?> <?= $class ?? '' ?>" <?= $disabledAttr ?> <?= $idAttr ?> <?= $clickAttr ?>>
    <?php if ($isLoading ?? false): ?>
        <i class="fa-solid fa-spinner fa-spin me-1"></i>
    <?php elseif (!empty($icon)): ?>
        <i class="<?= $icon ?> me-1"></i>
    <?php endif; ?>
    <span><?= htmlspecialchars($label) ?></span>
</button>
