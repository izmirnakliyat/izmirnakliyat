<?php
/**
 * Ana sitemap.xml urlset üretimi (tek kaynak — generate_full_sitemap + admin).
 * &lt;loc&gt; adreslerinde ? yok: seo_runtime_sitemap_allow_loc().
 */
declare(strict_types=1);

require_once __DIR__ . '/seo_runtime.php';
require_once __DIR__ . '/mynak_canonical_slug_redirects.php';

function sitemap_build_is_redirect_source_slug(string $slug): bool
{
    static $sources = null;
    if (!is_array($sources)) {
        $sources = array_fill_keys(array_keys(mynak_seo_cannibalization_redirect_map()), true);
    }

    return isset($sources[strtolower(trim($slug, '/'))]);
}

/**
 * @param array<string, bool> $opts
 * @return array{xml: string, url_count: int, seen: array<string, true>}
 */
function sitemap_build_main_urlset(mysqli $conn, string $site_url, array $opts = []): array
{
    $site_url = rtrim($site_url, '/');

    $opts = array_merge([
        'include_static' => true,
        'include_llm' => true,
        'include_services' => true,
        'include_blog' => true,
        // blog.php: kategori/etiket sayfaları canonical → /blog; sitemap’e koymak Semrush “Non-canonical in sitemap” üretir
        'include_blog_categories' => false,
        'include_pages' => true,
    ], $opts);

    $seen = [];
    $lines = [];
    $count = 0;

    $esc = static function (string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    };

    $tableExists = static function (mysqli $c, string $table): bool {
        $t = preg_replace('/[^a-z0-9_]/i', '', $table);
        if ($t === '') {
            return false;
        }
        $r = @$c->query("SHOW TABLES LIKE '{$t}'");

        return $r && $r->num_rows > 0;
    };

    $hasCol = static function (mysqli $c, string $table, string $col): bool {
        $t = preg_replace('/[^a-z0-9_]/i', '', $table);
        $col = preg_replace('/[^a-z0-9_]/i', '', $col);
        if ($t === '' || $col === '') {
            return false;
        }
        $r = @$c->query("SHOW COLUMNS FROM `{$t}` LIKE '{$col}'");

        return $r && $r->num_rows > 0;
    };

    $append = static function (
        string $loc,
        string $lastmod,
        string $changefreq,
        string $priority,
        ?string $extraBlock = null
    ) use (&$seen, &$lines, &$count, $esc, $site_url): void {
        if (!seo_runtime_sitemap_allow_loc($loc, $site_url)) {
            return;
        }
        if (isset($seen[$loc])) {
            return;
        }
        $seen[$loc] = true;
        $lines[] = '  <url>';
        $lines[] = '    <loc>' . $esc($loc) . '</loc>';
        $lines[] = '    <lastmod>' . $esc($lastmod) . '</lastmod>';
        $lines[] = '    <changefreq>' . $esc($changefreq) . '</changefreq>';
        $lines[] = '    <priority>' . $esc($priority) . '</priority>';
        if ($extraBlock !== null && $extraBlock !== '') {
            $lines[] = $extraBlock;
        }
        $lines[] = '  </url>';
        $count++;
    };

    $today = date('Y-m-d');

    $mkSlug = static function (string $text): string {
        $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
        $english = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];
        $text = str_replace($turkish, $english, $text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    };

    $xmlHead = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n"
        . '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n"
        . '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    // 1 — Ana sayfa
    $append(
        $site_url . '/',
        $today,
        'weekly',
        '1.0',
        '    <xhtml:link rel="alternate" hreflang="tr" href="' . $esc($site_url . '/') . '" />'
    );

    // 2 — Sabit rotalar (slug-router / .php eşlemeleri)
    if ($opts['include_static']) {
        $staticRoutes = [
            'blog' => ['weekly', '0.9'],
            'iletisim' => ['monthly', '0.85'],
            'galeri' => ['weekly', '0.75'],
            'teklif-alin' => ['monthly', '0.85'],
            'fiyat' => ['monthly', '0.9'],
        ];
        foreach ($staticRoutes as $path => $meta) {
            $append($site_url . '/' . $path, $today, $meta[0], $meta[1]);
        }
    }

    // 3 — LLM uçları: sitemap'ten kaldırıldı.
    // llms.txt / llms-corpus.txt / llms-full*.txt artık X-Robots-Tag: noindex ile yayınlanıyor (handler).
    // Google bunları "Tarandı - dizine eklenmedi" raporuna sokuyordu; LLM tüketicileri için
    // sitemap'te değil, robots.txt'te belirtilen yola erişiyorlar (zaten Allow: /llms*.txt).

    // 4 — Hizmetler
    if ($opts['include_services'] && $tableExists($conn, 'services')) {
        $hasU = $hasCol($conn, 'services', 'updated_at');
        $sql = 'SELECT ana_baslik, slug, created_at, foto' . ($hasU ? ', updated_at' : '') . ' FROM services WHERE status = 1 ORDER BY id ASC';
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $slug = !empty($row['slug']) ? (string) $row['slug'] : $mkSlug((string) $row['ana_baslik']);
                if (sitemap_build_is_redirect_source_slug($slug)) {
                    continue;
                }
                $lm = $today;
                if ($hasU && !empty($row['updated_at'])) {
                    $lm = date('Y-m-d', strtotime((string) $row['updated_at']));
                } elseif (!empty($row['created_at'])) {
                    $lm = date('Y-m-d', strtotime((string) $row['created_at']));
                }
                $loc = $site_url . '/' . rawurlencode($slug);
                $img = null;
                if (!empty($row['foto'])) {
                    $img = '    <image:image>' . "\n"
                        . '      <image:loc>' . $esc($site_url . '/uploads/services/' . $row['foto']) . '</image:loc>' . "\n"
                        . '      <image:title>' . $esc((string) $row['ana_baslik']) . '</image:title>' . "\n"
                        . '    </image:image>';
                }
                $append($loc, $lm, 'weekly', '0.85', $img);
            }
        }
    }

    // 5 — Blog yazıları (kanonik kök slug — .htaccess yönlendirmeleriyle uyumlu)
    if ($opts['include_blog'] && $tableExists($conn, 'blog_posts')) {
        // GSC "Tarandı - dizine eklenmedi" azaltma: ince içerikli (kelime sayısı < eşik) yazıları sitemap'tan çıkar.
        // Eşik 220 kelime: Google'ın tipik thin-content sınırının altı; saha bilgisi paragrafı + 2-3 alt bölüm = ~250+ kelime.
        $thinThreshold = 220;
        $sql = 'SELECT baslik, slug, updated_at, created_at, kapak_foto, icerik FROM blog_posts WHERE durum = 3 ORDER BY created_at DESC';
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $slug = !empty($row['slug']) ? (string) $row['slug'] : $mkSlug((string) $row['baslik']);
                if (sitemap_build_is_redirect_source_slug($slug)) {
                    continue;
                }
                $plain = trim(strip_tags((string) ($row['icerik'] ?? '')));
                $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;
                $wordCount = $plain === '' ? 0 : count(preg_split('/\s+/u', $plain) ?: []);
                if ($wordCount > 0 && $wordCount < $thinThreshold) {
                    continue;
                }
                $rawLm = !empty($row['updated_at']) ? $row['updated_at'] : $row['created_at'];
                $lm = $rawLm ? date('Y-m-d', strtotime((string) $rawLm)) : $today;
                $loc = $site_url . '/' . rawurlencode($slug);
                $img = null;
                if (!empty($row['kapak_foto'])) {
                    $img = '    <image:image>' . "\n"
                        . '      <image:loc>' . $esc($site_url . '/uploads/blog/' . $row['kapak_foto']) . '</image:loc>' . "\n"
                        . '      <image:title>' . $esc((string) $row['baslik']) . '</image:title>' . "\n"
                        . '    </image:image>';
                }
                // Daha uzun içerik = daha yüksek öncelik (Google crawl budget sinyali).
                $prio = $wordCount >= 800 ? '0.8' : ($wordCount >= 400 ? '0.7' : '0.6');
                $append($loc, $lm, 'weekly', $prio, $img);
            }
        }
    }

    // 6 — Blog kategorileri (yalnız en az bir yayımlı yazısı olanlar; boş/500 dönemi gürültüsü azalır)
    if ($opts['include_blog_categories'] && $tableExists($conn, 'blog_categories') && $tableExists($conn, 'blog_posts')) {
        $sql = 'SELECT c.ad, c.slug FROM blog_categories c '
            . 'WHERE EXISTS (SELECT 1 FROM blog_posts p WHERE p.kategori_id = c.id AND p.durum = 3) '
            . 'ORDER BY c.id ASC';
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $catSlug = !empty($row['slug']) ? (string) $row['slug'] : $mkSlug((string) $row['ad']);
                if ($catSlug === '') {
                    continue;
                }
                $append($site_url . '/blog/kategori/' . rawurlencode($catSlug), $today, 'weekly', '0.55');
            }
        }
    }

    // 7 — pages (statik slug’larla çakışanı atla)
    if ($opts['include_pages'] && $tableExists($conn, 'pages')) {
        $hasU = $hasCol($conn, 'pages', 'updated_at');
        $sql = 'SELECT title, slug, created_at' . ($hasU ? ', updated_at' : '') . ' FROM pages WHERE status = 1';
        $res = $conn->query($sql);
        // teklif-al: sayfa kaydı olabilir; kanonik /teklif-alin (.htaccess 301 — Semrush "wrong sitemap")
        $skipSlugs = ['blog', 'iletisim', 'galeri', 'teklif-alin', 'teklif-al'];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $slug = !empty($row['slug']) ? (string) $row['slug'] : $mkSlug((string) $row['title']);
                if ($slug === '' || in_array(strtolower($slug), $skipSlugs, true) || sitemap_build_is_redirect_source_slug($slug)) {
                    continue;
                }
                $lm = $today;
                if ($hasU && !empty($row['updated_at'])) {
                    $lm = date('Y-m-d', strtotime((string) $row['updated_at']));
                } elseif (!empty($row['created_at'])) {
                    $lm = date('Y-m-d', strtotime((string) $row['created_at']));
                }
                $append($site_url . '/' . rawurlencode($slug), $lm, 'monthly', '0.65');
            }
        }
    }

    $xml = $xmlHead . implode("\n", $lines) . "\n</urlset>\n";

    return ['xml' => $xml, 'url_count' => $count, 'seen' => $seen];
}

