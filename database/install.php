<?php
declare(strict_types=1);

/**
 * Veritabanı Kurulum & Seed Betiği (CLI)
 * Kullanım: php database/install.php
 * Seçenekler:
 *   --no-seed   Örnek verileri yüklemeden yalnızca tabloları ve yöneticiyi oluşturur.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'Bu kurulum betiği güvenlik nedeniyle yalnızca komut satırından (CLI) çalıştırılabilir.';
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/app/Env.php';
require $root . '/app/Database.php';

App\Env::load($root . '/.env');
$config = require $root . '/config/config.php';

$noSeed = in_array('--no-seed', $argv ?? [], true);

echo "=========================================================\n";
echo " example.com — Veritabanı Kurulum Yardımcısı\n";
echo "=========================================================\n";

try {
    $pdo = App\Database::connect($config['db']);
    echo "[OK] MySQL bağlantısı başarılı ({$config['db']['database']} @ {$config['db']['host']}).\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[HATA] MySQL bağlantısı kurulamadı:\n  " . $e->getMessage() . "\n");
    fwrite(STDERR, "Lütfen .env dosyasındaki DB_* bilgilerini ve MySQL servisini kontrol edin.\n");
    exit(1);
}

$executeSqlFile = static function (PDO $pdo, string $filePath): void {
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException("Dosya okunamadı: {$filePath}");
    }

    // Remove single-line SQL comments safely while preserving strings
    $lines = preg_split('/\R/', $sql) ?: [];
    $filtered = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*--/', $line)) {
            continue;
        }
        $filtered[] = $line;
    }
    $cleanSql = implode("\n", $filtered);

    // Split statements by semicolon outside quotes
    $statements = [];
    $buffer = '';
    $inSingle = false;
    $inDouble = false;
    $len = strlen($cleanSql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $cleanSql[$i];
        $prev = $i > 0 ? $cleanSql[$i - 1] : '';
        if ($ch === "'" && !$inDouble && $prev !== '\\') {
            $inSingle = !$inSingle;
        } elseif ($ch === '"' && !$inSingle && $prev !== '\\') {
            $inDouble = !$inDouble;
        }

        if ($ch === ';' && !$inSingle && !$inDouble) {
            $stmt = trim($buffer);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buffer = '';
        } else {
            $buffer .= $ch;
        }
    }
    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
    }
};

try {
    $executeSqlFile($pdo, __DIR__ . '/schema.sql');
    echo "[OK] Veritabanı tabloları (schema.sql) oluşturuldu.\n";

    // Create default admin user from .env if no users exist yet
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $adminUser = (string) App\Env::get('ADMIN_DEFAULT_USER', 'admin');
        $adminEmail = (string) App\Env::get('ADMIN_DEFAULT_EMAIL', 'iletisim@example.com');
        $adminPass = (string) App\Env::get('ADMIN_DEFAULT_PASSWORD', 'DegistirBeni2026!');

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, display_name, email, password_hash, created_at)
             VALUES (:u, :d, :e, :h, NOW())'
        );
        $stmt->execute([
            ':u' => $adminUser,
            ':d' => (string) App\Env::get('APP_NAME', 'Adınız Soyadınız'),
            ':e' => $adminEmail,
            ':h' => password_hash($adminPass, PASSWORD_DEFAULT),
        ]);
        echo "[OK] Yönetici hesabı oluşturuldu -> Kullanıcı: {$adminUser} | Şifre: {$adminPass}\n";
    } else {
        echo "[BILGI] Mevcut yönetici hesabı bulundu, üzerine yazılmadı.\n";
    }

    if (!$noSeed) {
        $executeSqlFile($pdo, __DIR__ . '/seed.sql');
        echo "[OK] Örnek başlangıç verileri (seed.sql) yüklendi.\n";
    } else {
        echo "[BILGI] --no-seed seçildiği için örnek veriler atlandı.\n";
    }

    echo "\nKurulum tamamlandı! Yerel sunucuyu başlatmak için:\n";
    echo "  ./scripts/start-local.sh\n";
    echo "Site:  http://127.0.0.1:8000\n";
    echo "Admin: http://127.0.0.1:8000/admin/giris\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[HATA] Kurulum sırasında hata oluştu:\n  " . $e->getMessage() . "\n");
    exit(1);
}
