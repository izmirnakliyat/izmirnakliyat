<?php
declare(strict_types=1);
/**
 * XAMPP/Windows: Apache bazen REQUEST_URI, SCRIPT_NAME, PHP_SELF içine /C:/xampp/htdocs/... yazar.
 * bootstrap ilk satırlarında çalışır; site_url_define yok — saf dize kuralı.
 */
if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
    return;
}

if (!function_exists('mynak_windows_shim_fix_uri_path')) {
    function mynak_windows_shim_fix_uri_path(string $pathPart): string
    {
        if ($pathPart === '' || $pathPart === '/') {
            return $pathPart;
        }
        $norm = str_replace('\\', '/', rawurldecode($pathPart));
        while (strlen($norm) >= 2 && str_starts_with($norm, '//')) {
            $norm = substr($norm, 1);
        }
        $looksBad = preg_match('#^/[a-zA-Z](?:\:|%3A)(/|$)#', $norm) === 1
            || str_contains(strtolower($norm), 'xampp');
        if (!$looksBad) {
            return $pathPart;
        }
        if (preg_match('#^/[a-zA-Z](?:\:|%3A)(?:/[^/]+)*/(?:htdocs|www)/([^/]+)(/.*)?$#i', $norm, $m) === 1
            || preg_match('#^/(?:[^/]+/)+[a-zA-Z](?:\:|%3A)(?:/[^/]+)*/(?:htdocs|www)/([^/]+)(/.*)?$#i', $norm, $m) === 1) {
            $proj = $m[1];
            $tail = isset($m[2]) ? (string) $m[2] : '';
            if (preg_match('#^[a-zA-Z]:$#', $proj) === 1) {
                return $pathPart;
            }

            return '/' . trim($proj . $tail, '/');
        }

        return $pathPart;
    }
}

if (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    $qpos = strpos($uri, '?');
    $pathPart = $qpos !== false ? substr($uri, 0, $qpos) : $uri;
    $queryPart = $qpos !== false ? substr($uri, $qpos) : '';
    $fixed = mynak_windows_shim_fix_uri_path($pathPart);
    if ($fixed !== $pathPart) {
        $_SERVER['REQUEST_URI'] = $fixed . $queryPart;
    }
}

foreach (['SCRIPT_NAME', 'PHP_SELF'] as $__mynakShimKey) {
    if (!empty($_SERVER[$__mynakShimKey]) && is_string($_SERVER[$__mynakShimKey])) {
        $__mynakFixed = mynak_windows_shim_fix_uri_path($_SERVER[$__mynakShimKey]);
        if ($__mynakFixed !== $_SERVER[$__mynakShimKey]) {
            $_SERVER[$__mynakShimKey] = $__mynakFixed;
        }
    }
}
unset($__mynakShimKey, $__mynakFixed);
