<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// mysqlnd yok / eski functions.php — stmt sonucu alınamazsa 500
if (!function_exists('mysqli_stmt_fetch_all_assoc')) {
    function mysqli_stmt_fetch_all_assoc($stmt)
    {
        $rows = [];
        if (!($stmt instanceof mysqli_stmt)) {
            return $rows;
        }
        if (function_exists('mysqli_stmt_get_result')) {
            $result = @mysqli_stmt_get_result($stmt);
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $result->free();
            }
            return $rows;
        }
        if (!@$stmt->store_result()) {
            return $rows;
        }
        $meta = $stmt->result_metadata();
        if (!$meta) {
            return $rows;
        }
        $fields = [];
        $row = [];
        $bind = [];
        while ($field = $meta->fetch_field()) {
            $fields[] = $field->name;
            $bind[] = &$row[$field->name];
        }
        $meta->free();
        if ($fields === [] || !call_user_func_array([$stmt, 'bind_result'], $bind)) {
            return $rows;
        }
        while ($stmt->fetch()) {
            $copy = [];
            foreach ($fields as $f) {
                $copy[$f] = $row[$f];
            }
            $rows[] = $copy;
        }
        return $rows;
    }
}

// Canlıda eski functions.php yüklenirse etiket/kategori listesi 500 vermesin
if (!function_exists('mysqli_stmt_bind_params_safe')) {
    function mysqli_stmt_bind_params_safe($stmt, $types, array $params)
    {
        if ($types === '' && $params === []) {
            return true;
        }
        if (!is_object($stmt) || !method_exists($stmt, 'bind_param')) {
            return false;
        }
        if (strlen($types) !== count($params)) {
            return false;
        }
        $bind = [$types];
        foreach (array_keys($params) as $k) {
            $bind[] = &$params[$k];
        }
        return (bool) call_user_func_array([$stmt, 'bind_param'], $bind);
    }
}

require_once __DIR__ . '/includes/mynak_wp_legacy_query_redirect.php';
mynak_public_try_wp_legacy_query_redirect($conn);

// --- 301 ve erken çıkışlar: header.php öncesi (headers already sent riski yok) ---
// Eski URL formatlarını 301 yönlendirme ile SEO dostu URL'ye yönlendir

// Eski ?kategori=ID sorgu formatı → güzel URL (yalnızca query string'de kategori= varken; slug-router çakışmasın)
$qs = $_SERVER['QUERY_STRING'] ?? '';
if (
    isset($_GET['kategori'])
    && (is_string($_GET['kategori']) || is_numeric($_GET['kategori']))
    && (int) $_GET['kategori'] > 0
    && strpos($qs, 'kategori=') !== false
) {
    $cat_id = (int) $_GET['kategori'];
    $cat_stmt = $conn->prepare("SELECT id, ad, slug FROM blog_categories WHERE id = ?");
    if (!$cat_stmt) {
        // prepare başarısız; devam et (sayfa yine yüklensin)
    } else {
    $cat_stmt->bind_param("i", $cat_id);
    $cat_stmt->execute();
    $cat_rows = mysqli_stmt_fetch_all_assoc($cat_stmt);
    $cat_stmt->close();
    if (!empty($cat_rows)) {
        $cat_row = $cat_rows[0];
        $pageNum = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $suf = 'kategori/' . rawurlencode($cat_row['slug']);
        if ($pageNum > 1) {
            $suf .= '/sayfa/' . $pageNum;
        }
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path($suf)), true, 301);
        exit;
    }
    }
}

