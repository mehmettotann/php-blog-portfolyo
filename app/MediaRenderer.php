<?php
declare(strict_types=1);

namespace App;

final class MediaRenderer
{
    /**
     * Renders an array of content blocks into safe HTML.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public static function renderBlocks(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? 'paragraph');
            $html .= match ($type) {
                'paragraph'      => self::renderParagraph($block),
                'heading'        => self::renderHeading($block),
                'image'          => self::renderImage($block),
                'video_local'    => self::renderLocalVideo($block),
                'video_external' => self::renderExternalVideo($block),
                'quote'          => self::renderQuote($block),
                'code'           => self::renderCode($block),
                'list'           => self::renderList($block),
                default          => '',
            };
        }
        return $html;
    }

    /**
     * Extracts plain searchable text from content blocks.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public static function extractPlainText(array $blocks): string
    {
        $parts = [];
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            foreach (['text', 'caption', 'title', 'cite', 'code'] as $field) {
                if (!empty($block[$field]) && is_string($block[$field])) {
                    $parts[] = trim(strip_tags($block[$field]));
                }
            }
            if (!empty($block['items']) && is_array($block['items'])) {
                foreach ($block['items'] as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        $parts[] = trim($item);
                    }
                }
            }
        }
        return trim(implode("\n", $parts));
    }

    /**
     * Formats inline text safely (supports **bold**, *italic*, `code`, [label](https://...)).
     */
    public static function formatInline(string $text): string
    {
        $escaped = Helpers::e($text);

        // Inline code: `code`
        $escaped = (string) preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $escaped);

        // Bold: **text**
        $escaped = (string) preg_replace('/\*\*([^\*\n]+)\*\*/', '<strong>$1</strong>', $escaped);

        // Italic: *text*
        $escaped = (string) preg_replace('/(?<!\*)\*([^\*\n]+)\*(?!\*)/', '<em>$1</em>', $escaped);

        // Safe links: [label](url)
        $escaped = (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function (array $m): string {
                $label = $m[1];
                $rawUrl = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
                if (!Helpers::isSafeUrl($rawUrl)) {
                    return $label;
                }
                $external = str_starts_with($rawUrl, 'http://') || str_starts_with($rawUrl, 'https://');
                $attrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
                return '<a href="' . Helpers::e($rawUrl) . '"' . $attrs . '>' . $label . '</a>';
            },
            $escaped
        );

