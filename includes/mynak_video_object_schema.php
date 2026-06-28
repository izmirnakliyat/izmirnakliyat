<?php

declare(strict_types=1);



require_once __DIR__ . '/video_sitemap_build.php';

require_once __DIR__ . '/mynak_home_videos.php';

require_once __DIR__ . '/mynak_video_watch_page.php';

require_once __DIR__ . '/mynak_youtube_video_pages.php';



function mynak_schema_iso_date_from_raw(?string $raw): string

{

    return mynak_schema_video_upload_datetime($raw);

}



/**

 * @return array<string, mixed>

 */

function mynak_schema_build_video_object_node(

    string $videoId,

    string $title,

    string $description,

    string $pageUrl,

    string $publisherId,

    string $uploadDateIso = '',

    string $sourceHtml = '',

    bool $isShort = false,

    int $durationSeconds = 0

): array {

    $title = mb_substr(trim($title), 0, 95);

    $description = mb_substr(trim($description !== '' ? $description : $title), 0, 150);

    $watchPageUrl = mynak_video_watch_page_url(

        preg_match('#^https?://#i', $pageUrl) ? preg_replace('#/video/.*$#', '', $pageUrl) : rtrim((string) SITE_URL, '/'),

        $videoId

    );

    if (preg_match('#^https?://#i', $pageUrl) && str_contains($pageUrl, '/video/')) {

        $watchPageUrl = rtrim($pageUrl, '/');

    }



    $node = [

        '@context' => 'https://schema.org',

        '@type' => 'VideoObject',

        '@id' => $watchPageUrl . '#video-' . $videoId,

        'name' => $title,

        'description' => $description,

        'thumbnailUrl' => 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',

        'embedUrl' => 'https://www.youtube.com/embed/' . $videoId,

        'contentUrl' => mynak_video_youtube_content_url($videoId, $sourceHtml),

        'url' => $watchPageUrl,

        'isFamilyFriendly' => true,

        'publisher' => ['@id' => $publisherId],

    ];

    $uploadIso = '';

    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {

        $cachedRaw = mynak_youtube_ensure_video_cache($videoId, false)['upload_date'];

        $uploadIso = mynak_schema_video_upload_datetime($cachedRaw);

        if ($uploadIso === '') {

            $uploadIso = mynak_youtube_fetch_upload_date_iso($videoId, false);

        }

    }

    if ($uploadIso === '' && trim($uploadDateIso) !== '') {

        $uploadIso = mynak_schema_video_upload_datetime($uploadDateIso);

    }

    if ($uploadIso !== '') {

        $node['uploadDate'] = $uploadIso;

    }



    if ($durationSeconds <= 0) {

        $durationSeconds = mynak_youtube_duration_seconds($videoId, false);

    }

    $durationIso = mynak_youtube_duration_iso8601($durationSeconds);

    if ($durationIso !== '') {

        $node['duration'] = $durationIso;

    }

    if ($isShort) {

        $node['contentUrl'] = 'https://www.youtube.com/shorts/' . $videoId;

    }



    return $node;

}



/**

 * @return list<array<string, mixed>>

 */

function mynak_schema_video_objects_from_html(

    string $html,

    string $pageUrl,

    string $fallbackTitle,

    string $publisherId,

    string $uploadDateIso = ''

): array {

    $html = mynak_video_normalize_watch_html($html);

    $ids = mynak_video_primary_embedded_ids($html);

    if ($ids === []) {

        return [];

    }



    $nodes = [];

    $total = count($ids);

    $n = 1;

    foreach ($ids as $id) {

        $title = mynak_video_sitemap_resolve_title($id, $html, $fallbackTitle, $n, $total);

        $isShort = mynak_youtube_is_short_video($id, $html);

        $nodes[] = mynak_schema_build_video_object_node(

            $id,

            $title,

            $title,

            mynak_video_watch_page_url(rtrim(preg_replace('#/video/.*$#', '', $pageUrl), '/'), $id),

            $publisherId,

            $uploadDateIso,

            $html,

            $isShort

        );

        $n++;

    }



    return $nodes;

}



/**

 * @param list<array<string, mixed>> $videoNodes

 */

function mynak_schema_video_object_ld_scripts(array $videoNodes): string

{

    if ($videoNodes === []) {

        return '';

    }

    if (!function_exists('seo_runtime_ld_script_from_array')) {

        return '';

    }



    $out = '';

    foreach ($videoNodes as $node) {

        $out .= seo_runtime_ld_script_from_array($node);

    }



    return $out;

}



/**

 * Ana sayfa vitrin videolari: ItemList (izleme sayfasi URL'leri).

 *

 * @return array<string, mixed>|null

 */

function mynak_schema_home_video_item_list(array $site_settings, string $canonical_origin, string $publisherId): ?array

{

    $items = [];

    $position = 1;

    foreach (mynak_home_videos_padded_list($site_settings) as $hv) {

        $id = trim((string) ($hv['youtube_id'] ?? ''));

        if ($id === '') {

            continue;

        }

        $fallbackTitle = trim((string) ($hv['name'] ?? '')) !== ''

            ? (string) $hv['name']

            : 'MY Nakliyat Tanitim Videosu';

        $ytTitle = mynak_youtube_fetch_title_via_oembed($id);

        $title = is_string($ytTitle) && $ytTitle !== ''

            ? mb_substr($ytTitle, 0, 95)

            : mb_substr($fallbackTitle, 0, 95);

        $desc = trim((string) ($hv['description'] ?? '')) !== ''

            ? mb_substr((string) $hv['description'], 0, 150)

            : $title;

        $uploadDate = mynak_schema_video_upload_datetime((string) ($hv['upload_date'] ?? ''));

        if ($uploadDate === '') {

            $uploadDate = mynak_youtube_fetch_upload_date_iso($id, false);

        }

        $watchUrl = mynak_video_watch_page_url($canonical_origin, $id);

        $videoNode = mynak_schema_build_video_object_node(

            $id,

            $title,

            $desc,

            $watchUrl,

            $publisherId,

            $uploadDate,

            '',

            mynak_youtube_is_short_video($id)

        );

        $items[] = [

            '@type' => 'ListItem',

            'position' => $position,

            'url' => $watchUrl,

            'item' => $videoNode,

        ];

        $position++;

    }



    if ($items === []) {

        return null;

    }



    return [

        '@context' => 'https://schema.org',

        '@type' => 'ItemList',

        'name' => 'MY Nakliyat Video Rehberleri',

        'itemListElement' => $items,

        'numberOfItems' => count($items),

    ];

}



