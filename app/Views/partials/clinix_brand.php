<?php
/** @var string $brandClass */
/** @var bool $showWordmark */
$brandClass = $brandClass ?? 'clinix-brand';
$showWordmark = !isset($showWordmark) || $showWordmark;
?>
<div class="<?= e($brandClass) ?>">
    <svg class="clinix-brand-mark" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
        <path d="M46 16a24 24 0 1 0 0 32" stroke="#9ec5d4" stroke-width="7" stroke-linecap="round"/>
        <path d="M46 16a24 24 0 0 1 0 32" stroke="#34b8a8" stroke-width="7" stroke-linecap="round"/>
        <path d="M32 25v14M25 32h14" stroke="#9ef0d0" stroke-width="4.5" stroke-linecap="round"/>
        <path d="M19 32h5l3-5 3.5 10 3-5h9" stroke="#163244" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <?php if ($showWordmark): ?>
        <span class="clinix-brand-wordmark">Clinix</span>
    <?php endif; ?>
</div>
