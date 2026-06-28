<?php
/**
 * SITE_URL tek tanım — session/SEO yönlendirme yok (robots.php uyumlu).
 */
declare(strict_types=1);

require_once __DIR__ . '/env_functions.php';

if (defined('SITE_URL')) {
    return;
}

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', realpath(__DIR__ . '/..') ?: dirname(__DIR__));
}

require_once __DIR__ . '/environment.php';
if (is_readable(__DIR__ . '/env_loader.php')) {
    require_once __DIR__ . '/env_loader.php';
}

/**
 * Canlı ortam: mynakliyat.com.tr host ailesi için SITE_URL’i .htaccess ile aynı şekilde
 * https://www… + isteğe bağlı alt path (.env’de http/apex kalsa bile tek kanonik köken).
 * Başka domain (staging) için .env değeri olduğu gibi (yalnızca sondaki / kaldırılır).
 */
function mynak_normalize_production_site_url(string $raw): string
{
    $default = 'https://www.mynakliyat.com.tr';
    $raw = trim($raw);
    if ($raw === '') {
        return $default;
    }
    $parts = parse_url($raw);
    if (!is_array($parts) || empty($parts['host'])) {
        return $default;
    }
    $host = strtolower((string) $parts['host']);
    if ($host !== 'mynakliyat.com.tr' && $host !== 'www.mynakliyat.com.tr') {
        return rtrim($raw, '/');
    }
    $path = isset($parts['path']) ? (string) $parts['path'] : '';
    $path = ($path === '' || $path === '/') ? '' : rtrim($path, '/');
    // Sık hata: yerel (http://host/mynakliyat) SITE_URL’si canlı .env’ye /mynakliyat path’i ile yapıştırılır;
    // asıl alan adı document root’ta (public_html) çalışır — menü /mynakliyat/... 404 verir. Kök sitede path’i yok say.
    if (strtolower($path) === '/mynakliyat') {
        $path = '';
    }

    return $default . $path;
}

if (!function_exists('mynak_preprocess_leaked_uri_path')) {
    /**
     * //C:/… veya C:/… gibi Windows sızıntılarını tek biçime (/C:/…) getirir; diğer path’lere dokunmaz.
     */
    function mynak_preprocess_leaked_uri_path(string $path): string
    {
        $p = str_replace('\\', '/', rawurldecode($path));
        // Alt klasör URL’si + sürücü sızıntısı: /mynakliyat/C:/xampp/… → /C:/xampp/…
        if (preg_match('#^((?:/[^/]+)+)/([A-Za-z](?:\:|%3A)/.*)$#', $p, $m) === 1) {
            $p = '/' . $m[2];
        }
        if (preg_match('#^/+([a-zA-Z]:(?:/|$).*)$#', $p, $m) === 1) {
            return '/' . $m[1];
        }
        if (preg_match('#^([a-zA-Z]:(?:/|$).*)$#', $p, $m) === 1) {
            return '/' . $m[1];
        }

        return $p;
    }
}

if (!function_exists('mynak_path_has_windows_filesystem_leak')) {
    /**
     * Path içinde /C:/…, /mynakliyat/C:/… (preprocess sonrası), xampp/htdocs/www sızıntısı var mı.
     */
    function mynak_path_has_windows_filesystem_leak(string $path): bool
    {
        if ($path === '' || $path === '/') {
            return false;
        }
        $p = function_exists('mynak_preprocess_leaked_uri_path')
            ? mynak_preprocess_leaked_uri_path($path)
            : str_replace('\\', '/', rawurldecode($path));
        $low = strtolower($p);

        return preg_match('#/[A-Za-z](?:\:|%3A)(/|$)#', $p) === 1
            || str_contains($low, 'xampp')
            || str_contains($low, '/htdocs/')
            || str_contains($low, '/www/');
    }
}

if (!function_exists('mynak_fix_uri_path_windows_leak')) {
    /**
     * İç link path’i: Windows dosya yolu sızıntısını web path’e çevirir; yoksa $path aynen döner.
     */
    function mynak_fix_uri_path_windows_leak(string $path): string
    {
        if (!function_exists('mynak_path_has_windows_filesystem_leak') || !mynak_path_has_windows_filesystem_leak($path)) {
            return $path;
        }
        if (!function_exists('mynak_normalize_leaked_windows_request_path')) {
            return $path;
        }
        $pre = function_exists('mynak_preprocess_leaked_uri_path') ? mynak_preprocess_leaked_uri_path($path) : $path;

        return mynak_normalize_leaked_windows_request_path($pre);
    }
}

