<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/site_url_define.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/video_sitemap_build.php';

header('Content-Type: application/xml; charset=utf-8');
$site_url = rtrim((string) SITE_URL, '/');
$built = mynak_build_video_sitemap_xml($conn, $site_url);
echo $built['xml'];
