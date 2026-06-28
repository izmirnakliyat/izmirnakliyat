<?php
declare(strict_types=1);

require_once __DIR__ . '/video_sitemap_build.php';

/**
 * HTML içinde bu video Shorts URL'si ile mi geçiyor?
 */
function mynak_video_id_is_shorts_in_html(string $videoId, string $html): bool
{
    if ($videoId === '' || $html === '') {
        return false;
    }

    return (bool) preg_match(
        '#youtube\.com/shorts/' . preg_quote($videoId, '#') . '(?:[/?"\']|$)#i',
        $html
    );
}

function mynak_video_youtube_content_url(string $videoId, string $html = ''): string
{
    if (mynak_video_id_is_shorts_in_html($videoId, $html)) {
        return 'https://www.youtube.com/shorts/' . $videoId;
    }

    return 'https://www.youtube.com/watch?v=' . $videoId;
}

/**
 * GSC "izleme sayfasında yok" — iframe'i semantik watch kutusuna al, h1-h6 içinden çıkar.
 */
function mynak_video_normalize_watch_html(string $html): string
{
    if ($html === '') {
        return $html;
    }

    // Ic ice mynak-video-watch sarmalayıcılarını duzelt (TinyMCE + pipeline cift uygulama)
    $html = preg_replace(
        '#(<div class="mynak-video-watch[^"]*"[^>]*>\s*<div class="mynak-video-frame[^"]*"[^>]*>)\s*<div class="mynak-video-watch[^"]*"[^>]*>\s*<div class="mynak-video-frame[^"]*"[^>]*>#is',
        '$1',
        $html
    ) ?? $html;
    $html = preg_replace_callback(
        '#<(h[1-6])(\s[^>]*)?>(.*?)</\1>#is',
        static function (array $m): string {
            $inner = (string) ($m[3] ?? '');
            if (!preg_match('#<iframe\b[^>]*(?:youtube\.com/embed/|youtube-nocookie\.com/embed/)#i', $inner)) {
                return $m[0];
            }
            $iframe = '';
            if (preg_match('#(<iframe\b[^>]*>.*?</iframe>)#is', $inner, $im)) {
                $iframe = (string) $im[1];
            }
            $rest = trim(preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $inner));
            $tag = (string) ($m[1] ?? 'h2');
            $attrs = (string) ($m[2] ?? '');
            $heading = $rest !== '' ? '<' . $tag . $attrs . '>' . $rest . '</' . $tag . '>' : '';

            return $heading . mynak_video_wrap_iframe_html($iframe);
        },
        $html
    );

    // Sarmalanmamış YouTube iframe'leri
    $html = preg_replace_callback(
        '#<iframe\b([^>]*)\bsrc=(["\'])[^"\']*(?:youtube\.com/embed/|youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})[^"\']*\2([^>]*)>.*?</iframe>#is',
        static function (array $m): string {
            $full = $m[0];
            if (str_contains($full, 'mynak-video-watch')) {
                return $full;
            }

            return mynak_video_wrap_iframe_html($full);
        },
        $html
    );

    return $html;
}

function mynak_video_wrap_iframe_html(string $iframeHtml): string
{
    $iframeHtml = trim($iframeHtml);
    if ($iframeHtml === '') {
        return '';
    }
    if (str_contains($iframeHtml, 'mynak-video-watch') || str_contains($iframeHtml, 'mynak-video-frame')) {
        return $iframeHtml;
    }
    if (!preg_match('#\btitle\s*=#i', $iframeHtml)) {
        $iframeHtml = preg_replace('#<iframe\b#i', '<iframe title="MY Nakliyat video"', $iframeHtml, 1);
    }

    return '<div class="mynak-video-watch my-4" role="region" aria-label="Video">'
        . '<div class="mynak-video-frame ratio ratio-16x9">' . $iframeHtml . '</div></div>';
}

/**
 * @return list<string>
 */
function mynak_video_primary_embedded_ids(string $html): array
{
    $ids = mynak_video_sitemap_extract_embedded_youtube_ids($html);
    if ($ids === []) {
        return [];
    }

    return [ (string) $ids[0] ];
}

/**
 * WebPage + mainEntity VideoObject (GSC izleme sayfası sinyali).
 *
 * @param array<string, mixed> $videoNode
 * @return array<string, mixed>
 */
function mynak_schema_build_watch_webpage_node(string $pageUrl, string $pageTitle, array $videoNode): array
{
    $pageUrl = rtrim(trim($pageUrl), '/');
    if ($pageUrl === '' || $videoNode === []) {
        return [];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        '@id' => $pageUrl . '#webpage',
        'url' => $pageUrl,
        'name' => mb_substr(trim($pageTitle), 0, 110),
        'mainEntity' => $videoNode,
    ];
}
