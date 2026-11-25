<?php
declare(strict_types=1);

namespace App;

use DOMDocument;
use DOMElement;
use DOMNode;

final class MediaRenderer
{
    /**
     * Renders an array of content blocks (both rich HTML and legacy structured blocks) into safe HTML.
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
                'html'           => self::sanitizeHtml((string) ($block['html'] ?? '')),
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
     * Converts stored blocks (rich HTML or legacy blocks) into clean editable HTML for the WYSIWYG editor.
     *
     * @param array<int, array<string, mixed>> $blocks
     */
    public static function renderForEditor(array $blocks): string
    {
        if ($blocks === []) {
            return '<p><br></p>';
        }

        // If already saved as rich HTML block, return sanitized HTML directly
        if (count($blocks) === 1 && (($blocks[0]['type'] ?? '') === 'html')) {
            $sanitized = self::sanitizeHtml((string) ($blocks[0]['html'] ?? ''));
            return $sanitized !== '' ? $sanitized : '<p><br></p>';
        }

        $html = '';
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? 'paragraph');
            if ($type === 'image') {
                // In editor, render plain figure+img without button wrapper so user can edit caption/image cleanly
                $src = trim((string) ($block['src'] ?? ''));
                if ($src !== '' && Helpers::isSafeUrl($src)) {
                    $alt = trim((string) ($block['alt'] ?? ($block['caption'] ?? 'Görsel')));
                    $cap = trim((string) ($block['caption'] ?? ''));
                    $html .= '<figure class="media-block media-image">'
                        . '<img src="' . Helpers::e($src) . '" alt="' . Helpers::e($alt) . '" loading="lazy" decoding="async">'
                        . ($cap !== '' ? '<figcaption class="media-caption">' . Helpers::e($cap) . '</figcaption>' : '')
                        . '</figure>';
                }
                continue;
            }
            $html .= match ($type) {
                'html'           => self::sanitizeHtml((string) ($block['html'] ?? '')),
                'paragraph'      => self::renderParagraph($block),
                'heading'        => self::renderHeading($block),
                'video_local'    => self::renderLocalVideo($block),
                'video_external' => self::renderExternalVideo($block),
                'quote'          => self::renderQuote($block),
                'code'           => self::renderCode($block),
                'list'           => self::renderList($block),
                default          => '',
            };
        }

        return trim($html) !== '' ? $html : '<p><br></p>';
    }

    /**
     * Strict server-side HTML sanitizer (DOMDocument whitelist) for WYSIWYG editor content.
     * Prevents XSS while preserving rich typography, images, HTML5 local videos, and YouTube/Vimeo embeds.
     */
    public static function sanitizeHtml(string $dirtyHtml): string
    {
        $dirtyHtml = trim($dirtyHtml);
        if ($dirtyHtml === '') {
            return '';
        }

        $allowedTags = [
            'p', 'br', 'hr',
            'h2', 'h3', 'h4',
            'strong', 'b', 'em', 'i', 'u', 's', 'mark', 'small', 'sub', 'sup',
            'a', 'ul', 'ol', 'li',
            'blockquote', 'cite', 'pre', 'code',
            'figure', 'figcaption', 'img',
            'video', 'source', 'iframe',
            'div', 'span',
        ];

        $allowedAttrs = [
            'a'          => ['href', 'target', 'rel', 'title'],
            'img'        => ['src', 'alt', 'title', 'loading', 'decoding', 'width', 'height'],
            'video'      => ['src', 'poster', 'controls', 'playsinline', 'preload', 'class'],
            'source'     => ['src', 'type'],
            'iframe'     => ['src', 'title', 'loading', 'allow', 'referrerpolicy', 'allowfullscreen'],
            'figure'     => ['class'],
            'figcaption' => ['class'],
            'blockquote' => ['class'],
            'pre'        => ['class'],
            'code'       => ['class'],
            'ul'         => ['class'],
            'ol'         => ['class'],
            'div'        => ['class'],
            'span'       => ['class'],
            'h2'         => ['id'],
            'h3'         => ['id'],
            'h4'         => ['id'],
        ];

        $allowedClasses = [
            'media-block', 'media-image', 'media-video-local', 'media-video-external',
            'media-caption', 'video-player-shell', 'html5-video-player', 'video-embed-16x9',
            'content-quote', 'content-list', 'code-block', 'code-block-header',
        ];

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prevUseErrors = libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="__bp_root__">' . $dirtyHtml . '</div></body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        $root = $dom->getElementById('__bp_root__');
        if (!$root instanceof DOMElement) {
            return '';
        }

        self::cleanDomNode($root, $allowedTags, $allowedAttrs, $allowedClasses);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * @param string[] $allowedTags
     * @param array<string, string[]> $allowedAttrs
     * @param string[] $allowedClasses
     */
    private static function cleanDomNode(
        DOMNode $parent,
        array $allowedTags,
        array $allowedAttrs,
        array $allowedClasses
    ): void {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);
                continue;
            }

            if ($node->nodeType !== XML_ELEMENT_NODE || !$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            // Drop dangerous/editor-only UI elements completely (including their children)
            if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math'], true)) {
                $parent->removeChild($node);
                continue;
            }

            // Convert h1 inside body to h2 for SEO hierarchy
            if ($tag === 'h1') {
                $h2 = $node->ownerDocument->createElement('h2');
                while ($node->firstChild !== null) {
                    $h2->appendChild($node->firstChild);
                }
                $parent->replaceChild($h2, $node);
                self::cleanDomNode($h2, $allowedTags, $allowedAttrs, $allowedClasses);
                continue;
            }

            // Unwrap disallowed formatting tags (like font, section, article) while keeping inner text
            if (!in_array($tag, $allowedTags, true)) {
                self::cleanDomNode($node, $allowedTags, $allowedAttrs, $allowedClasses);
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            // Validate iframe strictly: only YouTube / Vimeo embeds allowed
            if ($tag === 'iframe') {
                $src = trim($node->getAttribute('src'));
                $isAllowedEmbed = (bool) preg_match(
                    '#^https://(?:www\.youtube-nocookie\.com/embed/[a-zA-Z0-9_-]{11}|www\.youtube\.com/embed/[a-zA-Z0-9_-]{11}|player\.vimeo\.com/video/\d{6,12})(?:\?[^"\'\s]*)?$#',
                    $src
                );
                if (!$isAllowedEmbed) {
                    $parent->removeChild($node);
                    continue;
                }
            }

            // Clean attributes against whitelist
            $permitted = $allowedAttrs[$tag] ?? [];
            $attrNames = [];
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attr) {
                    $attrNames[] = $attr->name;
                }
            }

            foreach ($attrNames as $attrName) {
                $lowerAttr = strtolower($attrName);
                if (!in_array($lowerAttr, $permitted, true)) {
                    $node->removeAttribute($attrName);
                    continue;
                }

                $val = trim($node->getAttribute($attrName));

                if (in_array($lowerAttr, ['href', 'src', 'poster'], true)) {
                    if ($val === '' || !Helpers::isSafeUrl($val)) {
                        $node->removeAttribute($attrName);
                    }
                } elseif ($lowerAttr === 'class') {
                    $classes = preg_split('/\s+/', $val, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $kept = array_values(array_intersect($classes, $allowedClasses));
                    if ($kept === []) {
                        $node->removeAttribute($attrName);
                    } else {
                        $node->setAttribute('class', implode(' ', $kept));
                    }
                } elseif ($lowerAttr === 'type' && $tag === 'source') {
                    if (!in_array(strtolower($val), ['video/mp4', 'video/webm'], true)) {
                        $node->setAttribute('type', 'video/mp4');
                    }
                } elseif ($lowerAttr === 'target' && $tag === 'a') {
                    if ($val === '_blank') {
                        $node->setAttribute('rel', 'noopener noreferrer');
                    } else {
                        $node->removeAttribute('target');
                    }
                }
            }

            // Ensure safe defaults on media tags
            if ($tag === 'img') {
                if (!$node->hasAttribute('src')) {
                    $parent->removeChild($node);
                    continue;
                }
                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('decoding', 'async');
            } elseif ($tag === 'video') {
                $node->setAttribute('controls', '');
                $node->setAttribute('playsinline', '');
                $node->setAttribute('preload', 'metadata');
                if (!$node->hasAttribute('class')) {
                    $node->setAttribute('class', 'html5-video-player');
                }
            } elseif ($tag === 'iframe') {
                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('allowfullscreen', '');
                $node->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            }

            self::cleanDomNode($node, $allowedTags, $allowedAttrs, $allowedClasses);
        }
    }

    /**
     * Extracts plain searchable text from content blocks (including rich HTML blocks).
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
            if (($block['type'] ?? '') === 'html' && !empty($block['html']) && is_string($block['html'])) {
                $spaced = (string) preg_replace('/<\/(p|h[1-6]|li|div|blockquote|pre|figcaption|br)>/i', " $0", $block['html']);
                $clean = trim(html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $clean = (string) preg_replace('/\s+/u', ' ', $clean);
                if ($clean !== '') {
                    $parts[] = $clean;
                }
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
        $escaped = (string) preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $escaped);
        $escaped = (string) preg_replace('/\*\*([^\*\n]+)\*\*/', '<strong>$1</strong>', $escaped);
        $escaped = (string) preg_replace('/(?<!\*)\*([^\*\n]+)\*(?!\*)/', '<em>$1</em>', $escaped);

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
