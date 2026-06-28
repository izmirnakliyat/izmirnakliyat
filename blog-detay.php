<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once __DIR__ . '/includes/pipeline/page_content_blocks.php';
require_once __DIR__ . '/includes/mynak_wp_legacy_query_redirect.php';

// Doğrudan erişim: ?id= / ?slug= → kanonik slug veya /blog (SSOT)
if (empty($allow_indexing)) {
    mynak_public_try_blog_detay_legacy_redirect($conn);
}

// --- Normal akış (slug-router üzerinden gelindi) ---
$result = null;
if (isset($_GET['slug']) && is_string($_GET['slug']) && $_GET['slug'] !== '') {
    $slug = $conn->real_escape_string($_GET['slug']);
    $result = $conn->query("SELECT p.*, c.ad as kategori_adi, c.id as kategori_id, c.slug as kategori_slug 
                            FROM blog_posts p 
                            LEFT JOIN blog_categories c ON p.kategori_id = c.id 
                            WHERE p.slug = '$slug' AND p.durum = 3");
} elseif (isset($_GET['id']) && (int) $_GET['id'] > 0) {
    $id = (int) $_GET['id'];
    $result = $conn->query("SELECT p.*, c.ad as kategori_adi, c.id as kategori_id, c.slug as kategori_slug 
                        FROM blog_posts p 
                        LEFT JOIN blog_categories c ON p.kategori_id = c.id 
                        WHERE p.id = $id AND p.durum = 3");
} else {
    header('Location: ' . rtrim(SITE_URL, '/') . '/blog', true, 301);
    exit;
}

// Query hatası veya boş sonuç: /blog'a 301 (5xx üretme)
if (!($result instanceof mysqli_result) || $result->num_rows === 0) {
    header('Location: ' . rtrim(SITE_URL, '/') . '/blog', true, 301);
    exit;
}

$blog = $result->fetch_assoc();
$id = $blog['id'];

// Benzer yazıları getir (aynı kategoriden)
$similar_posts = null;
if (!empty($blog['kategori_id'])) {
    $similar_posts = $conn->query("SELECT id, baslik, kapak_foto, created_at, slug FROM blog_posts WHERE id != $id AND kategori_id = {$blog['kategori_id']} AND durum = 3 ORDER BY created_at DESC LIMIT 3");
}

// Eğer aynı kategoriden yazı bulunamazsa veya kategori yoksa, en son yazıları getir
if (!$similar_posts || $similar_posts->num_rows < 3) {
    $similar_posts = $conn->query("SELECT id, baslik, kapak_foto, created_at, slug FROM blog_posts WHERE id != $id AND durum = 3 ORDER BY created_at DESC LIMIT 3");
}

// Sayfa başlık bilgilerini ayarla (bu değişken header.php tarafından kullanılacak)
if (isset($blog['baslik'])) {
    $page_title = !empty($blog['seo_title']) ? $blog['seo_title'] : $blog['baslik'];
    if (function_exists('mynak_normalize_public_page_title')) {
        $page_title = mynak_normalize_public_page_title((string) $page_title);
    }
}

// Meta Açıklama
require_once __DIR__ . '/includes/mynak_meta_description.php';
if (!empty($blog['meta_description'])) {
    $page_meta_description = mynak_meta_description_clamp((string) $blog['meta_description'], 160);
} else {
    $clean_content = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($blog['icerik'] ?? ''))) ?? '');
    $page_meta_description = $clean_content !== ''
        ? mynak_meta_description_clamp($clean_content, 160)
        : mynak_default_meta_description_for_page((string) ($blog['baslik'] ?? ''), (string) ($blog['slug'] ?? ''));
}

// Meta Anahtar Kelimeler
if (!empty($blog['meta_keywords'])) {
    $page_meta_keywords = $blog['meta_keywords'];
} else {
    // Varsayılan: Başlık ve etiketler
    $page_meta_keywords = str_replace(' ', ', ', $blog['baslik']);
    if (!empty($blog['etiketler'])) {
        $page_meta_keywords .= ', ' . $blog['etiketler'];
    }
}


// Türkçe aylar
function turkish_month($en_month)
{
    $months = [
        'Jan' => 'Oca',
        'Feb' => 'Şub',
        'Mar' => 'Mar',
        'Apr' => 'Nis',
        'May' => 'May',
        'Jun' => 'Haz',
        'Jul' => 'Tem',
        'Aug' => 'Ağu',
        'Sep' => 'Eyl',
        'Oct' => 'Eki',
        'Nov' => 'Kas',
        'Dec' => 'Ara'
    ];
    return $months[$en_month] ?? $en_month;
}

