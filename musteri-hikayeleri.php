<?php
/**
 * Madde 5 — Public: Musteri Hikayeleri
 *
 * Iki mod:
 *   - Listing  (front controller hicbir alt-slug vermezse)
 *   - Detay    ($mynak_case_study_slug global degiskeni dolu ise)
 *
 * Front controller ayni dosyayi require eder; bu sayede sayfa.php gibi
 * tek-noktalı bir entrypoint mantigi korunur.
 */
declare(strict_types=1);

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/includes/functions.php';
}

$mynakCaseSlug = isset($mynak_case_study_slug) && is_string($mynak_case_study_slug)
    ? trim($mynak_case_study_slug)
    : '';

if ($mynakCaseSlug !== '') {
    /* ============================================================
     * DETAY
     * ============================================================ */
    $stmt = $conn->prepare("SELECT * FROM case_studies WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->bind_param('s', $mynakCaseSlug);
    $stmt->execute();
    $res = $stmt->get_result();
    $cs = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$cs) {
        http_response_code(404);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8"><title>404</title></head><body><h1>404 - Müşteri hikayesi bulunamadı</h1><p><a href="' . htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('musteri-hikayeleri'))) . '">Tüm hikayeler</a></p></body></html>';
        exit;
    }

    $page = [
        'id' => (int) $cs['id'],
        'title' => (string) $cs['baslik'],
        'slug' => 'musteri-hikayeleri/' . $cs['slug'],
        'content' => (string) $cs['icerik'],
        'meta_description' => (string) ($cs['meta_description'] ?: $cs['ozet']),
        'meta_keywords' => '',
        'created_at' => (string) ($cs['created_at'] ?? ''),
        'updated_at' => (string) ($cs['updated_at'] ?? $cs['created_at'] ?? ''),
        'type' => 'case_study',
    ];
    $page_title = (string) ($cs['meta_title'] ?: $cs['baslik']);
    $page_meta_description = (string) ($cs['meta_description'] ?: $cs['ozet']);
    $allow_indexing = true;

    require_once __DIR__ . '/includes/header.php';
    ?>

    <main id="content">
        <section class="page-banner" style="background-color: #f8f9fa; padding: 80px 0 40px;">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <p class="text-muted mb-2"><a href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('musteri-hikayeleri'))); ?>" style="color: inherit; text-decoration: none;"><i class="fas fa-arrow-left"></i> Tüm Müşteri Hikayeleri</a></p>
                        <h1 class="mb-3"><?php echo htmlspecialchars((string) $cs['baslik']); ?></h1>
                        <?php if (!empty($cs['ozet'])): ?>
                            <p class="lead text-muted col-md-8 mx-auto"><?php echo htmlspecialchars((string) $cs['ozet']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="page-content py-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-md-5">

                                <?php /* Tasima ozet panosu */ ?>
                                <div class="row g-3 mb-4 mynak-cs-meta">
                                    <?php if (!empty($cs['kalkis_il']) || !empty($cs['varis_il'])): ?>
                                        <div class="col-6 col-md-3">
                                            <div class="mynak-cs-chip"><i class="fas fa-route text-primary"></i> <strong><?php echo htmlspecialchars((string) ($cs['kalkis_il'] ?? '')); ?> &rarr; <?php echo htmlspecialchars((string) ($cs['varis_il'] ?? '')); ?></strong><br><small>Güzergah</small></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($cs['ev_tipi'])): ?>
                                        <div class="col-6 col-md-3">
                                            <div class="mynak-cs-chip"><i class="fas fa-home text-success"></i> <strong><?php echo htmlspecialchars((string) $cs['ev_tipi']); ?></strong><br><small>Ev Tipi</small></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($cs['tasima_tarihi']) && $cs['tasima_tarihi'] !== '0000-00-00'): ?>
                                        <div class="col-6 col-md-3">
                                            <div class="mynak-cs-chip"><i class="far fa-calendar text-warning"></i> <strong><?php echo htmlspecialchars(date('d.m.Y', (int) strtotime((string) $cs['tasima_tarihi']))); ?></strong><br><small>Taşıma Tarihi</small></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($cs['fiyat_araligi'])): ?>
                                        <div class="col-6 col-md-3">
                                            <div class="mynak-cs-chip"><i class="fas fa-tag text-danger"></i> <strong><?php echo htmlspecialchars((string) $cs['fiyat_araligi']); ?></strong><br><small>Fiyat Aralığı</small></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php /* Detay metni */ ?>
                                <div class="page-text">
                                    <?php echo demote_inline_h1_to_h2((string) $cs['icerik']); ?>
                                </div>

                                <?php /* Musteri yorumu kutusu */ ?>
                                <?php if (!empty($cs['musteri_yorumu'])): ?>
                                    <div class="mynak-cs-review mt-5">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="mynak-cs-review-avatar"><i class="fas fa-quote-left"></i></div>
                                            <div class="flex-grow-1">
                                                <div class="mynak-cs-review-stars mb-2">
                                                    <?php
                                                    $puan = (float) ($cs['puan'] ?? 5);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo '<i class="fas fa-star ' . ($i <= round($puan) ? 'text-warning' : 'text-muted') . '"></i>';
                                                    }
                                                    ?>
                                                    <strong class="ms-2"><?php echo number_format($puan, 1); ?> / 5.0</strong>
                                                </div>
                                                <blockquote class="mb-2">"<?php echo htmlspecialchars((string) $cs['musteri_yorumu']); ?>"</blockquote>
                                                <small class="text-muted"><strong><?php echo htmlspecialchars((string) ($cs['musteri_ad'] ?? 'Mynakliyat Müşterisi')); ?></strong></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php /* CTA */ ?>
                                <div class="text-center mt-5 mynak-cs-cta">
                                    <h4 class="mb-3">Siz de aynı güvende taşınmak ister misiniz?</h4>
                                    <a href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('teklif-alin'))); ?>" class="btn btn-primary btn-lg me-2"><i class="fas fa-file-alt"></i> Ücretsiz Teklif Al</a>
                                    <a href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('iletisim'))); ?>" class="btn btn-outline-primary btn-lg"><i class="fas fa-phone"></i> Hemen Ara</a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php /* JSON-LD: Review (BreadcrumbList header.php tarafindan otomatik basiliyor) */ ?>
    <?php
    $homeUrl = mynak_abs_url_from_public_path(mynak_public_path(''));
    $brandName = defined('MYNAK_BRAND_NAME') ? (string) MYNAK_BRAND_NAME : 'MY Nakliyat';

    $review = [
        '@context' => 'https://schema.org',
        '@type' => 'Review',
        'itemReviewed' => [
            '@type' => 'MovingCompany',
            'name' => $brandName,
            'url' => $homeUrl,
        ],
        'reviewRating' => [
            '@type' => 'Rating',
            'ratingValue' => (string) (float) ($cs['puan'] ?? 5),
            'bestRating' => '5',
            'worstRating' => '1',
        ],
        'name' => (string) $cs['baslik'],
        'reviewBody' => (string) ($cs['musteri_yorumu'] ?? $cs['ozet'] ?? ''),
        'author' => [
            '@type' => 'Person',
            'name' => (string) ($cs['musteri_ad'] ?? 'Mynakliyat Müşterisi'),
        ],
    ];
    if (!empty($cs['created_at'])) {
        $review['datePublished'] = date('c', (int) strtotime((string) $cs['created_at']));
    }
    ?>
    <script type="application/ld+json"><?php echo json_encode($review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

    <style>
    .mynak-cs-meta { margin-top: -20px; }
    .mynak-cs-chip { background: #f8f9fa; border-radius: 10px; padding: 14px; text-align: center; height: 100%; }
    .mynak-cs-chip strong { font-size: 0.95rem; }
    .mynak-cs-chip small { color: #6c757d; }
    .mynak-cs-review { background: linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%); border-left: 4px solid #ffc107; padding: 24px; border-radius: 12px; }
    .mynak-cs-review-avatar { font-size: 2.5rem; color: #ffc107; }
    .mynak-cs-review blockquote { font-size: 1.1rem; font-style: italic; color: #333; margin: 0; }
    .mynak-cs-cta { padding: 30px; background: #f8f9fa; border-radius: 12px; }
    </style>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/* ============================================================
 * LISTING
 * ============================================================ */
$listRows = [];
$res = $conn->query("SELECT id, slug, baslik, ozet, musteri_ad, musteri_yorumu, puan, kalkis_il, varis_il, ev_tipi, tasima_tarihi, fiyat_araligi, gorsel, created_at FROM case_studies WHERE status = 1 ORDER BY created_at DESC, id DESC LIMIT 60");
if ($res) {
    while ($x = $res->fetch_assoc()) { $listRows[] = $x; }
}

$page = [
    'id' => 0,
    'title' => 'Müşteri Hikayeleri',
    'slug' => 'musteri-hikayeleri',
    'content' => '',
    'meta_description' => 'Mynakliyat müşterilerinin gerçek taşınma hikayeleri: rotalar, ev tipleri, fiyat aralıkları ve doğrudan yorumlar. Sigortalı, profesyonel evden eve nakliyat tecrübeleri.',
    'meta_keywords' => 'müşteri yorumları, evden eve nakliyat hikayeleri, mynakliyat referans, taşınma deneyimi',
    'type' => 'page',
];
$page_title = 'Müşteri Hikayeleri';
$page_meta_description = (string) $page['meta_description'];
$allow_indexing = true;

require_once __DIR__ . '/includes/header.php';
?>

<main id="content">
    <section class="page-banner" style="background-color: #f8f9fa; padding: 80px 0 40px;">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1>Müşteri Hikayeleri</h1>
                    <p class="lead text-muted col-md-8 mx-auto mt-3">Gerçek müşterilerimizin taşınma süreçlerini, yazılı tekliften teslimata kadar tüm detayları ile okuyun.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="page-content py-5">
        <div class="container">
            <?php if (empty($listRows)): ?>
                <div class="alert alert-info text-center">Yakında burada müşteri hikayelerimiz yer alacak.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($listRows as $r): ?>
                        <?php $detayUrl = mynak_abs_url_from_public_path(mynak_public_path('musteri-hikayeleri/' . $r['slug'])); ?>
                        <div class="col-md-6 col-lg-4">
                            <article class="card h-100 shadow-sm border-0 mynak-cs-card">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars((string) ($r['kalkis_il'] ?? '')); ?> &rarr; <?php echo htmlspecialchars((string) ($r['varis_il'] ?? '')); ?></span>
                                        <?php if (!empty($r['ev_tipi'])): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars((string) $r['ev_tipi']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="h5 mb-3"><a href="<?php echo htmlspecialchars($detayUrl); ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars((string) $r['baslik']); ?></a></h3>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars((string) ($r['ozet'] ?? '')); ?></p>
                                    <?php if (!empty($r['musteri_yorumu'])): ?>
                                        <blockquote class="mynak-cs-card-quote mb-2">"<?php echo htmlspecialchars(mb_substr((string) $r['musteri_yorumu'], 0, 140, 'UTF-8') . (mb_strlen((string) $r['musteri_yorumu'], 'UTF-8') > 140 ? '...' : '')); ?>"</blockquote>
                                    <?php endif; ?>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php
                                            $puan = (float) ($r['puan'] ?? 5);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo '<i class="fas fa-star ' . ($i <= round($puan) ? 'text-warning' : 'text-muted') . '" style="font-size: .85rem;"></i>';
                                            }
                                            ?>
                                            <small class="text-muted ms-1"><?php echo htmlspecialchars((string) ($r['musteri_ad'] ?? '')); ?></small>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($detayUrl); ?>" class="btn btn-sm btn-outline-primary">Devamını Oku <i class="fas fa-arrow-right ms-1"></i></a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-5">
                <h3 class="h4 mb-3">Hizmetimizi denemek için hazır mısınız?</h3>
                <a href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('teklif-alin'))); ?>" class="btn btn-primary btn-lg me-2"><i class="fas fa-file-alt"></i> Ücretsiz Teklif Alın</a>
                <a href="<?php echo htmlspecialchars(mynak_abs_url_from_public_path(mynak_public_path('iletisim'))); ?>" class="btn btn-outline-primary btn-lg"><i class="fas fa-phone"></i> Bizi Arayın</a>
            </div>
        </div>
    </section>
</main>

<?php /* JSON-LD: ItemList (BreadcrumbList header.php tarafindan otomatik basiliyor) */ ?>
<?php
$itemList = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Mynakliyat Müşteri Hikayeleri',
    'itemListElement' => [],
];
foreach ($listRows as $i => $r) {
    $itemList['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'url' => mynak_abs_url_from_public_path(mynak_public_path('musteri-hikayeleri/' . $r['slug'])),
        'name' => (string) $r['baslik'],
    ];
}
?>
<?php if (!empty($itemList['itemListElement'])): ?>
<script type="application/ld+json"><?php echo json_encode($itemList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php endif; ?>

<style>
.mynak-cs-card { transition: transform .2s ease, box-shadow .2s ease; }
.mynak-cs-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,.08) !important; }
.mynak-cs-card-quote { font-size: .9rem; font-style: italic; color: #555; border-left: 3px solid #e9ecef; padding-left: 12px; }
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
