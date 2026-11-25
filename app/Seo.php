<?php
declare(strict_types=1);

namespace App;

final class Seo
{
    /**
     * Resolves an Open Graph / WhatsApp / Twitter share image with absolute URL,
     * real pixel dimensions, and MIME type. Always falls back to the site default
     * OG image so every shared link shows a rich image preview.
     *
     * @param array<string, string> $settings
     * @return array{url:string,secure_url:?string,width:int,height:int,type:string,alt:string}
     */
    public static function resolveOgImage(
        ?string $preferredPath,
        array $settings,
        string $appUrl,
        string $alt = ''
    ): array {
        $base = rtrim($appUrl, '/');
        $siteDefault = trim((string) ($settings['default_og_image'] ?? ''));
        $fallback = '/assets/img/og-default.jpg';

        $chosen = trim((string) $preferredPath);
        if ($chosen === '') {
            $chosen = $siteDefault !== '' ? $siteDefault : $fallback;
        }

        // Convert relative path to absolute URL
        if (str_starts_with($chosen, 'http://') || str_starts_with($chosen, 'https://')) {
            $absUrl = $chosen;
            $localFile = null;
        } else {
            $rel = '/' . ltrim($chosen, '/');
            $rootDir = dirname(__DIR__);
            $candidate = $rootDir . $rel;
            if (!is_file($candidate)) {
                $rel = $fallback;
                $candidate = $rootDir . $fallback;
            }
            $absUrl = $base . $rel;
            $localFile = is_file($candidate) ? $candidate : null;
        }

        $width = 1200;
        $height = 630;
        $mime = 'image/jpeg';

        if ($localFile !== null) {
            $info = @getimagesize($localFile);
            if (is_array($info)) {
                if (!empty($info[0]) && !empty($info[1])) {
                    $width = (int) $info[0];
                    $height = (int) $info[1];
                }
                if (!empty($info['mime']) && is_string($info['mime'])) {
                    $mime = $info['mime'];
                }
            }
        } else {
            $ext = strtolower(pathinfo((string) parse_url($absUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                default => 'image/jpeg',
            };
        }

        $secureUrl = str_starts_with($absUrl, 'https://') ? $absUrl : null;

        return [
            'url' => $absUrl,
            'secure_url' => $secureUrl,
            'width' => $width,
            'height' => $height,
            'type' => $mime,
            'alt' => $alt !== '' ? $alt : ($settings['site_title'] ?? 'Adınız Soyadınız'),
        ];
    }

    /**
     * Picks the first usable image from a post's blocks (image src or local video poster)
     * when the post has no explicit cover_image.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public static function firstImageFromBlocks(array $blocks): ?string
    {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (($block['type'] ?? '') === 'html' && !empty($block['html']) && is_string($block['html'])) {
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $block['html'], $m)) {
                    return trim($m[1]);
                }
                if (preg_match('/<video[^>]+poster=["\']([^"\']+)["\']/i', $block['html'], $m)) {
                    return trim($m[1]);
                }
            }
            if (($block['type'] ?? '') === 'image' && !empty($block['src']) && is_string($block['src'])) {
                return trim($block['src']);
            }
            if (($block['type'] ?? '') === 'video_local' && !empty($block['poster']) && is_string($block['poster'])) {
                return trim($block['poster']);
            }
        }
        return null;
    }

    /**
     * Builds WebSite JSON-LD schema with SearchAction.
     *
     * @param array<string, string> $settings
     * @return array<string, mixed>
     */
    public static function webSiteSchema(array $settings, string $appUrl): array
    {
        $base = rtrim($appUrl, '/');
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $settings['site_title'] ?? 'Adınız Soyadınız',
            'alternateName' => 'example.com',
            'url' => $base . '/',
            'description' => $settings['site_description'] ?? ($settings['site_tagline'] ?? ''),
            'inLanguage' => 'tr-TR',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $base . '/yazilar?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Builds Person JSON-LD schema.
     *
     * @param array<string, string> $settings
     * @param array<string, string> $socials
     * @return array<string, mixed>
     */
    public static function personSchema(array $settings, array $socials, string $appUrl): array
    {
        $base = rtrim($appUrl, '/');
        $sameAs = [];
        foreach ($socials as $link) {
            if (is_string($link) && (str_starts_with($link, 'https://') || str_starts_with($link, 'http://'))) {
                $sameAs[] = $link;
            }
        }

        $og = self::resolveOgImage(null, $settings, $appUrl);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $settings['site_title'] ?? 'Adınız Soyadınız',
            'url' => $base . '/',
            'jobTitle' => $settings['site_tagline'] ?? 'Yazılım Geliştirici',
            'description' => $settings['about_intro'] ?? ($settings['site_description'] ?? ''),
            'image' => $og['url'],
        ];

        if ($sameAs !== []) {
            $schema['sameAs'] = array_values(array_unique($sameAs));
        }

        return $schema;
    }

    /**
     * Builds BlogPosting JSON-LD schema for article pages.
     *
     * @param array<string, mixed> $post
     * @param array<string, string> $settings
     * @param array{url:string,width:int,height:int} $ogMeta
     * @return array<string, mixed>
     */
    public static function blogPostingSchema(
        array $post,
        array $settings,
        string $appUrl,
        string $canonicalUrl,
        array $ogMeta
    ): array {
        $base = rtrim($appUrl, '/');
        $authorName = $settings['site_title'] ?? 'Adınız Soyadınız';
        $publishedIso = self::isoDate((string) ($post['published_at'] ?: ($post['created_at'] ?? '')));
        $modifiedIso = self::isoDate((string) ($post['updated_at'] ?: ($post['published_at'] ?? '')));
        $plain = trim((string) ($post['content_text'] ?? ''));
        $words = $plain !== '' ? count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []) : 0;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => (string) ($post['title'] ?? ''),
            'description' => (string) ($post['excerpt'] ?: mb_substr($plain, 0, 160)),
            'url' => $canonicalUrl,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonicalUrl,
            ],
            'image' => [
                '@type' => 'ImageObject',
                'url' => $ogMeta['url'],
                'width' => $ogMeta['width'],
                'height' => $ogMeta['height'],
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $authorName,
                'url' => $base . '/hakkimda',
            ],
            'publisher' => [
                '@type' => 'Person',
                'name' => $authorName,
                'url' => $base . '/',
            ],
            'inLanguage' => 'tr-TR',
        ];

