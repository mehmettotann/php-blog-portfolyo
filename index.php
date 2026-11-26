<?php
declare(strict_types=1);

// =========================================================================
// example.com — Front Controller
// =========================================================================

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

App\Env::load(__DIR__ . '/.env');
$config = require __DIR__ . '/config/config.php';

date_default_timezone_set((string) ($config['app']['timezone'] ?? 'Europe/Istanbul'));

if (!empty($config['app']['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

// Security response headers (also reinforced by .htaccess on LiteSpeed/Apache)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

App\LiteSpeed::configure(
    !empty($config['litespeed']['enabled']),
    (int) ($config['litespeed']['ttl'] ?? 3600)
);

try {
    App\Database::connect($config['db']);
} catch (Throwable $e) {
    http_response_code(503);
    $debug = !empty($config['app']['debug']);
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>Bağlantı Hatası</title>'
        . '<style>body{font-family:system-ui,sans-serif;max-width:640px;margin:60px auto;padding:0 20px;line-height:1.6;color:#1e293b}'
        . 'code{background:#f1f5f9;padding:2px 6px;border-radius:4px}</style></head><body>'
        . '<h1>Veritabanı Bağlantısı Kurulamadı</h1>'
        . '<p>Lütfen <code>.env</code> dosyasındaki MySQL ayarlarını kontrol edin ve <code>php database/install.php</code> kurulum betiğini çalıştırdığınızdan emin olun.</p>';
    if ($debug) {
        echo '<pre style="background:#fef2f2;border:1px solid #fecaca;padding:12px;border-radius:8px;overflow:auto;">'
            . App\Helpers::e($e->getMessage()) . '</pre>';
    }
    echo '</body></html>';
    exit;
}

// Log AI crawlers, search engines, and social preview bots server-side (zero impact on humans)
if (in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'HEAD'], true)) {
    try {
        App\Analytics::recordBotVisit((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    } catch (Throwable) {
        // Non-fatal
    }
}

$router = new App\Router();
$site = new App\Controllers\SiteController($config);
$admin = new App\Controllers\AdminController($config);

// Public Routes
$router->get('/', static fn() => $site->home());
$router->get('/hakkimda', static fn() => $site->about());
$router->get('/yazilar', static fn() => $site->blogIndex());
$router->get('/yazilar/{slug}', static fn(array $p) => $site->blogShow($p['slug']));
$router->get('/projeler', static fn() => $site->projectsIndex());
$router->get('/projeler/{slug}', static fn(array $p) => $site->projectShow($p['slug']));
$router->get('/iletisim', static fn() => $site->contact());
$router->post('/iletisim', static fn() => $site->contactSubmit());
$router->get('/sitemap.xml', static fn() => $site->sitemap());
$router->get('/rss.xml', static fn() => $site->rss());
$router->get('/robots.txt', static fn() => $site->robots());
$router->get('/llms.txt', static fn() => $site->llmsTxt());

// Lightweight Public Analytics & Like API (Cache-exempt)
$router->post('/api/track', static fn() => $site->apiTrackVisit());
$router->get('/api/likes', static fn() => $site->apiGetLike());
$router->post('/api/like', static fn() => $site->apiToggleLike());

// Admin Auth
$router->get('/admin/giris', static fn() => $admin->loginForm());
$router->post('/admin/giris', static fn() => $admin->loginSubmit());
$router->post('/admin/cikis', static fn() => $admin->logout());

// Admin Dashboard & Analytics Reports
$router->get('/admin', static fn() => $admin->dashboard());
$router->get('/admin/raporlar', static fn() => $admin->reports());

// Admin Posts
$router->get('/admin/yazilar', static fn() => $admin->postsIndex());
$router->get('/admin/yazilar/yeni', static fn() => $admin->postCreate());
$router->get('/admin/yazilar/{id}/duzenle', static fn(array $p) => $admin->postEdit((int) $p['id']));
$router->post('/admin/yazilar/kaydet', static fn() => $admin->postSave());
$router->post('/admin/yazilar/{id}/sil', static fn(array $p) => $admin->postDelete((int) $p['id']));

// Admin Categories
$router->get('/admin/kategoriler', static fn() => $admin->categoriesIndex());
$router->post('/admin/kategoriler/kaydet', static fn() => $admin->categorySave());
$router->post('/admin/kategoriler/{id}/sil', static fn(array $p) => $admin->categoryDelete((int) $p['id']));

// Admin Projects
$router->get('/admin/projeler', static fn() => $admin->projectsIndex());
$router->get('/admin/projeler/yeni', static fn() => $admin->projectCreate());
$router->get('/admin/projeler/{id}/duzenle', static fn(array $p) => $admin->projectEdit((int) $p['id']));
$router->post('/admin/projeler/kaydet', static fn() => $admin->projectSave());
$router->post('/admin/projeler/{id}/sil', static fn(array $p) => $admin->projectDelete((int) $p['id']));

// Admin Media & JSON APIs
$router->get('/admin/medya', static fn() => $admin->mediaIndex());
$router->post('/admin/medya/yukle', static fn() => $admin->mediaUpload());
$router->post('/admin/medya/{id}/sil', static fn(array $p) => $admin->mediaDelete((int) $p['id']));
$router->post('/admin/api/upload', static fn() => $admin->apiUpload());
$router->get('/admin/api/media', static fn() => $admin->apiMediaList());

// Admin Settings & About
$router->get('/admin/ayarlar', static fn() => $admin->settingsForm());
$router->post('/admin/ayarlar/kaydet', static fn() => $admin->settingsSave());

// Admin Contact Messages
$router->get('/admin/mesajlar', static fn() => $admin->messagesIndex());
$router->post('/admin/mesajlar/{id}/okundu', static fn(array $p) => $admin->messageToggleRead((int) $p['id']));
$router->post('/admin/mesajlar/{id}/sil', static fn(array $p) => $admin->messageDelete((int) $p['id']));

$router->setNotFound(static fn() => $site->notFound());

$router->dispatch(
    (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string) ($_SERVER['REQUEST_URI'] ?? '/')
);
