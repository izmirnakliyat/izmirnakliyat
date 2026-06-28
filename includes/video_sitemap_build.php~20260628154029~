<?php
declare(strict_types=1);

require_once __DIR__ . '/mynak_home_videos.php';
require_once __DIR__ . '/mynak_video_watch_page.php';

/** @var array<string, string|null> */
$GLOBALS['mynak_youtube_oembed_runtime_cache'] = [];

/**
 * Metindeki tüm YouTube URL'leri (link, shorts, watch).
 *
 * @return list<string>
 */
function mynak_video_sitemap_extract_youtube_ids(string $html): array
{
    $ids = [];
    if ($html === '') {
        return $ids;
    }
    $patterns = [
        '#youtube\.com/watch\?v=([A-Za-z0-9_-]{11})#i',
        '#youtu\.be/([A-Za-z0-9_-]{11})#i',
        '#youtube\.com/embed/([A-Za-z0-9_-]{11})#i',
        '#youtube\.com/shorts/([A-Za-z0-9_-]{11})#i',
    ];
    foreach ($patterns as $p) {
        if (preg_match_all($p, $html, $m)) {
            foreach ($m[1] as $id) {
                $ids[$id] = true;
            }
        }
    }

    return array_keys($ids);
}

/**
 * Sayfada gerçekten oynatıcı (iframe embed) olan videolar — GSC "izleme sayfasında yok" için SSOT.
 *
 * @return list<string>
 */
function mynak_video_sitemap_extract_embedded_youtube_ids(string $html): array
{
    $ids = [];
    if ($html === '') {
        return $ids;
    }
    $patterns = [
        '#<iframe\b[^>]*\bsrc=(["\'])[^"\']*youtube\.com/embed/([A-Za-z0-9_-]{11})[^"\']*\1#i',
        '#<iframe\b[^>]*\bsrc=(["\'])[^"\']*youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})[^"\']*\1#i',
    ];
    foreach ($patterns as $p) {
        if (preg_match_all($p, $html, $m)) {
            foreach ($m[2] as $id) {
                $id = trim((string) $id);
                if ($id !== '') {
                    $ids[$id] = true;
                }
            }
        }
    }

    return array_keys($ids);
}

/**
 * iframe title="" degerlerini video ID ile eslestirir.
 *
 * @return array<string, string>
 */
function mynak_video_sitemap_iframe_titles_by_id(string $html): array
{
    $map = [];
    if ($html === '') {
        return $map;
    }
    $patterns = [
        '#<iframe\b[^>]*\btitle=(["\'])(.*?)\1[^>]*\bsrc=(["\'])[^"\']*(?:youtube\.com/embed/|youtu\.be/|youtube\.com/watch\?v=)([A-Za-z0-9_-]{11})[^"\']*\3#is',
        '#<iframe\b[^>]*\bsrc=(["\'])[^"\']*(?:youtube\.com/embed/|youtu\.be/|youtube\.com/watch\?v=)([A-Za-z0-9_-]{11})[^"\']*\1[^>]*\btitle=(["\'])(.*?)\3#is',
    ];
    foreach ($patterns as $i => $pattern) {
        if (!preg_match_all($pattern, $html, $m)) {
            continue;
        }
        $count = count($m[0]);
        for ($j = 0; $j < $count; $j++) {
            $id = $i === 0 ? (string) ($m[4][$j] ?? '') : (string) ($m[2][$j] ?? '');
            $title = $i === 0 ? html_entity_decode((string) ($m[2][$j] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') : html_entity_decode((string) ($m[4][$j] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $id = trim($id);
            $title = trim($title);
            if ($id !== '' && $title !== '' && !isset($map[$id])) {
                $map[$id] = $title;
            }
        }
    }

    return $map;
}

function mynak_video_sitemap_slug_from_title(string $text): string
{
    $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
    $english = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];
    $text = str_replace($turkish, $english, $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    return trim((string) $text, '-');
}

function mynak_video_sitemap_project_root(): string
{
    if (defined('PROJECT_ROOT') && is_string(PROJECT_ROOT) && PROJECT_ROOT !== '') {
        return rtrim(PROJECT_ROOT, DIRECTORY_SEPARATOR);
    }

    return dirname(__DIR__);
}

function mynak_video_sitemap_oembed_cache_path(string $videoId): string
{
    $dir = mynak_video_sitemap_project_root() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'youtube_oembed';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir . DIRECTORY_SEPARATOR . $videoId . '.json';
}

function mynak_schema_video_upload_datetime(?string $raw): string
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return '';
    }

    // ISO 8601 + timezone (GSC VideoObject uploadDate zorunlulugu)
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/', $raw)) {
        return $raw;
    }

    // Yalnizca tarih → Turkiye saati (+03:00)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw . 'T08:00:00+03:00';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return '';
    }

    $dt = (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone('Europe/Istanbul'));

    return $dt->format('Y-m-d\TH:i:sP');
}

