<?php
/**
 * Dinamik BreadcrumbList JSON-LD (Schema.org, Google Rich Results uyumlu).
 *
 * Kurallar:
 * - Yalnızca JSON-LD; ana sayfada çıktı yok.
 * - Mutlak URL; son öğe her zaman sayfanın kanonik URL’si ($canonical).
 * - Sorgu dizgileri yola dahil edilmez (header’daki $relPath zaten temiz).
 *
 * İsteğe bağlı istemci tarafı eşdeğeri (tek sayfa uygulamaları için örnek):
 *
 * function buildBreadcrumbJsonLd(origin, pathname) {
 *   const path = (pathname || '/').split('?')[0].replace(/\/+$/, '') || '/';
 *   if (path === '/' || path === '') return null;
 *   const segments = path.split('/').filter(Boolean);
 *   const items = [{ '@type': 'ListItem', position: 1, name: 'Ana Sayfa', item: origin + '/' }];
 *   let acc = '';
 *   segments.forEach((seg, i) => {
 *     acc += '/' + seg;
 *     const name = seg.replace(/[-_]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
 *     items.push({ '@type': 'ListItem', position: items.length + 1, name, item: origin + acc });
 *   });
 *   return { '@context': 'https://schema.org', '@type': 'BreadcrumbList', itemListElement: items };
 * }
 */

declare(strict_types=1);

/**
 * Mutlak URL: path segmentlerini RFC 3986 uyumlu kodla (Türkçe slug güvenliği).
 *
 * @param list<string> $pathSegments
 */
function mynak_breadcrumb_absolute_from_segments(string $canonical_origin, array $pathSegments): string
{
    $origin = rtrim($canonical_origin, '/');
    if ($pathSegments === []) {
        return $origin . '/';
    }
    $enc = [];
    foreach ($pathSegments as $seg) {
        if ($seg === '') {
            continue;
        }
        $enc[] = rawurlencode((string) $seg);
    }

    return $origin . '/' . implode('/', $enc);
}

/**
 * Sık kullanılan Türkçe yer / terim düzeltmeleri (slug parçaları küçük harf eşleşir).
 *
 * @var array<string, string>
 */
