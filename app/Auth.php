<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    private const MAX_ATTEMPTS = 6;
    private const WINDOW_MINUTES = 15;
    private const IDLE_TIMEOUT_SECONDS = 7200; // 2 hours idle timeout

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name('bp_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function isLoggedIn(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!isset($_COOKIE['bp_session'])) {
                return false;
            }
            self::startSession();
        }
        if (empty($_SESSION['admin_user_id']) || !is_int($_SESSION['admin_user_id'])) {
            return false;
        }
        $lastActive = (int) ($_SESSION['admin_last_activity'] ?? 0);
        if ($lastActive > 0 && (time() - $lastActive) > self::IDLE_TIMEOUT_SECONDS) {
            self::logout();
            return false;
        }
        $_SESSION['admin_last_activity'] = time();
        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, username, display_name, email FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $_SESSION['admin_user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function requireAdmin(): void
    {
        self::startSession();
        LiteSpeed::noCache();
        if (!self::isLoggedIn()) {
            Helpers::flash('warning', 'Devam etmek için yönetim paneline giriş yapmalısınız.');
            Helpers::redirect('/admin/giris');
        }
    }

    public static function isRateLimited(string $ip): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = :ip AND attempted_at >= (NOW() - INTERVAL :mins MINUTE)'
        );
        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindValue(':mins', self::WINDOW_MINUTES, PDO::PARAM_INT);
        $stmt->execute();
        return ((int) $stmt->fetchColumn()) >= self::MAX_ATTEMPTS;
    }

    public static function recordFailedAttempt(string $ip, string $username): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (ip_address, username, attempted_at) VALUES (:ip, :u, NOW())'
        );
        $stmt->execute([':ip' => $ip, ':u' => mb_substr($username, 0, 80)]);

        // Periodic cleanup of old records
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }

    public static function clearAttempts(string $ip): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
        $stmt->execute([':ip' => $ip]);
    }

    /**
     * @return array{ok:bool,error?:string}
     */
    public static function attempt(string $username, string $password): array
    {
        self::startSession();
        $ip = Helpers::clientIp();

        if (self::isRateLimited($ip)) {
            return [
                'ok' => false,
                'error' => 'Çok fazla hatalı giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.',
            ];
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, username, display_name, email, password_hash FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => trim($username)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
            self::recordFailedAttempt($ip, $username);
            return [
                'ok' => false,
                'error' => 'Kullanıcı adı veya şifre hatalı.',
            ];
        }

        self::clearAttempts($ip);
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int) $user['id'];
        $_SESSION['admin_username'] = (string) $user['username'];
        $_SESSION['admin_display_name'] = (string) $user['display_name'];
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));

        // Signal LiteSpeed Cache to bypass public cache while admin is signed in
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie('bp_admin_logged_in', '1', [
            'expires' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $upd = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $upd->execute([':id' => (int) $user['id']]);

        return ['ok' => true];
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }
        setcookie('bp_admin_logged_in', '', time() - 42000, '/');
        session_destroy();
    }
}