        if ($publishedIso !== '') {
            $schema['datePublished'] = $publishedIso;
        }
        if ($modifiedIso !== '') {
            $schema['dateModified'] = $modifiedIso;
        }
        if (!empty($post['category_name'])) {
            $schema['articleSection'] = (string) $post['category_name'];
        }
        if ($words > 0) {
            $schema['wordCount'] = $words;
        }
        if (!empty($post['reading_time'])) {
            $schema['timeRequired'] = 'PT' . max(1, (int) $post['reading_time']) . 'M';
        }

        return $schema;
    }

    /**
     * Builds CreativeWork JSON-LD schema for project detail pages.
     *
     * @param array<string, mixed> $project
     * @param array<string, string> $settings
     * @param array{url:string,width:int,height:int} $ogMeta
     * @return array<string, mixed>
     */
    public static function projectSchema(
        array $project,
        array $settings,
        string $appUrl,
        string $canonicalUrl,
        array $ogMeta
    ): array {
        $base = rtrim($appUrl, '/');
        $authorName = $settings['site_title'] ?? 'Adınız Soyadınız';
        $tags = array_values(array_filter(array_map('trim', explode(',', (string) ($project['technologies'] ?? '')))));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => (string) ($project['title'] ?? ''),
            'description' => (string) ($project['summary'] ?? ''),
            'url' => $canonicalUrl,
            'image' => $ogMeta['url'],
            'author' => [
                '@type' => 'Person',
                'name' => $authorName,
                'url' => $base . '/hakkimda',
            ],
            'inLanguage' => 'tr-TR',
        ];

        if (!empty($tags)) {
            $schema['keywords'] = implode(', ', $tags);
        }
        if (!empty($project['created_at'])) {
            $schema['dateCreated'] = self::isoDate((string) $project['created_at']);
        }
        if (!empty($project['updated_at'])) {
            $schema['dateModified'] = self::isoDate((string) $project['updated_at']);
        }

        return $schema;
    }

    /**
     * Builds BreadcrumbList JSON-LD schema.
     *
     * @param array<int, array{name:string,url:string}> $items
     * @return array<string, mixed>
     */
    public static function breadcrumbSchema(array $items): array
    {
        $list = [];
        foreach (array_values($items) as $idx => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    public static function isoDate(string $dateString): string
    {
        $trimmed = trim($dateString);
        if ($trimmed === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($trimmed))->format(DATE_ATOM);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Renders one or more JSON-LD schema objects safely as <script type="application/ld+json">.
     *
     * @param array<int, array<string, mixed>> $schemas
     */
    public static function renderJsonLd(array $schemas): string
    {
        $out = '';
        foreach ($schemas as $schema) {
            if (!is_array($schema) || $schema === []) {
                continue;
            }
            $json = json_encode(
                $schema,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
            if ($json !== false) {
                $out .= '<script type="application/ld+json">' . $json . "</script>\n";
            }
        }
        return $out;
    }
}