function mynak_breadcrumb_tr_word_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = [
        // İl adları
        'izmir' => 'İzmir', 'istanbul' => 'İstanbul', 'ankara' => 'Ankara', 'bursa' => 'Bursa',
        'antalya' => 'Antalya', 'adana' => 'Adana', 'gaziantep' => 'Gaziantep', 'konya' => 'Konya',
        'mersin' => 'Mersin', 'diyarbakir' => 'Diyarbakır', 'kayseri' => 'Kayseri', 'eskisehir' => 'Eskişehir',
        'samsun' => 'Samsun', 'denizli' => 'Denizli', 'sanliurfa' => 'Şanlıurfa', 'adapazari' => 'Adapazarı',
        'malatya' => 'Malatya', 'kahramanmaras' => 'Kahramanmaraş', 'erzurum' => 'Erzurum', 'van' => 'Van',
        'batman' => 'Batman', 'elazig' => 'Elazığ', 'trabzon' => 'Trabzon', 'balikesir' => 'Balıkesir',
        'tekirdag' => 'Tekirdağ', 'manisa' => 'Manisa', 'aydin' => 'Aydın', 'mugla' => 'Muğla',
        'canakkale' => 'Çanakkale', 'kirklareli' => 'Kırklareli', 'edirne' => 'Edirne', 'bodrum' => 'Bodrum',
        'kusadasi' => 'Kuşadası', 'cesme' => 'Çeşme',
        // İzmir ilçeleri
        'bornova' => 'Bornova', 'karsiyaka' => 'Karşıyaka', 'bayrakli' => 'Bayraklı', 'buca' => 'Buca',
        'konak' => 'Konak', 'gaziemir' => 'Gaziemir', 'balcova' => 'Balçova', 'narlidere' => 'Narlıdere',
        'guzelbahce' => 'Güzelbahçe', 'karabaglar' => 'Karabağlar', 'cigli' => 'Çiğli', 'menemen' => 'Menemen',
        'aliaga' => 'Aliağa', 'bergama' => 'Bergama', 'dikili' => 'Dikili', 'foca' => 'Foça',
        'kemalpasa' => 'Kemalpaşa', 'menderes' => 'Menderes', 'seferihisar' => 'Seferihisar',
        'selcuk' => 'Selçuk', 'torbali' => 'Torbalı', 'urla' => 'Urla', 'kinik' => 'Kınık',
        'kiraz' => 'Kiraz', 'odemis' => 'Ödemiş', 'tire' => 'Tire', 'bayindir' => 'Bayındır',
        'beydag' => 'Beydağ', 'karaburun' => 'Karaburun',
        // Sektör + hizmet kelimeleri (en kritik — mevcut URL'lerde geçiyor)
        'nakliyat' => 'Nakliyat', 'nakliye' => 'Nakliye', 'tasima' => 'Taşıma', 'tasimacilik' => 'Taşımacılık',
        'evden' => 'Evden', 'eve' => 'Eve', 'ev' => 'Ev', 'parca' => 'Parça', 'esya' => 'Eşya',
        'depolama' => 'Depolama', 'asansor' => 'Asansör', 'asansorlu' => 'Asansörlü',
        'sehir' => 'Şehir', 'sehirler' => 'Şehirler', 'sehirici' => 'Şehir İçi', 'ici' => 'İçi',
        'sehirlerarasi' => 'Şehirlerarası', 'arasi' => 'Arası', 'mobil' => 'Mobil',
        'ofis' => 'Ofis', 'ofisi' => 'Ofisi', 'kurumsal' => 'Kurumsal',
        'hizmet' => 'Hizmet', 'hizmeti' => 'Hizmeti', 'hizmetleri' => 'Hizmetleri',
        'mobilya' => 'Mobilya', 'montaj' => 'Montaj', 'kurulum' => 'Kurulum',
        'sepetli' => 'Sepetli', 'vinc' => 'Vinç', 'vinci' => 'Vinci', 'kiralama' => 'Kiralama',
        'antika' => 'Antika', 'piyano' => 'Piyano', 've' => 've',
        'ceyiz' => 'Çeyiz', 'ceyizi' => 'Çeyizi', 'galeri' => 'Galeri', 'video' => 'Video',
        'belgelerimiz' => 'Belgelerimiz', 'hakkimizda' => 'Hakkımızda', 'iletisim' => 'İletişim',
        'teklif' => 'Teklif', 'alin' => 'Alın', 'al' => 'Al', 'tavsiyeleri' => 'Tavsiyeleri',
        'basinda' => 'Basında', 'biz' => 'Biz', 'ekibimiz' => 'Ekibimiz',
        'tibbi' => 'Tıbbi', 'cihaz' => 'Cihaz',
        // Yardımcı kelimeler
        'fiyat' => 'Fiyat', 'fiyatlari' => 'Fiyatları', 'ucret' => 'Ücret', 'ucreti' => 'Ücreti',
        'ne' => 'Ne', 'kadar' => 'Kadar', 'en' => 'En', 'iyi' => 'İyi',
    ];

    return $map;
}

/**
 * URL parçasından okunabilir Türkçe başlık (slug → başlık).
 */
function mynak_breadcrumb_slug_to_title(string $slug): string
{
    $slug = rawurldecode($slug);
    $slug = str_replace(['-', '_'], ' ', $slug);
    $slug = trim(preg_replace('/\s+/u', ' ', $slug));
    if ($slug === '') {
        return '';
    }

    $words = preg_split('/\s+/u', $slug, -1, PREG_SPLIT_NO_EMPTY);
    if ($words === false) {
        return mb_convert_case($slug, MB_CASE_TITLE, 'UTF-8');
    }
    $map = mynak_breadcrumb_tr_word_map();
    foreach ($words as &$w) {
        $key = mb_strtolower($w, 'UTF-8');
        if (isset($map[$key])) {
            $w = $map[$key];
            continue;
        }
        $w = mb_convert_case($w, MB_CASE_TITLE, 'UTF-8');
    }
    unset($w);

    return implode(' ', $words);
}

