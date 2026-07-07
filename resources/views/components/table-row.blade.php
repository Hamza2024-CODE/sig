<?php
/**
 * Sovereign Design System - Table Row Component
 * @var array $cells // array of cell HTML contents or strings
 * @var string|null $class // Custom classes
 * @var string|null $onClick // Javascript action
 * @var bool|null $isSelected // Highlighted row state
 */

$rowClass = 'transition-colors duration-200';
if ($isSelected ?? false) {
    $rowClass .= ' bg-primary-50 dark:bg-primary-950/20';
} else {
    $rowClass .= ' hover:bg-slate-50 dark:hover:bg-slate-800/40';
}
$clickAttr = !empty($onClick) ? 'onclick="' . htmlspecialchars($onClick) . '" style="cursor: pointer;"' : '';
?>
<tr class="<?= $rowClass ?> <?= $class ?? '' ?>" <?= $clickAttr ?>>
    <?php foreach ($cells as $cell): ?>
        <td class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/50 text-slate-800 dark:text-slate-200 font-sans">
            <?= $cell ?>
        </td>
    <?php endforeach; ?>
</tr>