// Eski ?tag= → /blog/etiket/{slug}/
if (
    isset($_GET['tag'])
    && is_string($_GET['tag'])
    && $_GET['tag'] !== ''
) {
    $tag_input = (string) $_GET['tag'];
    if (strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '/blog/etiket/') === false) {
        $tag_slug = slug_olustur($tag_input);
        if ($tag_slug !== '') {
            $pageRaw = $_GET['page'] ?? 1;
            $pageNum = (is_string($pageRaw) || is_numeric($pageRaw)) ? max(1, (int) $pageRaw) : 1;
            $suf = 'etiket/' . rawurlencode($tag_slug);
            if ($pageNum > 1) {
                $suf .= '/sayfa/' . $pageNum;
            }
            header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path($suf)), true, 301);
            exit;
        }
    }
}

// Eski ?kategori_slug=X (sorgu tabanlı kategori filtresi) → /blog/kategori/X path-canonical 301
if (
    isset($_GET['kategori_slug'])
    && is_string($_GET['kategori_slug'])
    && $_GET['kategori_slug'] !== ''
    && strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '/blog/kategori/') === false
) {
    $catSlugRaw = (string) $_GET['kategori_slug'];
    $catSlug = function_exists('slug_olustur') ? slug_olustur($catSlugRaw) : strtolower(trim($catSlugRaw));
    if ($catSlug !== '') {
        $pageNum = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $suf = 'kategori/' . rawurlencode($catSlug);
        if ($pageNum > 1) {
            $suf .= '/sayfa/' . $pageNum;
        }
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path($suf)), true, 301);
        exit;
    }
}

// /blog?page=N (tek başına, tag/kategori filtresi yok) → kanonik path 301.
//   page=1 → /blog        (GSC "alternatif sayfa" raporundan düşür)
//   page=N → /blog/sayfa/N (GSC "Robots.txt tarafından engellendi" raporundan düşür — artık Allow + 301)
// REQUEST_URI içinde /sayfa/ varsa atla (path-based sayfalama zaten yönlendiriliyor).
if (
    isset($_GET['page'])
    && (is_string($_GET['page']) || is_numeric($_GET['page']))
    && !isset($_GET['tag'])
    && !isset($_GET['kategori'])
    && !isset($_GET['kategori_slug'])
    && strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '/sayfa/') === false
) {
    $pnReq = (int) $_GET['page'];
    if ($pnReq < 1) {
        // Geçersiz/0/negatif → /blog
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
        exit;
    }
    if ($pnReq === 1) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
        exit;
    }
    header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('sayfa/' . $pnReq)), true, 301);
    exit;
}

// header.php varsayılanı allow_indexing=true olduğundan blog akışında açıkça false ile başla
$allow_indexing = false;
$meta_robots = null;

// Sayfalama için değişkenler
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Filtreleme
$where = "WHERE durum = 3";
$params = [];
$current_category = null;
$current_tag = null;
$page_title = 'Blog Yazıları';
$page_meta_description = '';

// Kategori slug ile filtreleme (SEO dostu URL)
if (isset($_GET['kategori_slug']) && !empty($_GET['kategori_slug'])) {
    $cat_slug = $_GET['kategori_slug'];
    $cat_stmt = $conn->prepare("SELECT id, ad, slug FROM blog_categories WHERE slug = ?");
    if ($cat_stmt) {
    $cat_stmt->bind_param("s", $cat_slug);
    $cat_stmt->execute();
    $cat_rows = mysqli_stmt_fetch_all_assoc($cat_stmt);
    $cat_stmt->close();
    if (!empty($cat_rows)) {
        $cat_row = $cat_rows[0];
        $where .= " AND kategori_id = ?";
        $params[] = (int) $cat_row['id'];
        $current_category = $cat_row;

        $page_title = $cat_row['ad'] . ' Yazıları';
        $page_meta_description = $cat_row['ad'] . ' kategorisindeki blog yazıları. MY Nakliyat ' . $cat_row['ad'] . ' hizmetleri ve bilgileri.';
    }
    }
} elseif (isset($_GET['kategori']) && (int) $_GET['kategori'] > 0) {
    // slug-router: /blog/kategori/slug/ → $_GET['kategori'] = id
    $cat_id = (int) $_GET['kategori'];
    $cat_stmt = $conn->prepare("SELECT id, ad, slug FROM blog_categories WHERE id = ?");
    if ($cat_stmt) {
    $cat_stmt->bind_param("i", $cat_id);
    $cat_stmt->execute();
    $cat_rows = mysqli_stmt_fetch_all_assoc($cat_stmt);
    $cat_stmt->close();
    if (!empty($cat_rows)) {
        $cat_row = $cat_rows[0];
        $where .= " AND kategori_id = ?";
        $params[] = $cat_id;
        $current_category = $cat_row;
        $page_title = $cat_row['ad'] . ' Yazıları';
        $page_meta_description = $cat_row['ad'] . ' kategorisindeki blog yazıları. MY Nakliyat ' . $cat_row['ad'] . ' hizmetleri ve bilgileri.';
    }
    }
}

