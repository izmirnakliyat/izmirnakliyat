<?php
/**
 * Faz 1 + 16: Statik SEO / varlık denetimi (kaynak taraması).
 * Canlı HTTP doğrulaması: --http (SITE_URL üzerinde HEAD; yavaş, üretim için).
 *
 * php scripts/seo_full_audit.php
 * php scripts/seo_full_audit.php --http
 */
declare(strict_types=1);

$doHttp = in_array('--http', $argv ?? [], true);

$root = realpath(dirname(__DIR__));
if ($root === false) {
    fwrite(STDERR, "Kök bulunamadı.\n");
    exit(1);
}

require_once $root . '/config/config.php';
require_once $root . '/includes/functions.php';

$origin = rtrim(SITE_URL, '/');
$pathPrefix = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
$pathPrefix = ($pathPrefix && $pathPrefix !== '/') ? rtrim($pathPrefix, '/') : '';

$skipDirs = ['.git', 'node_modules', 'vendor', '.idea', '.vscode', '.cursor', 'cache', 'logs'];
$exts = ['php', 'html', 'htm', 'twig', 'js', 'css'];
$maxSize = 2 * 1024 * 1024;

$missingLocal = [];
$seenUrls = [];
$resolvedPaths = [];

$attrRe = '/\b(?:href|src)\s*=\s*([\'"])([^\'"]+)\1/i';

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $current) use ($root, $skipDirs) {
            $rel = str_replace('\\', '/', substr($current->getPathname(), strlen($root) + 1));
            foreach ($skipDirs as $sd) {
                if ($rel === $sd || strpos($rel, $sd . '/') === 0) {
                    return false;
                }
            }
            if (strpos($rel, 'uploads/') === 0 && !str_ends_with($rel, '.php')) {
                return false;
            }

            return true;
        }
    ),
    RecursiveIteratorIterator::LEAVES_ONLY
);

/**
 * Web köküne göre yerel dosya yolunu çöz.
 */
function resolve_local_public_file(string $root, string $webPath): ?string
{
    $webPath = '/' . ltrim($webPath, '/');
    $candidates = [$root . str_replace('/', DIRECTORY_SEPARATOR, $webPath)];

    foreach ($candidates as $fs) {
        if (is_file($fs)) {
            return $fs;
        }
    }

    return null;
}

/**
 * Mutlak URL içinden site içi path çıkar (localhost alt klasör uyumu).
 */
function url_to_site_path(string $url, string $origin, string $pathPrefix): ?string
{
    if (!preg_match('#^https?://#i', $url)) {
        return null;
    }
    $p = parse_url($url);
    if (!is_array($p) || empty($p['path'])) {
        return null;
    }
    $hostOk = isset($p['host']) && preg_match('#mynakliyat\.com\.tr$#i', $p['host']);
    $localOk = isset($p['host']) && (stripos($p['host'], 'localhost') !== false || $p['host'] === '127.0.0.1');
    if (!$hostOk && !$localOk) {
        return null;
    }
    $path = $p['path'];
    if ($pathPrefix !== '' && (strpos($path, $pathPrefix . '/') === 0 || $path === $pathPrefix)) {
        $path = substr($path, strlen($pathPrefix)) ?: '/';
    }

    return '/' . ltrim($path, '/');
}

/** @var SplFileInfo $file */
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $exts, true)) {
        continue;
    }
    if ($file->getSize() > $maxSize) {
        continue;
    }
    $relFile = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    if (str_starts_with($relFile, 'scripts/seo_full_audit.php')) {
        continue;
    }
    $content = @file_get_contents($file->getPathname());
    if ($content === false) {
        continue;
    }
    if (!preg_match_all($attrRe, $content, $m, PREG_SET_ORDER)) {
        continue;
    }
    foreach ($m as $match) {
        $raw = trim($match[2]);
        if ($raw === '' || str_starts_with($raw, 'data:') || str_starts_with($raw, '#')
            || preg_match('#^(mailto:|tel:|javascript:)#i', $raw)) {
            continue;
        }
        if (preg_match('#^https?://#i', $raw)) {
            if (preg_match('#(googleapis|gstatic|google\.com|googletagmanager|cdnjs|cdn\.jsdelivr|translate\.google)#i', $raw)) {
                continue;
            }
            $sp = url_to_site_path($raw, $origin, $pathPrefix);
            if ($sp === null) {
                continue;
            }
            $raw = $sp;
        }
        if ($raw[0] !== '/') {
            continue;
        }
        $pathOnly = parse_url($raw, PHP_URL_PATH) ?: $raw;
        if (!preg_match('#^/(assets|uploads|js|wp-content)/#i', $pathOnly)) {
            continue;
        }
        if (preg_match('#\.php(\?|$)#i', $pathOnly)) {
            continue;
        }
        $key = $pathOnly;
        if (isset($seenUrls[$key])) {
            continue;
        }
        $fs = resolve_local_public_file($root, $pathOnly);
        if ($fs !== null) {
            $seenUrls[$key] = true;
            $resolvedPaths[$pathOnly] = true;
            continue;
        }
        $missingLocal[] = ['file' => $relFile, 'ref' => $pathOnly];
        $seenUrls[$key] = false;
    }
}

echo "=== SEO tam statik denetim ===\n";
echo 'Kök: ' . $root . "\n";
echo 'SITE_URL: ' . SITE_URL . "\n";
echo 'Yerel eksik varlık (assets/uploads/js/wp-content): ' . count($missingLocal) . "\n\n";

foreach (array_slice($missingLocal, 0, 80) as $row) {
    echo $row['ref'] . ' ← ' . $row['file'] . "\n";
}
if (count($missingLocal) > 80) {
    echo '… +' . (count($missingLocal) - 80) . " kayıt\n";
}

$placeholder = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'default.webp';
echo "\nPlaceholder default.webp: " . (is_file($placeholder) ? 'OK' : 'EKSİK') . "\n";

// robots / sitemap hızlı kontrol
$robots = $root . '/robots.txt';
$sitemap = $root . '/sitemap.xml';
echo 'robots.txt: ' . (is_file($robots) ? 'OK' : 'EKSİK') . "\n";
echo 'sitemap.xml: ' . (is_file($sitemap) ? 'OK (dosya; güncelleme: generate_full_sitemap.php)' : 'EKSİK') . "\n";

if ($doHttp && $missingLocal === []) {
    echo "\n--http: örnek HEAD istekleri (ilk 15 çözümlü yol)…\n";
    $n = 0;
    foreach (array_keys($resolvedPaths) as $p) {
        if ($n++ >= 15) {
            break;
        }
        $url = $origin . $p;
        $ch = curl_init($url);
        if ($ch === false) {
            echo "curl yok\n";
            break;
        }
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo $code . ' ' . $url . "\n";
    }
}

echo "\nNot: Veritabanı / slug sayfaları bu betikte yok; googlebot-static-audit.php ile birlikte kullanın.\n";
exit(count($missingLocal) > 0 ? 2 : 0);
