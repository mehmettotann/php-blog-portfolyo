<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Analytics
{
    /**
     * Resolves real visitor IP (Cloudflare CF-Connecting-IP aware) and returns a
     * privacy-preserving SHA-256 hash so raw IPs are never stored in analytics tables.
     */
    public static function visitorIpHash(): string
    {
        $ip = self::realVisitorIp();
        $salt = (string) Env::get('APP_URL', 'example.com') . '|' . (string) Env::get('DB_DATABASE', 'blog');
        return hash('sha256', $ip . '|' . $salt);
    }

    public static function realVisitorIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_REAL_IP'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];
        foreach ($candidates as $cand) {
            if (is_string($cand) && filter_var(trim($cand), FILTER_VALIDATE_IP)) {
                return trim($cand);
            }
        }
        return '127.0.0.1';
    }

    /**
     * Detects known AI crawlers, search engine bots, social preview bots, and other crawlers.
     *
     * @return array{name:string,category:'ai'|'search'|'social'|'other'}|null
     */
    public static function detectBot(?string $ua): ?array
    {
        if ($ua === null || trim($ua) === '') {
            return null;
        }

        // Order matters: check more specific AI / assistant agents before generic ones (e.g. Applebot-Extended before Applebot)
        $rules = [
            // AI Crawlers & Assistants
            ['pattern' => '/OAI-SearchBot/i',       'name' => 'OAI-SearchBot (OpenAI)',     'category' => 'ai'],
            ['pattern' => '/ChatGPT-User/i',        'name' => 'ChatGPT-User (OpenAI)',      'category' => 'ai'],
            ['pattern' => '/GPTBot/i',              'name' => 'GPTBot (OpenAI)',            'category' => 'ai'],
            ['pattern' => '/Claude-SearchBot/i',    'name' => 'Claude-SearchBot',           'category' => 'ai'],
            ['pattern' => '/Claude-User/i',         'name' => 'Claude-User (Anthropic)',    'category' => 'ai'],
            ['pattern' => '/ClaudeBot|anthropic/i', 'name' => 'ClaudeBot (Anthropic)',      'category' => 'ai'],
            ['pattern' => '/Perplexity-User/i',     'name' => 'Perplexity-User',            'category' => 'ai'],
            ['pattern' => '/PerplexityBot/i',       'name' => 'PerplexityBot',              'category' => 'ai'],
            ['pattern' => '/Google-CloudVertexBot/i','name' => 'Google-CloudVertexBot',     'category' => 'ai'],
            ['pattern' => '/Google-Extended/i',     'name' => 'Google-Extended (Gemini)',   'category' => 'ai'],
            ['pattern' => '/Applebot-Extended/i',   'name' => 'Applebot-Extended (AI)',     'category' => 'ai'],
            ['pattern' => '/Meta-ExternalAgent/i',  'name' => 'Meta-ExternalAgent (AI)',    'category' => 'ai'],
            ['pattern' => '/Meta-ExternalFetcher/i','name' => 'Meta-ExternalFetcher (AI)',  'category' => 'ai'],
            ['pattern' => '/MistralAI/i',           'name' => 'MistralAI-User',             'category' => 'ai'],
            ['pattern' => '/DuckAssistBot/i',       'name' => 'DuckAssistBot (AI)',         'category' => 'ai'],
            ['pattern' => '/Bytespider|TikTokSpider/i','name' => 'Bytespider (ByteDance)',  'category' => 'ai'],
            ['pattern' => '/CCBot/i',               'name' => 'CCBot (Common Crawl)',       'category' => 'ai'],
            ['pattern' => '/Amazonbot/i',           'name' => 'Amazonbot (AI)',             'category' => 'ai'],
            ['pattern' => '/Manus/i',               'name' => 'Manus Bot (AI)',             'category' => 'ai'],

            // Search Engine Crawlers
            ['pattern' => '/Googlebot|GoogleOther|AdsBot-Google/i', 'name' => 'Googlebot',  'category' => 'search'],
            ['pattern' => '/bingbot|msnbot/i',      'name' => 'Bingbot (Microsoft)',        'category' => 'search'],
            ['pattern' => '/Yandex/i',              'name' => 'YandexBot',                  'category' => 'search'],
            ['pattern' => '/DuckDuckBot/i',         'name' => 'DuckDuckBot',                'category' => 'search'],
            ['pattern' => '/Applebot/i',            'name' => 'Applebot',                   'category' => 'search'],
            ['pattern' => '/Baiduspider/i',         'name' => 'Baiduspider',                'category' => 'search'],

            // Social & Link Preview Bots
            ['pattern' => '/WhatsApp/i',            'name' => 'WhatsApp Preview',           'category' => 'social'],
            ['pattern' => '/TelegramBot/i',         'name' => 'TelegramBot',                'category' => 'social'],
            ['pattern' => '/facebookexternalhit|Facebot/i', 'name' => 'Facebook / Meta',    'category' => 'social'],
            ['pattern' => '/Twitterbot/i',          'name' => 'X (Twitterbot)',             'category' => 'social'],
            ['pattern' => '/LinkedInBot/i',         'name' => 'LinkedInBot',                'category' => 'social'],
            ['pattern' => '/Discordbot|Slackbot/i', 'name' => 'Discord / Slack',            'category' => 'social'],

            // Archivers, SEO & Generic Bots
            ['pattern' => '/archive\.org_bot|ia_archiver/i', 'name' => 'Internet Archive',  'category' => 'other'],
            ['pattern' => '/AhrefsBot/i',           'name' => 'AhrefsBot',                  'category' => 'other'],
            ['pattern' => '/SemrushBot/i',          'name' => 'SemrushBot',                 'category' => 'other'],
            ['pattern' => '/PetalBot/i',            'name' => 'PetalBot (Huawei)',          'category' => 'other'],
            ['pattern' => '/UptimeRobot|Pingdom|StatusCake/i', 'name' => 'Uptime Monitor',  'category' => 'other'],
            ['pattern' => '/bot|crawl|spider|slurp|headless|lighthouse/i', 'name' => 'Other Bot', 'category' => 'other'],
        ];

        foreach ($rules as $rule) {
            if (preg_match($rule['pattern'], $ua)) {
                return ['name' => $rule['name'], 'category' => $rule['category']];
            }
        }

        return null;
    }

    public static function isBotUserAgent(?string $ua): bool
    {
        if ($ua === null || trim($ua) === '') {
            return true;
        }
        if (self::detectBot($ua) !== null) {
            return true;
        }
        return (bool) preg_match('/curl|wget|python-requests|go-http-client|preview/i', $ua);
    }

    /**
     * Records a server-side bot or AI crawler visit into `bot_visits`.
     */
    public static function recordBotVisit(string $uriPath): void
    {
        $detected = self::detectBot($_SERVER['HTTP_USER_AGENT'] ?? null);
        if ($detected === null) {
            return;
        }

        $cleanPath = '/' . trim((string) parse_url($uriPath, PHP_URL_PATH), '/');
        if ($cleanPath !== '/' && str_ends_with($cleanPath, '/')) {
            $cleanPath = rtrim($cleanPath, '/');
        }
        if (str_starts_with($cleanPath, '/admin') || str_starts_with($cleanPath, '/api')) {
            return;
        }
        $cleanPath = mb_substr($cleanPath, 0, 160);

        $botName = substr($detected['name'], 0, 50);
        // Ensure ASCII-safe name for ASCII column
        $botName = preg_replace('/[^\x20-\x7E]/', '', $botName) ?: 'Other Bot';

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO bot_visits (visit_date, bot_name, bot_category, path, hit_count, last_seen_at)
             VALUES (CURDATE(), :name, :cat, :path, 1, NOW())
             ON DUPLICATE KEY UPDATE
                hit_count = hit_count + 1,
                last_seen_at = NOW()'
        );
        $stmt->execute([
            ':name' => $botName,
            ':cat' => $detected['category'],
            ':path' => $cleanPath,
        ]);
    }

    /**
     * Records a page hit in `page_visit_visitors`.
     * Strictly validates routes and published DB targets to prevent analytics table pollution.
     */
    public static function recordVisit(string $path, string $pageType, int $targetId = 0, string $title = ''): void
    {
        if (self::isBotUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null)) {
            return;
        }

        $cleanPath = '/' . trim((string) parse_url($path, PHP_URL_PATH), '/');
        if ($cleanPath !== '/' && str_ends_with($cleanPath, '/')) {
            $cleanPath = rtrim($cleanPath, '/');
        }
        if (str_starts_with($cleanPath, '/admin') || str_starts_with($cleanPath, '/api')) {
            return;
        }

        $pdo = Database::pdo();
        $resolvedPath = null;
        $resolvedType = null;
        $resolvedTargetId = 0;
        $resolvedTitle = '';

        // Whitelist genuine static pages
        $staticRoutes = [
            '/'         => ['type' => 'home', 'title' => 'Ana Sayfa'],
            '/hakkimda' => ['type' => 'page', 'title' => 'Hakkımda'],
            '/yazilar'  => ['type' => 'page', 'title' => 'Yazılar & Notlar'],
            '/projeler' => ['type' => 'page', 'title' => 'Projeler & Çalışmalar'],
            '/iletisim' => ['type' => 'page', 'title' => 'İletişim'],
        ];

        if (isset($staticRoutes[$cleanPath])) {
            $resolvedPath = $cleanPath;
            $resolvedType = $staticRoutes[$cleanPath]['type'];
            $resolvedTitle = $staticRoutes[$cleanPath]['title'];
        } elseif ($pageType === 'post' && $targetId > 0) {
            $stmt = $pdo->prepare("SELECT id, slug, title FROM posts WHERE id = :id AND status = 'published' LIMIT 1");
            $stmt->execute([':id' => $targetId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return; // Reject fake/unpublished post ID
            }
            $resolvedPath = '/yazilar/' . (string) $row['slug'];
            $resolvedType = 'post';
            $resolvedTargetId = (int) $row['id'];
            $resolvedTitle = (string) $row['title'];
        } elseif ($pageType === 'project' && $targetId > 0) {
            $stmt = $pdo->prepare("SELECT id, slug, title FROM projects WHERE id = :id AND status = 'published' LIMIT 1");
            $stmt->execute([':id' => $targetId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return; // Reject fake/unpublished project ID
            }
            $resolvedPath = '/projeler/' . (string) $row['slug'];
            $resolvedType = 'project';
            $resolvedTargetId = (int) $row['id'];
            $resolvedTitle = (string) $row['title'];
        } else {
            return; // Ignore unknown/fabricated paths to prevent DB pollution
        }

        $resolvedPath = mb_substr($resolvedPath, 0, 160);
        $resolvedTitle = mb_substr(trim($resolvedTitle), 0, 200);
        $ipHash = self::visitorIpHash();

        $stmt = $pdo->prepare(
            'INSERT INTO page_visit_visitors (visit_date, path, page_type, target_id, page_title, ip_hash, hit_count, last_seen_at)
             VALUES (CURDATE(), :path, :type, :tid, :title, :iph, 1, NOW())
             ON DUPLICATE KEY UPDATE
                hit_count = hit_count + 1,
                page_title = IF(VALUES(page_title) <> "", VALUES(page_title), page_title),
                last_seen_at = NOW()'
        );
        $stmt->execute([
            ':path' => $resolvedPath,
            ':type' => $resolvedType,
            ':tid' => $resolvedTargetId,
            ':title' => $resolvedTitle,
            ':iph' => $ipHash,
        ]);
    }

    /**
     * Returns like count & whether current visitor IP has liked the given post/project.
     *
     * @return array{count:int,liked:bool}
     */
    public static function getLikeStatus(string $targetType, int $targetId): array
    {
        if (!in_array($targetType, ['post', 'project'], true) || $targetId <= 0) {
            return ['count' => 0, 'liked' => false];
        }

        $pdo = Database::pdo();
        $cntStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM content_likes WHERE target_type = :t AND target_id = :id'
        );
        $cntStmt->execute([':t' => $targetType, ':id' => $targetId]);
        $count = (int) $cntStmt->fetchColumn();

        $chkStmt = $pdo->prepare(
            'SELECT 1 FROM content_likes WHERE target_type = :t AND target_id = :id AND ip_hash = :iph LIMIT 1'
        );
        $chkStmt->execute([
            ':t' => $targetType,
            ':id' => $targetId,
            ':iph' => self::visitorIpHash(),
        ]);
        $liked = $chkStmt->fetchColumn() !== false;

        return ['count' => $count, 'liked' => $liked];
    }

    /**
     * Toggles like for current visitor IP on a post or project.
     *
     * @return array{count:int,liked:bool}
     */
    public static function toggleLike(string $targetType, int $targetId): array
    {
        if (!in_array($targetType, ['post', 'project'], true) || $targetId <= 0) {
            return ['count' => 0, 'liked' => false];
        }

        $pdo = Database::pdo();
        $ipHash = self::visitorIpHash();

        // Ensure target actually exists and is published
        $table = $targetType === 'post' ? 'posts' : 'projects';
        $existsStmt = $pdo->prepare("SELECT id FROM {$table} WHERE id = :id AND status = 'published' LIMIT 1");
        $existsStmt->execute([':id' => $targetId]);
        if ($existsStmt->fetchColumn() === false) {
            return ['count' => 0, 'liked' => false];
        }

        $delStmt = $pdo->prepare(
            'DELETE FROM content_likes WHERE target_type = :t AND target_id = :id AND ip_hash = :iph'
        );
        $delStmt->execute([':t' => $targetType, ':id' => $targetId, ':iph' => $ipHash]);

        if ($delStmt->rowCount() > 0) {
            $liked = false;
        } else {
            $insStmt = $pdo->prepare(
                'INSERT IGNORE INTO content_likes (target_type, target_id, ip_hash, created_at)
                 VALUES (:t, :id, :iph, NOW())'
            );
            $insStmt->execute([':t' => $targetType, ':id' => $targetId, ':iph' => $ipHash]);
            $liked = true;
        }

        $cntStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM content_likes WHERE target_type = :t AND target_id = :id'
        );
        $cntStmt->execute([':t' => $targetType, ':id' => $targetId]);
        $count = (int) $cntStmt->fetchColumn();

        return ['count' => $count, 'liked' => $liked];
    }

    /**
     * Generates full report data for the Admin Analytics dashboard.
     *
     * @return array<string, mixed>
     */
    public static function buildReport(string $period = 'month', string $typeFilter = 'all'): array
    {
        $pdo = Database::pdo();

        $daysMap = [
            'day'   => 0,    // Today only (CURDATE())
            'week'  => 6,    // Last 7 days inclusive
            'month' => 29,   // Last 30 days inclusive
            'year'  => 364,  // Last 365 days inclusive
            'all'   => null, // All time
        ];
        if (!array_key_exists($period, $daysMap)) {
            $period = 'month';
        }
        $daysBack = $daysMap[$period];

        $dateWhere = $daysBack === null
            ? '1 = 1'
            : ('visit_date >= DATE_SUB(CURDATE(), INTERVAL ' . (int) $daysBack . ' DAY)');

        $typeWhere = '1 = 1';
        $params = [];
        if (in_array($typeFilter, ['post', 'project', 'page', 'home'], true)) {
            $typeWhere = 'page_type = :tf';
            $params[':tf'] = $typeFilter;
        }

        // 1. Site-wide summary for selected period (exact unique IPs + total hits)
        $sumStmt = $pdo->prepare(
            "SELECT
                COUNT(DISTINCT ip_hash) AS unique_visitors,
                COALESCE(SUM(hit_count), 0) AS total_hits
             FROM page_visit_visitors
             WHERE {$dateWhere} AND {$typeWhere}"
        );
        $sumStmt->execute($params);
        $summary = $sumStmt->fetch(PDO::FETCH_ASSOC) ?: ['unique_visitors' => 0, 'total_hits' => 0];

        // 2. Quick comparison across ALL 4 periods (Daily / Weekly / Monthly / Yearly)
        $periodsOverview = [];
        foreach (['day' => 0, 'week' => 6, 'month' => 29, 'year' => 364] as $pKey => $d) {
            $q = $pdo->query(
                'SELECT COUNT(DISTINCT ip_hash) AS uniq, COALESCE(SUM(hit_count), 0) AS hits
                 FROM page_visit_visitors
                 WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL ' . $d . ' DAY)'
            )->fetch(PDO::FETCH_ASSOC);
            $periodsOverview[$pKey] = [
                'unique' => (int) ($q['uniq'] ?? 0),
                'hits' => (int) ($q['hits'] ?? 0),
            ];
        }

        // 3. Per-page breakdown (unique IPs per page + total clicks + likes for posts/projects)
        $pagesStmt = $pdo->prepare(
            "SELECT
                v.path,
                MAX(v.page_type) AS page_type,
                MAX(v.target_id) AS target_id,
                MAX(v.page_title) AS page_title,
                COUNT(DISTINCT v.ip_hash) AS unique_visitors,
                COALESCE(SUM(v.hit_count), 0) AS total_hits,
                MAX(v.last_seen_at) AS last_seen_at
             FROM page_visit_visitors v
             WHERE {$dateWhere} AND {$typeWhere}
             GROUP BY v.path
             ORDER BY total_hits DESC, unique_visitors DESC
             LIMIT 100"
        );
        $pagesStmt->execute($params);
        $pages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach like counts cleanly
        $likesMap = [];
        foreach ($pdo->query('SELECT target_type, target_id, COUNT(*) AS cnt FROM content_likes GROUP BY target_type, target_id') as $lr) {
            $likesMap[$lr['target_type'] . ':' . (int) $lr['target_id']] = (int) $lr['cnt'];
        }

        foreach ($pages as &$row) {
            $key = $row['page_type'] . ':' . (int) $row['target_id'];
            $row['likes'] = $likesMap[$key] ?? 0;
        }
        unset($row);

        // 4. Daily trend for chart (last 14 days for day/week/month, or monthly buckets for year/all)
        $trendDays = match ($period) {
            'day'   => 7,
            'week'  => 7,
            'month' => 30,
            default => 30,
        };
        $trendStmt = $pdo->query(
            'SELECT visit_date, COUNT(DISTINCT ip_hash) AS uniq, COALESCE(SUM(hit_count), 0) AS hits
             FROM page_visit_visitors
             WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL ' . ($trendDays - 1) . ' DAY)
             GROUP BY visit_date
             ORDER BY visit_date ASC'
        );
        $trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Top liked posts & projects
        $topLikedPosts = $pdo->query(
            'SELECT p.id, p.title, p.slug, COUNT(l.id) AS like_count
             FROM posts p
             LEFT JOIN content_likes l ON l.target_type = "post" AND l.target_id = p.id
             WHERE p.status = "published"
             GROUP BY p.id, p.title, p.slug
             ORDER BY like_count DESC, p.id DESC
             LIMIT 10'
        )->fetchAll(PDO::FETCH_ASSOC);

        $topLikedProjects = $pdo->query(
            'SELECT pr.id, pr.title, pr.slug, COUNT(l.id) AS like_count
             FROM projects pr
             LEFT JOIN content_likes l ON l.target_type = "project" AND l.target_id = pr.id
             WHERE pr.status = "published"
             GROUP BY pr.id, pr.title, pr.slug
             ORDER BY like_count DESC, pr.id DESC
             LIMIT 10'
        )->fetchAll(PDO::FETCH_ASSOC);

        $totalLikes = (int) $pdo->query('SELECT COUNT(*) FROM content_likes')->fetchColumn();

        // 6. Bot & AI Crawler summary for the same selected period
        $bots = self::buildBotReport($dateWhere);

        return [
            'period' => $period,
            'typeFilter' => $typeFilter,
            'summary' => [
                'unique_visitors' => (int) ($summary['unique_visitors'] ?? 0),
                'total_hits' => (int) ($summary['total_hits'] ?? 0),
                'total_likes' => $totalLikes,
            ],
            'periodsOverview' => $periodsOverview,
            'pages' => $pages,
            'trend' => $trendRows,
            'topLikedPosts' => $topLikedPosts,
            'topLikedProjects' => $topLikedProjects,
            'bots' => $bots,
        ];
    }

    /**
     * Builds categorized AI / Search / Social / Other bot report for the given SQL date filter.
     *
     * @return array<string, mixed>
     */
    private static function buildBotReport(string $dateWhere): array
    {
        $empty = [
            'byCategory' => ['ai' => 0, 'search' => 0, 'social' => 0, 'other' => 0],
            'totalHits' => 0,
            'list' => [],
            'topPaths' => [],
        ];

        try {
            $pdo = Database::pdo();

            $byCat = ['ai' => 0, 'search' => 0, 'social' => 0, 'other' => 0];
            $totalHits = 0;
            foreach ($pdo->query("SELECT bot_category, COALESCE(SUM(hit_count), 0) AS hits FROM bot_visits WHERE {$dateWhere} GROUP BY bot_category") as $r) {
                $cat = (string) $r['bot_category'];
                $h = (int) $r['hits'];
                if (isset($byCat[$cat])) {
                    $byCat[$cat] = $h;
                }
                $totalHits += $h;
            }

            $list = $pdo->query(
                "SELECT
                    bot_name,
                    bot_category,
                    COALESCE(SUM(hit_count), 0) AS total_hits,
                    COUNT(DISTINCT path) AS unique_paths,
                    MAX(last_seen_at) AS last_seen_at
                 FROM bot_visits
                 WHERE {$dateWhere}
                 GROUP BY bot_name, bot_category
                 ORDER BY total_hits DESC, last_seen_at DESC
                 LIMIT 50"
            )->fetchAll(PDO::FETCH_ASSOC);

            $topPaths = $pdo->query(
                "SELECT
                    path,
                    COALESCE(SUM(CASE WHEN bot_category = 'ai' THEN hit_count ELSE 0 END), 0) AS ai_hits,
                    COALESCE(SUM(CASE WHEN bot_category = 'search' THEN hit_count ELSE 0 END), 0) AS search_hits,
                    COALESCE(SUM(CASE WHEN bot_category = 'social' THEN hit_count ELSE 0 END), 0) AS social_hits,
                    COALESCE(SUM(CASE WHEN bot_category = 'other' THEN hit_count ELSE 0 END), 0) AS other_hits,
                    COALESCE(SUM(hit_count), 0) AS total_hits,
                    MAX(last_seen_at) AS last_seen_at
                 FROM bot_visits
                 WHERE {$dateWhere}
                 GROUP BY path
                 ORDER BY total_hits DESC, last_seen_at DESC
                 LIMIT 50"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Per-bot URL breakdown & per-URL bot breakdown in a single query
            $detailRows = $pdo->query(
                "SELECT
                    bot_name,
                    bot_category,
                    path,
                    COALESCE(SUM(hit_count), 0) AS hits,
                    MAX(last_seen_at) AS last_seen_at
                 FROM bot_visits
                 WHERE {$dateWhere}
                 GROUP BY bot_name, bot_category, path
                 ORDER BY hits DESC, last_seen_at DESC
                 LIMIT 2000"
            )->fetchAll(PDO::FETCH_ASSOC);

            $botPathsMap = [];
            $pathBotsMap = [];
            foreach ($detailRows as $dr) {
                $bName = (string) $dr['bot_name'];
                $bCat = (string) $dr['bot_category'];
                $bPath = (string) $dr['path'];
                $bHits = (int) $dr['hits'];
                $bSeen = (string) ($dr['last_seen_at'] ?? '');

                $botKey = $bName . '|' . $bCat;
                if (!isset($botPathsMap[$botKey])) {
                    $botPathsMap[$botKey] = [];
                }
                if (count($botPathsMap[$botKey]) < 50) {
                    $botPathsMap[$botKey][] = [
                        'path' => $bPath,
                        'hits' => $bHits,
                        'last_seen_at' => $bSeen,
                    ];
                }

                if (!isset($pathBotsMap[$bPath])) {
                    $pathBotsMap[$bPath] = [];
                }
                if (count($pathBotsMap[$bPath]) < 25) {
                    $pathBotsMap[$bPath][] = [
                        'bot_name' => $bName,
                        'bot_category' => $bCat,
                        'hits' => $bHits,
                        'last_seen_at' => $bSeen,
                    ];
                }
            }

            foreach ($list as &$botRow) {
                $botKey = (string) $botRow['bot_name'] . '|' . (string) $botRow['bot_category'];
                $botRow['paths'] = $botPathsMap[$botKey] ?? [];
            }
            unset($botRow);

            foreach ($topPaths as &$pathRow) {
                $p = (string) $pathRow['path'];
                $pathRow['bots'] = $pathBotsMap[$p] ?? [];
            }
            unset($pathRow);

            return [
                'byCategory' => $byCat,
                'totalHits' => $totalHits,
                'list' => $list,
                'topPaths' => $topPaths,
            ];
        } catch (\Throwable) {
            return $empty;
        }
    }
}
