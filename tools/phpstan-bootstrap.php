<?php
/**
 * PHPStan bootstrap — projedeki global ortamı analiz aracına bildirir.
 *
 * Bu dosya analiz sırasında parse edilmez; yalnızca PHPStan'ın global state
 * varsayımlarını oluşturmak için yüklenir. Çalışma zamanına etkisi yoktur.
 */

declare(strict_types=1);

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://www.mynakliyat.com.tr');
}
if (!defined('MYNAK_SEO_FAQ_EXTRACTOR_LOADED')) {
    define('MYNAK_SEO_FAQ_EXTRACTOR_LOADED', true);
}

// Global db handle (mysqli) — proje genelinde include'larla taşınır.
/** @var \mysqli $conn */
global $conn;
