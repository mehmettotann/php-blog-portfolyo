<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use PDO;

final class Helpers
{
    /** @var array<string, string>|null */
    private static ?array $settingsCache = null;

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function slugify(string $text): string
    {
        $trMap = [
            'ç' => 'c', 'Ç' => 'c',
            'ğ' => 'g', 'Ğ' => 'g',
            'ı' => 'i', 'İ' => 'i', 'I' => 'i',
            'ö' => 'o', 'Ö' => 'o',
            'ş' => 's', 'Ş' => 's',
            'ü' => 'u', 'Ü' => 'u',
        ];
        $text = strtr($text, $trMap);
        $text = mb_strtolower($text, 'UTF-8');
        $text = (string) preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'icerik-' . bin2hex(random_bytes(3));
    }

    public static function formatDateTr(?string $dateString): string
    {
        if ($dateString === null || trim($dateString) === '') {
            return '';
        }
        try {
            $dt = new DateTimeImmutable($dateString);
        } catch (\Throwable) {
            return $dateString;
        }
        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];
        $monthName = $months[(int) $dt->format('n')] ?? $dt->format('m');
        return $dt->format('j') . ' ' . $monthName . ' ' . $dt->format('Y');
    }

    public static function formatDateTimeTr(?string $dateString): string
    {
        if ($dateString === null || trim($dateString) === '') {
            return '';
        }
        try {
            $dt = new DateTimeImmutable($dateString);
        } catch (\Throwable) {
            return $dateString;
        }
        return self::formatDateTr($dateString) . ' · ' . $dt->format('H:i');
    }

    public static function estimateReadingTime(string $text): int
    {
        $plain = trim(strip_tags($text));
        if ($plain === '') {
            return 1;
        }
        $words = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $count = is_array($words) ? count($words) : 0;
        return max(1, (int) ceil($count / 200));
    }

    /**
     * Parses a YouTube or Vimeo URL and returns provider, video_id, and safe embed URL.
     *
     * @return array{provider:string,video_id:string,embed_url:string,watch_url:string}|null
     */
    public static function parseVideoUrl(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // YouTube patterns: watch?v=, youtu.be/, embed/, shorts/, live/
        if (preg_match(
            '#(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:[^&\s]*&)*v=|embed/|shorts/|live/)|youtu\.be/)([a-zA-Z0-9_-]{11})#i',
            $url,
            $m
        )) {
            $id = $m[1];
            return [
                'provider' => 'youtube',
                'video_id' => $id,
                'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0',
                'watch_url' => 'https://www.youtube.com/watch?v=' . $id,
            ];
        }

        // Vimeo patterns: vimeo.com/123456789, player.vimeo.com/video/123456789, vimeo.com/channels/.../123456789
        if (preg_match(
            '#(?:https?://)?(?:www\.|player\.)?vimeo\.com/(?:video/|channels/[a-zA-Z0-9_-]+/|groups/[a-zA-Z0-9_-]+/videos/)?(\d{6,12})#i',
            $url,
            $m
        )) {
            $id = $m[1];
            return [
                'provider' => 'vimeo',
                'video_id' => $id,
                'embed_url' => 'https://player.vimeo.com/video/' . $id . '?dnt=1',
                'watch_url' => 'https://vimeo.com/' . $id,
            ];
        }

        return null;
    }

    public static function isSafeUrl(?string $url): bool
    {
        if ($url === null || trim($url) === '') {
            return false;
        }
        $url = trim($url);
        if (str_starts_with($url, '/')) {
            return !str_starts_with($url, '//');
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array(strtolower((string) $scheme), ['http', 'https', 'mailto'], true);
    }

    public static function safeHref(?string $url): string
    {
        if (!self::isSafeUrl($url)) {
            return '#';
        }
        return self::e(trim((string) $url));
    }

    public static function csrfToken(): string
    {
        Auth::startSession();
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(?string $token = null): bool
    {
        Auth::startSession();
        $provided = $token ?? ($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!is_string($provided) || $provided === '' || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals((string) $_SESSION['_csrf_token'], $provided);
    }

    public static function flash(string $type, string $message): void
    {
        Auth::startSession();
        $_SESSION['_flashes'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return array<int, array{type:string,message:string}>
     */
    public static function getFlashes(): array
    {
        Auth::startSession();
        $flashes = $_SESSION['_flashes'] ?? [];
        unset($_SESSION['_flashes']);
        return is_array($flashes) ? $flashes : [];
    }

    public static function redirect(string $path, int $code = 302): never
    {
        header('Location: ' . $path, true, $code);
        exit;
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;
        foreach ($units as $unit) {
            if ($size < 1024 || $unit === 'GB') {
                return number_format($size, 1, ',', '.') . ' ' . $unit;
            }
            $size /= 1024;
        }
        return $bytes . ' B';
    }

    public static function clientIp(): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return substr((string) $remote, 0, 45);
    }

    /**
     * @return array<string, string>
     */
    public static function allSettings(bool $fresh = false): array
    {
        if (self::$settingsCache !== null && !$fresh) {
            return self::$settingsCache;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        self::$settingsCache = $map;
        return $map;
    }

    public static function setting(string $key, string $default = ''): string
    {
        $all = self::allSettings();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function settingJson(string $key, array $default = []): array
    {
        $raw = self::setting($key, '');
        if ($raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function saveSetting(string $key, string $value): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES (:k, :v, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $stmt->execute([':k' => $key, ':v' => $value]);
        self::$settingsCache = null;
    }

    public static function uniqueSlug(string $table, string $baseSlug, ?int $ignoreId = null): string
    {
        $allowedTables = ['posts', 'projects', 'categories'];
        if (!in_array($table, $allowedTables, true)) {
            return $baseSlug;
        }

        $pdo = Database::pdo();
        $slug = self::slugify($baseSlug);
        $candidate = $slug;
        $counter = 2;

        while (true) {
            if ($ignoreId !== null) {
                $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE slug = :slug AND id <> :id LIMIT 1");
                $stmt->execute([':slug' => $candidate, ':id' => $ignoreId]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE slug = :slug LIMIT 1");
                $stmt->execute([':slug' => $candidate]);
            }

            if ($stmt->fetch() === false) {
                return $candidate;
            }
            $candidate = $slug . '-' . $counter;
            $counter++;
        }
    }

    /**
     * Renders a PHP view file inside an optional layout.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $viewPath = dirname(__DIR__) . '/views/' . $view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'Görünüm dosyası bulunamadı.';
            return;
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = dirname(__DIR__) . '/views/' . $layout . '.php';
        if (is_file($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }
}
