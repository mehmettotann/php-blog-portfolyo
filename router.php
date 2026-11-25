<?php
declare(strict_types=1);

/**
 * Lokal geliştirme sunucusu yönlendiricisi (`php -S 127.0.0.1:8000 router.php`).
 * - Hassas dosya ve klasörlere erişimi engeller (cPanel .htaccess davranışını taklit eder).
 * - MP4/WebM video dosyaları için HTTP 206 Partial Content (Byte-Range) akışı sağlar.
 */

$uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$decoded = rawurldecode($uri);

// Block sensitive paths just like .htaccess
if (preg_match('#^/(app|config|views|database|scripts)(/|$)#i', $decoded)
    || preg_match('#^/(\.|router\.php|README\.md)#i', $decoded)
    || preg_match('#\.(env|sql|sh)$#i', $decoded)
) {
    http_response_code(403);
    echo '403 Erişim Engellendi';
    exit;
}

// Block PHP execution inside /uploads/
if (preg_match('#^/uploads/.*\.(php|phtml|phar)$#i', $decoded)) {
    http_response_code(403);
    echo '403 Erişim Engellendi';
    exit;
}

$fullPath = realpath(__DIR__ . $decoded);
$rootReal = realpath(__DIR__);

if ($fullPath !== false && $rootReal !== false && str_starts_with($fullPath, $rootReal . DIRECTORY_SEPARATOR) && is_file($fullPath)) {
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    // Proper HTTP 206 Byte-Range streaming for HTML5 MP4/WebM videos during local dev
    if (in_array($ext, ['mp4', 'webm', 'm4v'], true)) {
        $mime = $ext === 'webm' ? 'video/webm' : 'video/mp4';
        $size = filesize($fullPath);
        if ($size === false) {
            http_response_code(404);
            exit;
        }

        $start = 0;
        $end = $size - 1;
        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=86400');

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] !== '') {
                $start = (int) $m[1];
            }
            if ($m[2] !== '') {
                $end = (int) $m[2];
            }
            if ($start > $end || $start >= $size) {
                http_response_code(416);
                header("Content-Range: bytes */{$size}");
                exit;
            }
            $end = min($end, $size - 1);
            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$size}");
        }

        $length = $end - $start + 1;
        header('Content-Length: ' . $length);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            exit;
        }

        $fp = fopen($fullPath, 'rb');
        if ($fp !== false) {
            fseek($fp, $start);
            $remaining = $length;
            while ($remaining > 0 && !feof($fp)) {
                $chunk = fread($fp, min(65536, $remaining));
                if ($chunk === false) {
                    break;
                }
                echo $chunk;
                flush();
                $remaining -= strlen($chunk);
            }
            fclose($fp);
        }
        exit;
    }

    // Serve other static assets natively
    return false;
}

require __DIR__ . '/index.php';
