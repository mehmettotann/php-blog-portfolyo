<?php
use App\Helpers;

$activeAdminNav = $activeAdminNav ?? '';
$unreadMessagesCount = (int) ($unreadMessagesCount ?? 0);
$flashes = Helpers::getFlashes();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= Helpers::e(Helpers::csrfToken()) ?>">
    <title><?= Helpers::e(($pageTitle ?? 'Yönetim') . ' — Yönetim Paneli') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        (function () {
            var saved = localStorage.getItem('bp-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'light'));
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/style.css?v=1.3">
    <link rel="stylesheet" href="/assets/css/admin.css?v=1.3">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-brand">
                <a href="/admin" class="brand brand-sm">
                    <span class="brand-mark">BP</span>
                    <strong>Yönetim</strong>
                </a>
                <a href="/" target="_blank" class="view-site-link" title="Siteyi yeni sekmede aç">Siteyi Gör ↗</a>
            </div>

            <nav class="admin-nav" aria-label="Yönetim menüsü">
                <a href="/admin" class="<?= $activeAdminNav === 'dashboard' ? 'is-active' : '' ?>">Genel Bakış</a>
                <a href="/admin/raporlar" class="<?= $activeAdminNav === 'reports' ? 'is-active' : '' ?>">Raporlar &amp; İstatistik</a>
                <a href="/admin/yazilar" class="<?= $activeAdminNav === 'posts' ? 'is-active' : '' ?>">Blog Yazıları</a>
                <a href="/admin/kategoriler" class="<?= $activeAdminNav === 'categories' ? 'is-active' : '' ?>">Kategoriler</a>
                <a href="/admin/projeler" class="<?= $activeAdminNav === 'projects' ? 'is-active' : '' ?>">Projeler</a>
                <a href="/admin/medya" class="<?= $activeAdminNav === 'media' ? 'is-active' : '' ?>">Medya Kütüphanesi</a>
                <a href="/admin/mesajlar" class="<?= $activeAdminNav === 'messages' ? 'is-active' : '' ?>">
                    <span>Mesajlar</span>
                    <?php if ($unreadMessagesCount > 0): ?>
                        <span class="nav-badge"><?= $unreadMessagesCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/admin/ayarlar" class="<?= $activeAdminNav === 'settings' ? 'is-active' : '' ?>">Site &amp; Hakkımda</a>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-user-meta">
                    <strong><?= Helpers::e((string) ($adminUser['display_name'] ?? 'Yönetici')) ?></strong>
                    <span>@<?= Helpers::e((string) ($adminUser['username'] ?? 'admin')) ?></span>
                </div>
                <div class="admin-footer-actions">
                    <button type="button" class="btn btn-secondary btn-xs" id="theme-toggle">Tema</button>
                    <form action="/admin/cikis" method="post">
                        <?= Helpers::csrfField() ?>
                        <button type="submit" class="btn btn-danger-outline btn-xs">Çıkış</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="admin-main-wrap">
            <header class="admin-topbar">
                <button type="button" class="icon-btn admin-menu-toggle" id="admin-menu-toggle" aria-label="Menüyü aç/kapat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
                <h1 class="admin-page-title"><?= Helpers::e($pageTitle ?? 'Yönetim') ?></h1>
                <div class="admin-topbar-right">
                    <a href="/admin/yazilar/yeni" class="btn btn-secondary btn-xs">+ Yazı</a>
                    <a href="/admin/projeler/yeni" class="btn btn-primary btn-xs">+ Proje</a>
                </div>
            </header>

            <main class="admin-content">
                <?php if (!empty($flashes)): ?>
                    <div class="flash-stack">
                        <?php foreach ($flashes as $fl): ?>
                            <div class="alert alert-<?= Helpers::e($fl['type']) ?>" role="status">
                                <?= Helpers::e($fl['message']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Reusable Media Library Picker Modal -->
    <div class="modal-overlay" id="media-picker-modal" hidden role="dialog" aria-modal="true" aria-label="Medya Seçici">
        <div class="modal-card">
            <div class="modal-head">
                <h3 id="media-picker-title">Sunucudan Medya Seç</h3>
                <button type="button" class="icon-btn" id="media-picker-close" aria-label="Kapat">&times;</button>
            </div>
            <div class="modal-body" id="media-picker-grid">
                <p class="muted-text">Yükleniyor...</p>
            </div>
        </div>
    </div>

    <script src="/assets/js/app.js?v=1.1" defer></script>
    <script src="/assets/js/admin.js?v=1.1" defer></script>
</body>
</html>