/**
 * Onbellek / sitemap icin Y-m-d (geriye uyumluluk).
 */
function mynak_youtube_normalize_upload_date_iso(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '';
    }

    return gmdate('Y-m-d', $ts);
}

function mynak_youtube_parse_upload_date_from_watch_html(string $html): string
{
    if ($html === '') {
        return '';
    }
    $patterns = [
        '#"uploadDate"\s*:\s*"([^"]+)"#',
        '#"datePublished"\s*:\s*"([^"]+)"#',
        '#itemprop="uploadDate"\s+content="([^"]+)"#i',
        '#itemprop="datePublished"\s+content="([^"]+)"#i',
    ];
    foreach ($patterns as $pattern) {
        if (!preg_match($pattern, $html, $m)) {
            continue;
        }
        $iso = mynak_youtube_normalize_upload_date_iso((string) ($m[1] ?? ''));
        if ($iso !== '') {
            return $iso;
        }
    }

    return '';
}

function mynak_youtube_fetch_upload_date_from_watch_page(string $videoId): string
{
    if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
        return '';
    }
    $watchUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($videoId);
    $html = mynak_video_sitemap_http_get($watchUrl);

    return is_string($html) ? mynak_youtube_parse_upload_date_from_watch_html($html) : '';
}

/**
 * @return array{title: ?string, upload_date: string}|null
 */
function mynak_youtube_read_cache_file(string $videoId, bool $ignoreTtl = false): ?array
{
    $cachePath = mynak_video_sitemap_oembed_cache_path($videoId);
    if (!is_readable($cachePath)) {
        return null;
    }
    $cached = json_decode((string) file_get_contents($cachePath), true);
    if (!is_array($cached)) {
        return null;
    }
    $fetchedAt = (int) ($cached['fetched_at'] ?? 0);
    $ttl = 7 * 86400;
    if (!$ignoreTtl && ($fetchedAt <= 0 || (time() - $fetchedAt) >= $ttl)) {
        return null;
    }
    $title = trim((string) ($cached['title'] ?? ''));

    return [
        'title' => $title !== '' ? $title : null,
        'upload_date' => mynak_youtube_normalize_upload_date_iso((string) ($cached['upload_date'] ?? '')),
        'raw' => $cached,
    ];
}

/**
 * Sayfa render: yalnizca dosya onbellegi (HTTP yok — timeout onlemi).
 * Sitemap/CLI: $allowNetworkFetch=true ile YouTube'dan doldurur.
 *
 * @return array{title: ?string, upload_date: string}
 */