/**

 * @return array<string, mixed>|null

 */

function mynak_schema_shorts_hub_item_list(mysqli $conn, array $site_settings, string $canonical_origin, string $publisherId): ?array

{

    $shorts = mynak_youtube_shorts_public_list($conn, $site_settings, $canonical_origin);

    if ($shorts === []) {

        return null;

    }



    $items = [];

    $position = 1;

    foreach ($shorts as $row) {

        $id = (string) ($row['youtube_id'] ?? '');

        if ($id === '') {

            continue;

        }

        $watchUrl = (string) ($row['watch_url'] ?? mynak_video_watch_page_url($canonical_origin, $id));

        $title = (string) ($row['title'] ?? 'MY Nakliyat Kisa Video');

        $videoNode = mynak_schema_build_video_object_node(

            $id,

            $title,

            (string) ($row['description'] ?? $title),

            $watchUrl,

            $publisherId,

            '',

            '',

            true

        );

        $items[] = [

            '@type' => 'ListItem',

            'position' => $position,

            'url' => $watchUrl,

            'item' => $videoNode,

        ];

        $position++;

    }



    if ($items === []) {

        return null;

    }



    return [

        '@context' => 'https://schema.org',

        '@type' => 'ItemList',

        'name' => 'MY Nakliyat Kisa Videolar',

        'itemListElement' => $items,

        'numberOfItems' => count($items),

    ];

}



/**

 * @param array<string, mixed> $flex

 */

function mynak_schema_video_object_ld_fragment(

    string $page_type,

    ?array $page,

    ?array $blog,

    string $canonical,

    string $canonical_origin,

    array $site_settings,

    string $moving_company_at_id,

    array $flex

): string {

    if ($page_type === 'llms_export' || $moving_company_at_id === '') {

        return '';

    }



    if (!function_exists('seo_runtime_ld_script_from_array')) {

        return '';

    }



    global $conn;

    $publisherId = $moving_company_at_id;

    $out = '';



    if ($page_type === 'home') {

        $list = mynak_schema_home_video_item_list($site_settings, $canonical_origin, $publisherId);

        if (is_array($list)) {

            $out .= seo_runtime_ld_script_from_array($list);

        }



        return $out;

    }



    if ($page_type === 'shorts_hub' && $conn instanceof mysqli) {

        $list = mynak_schema_shorts_hub_item_list($conn, $site_settings, $canonical_origin, $publisherId);

        if (is_array($list)) {

            $out .= seo_runtime_ld_script_from_array($list);

            $collection = [

                '@context' => 'https://schema.org',

                '@type' => 'CollectionPage',

                '@id' => rtrim($canonical, '/') . '#collection',

                'url' => rtrim($canonical, '/'),

                'name' => 'MY Nakliyat Kisa Videolar',

                'description' => 'Izmir evden eve nakliyat kisa video rehberleri',

                'mainEntity' => $list,

            ];

            $out .= seo_runtime_ld_script_from_array($collection);

        }



        return $out;

    }



    if ($page_type === 'video_watch') {

        $videoId = '';

        if (preg_match('#/video/([A-Za-z0-9_-]{11})(?:/|$)#', $canonical, $m)) {

            $videoId = $m[1];

        }

        if ($videoId === '' && is_array($page)) {

            $slug = (string) ($page['slug'] ?? '');

            if (preg_match('#^video/([A-Za-z0-9_-]{11})$#', $slug, $m2)) {

                $videoId = $m2[1];

            }

        }

        if ($videoId === '') {

            return '';

        }



        $registry = ($conn instanceof mysqli)

            ? mynak_youtube_video_registry_collect($conn, $site_settings)

            : [];

        $meta = $registry[$videoId] ?? [];

        $title = trim((string) ($meta['title'] ?? ''));

        if ($title === '') {

            $ytTitle = mynak_youtube_fetch_title_via_oembed($videoId, false);

            $title = is_string($ytTitle) && $ytTitle !== '' ? $ytTitle : 'MY Nakliyat Video';

        }

        $desc = trim((string) ($meta['description'] ?? ''));

        if ($desc === '') {

            $desc = $title;

        }

        $isShort = !empty($meta['is_short']) || mynak_youtube_is_short_video($videoId, (string) ($meta['source_html'] ?? ''), $registry);

        $videoNode = mynak_schema_build_video_object_node(

            $videoId,

            $title,

            $desc,

            rtrim($canonical, '/'),

            $publisherId,

            (string) ($meta['upload_date'] ?? ''),

            (string) ($meta['source_html'] ?? ''),

            $isShort

        );

        $watchPage = mynak_schema_build_watch_webpage_node(rtrim($canonical, '/'), $title, $videoNode);

        if ($watchPage !== []) {

            $out .= seo_runtime_ld_script_from_array($watchPage);

        }



        return $out;

    }



    // Ilce/hizmet sayfalarinda VideoObject yok — izleme sayfasi /video/ID uzerinden.

    return '';

}


