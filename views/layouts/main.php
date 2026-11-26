<?php
use App\Auth;
use App\Helpers;
use App\Seo;

$siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
$siteTagline = $settings['site_tagline'] ?? 'Kişisel Blog ve Dijital Portfolyo';
$metaDescription = $metaDescription ?? ($settings['site_description'] ?? $siteTagline);
$metaRobots = $metaRobots ?? 'index, follow';
$ogType = $ogType ?? 'website';
$ogMeta = $ogMeta ?? null;
$articleMeta = $articleMeta ?? null;
$jsonLd = $jsonLd ?? [];
$activeNav = $activeNav ?? '';
$footerText = $settings['footer_text'] ?? ('© ' . date('Y') . ' Adınız Soyadınız · Tüm hakları saklıdır.');
$socials = $socials ?? Helpers::settingJson('social_links', []);
$flashes = Helpers::getFlashes();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($pageTitle ?? $siteTitle) ?></title>
    <meta name="description" content="<?= Helpers::e($metaDescription) ?>">
    <meta name="robots" content="<?= Helpers::e($metaRobots) ?>">
    <meta name="author" content="<?= Helpers::e($siteTitle) ?>">
    <meta name="theme-color" content="#2563eb">
    <?php if (!empty($canonicalUrl)): ?>
    <link rel="canonical" href="<?= Helpers::e($canonicalUrl) ?>">
    <?php endif; ?>

    <!-- Favicon & App Icons -->
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="alternate" type="application/rss+xml" title="<?= Helpers::e($siteTitle) ?> — RSS" href="/rss.xml">

    <!-- Open Graph / WhatsApp / LinkedIn / Facebook -->
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="<?= Helpers::e($siteTitle) ?>">
    <meta property="og:type" content="<?= Helpers::e($ogType) ?>">
    <meta property="og:title" content="<?= Helpers::e($pageTitle ?? $siteTitle) ?>">
    <meta property="og:description" content="<?= Helpers::e($metaDescription) ?>">
    <?php if (!empty($canonicalUrl)): ?>
    <meta property="og:url" content="<?= Helpers::e($canonicalUrl) ?>">
    <?php endif; ?>
    <?php if (is_array($ogMeta) && !empty($ogMeta['url'])): ?>
    <meta property="og:image" content="<?= Helpers::e((string) $ogMeta['url']) ?>">
    <?php if (!empty($ogMeta['secure_url'])): ?>
    <meta property="og:image:secure_url" content="<?= Helpers::e((string) $ogMeta['secure_url']) ?>">
    <?php endif; ?>
    <meta property="og:image:type" content="<?= Helpers::e((string) ($ogMeta['type'] ?? 'image/jpeg')) ?>">
    <meta property="og:image:width" content="<?= (int) ($ogMeta['width'] ?? 1200) ?>">
    <meta property="og:image:height" content="<?= (int) ($ogMeta['height'] ?? 630) ?>">
    <meta property="og:image:alt" content="<?= Helpers::e((string) ($ogMeta['alt'] ?? $siteTitle)) ?>">
    <meta itemprop="image" content="<?= Helpers::e((string) $ogMeta['url']) ?>">
    <?php endif; ?>

    <?php if (is_array($articleMeta)): ?>
        <?php if (!empty($articleMeta['published_time'])): ?>
    <meta property="article:published_time" content="<?= Helpers::e((string) $articleMeta['published_time']) ?>">
        <?php endif; ?>
        <?php if (!empty($articleMeta['modified_time'])): ?>
    <meta property="article:modified_time" content="<?= Helpers::e((string) $articleMeta['modified_time']) ?>">
        <?php endif; ?>
        <?php if (!empty($articleMeta['section'])): ?>
    <meta property="article:section" content="<?= Helpers::e((string) $articleMeta['section']) ?>">
        <?php endif; ?>
    <meta property="article:author" content="<?= Helpers::e($siteTitle) ?>">
    <?php endif; ?>

    <!-- Twitter / X Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= Helpers::e($pageTitle ?? $siteTitle) ?>">
    <meta name="twitter:description" content="<?= Helpers::e($metaDescription) ?>">
    <?php if (is_array($ogMeta) && !empty($ogMeta['url'])): ?>
    <meta name="twitter:image" content="<?= Helpers::e((string) $ogMeta['url']) ?>">
    <meta name="twitter:image:alt" content="<?= Helpers::e((string) ($ogMeta['alt'] ?? $siteTitle)) ?>">
    <?php endif; ?>

    <?php if (!empty($jsonLd)): ?>
    <!-- Schema.org JSON-LD -->
    <?= Seo::renderJsonLd($jsonLd) ?>
    <?php endif; ?>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Zero-FOUC Theme Initialization -->
    <script>
        (function () {
            var saved = localStorage.getItem('bp-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var theme = saved || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <link rel="stylesheet" href="/assets/css/style.css?v=1.3">
</head>
<?php
$trackAttrs = '';
if (!empty($trackMeta) && is_array($trackMeta)) {
    $trackAttrs = ' data-track-type="' . Helpers::e((string) ($trackMeta['type'] ?? 'page')) . '"'
        . ' data-track-id="' . (int) ($trackMeta['id'] ?? 0) . '"'
        . ' data-track-title="' . Helpers::e((string) ($trackMeta['title'] ?? ($pageTitle ?? ''))) . '"';
}
?>
<body<?= $trackAttrs ?>>
    <a class="skip-link" href="#main-content">İçeriğe geç</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="/" class="brand" aria-label="<?= Helpers::e($siteTitle) ?> Ana Sayfa">
                <span class="brand-mark" aria-hidden="true">BP</span>
                <span class="brand-text">
                    <strong><?= Helpers::e($siteTitle) ?></strong>
                </span>
            </a>

            <nav class="main-nav" id="main-nav" aria-label="Ana menü">
                <a href="/" class="<?= $activeNav === 'home' ? 'is-active' : '' ?>">Ana Sayfa</a>
                <a href="/hakkimda" class="<?= $activeNav === 'about' ? 'is-active' : '' ?>">Hakkımda</a>
                <a href="/yazilar" class="<?= $activeNav === 'blog' ? 'is-active' : '' ?>">Yazılar</a>
                <a href="/projeler" class="<?= $activeNav === 'projects' ? 'is-active' : '' ?>">Projeler</a>
                <a href="/iletisim" class="<?= $activeNav === 'contact' ? 'is-active' : '' ?>">İletişim</a>
                <?php if (Auth::isLoggedIn()): ?>
                    <a href="/admin" class="nav-admin-pill">Panel</a>
                <?php endif; ?>
            </nav>

            <div class="header-actions">
                <button type="button" class="icon-btn" id="theme-toggle" aria-label="Açık / koyu temayı değiştir" title="Tema değiştir">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                    </svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
                <button type="button" class="icon-btn nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="Menüyü aç/kapat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <main id="main-content" class="site-main">
        <?php if (!empty($flashes)): ?>
            <div class="container flash-stack">
                <?php foreach ($flashes as $fl): ?>
                    <div class="alert alert-<?= Helpers::e($fl['type']) ?>" role="status">
                        <?= Helpers::e($fl['message']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>

        <?php if (empty($hasInlineShare) && empty($hideShareBar)): ?>
            <section class="global-share-band" aria-label="Sayfayı paylaş">
                <div class="container">
                    <?php
                    $shareHeading = 'Bu sayfayı paylaş';
                    $shareTitle = (string) ($pageTitle ?? $siteTitle);
                    $shareUrl = (string) ($canonicalUrl ?? '');
                    require dirname(__DIR__) . '/site/_share_bar.php';
                    ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <a href="/" class="brand brand-sm">
                    <span class="brand-mark" aria-hidden="true">BP</span>
                    <strong><?= Helpers::e($siteTitle) ?></strong>
                </a>
                <p class="footer-copy"><?= Helpers::e($footerText) ?></p>
            </div>

            <div class="footer-links">
                <div class="footer-nav">
                    <a href="/hakkimda">Hakkımda</a>
                    <a href="/yazilar">Yazılar</a>
                    <a href="/projeler">Projeler</a>
                    <a href="/iletisim">İletişim</a>
                </div>
                <?php if (!empty($socials)): ?>
                    <div class="footer-socials">
                        <?php
                        $socialLabels = [
                            'github' => 'GitHub',
                            'linkedin' => 'LinkedIn',
                            'x' => 'X',
                            'instagram' => 'Instagram',
                            'youtube' => 'YouTube',
                        ];
                        foreach ($socialLabels as $k => $label):
                            if (empty($socials[$k])) continue;
                        ?>
                            <a href="<?= Helpers::safeHref($socials[$k]) ?>" target="_blank" rel="noopener noreferrer"><?= $label ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Lightbox Modal for Gallery & Article Images -->
    <div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-label="Görsel önizleme">
        <div class="lightbox-backdrop" data-lightbox-close></div>
        <div class="lightbox-dialog">
            <button type="button" class="lightbox-close" data-lightbox-close aria-label="Kapat">&times;</button>
            <button type="button" class="lightbox-nav lightbox-prev" id="lightbox-prev" aria-label="Önceki görsel">&#8249;</button>
            <figure class="lightbox-figure">
                <img src="" alt="" id="lightbox-img">
                <figcaption id="lightbox-caption"></figcaption>
            </figure>
            <button type="button" class="lightbox-nav lightbox-next" id="lightbox-next" aria-label="Sonraki görsel">&#8250;</button>
        </div>
    </div>

    <script src="/assets/js/app.js?v=1.3" defer></script>
</body>
</html>