        return nl2br($escaped, false);
    }

    /**
     * Renders a standalone responsive HTML5 local video player.
     */
    public static function renderLocalVideoPlayer(
        string $src,
        ?string $poster = null,
        ?string $title = null,
        ?string $caption = null
    ): string {
        $src = trim($src);
        if ($src === '' || !Helpers::isSafeUrl($src)) {
            return '';
        }

        $ext = strtolower(pathinfo((string) parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mime = $ext === 'webm' ? 'video/webm' : 'video/mp4';

        $posterAttr = '';
        if ($poster !== null && trim($poster) !== '' && Helpers::isSafeUrl($poster)) {
            $posterAttr = ' poster="' . Helpers::e(trim($poster)) . '"';
        }

        $captionHtml = '';
        if (($title !== null && trim($title) !== '') || ($caption !== null && trim($caption) !== '')) {
            $captionParts = [];
            if ($title !== null && trim($title) !== '') {
                $captionParts[] = '<strong>' . Helpers::e(trim($title)) . '</strong>';
            }
            if ($caption !== null && trim($caption) !== '') {
                $captionParts[] = Helpers::e(trim($caption));
            }
            $captionHtml = '<figcaption class="media-caption">' . implode(' — ', $captionParts) . '</figcaption>';
        }

        return '<figure class="media-block media-video-local">'
            . '<div class="video-player-shell">'
            . '<video class="html5-video-player" controls playsinline preload="metadata"' . $posterAttr . '>'
            . '<source src="' . Helpers::e($src) . '" type="' . $mime . '">'
            . 'Tarayıcınız HTML5 video oynatmayı desteklemiyor. '
            . '<a href="' . Helpers::e($src) . '" download>Videoyu indir</a>.'
            . '</video>'
            . '</div>'
            . $captionHtml
            . '</figure>';
    }

    /**
     * Renders a standalone responsive 16:9 lazy-loaded external video iframe (YouTube / Vimeo).
     */
    public static function renderExternalVideoPlayer(
        string $url,
        ?string $title = null,
        ?string $caption = null,
        ?string $provider = null,
        ?string $videoId = null
    ): string {
        $embedUrl = null;
        $resolvedProvider = $provider;

        if ($provider !== null && $videoId !== null && $videoId !== '') {
            if ($provider === 'youtube' && preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
                $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $videoId . '?rel=0';
            } elseif ($provider === 'vimeo' && preg_match('/^\d{6,12}$/', $videoId)) {
                $embedUrl = 'https://player.vimeo.com/video/' . $videoId . '?dnt=1';
            }
        }

        if ($embedUrl === null) {
            $parsed = Helpers::parseVideoUrl($url);
            if ($parsed === null) {
                return '';
            }
            $embedUrl = $parsed['embed_url'];
            $resolvedProvider = $parsed['provider'];
        }

        $iframeTitle = ($title !== null && trim($title) !== '')
            ? trim($title)
            : (($resolvedProvider === 'vimeo' ? 'Vimeo' : 'YouTube') . ' Video');

        $captionHtml = '';
        if (($title !== null && trim($title) !== '') || ($caption !== null && trim($caption) !== '')) {
            $captionParts = [];
            if ($title !== null && trim($title) !== '') {
                $captionParts[] = '<strong>' . Helpers::e(trim($title)) . '</strong>';
            }
            if ($caption !== null && trim($caption) !== '') {
                $captionParts[] = Helpers::e(trim($caption));
            }
            $captionHtml = '<figcaption class="media-caption">' . implode(' — ', $captionParts) . '</figcaption>';
        }

        return '<figure class="media-block media-video-external">'
            . '<div class="video-embed-16x9">'
            . '<iframe src="' . Helpers::e($embedUrl) . '" '
            . 'title="' . Helpers::e($iframeTitle) . '" '
            . 'loading="lazy" '
            . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
            . 'referrerpolicy="strict-origin-when-cross-origin" '
            . 'allowfullscreen></iframe>'
            . '</div>'
            . $captionHtml
            . '</figure>';
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderParagraph(array $block): string
    {
        $text = trim((string) ($block['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        return '<p>' . self::formatInline($text) . '</p>';
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderHeading(array $block): string
    {
        $text = trim((string) ($block['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        $level = (int) ($block['level'] ?? 2);
        $tag = ($level === 3) ? 'h3' : 'h2';
        $id = Helpers::slugify($text);
        return '<' . $tag . ' id="' . Helpers::e($id) . '">' . Helpers::e($text) . '</' . $tag . '>';
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderImage(array $block): string
    {
        $src = trim((string) ($block['src'] ?? ''));
        if ($src === '' || !Helpers::isSafeUrl($src)) {
            return '';
        }
        $alt = trim((string) ($block['alt'] ?? ($block['caption'] ?? 'Görsel')));
        $caption = trim((string) ($block['caption'] ?? ''));

        $figCaption = $caption !== '' ? '<figcaption class="media-caption">' . Helpers::e($caption) . '</figcaption>' : '';

        return '<figure class="media-block media-image">'
            . '<button type="button" class="lightbox-trigger" data-lightbox-src="' . Helpers::e($src) . '" data-lightbox-caption="' . Helpers::e($caption !== '' ? $caption : $alt) . '" aria-label="Görseli büyüt">'
            . '<img src="' . Helpers::e($src) . '" alt="' . Helpers::e($alt) . '" loading="lazy" decoding="async">'
            . '</button>'
            . $figCaption
            . '</figure>';
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderLocalVideo(array $block): string
    {
        return self::renderLocalVideoPlayer(
            (string) ($block['src'] ?? ''),
            isset($block['poster']) ? (string) $block['poster'] : null,
            isset($block['title']) ? (string) $block['title'] : null,
            isset($block['caption']) ? (string) $block['caption'] : null
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderExternalVideo(array $block): string
    {
        return self::renderExternalVideoPlayer(
            (string) ($block['url'] ?? ''),
            isset($block['title']) ? (string) $block['title'] : null,
            isset($block['caption']) ? (string) $block['caption'] : null,
            isset($block['provider']) ? (string) $block['provider'] : null,
            isset($block['video_id']) ? (string) $block['video_id'] : null
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderQuote(array $block): string
    {
        $text = trim((string) ($block['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        $cite = trim((string) ($block['cite'] ?? ''));
        $citeHtml = $cite !== '' ? '<cite>— ' . Helpers::e($cite) . '</cite>' : '';
        return '<blockquote class="content-quote"><p>' . self::formatInline($text) . '</p>' . $citeHtml . '</blockquote>';
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderCode(array $block): string
    {
        $code = (string) ($block['code'] ?? '');
        if (trim($code) === '') {
            return '';
        }
        $lang = trim((string) ($block['language'] ?? ''));
        $langBadge = $lang !== '' ? '<div class="code-block-header"><span>' . Helpers::e($lang) . '</span></div>' : '';
        return '<div class="code-block">' . $langBadge . '<pre><code>' . Helpers::e($code) . '</code></pre></div>';
    }

    /**
     * @param array<string, mixed> $block
     */
    private static function renderList(array $block): string
    {
        $items = $block['items'] ?? [];
        if (is_string($items)) {
            $items = array_filter(array_map('trim', explode("\n", $items)), static fn(string $v): bool => $v !== '');
        }
        if (!is_array($items) || $items === []) {
            return '';
        }
        $tag = (($block['style'] ?? 'unordered') === 'ordered') ? 'ol' : 'ul';
        $li = '';
        foreach ($items as $item) {
            if (!is_string($item) || trim($item) === '') {
                continue;
            }
            $li .= '<li>' . self::formatInline(trim($item)) . '</li>';
        }
        return $li !== '' ? '<' . $tag . ' class="content-list">' . $li . '</' . $tag . '>' : '';
    }
}
