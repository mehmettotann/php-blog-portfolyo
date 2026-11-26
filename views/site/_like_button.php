<?php
use App\Helpers;

$likeType = $likeType ?? 'post';
$likeId = (int) ($likeId ?? 0);
$likePrompt = $likeType === 'project' ? 'Bu projeyi beğendin mi?' : 'Bu yazıyı beğendin mi?';
?>
<?php if ($likeId > 0): ?>
<div class="like-box" data-like-widget data-like-type="<?= Helpers::e($likeType) ?>" data-like-id="<?= $likeId ?>">
    <span class="like-box-text"><?= Helpers::e($likePrompt) ?></span>
    <button type="button" class="like-btn" data-like-btn aria-pressed="false">
        <svg class="like-heart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
        <span data-like-label>Beğen</span>
        <span class="like-count" data-like-count>0</span>
    </button>
</div>
<?php endif; ?>
