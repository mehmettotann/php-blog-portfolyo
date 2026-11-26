<?php
use App\Helpers;

$shareUrl = (string) ($shareUrl ?? ($canonicalUrl ?? ''));
if ($shareUrl === '') {
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'example.com');
    $reqPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $shareUrl = $scheme . '://' . $host . $reqPath;
}
$shareTitle = (string) ($shareTitle ?? ($pageTitle ?? ($settings['site_title'] ?? 'Adınız Soyadınız')));
$shareHeading = (string) ($shareHeading ?? 'Bu sayfayı paylaş');

$encUrl = rawurlencode($shareUrl);
$encTitle = rawurlencode($shareTitle);
$encTextUrl = rawurlencode($shareTitle . ' ' . $shareUrl);
?>
<div class="share-bar" data-share-bar data-share-url="<?= Helpers::e($shareUrl) ?>" data-share-title="<?= Helpers::e($shareTitle) ?>">
    <span class="share-bar-label"><?= Helpers::e($shareHeading) ?></span>
    <div class="share-buttons">
        <a class="share-btn share-whatsapp"
           href="https://api.whatsapp.com/send?text=<?= $encTextUrl ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="WhatsApp ile paylaş" title="WhatsApp">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
            <span>WhatsApp</span>
        </a>

        <a class="share-btn share-facebook"
           href="https://www.facebook.com/sharer/sharer.php?u=<?= $encUrl ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="Facebook ile paylaş" title="Facebook">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
            </svg>
            <span>Facebook</span>
        </a>

        <a class="share-btn share-x"
           href="https://twitter.com/intent/tweet?text=<?= $encTitle ?>&amp;url=<?= $encUrl ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="X (Twitter) ile paylaş" title="X">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4l11.733 16h4.267l-11.733 -16z"/>
                <path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"/>
            </svg>
            <span>X</span>
        </a>

        <a class="share-btn share-linkedin"
           href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $encUrl ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="LinkedIn ile paylaş" title="LinkedIn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                <rect x="2" y="9" width="4" height="12"/>
                <circle cx="4" cy="4" r="2"/>
            </svg>
            <span>LinkedIn</span>
        </a>

        <a class="share-btn share-telegram"
           href="https://t.me/share/url?url=<?= $encUrl ?>&amp;text=<?= $encTitle ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="Telegram ile paylaş" title="Telegram">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            <span>Telegram</span>
        </a>

        <button type="button" class="share-btn share-instagram" data-share-instagram
                aria-label="Instagram veya telefon paylaşım menüsüyle paylaş" title="Instagram / Uygulamalar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
            </svg>
            <span>Instagram</span>
        </button>

        <button type="button" class="share-btn share-copy" data-share-copy
                aria-label="Bağlantıyı kopyala" title="Linki Kopyala">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
            </svg>
            <span data-share-copy-label>Kopyala</span>
        </button>
    </div>
    <span class="share-feedback" data-share-feedback role="status" aria-live="polite"></span>
</div>