// Tag işleme (SEO dostu URL)
if (isset($_GET['tag']) && !empty($_GET['tag'])) {
    $tag_input = $_GET['tag'];
    // Slug'dan orijinal etiketi bul
    $current_tag = mynak_find_tag_by_slug($conn, $tag_input);
    if (!$current_tag) {
        $current_tag = urldecode($tag_input);
    }

    if ($current_tag) {
        // Virgül ayracına göre mümkün olduğunca önek eşleşme (tek başına %…% taramasından daha dar)
        $t = $current_tag;
        $where .= ' AND (etiketler = ? OR etiketler LIKE ? OR etiketler LIKE ? OR etiketler LIKE ? OR etiketler LIKE ? OR etiketler LIKE ? OR etiketler LIKE ?)';
        $params[] = $t;
        $params[] = $t . ',%';
        $params[] = '%, ' . $t . ',%';
        $params[] = '%, ' . $t;
        $params[] = '%,' . $t . ',%';
        $params[] = '%,' . $t;
        $params[] = '%' . $t . '%';

        // Başlık ve Açıklama Güncelle
        $page_title = $current_tag . ' Etiketli Yazılar';
        $page_meta_description = $current_tag . ' etiketi ile ilgili blog yazıları ve makaleler.';
    }
}

// Sayfalama bilgisini başlığa/açıklamaya ekle
if ($page > 1) {
    // Header.php zaten başlığa ekliyor ama meta description için biz ekleyelim
    $page_meta_description .= ' - Sayfa ' . $page;
}

// Varsayılan açıklama eğer boşsa
if (empty($page_meta_description)) {
    $page_meta_description = "MY Nakliyat blog: Evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya taşıma ve şehirler arası nakliyat hakkında güncel rehberler, ipuçları ve haberler.";
    if ($page > 1) {
        $page_meta_description .= ' - Sayfa ' . $page;
    }
}

// İndeks politikası (Google: faceted listeler çoğaltır; odağı yazılara ver)
// Yalnızca /blog ana listesi sayfa 1 indexlenebilir; etiket/kategori/sayfalama = noindex, follow (header pipeline).
if (!$current_tag && !$current_category && $page <= 1) {
    $allow_indexing = true;
}

// QUERY_STRING’de ?page= / ?tag= / ?kategori= → noindex, follow (robots disallow ile hizalı; rewrite ile gelen $_GET hariç)
if (function_exists('mynak_blog_query_string_has_seo_param_keys') && mynak_blog_query_string_has_seo_param_keys()) {
    $meta_robots = 'noindex, follow';
    $allow_indexing = false;
}

// Toplam sayfa — header.php ÖNCESİ: geçersiz sayfa 301 (çıktı başlamadan Location gönderilmeli)
$count_sql = "SELECT COUNT(*) as total FROM blog_posts $where";
$stmt = $conn->prepare($count_sql);
$total_posts = 0;
if ($stmt) {
    $bound = true;
    if (!empty($params)) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : 's';
        }
        $bound = mysqli_stmt_bind_params_safe($stmt, $types, $params);
    }
    if ($bound) {
        $stmt->execute();
        $count_rows = mysqli_stmt_fetch_all_assoc($stmt);
        $total_posts = isset($count_rows[0]['total']) ? (int) $count_rows[0]['total'] : 0;
    }
    $stmt->close();
}
$total_pages = $per_page > 0 ? (int) ceil($total_posts / $per_page) : 0;