/**
 * @return list<array{name:string,url:string}>
 */
function mynak_breadcrumb_dedupe_adjacent(array $items): array
{
    $out = [];
    foreach ($items as $it) {
        $prev = $out[count($out) - 1] ?? null;
        if ($prev !== null && $prev['name'] === $it['name'] && $prev['url'] === $it['url']) {
            continue;
        }
        $out[] = $it;
    }

    return $out;
}

function mynak_breadcrumb_is_home_path(string $relPath): bool
{
    $p = trim($relPath, '/');
    return $p === '' || $p === 'index' || $p === 'index.php';
}

function mynak_breadcrumb_is_excluded_path(string $relPath): bool
{
    $p = $relPath;
    if ($p === '' || $p === '/') {
        return false;
    }
    if (strncmp($p, '/admin', 6) === 0) {
        return true;
    }

    return false;
}

/**
 * @return list<string>
 */
function mynak_breadcrumb_path_segments(string $relPath): array
{
    $p = trim($relPath, '/');
    if ($p === '') {
        return [];
    }

    return array_values(array_filter(explode('/', $p), static function ($s) {
        return $s !== '';
    }));
}

/**
 * Son liste öğesinin adını (blog yazısı / sayfa) çöz.
 */
function mynak_breadcrumb_resolve_entity_title(string $fallback): string
{
    global $blog, $page, $page_title, $title_suffix;

    if (isset($blog) && is_array($blog) && !empty($blog['baslik'])) {
        return (string) $blog['baslik'];
    }
    if (isset($page) && is_array($page) && !empty($page['title'])) {
        return (string) $page['title'];
    }
    if (!empty($page_title)) {
        $t = (string) $page_title;
        if (!empty($title_suffix) && $title_suffix !== '') {
            $sl = mb_strlen($title_suffix, 'UTF-8');
            if ($sl > 0 && mb_substr($t, -$sl, null, 'UTF-8') === $title_suffix) {
                $t = mb_substr($t, 0, -$sl, 'UTF-8');
            }
        }

        return trim($t) !== '' ? trim($t) : $fallback;
    }

    return $fallback;
}

/**
 * Blog yazısı: Ana Sayfa > Blog > [Kategori] > Başlık
 *
 * @return list<array{name:string,url:string}>
 */
function mynak_breadcrumb_build_blog_post(string $canonical_origin, string $canonical_full): array
{
    global $blog;

    if (!isset($blog) || !is_array($blog) || empty($blog['slug'])) {
        return [];
    }

    $items = [];
    $items[] = ['name' => 'Ana Sayfa', 'url' => $canonical_origin . '/'];
    $items[] = ['name' => 'Blog', 'url' => rtrim($canonical_origin, '/') . '/blog/'];

    if (!empty($blog['kategori_slug']) && !empty($blog['kategori_adi'])) {
        $items[] = [
            'name' => (string) $blog['kategori_adi'],
            'url' => rtrim($canonical_origin, '/') . '/blog/kategori/' . rawurlencode((string) $blog['kategori_slug']) . '/',
        ];
    }

    $items[] = [
        'name' => mynak_breadcrumb_resolve_entity_title(mynak_breadcrumb_slug_to_title((string) $blog['slug'])),
        'url' => $canonical_full,
    ];

    return mynak_breadcrumb_dedupe_adjacent($items);
}

/**
 * /blog/... listeleme ve filtre sayfaları.
 *
 * @param list<string> $segs
 * @return list<array{name:string,url:string}>
 */
