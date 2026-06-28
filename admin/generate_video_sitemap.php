<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_web.php';
require_once dirname(__DIR__) . '/includes/video_sitemap_build.php';

$site_url = rtrim((string) SITE_URL, '/');
$projectRoot = dirname(__DIR__);
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run'])) {
    $result = mynak_write_video_sitemap_file($conn, $site_url, $projectRoot);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-video"></i> Video Sitemap Olustur</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Terminal olmadan <code>public_html/video-sitemap.xml</code> dosyasini uretir,
                        <code>sitemap-index.xml</code> dosyasini gunceller ve her video icin YouTube oEmbed
                        uzerinden gercek baslik ceker (7 gun onbellek).
                    </p>

                    <?php if (is_array($result)): ?>
                        <div class="alert alert-<?php echo $result['ok'] ? ($result['url_count'] > 0 ? 'success' : 'warning') : 'danger'; ?>">
                            <?php echo htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($result['ok']): ?>
                                <div class="mt-2 small">
                                    URL: <strong><?php echo (int) $result['url_count']; ?></strong>
                                    · Video: <strong><?php echo (int) $result['video_count']; ?></strong>
                                    · YouTube basligi: <strong><?php echo (int) ($result['youtube_titles'] ?? 0); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($result['ok']): ?>
                            <p class="mb-0">
                                <a href="<?php echo htmlspecialchars($site_url . '/video-sitemap.xml', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    <?php echo htmlspecialchars($site_url . '/video-sitemap.xml', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="post" class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-play"></i> video-sitemap.xml Olustur
                        </button>
                        <a href="seo_management.php" class="btn btn-outline-secondary ms-2">SEO Yonetimine Don</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
