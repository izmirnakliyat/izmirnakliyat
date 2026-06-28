<?php
/**
 * Statik "Googlebot benzeri" tarama: kaynak dosyalarda SEO/tarama riski üreten kalıplar.
 * Canlı HTTP crawl değildir; çıktıyı inceleyip false positive'leri elersiniz.
 *
 * Çalıştırma: php scripts/googlebot-static-audit.php
 */
declare(strict_types=1);

$root = realpath(dirname(__DIR__));
if ($root === false) {
    fwrite(STDERR, "Kök bulunamadı.\n");
    exit(1);
}

$skipDirNames = [
    '.git', '.svn', 'node_modules', 'vendor', '.idea', '.vscode', '.cursor',
    'admin', // robots Disallow; canlı yüzey denetimi (Google taramaz)
];
$extensions = ['php', 'js', 'html', 'htm', 'xml', 'txt', 'css', 'json', 'md', 'twig'];
$maxFileSize = 2 * 1024 * 1024;

// robots.txt ile çelişebilecek veya 301/duplicate üreten kalıplar
$rules = [
    'non_www_site' => [
        'label' => 'www olmayan mynakliyat.com.tr (kanonik www ile çakışma riski)',
        'regex' => '/https?:\/\/mynakliyat\.com\.tr\b/i',
    ],
    'blog_php' => [
        'label' => 'blog.php referansı (robots Disallow; uzantısız /blog tercih)',
        'regex' => '/\bblog\.php\b/i',
    ],
    'galeri_php' => [
        'label' => 'galeri.php referansı (kanonik /galeri)',
        'regex' => '/\bgaleri\.php\b/i',
    ],
    'index_php_link' => [
        'label' => 'href içinde index.php (kanonik /)',
        'regex' => '/href\s*=\s*["\'][^"\'\r\n]*index\.php/i',
    ],
    'slug_router' => [
        'label' => 'slug-router.php dış referans (dâhilî yönlendirme; dış link olmamalı)',
        'regex' => '/["\'][^"\'\r\n]*slug-router\.php/i',
    ],
    'disallowed_path_href' => [
        'label' => 'href içinde robots ile engelli path (/admin, /config, /includes, /logs, /cache, /cron)',
        'regex' => '/href\s*=\s*["\'][^"\'\r\n]*\/(admin|config|includes|logs|cache|cron)\//i',
    ],
    'ajax_href' => [
        'label' => 'href içinde site /ajax/ (cdnjs …/ajax/libs/ hariç)',
        'regex' => '/href\s*=\s*["\'][^"\'\r\n]*\/ajax\/(?!libs\/)/i',
    ],
    'teklif_al_redirect' => [
        'label' => '/teklif-al yolu (kanonik /teklif-alin; -in hariç)',
        'regex' => '/\/teklif-al(?!in)(?:["\'\/?#]|$)/i',
    ],
    'http_mixed' => [
        'label' => 'http://www.mynakliyat veya http://mynakliyat (HTTPS site için zayıf sinyal)',
        'regex' => '/http:\/\/(www\.)?mynakliyat\.com\.tr/i',
    ],
    'noindex_meta' => [
        'label' => 'noindex meta (bilgi; sayfa bilinçli mi?)',
        'regex' => '/noindex/i',
    ],
    'empty_href' => [
        'label' => 'href="" veya href=\'\'',
        'regex' => '/href\s*=\s*["\']["\']/i',
    ],
];

$findings = [];
$fileCount = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $current) use ($root, $skipDirNames) {
            $path = $current->getPathname();
            $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
            foreach ($skipDirNames as $sd) {
                if ($rel === $sd || strpos($rel, $sd . '/') === 0) {
                    return false;
                }
            }
            if (strpos($rel, 'uploads/') === 0) {
                return false;
            }
            return true;
        }
    ),
    RecursiveIteratorIterator::LEAVES_ONLY
);

/** @var SplFileInfo $file */
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $extensions, true)) {
        continue;
    }
    if ($file->getSize() > $maxFileSize) {
        continue;
    }
    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    if ($rel === 'scripts/googlebot-static-audit.php') {
        continue;
    }
    $content = @file_get_contents($path);
    if ($content === false) {
        continue;
    }
    $fileCount++;

    foreach ($rules as $key => $rule) {
        if (!preg_match($rule['regex'], $content)) {
            continue;
        }
        $lines = [];
        foreach (preg_split("/\r\n|\n|\r/", $content) as $num => $line) {
            if (preg_match($rule['regex'], $line)) {
                $lines[] = $num + 1;
            }
        }
        $findings[] = [
            'file' => $rel,
            'rule' => $key,
            'label' => $rule['label'],
            'lines' => array_slice($lines, 0, 8),
            'line_count' => count($lines),
        ];
    }
}

usort($findings, function ($a, $b) {
    return [$a['rule'], $a['file']] <=> [$b['rule'], $b['file']];
});

echo "=== Googlebot-benzeri statik tarama ===\n";
echo "Kök: {$root}\n";
echo "Taranan dosya: {$fileCount}\n";
echo "Bulgu kaydı: " . count($findings) . "\n\n";

$byRule = [];
foreach ($findings as $f) {
    $byRule[$f['rule']] = ($byRule[$f['rule']] ?? 0) + 1;
}
foreach ($byRule as $r => $c) {
    echo "  [{$r}] {$c} dosya\n";
}
echo "\n--- Detay (satır: ilk 8) ---\n";

$prev = null;
foreach ($findings as $f) {
    if ($prev !== $f['rule']) {
        echo "\n## " . $f['label'] . " [" . $f['rule'] . "]\n";
        $prev = $f['rule'];
    }
    $ln = implode(', ', $f['lines']);
    $more = $f['line_count'] > 8 ? ' … +' . ($f['line_count'] - 8) . ' satır' : '';
    echo "  {$f['file']}: {$ln}{$more}\n";
}

if ($findings === []) {
    echo "\nTanımlı kalıplarda bulgu yok (veya tüm eşleşmeler filtrelendi).\n";
}

echo "\nNot: admin/, cache/html/, XML export ve yorum satırları false positive üretebilir.\n";
exit(0);
