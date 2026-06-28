<?php
declare(strict_types=1);

require_once __DIR__ . '/mynak_home_videos.php';
require_once __DIR__ . '/mynak_video_watch_page.php';
require_once __DIR__ . '/video_sitemap_build.php';

/**
 * Kanonik video izleme sayfasi URL'si (GSC "izleme sayfasi" sinyali).
 */
function mynak_video_watch_page_url(string $siteUrl, string $videoId): string
{
    $id = mynak_youtube_id_normalize_from_user_input($videoId);
    if ($id === '') {
        return rtrim($siteUrl, '/');
    }

    return rtrim($siteUrl, '/') . '/video/' . rawurlencode($id);
}

/**
 * @return list<array{youtube_id:string, name:string, description:string, upload_date:string, is_short:bool}>
 */
function mynak_youtube_shorts_settings_list(array $siteSettings): array
{
    $raw = trim((string) ($siteSettings['mynak_youtube_shorts_ids'] ?? ''));
    if ($raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    $items = [];
    foreach ($decoded as $row) {
        if (is_string($row)) {
            $id = mynak_youtube_id_normalize_from_user_input($row);
            if ($id === '') {
                continue;
            }
            $items[] = [
                'youtube_id' => $id,
                'name' => '',
                'description' => '',
                'upload_date' => '',
                'is_short' => true,
            ];
            continue;
        }
        if (!is_array($row)) {
            continue;
        }
        $id = mynak_youtube_id_normalize_from_user_input(
            (string) ($row['id'] ?? $row['youtube_id'] ?? '')
        );
        if ($id === '') {
            continue;
        }
        $items[] = [
            'youtube_id' => $id,
            'name' => trim((string) ($row['name'] ?? $row['title'] ?? '')),
            'description' => trim((string) ($row['description'] ?? '')),
            'upload_date' => trim((string) ($row['uploadDate'] ?? $row['datePublished'] ?? '')),
            'is_short' => !isset($row['isShort']) || (bool) $row['isShort'],
        ];
    }

    return $items;
}

/**
 * @param array<string, bool> $shortsFlagById
 */
function mynak_youtube_register_short_flag(array &$shortsFlagById, string $videoId, bool $isShort): void
{
    $id = mynak_youtube_id_normalize_from_user_input($videoId);
    if ($id === '') {
        return;
    }
    if ($isShort || !isset($shortsFlagById[$id])) {
        $shortsFlagById[$id] = $isShort;
    }
}

/**
 * @return array<string, array{youtube_id:string, title:string, description:string, upload_date:string, is_short:bool, source_html:string}>
 */
function mynak_youtube_video_registry_collect(mysqli $conn, array $siteSettings): array
{
    /** @var array<string, bool> $shortsFlags */
    $shortsFlags = [];
    /** @var array<string, array{youtube_id:string, title:string, description:string, upload_date:string, is_short:bool, source_html:string}> $registry */
    $registry = [];

    $upsert = static function (string $id, array $meta) use (&$registry, &$shortsFlags): void {
        $id = mynak_youtube_id_normalize_from_user_input($id);
        if ($id === '') {
            return;
        }
        $isShort = !empty($meta['is_short']) || !empty($shortsFlags[$id]);
        if (!isset($registry[$id])) {
            $registry[$id] = [
                'youtube_id' => $id,
                'title' => trim((string) ($meta['title'] ?? '')),
                'description' => trim((string) ($meta['description'] ?? '')),
                'upload_date' => trim((string) ($meta['upload_date'] ?? '')),
                'is_short' => $isShort,
                'source_html' => (string) ($meta['source_html'] ?? ''),
            ];
            return;
        }
        if ($registry[$id]['title'] === '' && trim((string) ($meta['title'] ?? '')) !== '') {
            $registry[$id]['title'] = trim((string) $meta['title']);
        }
        if ($registry[$id]['description'] === '' && trim((string) ($meta['description'] ?? '')) !== '') {
            $registry[$id]['description'] = trim((string) $meta['description']);
        }
        if ($registry[$id]['upload_date'] === '' && trim((string) ($meta['upload_date'] ?? '')) !== '') {
            $registry[$id]['upload_date'] = trim((string) $meta['upload_date']);
        }
        if ($isShort) {
            $registry[$id]['is_short'] = true;
        }
        if ($registry[$id]['source_html'] === '' && trim((string) ($meta['source_html'] ?? '')) !== '') {
            $registry[$id]['source_html'] = (string) $meta['source_html'];
        }
    };

    foreach (mynak_youtube_shorts_settings_list($siteSettings) as $row) {
        mynak_youtube_register_short_flag($shortsFlags, (string) $row['youtube_id'], true);
        $upsert((string) $row['youtube_id'], [
            'title' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'upload_date' => (string) ($row['upload_date'] ?? ''),
            'is_short' => true,
            'source_html' => '',
        ]);
    }

    foreach (mynak_home_videos_padded_list($siteSettings) as $hv) {
        $id = trim((string) ($hv['youtube_id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $upsert($id, [
            'title' => (string) ($hv['name'] ?? ''),
            'description' => (string) ($hv['description'] ?? ''),
            'upload_date' => (string) ($hv['upload_date'] ?? ''),
            'is_short' => false,
            'source_html' => '',
        ]);
    }

    $contentQueries = [
        'SELECT title, content AS html FROM pages WHERE status = 1',
        'SELECT ana_baslik AS title, icerik AS html FROM services WHERE status = 1',
        'SELECT baslik AS title, icerik AS html FROM blog_posts WHERE durum = 3',
    ];
    foreach ($contentQueries as $sql) {
        $res = @$conn->query($sql);
        if (!$res) {
            continue;
        }
        while ($row = $res->fetch_assoc()) {
            $html = (string) ($row['html'] ?? '');
            if ($html === '') {
                continue;
            }
            $title = (string) ($row['title'] ?? '');
            foreach (mynak_video_sitemap_extract_embedded_youtube_ids($html) as $id) {
                $isShort = mynak_video_id_is_shorts_in_html($id, $html)
                    || !empty($shortsFlags[$id]);
                if ($isShort) {
                    mynak_youtube_register_short_flag($shortsFlags, $id, true);
                }
                $upsert($id, [
                    'title' => $title,
                    'description' => $title,
                    'upload_date' => '',
                    'is_short' => $isShort,
                    'source_html' => $html,
                ]);
            }
        }
        $res->free();
    }

    foreach ($registry as $id => &$meta) {
        if (!empty($shortsFlags[$id])) {
            $meta['is_short'] = true;
        } elseif (mynak_youtube_duration_seconds($id, false) > 0 && mynak_youtube_duration_seconds($id, false) <= 60) {
            $meta['is_short'] = true;
        }
    }
    unset($meta);

    return $registry;
}

function mynak_youtube_is_short_video(string $videoId, string $sourceHtml = '', ?array $registry = null): bool
{
    $id = mynak_youtube_id_normalize_from_user_input($videoId);
    if ($id === '') {
        return false;
    }
    if (is_array($registry) && !empty($registry[$id]['is_short'])) {
        return true;
    }
    if ($sourceHtml !== '' && mynak_video_id_is_shorts_in_html($id, $sourceHtml)) {
        return true;
    }
    $dur = mynak_youtube_duration_seconds($id, false);

    return $dur > 0 && $dur <= 60;
}

function mynak_youtube_parse_duration_seconds_from_watch_html(string $html): int
{
    if ($html === '') {
        return 0;
    }
    $patterns = [
        '#"lengthSeconds"\s*:\s*"(\d+)"#',
        '#"lengthSeconds"\s*:\s*(\d+)#',
        '#"approxDurationMs"\s*:\s*"(\d+)"#',
        '#itemprop="duration"\s+content="([^"]+)"#i',
    ];
    foreach ($patterns as $pattern) {
        if (!preg_match($pattern, $html, $m)) {
            continue;
        }
        $val = (string) ($m[1] ?? '');
        if (preg_match('#^PT(\d+)S$#i', $val, $iso)) {
            return max(0, (int) $iso[1]);
        }
        if (str_contains($pattern, 'approxDurationMs')) {
            return max(0, (int) round(((int) $val) / 1000));
        }

        return max(0, (int) $val);
    }

    if (preg_match('#"isShort"\s*:\s*true#i', $html)) {
        return 45;
    }

    return 0;
}

function mynak_youtube_duration_seconds(string $videoId, bool $allowNetworkFetch = false): int
{
    $id = mynak_youtube_id_normalize_from_user_input($videoId);
    if ($id === '') {
        return 0;
    }

    $cached = mynak_youtube_read_cache_file($id, !$allowNetworkFetch);
    if (is_array($cached) && is_array($cached['raw'] ?? null)) {
        $dur = (int) ($cached['raw']['duration_seconds'] ?? 0);
        if ($dur > 0) {
            return $dur;
        }
    }

    if (!$allowNetworkFetch) {
        return 0;
    }

    $watchUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($id);
    $html = mynak_video_sitemap_http_get($watchUrl);
    $seconds = is_string($html) ? mynak_youtube_parse_duration_seconds_from_watch_html($html) : 0;
    if ($seconds <= 0) {
        $shortsHtml = mynak_video_sitemap_http_get('https://www.youtube.com/shorts/' . rawurlencode($id));
        if (is_string($shortsHtml) && preg_match('#"isShort"\s*:\s*true#i', $shortsHtml)) {
            $seconds = mynak_youtube_parse_duration_seconds_from_watch_html($shortsHtml);
            if ($seconds <= 0) {
                $seconds = 45;
            }
        }
    }

    if ($seconds > 0 && is_array($cached) && is_array($cached['raw'] ?? null)) {
        $cached['raw']['duration_seconds'] = $seconds;
        @file_put_contents(
            mynak_video_sitemap_oembed_cache_path($id),
            json_encode($cached['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    return max(0, $seconds);
}

function mynak_youtube_duration_iso8601(int $seconds): string
{
    if ($seconds <= 0) {
        return '';
    }

    return 'PT' . $seconds . 'S';
}

function mynak_youtube_watch_embed_iframe(string $videoId, string $title = '', bool $isShort = false): string
{
    $id = mynak_youtube_id_normalize_from_user_input($videoId);
    if ($id === '') {
        return '';
    }
    $titleOut = trim($title) !== '' ? trim($title) : 'MY Nakliyat Video';
    $src = 'https://www.youtube.com/embed/' . $id;
    $ratioClass = $isShort ? ' ratio-9x16 mynak-video-frame--short' : ' ratio-16x9';

    return '<div class="mynak-video-watch my-4" role="region" aria-label="Video">'
        . '<div class="mynak-video-frame' . $ratioClass . '">'
        . '<iframe src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"'
        . ' title="' . htmlspecialchars($titleOut, ENT_QUOTES, 'UTF-8') . '"'
        . ' frameborder="0" loading="eager"'
        . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
        . ' referrerpolicy="strict-origin-when-cross-origin"'
        . ' allowfullscreen></iframe>'
        . '</div></div>';
}

/**
 * @return list<array{youtube_id:string, title:string, description:string, watch_url:string, is_short:bool}>
 */
function mynak_youtube_shorts_public_list(mysqli $conn, array $siteSettings, string $siteUrl): array
{
    $registry = mynak_youtube_video_registry_collect($conn, $siteSettings);
    $out = [];
    foreach ($registry as $meta) {
        if (empty($meta['is_short'])) {
            continue;
        }
        $id = (string) $meta['youtube_id'];
        $title = trim((string) $meta['title']);
        if ($title === '') {
            $ytTitle = mynak_youtube_fetch_title_via_oembed($id, false);
            $title = is_string($ytTitle) && $ytTitle !== '' ? $ytTitle : 'MY Nakliyat Kisa Video';
        }
        $out[] = [
            'youtube_id' => $id,
            'title' => $title,
            'description' => trim((string) $meta['description']) !== '' ? (string) $meta['description'] : $title,
            'watch_url' => mynak_video_watch_page_url($siteUrl, $id),
            'is_short' => true,
        ];
    }

    return $out;
}
