<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Helpers;
use App\LiteSpeed;
use App\MediaRenderer;
use App\Uploader;
use PDO;
use Throwable;

final class AdminController
{
    /** @var array<string, mixed> */
    private array $config;
    private Uploader $uploader;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->uploader = new Uploader((array) ($config['uploads'] ?? []));
    }

    public function loginForm(): void
    {
        Auth::startSession();
        LiteSpeed::noCache();

        if (Auth::isLoggedIn()) {
            Helpers::redirect('/admin');
        }

        Helpers::render('admin/login', [
            'pageTitle' => 'Yönetim Girişi — ' . Helpers::setting('site_title', 'Adınız Soyadınız'),
        ], null);
    }

    public function loginSubmit(): void
    {
        Auth::startSession();
        LiteSpeed::noCache();

        if (!Helpers::verifyCsrf()) {
            Helpers::flash('error', 'Oturum doğrulaması geçersiz. Lütfen tekrar deneyin.');
            Helpers::redirect('/admin/giris');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $res = Auth::attempt($username, $password);
        if (!$res['ok']) {
            Helpers::flash('error', (string) ($res['error'] ?? 'Giriş başarısız.'));
            Helpers::redirect('/admin/giris');
        }

        Helpers::flash('success', 'Hoş geldiniz!');
        Helpers::redirect('/admin');
    }

    public function logout(): void
    {
        Auth::startSession();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && Helpers::verifyCsrf()) {
            Auth::logout();
        }
        Helpers::redirect('/admin/giris');
    }

    public function dashboard(): void
    {
        Auth::requireAdmin();
        $pdo = Database::pdo();

        $stats = [
            'posts_published' => (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
            'posts_draft' => (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
            'projects_total' => (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
            'media_total' => (int) $pdo->query('SELECT COUNT(*) FROM media_files')->fetchColumn(),
            'messages_unread' => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(),
            'messages_total' => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
        ];

        $recentPosts = $pdo->query(
            'SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.updated_at DESC
             LIMIT 5'
        )->fetchAll(PDO::FETCH_ASSOC);

        $recentMessages = $pdo->query(
            'SELECT id, name, email, subject, is_read, created_at
             FROM contact_messages
             ORDER BY created_at DESC
             LIMIT 5'
        )->fetchAll(PDO::FETCH_ASSOC);

        $serverInfo = [
            'php_version' => PHP_VERSION,
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size' => (string) ini_get('post_max_size'),
            'memory_limit' => (string) ini_get('memory_limit'),
            'litespeed_cache' => !empty($this->config['litespeed']['enabled']) ? 'Aktif' : 'Kapalı (Lokal)',
        ];

        $this->renderAdmin('admin/dashboard', [
            'pageTitle' => 'Genel Bakış',
            'activeAdminNav' => 'dashboard',
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'recentMessages' => $recentMessages,
            'serverInfo' => $serverInfo,
        ]);
    }

    // =========================================================================
    // BLOG POSTS
    // =========================================================================

    public function postsIndex(): void
    {
        Auth::requireAdmin();
        $pdo = Database::pdo();

        $posts = $pdo->query(
            'SELECT p.id, p.title, p.slug, p.status, p.reading_time, p.published_at, p.updated_at,
                    c.name AS category_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.updated_at DESC, p.id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/posts_index', [
            'pageTitle' => 'Blog Yazıları',
            'activeAdminNav' => 'posts',
            'posts' => $posts,
        ]);
    }

    public function postCreate(): void
    {
        Auth::requireAdmin();
        $categories = Database::pdo()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/post_form', [
            'pageTitle' => 'Yeni Blog Yazısı',
            'activeAdminNav' => 'posts',
            'post' => [
                'id' => 0,
                'title' => '',
                'slug' => '',
                'category_id' => '',
                'excerpt' => '',
                'cover_image' => '',
                'status' => 'draft',
                'published_at' => date('Y-m-d\TH:i'),
                'content_blocks' => json_encode([
                    ['type' => 'paragraph', 'text' => ''],
                ], JSON_UNESCAPED_UNICODE),
            ],
            'editorHtml' => '<p><br></p>',
            'categories' => $categories,
            'maxImageMb' => (int) ($this->config['uploads']['max_image_mb'] ?? 10),
            'maxVideoMb' => (int) ($this->config['uploads']['max_video_mb'] ?? 150),
        ]);
    }

    public function postEdit(int $id): void
    {
        Auth::requireAdmin();
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($post)) {
            Helpers::flash('error', 'Düzenlenecek yazı bulunamadı.');
            Helpers::redirect('/admin/yazilar');
        }

        $categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        $decodedBlocks = json_decode((string) ($post['content_blocks'] ?? '[]'), true);
        $editorHtml = MediaRenderer::renderForEditor(is_array($decodedBlocks) ? $decodedBlocks : []);

        $this->renderAdmin('admin/post_form', [
            'pageTitle' => 'Yazıyı Düzenle',
            'activeAdminNav' => 'posts',
            'post' => $post,
            'editorHtml' => $editorHtml,
            'categories' => $categories,
            'maxImageMb' => (int) ($this->config['uploads']['max_image_mb'] ?? 10),
            'maxVideoMb' => (int) ($this->config['uploads']['max_video_mb'] ?? 150),
        ]);
    }

    public function postSave(): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/yazilar');

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $rawSlug = trim((string) ($_POST['slug'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $coverImage = trim((string) ($_POST['cover_image'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'draft');
        $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';
        $publishedAtInput = trim((string) ($_POST['published_at'] ?? ''));
        $blocksRaw = (string) ($_POST['content_blocks_json'] ?? '[]');

        if ($title === '') {
            Helpers::flash('error', 'Yazı başlığı boş bırakılamaz.');
            Helpers::redirect($id > 0 ? "/admin/yazilar/{$id}/duzenle" : '/admin/yazilar/yeni');
        }

        // Optional direct cover file upload
        if (!empty($_FILES['cover_image_file']['name'])) {
            try {
                $uploaded = $this->uploader->upload($_FILES['cover_image_file'], 'image');
                $coverImage = $uploaded['path'];
            } catch (Throwable $e) {
                Helpers::flash('error', 'Kapak görseli yüklenemedi: ' . $e->getMessage());
                Helpers::redirect($id > 0 ? "/admin/yazilar/{$id}/duzenle" : '/admin/yazilar/yeni');
            }
        }

        if (isset($_POST['content_html'])) {
            $cleanHtml = MediaRenderer::sanitizeHtml((string) $_POST['content_html']);
            $blocks = $cleanHtml !== '' ? [['type' => 'html', 'html' => $cleanHtml]] : [];
        } else {
            $blocks = $this->sanitizeBlocks($blocksRaw);
        }
        $plainText = MediaRenderer::extractPlainText($blocks);
        $readingTime = Helpers::estimateReadingTime($title . ' ' . $excerpt . ' ' . $plainText);
        $slug = Helpers::uniqueSlug('posts', $rawSlug !== '' ? $rawSlug : $title, $id > 0 ? $id : null);

        $publishedAt = null;
        if ($publishedAtInput !== '') {
            $publishedAt = str_replace('T', ' ', $publishedAtInput);
            if (strlen($publishedAt) === 16) {
                $publishedAt .= ':00';
            }
        } elseif ($status === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $blocksJson = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        $pdo = Database::pdo();

        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE posts
                 SET category_id = :cat, title = :title, slug = :slug, excerpt = :excerpt,
                     content_blocks = :blocks, content_text = :text, cover_image = :cover,
                     reading_time = :rt, status = :status, published_at = :pub, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':cat' => $categoryId > 0 ? $categoryId : null,
                ':title' => $title,
                ':slug' => $slug,
                ':excerpt' => $excerpt,
                ':blocks' => $blocksJson,
                ':text' => $plainText,
                ':cover' => $coverImage !== '' ? $coverImage : null,
                ':rt' => $readingTime,
                ':status' => $status,
                ':pub' => $publishedAt,
                ':id' => $id,
            ]);
            LiteSpeed::purge(['home', 'posts', 'post_' . $id, 'sitemap', 'rss']);
            Helpers::flash('success', 'Blog yazısı güncellendi.');
            Helpers::redirect("/admin/yazilar/{$id}/duzenle");
        }

        $stmt = $pdo->prepare(
            'INSERT INTO posts (category_id, title, slug, excerpt, content_blocks, content_text, cover_image, reading_time, status, published_at, created_at, updated_at)
             VALUES (:cat, :title, :slug, :excerpt, :blocks, :text, :cover, :rt, :status, :pub, NOW(), NOW())'
        );
        $stmt->execute([
            ':cat' => $categoryId > 0 ? $categoryId : null,
            ':title' => $title,
            ':slug' => $slug,
            ':excerpt' => $excerpt,
            ':blocks' => $blocksJson,
            ':text' => $plainText,
            ':cover' => $coverImage !== '' ? $coverImage : null,
            ':rt' => $readingTime,
            ':status' => $status,
            ':pub' => $publishedAt,
        ]);
        $newId = (int) $pdo->lastInsertId();
        LiteSpeed::purge(['home', 'posts', 'sitemap', 'rss']);
        Helpers::flash('success', 'Yeni blog yazısı oluşturuldu.');
        Helpers::redirect("/admin/yazilar/{$newId}/duzenle");
    }

    public function postDelete(int $id): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/yazilar');

        $stmt = Database::pdo()->prepare('DELETE FROM posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        LiteSpeed::purge(['home', 'posts', 'post_' . $id, 'sitemap']);
        Helpers::flash('success', 'Blog yazısı silindi.');
        Helpers::redirect('/admin/yazilar');
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    public function categoriesIndex(): void
    {
        Auth::requireAdmin();
        $categories = Database::pdo()->query(
            'SELECT c.*, COUNT(p.id) AS post_count
             FROM categories c
             LEFT JOIN posts p ON p.category_id = c.id
             GROUP BY c.id
             ORDER BY c.name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/categories', [
            'pageTitle' => 'Kategoriler',
            'activeAdminNav' => 'categories',
            'categories' => $categories,
        ]);
    }

    public function categorySave(): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/kategoriler');

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $rawSlug = trim((string) ($_POST['slug'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($name === '') {
            Helpers::flash('error', 'Kategori adı zorunludur.');
            Helpers::redirect('/admin/kategoriler');
        }

        $slug = Helpers::uniqueSlug('categories', $rawSlug !== '' ? $rawSlug : $name, $id > 0 ? $id : null);
        $pdo = Database::pdo();

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE categories SET name = :n, slug = :s, description = :d WHERE id = :id');
            $stmt->execute([':n' => $name, ':s' => $slug, ':d' => $description, ':id' => $id]);
            Helpers::flash('success', 'Kategori güncellendi.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, created_at) VALUES (:n, :s, :d, NOW())');
            $stmt->execute([':n' => $name, ':s' => $slug, ':d' => $description]);
            Helpers::flash('success', 'Yeni kategori eklendi.');
        }

        LiteSpeed::purge(['posts']);
        Helpers::redirect('/admin/kategoriler');
    }

    public function categoryDelete(int $id): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/kategoriler');

        $stmt = Database::pdo()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        LiteSpeed::purge(['posts']);
        Helpers::flash('success', 'Kategori silindi (ilişkili yazılar korundu).');
        Helpers::redirect('/admin/kategoriler');
    }

    // =========================================================================
    // PROJECTS & PROJECT MEDIA (Multi-Image + Multi-Video Local & External)
    // =========================================================================

    public function projectsIndex(): void
    {
        Auth::requireAdmin();
        $projects = Database::pdo()->query(
            "SELECT pr.*,
                    (SELECT COUNT(*) FROM project_media pm WHERE pm.project_id = pr.id AND pm.media_type = 'image') AS image_count,
                    (SELECT COUNT(*) FROM project_media pm WHERE pm.project_id = pr.id AND pm.media_type IN ('video_local','video_external')) AS video_count
             FROM projects pr
             ORDER BY pr.is_featured DESC, pr.sort_order ASC, pr.updated_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/projects_index', [
            'pageTitle' => 'Projeler & Çalışmalar',
            'activeAdminNav' => 'projects',
            'projects' => $projects,
        ]);
    }

    public function projectCreate(): void
    {
        Auth::requireAdmin();
        $this->renderAdmin('admin/project_form', [
            'pageTitle' => 'Yeni Proje Ekle',
            'activeAdminNav' => 'projects',
            'project' => [
                'id' => 0,
                'title' => '',
                'slug' => '',
                'summary' => '',
                'cover_image' => '',
                'technologies' => '',
                'live_url' => '',
                'source_url' => '',
                'project_year' => date('Y'),
                'status' => 'published',
                'is_featured' => 0,
                'sort_order' => 0,
                'description_blocks' => json_encode([
                    ['type' => 'paragraph', 'text' => ''],
                ], JSON_UNESCAPED_UNICODE),
            ],
            'editorHtml' => '<p><br></p>',
            'mediaItems' => [],
            'maxImageMb' => (int) ($this->config['uploads']['max_image_mb'] ?? 10),
            'maxVideoMb' => (int) ($this->config['uploads']['max_video_mb'] ?? 150),
        ]);
    }

    public function projectEdit(int $id): void
    {
        Auth::requireAdmin();
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($project)) {
            Helpers::flash('error', 'Proje bulunamadı.');
            Helpers::redirect('/admin/projeler');
        }

        $mediaStmt = $pdo->prepare('SELECT * FROM project_media WHERE project_id = :pid ORDER BY sort_order ASC, id ASC');
        $mediaStmt->execute([':pid' => $id]);
        $mediaItems = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        $decodedBlocks = json_decode((string) ($project['description_blocks'] ?? '[]'), true);
        $editorHtml = MediaRenderer::renderForEditor(is_array($decodedBlocks) ? $decodedBlocks : []);

        $this->renderAdmin('admin/project_form', [
            'pageTitle' => 'Projeyi Düzenle',
            'activeAdminNav' => 'projects',
            'project' => $project,
            'editorHtml' => $editorHtml,
            'mediaItems' => $mediaItems,
            'maxImageMb' => (int) ($this->config['uploads']['max_image_mb'] ?? 10),
            'maxVideoMb' => (int) ($this->config['uploads']['max_video_mb'] ?? 150),
        ]);
    }

    public function projectSave(): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/projeler');

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $rawSlug = trim((string) ($_POST['slug'] ?? ''));
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $coverImage = trim((string) ($_POST['cover_image'] ?? ''));
        $technologies = trim((string) ($_POST['technologies'] ?? ''));
        $liveUrl = trim((string) ($_POST['live_url'] ?? ''));
        $sourceUrl = trim((string) ($_POST['source_url'] ?? ''));
        $projectYear = trim((string) ($_POST['project_year'] ?? date('Y')));
        $status = in_array((string) ($_POST['status'] ?? 'published'), ['draft', 'published'], true)
            ? (string) $_POST['status']
            : 'published';
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($title === '') {
            Helpers::flash('error', 'Proje başlığı boş bırakılamaz.');
            Helpers::redirect($id > 0 ? "/admin/projeler/{$id}/duzenle" : '/admin/projeler/yeni');
        }

        if (!empty($_FILES['cover_image_file']['name'])) {
            try {
                $uploaded = $this->uploader->upload($_FILES['cover_image_file'], 'image');
                $coverImage = $uploaded['path'];
            } catch (Throwable $e) {
                Helpers::flash('error', 'Kapak görseli yüklenemedi: ' . $e->getMessage());
                Helpers::redirect($id > 0 ? "/admin/projeler/{$id}/duzenle" : '/admin/projeler/yeni');
            }
        }

        if (isset($_POST['content_html'])) {
            $cleanHtml = MediaRenderer::sanitizeHtml((string) $_POST['content_html']);
            $blocks = $cleanHtml !== '' ? [['type' => 'html', 'html' => $cleanHtml]] : [];
        } else {
            $blocks = $this->sanitizeBlocks((string) ($_POST['content_blocks_json'] ?? '[]'));
        }
        $blocksJson = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        $slug = Helpers::uniqueSlug('projects', $rawSlug !== '' ? $rawSlug : $title, $id > 0 ? $id : null);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE projects
                     SET title = :title, slug = :slug, summary = :summary, description_blocks = :blocks,
                         cover_image = :cover, technologies = :tech, live_url = :live, source_url = :src,
                         project_year = :yr, status = :status, is_featured = :feat, sort_order = :ord, updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':title' => $title,
                    ':slug' => $slug,
                    ':summary' => $summary,
                    ':blocks' => $blocksJson,
                    ':cover' => $coverImage !== '' ? $coverImage : null,
                    ':tech' => $technologies,
                    ':live' => ($liveUrl !== '' && Helpers::isSafeUrl($liveUrl)) ? $liveUrl : null,
                    ':src' => ($sourceUrl !== '' && Helpers::isSafeUrl($sourceUrl)) ? $sourceUrl : null,
                    ':yr' => mb_substr($projectYear, 0, 20),
                    ':status' => $status,
                    ':feat' => $isFeatured,
                    ':ord' => $sortOrder,
                    ':id' => $id,
                ]);
                $projectId = $id;
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO projects (title, slug, summary, description_blocks, cover_image, technologies, live_url, source_url, project_year, status, is_featured, sort_order, created_at, updated_at)
                     VALUES (:title, :slug, :summary, :blocks, :cover, :tech, :live, :src, :yr, :status, :feat, :ord, NOW(), NOW())'
                );
                $stmt->execute([
                    ':title' => $title,
                    ':slug' => $slug,
                    ':summary' => $summary,
                    ':blocks' => $blocksJson,
                    ':cover' => $coverImage !== '' ? $coverImage : null,
                    ':tech' => $technologies,
                    ':live' => ($liveUrl !== '' && Helpers::isSafeUrl($liveUrl)) ? $liveUrl : null,
                    ':src' => ($sourceUrl !== '' && Helpers::isSafeUrl($sourceUrl)) ? $sourceUrl : null,
                    ':yr' => mb_substr($projectYear, 0, 20),
                    ':status' => $status,
                    ':feat' => $isFeatured,
                    ':ord' => $sortOrder,
                ]);
                $projectId = (int) $pdo->lastInsertId();
            }

            // Replace project media collection cleanly from structured JSON list (supports sorting, adding, removing)
            $mediaRaw = (string) ($_POST['project_media_json'] ?? '[]');
            $decodedMedia = json_decode($mediaRaw, true);
            if (is_array($decodedMedia)) {
                $delStmt = $pdo->prepare('DELETE FROM project_media WHERE project_id = :pid');
                $delStmt->execute([':pid' => $projectId]);

                $insStmt = $pdo->prepare(
                    'INSERT INTO project_media (project_id, media_type, file_path, poster_path, external_url, provider, video_id, title, caption, sort_order, created_at)
                     VALUES (:pid, :type, :fp, :poster, :ext, :prov, :vid, :title, :cap, :ord, NOW())'
                );

                $orderIdx = 0;
                foreach ($decodedMedia as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $mType = (string) ($item['media_type'] ?? '');
                    if (!in_array($mType, ['image', 'video_local', 'video_external'], true)) {
                        continue;
                    }

                    $filePath = trim((string) ($item['file_path'] ?? ''));
                    $posterPath = trim((string) ($item['poster_path'] ?? ''));
                    $extUrl = trim((string) ($item['external_url'] ?? ''));
                    $provider = null;
                    $videoId = null;

                    if ($mType === 'image' || $mType === 'video_local') {
                        if ($filePath === '' || !Helpers::isSafeUrl($filePath)) {
                            continue;
                        }
                    } elseif ($mType === 'video_external') {
                        $parsed = Helpers::parseVideoUrl($extUrl);
                        if ($parsed === null) {
                            continue;
                        }
                        $provider = $parsed['provider'];
                        $videoId = $parsed['video_id'];
                        $extUrl = $parsed['watch_url'];
                    }

                    $insStmt->execute([
                        ':pid' => $projectId,
                        ':type' => $mType,
                        ':fp' => $filePath !== '' ? $filePath : null,
                        ':poster' => ($posterPath !== '' && Helpers::isSafeUrl($posterPath)) ? $posterPath : null,
                        ':ext' => $extUrl !== '' ? $extUrl : null,
                        ':prov' => $provider,
                        ':vid' => $videoId,
                        ':title' => mb_substr(trim((string) ($item['title'] ?? '')), 0, 180) ?: null,
                        ':cap' => mb_substr(trim((string) ($item['caption'] ?? '')), 0, 300) ?: null,
                        ':ord' => $orderIdx++,
                    ]);
                }
            }

            $pdo->commit();
            LiteSpeed::purge(['home', 'projects', 'project_' . $projectId, 'sitemap']);
            Helpers::flash('success', $id > 0 ? 'Proje güncellendi.' : 'Yeni proje oluşturuldu.');
            Helpers::redirect("/admin/projeler/{$projectId}/duzenle");
        } catch (Throwable $e) {
            $pdo->rollBack();
            Helpers::flash('error', 'Proje kaydedilirken hata oluştu: ' . $e->getMessage());
            Helpers::redirect($id > 0 ? "/admin/projeler/{$id}/duzenle" : '/admin/projeler/yeni');
        }
    }

    public function projectDelete(int $id): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/projeler');

        $stmt = Database::pdo()->prepare('DELETE FROM projects WHERE id = :id');
        $stmt->execute([':id' => $id]);
        LiteSpeed::purge(['home', 'projects', 'project_' . $id, 'sitemap']);
        Helpers::flash('success', 'Proje silindi.');
        Helpers::redirect('/admin/projeler');
    }

    // =========================================================================
    // MEDIA LIBRARY & AJAX UPLOAD / PICKER API
    // =========================================================================

    public function mediaIndex(): void
    {
        Auth::requireAdmin();
        $filter = (string) ($_GET['tur'] ?? 'all');
        $pdo = Database::pdo();

        if ($filter === 'image' || $filter === 'video') {
            $stmt = $pdo->prepare('SELECT * FROM media_files WHERE file_type = :t ORDER BY created_at DESC, id DESC');
            $stmt->execute([':t' => $filter]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $files = $pdo->query('SELECT * FROM media_files ORDER BY created_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->renderAdmin('admin/media_library', [
            'pageTitle' => 'Medya Kütüphanesi',
            'activeAdminNav' => 'media',
            'files' => $files,
            'filter' => $filter,
            'maxImageMb' => (int) ($this->config['uploads']['max_image_mb'] ?? 10),
            'maxVideoMb' => (int) ($this->config['uploads']['max_video_mb'] ?? 150),
        ]);
    }

    public function mediaUpload(): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/medya');

        $type = ((string) ($_POST['file_type'] ?? 'image')) === 'video' ? 'video' : 'image';
        if (empty($_FILES['media_file']['name'])) {
            Helpers::flash('error', 'Lütfen yüklenecek dosyayı seçin.');
            Helpers::redirect('/admin/medya');
        }

        try {
            $res = $this->uploader->upload($_FILES['media_file'], $type);
            Helpers::flash('success', 'Dosya yüklendi: ' . $res['path']);
        } catch (Throwable $e) {
            Helpers::flash('error', $e->getMessage());
        }

        Helpers::redirect('/admin/medya');
    }

    public function mediaDelete(int $id): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/medya');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, file_path FROM media_files WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (is_array($file)) {
            $this->uploader->deleteFile((string) $file['file_path']);
            $del = $pdo->prepare('DELETE FROM media_files WHERE id = :id');
            $del->execute([':id' => $id]);
            Helpers::flash('success', 'Medya dosyası silindi.');
        }

        Helpers::redirect('/admin/medya');
    }

    public function apiUpload(): void
    {
        Auth::startSession();
        LiteSpeed::noCache();
        header('Content-Type: application/json; charset=UTF-8');

        if (!Auth::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Yetkisiz erişim.']);
            return;
        }

        if (!Helpers::verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF doğrulaması başarısız.']);
            return;
        }

        $type = ((string) ($_POST['type'] ?? 'image')) === 'video' ? 'video' : 'image';
        if (empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Dosya gönderilmedi veya sunucu post_max_size sınırını aştı.']);
            return;
        }

        try {
            $result = $this->uploader->upload($_FILES['file'], $type);
            echo json_encode(['ok' => true, 'file' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function apiMediaList(): void
    {
        Auth::startSession();
        LiteSpeed::noCache();
        header('Content-Type: application/json; charset=UTF-8');

        if (!Auth::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Yetkisiz erişim.']);
            return;
        }

        $type = (string) ($_GET['type'] ?? 'all');
        $pdo = Database::pdo();
        if ($type === 'image' || $type === 'video') {
            $stmt = $pdo->prepare('SELECT id, file_type, file_path, original_name, mime_type, file_size, created_at FROM media_files WHERE file_type = :t ORDER BY created_at DESC, id DESC LIMIT 100');
            $stmt->execute([':t' => $type]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $items = $pdo->query('SELECT id, file_type, file_path, original_name, mime_type, file_size, created_at FROM media_files ORDER BY created_at DESC, id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($items as &$item) {
            $item['size_label'] = Helpers::formatBytes((int) ($item['file_size'] ?? 0));
        }

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // =========================================================================
    // SITE & ABOUT SETTINGS
    // =========================================================================

    public function settingsForm(): void
    {
        Auth::requireAdmin();
        $this->renderAdmin('admin/settings', [
            'pageTitle' => 'Site & Hakkımda Ayarları',
            'activeAdminNav' => 'settings',
            'settings' => Helpers::allSettings(true),
            'skills' => Helpers::settingJson('about_skills', []),
            'timeline' => Helpers::settingJson('about_timeline', []),
            'interests' => Helpers::settingJson('about_interests', []),
            'socials' => Helpers::settingJson('social_links', []),
            'currentUser' => Auth::user(),
        ]);
    }

    public function settingsSave(): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/ayarlar');

        $simpleFields = [
            'site_title',
            'site_tagline',
            'site_description',
            'default_og_image',
            'hero_badge',
            'hero_title',
            'hero_subtitle',
            'about_intro',
            'about_story',
            'contact_email',
            'contact_location',
            'footer_text',
        ];
        foreach ($simpleFields as $field) {
            if (isset($_POST[$field])) {
                Helpers::saveSetting($field, trim((string) $_POST[$field]));
            }
        }

        // Skills (one per line)
        $skillsRaw = (string) ($_POST['about_skills_text'] ?? '');
        $skills = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $skillsRaw) ?: [])));
        Helpers::saveSetting('about_skills', json_encode($skills, JSON_UNESCAPED_UNICODE) ?: '[]');

        // Interests (comma or newline separated)
        $interestsRaw = (string) ($_POST['about_interests_text'] ?? '');
        $interests = array_values(array_filter(array_map('trim', preg_split('/[,\r\n]+/', $interestsRaw) ?: [])));
        Helpers::saveSetting('about_interests', json_encode($interests, JSON_UNESCAPED_UNICODE) ?: '[]');

        // Timeline items (parallel arrays: period, role, place, detail)
        $periods = (array) ($_POST['timeline_period'] ?? []);
        $roles = (array) ($_POST['timeline_role'] ?? []);
        $places = (array) ($_POST['timeline_place'] ?? []);
        $details = (array) ($_POST['timeline_detail'] ?? []);
        $timeline = [];
        foreach ($periods as $i => $p) {
            $pStr = trim((string) $p);
            $rStr = trim((string) ($roles[$i] ?? ''));
            $plStr = trim((string) ($places[$i] ?? ''));
            $dStr = trim((string) ($details[$i] ?? ''));
            if ($pStr === '' && $rStr === '') {
                continue;
            }
            $timeline[] = [
                'period' => $pStr,
                'role' => $rStr,
                'place' => $plStr,
                'detail' => $dStr,
            ];
        }
        Helpers::saveSetting('about_timeline', json_encode($timeline, JSON_UNESCAPED_UNICODE) ?: '[]');

        // Social links
        $socialKeys = ['github', 'linkedin', 'x', 'instagram', 'youtube'];
        $socials = [];
        foreach ($socialKeys as $sk) {
            $val = trim((string) ($_POST['social_' . $sk] ?? ''));
            if ($val !== '' && Helpers::isSafeUrl($val)) {
                $socials[$sk] = $val;
            }
        }
        Helpers::saveSetting('social_links', json_encode($socials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

        // Optional admin password update
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        if ($newPassword !== '') {
            if (mb_strlen($newPassword) < 8) {
                Helpers::flash('error', 'Yeni şifre en az 8 karakter olmalıdır. Diğer ayarlar kaydedildi.');
                LiteSpeed::purge();
                Helpers::redirect('/admin/ayarlar');
            }
            $user = Auth::user();
            if ($user !== null) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => (int) $user['id']]);
                $hash = (string) $stmt->fetchColumn();
                if (!password_verify($currentPassword, $hash)) {
                    Helpers::flash('error', 'Mevcut şifreniz doğru değil; şifre güncellenmedi. Diğer ayarlar kaydedildi.');
                    LiteSpeed::purge();
                    Helpers::redirect('/admin/ayarlar');
                }
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                $upd->execute([':h' => $newHash, ':id' => (int) $user['id']]);
                Helpers::flash('success', 'Site ayarları ve yönetici şifresi güncellendi.');
                LiteSpeed::purge();
                Helpers::redirect('/admin/ayarlar');
            }
        }

        LiteSpeed::purge();
        Helpers::flash('success', 'Site ve Hakkımda ayarları kaydedildi.');
        Helpers::redirect('/admin/ayarlar');
    }

    // =========================================================================
    // CONTACT MESSAGES
    // =========================================================================

    public function messagesIndex(): void
    {
        Auth::requireAdmin();
        $messages = Database::pdo()->query(
            'SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->renderAdmin('admin/messages', [
            'pageTitle' => 'İletişim Mesajları',
            'activeAdminNav' => 'messages',
            'messages' => $messages,
        ]);
    }

    public function messageToggleRead(int $id): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/mesajlar');

        $stmt = Database::pdo()->prepare('UPDATE contact_messages SET is_read = IF(is_read = 1, 0, 1) WHERE id = :id');
        $stmt->execute([':id' => $id]);
        Helpers::flash('success', 'Mesaj okunma durumu güncellendi.');
        Helpers::redirect('/admin/mesajlar');
    }

    public function messageDelete(int $id): void
    {
        Auth::requireAdmin();
        $this->assertCsrf('/admin/mesajlar');

        $stmt = Database::pdo()->prepare('DELETE FROM contact_messages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        Helpers::flash('success', 'Mesaj silindi.');
        Helpers::redirect('/admin/mesajlar');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * @param array<string, mixed> $data
     */
    private function renderAdmin(string $view, array $data): void
    {
        $unreadCount = (int) Database::pdo()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
        $data['unreadMessagesCount'] = $unreadCount;
        $data['adminUser'] = Auth::user();
        $data['siteTitle'] = Helpers::setting('site_title', 'Adınız Soyadınız');
        Helpers::render($view, $data, 'layouts/admin');
    }

    private function assertCsrf(string $redirectTo): void
    {
        if (!Helpers::verifyCsrf()) {
            Helpers::flash('error', 'Güvenlik doğrulaması (CSRF) başarısız. Lütfen tekrar deneyin.');
            Helpers::redirect($redirectTo);
        }
    }

    /**
     * Validates and normalizes block-based editor JSON.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeBlocks(string $rawJson): array
    {
        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $clean = [];
        foreach ($decoded as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            switch ($type) {
                case 'paragraph':
                    $text = trim((string) ($block['text'] ?? ''));
                    if ($text !== '') {
                        $clean[] = ['type' => 'paragraph', 'text' => $text];
                    }
                    break;

                case 'heading':
                    $text = trim((string) ($block['text'] ?? ''));
                    $level = ((int) ($block['level'] ?? 2)) === 3 ? 3 : 2;
                    if ($text !== '') {
                        $clean[] = ['type' => 'heading', 'level' => $level, 'text' => $text];
                    }
                    break;

                case 'image':
                    $src = trim((string) ($block['src'] ?? ''));
                    if ($src !== '' && Helpers::isSafeUrl($src)) {
                        $clean[] = [
                            'type' => 'image',
                            'src' => $src,
                            'alt' => trim((string) ($block['alt'] ?? '')),
                            'caption' => trim((string) ($block['caption'] ?? '')),
                        ];
                    }
                    break;

                case 'video_local':
                    $src = trim((string) ($block['src'] ?? ''));
                    $poster = trim((string) ($block['poster'] ?? ''));
                    if ($src !== '' && Helpers::isSafeUrl($src)) {
                        $clean[] = [
                            'type' => 'video_local',
                            'src' => $src,
                            'poster' => ($poster !== '' && Helpers::isSafeUrl($poster)) ? $poster : '',
                            'title' => trim((string) ($block['title'] ?? '')),
                            'caption' => trim((string) ($block['caption'] ?? '')),
                        ];
                    }
                    break;

                case 'video_external':
                    $url = trim((string) ($block['url'] ?? ''));
                    $parsed = Helpers::parseVideoUrl($url);
                    if ($parsed !== null) {
                        $clean[] = [
                            'type' => 'video_external',
                            'url' => $parsed['watch_url'],
                            'provider' => $parsed['provider'],
                            'video_id' => $parsed['video_id'],
                            'title' => trim((string) ($block['title'] ?? '')),
                            'caption' => trim((string) ($block['caption'] ?? '')),
                        ];
                    }
                    break;

                case 'quote':
                    $text = trim((string) ($block['text'] ?? ''));
                    if ($text !== '') {
                        $clean[] = [
                            'type' => 'quote',
                            'text' => $text,
                            'cite' => trim((string) ($block['cite'] ?? '')),
                        ];
                    }
                    break;

                case 'code':
                    $code = (string) ($block['code'] ?? '');
                    if (trim($code) !== '') {
                        $clean[] = [
                            'type' => 'code',
                            'language' => mb_substr(trim((string) ($block['language'] ?? '')), 0, 30),
                            'code' => $code,
                        ];
                    }
                    break;

                case 'list':
                    $style = ((string) ($block['style'] ?? 'unordered')) === 'ordered' ? 'ordered' : 'unordered';
                    $items = $block['items'] ?? [];
                    if (is_string($items)) {
                        $items = preg_split('/\r\n|\r|\n/', $items) ?: [];
                    }
                    $cleanItems = [];
                    if (is_array($items)) {
                        foreach ($items as $it) {
                            $s = trim((string) $it);
                            if ($s !== '') {
                                $cleanItems[] = $s;
                            }
                        }
                    }
                    if ($cleanItems !== []) {
                        $clean[] = ['type' => 'list', 'style' => $style, 'items' => $cleanItems];
                    }
                    break;
            }
        }

        return $clean;
    }
}
