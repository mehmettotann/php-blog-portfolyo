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

    public static function isBotUserAgent(?string $ua): bool
    {
        if ($ua === null || trim($ua) === '') {
            return true;
        }
        return (bool) preg_match(
            '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|whatsapp|telegrambot|twitterbot|linkedinbot|preview|curl|wget|python-requests|go-http-client|headless|lighthouse|uptime|pingdom|ahrefs|semrush|mj12bot|dotbot|bytespider|gptbot|claudebot|perplexity|anthropic|openai/i',
            $ua
        );
    }

    /**
     * Records a page hit in `page_visit_visitors`.
     * Same IP on same path on same day increments `hit_count` (single row per day/path/IP).
     */
    public static function recordVisit(string $path, string $pageType, int $targetId = 0, string $title = ''): void
    {
        if (self::isBotUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null)) {
            return;
        }

        // Normalize path cleanly
        $cleanPath = '/' . trim((string) parse_url($path, PHP_URL_PATH), '/');
        if ($cleanPath !== '/' && str_ends_with($cleanPath, '/')) {
            $cleanPath = rtrim($cleanPath, '/');
        }
        if (str_starts_with($cleanPath, '/admin') || str_starts_with($cleanPath, '/api')) {
            return;
        }
        $cleanPath = mb_substr($cleanPath, 0, 160);

        $allowedTypes = ['home', 'page', 'post', 'project'];
        if (!in_array($pageType, $allowedTypes, true)) {
            $pageType = 'page';
        }

        $ipHash = self::visitorIpHash();
        $cleanTitle = mb_substr(trim($title), 0, 200);

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO page_visit_visitors (visit_date, path, page_type, target_id, page_title, ip_hash, hit_count, last_seen_at)
             VALUES (CURDATE(), :path, :type, :tid, :title, :iph, 1, NOW())
             ON DUPLICATE KEY UPDATE
                hit_count = hit_count + 1,
                page_title = IF(VALUES(page_title) <> "", VALUES(page_title), page_title),
                last_seen_at = NOW()'
        );
        $stmt->execute([
            ':path' => $cleanPath,
            ':type' => $pageType,
            ':tid' => max(0, $targetId),
            ':title' => $cleanTitle,
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
        ];
    }
}
