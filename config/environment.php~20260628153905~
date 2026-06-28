<?php
/**
 * Yerel geliştirme vs canlı ayrımı (DB ve SITE_URL farklı kurallar).
 */
declare(strict_types=1);

if (!function_exists('mynak_http_host_is_local')) {
    /**
     * İstek HTTP(S) ise ve host yerel kabul ediliyorsa true. CLI’da false (SITE_URL canlı kalsın).
     */
    function mynak_http_host_is_local(): bool
    {
        $raw = $_SERVER['HTTP_HOST'] ?? '';
        if ($raw === '') {
            return false;
        }
        $h = strtolower($raw);
        $h = preg_replace('/:\d+$/', '', $h) ?? $h;
        if (in_array($h, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }
        if (str_starts_with($h, 'localhost.')) {
            return true;
        }
        if (str_ends_with($h, '.local') || str_ends_with($h, '.test')) {
            return true;
        }
        // Özel / yerel ağ IP (192.168.x, 10.x, 127.x dışındaki rezerve) — scrub + SITE_URL yerel dalı açılır
        if (filter_var($h, FILTER_VALIDATE_IP)) {
            $isPublic = filter_var($h, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($isPublic === false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('mynak_should_scrub_windows_path_hrefs')) {
    /**
     * Menü/href düzeltmesi: localhost benzeri host veya belgede XAMPP/WAMP kökü.
     */
    function mynak_should_scrub_windows_path_hrefs(): bool
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return false;
        }
        if (function_exists('mynak_http_host_is_local') && mynak_http_host_is_local()) {
            return true;
        }
        $dr = strtolower(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));
        $sf = strtolower(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? '')));

        return str_contains($dr, 'xampp') || str_contains($dr, 'wamp') || str_contains($dr, 'laragon')
            || str_contains($sf, 'xampp') || str_contains($sf, 'wamp') || str_contains($sf, 'laragon');
    }
}

if (!function_exists('mynak_db_use_local_credentials')) {
    /** CLI / cron: yerel DB; tarayıcıda yalnızca yerel host’ta yerel DB */
    function mynak_db_use_local_credentials(): bool
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return true;
        }

        return mynak_http_host_is_local();
    }
}

if (!function_exists('mynak_allow_dev_debug_tools')) {
    /**
     * Form debug, admin/debug_test vb. yalnızca yerelde (veya MYNAK_ALLOW_FORM_DEBUG=1 ile LAN).
     * Canlı domain’de yanlışlıkla açık kalmayı önler.
     */
    function mynak_allow_dev_debug_tools(): bool
    {
        if (function_exists('mynak_http_host_is_local') && mynak_http_host_is_local()) {
            return true;
        }
        if (function_exists('mynak_env_str')) {
            $flag = strtolower(trim((string) mynak_env_str('MYNAK_ALLOW_FORM_DEBUG')));
            if ($flag === '1' || $flag === 'true' || $flag === 'yes') {
                return true;
            }
        }

        return false;
    }
}
