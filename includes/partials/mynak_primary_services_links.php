<?php
declare(strict_types=1);

if (!function_exists('seo_rt_primary_service_lines')) {
    require_once dirname(__DIR__) . '/seo_runtime/internal_linking.php';
}
if (!function_exists('seo_rt_primary_service_public_url')) {
    require_once dirname(__DIR__) . '/seo_runtime/paths.php';
}

$lines = seo_rt_primary_service_lines();
if ($lines === []) {
    return;
}

$currentSlug = isset($mynak_current_content_slug) ? trim((string) $mynak_current_content_slug) : '';
$canonicalOrigin = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
?>
<aside class="mynak-primary-services-links card border-0 shadow-sm mt-4" aria-label="MY Nakliyat birincil hizmetleri">
    <div class="card-body p-4">
        <h2 class="h5 mb-3">MY Nakliyat Profesyonel Hizmetler</h2>
        <p class="small text-muted mb-3">İzmir merkezli evden eve nakliyat, şehirler arası taşıma, ofis taşıma, eşya depolama ve asansörlü taşımacılık.</p>
        <ul class="list-unstyled mb-0 row g-2">
            <?php foreach ($lines as $line):
                $graphSlug = (string) ($line['graph_slug'] ?? '');
                if ($graphSlug !== '' && $currentSlug !== '' && $graphSlug === $currentSlug) {
                    continue;
                }
                $pubSlug = (string) ($line['public_slug'] ?? '');
                if ($pubSlug !== '' && $currentSlug !== '' && $pubSlug === $currentSlug) {
                    continue;
                }
                $href = $canonicalOrigin !== ''
                    ? seo_rt_primary_service_public_url($canonicalOrigin, $graphSlug)
                    : mynak_public_path($pubSlug);
            ?>
            <li class="col-md-6">
                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" class="d-block p-2 rounded text-decoration-none mynak-primary-svc-link">
                    <strong><?= htmlspecialchars((string) ($line['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="d-block small text-muted"><?= htmlspecialchars((string) ($line['service_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</aside>
<style>
.mynak-primary-svc-link { background: #f8faff; border: 1px solid #e8eef8; color: #1a2238; transition: background .15s ease; }
.mynak-primary-svc-link:hover { background: #eef4ff; color: #1a73e8; }
</style>
