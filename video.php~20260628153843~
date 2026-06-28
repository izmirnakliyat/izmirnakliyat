<?php
declare(strict_types=1);

if (!defined('MYNAK_BOOTSTRAP_LOADED')) {
    require_once __DIR__ . '/bootstrap.php';
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
}
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mynak_youtube_video_pages.php';
require_once __DIR__ . '/includes/mynak_video_object_schema.php';

$videoId = mynak_youtube_id_normalize_from_user_input((string) ($mynak_video_id ?? ''));
if ($videoId === '') {
    header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path('shorts')), true, 302);
    exit;
}

$site_settings = mynak_site_settings_bootstrap($conn);
$siteUrl = rtrim((string) SITE_URL, '/');
$registry = mynak_youtube_video_registry_collect($conn, $site_settings);
$meta = $registry[$videoId] ?? [
    'youtube_id' => $videoId,
    'title' => '',
    'description' => '',
    'upload_date' => '',
    'is_short' => mynak_youtube_is_short_video($videoId),
    'source_html' => '',
];

$ytTitle = mynak_youtube_fetch_title_via_oembed($videoId, false);
$videoTitle = trim((string) ($meta['title'] ?? ''));
if ($videoTitle === '' && is_string($ytTitle) && $ytTitle !== '') {
    $videoTitle = $ytTitle;
}
if ($videoTitle === '') {
    $videoTitle = 'MY Nakliyat Video';
}

$videoDesc = trim((string) ($meta['description'] ?? ''));
if ($videoDesc === '') {
    $videoDesc = $videoTitle;
}

$isShort = !empty($meta['is_short']) || mynak_youtube_is_short_video($videoId, (string) ($meta['source_html'] ?? ''), $registry);
$watchPageUrl = mynak_video_watch_page_url($siteUrl, $videoId);
$youtubeWatchUrl = mynak_video_youtube_content_url($videoId, (string) ($meta['source_html'] ?? ''));

$page_title = mb_substr($videoTitle . ($isShort ? ' | Kisa Video' : ' | Video'), 0, 70);
$hide_title_suffix = true;
$page_meta_description = mb_substr(
    $isShort
        ? $videoDesc . ' — MY Nakliyat kisa video rehberi. Izmir evden eve nakliyat.'
        : $videoDesc . ' — MY Nakliyat video rehberi.',
    0,
    160
);
$allow_indexing = true;

$page = [
    'id' => 0,
    'slug' => 'video/' . $videoId,
    'title' => $videoTitle,
    'type' => 'video_watch',
    'content' => '',
];

$mynak_video_watch_context = [
    'video_id' => $videoId,
    'is_short' => $isShort,
    'watch_page_url' => $watchPageUrl,
    'youtube_watch_url' => $youtubeWatchUrl,
];

require_once __DIR__ . '/includes/header.php';
?>

<style>
.mynak-video-landing {
    max-width: 960px;
    margin: 0 auto;
}
.mynak-video-landing--short {
    max-width: 420px;
}
.mynak-video-landing .mynak-video-frame--short {
    max-width: 360px;
    margin: 0 auto;
    aspect-ratio: 9 / 16;
}
.mynak-video-landing .mynak-video-frame.ratio-16x9 {
    aspect-ratio: 16 / 9;
}
.mynak-video-landing .mynak-video-frame iframe {
    width: 100%;
    height: 100%;
    border: 0;
    border-radius: 12px;
}
.mynak-video-landing__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
}
</style>

<main id="content" class="padding">
    <div class="container">
        <article class="mynak-video-landing<?php echo $isShort ? ' mynak-video-landing--short' : ''; ?>">
            <header class="mb-4 text-center">
                <?php if ($isShort): ?>
                    <p class="text-muted small mb-2">Kisa video</p>
                <?php endif; ?>
                <h1 class="h2"><?php echo htmlspecialchars($videoTitle); ?></h1>
                <?php if ($videoDesc !== '' && $videoDesc !== $videoTitle): ?>
                    <p class="text-muted mt-2 mb-0"><?php echo htmlspecialchars($videoDesc); ?></p>
                <?php endif; ?>
            </header>

            <?php echo mynak_youtube_watch_embed_iframe($videoId, $videoTitle, $isShort); ?>

            <div class="mynak-video-landing__actions justify-content-center">
                <a class="btn btn-primary" href="<?php echo htmlspecialchars($youtubeWatchUrl); ?>" target="_blank" rel="noopener noreferrer">
                    YouTube'da izle
                </a>
                <?php if ($isShort): ?>
                    <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('shorts'))); ?>">
                        Tum kisa videolar
                    </a>
                <?php endif; ?>
                <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('teklif-alin'))); ?>">
                    Ucretsiz teklif al
                </a>
            </div>
        </article>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