if (!function_exists('mynak_xampp_drive_path_to_web_path')) {
    /**
     * DOCUMENT_ROOT eşleşmese bile: /C:/xampp/htdocs/proje/alt → /proje/alt (standart XAMPP dizinleri).
     */
    function mynak_xampp_drive_path_to_web_path(string $normPath): ?string
    {
        $p = function_exists('mynak_preprocess_leaked_uri_path')
            ? mynak_preprocess_leaked_uri_path($normPath)
            : str_replace('\\', '/', rawurldecode($normPath));
        if ($p === '' || $p[0] !== '/') {
            $p = '/' . ltrim($p, '/');
        }
        $patterns = [
            '#^/[a-zA-Z]:/+xampp/+htdocs/+([^/]+)(/.*)?$#i',
            '#^/[a-zA-Z]:/+wamp(?:64)?/+www/+([^/]+)(/.*)?$#i',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $p, $m) === 1) {
                $proj = $m[1];
                $tail = isset($m[2]) ? trim($m[2], '/') : '';
                if ($tail === '') {
                    return '/' . $proj;
                }

                return '/' . $proj . '/' . $tail;
            }
        }

        return null;
    }
}

if (!function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
    /**
     * DOCUMENT_ROOT ile PROJECT_ROOT göre web path öneki (örn. /mynakliyat). Alt klasör kurulumunda tek segment.
     */
    function mynak_infer_web_path_prefix_from_filesystem(): string
    {
        $dr = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/')) : '';
        if ($dr === '' || !defined('PROJECT_ROOT')) {
            return '';
        }
        $pr = realpath(PROJECT_ROOT);
        $pr = $pr ? str_replace('\\', '/', $pr) : str_replace('\\', '/', (string) PROJECT_ROOT);
        $drLower = strtolower($dr);
        $prLower = strtolower($pr);
        if (!str_starts_with($prLower, $drLower)) {
            return '';
        }
        $inside = substr($pr, strlen($dr));
        $inside = trim(str_replace('\\', '/', $inside), '/');
        if ($inside === '') {
            return '';
        }
        $first = explode('/', $inside, 2)[0];
        if ($first === '' || preg_match('#^[a-zA-Z]:$#', $first) === 1) {
            return '';
        }

        return '/' . $first;
    }
}

if (!function_exists('mynak_web_path_from_leaked_path_via_project_dir')) {
    /**
     * Sızıntı path içinde proje klasör adı (PROJECT_ROOT basename) geçiyorsa /proje/alt web path üretir.
     */
    function mynak_web_path_from_leaked_path_via_project_dir(string $normPath): ?string
    {
        if (!defined('PROJECT_ROOT')) {
            return null;
        }
        $folder = basename(str_replace('\\', '/', (string) PROJECT_ROOT));
        if ($folder === '' || $folder === '.' || $folder === '..') {
            return null;
        }
        $p = str_replace('\\', '/', rawurldecode($normPath));
        if ($p === '' || $p[0] !== '/') {
            $p = '/' . ltrim($p, '/');
        }
        if (preg_match('#(/' . preg_quote($folder, '#') . ')(/.*)?$#i', $p, $m) !== 1) {
            return null;
        }
        $rest = isset($m[2]) ? (string) $m[2] : '';
        $out = $m[1] . $rest;
        $out = rtrim(str_replace('//', '/', $out), '/');

        return $out === '' ? '/' . $folder : $out;
    }
}

