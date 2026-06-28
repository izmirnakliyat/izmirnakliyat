<?php
declare(strict_types=1);
/** @var array $data mynak_google_reviews_fetch çıktısı */
$reviews_rows = $data['reviews_rows'] ?? [];
$g_maps_url = (string) ($data['g_maps_url'] ?? '');
$g_total = (string) ($data['g_total'] ?? '');
$g_rating = (string) ($data['g_rating'] ?? '');
$avatar_colors = $data['avatar_colors'] ?? ['#4285F4'];
if ($reviews_rows === []) {
    return;
}
?>
<section class="mynak-gbp-reviews-grid my-5" id="google-musteri-yorumlari" aria-label="Google Business müşteri yorumları">
    <div class="mynak-gbp-reviews-summary card border-0 shadow-sm mb-4">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="h4 mb-2">Google'da Doğrulanmış Müşteri Yorumları</h2>
                <p class="mb-0 text-muted small">
                    Kaynak: Google Business Profile · Ortalama <strong><?= htmlspecialchars($g_rating, ENT_QUOTES, 'UTF-8'); ?></strong> / 5
                    · <strong><?= htmlspecialchars($g_total, ENT_QUOTES, 'UTF-8'); ?>+</strong> yorum
                </p>
            </div>
            <a href="<?= htmlspecialchars($g_maps_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener noreferrer">
                Tüm yorumları Google'da gör
            </a>
        </div>
    </div>
    <div class="row g-3">
        <?php foreach ($reviews_rows as $i => $gr):
            $color = $avatar_colors[$i % count($avatar_colors)];
            $initials = function_exists('mynak_google_reviews_author_initials')
                ? mynak_google_reviews_author_initials((string) ($gr['author_name'] ?? ''))
                : '?';
            $rating = (int) ($gr['rating'] ?? 5);
            $reviewText = trim((string) ($gr['text'] ?? ''));
            if ($reviewText === '') {
                continue;
            }
        ?>
        <div class="col-md-6">
            <article class="mynak-gbp-review-card card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="mynak-gbp-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-semibold"
                                 style="width:42px;height:42px;background:<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>;">
                                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars((string) ($gr['author_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small text-muted"><?= htmlspecialchars((string) ($gr['relative_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> · Google</div>
                            </div>
                        </div>
                        <div class="text-warning small" aria-label="<?= $rating; ?> / 5 yıldız">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fas fa-star<?= $s <= $rating ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="mb-0 small"><?= nl2br(htmlspecialchars(mb_strimwidth($reviewText, 0, 500, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8')); ?></p>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
</section>