/**
 * Googlebot / eski linkler: var olmayan sayfa numarası → son geçerli sayfaya 301.
 */
if ($total_pages > 0 && $page > $total_pages) {
    $pn = $total_pages;
    if ($current_category) {
        $suf = 'kategori/' . rawurlencode((string) $current_category['slug']);
        if ($pn > 1) {
            $suf .= '/sayfa/' . $pn;
        }
        $u = mynak_abs_url_from_public_path(mynak_blog_href_path($suf));
    } elseif ($current_tag) {
        $tagSeg = !empty($_GET['tag']) ? slug_olustur((string) $_GET['tag']) : slug_olustur((string) $current_tag);
        $suf = 'etiket/' . rawurlencode($tagSeg);
        if ($pn > 1) {
            $suf .= '/sayfa/' . $pn;
        }
        $u = mynak_abs_url_from_public_path(mynak_blog_href_path($suf));
    } else {
        $u = mynak_abs_url_from_public_path(
            $pn > 1 ? mynak_blog_href_path('sayfa/' . $pn) : mynak_blog_href_path('')
        );
    }
    header('Location: ' . $u, true, 301);
    exit;
}
if ($total_pages === 0 && $page > 1) {
    if ($current_category) {
        $u = mynak_abs_url_from_public_path(
            mynak_blog_href_path('kategori/' . rawurlencode((string) $current_category['slug']))
        );
    } elseif ($current_tag) {
        $tagSeg = !empty($_GET['tag']) ? slug_olustur((string) $_GET['tag']) : slug_olustur((string) $current_tag);
        $u = mynak_abs_url_from_public_path(mynak_blog_href_path('etiket/' . rawurlencode($tagSeg)));
    } else {
        $u = mynak_abs_url_from_public_path(mynak_blog_href_path(''));
    }
    header('Location: ' . $u, true, 301);
    exit;
}