// LCP: kapak varsa tek preload (header.php ile aynı URL; placeholder kapakta preload yok)
$mynak_lcp_preload_href = '';
$mynak_lcp_preload_type = '';
if (!empty($blog['kapak_foto']) && function_exists('blog_kapak_full_url')) {
    $mynak_lcp_preload_href = blog_kapak_full_url($blog['kapak_foto']);
    $p = (string) (parse_url($mynak_lcp_preload_href, PHP_URL_PATH) ?? '');
    $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
    $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'avif' => 'image/avif', 'gif' => 'image/gif'];
    $mynak_lcp_preload_type = $mimeMap[$ext] ?? 'image/jpeg';
}

require_once 'includes/header.php';
?>

<!-- Blog Banner Section -->
<section class="page-banner">
    <div class="container">
        <div class="banner-content text-center">
            <h2 class="banner-title">Blog</h2>
            <div class="breadcrumb">
                <a href="<?php echo htmlspecialchars(mynak_public_path(''), ENT_QUOTES, 'UTF-8'); ?>">Ana Sayfa</a> <span class="separator">/</span>
                <a href="<?php echo htmlspecialchars(mynak_public_path('blog'), ENT_QUOTES, 'UTF-8'); ?>">Blog</a> <span class="separator">/</span>
                <span class="current"><?php echo mynak_esc_html((string) $blog['baslik']); ?></span>
            </div>
        </div>
    </div>
</section>