if (!function_exists('mynak_normalize_leaked_windows_request_path')) {
    /**
     * Bazı Windows/XAMPP kurulumlarında istek path’i /C:/xampp/htdocs/proje/… şeklinde gelir;
     * DOCUMENT_ROOT’a göre web path’e (/proje/…) çevirir. Diğer ortamlarda $path aynen döner.
     */
    function mynak_normalize_leaked_windows_request_path(string $path): string
    {
        $norm = function_exists('mynak_preprocess_leaked_uri_path')
            ? mynak_preprocess_leaked_uri_path($path)
            : str_replace('\\', '/', rawurldecode($path));
        if (preg_match('#^/[a-zA-Z]:$#', $norm) === 1) {
            $norm .= '/';
        }
        if (preg_match('#^/[a-zA-Z]:(/|$)#', $norm) !== 1) {
            return $path;
        }
        $fs = ltrim($norm, '/');
        $dr = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/')) : '';
        $fsLower = strtolower($fs);
        $tryStrip = static function (string $baseFs, string $baseRoot): ?string {
            $baseRoot = rtrim(str_replace('\\', '/', $baseRoot), '/');
            if ($baseRoot === '') {
                return null;
            }
            $bfs = strtolower($baseFs);
            $br = strtolower($baseRoot);
            if (!str_starts_with($bfs, $br)) {
                return null;
            }
            $tail = substr($baseFs, strlen($baseRoot));
            $tail = ltrim(str_replace('\\', '/', $tail), '/');
            if ($tail === '') {
                $seg = basename($baseRoot);
                if ($seg !== '' && $seg !== '.' && $seg !== '..') {
                    return '/' . $seg;
                }

                return '/';
            }
            $rel = '/' . $tail;
            if (substr($rel, -1) === '/') {
                $rel = rtrim($rel, '/');
            }

            return $rel;
        };
        if ($dr !== '') {
            $rel = $tryStrip($fs, $dr);
            if ($rel !== null) {
                return $rel;
            }
        }
        if (defined('PROJECT_ROOT')) {
            $rp = realpath(PROJECT_ROOT);
            $rp = $rp ? str_replace('\\', '/', $rp) : str_replace('\\', '/', (string) PROJECT_ROOT);
            $rel = $tryStrip($fs, $rp);
            if ($rel !== null) {
                return $rel;
            }
        }

        if (function_exists('mynak_xampp_drive_path_to_web_path')) {
            $xb = mynak_xampp_drive_path_to_web_path($norm);
            if ($xb !== null) {
                return $xb;
            }
        }
        if (function_exists('mynak_web_path_from_leaked_path_via_project_dir')) {
            $via = mynak_web_path_from_leaked_path_via_project_dir($norm);
            if ($via !== null) {
                return $via;
            }
        }
        if (function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
            $inf = mynak_infer_web_path_prefix_from_filesystem();
            if ($inf !== '') {
                return $inf;
            }
        }

        return '/';
    }
}

if (!function_exists('mynak_sanitize_parsed_url_path')) {
    /**
     * parse_url(..., PHP_URL_PATH) çıktısındaki Windows sızıntısını (/C:/xampp/…) web path’e çevirir.
     */
    function mynak_sanitize_parsed_url_path(?string $path): string
    {
        if ($path === null || $path === '' || $path === '/') {
            return '';
        }
        $p = function_exists('mynak_preprocess_leaked_uri_path')
            ? mynak_preprocess_leaked_uri_path($path)
            : str_replace('\\', '/', rawurldecode($path));
        if (preg_match('#^/[a-zA-Z]:$#', $p) === 1) {
            $p .= '/';
        }
        if (preg_match('#^/[a-zA-Z]:(/|$)#', $p) === 1) {
            $p = mynak_normalize_leaked_windows_request_path($p);
        }
        if ($p !== '' && $p !== '/' && preg_match('#^/[a-zA-Z]:(/|$)#', str_replace('\\', '/', $p)) === 1) {
            $again = null;
            if (function_exists('mynak_web_path_from_leaked_path_via_project_dir')) {
                $again = mynak_web_path_from_leaked_path_via_project_dir($p);
            }
            if ($again === null && function_exists('mynak_xampp_drive_path_to_web_path')) {
                $again = mynak_xampp_drive_path_to_web_path($p);
            }
            if ($again === null && function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
                $again = mynak_infer_web_path_prefix_from_filesystem();
            }
            $p = ($again !== null && $again !== '') ? $again : '';
        }
        if ($p === '/' || $p === '') {
            return '';
        }

        return rtrim($p, '/');
    }
}