// Boş etiket/kategori (0 yazı) → 410 Gone (GSC noindex raporundan düşür)
// Ana /blog listesi etkilenmez; sadece etiket veya kategori filtresi aktifken.
if ($total_posts === 0 && ($current_tag !== null || $current_category !== null) && $page <= 1) {
    http_response_code(410);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow', true);
    $blogHref = htmlspecialchars(mynak_abs_url_from_public_path(mynak_blog_href_path('')), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>410 — İçerik bulunamadı</title></head><body>'
        . '<h1>410 — Etiket veya kategoride yazı yok</h1>'
        . '<p>Bu listede şu anda yazı bulunmuyor.</p>'
        . '<p><a href="' . $blogHref . '">Tüm blog yazılarını gör</a></p>'
        . '</body></html>';
    exit;
}

// Kanonik: tam URL; SITE_URL path sızıntısından bağımsız (HTTP_HOST + mynak_public_path)
// .htaccess tüm trailing slash'ı 301 kaldırır → tüm canonical'lar slash'sız.
$canonical_override = '';
if ($current_tag || $current_category) {
    if ($current_category) {
        $suf = 'kategori/' . rawurlencode((string) $current_category['slug']);
    } else {
        $tagSeg = !empty($_GET['tag']) ? slug_olustur((string) $_GET['tag']) : slug_olustur((string) $current_tag);
        $suf = 'etiket/' . rawurlencode($tagSeg);
    }
    if ($page > 1) {
        $suf .= '/sayfa/' . $page;
    }
    $canonical_override = mynak_abs_url_from_public_path(mynak_blog_href_path($suf));
} elseif ($page > 1) {
    $canonical_override = mynak_abs_url_from_public_path(mynak_blog_href_path('sayfa/' . $page));
} else {
    $canonical_override = mynak_abs_url_from_public_path(mynak_blog_href_path(''));
}

// Kanonik yalnızca path; sorgu parametresi eklenmez (robots + GSC tutarlılığı)
require_once __DIR__ . '/includes/header.php';

?>
<main id="content">
<?php

// Liste şablonu için gerekli sütunlar (p.* + bind_result çok sütun / mükerrer ad riski azaltılır)
$sql = "SELECT p.id, p.slug, p.baslik, p.icerik, p.kapak_foto, p.created_at, c.ad AS kategori_adi
        FROM blog_posts p
        LEFT JOIN blog_categories c ON p.kategori_id = c.id
        $where
        ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$blog_posts = [];
if ($stmt) {
    $bind_params = array_merge($params, [$per_page, $offset]);
    $types = '';
    foreach ($params as $p) {
        $types .= is_int($p) ? 'i' : 's';
    }
    $types .= 'ii';
    if (mysqli_stmt_bind_params_safe($stmt, $types, $bind_params)) {
        $stmt->execute();
        $blog_posts = mysqli_stmt_fetch_all_assoc($stmt);
    }
    $stmt->close();
}
?>

<!-- Blog Banner Section -->
<section class="page-banner">
    <div class="container">
        <div class="banner-content text-center">
            <h1 class="banner-title">
                <?php
                if ($current_category) {
                    $blog_banner_h1 = mynak_esc_html((string) $current_category['ad']) . ' Yazıları';
                } elseif ($current_tag) {
                    $blog_banner_h1 = '"' . mynak_esc_html((string) $current_tag) . '" Etiketli Yazılar';
                } else {
                    $blog_banner_h1 = 'Blog';
                }
                if ($page > 1) {
                    $blog_banner_h1 .= ' — Sayfa ' . (int) $page;
                }
                echo $blog_banner_h1;
                ?>
            </h1>
            <div class="breadcrumb">
                <a href="<?php echo htmlspecialchars(mynak_public_path(''), ENT_QUOTES, 'UTF-8'); ?>">Ana Sayfa</a>
                <span class="separator">/</span>
                <?php if ($current_category || $current_tag): ?>
                    <a href="<?php echo htmlspecialchars(mynak_blog_href_path(''), ENT_QUOTES, 'UTF-8'); ?>">Blog</a>
                    <span class="separator">/</span>
                    <span class="current">
                        <?php
                        if ($current_category) {
                            echo mynak_esc_html((string) $current_category['ad']);
                        } elseif ($current_tag) {
                            echo mynak_esc_html((string) $current_tag);
                        }
                        ?>
                    </span>
                <?php else: ?>
                    <span class="current">Blog</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Blog Section -->
<section class="blog-section">
    <div class="blog-container">
        <!-- Sol Taraf - Blog Yazıları (%75) -->
        <div class="blog-main">
            <div class="blog-grid">
                <?php if (!empty($blog_posts)): ?>
                    <?php foreach ($blog_posts as $blog): ?>
                        <?php
                        $blogSlugClean = trim((string) ($blog['slug'] ?? ''), '/');
                        if ($blogSlugClean === '') {
                            $blogPostPath = function_exists('mynak_blog_href_path') ? mynak_blog_href_path('') : '/blog/';
                        } else {
                            $blogPostPath = mynak_public_path($blogSlugClean);
                        }
                        $blogPostUrl = function_exists('mynak_abs_url_from_public_path')
                            ? mynak_abs_url_from_public_path($blogPostPath)
                            : $blogPostPath;
                        $blogPostHref = htmlspecialchars($blogPostUrl, ENT_QUOTES, 'UTF-8');
                        $blogExcerptRaw = mynak_decode_html_entities(strip_tags((string) ($blog['icerik'] ?? '')));
                        if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($blogExcerptRaw, 'UTF-8') > 100) {
                            $blogExcerptOut = mb_substr($blogExcerptRaw, 0, 100, 'UTF-8') . '…';
                        } else {
                            $blogExcerptOut = strlen($blogExcerptRaw) > 100 ? substr($blogExcerptRaw, 0, 100) . '…' : $blogExcerptRaw;
                        }
                        ?>
                        <article class="blog-item">
                            <div class="blog-card-inner">
                                <div class="blog-img">
                                    <a href="<?php echo $blogPostHref; ?>">
                                        <?php if (!empty($blog['kapak_foto'])): ?>
                                            <img src="<?php echo htmlspecialchars(blog_kapak_full_url($blog['kapak_foto'])); ?>"
                                                alt="<?php echo mynak_esc_html(mynak_public_image_alt((string) $blog['baslik'], 'MY Nakliyat blog yazısı kapak görseli', blog_kapak_full_url($blog['kapak_foto']))); ?>"
                                                loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1000&auto=format&fit=crop&ixlib=rb-4.0.3"
                                                alt="<?php echo htmlspecialchars(mynak_public_image_alt('', 'MY Nakliyat blog yazısı kapak görseli')); ?>" loading="lazy" decoding="async">
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="blog-content">
                                    <span class="blog-category"><?php echo mynak_esc_html((string) ($blog['kategori_adi'] ?? 'Genel')); ?></span>
                                    <h3 class="blog-title"><a href="<?php echo $blogPostHref; ?>"><?php echo mynak_esc_html((string) $blog['baslik']); ?></a></h3>
                                    <p class="blog-excerpt"><?php echo mynak_esc_html($blogExcerptOut); ?></p>
                                    <a href="<?php echo $blogPostHref; ?>" class="blog-read-more">DEVAMINI OKU →</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-result">
                        <p>Maalesef bu kriterlere uygun blog yazısı bulunamadı.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sayfalama -->
            <?php if ($total_pages > 1):
                $buildPageUrl = function ($pageNum, $category, $tag) {
                    $parts = [];
                    if ($category) {
                        $parts[] = 'kategori';
                        $parts[] = (string) $category['slug'];
                    } elseif ($tag) {
                        $parts[] = 'etiket';
                        $parts[] = slug_olustur($tag);
                    }
                    if ($pageNum > 1) {
                        $parts[] = 'sayfa';
                        $parts[] = (string) $pageNum;
                    }
                    $suffix = implode('/', $parts);

                    return mynak_blog_href_path($suffix);
                };
                ?>
                    <nav aria-label="Blog sayfalama" class="pagination-wrap">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="<?php echo htmlspecialchars($buildPageUrl($page - 1, $current_category, $current_tag)); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $visible = 2;
                        $start = max(1, $page - $visible);
                        $end = min($total_pages, $page + $visible);
                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($buildPageUrl(1, $current_category, $current_tag)) . '">1</a></li>';
                            if ($start > 2)
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            $active = $i === $page ? ' active' : '';
                            echo '<li class="page-item' . $active . '"><a class="page-link" href="' . htmlspecialchars($buildPageUrl($i, $current_category, $current_tag)) . '">' . $i . '</a></li>';
                        }
                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1)
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($buildPageUrl($total_pages, $current_category, $current_tag)) . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="<?php echo htmlspecialchars($buildPageUrl($page + 1, $current_category, $current_tag)); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>

        <!-- Sağ Taraf - Sidebar (%25) -->
        <aside class="blog-sidebar" aria-label="Blog kategorileri ve etiketler">
            <!-- Kategoriler -->
            <div class="sidebar-widget categories-widget">
                <h4 class="widget-title">Kategoriler</h4>
                <ul class="categories-list">
                    <li>
                        <a href="<?php echo htmlspecialchars(mynak_blog_href_path(''), ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo (!$current_category && !$current_tag) ? 'active' : ''; ?>">
                            Tümü
                        </a>
                    </li>
                    <?php
                    // Kategorileri yeniden getir (ONLY_FULL_GROUP_BY uyumlu; query false iken fatal önlenir)
                    $categories = $conn->query(
                        'SELECT c.id, c.ad, c.slug, COUNT(p.id) AS post_count
                         FROM blog_categories c
                         LEFT JOIN blog_posts p ON c.id = p.kategori_id AND p.durum = 3
                         GROUP BY c.id, c.ad, c.slug
                         ORDER BY c.ad ASC'
                    );

                    if ($categories) {
                    while ($category = $categories->fetch_assoc()):
                        $is_active = ($current_category && $current_category['id'] == $category['id']);
                        ?>
                            <li>
                            <a href="<?php echo htmlspecialchars(mynak_blog_href_path('kategori/' . $category['slug']), ENT_QUOTES, 'UTF-8'); ?>"
                                class="<?php echo $is_active ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['ad']); ?> <span
                                    class="count">(<?php echo $category['post_count']; ?>)</span>
                            </a>
                        </li>
                    <?php endwhile;
                    }
                    ?>
                </ul>
            </div>

            <!-- Etiketler -->
            <div class="sidebar-widget tags-widget">
                <h4 class="widget-title">Etiketler</h4>
                <div class="tags-cloud">
                    <?php
                    $all_tags = function_exists('mynak_blog_tag_cloud_get')
                        ? mynak_blog_tag_cloud_get($conn)
                        : [];
                    $max_tags = 15;
                    $count = 0;
                    foreach ($all_tags as $tag => $count_tag):
                        $tag_slug = slug_olustur($tag);
                        $is_active = ($current_tag === $tag);
                        $hidden = $count >= $max_tags ? ' style="display:none;" class="extra-tag"' : '';
                        ?>
                        <a href="<?php echo htmlspecialchars(mynak_blog_href_path('etiket/' . $tag_slug), ENT_QUOTES, 'UTF-8'); ?>"
                            class="tag-link<?php echo $is_active ? ' active' : ''; ?>" <?php echo $hidden; ?>>
                            <?php echo htmlspecialchars($tag); ?> (<?php echo $count_tag; ?>)
                        </a>
                     <?php $count++; endforeach; ?>
                    <?php if (count($all_tags) > $max_tags): ?>
                        <button id="show-all-tags" class="tag-link" style="background:#eee;color:#0056b3;">Tümünü
                            Göster</button>
                    <?php endif; ?>
                </div>
            </div>

        </aside>
    </div>
