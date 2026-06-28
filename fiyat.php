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

$site_settings = mynak_site_settings_bootstrap($conn);

$page_title = 'İzmir Nakliyat Fiyatları 2026 | MY Nakliyat';
$hide_title_suffix = true;
$page_meta_description = 'İzmir evden eve nakliyat fiyat aralıklarını şeffaf şekilde sunuyoruz. Daire tipi, kat ve mesafe kriterleri. Ücretsiz keşif için teklif alın.';
$allow_indexing = true;
$mynak_head_stylesheets = [
    ASSET_PATH . 'css/lead-magnet-landing.css',
    ASSET_PATH . 'css/pricing-landing.css',
];

$page = [
    'id' => 0,
    'slug' => 'fiyat',
    'title' => 'İzmir Nakliyat Fiyatları',
    'type' => 'service',
    'content' => '',
];

require_once __DIR__ . '/includes/header.php';
?>

<main id="content">
    <?php require __DIR__ . '/includes/partials/pricing_landing_content.php'; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
