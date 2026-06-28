<?php
declare(strict_types=1);

/**
 * Ana sayfa (index) iş mantığı — DB burada; görünüm yalnızca dizileri basar.
 *
 * @return array{
 *   section_content: array<string, array<string, mixed>>,
 *   running_texts: list<string>,
 *   gallery_cover_image: string,
 *   hero_slides: list<array<string, mixed>>,
 *   projects_gallery_images: list<array<string, mixed>>,
 *   home_google_reviews: array<string, mixed>|null,
 *   home_sponsors: list<array<string, mixed>>,
 *   home_blog_posts: list<array<string, mixed>>,
 *   home_videos: list<array{youtube_id:string,name:string,description:string,upload_date:string}>
 * }
 */
function mynak_home_index_view_model(mysqli $conn): array
{
    $section_content = [];
    $sections_result = $conn->query('SELECT * FROM homepage_sections_content WHERE status = 1');
    if ($sections_result && $sections_result->num_rows > 0) {
        while ($section = $sections_result->fetch_assoc()) {
            foreach ($section as $sk => $sv) {
                if (!is_string($sv) || $sv === '') {
                    continue;
                }
                $section[$sk] = ($sk === 'description' || str_ends_with($sk, '_html'))
                    ? mynak_normalize_rich_html($sv)
                    : mynak_normalize_db_text($sv);
            }
            $section_content[$section['section_key']] = $section;
        }
    }

    $running_texts = [];
    $rt_result = $conn->query("SELECT `value` FROM settings WHERE `name` = 'homepage_running_text' LIMIT 1");
    if ($rt_result && $rt_row = $rt_result->fetch_assoc()) {
        $running_texts = array_values(array_unique(array_filter(array_map(
            static function ($t): string {
                return mynak_normalize_db_text(trim((string) $t));
            },
            explode('|', (string) $rt_row['value'])
        ))));
    }

    $gallery_cover_image = '';
    $cover_result = $conn->query("SELECT value FROM settings WHERE name = 'gallery_cover_image' LIMIT 1");
    if ($cover_result && $cover_row = $cover_result->fetch_assoc()) {
        $gallery_cover_image = (string) $cover_row['value'];
    }

    $hero_slides = [];
    $slides_q = $conn->query('SELECT id, title, subtitle, button1_text, button1_link, button2_text, button2_link, image, image2, bg_color, order_number FROM slides WHERE status = 1 ORDER BY order_number ASC');
    if ($slides_q && $slides_q->num_rows > 0) {
        while ($row = $slides_q->fetch_assoc()) {
            foreach (['title', 'subtitle', 'button1_text', 'button2_text'] as $sf) {
                if (isset($row[$sf]) && is_string($row[$sf])) {
                    $row[$sf] = mynak_normalize_db_text($row[$sf]);
                }
            }
            $hero_slides[] = $row;
        }
    }

    $projects_gallery_images = [];
    if (isset($section_content['projects'])) {
        $gallery_result = $conn->query('SELECT * FROM gallery WHERE status = 1 ORDER BY order_number ASC, id DESC LIMIT 8');
        if ($gallery_result && $gallery_result->num_rows > 0) {
            while ($row = $gallery_result->fetch_assoc()) {
                $projects_gallery_images[] = $row;
            }
        }
    }

    $home_google_reviews = null;
    require_once dirname(__DIR__) . '/mynak_google_reviews.php';
    $gbpFetched = mynak_google_reviews_fetch($conn, 8);
    if (is_array($gbpFetched)) {
        $gbpFetched['gr_marquee_sec'] = max(36, min(100, count($gbpFetched['reviews_rows']) * 14));
        $home_google_reviews = $gbpFetched;
    }

    $home_sponsors = [];
    $sponsors_result = $conn->query('SELECT * FROM sponsors WHERE status = 1 ORDER BY order_number ASC, id DESC');
    if ($sponsors_result && $sponsors_result->num_rows > 0) {
        while ($row = $sponsors_result->fetch_assoc()) {
            $home_sponsors[] = $row;
        }
    }

    $home_blog_posts = [];
    $blog_query = 'SELECT p.*, c.ad as kategori_adi, c.slug as kategori_slug
        FROM blog_posts p
        LEFT JOIN blog_categories c ON p.kategori_id = c.id
        WHERE p.durum = 3
        ORDER BY p.created_at DESC LIMIT 3';
    $blog_result = $conn->query($blog_query);
    if ($blog_result && $blog_result->num_rows > 0) {
        while ($row = $blog_result->fetch_assoc()) {
            $plain = mynak_decode_html_entities(strip_tags((string) ($row['icerik'] ?? '')));
            $row['excerpt_plain'] = (function_exists('mb_strlen') && mb_strlen($plain, 'UTF-8') > 100)
                ? mb_substr($plain, 0, 100, 'UTF-8') . '...'
                : ((strlen($plain) > 100) ? substr($plain, 0, 100) . '...' : $plain);
            $anchor = mynak_decode_html_entities((string) ($row['baslik'] ?? ''));
            if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($anchor, 'UTF-8') > 56) {
                $anchor = mb_substr($anchor, 0, 56, 'UTF-8') . '…';
            } elseif (strlen($anchor) > 56) {
                $anchor = substr($anchor, 0, 56) . '…';
            }
            $row['read_more_anchor'] = $anchor . ' — yazının tamamını okuyun';
            $home_blog_posts[] = $row;
        }
    }

    require_once dirname(__DIR__) . '/mynak_home_videos.php';
    $homeVideoSettings = [];
    $hvRes = $conn->query("SELECT name, value FROM settings WHERE name = 'mynak_home_youtube_ids' LIMIT 1");
    if ($hvRes && ($hvRow = $hvRes->fetch_assoc())) {
        $homeVideoSettings['mynak_home_youtube_ids'] = (string) ($hvRow['value'] ?? '');
    }
    if ($hvRes) {
        $hvRes->free();
    }
    $home_videos = mynak_home_videos_padded_list($homeVideoSettings);

    return [
        'section_content' => $section_content,
        'running_texts' => $running_texts,
        'gallery_cover_image' => $gallery_cover_image,
        'hero_slides' => $hero_slides,
        'projects_gallery_images' => $projects_gallery_images,
        'home_google_reviews' => $home_google_reviews,
        'home_sponsors' => $home_sponsors,
        'home_blog_posts' => $home_blog_posts,
        'home_videos' => $home_videos,
    ];
}