</section>

<style>
    /* Blog Section Styles */
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

    .blog-section {
        padding: 0 0 80px;
    }

    /* Grid Layout - 75% main, 25% sidebar */
    .blog-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 75% 25%;
        gap: 30px;
        padding: 0 15px;
    }

    .blog-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        margin-bottom: 40px;
    }

    .blog-item {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.07);
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
    }

    .blog-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .blog-img {
        position: relative;
        overflow: hidden;
        width: 100%;
        aspect-ratio: 16/9;
        height: auto;
        background: #f5f5f5;
    }

    .blog-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        aspect-ratio: 16/9;
        display: block;
        transition: transform 0.5s;
    }

    .blog-item:hover .blog-img img {
        transform: scale(1.05);
    }

    .blog-date {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #0056b3;
        color: #fff;
        padding: 8px 12px;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 2;
    }

    .blog-date .day {
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
    }

    .blog-date .month {
        font-size: 12px;
        margin-top: 3px;
        text-transform: uppercase;
    }

    .blog-card-inner {
        display: flex;
        flex-direction: column;
        height: 100%;
        border-radius: 10px;
    }

    .blog-img a {
        display: block;
        line-height: 0;
        text-decoration: none;
    }

    .blog-title a {
        color: inherit;
        text-decoration: none;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .blog-title a:focus-visible,
    .blog-img a:focus-visible,
    .blog-read-more:focus-visible {
        outline: 3px solid #0056b3;
        outline-offset: 2px;
    }

    .blog-content {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .blog-category {
        display: inline-block;
        font-size: 13px;
        font-weight: 500;
        color: #0056b3;
        margin-bottom: 10px;
    }

    .blog-title {
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 12px 0;
        line-height: 1.4;
        transition: color 0.2s;
    }

    .blog-excerpt {
        color: #555;
        margin-bottom: 0;
        line-height: 1.6;
        font-size: 14px;
        flex: 1;
        /* Üç satırdan fazla olursa ... ile kırpma */
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .blog-read-more {
        display: block;
        margin-top: 14px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #0056b3;
        text-decoration: none;
    }

    .blog-item:hover .blog-title a {
        color: #0056b3;
    }

    .blog-item:hover .blog-read-more,
    .blog-read-more:hover {
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .pagination-wrap {
        margin-top: 40px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        padding: 0;
        margin: 0;
    }

    .page-item {
        list-style: none;
    }

    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #f5f5f5;
        color: #333;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }

    .page-item.active .page-link,
    .page-link:hover {
        background: #0056b3;
        color: #fff;
    }

    .no-result {
        background: #f8f9fa;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        grid-column: 1 / -1;
    }

    /* Sidebar Styles */
    .blog-sidebar {
        position: sticky;
        top: 30px;
    }

    .sidebar-widget {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.07);
    }

    .widget-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .categories-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .categories-list li {
        margin-bottom: 8px;
    }

    .categories-list a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #333;
        text-decoration: none;
        padding: 8px 0;
        transition: color 0.3s;
        font-size: 14px;
    }

    .categories-list a:hover,
    .categories-list a.active {
        color: #0056b3;
    }

    .categories-list .count {
        background: #f5f5f5;
        color: #555;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 12px;
        transition: background 0.3s, color 0.3s;
    }

    .categories-list a:hover .count,
    .categories-list a.active .count {
        background: #0056b3;
        color: #fff;
    }

    .tags-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .tag-link {
        display: inline-block;
        padding: 5px 10px;
        background: #f5f5f5;
        color: #555;
        border-radius: 5px;
        font-size: 12px;
        text-decoration: none;
        transition: all 0.3s;
    }

    .tag-link:hover,
    .tag-link.active {
        background: #0056b3;
        color: #fff;
    }

    .cta-widget {
        text-align: center;
        background: #f8f9fa;
    }

    .cta-widget h4 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .cta-widget p {
        margin-bottom: 15px;
        color: #555;
        font-size: 14px;
    }

    .cta-widget .btn {
        display: inline-block;
        padding: 10px 20px;
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

    /* Responsive Tasarım */
    @media (max-width: 1200px) {
        .blog-container {
            max-width: 960px;
        }

        .blog-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 992px) {
        .blog-container {
            grid-template-columns: 1fr;
            max-width: 720px;
        }

        .blog-sidebar {
            margin-top: 40px;
        }
    }

    @media (max-width: 768px) {
        .blog-container {
            max-width: 540px;
        }

        .blog-grid {
            grid-template-columns: 1fr;
        }

        .page-banner {
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .page-banner .banner-title {
            font-size: 28px;
        }
    }
</style>

</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    // Kısa sayfalandırma için JS ile sunucuya gerek kalmadan PHP ile aşağıdaki kodu ekle
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('show-all-tags');
        if (btn) {
            btn.addEventListener('click', function () {
                var hiddenTags = document.querySelectorAll('.extra-tag');
                var isOpen = btn.classList.toggle('open');
                hiddenTags.forEach(function (tag) {
                    tag.style.display = isOpen ? 'inline-block' : 'none';
                });
                btn.textContent = isOpen ? 'Daha Az Göster' : 'Tümünü Göster';
            });
        }
    });
</script>