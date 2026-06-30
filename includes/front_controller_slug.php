<?php
declare(strict_types=1);

require_once __DIR__ . '/mynak_legacy_url_recovery.php';
require_once __DIR__ . '/mynak_gsc_404_slug_redirects.php';
require_once __DIR__ . '/mynak_gsc_legacy_path_redirects.php';
require_once __DIR__ . '/mynak_canonical_slug_redirects.php';
require_once __DIR__ . '/mynak_broken_link_recovery.php';

/**
 * Slug çözümleyici (eski slug-router.php mantığı). Çağıran mutlaka exit eder.
 * Yönlendirmeler SITE_URL’e bağlı değildir (Windows path sızıntısı önlenir).
 */
function mynak_fc_dispatch_slug(string $slug, mysqli $conn): void
{
    $root = dirname(__DIR__);

    if ($slug === '') {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path('')), true, 301);
        exit;
    }

    // /index, /index.html, /home, /main → ana sayfa 301 (GSC "alternatif sayfa" raporundan düşür)
    $slugLowerRoot = strtolower($slug);
    if (in_array($slugLowerRoot, ['index', 'index.html', 'index.htm', 'home', 'main', 'anasayfa'], true)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path('')), true, 301);
        exit;
    }

    $fiyatAliases = [
        'izmir-nakliyat-fiyatlari',
        'nakliyat-fiyatlari',
        'evden-eve-nakliyat-fiyat-listesi',
        'izmir-evden-eve-nakliyat-fiyatlari',
    ];
    if (in_array($slugLowerRoot, $fiyatAliases, true)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path('fiyat')), true, 301);
        exit;
    }

    mynak_fc_try_cannibalization_slug_redirect($slug);
    mynak_fc_try_gsc_legacy_path_redirect($conn, $slug);

    // Eski WordPress tarih arşivi: /Yıl/Ay/yazi-slug → kanonik /yazi-slug (GSC 404 azaltma)
    if (preg_match('#^(\d{4})/(\d{2})/([^/]+)/?$#u', $slug, $m)) {
        $ymSlug = $m[3];
        if ($ymSlug !== '' && !preg_match('#^\d+$#', $ymSlug)) {
            $stmt = $conn->prepare(
                "SELECT slug FROM ("
                . 'SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 '
                . 'UNION ALL SELECT slug FROM services WHERE slug = ? AND status = 1 '
                . 'UNION ALL SELECT slug FROM pages WHERE slug = ? AND status = 1'
                . ') AS u LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('sss', $ymSlug, $ymSlug, $ymSlug);
                $stmt->execute();
                $ymRows = mysqli_stmt_fetch_all_assoc($stmt);
                $stmt->close();
                if (!empty($ymRows[0]['slug'])) {
                    $targetSlug = (string) $ymRows[0]['slug'];
                    header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($targetSlug)), true, 301);
                    exit;
                }
            }
        }
    }

    // WP RSS/feed uçları → blog listesi
    if ($slug === 'feed' || preg_match('#^feed/(atom|rss2?)$#iu', $slug)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
        exit;
    }
    if (preg_match('#^comments/feed/?$#iu', $slug)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
        exit;
    }
    if (preg_match('#^blog/feed/?$#iu', $slug) || preg_match('#^blog/(atom|rss2?)/?$#iu', $slug)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
        exit;
    }

    // WP category/… → blog/kategori/… (yalnız DB eşleşmesi varsa)
    if (preg_match('#^category/([^/]+)(?:/page/(\d+))?/?$#u', $slug, $m)) {
        $catSlug = $m[1];
        $stmt = $conn->prepare('SELECT slug FROM blog_categories WHERE slug = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $catSlug);
            $stmt->execute();
            $catRows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($catRows[0]['slug'])) {
                $out = 'kategori/' . rawurlencode((string) $catRows[0]['slug']);
                if (isset($m[2])) {
                    $pn = max(1, (int) $m[2]);
                    if ($pn > 1) {
                        $out .= '/sayfa/' . $pn;
                    }
                }
                header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path($out)), true, 301);
                exit;
            }
        }
    }

    $slugLower = strtolower($slug);
    if ($slugLower === 'wp-login' || $slugLower === 'wp-login.php' || str_starts_with($slugLower, 'wp-admin')) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path('')), true, 301);
        exit;
    }
    if ($slugLower === 'xmlrpc.php' || $slugLower === 'xmlrpc') {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path('')), true, 301);
        exit;
    }

    if (preg_match('#^tag/([^/]+)/feed/?$#u', $slug, $m)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('etiket/' . rawurlencode($m[1]))), true, 301);
        exit;
    }
    if (preg_match('#^tag/([^/]+)/page/(\d+)/?$#u', $slug, $m)) {
        $pn = max(1, (int) $m[2]);
        $suf = 'etiket/' . rawurlencode($m[1]);
        if ($pn > 1) {
            $suf .= '/sayfa/' . $pn;
        }
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path($suf)), true, 301);
        exit;
    }
    if (preg_match('#^tag/([^/]+)/?$#u', $slug, $m)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('etiket/' . rawurlencode($m[1]))), true, 301);
        exit;
    }

    if (preg_match('#^etiket/([^/]+)/feed/?$#u', $slug, $m)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('etiket/' . rawurlencode($m[1]))), true, 301);
        exit;
    }
    if (preg_match('#^etiket/([^/]+)/page/(\d+)/?$#u', $slug, $m)) {
        $pn = max(1, (int) $m[2]);
        $suf = 'etiket/' . rawurlencode($m[1]);
        if ($pn > 1) {
            $suf .= '/sayfa/' . $pn;
        }
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path($suf)), true, 301);
        exit;
    }
    if (preg_match('#^etiket/([^/]+)/?$#u', $slug, $m)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('etiket/' . rawurlencode($m[1]))), true, 301);
        exit;
    }

    mynak_fc_try_gsc_404_slug_redirect($conn, $slug);

    if (preg_match('#^author/[^/]+$#', $slug)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
        exit;
    }

    if ($slug === 'haberler' || $slug === 'haberx') {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
        exit;
    }

    if (preg_match('#^blog/page/(\d+)$#', $slug, $m)) {
        $pn = max(1, (int) $m[1]);
        if ($pn === 1) {
            header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
            exit;
        }
        header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('sayfa/' . $pn)), true, 301);
        exit;
    }

    if (preg_match('#^blog/sayfa/(\d+)$#', $slug, $m)) {
        $pn = max(1, (int) $m[1]);
        if ($pn === 1) {
            header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('')), true, 301);
            exit;
        }
        $_GET['page'] = $pn;
        require $root . '/blog.php';
        exit;
    }

    if ($slug === 'blog') {
        require $root . '/blog.php';
        exit;
    }

    // Madde 5: Musteri Hikayeleri (Case Studies)
    if ($slug === 'musteri-hikayeleri') {
        require $root . '/musteri-hikayeleri.php';
        exit;
    }
    if (preg_match('#^musteri-hikayeleri/([a-z0-9][a-z0-9-]*)$#u', $slug, $csm)) {
        $mynak_case_study_slug = $csm[1];
        require $root . '/musteri-hikayeleri.php';
        exit;
    }

    // Ekibimiz sayfası: pages tablosunda kayıt olmasa bile sayfa.php'nin
    // team_members özel bloğunu tetikle (GSC/sitemap'te mevcut).
    if ($slug === 'ekibimiz') {
        $page = [
            'id'    => 0,
            'title' => 'Ekibimiz',
            'slug'  => 'ekibimiz',
            'type'  => 'team',
            'content' => '',
            'meta_description' => 'MY Nakliyat yönetim ve operasyon ekibi ile tanışın.',
        ];
        $page_title = 'Ekibimiz';
        $allow_indexing = true;
        require $root . '/sayfa.php';
        exit;
    }

    // Video izleme sayfalari (GSC / Google Kisa Videolar)
    if ($slug === 'shorts') {
        require $root . '/shorts.php';
        exit;
    }
    if (preg_match('#^video/([A-Za-z0-9_-]{11})$#', $slug, $vm)) {
        $mynak_video_id = $vm[1];
        require $root . '/video.php';
        exit;
    }

    // Local cluster alias: /izmir/{existing-slug} → /{existing-slug} 301
    // (mevcut ilçe sayfalarının çift URL'le indexlenmesini önler; sadece mevcut satırlar için)
    if (preg_match('#^izmir/([a-z0-9][a-z0-9-]*)$#u', $slug, $m)) {
        $alias = $m[1];
        $stmt = $conn->prepare('SELECT 1 FROM services WHERE slug = ? AND status = 1 UNION SELECT 1 FROM pages WHERE slug = ? AND status = 1 LIMIT 1');
        $stmt->bind_param('ss', $alias, $alias);
        $stmt->execute();
        $aliasFound = mysqli_stmt_fetch_all_assoc($stmt);
        $stmt->close();
        if (!empty($aliasFound)) {
            header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($alias)), true, 301);
            exit;
        }
    }

    if (preg_match('#^(.+)/blog\.php$#i', $slug, $m)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path(trim($m[1], '/'))), true, 301);
        exit;
    }

    if (preg_match('#^(.+)/index\.php$#i', $slug, $m)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path(trim($m[1], '/'))), true, 301);
        exit;
    }

    if (preg_match('#^(.+)/feed$#i', $slug, $m)) {
        $pslug = $m[1];
        if (strpos($pslug, '/') === false) {
            $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
            $stmt->bind_param('s', $pslug);
            $stmt->execute();
            $feed_chk = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($feed_chk)) {
                header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($pslug)), true, 301);
                exit;
            }
        }
    }

    $staticPathTails = seo_rt_static_path_tails_from_cluster_defs();
    if (preg_match('#^([^/]+)/([^/]+)$#u', $slug, $m)) {
        if ($m[1] !== 'blog' && $m[1] !== 'tag' && $m[1] !== 'etiket' && $m[1] !== 'hizmet') {
            $tail = mb_strtolower($m[2], 'UTF-8');
            if (in_array($tail, $staticPathTails, true)) {
                $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
                $stmt->bind_param('s', $m[1]);
                $stmt->execute();
                $st_chk = mysqli_stmt_fetch_all_assoc($stmt);
                $stmt->close();
                if (!empty($st_chk)) {
                    header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($m[1])), true, 301);
                    exit;
                }
            }
        }
    }

    if (preg_match('#^hizmet/([^/]+)$#u', $slug, $m)) {
        header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($m[1])), true, 301);
        exit;
    }

    if (preg_match('#^blog/([^/]+)$#u', $slug, $m)) {
        $inner = $m[1];
        $reserved = ['etiket', 'kategori', 'page', 'sayfa'];
        if (!in_array($inner, $reserved, true)) {
            $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
            $stmt->bind_param('s', $inner);
            $stmt->execute();
            $wp_chk = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
            if (!empty($wp_chk)) {
                header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($inner)), true, 301);
                exit;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // YAZAR SAYFALARI (/yazarlar, /yazarlar/{slug}, /author/{slug})
    // E-E-A-T için kritik: BlogPosting.author.url alanlarının 200 OK
    // dönmesi gerekir.
    // ─────────────────────────────────────────────────────────────
    $authorRoute = null;
    if ($slug === 'yazarlar' || $slug === 'authors') {
        $authorRoute = ['list'];
    } elseif (preg_match('#^yazarlar/([a-z0-9-]+)/?$#u', $slug, $am)
           || preg_match('#^author/([a-z0-9-]+)/?$#u', $slug, $am)) {
        $authorRoute = ['detail', $am[1]];
    }
    if ($authorRoute !== null) {
        require_once $root . '/authors_public.php';
        mynak_authors_public_dispatch($conn, $authorRoute);
        exit;
    }

    require_once $root . '/includes/pipeline/city_pair_landings.php';
    $mynakCityPair = mynak_city_pair_landing_data($slug);
    if ($mynakCityPair !== null) {
        $page = $mynakCityPair;
        $page_title = $mynakCityPair['title'];
        $allow_indexing = true;
        require $root . '/sayfa.php';
        exit;
    }

    $stmt = $conn->prepare('SELECT * FROM services WHERE slug = ? AND status = 1');
    if (!$stmt) {
        $service_rows = [];
    } else {
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $service_rows = mysqli_stmt_fetch_all_assoc($stmt);
        $stmt->close();
    }

    if (!empty($service_rows)) {
        $service = $service_rows[0];
        // LLM bot'lar için markdown content negotiation
        if (!function_exists('mynak_cn_wants_markdown')) {
            require_once __DIR__ . '/handlers/content_negotiation.php';
        }
        if (mynak_cn_wants_markdown() && mynak_cn_try_emit_service_markdown($conn, (string) $service['slug'])) {
            exit;
        }
        // Detay sayfa içerik önceliği: icerik (uzun HTML, yeni kolon) → aciklama (kısa özet, ana sayfa kartı için).
        $mynakDetayIcerik = isset($service['icerik']) && trim((string) $service['icerik']) !== ''
            ? (string) $service['icerik']
            : (string) ($service['aciklama'] ?? '');
        $page = [
            'id' => $service['id'],
            'title' => $service['ana_baslik'],
            'seo_title' => $service['seo_title'] ?? '',
            'content' => $mynakDetayIcerik,
            'slug' => $service['slug'],
            'created_at' => $service['created_at'],
            'updated_at' => $service['updated_at'] ?? ($service['created_at'] ?? null),
            'meta_description' => $service['meta_description'],
            'meta_keywords' => $service['meta_keywords'],
            'type' => 'service',
        ];
        unset($page_title);
        $allow_indexing = true;
        require $root . '/sayfa.php';
        exit;
    }

    if (preg_match('#^blog-detay/(.+)$#', $slug, $blogDetayMatch)) {
        $blogSlug = $blogDetayMatch[1];
        $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $blogSlug);
            $stmt->execute();
            $bd_rows = mysqli_stmt_fetch_all_assoc($stmt);
            $stmt->close();
        } else {
            $bd_rows = [];
        }
        if (!empty($bd_rows)) {
            header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($blogSlug)), true, 301);
            exit;
        }
    }

    $stmt = $conn->prepare('SELECT * FROM blog_posts WHERE slug = ? AND durum = 3');
    if (!$stmt) {
        $blog_rows = [];
    } else {
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $blog_rows = mysqli_stmt_fetch_all_assoc($stmt);
        $stmt->close();
    }

    if (!empty($blog_rows)) {
        $blog = $blog_rows[0];
        require_once $root . '/includes/mynak_faz2_ilce_seo.php';
        if (mynak_faz2_is_ilce_slug((string) ($blog['slug'] ?? ''))) {
            $faz2Sn = mynak_faz2_snippets_for_slug((string) $blog['slug']);
            if ($faz2Sn !== null) {
                $page = [
                    'id' => $blog['id'],
                    'title' => $faz2Sn['h1'],
                    'seo_title' => !empty($blog['seo_title']) ? (string) $blog['seo_title'] : $faz2Sn['seo_title'],
                    'slug' => $blog['slug'],
                    'type' => 'service',
                    'meta_description' => !empty($blog['meta_description'])
                        ? (string) $blog['meta_description']
                        : $faz2Sn['meta_description'],
                ];
            }
        }
        // blog-detay.php uyumluluğu: tekil sayfa $_GET['slug']'ı bekliyor.
        $_GET['slug'] = (string) ($blog['slug'] ?? $slug);
        // LLM bot'lar için markdown content negotiation
        if (!function_exists('mynak_cn_wants_markdown')) {
            require_once __DIR__ . '/handlers/content_negotiation.php';
        }
        if (mynak_cn_wants_markdown() && mynak_cn_try_emit_blog_markdown($conn, (string) $blog['slug'])) {
            exit;
        }
        $page_title = !empty($blog['seo_title']) ? $blog['seo_title'] : $blog['baslik'];
        // İnce içerik (kelime < 220) → noindex,follow + sitemap dışı.
        // Google "Tarandı - dizine eklenmedi" raporundaki ana tetikleyici thin content.
        $__plain = trim(strip_tags((string) ($blog['icerik'] ?? '')));
        $__plain = preg_replace('/\s+/u', ' ', $__plain) ?? $__plain;
        $__wc = $__plain === '' ? 0 : count(preg_split('/\s+/u', $__plain) ?: []);
        if ($__wc > 0 && $__wc < 220) {
            $allow_indexing = false;
            $meta_robots = 'noindex, follow';
        } else {
            $allow_indexing = true;
        }
        require $root . '/blog-detay.php';
        exit;
    }

    $stmt = $conn->prepare('SELECT * FROM pages WHERE slug = ? AND status = 1');
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $page_rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();

    if (!empty($page_rows)) {
        $page = $page_rows[0];
        // LLM bot'lar için markdown content negotiation (pages)
        if (!function_exists('mynak_cn_wants_markdown')) {
            require_once __DIR__ . '/handlers/content_negotiation.php';
        }
        if (mynak_cn_wants_markdown() && mynak_cn_try_emit_page_markdown($conn, (string) $page['slug'])) {
            exit;
        }
        if (!isset($page['type']) || (string) $page['type'] === '') {
            require_once $root . '/includes/mynak_faz2_ilce_seo.php';
            if (mynak_faz2_is_ilce_slug((string) ($page['slug'] ?? ''))) {
                $page['type'] = 'service';
            }
        }
        $page_title = $page['title'];
        $allow_indexing = true;
        require $root . '/sayfa.php';
        exit;
    }

    if (preg_match('#^blog/etiket/([^/]+)(?:/sayfa/(\d+))?/?$#', $slug, $m)) {
        $tagSlug = $m[1];
        if (isset($m[2]) && (int) $m[2] === 1) {
            header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('etiket/' . rawurlencode($tagSlug))), true, 301);
            exit;
        }
        $_GET['tag'] = $tagSlug;
        if (isset($m[2])) {
            $_GET['page'] = (int) $m[2];
        }
        require $root . '/blog.php';
        exit;
    }

    if (preg_match('#^blog/kategori/([^/]+)(?:/sayfa/(\d+))?/?$#', $slug, $m)) {
        $categorySlug = $m[1];
        $stmt = $conn->prepare('SELECT id, ad FROM blog_categories WHERE slug = ? LIMIT 1');
        if (!$stmt) {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>503</title></head><body><p>Blog geçici olarak kullanılamıyor.</p></body></html>';
            exit;
        }
        $stmt->bind_param('s', $categorySlug);
        $stmt->execute();
        $cat_rows = mysqli_stmt_fetch_all_assoc($stmt);
        $stmt->close();
        $cat = $cat_rows[0] ?? null;
        if ($cat) {
            if (isset($m[2]) && (int) $m[2] === 1) {
                header('Location: ' . mynak_abs_url_from_public_path(mynak_blog_href_path('kategori/' . rawurlencode($categorySlug))), true, 301);
                exit;
            }
            $_GET['kategori'] = (int) $cat['id'];
            if (isset($m[2])) {
                $_GET['page'] = (int) $m[2];
            }
            $page_title = 'Blog: ' . $cat['ad'];
            require $root . '/blog.php';
            exit;
        }
    }

    mynak_fc_try_wp_appendage_redirect($conn, $slug);
    mynak_fc_try_fuzzy_blog_slug_redirect($conn, $slug, false);

    // Son seans: normalize + partial match ile kirik link kurtarma
    mynak_fc_try_broken_link_recovery($conn, $slug);

    // 410 Gone yalnızca DB'de gerçekten var olup silinmiş/pasif içerik için.
    // Hiç var olmamış URL'ler 404 döner — 410 yalnızca kanıtlanmış silme durumunda.
    $isConfirmedDeleted = false;
    if ($slug !== '' && !str_contains($slug, '/')) {
        $stmtDead = $conn->prepare(
            'SELECT 1 FROM blog_posts WHERE slug = ? AND durum != 3 LIMIT 1'
        );
        if ($stmtDead instanceof mysqli_stmt) {
            $stmtDead->bind_param('s', $slug);
            $stmtDead->execute();
            $stmtDead->store_result();
            $isConfirmedDeleted = $stmtDead->num_rows > 0;
            $stmtDead->close();
        }
        if (!$isConfirmedDeleted) {
            $stmtDead2 = $conn->prepare(
                'SELECT 1 FROM services WHERE slug = ? AND status != 1 LIMIT 1'
            );
            if ($stmtDead2 instanceof mysqli_stmt) {
                $stmtDead2->bind_param('s', $slug);
                $stmtDead2->execute();
                $stmtDead2->store_result();
                $isConfirmedDeleted = $stmtDead2->num_rows > 0;
                $stmtDead2->close();
            }
        }
        if (!$isConfirmedDeleted) {
            $stmtDead3 = $conn->prepare(
                'SELECT 1 FROM pages WHERE slug = ? AND status != 1 LIMIT 1'
            );
            if ($stmtDead3 instanceof mysqli_stmt) {
                $stmtDead3->bind_param('s', $slug);
                $stmtDead3->execute();
                $stmtDead3->store_result();
                $isConfirmedDeleted = $stmtDead3->num_rows > 0;
                $stmtDead3->close();
            }
        }
    }

    $statusCode = $isConfirmedDeleted ? 410 : 404;
    $statusLabel = $isConfirmedDeleted ? '410 — Kalıcı olarak kaldırıldı' : '404 — İçerik bulunamadı';
    $statusBody = $isConfirmedDeleted
        ? 'Aradığınız içerik kalıcı olarak kaldırılmıştır.'
        : 'Aradığınız adres taşınmış veya kaldırılmış olabilir.';

    http_response_code($statusCode);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow', true);
    $homeHref = htmlspecialchars(rtrim((string) (defined('SITE_URL') ? SITE_URL : '/'), '/') . '/', ENT_QUOTES, 'UTF-8');
    $blogHref = htmlspecialchars(rtrim((string) (defined('SITE_URL') ? SITE_URL : '/'), '/') . '/blog', ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</title></head><body>'
        . '<h1>' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p>' . htmlspecialchars($statusBody, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><a href="' . $homeHref . '">Ana sayfa</a> &middot; <a href="' . $blogHref . '">Blog</a></p>'
        . '</body></html>';
    exit;
}
