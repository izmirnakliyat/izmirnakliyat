<?php
declare(strict_types=1);
/**
 * Public Authors — yazar liste + yazar detay sayfaları.
 *
 *   /yazarlar           → liste
 *   /yazarlar/{slug}    → detay (Person JSON-LD, knowsAbout, yazıları)
 *
 * Bu sayfa, BlogPosting.author.url alanlarının canlı 200 OK dönmesini
 * sağlar — E-E-A-T için kritik.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

/**
 * Yazar sayfası dispatcher.
 *   $route = ['list']                  → liste
 *   $route = ['detail', '<slug>']      → tek yazar
 */
function mynak_authors_public_dispatch(mysqli $conn, array $route): void
{
    $mode = $route[0] ?? 'list';

    if ($mode === 'detail') {
        $slug = (string) ($route[1] ?? '');
        mynak_authors_public_render_detail($conn, $slug);
        return;
    }
    mynak_authors_public_render_list($conn);
}

/**
 * Yazar listesi — tüm aktif yazarları kartlar halinde döner.
 */
function mynak_authors_public_render_list(mysqli $conn): void
{
    $sql = "SELECT a.*,
                   (SELECT COUNT(*) FROM blog_posts WHERE author_id = a.id AND durum = 3) AS post_count
            FROM authors a
            WHERE a.status = 1
            ORDER BY a.is_default DESC, post_count DESC, a.name ASC";
    $rows = $conn->query($sql);
    $authors = [];
    if ($rows) {
        while ($r = $rows->fetch_assoc()) {
            $authors[] = $r;
        }
    }

    $page_title              = 'Yazarlar — Nakliyat Uzmanlarımız';
    $page_meta_description   = 'My Nakliyat içerik ekibi: editörlerimiz, nakliye uzmanlarımız ve ekspertizlerimiz. Sektörde 18+ yıllık saha deneyimine sahip ekip.';
    $allow_indexing          = true;
    $canonical_override      = mynak_abs_url_from_public_path('yazarlar');

    require_once __DIR__ . '/includes/header.php';
    ?>
    <main id="content">
        <section class="page-banner">
            <div class="container">
                <div class="banner-content text-center">
                    <h1 class="banner-title">Yazarlarımız</h1>
                    <p class="banner-description">
                        My Nakliyat içerik ekibi — editörler, saha uzmanları ve nakliye eksperleri.
                    </p>
                </div>
            </div>
        </section>

        <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    <?php foreach ($authors as $a):
                        if ((int) $a['is_default'] === 1 && empty($a['photo_url'])) {
                            // Default rozet
                        }
                        $authorUrl = mynak_abs_url_from_public_path('yazarlar/' . $a['slug']);
                        $bioShort = mb_strimwidth((string)$a['bio'], 0, 180, '…', 'UTF-8');
                        $knowsAbout = array_filter(array_map('trim', preg_split('/\r?\n/u', (string)$a['knows_about']) ?: []));
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0" style="border-radius: 16px;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if (!empty($a['photo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($a['photo_url']); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) $a['name'], (string) $a['name'] . ' — MY Nakliyat yazar', (string) $a['photo_url'])); ?>" class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover; margin-right: 12px;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px; background: linear-gradient(135deg, #4dabf7, #1971c2); color: white; font-size: 1.5rem; font-weight: 700; margin-right: 12px;">
                                            <?php echo htmlspecialchars(mb_substr($a['name'], 0, 1, 'UTF-8')); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h2 class="h5 mb-1"><?php echo htmlspecialchars($a['name']); ?></h2>
                                        <?php if (!empty($a['title'])): ?>
                                            <div class="text-muted small"><?php echo htmlspecialchars($a['title']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="card-text text-muted" style="font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($bioShort); ?>
                                </p>
                                <?php if (!empty($knowsAbout)): ?>
                                    <div class="mb-3">
                                        <?php foreach (array_slice($knowsAbout, 0, 4) as $ka): ?>
                                            <span class="badge bg-light text-dark border me-1 mb-1" style="font-weight: 500;"><?php echo htmlspecialchars($ka); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="bi bi-file-text"></i> <?php echo (int) $a['post_count']; ?> yazı</small>
                                    <a href="<?php echo htmlspecialchars($authorUrl); ?>" class="btn btn-sm btn-outline-primary">Profili Gör →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
}

/**
 * Yazar detay — single Person.
 */
function mynak_authors_public_render_detail(mysqli $conn, string $slug): void
{
    if ($slug === '' || !preg_match('#^[a-z0-9-]+$#', $slug)) {
        http_response_code(404);
        require_once __DIR__ . '/includes/header.php';
        echo '<main id="content"><section class="py-5"><div class="container text-center"><h1>Yazar bulunamadı</h1><p><a href="/yazarlar">Tüm yazarlar</a></p></div></section></main>';
        require_once __DIR__ . '/includes/footer.php';
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM authors WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $author = $stmt->get_result()->fetch_assoc();
    if (!$author) {
        http_response_code(404);
        require_once __DIR__ . '/includes/header.php';
        echo '<main id="content"><section class="py-5"><div class="container text-center"><h1>Yazar bulunamadı</h1><p><a href="/yazarlar">Tüm yazarlar</a></p></div></section></main>';
        require_once __DIR__ . '/includes/footer.php';
        return;
    }

    // Yazarın yayında olan yazıları
    $aid = (int) $author['id'];
    $posts = [];
    $r = $conn->query("SELECT id, slug, baslik, kapak_foto, created_at, meta_description
                       FROM blog_posts
                       WHERE author_id = $aid AND durum = 3
                       ORDER BY created_at DESC
                       LIMIT 30");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $posts[] = $row;
        }
    }

    $knowsAbout = array_filter(array_map('trim', preg_split('/\r?\n/u', (string) $author['knows_about']) ?: []));
    $page_title            = $author['name'] . (!empty($author['title']) ? ' — ' . $author['title'] : '');
    $page_meta_description = mb_strimwidth((string)$author['bio'], 0, 155, '…', 'UTF-8');
    $allow_indexing        = true;
    $canonical_override    = mynak_abs_url_from_public_path('yazarlar/' . $author['slug']);

    // Person JSON-LD (E-E-A-T)
    $personJsonLd = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Person',
        '@id'         => $canonical_override . '#person',
        'name'        => $author['name'],
        'url'         => $canonical_override,
        'description' => (string) $author['bio'],
    ];
    if (!empty($author['title']))    $personJsonLd['jobTitle']    = $author['title'];
    if (!empty($author['email']))    $personJsonLd['email']       = $author['email'];
    if (!empty($author['photo_url'])) {
        $personJsonLd['image'] = mynak_abs_url_from_public_path(ltrim($author['photo_url'], '/'));
    }
    $personJsonLd['worksFor'] = [
        '@type' => 'Organization',
        'name'  => defined('MYNAK_BRAND_NAME') ? (string) MYNAK_BRAND_NAME : 'My Nakliyat',
        'url'   => mynak_abs_url_from_public_path(''),
    ];
    if ($knowsAbout) $personJsonLd['knowsAbout'] = array_values($knowsAbout);
    $sameAs = [];
    if (!empty($author['linkedin'])) $sameAs[] = $author['linkedin'];
    if (!empty($author['twitter']))  $sameAs[] = $author['twitter'];
    if ($sameAs) $personJsonLd['sameAs'] = $sameAs;

    require_once __DIR__ . '/includes/header.php';
    ?>
    <main id="content">
        <section class="page-banner">
            <div class="container">
                <div class="banner-content text-center">
                    <h1 class="banner-title"><?php echo htmlspecialchars($author['name']); ?></h1>
                    <?php if (!empty($author['title'])): ?>
                        <p class="banner-description"><?php echo htmlspecialchars($author['title']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                            <div class="card-body p-4 text-center">
                                <?php if (!empty($author['photo_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($author['photo_url']); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) $author['name'], (string) $author['name'] . ' — MY Nakliyat yazar', (string) $author['photo_url'])); ?>" class="rounded-circle mb-3" style="width: 128px; height: 128px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 128px; height: 128px; background: linear-gradient(135deg, #4dabf7, #1971c2); color: white; font-size: 3rem; font-weight: 700;">
                                        <?php echo htmlspecialchars(mb_substr($author['name'], 0, 1, 'UTF-8')); ?>
                                    </div>
                                <?php endif; ?>
                                <h2 class="h5 mb-1"><?php echo htmlspecialchars($author['name']); ?></h2>
                                <?php if (!empty($author['title'])): ?>
                                    <div class="text-muted mb-3"><?php echo htmlspecialchars($author['title']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($author['email'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($author['email']); ?>" class="btn btn-sm btn-outline-primary mb-2">
                                        <i class="bi bi-envelope"></i> İletişim
                                    </a>
                                <?php endif; ?>
                                <div class="text-muted small">
                                    <i class="bi bi-file-text"></i> <?php echo count($posts); ?> yayınlanmış yazı
                                </div>
                            </div>
                        </div>

                        <?php if ($knowsAbout): ?>
                        <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                            <div class="card-body p-4">
                                <h3 class="h6 mb-3"><i class="bi bi-mortarboard"></i> Uzmanlık Alanları</h3>
                                <?php foreach ($knowsAbout as $ka): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1" style="font-weight: 500;"><?php echo htmlspecialchars($ka); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                            <div class="card-body p-4">
                                <h2 class="h5 mb-3">Hakkında</h2>
                                <p class="text-muted" style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars((string)$author['bio'])); ?></p>
                            </div>
                        </div>

                        <?php if ($posts): ?>
                        <h2 class="h4 mb-3">Yayınlanmış Yazılar</h2>
                        <div class="row g-3">
                            <?php foreach ($posts as $p):
                                $purl = mynak_abs_url_from_public_path($p['slug']);
                                $img  = !empty($p['kapak_foto']) ? '/uploads/blog/' . $p['kapak_foto'] : '';
                            ?>
                            <div class="col-md-6">
                                <a href="<?php echo htmlspecialchars($purl); ?>" class="card h-100 border-0 shadow-sm text-decoration-none text-reset" style="border-radius: 12px;">
                                    <?php if ($img): ?>
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) $p['baslik'], 'MY Nakliyat blog yazısı', (string) $img)); ?>" class="card-img-top" style="height: 160px; object-fit: cover; border-radius: 12px 12px 0 0;">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h3 class="h6 mb-2" style="line-height: 1.4;"><?php echo htmlspecialchars($p['baslik']); ?></h3>
                                        <div class="text-muted small">
                                            <?php echo date('d.m.Y', strtotime((string)$p['created_at'])); ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
}
