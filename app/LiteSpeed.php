<?php
declare(strict_types=1);

namespace App;

final class LiteSpeed
{
    private static bool $enabled = false;
    private static int $ttl = 3600;

    public static function configure(bool $enabled, int $ttl = 3600): void
    {
        self::$enabled = $enabled;
        self::$ttl = max(60, $ttl);
    }

    /**
     * Public cacheable GET response header for LiteSpeed Web Server / LSCache.
     *
     * @param string[] $tags
     */
    public static function cachePublic(array $tags = ['site'], ?int $ttl = null): void
    {
        if (headers_sent()) {
            return;
        }

        if (!self::$enabled || Auth::isLoggedIn() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            header('X-LiteSpeed-Cache-Control: no-cache');
            return;
        }

        $maxAge = $ttl ?? self::$ttl;
        header('X-LiteSpeed-Cache-Control: public, max-age=' . $maxAge);
        if ($tags !== []) {
            $safeTags = array_map(
                static fn(string $t): string => preg_replace('/[^a-zA-Z0-9_-]/', '', $t) ?: 'site',
                $tags
            );
            header('X-LiteSpeed-Tag: ' . implode(',', array_unique($safeTags)));
        }
    }

    public static function noCache(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-LiteSpeed-Cache-Control: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Purge cache tags (or entire site) on LiteSpeed when admin modifies content.
     *
     * @param string[] $tags
     */
    public static function purge(array $tags = []): void
    {
        if (!self::$enabled || headers_sent()) {
            return;
        }
        if ($tags === []) {
            header('X-LiteSpeed-Purge: *');
            return;
        }
        $formatted = array_map(
            static fn(string $t): string => 'tag=' . (preg_replace('/[^a-zA-Z0-9_-]/', '', $t) ?: 'site'),
            $tags
        );
        header('X-LiteSpeed-Purge: ' . implode(',', array_unique($formatted)));
    }
}
