<?php
declare(strict_types=1);
/**
 * Eski giriş — tüm trafik index.php ön kumandasına yönlendirilir.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/site_url_define.php';
require_once __DIR__ . '/config/seo.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug'], '/') : '';
$parts = $slug === '' ? [] : explode('/', $slug);
$inner = $slug === '' ? '' : implode('/', $parts);
$target = mynak_abs_url_from_public_path(mynak_public_path($inner));

header('Location: ' . $target, true, 301);
exit;
