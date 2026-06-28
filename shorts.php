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

$site_settings = mynak_site_settings_bootstrap($conn);
$siteUrl = rtrim((string) SITE_URL, '/');
$shorts = mynak_youtube_shorts_public_list($conn, $site_settings, $siteUrl);

$page_title = 'Kisa Videolar | MY Nakliyat';
$hide_title_suffix = true;
$page_meta_description = 'MY Nakliyat kisa videolari: Izmir evden eve nakliyat, paketleme ve tasima ipuclari. Google Kisa Videolar icin optimize edilmis rehberler.';
$allow_indexing = true;

$page = [
    'id' => 0,
    'slug' => 'shorts',
    'title' => 'Kisa Videolar',
    'type' => 'shorts_hub',
    'content' => '',
];

require_once __DIR__ . '/includes/header.php';
?>

<style>
.mynak-shorts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 24px;
}
.mynak-shorts-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}
.mynak-shorts-card__thumb {
    display: block;
    position: relative;
    aspect-ratio: 9 / 16;
    background: #111;
}
.mynak-shorts-card__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mynak-shorts-card__body {
    padding: 14px;
}
.mynak-shorts-card__body h2 {
    font-size: 1rem;
    margin: 0;
    line-height: 1.4;
}
</style>

<main id="content" class="padding">
    <div class="container">
        <header class="text-center mb-5">
            <p class="text-muted mb-2">YouTube Shorts</p>
            <h1 class="h2">Kisa videolar</h1>
            <p class="text-muted mt-2 mb-0">Izmir nakliyat rehberleri — paketleme, tasima ve bolge ipuclari.</p>
        </header>

        <?php if ($shorts === []): ?>
            <div class="alert alert-info text-center">
                Henuz kisa video eklenmedi. Admin &rarr; Site Ayarlari &rarr; <strong>mynak_youtube_shorts_ids</strong> alanina Shorts URL veya video ID girin.
            </div>
        <?php else: ?>
            <div class="mynak-shorts-grid">
                <?php foreach ($shorts as $item): ?>
                    <article class="mynak-shorts-card">
                        <a class="mynak-shorts-card__thumb" href="<?php echo htmlspecialchars((string) $item['watch_url']); ?>">
                            <img src="https://i.ytimg.com/vi/<?php echo htmlspecialchars((string) $item['youtube_id']); ?>/hqdefault.jpg"
                                alt="<?php echo htmlspecialchars((string) $item['title']); ?>"
                                loading="lazy" width="360" height="640">
                        </a>
                        <div class="mynak-shorts-card__body">
                            <h2>
                                <a href="<?php echo htmlspecialchars((string) $item['watch_url']); ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars((string) $item['title']); ?>
                                </a>
                            </h2>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
