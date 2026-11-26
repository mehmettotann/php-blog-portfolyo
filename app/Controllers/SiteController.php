<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Analytics;
use App\Auth;
use App\Database;
use App\Helpers;
use App\LiteSpeed;
use App\Seo;
use PDO;

final class SiteController
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function baseUrl(): string
    {
        return rtrim((string) ($this->config['app']['url'] ?? 'https://example.com'), '/');
    }

    public function home(): void
    {
        LiteSpeed::cachePublic(['site', 'home', 'posts', 'projects']);
        $pdo = Database::pdo();

        $postsStmt = $pdo->query(
            "SELECT p.id, p.title, p.slug, p.excerpt, p.cover_image, p.reading_time, p.published_at,
                    c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT 3"
        );
        $latestPosts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectsStmt = $pdo->query(
            "SELECT pr.id, pr.title, pr.slug, pr.summary, pr.cover_image, pr.technologies, pr.project_year, pr.live_url, pr.source_url,
                    (SELECT COUNT(*) FROM project_media pm WHERE pm.project_id = pr.id AND pm.media_type = 'image') AS image_count,
                    (SELECT COUNT(*) FROM project_media pm WHERE pm.project_id = pr.id AND pm.media_type IN ('video_local','video_external')) AS video_count
             FROM projects pr
             WHERE pr.status = 'published'
             ORDER BY pr.is_featured DESC, pr.sort_order ASC, pr.created_at DESC
             LIMIT 4"
        );
        $featuredProjects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = Helpers::allSettings();
        $socials = Helpers::settingJson('social_links', []);
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $tagline = $settings['site_tagline'] ?? 'Kişisel Blog ve Dijital Portfolyo';
        $base = $this->baseUrl();

        Helpers::render('site/home', [
            'pageTitle' => $siteTitle . ' — ' . $tagline,
            'metaDescription' => $settings['site_description'] ?? $tagline,
            'activeNav' => 'home',
            'trackMeta' => ['type' => 'home', 'id' => 0, 'title' => 'Ana Sayfa'],
            'canonicalUrl' => $base . '/',
            'ogMeta' => Seo::resolveOgImage(null, $settings, $base, $siteTitle . ' — ' . $tagline),
            'jsonLd' => [
                Seo::webSiteSchema($settings, $base),
                Seo::personSchema($settings, $socials, $base),
            ],
            'settings' => $settings,
            'latestPosts' => $latestPosts,
            'featuredProjects' => $featuredProjects,
            'interests' => Helpers::settingJson('about_interests', []),
            'skills' => Helpers::settingJson('about_skills', []),
            'socials' => $socials,
        ]);
    }

    public function about(): void
    {
        LiteSpeed::cachePublic(['site', 'about']);
        $settings = Helpers::allSettings();
        $socials = Helpers::settingJson('social_links', []);
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $base = $this->baseUrl();
        $canonical = $base . '/hakkimda';

        Helpers::render('site/about', [
            'pageTitle' => 'Hakkımda — ' . $siteTitle,
            'metaDescription' => $settings['about_intro'] ?? ($settings['site_description'] ?? ''),
            'activeNav' => 'about',
            'trackMeta' => ['type' => 'page', 'id' => 0, 'title' => 'Hakkımda'],
            'canonicalUrl' => $canonical,
            'ogMeta' => Seo::resolveOgImage(null, $settings, $base, 'Hakkımda — ' . $siteTitle),
            'jsonLd' => [
                Seo::personSchema($settings, $socials, $base),
                Seo::breadcrumbSchema([
                    ['name' => 'Ana Sayfa', 'url' => $base . '/'],
                    ['name' => 'Hakkımda', 'url' => $canonical],
                ]),
            ],
            'settings' => $settings,
            'skills' => Helpers::settingJson('about_skills', []),
            'timeline' => Helpers::settingJson('about_timeline', []),
            'interests' => Helpers::settingJson('about_interests', []),
            'socials' => $socials,
        ]);
    }

    public function blogIndex(): void
    {
        LiteSpeed::cachePublic(['site', 'posts']);
        $pdo = Database::pdo();

        $categorySlug = trim((string) ($_GET['kategori'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['sayfa'] ?? 1));
        $perPage = 6;
        $offset = ($page - 1) * $perPage;

        $where = ["p.status = 'published'"];
        $params = [];

        $activeCategory = null;
        if ($categorySlug !== '') {
            $catStmt = $pdo->prepare('SELECT id, name, slug, description FROM categories WHERE slug = :slug LIMIT 1');
            $catStmt->execute([':slug' => $categorySlug]);
            $activeCategory = $catStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($activeCategory !== null) {
                $where[] = 'p.category_id = :cat_id';
                $params[':cat_id'] = (int) $activeCategory['id'];
            } else {
                $where[] = '1 = 0';
            }
        }

        if ($search !== '') {
            $where[] = '(p.title LIKE :q1 OR p.excerpt LIKE :q2 OR p.content_text LIKE :q3)';
            $like = '%' . $search . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = "SELECT p.id, p.title, p.slug, p.excerpt, p.cover_image, p.reading_time, p.published_at,
                       c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE {$whereSql}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categories = $pdo->query(
            "SELECT c.id, c.name, c.slug, COUNT(p.id) AS post_count
             FROM categories c
             LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published'
             GROUP BY c.id, c.name, c.slug
             ORDER BY c.name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $base = $this->baseUrl();
        $headingTitle = $activeCategory ? ('Yazılar: ' . $activeCategory['name']) : 'Yazılar & Notlar';

        // Dynamic canonical URL for categories & pagination; noindex for internal search queries
        $canonicalParams = [];
        if ($activeCategory !== null) {
            $canonicalParams['kategori'] = (string) $activeCategory['slug'];
        }
        if ($page > 1 && $search === '') {
            $canonicalParams['sayfa'] = $page;
        }
        $canonical = $base . '/yazilar' . ($canonicalParams !== [] ? ('?' . http_build_query($canonicalParams)) : '');
        $metaRobots = ($search !== '' || ($categorySlug !== '' && $activeCategory === null))
            ? 'noindex, follow'
            : 'index, follow';

        $metaDesc = ($activeCategory && !empty($activeCategory['description']))
            ? (string) $activeCategory['description']
            : ('Teknoloji, yazılım, inceleme ve kişisel öğrenme notları — ' . $siteTitle);

        $crumbs = [
            ['name' => 'Ana Sayfa', 'url' => $base . '/'],
            ['name' => 'Yazılar', 'url' => $base . '/yazilar'],
        ];
        if ($activeCategory !== null) {
            $crumbs[] = ['name' => (string) $activeCategory['name'], 'url' => $base . '/yazilar?kategori=' . rawurlencode((string) $activeCategory['slug'])];
        }

        Helpers::render('site/blog_index', [
            'pageTitle' => $headingTitle . ' — ' . $siteTitle,
            'metaDescription' => $metaDesc,
            'metaRobots' => $metaRobots,
            'activeNav' => 'blog',
            'trackMeta' => ['type' => 'page', 'id' => 0, 'title' => $headingTitle],
            'canonicalUrl' => $canonical,
            'ogMeta' => Seo::resolveOgImage(null, $settings, $base, $headingTitle . ' — ' . $siteTitle),
            'jsonLd' => [Seo::breadcrumbSchema($crumbs)],
            'settings' => $settings,
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'categorySlug' => $categorySlug,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    public function blogShow(string $slug): void
    {
        $pdo = Database::pdo();
        $isAdmin = Auth::isLoggedIn();

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.slug = :slug" . ($isAdmin ? '' : " AND p.status = 'published'") . ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($post)) {
            $this->notFound();
            return;
        }

        if ($post['status'] === 'published') {
            LiteSpeed::cachePublic(['site', 'posts', 'post_' . $post['id']]);
        } else {
            LiteSpeed::noCache();
        }

        $blocks = json_decode((string) ($post['content_blocks'] ?? '[]'), true);
        if (!is_array($blocks)) {
            $blocks = [];
        }

        // Related posts (same category or latest excluding current)
        $relStmt = $pdo->prepare(
            "SELECT p.id, p.title, p.slug, p.cover_image, p.reading_time, p.published_at,
                    c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND p.id <> :id
             ORDER BY (p.category_id = :cat_id) DESC, COALESCE(p.published_at, p.created_at) DESC
             LIMIT 3"
        );
        $relStmt->execute([
            ':id' => (int) $post['id'],
            ':cat_id' => (int) ($post['category_id'] ?? 0),
        ]);
        $relatedPosts = $relStmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $base = $this->baseUrl();
        $canonical = $base . '/yazilar/' . rawurlencode((string) $post['slug']);

        // Use post's cover image -> else first image/video poster inside content blocks -> else site default OG image
        $preferredImage = !empty($post['cover_image'])
            ? (string) $post['cover_image']
            : Seo::firstImageFromBlocks($blocks);
        $ogMeta = Seo::resolveOgImage($preferredImage, $settings, $base, (string) $post['title']);

        $crumbs = [
            ['name' => 'Ana Sayfa', 'url' => $base . '/'],
            ['name' => 'Yazılar', 'url' => $base . '/yazilar'],
        ];
        if (!empty($post['category_name']) && !empty($post['category_slug'])) {
            $crumbs[] = [
                'name' => (string) $post['category_name'],
                'url' => $base . '/yazilar?kategori=' . rawurlencode((string) $post['category_slug']),
            ];
        }
        $crumbs[] = ['name' => (string) $post['title'], 'url' => $canonical];

        Helpers::render('site/blog_show', [
            'pageTitle' => (string) $post['title'] . ' — ' . $siteTitle,
            'metaDescription' => (string) ($post['excerpt'] ?: mb_substr((string) $post['content_text'], 0, 160)),
            'metaRobots' => $post['status'] === 'published' ? 'index, follow' : 'noindex, nofollow',
            'ogType' => 'article',
            'ogMeta' => $ogMeta,
            'articleMeta' => [
                'published_time' => Seo::isoDate((string) ($post['published_at'] ?: $post['created_at'])),
                'modified_time' => Seo::isoDate((string) ($post['updated_at'] ?: $post['published_at'])),
                'section' => (string) ($post['category_name'] ?? ''),
            ],
            'jsonLd' => [
                Seo::blogPostingSchema($post, $settings, $base, $canonical, $ogMeta),
                Seo::breadcrumbSchema($crumbs),
            ],
            'activeNav' => 'blog',
            'hasInlineShare' => true,
            'trackMeta' => $post['status'] === 'published'
                ? ['type' => 'post', 'id' => (int) $post['id'], 'title' => (string) $post['title']]
                : null,
            'canonicalUrl' => $canonical,
            'settings' => $settings,
            'post' => $post,
            'blocks' => $blocks,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    public function projectsIndex(): void
    {
        LiteSpeed::cachePublic(['site', 'projects']);
        $pdo = Database::pdo();

        $tag = trim((string) ($_GET['etiket'] ?? ''));
        $page = max(1, (int) ($_GET['sayfa'] ?? 1));
        $perPage = 6;
        $offset = ($page - 1) * $perPage;

        $where = ["pr.status = 'published'"];
        $params = [];
        if ($tag !== '') {
            $where[] = 'pr.technologies LIKE :tag';
            $params[':tag'] = '%' . $tag . '%';
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM projects pr WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = "SELECT pr.*,
                       (SELECT COUNT(*) FROM project_media pm WHERE pm.project_id = pr.id AND pm.media_type = 'image') AS image_count,
                       (SELECT COUNT(*) FROM project_media pm WHERE pm.project_id = pr.id AND pm.media_type IN ('video_local','video_external')) AS video_count
                FROM projects pr
                WHERE {$whereSql}
                ORDER BY pr.is_featured DESC, pr.sort_order ASC, pr.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Collect all unique tags across published projects
        $allTagRows = $pdo->query("SELECT technologies FROM projects WHERE status = 'published'")->fetchAll(PDO::FETCH_COLUMN);
        $allTags = [];
        foreach ($allTagRows as $rowStr) {
            foreach (explode(',', (string) $rowStr) as $t) {
                $clean = trim($t);
                if ($clean !== '') {
                    $allTags[mb_strtolower($clean)] = $clean;
                }
            }
        }
        asort($allTags);

        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $base = $this->baseUrl();
        $canonicalParams = [];
        if ($tag !== '') $canonicalParams['etiket'] = $tag;
        if ($page > 1) $canonicalParams['sayfa'] = $page;
        $canonical = $base . '/projeler' . ($canonicalParams !== [] ? ('?' . http_build_query($canonicalParams)) : '');

        Helpers::render('site/projects_index', [
            'pageTitle' => ($tag !== '' ? "Projeler ({$tag})" : 'Projeler & Çalışmalar') . ' — ' . $siteTitle,
            'metaDescription' => 'Üretilen web uygulamaları, teknik çalışmalar, deneysel projeler ve demo videolar — ' . $siteTitle,
            'activeNav' => 'projects',
            'trackMeta' => ['type' => 'page', 'id' => 0, 'title' => 'Projeler & Çalışmalar'],
            'canonicalUrl' => $canonical,
            'ogMeta' => Seo::resolveOgImage(null, $settings, $base, 'Projeler & Çalışmalar — ' . $siteTitle),
            'jsonLd' => [
                Seo::breadcrumbSchema([
                    ['name' => 'Ana Sayfa', 'url' => $base . '/'],
                    ['name' => 'Projeler', 'url' => $base . '/projeler'],
                ]),
            ],
            'settings' => $settings,
            'projects' => $projects,
            'allTags' => array_values($allTags),
            'activeTag' => $tag,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    public function projectShow(string $slug): void
    {
        $pdo = Database::pdo();
        $isAdmin = Auth::isLoggedIn();

        $sql = 'SELECT * FROM projects WHERE slug = :slug' . ($isAdmin ? '' : " AND status = 'published'") . ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($project)) {
            $this->notFound();
            return;
        }

        if ($project['status'] === 'published') {
            LiteSpeed::cachePublic(['site', 'projects', 'project_' . $project['id']]);
        } else {
            LiteSpeed::noCache();
        }

        $mediaStmt = $pdo->prepare(
            'SELECT * FROM project_media WHERE project_id = :pid ORDER BY sort_order ASC, id ASC'
        );
        $mediaStmt->execute([':pid' => (int) $project['id']]);
        $allMedia = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        $galleryImages = [];
        $videos = [];
        foreach ($allMedia as $m) {
            if ($m['media_type'] === 'image') {
                $galleryImages[] = $m;
            } else {
                $videos[] = $m;
            }
        }

        $blocks = json_decode((string) ($project['description_blocks'] ?? '[]'), true);
        if (!is_array($blocks)) {
            $blocks = [];
        }

        $tags = array_values(array_filter(array_map('trim', explode(',', (string) ($project['technologies'] ?? '')))));

        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $base = $this->baseUrl();
        $canonical = $base . '/projeler/' . rawurlencode((string) $project['slug']);

        // Project share image: cover_image -> first gallery image -> first block image -> default OG image
        $preferredImage = !empty($project['cover_image'])
            ? (string) $project['cover_image']
            : (!empty($galleryImages[0]['file_path'])
                ? (string) $galleryImages[0]['file_path']
                : Seo::firstImageFromBlocks($blocks));
        $ogMeta = Seo::resolveOgImage($preferredImage, $settings, $base, (string) $project['title']);

        Helpers::render('site/project_show', [
            'pageTitle' => (string) $project['title'] . ' — Projeler — ' . $siteTitle,
            'metaDescription' => (string) ($project['summary'] ?? ''),
            'metaRobots' => $project['status'] === 'published' ? 'index, follow' : 'noindex, nofollow',
            'ogMeta' => $ogMeta,
            'jsonLd' => [
                Seo::projectSchema($project, $settings, $base, $canonical, $ogMeta),
                Seo::breadcrumbSchema([
                    ['name' => 'Ana Sayfa', 'url' => $base . '/'],
                    ['name' => 'Projeler', 'url' => $base . '/projeler'],
                    ['name' => (string) $project['title'], 'url' => $canonical],
                ]),
            ],
            'activeNav' => 'projects',
            'hasInlineShare' => true,
            'trackMeta' => $project['status'] === 'published'
                ? ['type' => 'project', 'id' => (int) $project['id'], 'title' => (string) $project['title']]
                : null,
            'canonicalUrl' => $canonical,
            'settings' => $settings,
            'project' => $project,
            'blocks' => $blocks,
            'tags' => $tags,
            'galleryImages' => $galleryImages,
            'videos' => $videos,
        ]);
    }

    public function contact(): void
    {
        Auth::startSession();
        LiteSpeed::noCache();

        $a = random_int(2, 9);
        $b = random_int(1, 8);
        $_SESSION['contact_captcha_answer'] = $a + $b;
        $_SESSION['contact_form_loaded_at'] = time();

        $old = $_SESSION['contact_old'] ?? [];
        unset($_SESSION['contact_old']);

        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $base = $this->baseUrl();
        $canonical = $base . '/iletisim';

        Helpers::render('site/contact', [
            'pageTitle' => 'İletişim — ' . $siteTitle,
            'metaDescription' => 'Adınız Soyadınız ile iletişime geçin, sosyal medya hesapları ve mesaj formu.',
            'activeNav' => 'contact',
            'trackMeta' => ['type' => 'page', 'id' => 0, 'title' => 'İletişim'],
            'canonicalUrl' => $canonical,
            'ogMeta' => Seo::resolveOgImage(null, $settings, $base, 'İletişim — ' . $siteTitle),
            'jsonLd' => [
                Seo::breadcrumbSchema([
                    ['name' => 'Ana Sayfa', 'url' => $base . '/'],
                    ['name' => 'İletişim', 'url' => $canonical],
                ]),
            ],
            'settings' => $settings,
            'socials' => Helpers::settingJson('social_links', []),
            'captchaQuestion' => "{$a} + {$b} kaç eder?",
            'old' => is_array($old) ? $old : [],
        ]);
    }

    public function contactSubmit(): void
    {
        Auth::startSession();
        LiteSpeed::noCache();

        if (!Helpers::verifyCsrf()) {
            Helpers::flash('error', 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.');
            Helpers::redirect('/iletisim');
        }

        // 1. Honeypot check
        $honeypot = trim((string) ($_POST['website'] ?? ''));
        if ($honeypot !== '') {
            Helpers::flash('success', 'Mesajınız alındı. Teşekkürler!');
            Helpers::redirect('/iletisim');
        }

        // 2. Time trap
        $loadedAt = (int) ($_SESSION['contact_form_loaded_at'] ?? 0);
        if ($loadedAt > 0 && (time() - $loadedAt) < 2) {
            Helpers::flash('error', 'Form çok hızlı gönderildi. Lütfen birkaç saniye bekleyip tekrar deneyin.');
            Helpers::redirect('/iletisim');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $captcha = trim((string) ($_POST['captcha'] ?? ''));

        $_SESSION['contact_old'] = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ];

        // 3. Math anti-spam check
        $expectedCaptcha = (int) ($_SESSION['contact_captcha_answer'] ?? -1);
        if ($expectedCaptcha < 0 || !is_numeric($captcha) || (int) $captcha !== $expectedCaptcha) {
            Helpers::flash('error', 'Güvenlik sorusunun yanıtı hatalı. Lütfen tekrar deneyin.');
            Helpers::redirect('/iletisim');
        }

        // 4. Field validation
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            Helpers::flash('error', 'Lütfen geçerli bir ad soyad girin (2–120 karakter).');
            Helpers::redirect('/iletisim');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
            Helpers::flash('error', 'Lütfen geçerli bir e-posta adresi girin.');
            Helpers::redirect('/iletisim');
        }
        if (mb_strlen($subject) > 180) {
            Helpers::flash('error', 'Konu alanı en fazla 180 karakter olabilir.');
            Helpers::redirect('/iletisim');
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
            Helpers::flash('error', 'Mesajınız en az 10, en fazla 5000 karakter olmalıdır.');
            Helpers::redirect('/iletisim');
        }

        // 5. Simple IP rate limit: max 3 messages in 10 minutes
        $pdo = Database::pdo();
        $ip = Helpers::clientIp();
        $rateStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM contact_messages WHERE ip_address = :ip AND created_at >= (NOW() - INTERVAL 10 MINUTE)'
        );
        $rateStmt->execute([':ip' => $ip]);
        if ((int) $rateStmt->fetchColumn() >= 3) {
            Helpers::flash('error', 'Kısa süre içinde çok fazla mesaj gönderdiniz. Lütfen biraz sonra tekrar deneyin.');
            Helpers::redirect('/iletisim');
        }

        $insert = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, subject, message, ip_address, is_read, created_at)
             VALUES (:name, :email, :subject, :message, :ip, 0, NOW())'
        );
        $insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':subject' => $subject !== '' ? $subject : 'İletişim Formu Mesajı',
            ':message' => $message,
            ':ip' => $ip,
        ]);

        unset($_SESSION['contact_old'], $_SESSION['contact_captcha_answer']);
        Helpers::flash('success', 'Mesajınız başarıyla gönderildi. En kısa sürede dönüş yapacağım.');
        Helpers::redirect('/iletisim');
    }

    public function sitemap(): void
    {
        LiteSpeed::cachePublic(['site', 'sitemap'], 7200);
        header('Content-Type: application/xml; charset=UTF-8');

        $base = $this->baseUrl();
        $pdo = Database::pdo();

        $urls = [
            ['loc' => $base . '/', 'priority' => '1.0', 'lastmod' => date('Y-m-d')],
            ['loc' => $base . '/hakkimda', 'priority' => '0.8', 'lastmod' => date('Y-m-d')],
            ['loc' => $base . '/yazilar', 'priority' => '0.9', 'lastmod' => date('Y-m-d')],
            ['loc' => $base . '/projeler', 'priority' => '0.9', 'lastmod' => date('Y-m-d')],
            ['loc' => $base . '/iletisim', 'priority' => '0.7', 'lastmod' => date('Y-m-d')],
        ];

        $categories = $pdo->query(
            "SELECT DISTINCT c.slug FROM categories c
             INNER JOIN posts p ON p.category_id = c.id AND p.status = 'published'
             ORDER BY c.name ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($categories as $catSlug) {
            $urls[] = [
                'loc' => $base . '/yazilar?kategori=' . rawurlencode((string) $catSlug),
                'priority' => '0.7',
                'lastmod' => date('Y-m-d'),
            ];
        }

        $posts = $pdo->query("SELECT slug, updated_at, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($posts as $p) {
            $dt = (string) ($p['updated_at'] ?: $p['published_at'] ?: date('Y-m-d'));
            $urls[] = [
                'loc' => $base . '/yazilar/' . rawurlencode((string) $p['slug']),
                'priority' => '0.8',
                'lastmod' => substr($dt, 0, 10),
            ];
        }

        $projects = $pdo->query("SELECT slug, updated_at, created_at FROM projects WHERE status = 'published' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($projects as $pr) {
            $dt = (string) ($pr['updated_at'] ?: $pr['created_at'] ?: date('Y-m-d'));
            $urls[] = [
                'loc' => $base . '/projeler/' . rawurlencode((string) $pr['slug']),
                'priority' => '0.8',
                'lastmod' => substr($dt, 0, 10),
            ];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo "  <url>\n";
            echo '    <loc>' . Helpers::e($u['loc']) . "</loc>\n";
            echo '    <lastmod>' . Helpers::e($u['lastmod']) . "</lastmod>\n";
            echo '    <priority>' . Helpers::e($u['priority']) . "</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }

    public function rss(): void
    {
        LiteSpeed::cachePublic(['site', 'posts', 'rss'], 3600);
        header('Content-Type: application/rss+xml; charset=UTF-8');

        $base = $this->baseUrl();
        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $siteDesc = $settings['site_description'] ?? ($settings['site_tagline'] ?? 'Kişisel Blog');

        $posts = Database::pdo()->query(
            "SELECT p.title, p.slug, p.excerpt, p.cover_image, p.published_at, p.created_at,
                    c.name AS category_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT 20"
        )->fetchAll(PDO::FETCH_ASSOC);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo "  <channel>\n";
        echo '    <title>' . Helpers::e($siteTitle) . "</title>\n";
        echo '    <link>' . Helpers::e($base . '/') . "</link>\n";
        echo '    <description>' . Helpers::e($siteDesc) . "</description>\n";
        echo "    <language>tr-TR</language>\n";
        echo '    <atom:link href="' . Helpers::e($base . '/rss.xml') . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($posts as $p) {
            $link = $base . '/yazilar/' . rawurlencode((string) $p['slug']);
            $pubRaw = (string) ($p['published_at'] ?: $p['created_at']);
            $pubRfc = date(DATE_RSS);
            if ($pubRaw !== '') {
                try {
                    $pubRfc = (new \DateTimeImmutable($pubRaw))->format(DATE_RSS);
                } catch (\Throwable) {
                }
            }
            $og = Seo::resolveOgImage(!empty($p['cover_image']) ? (string) $p['cover_image'] : null, $settings, $base);

            echo "    <item>\n";
            echo '      <title>' . Helpers::e((string) $p['title']) . "</title>\n";
            echo '      <link>' . Helpers::e($link) . "</link>\n";
            echo '      <guid isPermaLink="true">' . Helpers::e($link) . "</guid>\n";
            echo '      <pubDate>' . Helpers::e($pubRfc) . "</pubDate>\n";
            if (!empty($p['category_name'])) {
                echo '      <category>' . Helpers::e((string) $p['category_name']) . "</category>\n";
            }
            echo '      <description>' . Helpers::e((string) ($p['excerpt'] ?? '')) . "</description>\n";
            echo '      <enclosure url="' . Helpers::e($og['url']) . '" length="0" type="' . Helpers::e($og['type']) . '" />' . "\n";
            echo "    </item>\n";
        }

        echo "  </channel>\n";
        echo "</rss>\n";
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        $base = $this->baseUrl();
        echo "User-agent: *\n";
        echo "Disallow: /admin/\n";
        echo "Sitemap: {$base}/sitemap.xml\n";
    }

    /**
     * Dynamic /llms.txt endpoint (llmstxt.org specification) for AI crawlers & assistants.
     */
    public function llmsTxt(): void
    {
        LiteSpeed::cachePublic(['site', 'posts', 'projects', 'llms'], 3600);
        header('Content-Type: text/plain; charset=UTF-8');

        $base = $this->baseUrl();
        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';
        $siteDesc = $settings['site_description'] ?? ($settings['site_tagline'] ?? 'Kişisel Blog ve Dijital Portfolyo');
        $aboutIntro = trim((string) ($settings['about_intro'] ?? ''));
        $skills = Helpers::settingJson('about_skills', []);
        $socials = Helpers::settingJson('social_links', []);

        $pdo = Database::pdo();

        $projects = $pdo->query(
            "SELECT title, slug, summary, technologies, live_url, source_url
             FROM projects
             WHERE status = 'published'
             ORDER BY is_featured DESC, sort_order ASC, created_at DESC
             LIMIT 30"
        )->fetchAll(PDO::FETCH_ASSOC);

        $posts = $pdo->query(
            "SELECT p.title, p.slug, p.excerpt, p.published_at, c.name AS category_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT 30"
        )->fetchAll(PDO::FETCH_ASSOC);

        $lines = [];
        $lines[] = '# ' . $siteTitle;
        $lines[] = '';
        $lines[] = '> ' . $siteDesc;
        $lines[] = '';
        if ($aboutIntro !== '') {
            $lines[] = $aboutIntro;
            $lines[] = '';
        }
        if (!empty($skills)) {
            $lines[] = 'Uzmanlık Alanları: ' . implode(', ', array_map('strval', $skills));
            $lines[] = '';
        }

        $lines[] = '## Ana Sayfalar';
        $lines[] = "- [Ana Sayfa]({$base}/): Kişisel tanıtım, son blog yazıları ve öne çıkan projeler.";
        $lines[] = "- [Hakkımda]({$base}/hakkimda): Biyografi, uzmanlık alanları, eğitim/deneyim ve ilgi alanları.";
        $lines[] = "- [Yazılar / Blog]({$base}/yazilar): Teknoloji, yazılım, inceleme ve öğrenme notları.";
        $lines[] = "- [Projeler]({$base}/projeler): Geliştirilen yazılımlar, açık kaynak çalışmalar, görseller ve demolar.";
        $lines[] = "- [İletişim]({$base}/iletisim): İletişim formu ve sosyal medya bağlantıları.";
        $lines[] = '';

        if (!empty($projects)) {
            $lines[] = '## Projeler';
            foreach ($projects as $pr) {
                $url = $base . '/projeler/' . rawurlencode((string) $pr['slug']);
                $desc = trim((string) ($pr['summary'] ?? ''));
                $tech = trim((string) ($pr['technologies'] ?? ''));
                $extra = [];
                if ($tech !== '') {
                    $extra[] = 'Teknolojiler: ' . $tech;
                }
                if (!empty($pr['source_url'])) {
                    $extra[] = 'Kaynak: ' . $pr['source_url'];
                }
                $suffix = $extra !== [] ? (' (' . implode(' | ', $extra) . ')') : '';
                $lines[] = "- [{$pr['title']}]({$url}): {$desc}{$suffix}";
            }
            $lines[] = '';
        }

        if (!empty($posts)) {
            $lines[] = '## Blog Yazıları';
            foreach ($posts as $p) {
                $url = $base . '/yazilar/' . rawurlencode((string) $p['slug']);
                $cat = !empty($p['category_name']) ? ('[' . $p['category_name'] . '] ') : '';
                $excerpt = trim((string) ($p['excerpt'] ?? ''));
                $lines[] = "- [{$p['title']}]({$url}): {$cat}{$excerpt}";
            }
            $lines[] = '';
        }

        if (!empty($socials)) {
            $lines[] = '## Sosyal Medya & Bağlantılar';
            foreach ($socials as $k => $v) {
                if (is_string($v) && $v !== '') {
                    $lines[] = '- ' . ucfirst((string) $k) . ': ' . $v;
                }
            }
            $lines[] = '';
        }

        $lines[] = '## İndeks ve Beslemeler';
        $lines[] = "- [Site Haritası (XML)]({$base}/sitemap.xml)";
        $lines[] = "- [RSS Beslemesi]({$base}/rss.xml)";

        echo implode("\n", $lines) . "\n";
    }

    public function apiTrackVisit(): void
    {
        LiteSpeed::noCache();
        header('Content-Type: application/json; charset=UTF-8');

        $raw = (string) file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $path = trim((string) ($data['path'] ?? '/'));
        $type = trim((string) ($data['type'] ?? 'page'));
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));

        try {
            Analytics::recordVisit($path, $type, $id, $title);
        } catch (\Throwable) {
            // Silently ignore if analytics table is not yet migrated
        }

        http_response_code(204);
    }

    public function apiGetLike(): void
    {
        LiteSpeed::noCache();
        header('Content-Type: application/json; charset=UTF-8');

        $type = trim((string) ($_GET['type'] ?? ''));
        $id = (int) ($_GET['id'] ?? 0);

        try {
            $res = Analytics::getLikeStatus($type, $id);
            echo json_encode(['ok' => true, 'count' => $res['count'], 'liked' => $res['liked']]);
        } catch (\Throwable) {
            echo json_encode(['ok' => true, 'count' => 0, 'liked' => false]);
        }
    }

    public function apiToggleLike(): void
    {
        LiteSpeed::noCache();
        header('Content-Type: application/json; charset=UTF-8');

        $raw = (string) file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $type = trim((string) ($data['type'] ?? ''));
        $id = (int) ($data['id'] ?? 0);

        if (!in_array($type, ['post', 'project'], true) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz istek.']);
            return;
        }

        try {
            $res = Analytics::toggleLike($type, $id);
            echo json_encode(['ok' => true, 'count' => $res['count'], 'liked' => $res['liked']]);
        } catch (\Throwable) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Beğeni kaydedilemedi.']);
        }
    }

    public function notFound(): void
    {
        http_response_code(404);
        LiteSpeed::noCache();
        $settings = Helpers::allSettings();
        $siteTitle = $settings['site_title'] ?? 'Adınız Soyadınız';

        Helpers::render('site/404', [
            'pageTitle' => 'Sayfa Bulunamadı (404) — ' . $siteTitle,
            'metaDescription' => 'Aradığınız sayfa bulunamadı.',
            'metaRobots' => 'noindex, nofollow',
            'hideShareBar' => true,
            'activeNav' => '',
            'ogMeta' => Seo::resolveOgImage(null, $settings, $this->baseUrl(), $siteTitle),
            'settings' => $settings,
        ]);
    }
}