function mynak_youtube_ensure_video_cache(string $videoId, bool $allowNetworkFetch = false): array
{
    $empty = ['title' => null, 'upload_date' => ''];
    if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
        return $empty;
    }

    static $runtime = [];
    $runtimeKey = $videoId . ($allowNetworkFetch ? ':net' : ':ro');
    if (isset($runtime[$runtimeKey])) {
        return $runtime[$runtimeKey];
    }

    $cached = mynak_youtube_read_cache_file($videoId, !$allowNetworkFetch);
    if ($cached !== null) {
        $uploadDate = (string) ($cached['upload_date'] ?? '');
        if ($allowNetworkFetch && $uploadDate === '' && ($cached['title'] ?? null) !== null) {
            $uploadDate = mynak_youtube_fetch_upload_date_from_watch_page($videoId);
            if ($uploadDate !== '' && is_array($cached['raw'] ?? null)) {
                $cached['raw']['upload_date'] = $uploadDate;
                @file_put_contents(
                    mynak_video_sitemap_oembed_cache_path($videoId),
                    json_encode($cached['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }
        }
        $cachedTitle = $cached['title'] ?? null;
        $runtime[$runtimeKey] = [
            'title' => $cachedTitle,
            'upload_date' => $uploadDate,
        ];

        if (is_string($cachedTitle) && $cachedTitle !== '') {
            $GLOBALS['mynak_youtube_oembed_runtime_cache'][$videoId] = $cachedTitle;
        }

        return $runtime[$runtimeKey];
    }

    if (!$allowNetworkFetch) {
        $runtime[$runtimeKey] = $empty;

        return $empty;
    }

    $watchUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($videoId);
    $oembedUrl = 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode($watchUrl);
    $json = mynak_video_sitemap_http_get($oembedUrl);
    $title = null;
    if ($json !== null) {
        $data = json_decode($json, true);
        if (is_array($data)) {
            $t = trim((string) ($data['title'] ?? ''));
            if ($t !== '') {
                $title = $t;
            }
        }
    }

    $uploadDate = mynak_youtube_fetch_upload_date_from_watch_page($videoId);

    @file_put_contents(mynak_video_sitemap_oembed_cache_path($videoId), json_encode([
        'title' => $title,
        'upload_date' => $uploadDate,
        'fetched_at' => time(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $runtime[$runtimeKey] = [
        'title' => $title,
        'upload_date' => $uploadDate,
    ];

    if ($title !== null && $title !== '') {
        $GLOBALS['mynak_youtube_oembed_runtime_cache'][$videoId] = $title;
    }

    return $runtime[$runtimeKey];
}

/**
 * YouTube oEmbed ile gercek video basligini getirir (7 gun dosya onbellegi).
 */
function mynak_youtube_fetch_title_via_oembed(string $videoId, bool $allowNetworkFetch = false): ?string
{
    return mynak_youtube_ensure_video_cache($videoId, $allowNetworkFetch)['title'];
}

/**
 * GSC VideoObject uploadDate icin ISO 8601 datetime (timezone dahil).
 */
function mynak_youtube_fetch_upload_date_iso(string $videoId, bool $allowNetworkFetch = false): string
{
    $raw = mynak_youtube_ensure_video_cache($videoId, $allowNetworkFetch)['upload_date'];

    return mynak_schema_video_upload_datetime($raw);
}

function mynak_video_sitemap_http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'MY-Nakliyat-Video-Sitemap/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) {
            return null;
        }

        return (string) $body;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
            'header' => "User-Agent: MY-Nakliyat-Video-Sitemap/1.0\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        return null;
    }

    return (string) $body;
}

function mynak_video_sitemap_resolve_title(
    string $videoId,
    string $html,
    string $fallbackTitle,
    int $index,
    int $total
): string {
    $ytTitle = mynak_youtube_fetch_title_via_oembed($videoId, true);
    if (is_string($ytTitle) && $ytTitle !== '') {
        return mb_substr($ytTitle, 0, 95);
    }

    $iframeTitles = mynak_video_sitemap_iframe_titles_by_id($html);
    if (isset($iframeTitles[$videoId]) && trim($iframeTitles[$videoId]) !== '') {
        return mb_substr(trim($iframeTitles[$videoId]), 0, 95);
    }

    if ($total === 1) {
        return mb_substr($fallbackTitle, 0, 95);
    }

    return mb_substr($fallbackTitle . ' | Video ' . (string) $index, 0, 95);
}

/**
 * @param list<array{thumb:string,title:string,desc:string,embed:string,watch:string}> $videos
 */
function mynak_video_sitemap_append_url(string &$xml, string $loc, array $videos): void
{
    if ($videos === []) {
        return;
    }
    $xml .= "  <url>\n";
    $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
    foreach ($videos as $v) {
        $xml .= "    <video:video>\n";
        $xml .= '      <video:thumbnail_loc>' . htmlspecialchars($v['thumb'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</video:thumbnail_loc>\n";
        $xml .= '      <video:title>' . htmlspecialchars($v['title'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</video:title>\n";
        $xml .= '      <video:description>' . htmlspecialchars($v['desc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</video:description>\n";
        $xml .= '      <video:player_loc allow_embed="yes">' . htmlspecialchars($v['embed'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</video:player_loc>\n";
        $xml .= '      <video:content_loc>' . htmlspecialchars($v['watch'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</video:content_loc>\n";
        if (!empty($v['duration'])) {
            $xml .= '      <video:duration>' . (int) $v['duration'] . "</video:duration>\n";
        }
        if (!empty($v['publication_date'])) {
            $xml .= '      <video:publication_date>' . htmlspecialchars($v['publication_date'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</video:publication_date>\n";
        }
        $xml .= "    </video:video>\n";
    }
    $xml .= "  </url>\n";
}

/**
 * @return list<array{thumb:string,title:string,desc:string,embed:string,watch:string}>
 */
function mynak_video_sitemap_videos_from_ids(
    array $ids,
    string $fallbackTitle,
    string $fallbackDesc,
    string $html = ''
): array {
    $html = mynak_video_normalize_watch_html($html);
    $videos = [];
    $total = count($ids);
    $n = 1;
    foreach ($ids as $id) {
        $id = (string) $id;
        if ($id === '') {
            continue;
        }
        $title = mynak_video_sitemap_resolve_title($id, $html, $fallbackTitle, $n, $total);
        $desc = mb_substr($title !== '' ? $title : $fallbackDesc, 0, 150);
        $videos[] = [
            'thumb' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
            'title' => $title,
            'desc' => $desc,
            'embed' => 'https://www.youtube.com/embed/' . $id,
            'watch' => mynak_video_youtube_content_url($id, $html),
            'publication_date' => mynak_youtube_fetch_upload_date_iso($id, true),
            'duration' => mynak_youtube_duration_seconds($id, true),
        ];
        $n++;
    }

    return $videos;
}

/**
 * Sayfa/hizmet/blog: yalnizca normalize sonrasi gomulu birincil video (GSC izleme sayfasi).
 *
 * @return list<array{thumb:string,title:string,desc:string,embed:string,watch:string,publication_date?:string}>
 */
function mynak_video_sitemap_videos_from_page_html(
    string $html,
    string $fallbackTitle,
    string $fallbackDesc
): array {
    $html = mynak_video_normalize_watch_html($html);
    $ids = mynak_video_primary_embedded_ids($html);
    if ($ids === []) {
        return [];
    }

    return mynak_video_sitemap_videos_from_ids($ids, $fallbackTitle, $fallbackDesc, $html);
}

/**
 * @param array<string, array<string, array{thumb:string,title:string,desc:string,embed:string,watch:string}>> $entries
 * @param list<array{thumb:string,title:string,desc:string,embed:string,watch:string}> $videos
 */
function mynak_video_sitemap_merge_entries(array &$entries, string $loc, array $videos): void
{
    if ($videos === []) {
        return;
    }
    if (!isset($entries[$loc])) {
        $entries[$loc] = [];
    }
    foreach ($videos as $video) {
        $entries[$loc][$video['watch']] = $video;
    }
}

/**
 * @return array{xml:string,url_count:int,video_count:int,youtube_titles:int}
 */
function mynak_build_video_sitemap_xml(mysqli $conn, string $site_url): array
{
    require_once __DIR__ . '/mynak_youtube_video_pages.php';
    $GLOBALS['mynak_youtube_oembed_runtime_cache'] = [];
    $site_url = rtrim($site_url, '/');
    /** @var array<string, array<string, array{thumb:string,title:string,desc:string,embed:string,watch:string,duration?:int,publication_date?:string}>> $entries */
    $entries = [];

    $siteSettings = [];
    $settingsRes = $conn->query("SELECT name, value FROM settings WHERE name IN ('mynak_home_youtube_ids', 'mynak_youtube_shorts_ids')");
    if ($settingsRes) {
        while ($row = $settingsRes->fetch_assoc()) {
            $siteSettings[(string) $row['name']] = (string) ($row['value'] ?? '');
        }
        $settingsRes->free();
    }

    $registry = mynak_youtube_video_registry_collect($conn, $siteSettings);
    foreach ($registry as $id => $meta) {
        $ytTitle = mynak_youtube_fetch_title_via_oembed($id, true);
        $title = trim((string) ($meta['title'] ?? ''));
        if ($title === '' && is_string($ytTitle) && $ytTitle !== '') {
            $title = $ytTitle;
        }
        if ($title === '') {
            $title = 'MY Nakliyat Video';
        }
        $desc = trim((string) ($meta['description'] ?? ''));
        if ($desc === '') {
            $desc = $title;
        }
        $isShort = !empty($meta['is_short']);
        $sourceHtml = (string) ($meta['source_html'] ?? '');
        $duration = mynak_youtube_duration_seconds($id, true);
        $videos = [[
            'thumb' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
            'title' => mb_substr($title, 0, 95),
            'desc' => mb_substr($desc, 0, 150),
            'embed' => 'https://www.youtube.com/embed/' . $id,
            'watch' => $isShort
                ? 'https://www.youtube.com/shorts/' . $id
                : mynak_video_youtube_content_url($id, $sourceHtml),
            'publication_date' => mynak_youtube_fetch_upload_date_iso($id, true),
            'duration' => $duration > 0 ? $duration : ($isShort ? 45 : 0),
        ]];
        mynak_video_sitemap_merge_entries($entries, mynak_video_watch_page_url($site_url, $id), $videos);
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

    $urlCount = 0;
    $videoCount = 0;
    foreach ($entries as $loc => $videosByWatch) {
        $videos = array_values($videosByWatch);
        mynak_video_sitemap_append_url($xml, $loc, $videos);
        $urlCount++;
        $videoCount += count($videos);
    }

    $xml .= "</urlset>\n";

    $youtubeTitles = 0;
    foreach ($GLOBALS['mynak_youtube_oembed_runtime_cache'] as $title) {
        if (is_string($title) && $title !== '') {
            $youtubeTitles++;
        }
    }

    return [
        'xml' => $xml,
        'url_count' => $urlCount,
        'video_count' => $videoCount,
        'youtube_titles' => $youtubeTitles,
    ];
}

/**
 * @return array{ok:bool,message:string,url_count:int,video_count:int,youtube_titles:int,path:string}
 */
function mynak_write_video_sitemap_file(mysqli $conn, string $site_url, string $projectRoot): array
{
    if (!defined('PROJECT_ROOT')) {
        define('PROJECT_ROOT', $projectRoot);
    }

    $built = mynak_build_video_sitemap_xml($conn, $site_url);
    $path = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'video-sitemap.xml';
    $bytes = file_put_contents($path, $built['xml']);
    if ($bytes === false) {
        return [
            'ok' => false,
            'message' => 'video-sitemap.xml yazilamadi. public_html izinlerini kontrol edin.',
            'url_count' => 0,
            'video_count' => 0,
            'youtube_titles' => 0,
            'path' => $path,
        ];
    }

    require_once __DIR__ . '/sitemap_build.php';
    $indexPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sitemap-index.xml';
    @file_put_contents($indexPath, sitemap_build_index_xml($site_url, $projectRoot));

    if ($built['url_count'] === 0) {
        return [
            'ok' => true,
            'message' => 'Dosya olusturuldu ancak hic video bulunamadi. Blog/sayfa/hizmet icerigine YouTube embed ekleyin veya Admin > Site Ayarlari > mynak_home_youtube_ids doldurun.',
            'url_count' => 0,
            'video_count' => 0,
            'youtube_titles' => 0,
            'path' => $path,
        ];
    }

    $msg = 'video-sitemap.xml basariyla olusturuldu.';
    if ($built['youtube_titles'] > 0) {
        $msg .= ' YouTube/oEmbed basliklari (onbellek dahil): ' . (int) $built['youtube_titles'] . ' video.';
    } else {
        $msg .= ' YouTube oEmbed basligi alinamadi; video basliklari sayfa/iframe yedeginden yazildi. '
            . 'Sunucu youtube.com erisemiyorsa cache/youtube_oembed klasorunu kontrol edin.';
    }

    return [
        'ok' => true,
        'message' => $msg,
        'url_count' => $built['url_count'],
        'video_count' => $built['video_count'],
        'youtube_titles' => $built['youtube_titles'],
        'path' => $path,
    ];
}