function mynak_breadcrumb_build_blog_branch(array $segs, string $canonical_origin, string $canonical_full): array
{
    global $current_category, $current_tag;

    $items = [];
    $items[] = ['name' => 'Ana Sayfa', 'url' => $canonical_origin . '/'];
    $items[] = ['name' => 'Blog', 'url' => rtrim($canonical_origin, '/') . '/blog/'];

    $i = 1;
    $n = count($segs);
    while ($i < $n) {
        if ($segs[$i] === 'sayfa' && isset($segs[$i + 1]) && ctype_digit((string) $segs[$i + 1])) {
            $pn = (int) $segs[$i + 1];
            $slice = array_slice($segs, 0, $i + 2);
            $prefix = array_slice($segs, 0, $i);
            if ($prefix === ['blog']) {
                $pageLabel = 'Blog — Sayfa ' . $pn;
            } elseif (
                count($prefix) >= 3
                && ($prefix[0] ?? '') === 'blog'
                && ($prefix[1] ?? '') === 'kategori'
                && ($prefix[2] ?? '') !== ''
                && !empty($current_category)
                && !empty($current_category['slug'])
                && (string) $current_category['slug'] === (string) $prefix[2]
                && !empty($current_category['ad'])
            ) {
                $pageLabel = (string) $current_category['ad'] . ' Yazıları — Sayfa ' . $pn;
            } elseif (
                count($prefix) >= 3
                && ($prefix[0] ?? '') === 'blog'
                && ($prefix[1] ?? '') === 'etiket'
                && ($prefix[2] ?? '') !== ''
                && !empty($current_tag)
            ) {
                $pageLabel = '"' . (string) $current_tag . '" Etiketli Yazılar — Sayfa ' . $pn;
            } else {
                $pageLabel = 'Sayfa ' . $pn;
            }
            $items[] = ['name' => $pageLabel, 'url' => mynak_breadcrumb_absolute_from_segments($canonical_origin, $slice)];
            $i += 2;
            continue;
        }
        if ($segs[$i] === 'kategori' && isset($segs[$i + 1])) {
            $slug = (string) $segs[$i + 1];
            $baseName = (!empty($current_category['slug']) && (string) $current_category['slug'] === $slug && !empty($current_category['ad']))
                ? (string) $current_category['ad']
                : mynak_breadcrumb_slug_to_title($slug);
            $name = $baseName . ' Yazıları';
            $slice = array_slice($segs, 0, $i + 2);
            $items[] = ['name' => $name, 'url' => mynak_breadcrumb_absolute_from_segments($canonical_origin, $slice)];
            $i += 2;
            continue;
        }
        if ($segs[$i] === 'etiket' && isset($segs[$i + 1])) {
            $slug = (string) $segs[$i + 1];
            $tagLabel = !empty($current_tag) ? (string) $current_tag : mynak_breadcrumb_slug_to_title($slug);
            $name = '"' . $tagLabel . '" Etiketli Yazılar';
            $slice = array_slice($segs, 0, $i + 2);
            $items[] = ['name' => $name, 'url' => mynak_breadcrumb_absolute_from_segments($canonical_origin, $slice)];
            $i += 2;
            continue;
        }

        $slice = array_slice($segs, 0, $i + 1);
        $items[] = ['name' => mynak_breadcrumb_slug_to_title((string) $segs[$i]), 'url' => mynak_breadcrumb_absolute_from_segments($canonical_origin, $slice)];
        $i++;
    }

    if (count($items) >= 2) {
        $last = count($items) - 1;
        $items[$last]['url'] = $canonical_full;
    }

    return mynak_breadcrumb_dedupe_adjacent($items);
}

/**
 * Çok parçalı yol: her segment için kümülatif URL.
 *
 * @param list<string> $segs
 * @return list<array{name:string,url:string}>
 */