/**
 * image-sitemap.xml ve video-sitemap.xml varsa indekse eklenir.
 */
function sitemap_build_index_xml(string $site_url, string $projectRoot): string
{
    $site_url = rtrim($site_url, '/');
    $today = date('Y-m-d');
    $esc = static function (string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    };

    $parts = [];
    $parts[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $parts[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $parts[] = '  <sitemap>';
    $parts[] = '    <loc>' . $esc($site_url . '/sitemap.xml') . '</loc>';
    $parts[] = '    <lastmod>' . $esc($today) . '</lastmod>';
    $parts[] = '  </sitemap>';

    $imgPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'image-sitemap.xml';
    if (is_file($imgPath) && filesize($imgPath) > 100) {
        $imLm = date('Y-m-d', (int) filemtime($imgPath));
        $parts[] = '  <sitemap>';
        $parts[] = '    <loc>' . $esc($site_url . '/image-sitemap.xml') . '</loc>';
        $parts[] = '    <lastmod>' . $esc($imLm) . '</lastmod>';
        $parts[] = '  </sitemap>';
    }

    $videoPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'video-sitemap.xml';
    if (is_file($videoPath) && filesize($videoPath) > 100) {
        $vm = date('Y-m-d', (int) filemtime($videoPath));
        $parts[] = '  <sitemap>';
        $parts[] = '    <loc>' . $esc($site_url . '/video-sitemap.xml') . '</loc>';
        $parts[] = '    <lastmod>' . $esc($vm) . '</lastmod>';
        $parts[] = '  </sitemap>';
    }

    $parts[] = '</sitemapindex>';

    return implode("\n", $parts) . "\n";
}