if (!function_exists('mynak_sanitize_complete_site_url')) {
    /**
     * Tam SITE_URL içindeki /C:/… veya C%3A sızıntısını kaldırır (blog linkleri ve kanonik kök).
     */
    function mynak_sanitize_complete_site_url(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if ($url === '') {
            return $url;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }
        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        if ($path === '' || $path === '/') {
            return $url;
        }
        $pathNorm = function_exists('mynak_preprocess_leaked_uri_path')
            ? mynak_preprocess_leaked_uri_path($path)
            : str_replace('\\', '/', rawurldecode($path));
        if (!function_exists('mynak_normalize_leaked_windows_request_path')) {
            return $url;
        }
        if (preg_match('#^/[a-zA-Z]:$#', $pathNorm) === 1) {
            $pathNorm .= '/';
        }
        if (preg_match('#^/[a-zA-Z]:(/|$)#', $pathNorm) !== 1) {
            return $url;
        }
        $fixedPath = mynak_normalize_leaked_windows_request_path($pathNorm);
        if ($fixedPath === $path || $fixedPath === $pathNorm) {
            $fixedPath = null;
            if (function_exists('mynak_xampp_drive_path_to_web_path')) {
                $fixedPath = mynak_xampp_drive_path_to_web_path($pathNorm);
            }
            if ($fixedPath === null && function_exists('mynak_web_path_from_leaked_path_via_project_dir')) {
                $fixedPath = mynak_web_path_from_leaked_path_via_project_dir($pathNorm);
            }
            if ($fixedPath === null && function_exists('mynak_infer_web_path_prefix_from_filesystem')) {
                $fixedPath = mynak_infer_web_path_prefix_from_filesystem();
            }
            if ($fixedPath === null || $fixedPath === '') {
                $scheme = $parts['scheme'] ?? 'http';
                $host = $parts['host'] ?? '';
                $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
                $query = isset($parts['query']) ? '?' . $parts['query'] : '';
                $frag = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

                return rtrim($scheme . '://' . $host . $port . $query . $frag, '/');
            }
        }
        $fpCheck = str_replace('\\', '/', (string) $fixedPath);
        if ($fpCheck !== '' && preg_match('#^/[a-zA-Z]:(/|$)#', $fpCheck) === 1) {
            $fixedPath = function_exists('mynak_infer_web_path_prefix_from_filesystem')
                ? mynak_infer_web_path_prefix_from_filesystem()
                : '';
        }
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $frag = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        $pathOut = ($fixedPath === '/' || $fixedPath === '') ? '' : $fixedPath;

        return rtrim($scheme . '://' . $host . $port . $pathOut . $query . $frag, '/');
    }
}

$is_local = mynak_http_host_is_local();