<!-- Blog Detail Section -->
<section class="blog-detail-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <article class="blog-detail">
                    <div class="blog-detail-image">
                            <img src="<?php echo htmlspecialchars(blog_kapak_full_url($blog['kapak_foto'] ?? '')); ?>"
                                alt="<?php echo mynak_esc_html(mynak_public_image_alt((string) $blog['baslik'], 'MY Nakliyat blog kapak görseli', blog_kapak_full_url($blog['kapak_foto'] ?? ''))); ?>"
                                loading="eager" fetchpriority="high" decoding="async"
                                style="width: 100%; height: 100%; object-fit: contain;">
                            <div class="blog-meta">
                                <?php if (!empty($blog['kategori_adi'])): ?>
                                    <div class="blog-category">
                                        <?php
                                        $katHref = !empty($blog['kategori_slug'])
                                            ? '/blog/kategori/' . rawurlencode((string) $blog['kategori_slug']) . '/'
                                            : ('/blog?kategori=' . (int) ($blog['kategori_id'] ?? 0));
                                        ?>
                                        <a href="<?php echo htmlspecialchars(normalize_internal_link_url($katHref)); ?>"><?php echo mynak_esc_html((string) $blog['kategori_adi']); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <h1 class="blog-title"><?php echo mynak_esc_html((string) $blog['baslik']); ?></h1>

                    <div class="blog-content">
                        <?php
                        $GLOBALS['mynak_img_alt_context'] = trim((string) ($blog['baslik'] ?? '')) . ' — MY Nakliyat blog';
                        echo mynak_blok_isle($conn, (string) $blog['icerik']);
                        unset($GLOBALS['mynak_img_alt_context']);
                        ?>
                    </div>

                    <?php
                    $blogSlugCurrent = trim((string) ($blog['slug'] ?? ''));
                    if ($blogSlugCurrent === 'izmir-evden-eve-nakliyat-yorumlari') {
                        require_once __DIR__ . '/includes/mynak_google_reviews.php';
                        $gbpReviewsData = mynak_google_reviews_fetch($conn, 30);
                        if (is_array($gbpReviewsData)) {
                            echo mynak_google_reviews_render($gbpReviewsData, 'grid');
                        }
                    }
                    if (!function_exists('seo_runtime_primary_services_links_html')) {
                        require_once __DIR__ . '/includes/seo_runtime/internal_linking.php';
                    }
                    if (function_exists('seo_runtime_primary_services_links_html')) {
                        echo seo_runtime_primary_services_links_html($blogSlugCurrent);
                    }
                    ?>

                    <?php if (!empty($blog['etiketler'])): ?>
                        <div class="blog-tags">
                            <h5>Etiketler:</h5>
                            <div class="tags-list">
                                <?php
                                $tags = explode(',', $blog['etiketler']);
                                foreach ($tags as $tag):
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                        $tag_slug = slug_olustur($tag);
                                        ?>
                                        <a href="<?php echo SITE_URL; ?>/blog/etiket/<?php echo $tag_slug; ?>/"
                                            class="tag-link"><?php echo mynak_esc_html($tag); ?></a>
                                        <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="blog-navigation">
                        <div class="row">
                            <?php
                            // Önceki yazı
                            $prev_post = $conn->query("SELECT id, baslik, slug FROM blog_posts WHERE id < $id AND durum = 3 ORDER BY id DESC LIMIT 1");
                            if ($prev = $prev_post->fetch_assoc()):
                                ?>
                                <div class="col-6">
                                    <a href="<?php echo htmlspecialchars(mynak_public_path($prev['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link prev">
                                        <span class="nav-title"><i class="fas fa-arrow-left"></i> Önceki Yazı</span>
                                        <h6><?php echo mynak_esc_html((string) $prev['baslik']); ?></h6>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Sonraki yazı
                            $next_post = $conn->query("SELECT id, baslik, slug FROM blog_posts WHERE id > $id AND durum = 3 ORDER BY id ASC LIMIT 1");
                            if ($next = $next_post->fetch_assoc()):
                                ?>
                                <div class="col-6">
                                    <a href="<?php echo htmlspecialchars(mynak_public_path($next['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link next">
                                        <span class="nav-title">Sonraki Yazı <i class="fas fa-arrow-right"></i></span>
                                        <h6><?php echo mynak_esc_html((string) $next['baslik']); ?></h6>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>

            <div class="col-lg-4">
                <div class="blog-sidebar">
                    <!-- Benzer Yazılar -->
                    <div class="sidebar-widget related-posts-widget">
                        <h4 class="widget-title">Benzer Yazılar</h4>
                        <div class="related-posts">
                            <?php while ($similar = $similar_posts->fetch_assoc()): ?>
                                <div class="related-post">
                                    <div class="post-image">
                                        <img src="<?php echo htmlspecialchars(blog_kapak_full_url($similar['kapak_foto'] ?? '')); ?>"
                                                alt="<?php echo mynak_esc_html(mynak_public_image_alt((string) $similar['baslik'], 'MY Nakliyat blog yazısı', blog_kapak_full_url($similar['kapak_foto'] ?? ''))); ?>"
                                                loading="lazy" decoding="async"
                                                style="width: 100%; height: 100%; object-fit: contain;">

                                    </div>
                                    <div class="post-info">
                                        <h6 class="post-title">
                                            <a href="<?php echo htmlspecialchars(mynak_public_path($similar['slug']), ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo mynak_esc_html((string) $similar['baslik']); ?>
                                            </a>
                                        </h6>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Paylaş -->
                    <div class="sidebar-widget share-widget">
                        <h4 class="widget-title">Paylaş</h4>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                                target="_blank" class="facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($blog['baslik']); ?>"
                                target="_blank" class="twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($blog['baslik']); ?>"
                                target="_blank" class="linkedin">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="https://wa.me/?text=<?php echo urlencode($blog['baslik'] . ' - https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                                target="_blank" class="whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Yazıya Dön -->
                    <div class="sidebar-widget cta-widget">
                        <h4>Blog Yazılarımızı Keşfedin</h4>
                        <p>Okuma deneyiminizi diğer blog yazılarımızla sürdürebilirsiniz.</p>
                        <a href="<?php echo htmlspecialchars(normalize_internal_link_url('/blog')); ?>" class="btn btn-primary">Tüm Yazılar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Blog Detail Styles */
    .page-banner {
        background: #f8f9fa;
        padding: 80px 0;
        margin-bottom: 60px;
    }

    .page-banner .banner-title {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    /* H1 etiketinin SEO için optimize edilmesi */
    .blog-detail .blog-title {
        font-size: 32px;
        font-weight: 700;
        margin: 30px 0 20px;
        padding: 0 30px;
        line-height: 1.3;
        color: #333;
        /* SEO için önemli: H1 etiketi net görünür olmalı */
    }

    .page-banner .breadcrumb {
        display: flex;
        justify-content: center;
        font-size: 16px;
    }

    .page-banner .breadcrumb a {
        color: #333;
        text-decoration: none;
    }

    .page-banner .breadcrumb .separator {
        margin: 0 10px;
        color: #555;
    }

    .page-banner .breadcrumb .current {
        color: #0056b3;
    }

    .blog-detail-section {
        padding: 0 0 80px;
    }

    .blog-detail {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
        margin-bottom: 40px;
    }

    .blog-detail-image {
        position: relative;
        height: 500px;
        background-color: #f8f9fa;
        overflow: hidden;
    }

    .blog-detail-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .blog-meta {
        position: absolute;
        top: 20px;
        display: flex;
        gap: 15px;
        padding: 0 20px;
        width: 100%;
    }



    .blog-category {
        background: rgba(255, 255, 255, 0.9);
        padding: 5px 15px;
        border-radius: 5px;
        align-self: flex-start;
    }

    .blog-category a {
        color: #0056b3;
        font-weight: 600;
        text-decoration: none;
        font-size: 14px;
    }

    .blog-detail .blog-title {
        font-size: 28px;
        font-weight: 700;
        margin: 30px 0 20px;
        padding: 0 30px;
        line-height: 1.4;
    }

    .blog-content {
        padding: 0 30px 30px;
        line-height: 1.8;
        color: #444;
    }

    .blog-content p {
        margin-bottom: 20px;
    }

    .blog-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 20px 0;
    }

    .blog-content h2,
    .blog-content h3,
    .blog-content h4 {
        margin-top: 30px;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .blog-content h2 {
        font-size: 24px;
    }

    .blog-content h3 {
        font-size: 20px;
    }

    .blog-content h4 {
        font-size: 18px;
    }

    .blog-content ul,
    .blog-content ol {
        margin-bottom: 20px;
        padding-left: 20px;
    }

    .blog-content li {
        margin-bottom: 10px;
    }

    .blog-content figure {
        margin: 20px 0;
        text-align: center;
    }

    .blog-content figure img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }

    .blog-content figure figcaption {
        color: #666;
        font-size: 14px;
        margin-top: 8px;
    }

    .blog-content blockquote {
        border-left: 4px solid #0056b3;
        padding: 15px 20px;
        margin: 20px 0;
        background: #f8f9fa;
        font-style: italic;
    }

    .blog-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    .blog-content table th,
    .blog-content table td {
        border: 1px solid #ddd;
        padding: 10px;
    }

    .blog-tags {
        padding: 20px 30px;
        border-top: 1px solid #eee;
    }

    .blog-tags h5 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .tag-link {
        display: inline-block;
        padding: 5px 12px;
        background: #f5f5f5;
        color: #555;
        border-radius: 5px;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.3s;
    }

    .tag-link:hover {
        background: #0056b3;
        color: #fff;
    }

    .blog-navigation {
        padding: 20px 30px;
        border-top: 1px solid #eee;
    }

    .nav-link {
        padding: 15px;
        border-radius: 8px;
        background: #f8f9fa;
        display: block;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        height: 100%;
    }

    .nav-link:hover {
        background: #0056b3;
        color: #fff;
    }

    .nav-link.prev {
        text-align: left;
    }

    .nav-link.next {
        text-align: right;
    }

    .nav-title {
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .nav-link h6 {
        margin: 0;
        font-size: 16px;
    }

    /* Sidebar Styles */
    .blog-sidebar {
        position: sticky;
        top: 30px;
    }

    .sidebar-widget {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
    }

    .widget-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .related-posts {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .related-post {
        display: flex;
        gap: 15px;
    }

    .post-image {
        width: 90px;
        height: 90px;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
    }

    .post-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }



    .post-info {
        flex-grow: 1;
    }

    .post-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
        line-height: 1.4;
    }

    .post-title a {
        color: #333;
        text-decoration: none;
        transition: color 0.3s;
    }

    .post-title a:hover {
        color: #0056b3;
    }

    .share-buttons {
        display: flex;
        gap: 10px;
    }

    .share-buttons a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: #fff;
        font-size: 16px;
        text-decoration: none;
        transition: all 0.3s;
    }

    .share-buttons a:hover {
        transform: translateY(-3px);
    }

    .share-buttons .facebook {
        background: #3b5998;
    }

    .share-buttons .twitter {
        background: #1da1f2;
    }

    .share-buttons .linkedin {
        background: #0077b5;
    }

    .share-buttons .whatsapp {
        background: #25d366;
    }

    .cta-widget {
        text-align: center;
        background: #f8f9fa;
    }

    .cta-widget h4 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .cta-widget p {
        margin-bottom: 20px;
        color: #555;
    }

    .cta-widget .btn {
        display: inline-block;
        padding: 12px 25px;
        background: #0056b3;
        color: #fff;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }

    .cta-widget .btn:hover {
        background: #003d82;
        transform: translateY(-3px);
    }

    @media (max-width: 991px) {
        .blog-sidebar {
            margin-top: 40px;
            position: static;
            top: auto;
        }

        .blog-detail-section .row {
            flex-direction: column;
        }

        .blog-detail-section .col-lg-8 {
            width: 100%;
            max-width: 100%;
            order: 1;
        }

        .blog-detail-section .col-lg-4 {
            width: 100%;
            max-width: 100%;
            order: 2;
        }
    }

    @media (max-width: 767px) {
        .page-banner {
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .page-banner .banner-title {
            font-size: 28px;
        }

        .blog-detail-image {
            height: 300px;
        }

        .blog-detail .blog-title {
            font-size: 24px;
            padding: 0 20px;
        }

        .blog-content {
            padding: 0 20px 20px;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>