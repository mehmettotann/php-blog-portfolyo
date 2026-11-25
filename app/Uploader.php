<?php
declare(strict_types=1);

namespace App;

use finfo;
use RuntimeException;

final class Uploader
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $uploadsConfig
     */
    public function __construct(array $uploadsConfig)
    {
        $this->config = $uploadsConfig;
    }

    /**
     * Securely uploads an image or video file to /uploads/images/YYYY/MM or /uploads/videos/YYYY/MM.
     *
     * @param array<string, mixed> $file $_FILES entry
     * @param 'image'|'video' $type
     * @return array{path:string,mime:string,size:int,original_name:string,type:string}
     */
    public function upload(array $file, string $type): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->uploadErrorMessage($error));
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Geçersiz dosya yükleme isteği.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('Yüklenen dosya boş.');
        }

        $maxMb = $type === 'video'
            ? (int) ($this->config['max_video_mb'] ?? 150)
            : (int) ($this->config['max_image_mb'] ?? 10);
        $maxBytes = $maxMb * 1024 * 1024;
        if ($size > $maxBytes) {
            throw new RuntimeException("Dosya boyutu izin verilen üst sınırı ({$maxMb} MB) aşıyor.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) $finfo->file($tmpPath);

        $allowedMap = $type === 'video'
            ? (array) ($this->config['allowed_video_mimes'] ?? ['video/mp4' => 'mp4', 'video/webm' => 'webm'])
            : (array) ($this->config['allowed_image_mimes'] ?? ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif']);

        // Some MP4 files are detected by libmagic as application/octet-stream or video/quicktime; verify ftyp box for mp4
        if ($type === 'video' && !isset($allowedMap[$detectedMime])) {
            $extCheck = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if ($extCheck === 'mp4' && $this->hasMp4Signature($tmpPath)) {
                $detectedMime = 'video/mp4';
            } elseif ($extCheck === 'webm' && $this->hasWebmSignature($tmpPath)) {
                $detectedMime = 'video/webm';
            }
        }

        if (!isset($allowedMap[$detectedMime])) {
            $allowedLabel = $type === 'video' ? 'MP4 veya WebM' : 'JPG, PNG, WebP, GIF veya AVIF';
            throw new RuntimeException("Desteklenmeyen dosya türü ({$detectedMime}). Yalnızca {$allowedLabel} yükleyebilirsiniz.");
        }

        // Extra verification for images: ensure valid image dimensions
        if ($type === 'image' && $detectedMime !== 'image/avif') {
            $imgInfo = @getimagesize($tmpPath);
            if ($imgInfo === false) {
                throw new RuntimeException('Yüklenen dosya geçerli bir görsel değil.');
            }
        }

        $ext = (string) $allowedMap[$detectedMime];
        $subDir = ($type === 'video' ? 'videos' : 'images') . '/' . date('Y/m');
        $targetDir = rtrim((string) $this->config['base_dir'], '/') . '/' . $subDir;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Yükleme klasörü oluşturulamadı.');
        }

        $originalName = (string) ($file['name'] ?? 'dosya.' . $ext);
        $baseSlug = Helpers::slugify(pathinfo($originalName, PATHINFO_FILENAME));
        $baseSlug = substr($baseSlug, 0, 48);
        $filename = $baseSlug . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
        $fullPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $fullPath)) {
            throw new RuntimeException('Dosya sunucuya kaydedilemedi.');
        }

        @chmod($fullPath, 0644);

        $publicPath = rtrim((string) $this->config['base_url'], '/') . '/' . $subDir . '/' . $filename;

        $this->recordMedia($type, $publicPath, $originalName, $detectedMime, $size);

        return [
            'path' => $publicPath,
            'mime' => $detectedMime,
            'size' => $size,
            'original_name' => $originalName,
            'type' => $type,
        ];
    }

    /**
     * Safely deletes a file inside /uploads/ (prevents path traversal).
     */
    public function deleteFile(string $publicPath): bool
    {
        if (!str_starts_with($publicPath, '/uploads/')) {
            return false;
        }
        // Protect bundled sample seed files only if desired? Let user delete if they explicitly remove from library,
        // just ensure strictly inside uploads directory!
        $baseReal = realpath((string) $this->config['base_dir']);
        if ($baseReal === false) {
            return false;
        }
        $relative = ltrim(substr($publicPath, strlen('/uploads')), '/');
        $candidate = realpath($baseReal . '/' . $relative);
        if ($candidate === false || !str_starts_with($candidate, $baseReal . DIRECTORY_SEPARATOR)) {
            return false;
        }
        if (is_file($candidate)) {
            return @unlink($candidate);
        }
        return false;
    }

    private function recordMedia(string $type, string $path, string $originalName, string $mime, int $size): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare(
                'INSERT INTO media_files (file_type, file_path, original_name, mime_type, file_size, created_at)
                 VALUES (:t, :p, :o, :m, :s, NOW())'
            );
            $stmt->execute([
                ':t' => $type,
                ':p' => $path,
                ':o' => mb_substr($originalName, 0, 255),
                ':m' => $mime,
                ':s' => $size,
            ]);
        } catch (\Throwable) {
            // Non-fatal if catalog insert fails
        }
    }

    private function hasMp4Signature(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return false;
        }
        $header = fread($fh, 16);
        fclose($fh);
        return is_string($header) && strlen($header) >= 12 && substr($header, 4, 4) === 'ftyp';
    }

    private function hasWebmSignature(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return false;
        }
        $header = fread($fh, 4);
        fclose($fh);
        return $header === "\x1A\x45\xDF\xA3";
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'Dosya boyutu sunucunun php.ini upload_max_filesize sınırını aşıyor.',
            UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu form limitini aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya yüklemesi yarıda kesildi. Lütfen tekrar deneyin.',
            UPLOAD_ERR_NO_FILE => 'Lütfen bir dosya seçin.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda geçici yükleme klasörü bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı. Klasör yazma izinlerini kontrol edin.',
            UPLOAD_ERR_EXTENSION => 'Bir PHP eklentisi dosya yüklemeyi engelledi.',
            default => 'Dosya yüklenirken beklenmeyen bir hata oluştu.',
        };
    }
}