if ($is_local) {
    if (!function_exists('mynak_local_site_url_from_docroot')) {
        /**
         * SCRIPT_NAME güvenilmez (/C:/…/foo.php) olduğunda yalnızca DOCUMENT_ROOT + SCRIPT_FILENAME ile kök URL.
         */
        function mynak_local_site_url_from_docroot(): string
        {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
                    && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
            $scheme = $https ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $sf = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_FILENAME']) : '';
            $dr = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/')) : '';
            if ($sf === '' || $dr === '') {
                return $scheme . '://' . $host;
            }
            $sfLower = strtolower($sf);
            $drLower = strtolower($dr);
            if (!str_starts_with($sfLower, $drLower)) {
                return $scheme . '://' . $host;
            }
            $inside = substr($sf, strlen($dr));
            $dir = str_replace('\\', '/', dirname($inside));
            if ($dir === '/' || $dir === '.' || $dir === '') {
                return $scheme . '://' . $host;
            }
            $seg = trim($dir, '/');
            if ($seg === '' || str_contains($seg, '..')) {
                return $scheme . '://' . $host;
            }
            $first = explode('/', $seg, 2)[0];
            if ($first === '' || preg_match('#^[a-zA-Z]:$#', $first) === 1) {
                return $scheme . '://' . $host;
            }

            return $scheme . '://' . $host . '/' . $first;
        }
    }
    if (!function_exists('mynak_local_site_url_from_request')) {
        /**
         * XAMPP: /mynakliyat/index.php → http://host/mynakliyat; DocumentRoot kökünde /index.php → http://host.
         * SCRIPT_NAME bazen /C:/…/index.php veya ters eğik çizgi içerir; o zaman DOCUMENT_ROOT + SCRIPT_FILENAME kullanılır.
         */
        function mynak_local_site_url_from_request(): string
        {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
                    && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
            $scheme = $https ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', rawurldecode((string) $_SERVER['SCRIPT_NAME'])) : '';

            $fromScriptName = static function (string $sn) use ($scheme, $host): ?string {
                if ($sn !== '' && preg_match('#^/([^/]+)/[^/]+\.php$#', $sn, $m)) {
                    if (preg_match('#^[a-zA-Z]:$#', $m[1]) === 1) {
                        return null;
                    }

                    return $scheme . '://' . $host . '/' . $m[1];
                }

                return null;
            };

            $captured = $fromScriptName($script);
            if ($captured !== null && preg_match('#/[a-zA-Z]:/#', $script) !== 1) {
                return $captured;
            }

            $sf = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_FILENAME']) : '';
            $dr = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/')) : '';
            if ($sf !== '' && $dr !== '') {
                $sfLower = strtolower($sf);
                $drLower = strtolower($dr);
                if (str_starts_with($sfLower, $drLower)) {
                    $inside = substr($sf, strlen($dr));
                    $dir = dirname($inside);
                    $dir = str_replace('\\', '/', (string) $dir);
                    if ($dir !== '/' && $dir !== '.' && $dir !== '') {
                        $seg = trim($dir, '/');
                        if ($seg !== '') {
                            $first = explode('/', $seg, 2)[0];
                            if (preg_match('#^[a-zA-Z]:$#', $first) !== 1) {
                                return $scheme . '://' . $host . '/' . $first;
                            }
                        }
                    }
                }
            }

            if ($captured !== null) {
                return $captured;
            }

            return $scheme . '://' . $host;
        }
    }
    $localUrl = mynak_local_site_url_from_request();
    $lu = parse_url($localUrl);
    if (is_array($lu) && !empty($lu['path'])) {
        $lp = str_replace('\\', '/', rawurldecode((string) $lu['path']));
        if ($lp !== '' && preg_match('#^/[a-zA-Z]:(/|$)#', $lp) === 1) {
            $lp = mynak_normalize_leaked_windows_request_path($lp);
            $port = isset($lu['port']) ? ':' . (int) $lu['port'] : '';
            $localUrl = ($lu['scheme'] ?? 'http') . '://' . ($lu['host'] ?? 'localhost') . $port;
            $localUrl .= ($lp === '/' || $lp === '') ? '' : $lp;
        }
    }
    $localUrl = rtrim(mynak_sanitize_complete_site_url(rtrim($localUrl, '/')), '/');
    $lu2 = parse_url($localUrl);
    $lp2 = isset($lu2['path']) ? str_replace('\\', '/', rawurldecode((string) $lu2['path'])) : '';
    if ($lp2 !== '' && preg_match('#^/[a-zA-Z]:(/|$)#', $lp2) === 1) {
        $localUrl = rtrim(mynak_local_site_url_from_docroot(), '/');
    }
    // Son çare: localhost SITE_URL asla dosya yolu taşımasın (Blog menüsü → /C:/xampp/... üretir).
    $luFinal = parse_url($localUrl);
    $lpF = isset($luFinal['path']) ? str_replace('\\', '/', rawurldecode((string) $luFinal['path'])) : '';
    $lpFL = strtolower($lpF);
    $sitePathToxic = $lpF !== '' && (
        preg_match('#^/[a-zA-Z](?:\:|%3A)(/|$)#', $lpF) === 1
        || str_contains($lpFL, 'xampp')
        || str_contains($lpFL, '/htdocs/')
        || str_contains($lpFL, '/www/')
    );
    if ($sitePathToxic) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $port = isset($luFinal['port']) ? ':' . (int) $luFinal['port'] : '';
        $origin = rtrim($scheme . '://' . $host . $port, '/');
        $pref = function_exists('mynak_infer_web_path_prefix_from_filesystem')
            ? rtrim(mynak_infer_web_path_prefix_from_filesystem(), '/')
            : '';
        $localUrl = $pref !== '' ? $origin . $pref : $origin;
        $localUrl = rtrim(mynak_sanitize_complete_site_url(rtrim($localUrl, '/')), '/');
    }
    define('SITE_URL', mynak_sanitize_complete_site_url(rtrim($localUrl, '/')));
} else {
    mynak_load_dotenv(PROJECT_ROOT . DIRECTORY_SEPARATOR . '.env');
    define('SITE_URL', mynak_sanitize_complete_site_url(mynak_normalize_production_site_url(mynak_env_str('SITE_URL'))));
}