function mynak_breadcrumb_build_generic(array $segs, string $canonical_origin, string $canonical_full): array
{
    $items = [];
    $items[] = ['name' => 'Ana Sayfa', 'url' => $canonical_origin . '/'];

    $acc = [];
    $last = count($segs) - 1;
    foreach ($segs as $idx => $seg) {
        $acc[] = $seg;
        $is_last = ($idx === $last);
        $title = mynak_breadcrumb_slug_to_title((string) $seg);
        if ($is_last) {
            $title = mynak_breadcrumb_resolve_entity_title($title);
        }
        $items[] = [
            'name' => $title,
            'url' => $is_last ? $canonical_full : mynak_breadcrumb_absolute_from_segments($canonical_origin, $acc),
        ];
    }

    return mynak_breadcrumb_dedupe_adjacent($items);
}

/**
 * @return list<array{name:string,url:string}>
 */
function mynak_breadcrumb_build_items(string $relPath, string $canonical_full, string $canonical_origin): array
{
    global $blog;

    if (mynak_breadcrumb_is_home_path($relPath) || mynak_breadcrumb_is_excluded_path($relPath)) {
        return [];
    }

    if (isset($blog) && is_array($blog) && !empty($blog['slug'])) {
        $segs = mynak_breadcrumb_path_segments($relPath);
        if ($segs === [] || ($segs[0] ?? '') !== 'blog') {
            return mynak_breadcrumb_build_blog_post($canonical_origin, $canonical_full);
        }
    }

    $segs = mynak_breadcrumb_path_segments($relPath);
    if ($segs === []) {
        return [];
    }

    if (($segs[0] ?? '') === 'blog') {
        return mynak_breadcrumb_build_blog_branch($segs, $canonical_origin, $canonical_full);
    }

    return mynak_breadcrumb_build_generic($segs, $canonical_origin, $canonical_full);
}

/**
 * BreadcrumbList kök düğümü (yalnız veri). Üretim &lt;head&gt; çıktısı yalnızca schema_factory().
 *
 * @param list<array{name:string,url:string}> $breadcrumbs
 * @return array<string, mixed>|null
 */
function mynak_breadcrumb_schema_from_items(array $breadcrumbs): ?array
{
    if ($breadcrumbs === [] || count($breadcrumbs) < 2) {
        return null;
    }

    $itemListElement = [];
    $position = 1;

    foreach ($breadcrumbs as $breadcrumb) {
        $url = isset($breadcrumb['url']) ? trim((string) $breadcrumb['url']) : '';
        $parts = $url !== '' ? parse_url($url) : false;
        $urlOk = is_array($parts) && !empty($parts['scheme']) && !empty($parts['host'])
            && preg_match('#^https?$#i', (string) $parts['scheme']);
        if (!$urlOk) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = (string) $parts['host'];
        $path = isset($parts['path']) && $parts['path'] !== '' ? (string) $parts['path'] : '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $normUrl = $scheme . '://' . $host . $path . $query;

        $rawName = isset($breadcrumb['name']) ? (string) $breadcrumb['name'] : '';
        $name = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($rawName), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $name = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $name);
        if ($name === '') {
            $pieces = array_values(array_filter(explode('/', trim($path, '/'))));
            $lastSeg = $pieces !== [] ? (string) $pieces[count($pieces) - 1] : '';
            $name = $lastSeg !== ''
                ? trim(preg_replace('/\s+/u', ' ', str_replace(['-', '_'], ' ', rawurldecode($lastSeg))))
                : 'Sayfa';
        }

        $itemListElement[] = [
            '@type' => 'ListItem',
            'position' => (int) $position,
            'name' => $name,
            'item' => $normUrl,
        ];
        $position++;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $itemListElement,
    ];
}

/**
 * @deprecated NOT USED IN PRODUCTION HEAD. Üretim BreadcrumbList yalnızca schema_factory(full_head).
 * Geriye dönük çağrılar boş döner; head’e bağlamayın.
 */
function mynak_breadcrumb_jsonld_html(string $relPath, string $canonical_full, string $canonical_origin): string
{
    return '';
}
