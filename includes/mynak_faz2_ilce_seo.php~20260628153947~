<?php
declare(strict_types=1);

/**
 * FAZ 2 — İlçe cluster SEO (title, meta, schema location_vector).
 * URL/slug/canonical değiştirmez; yalnızca services/pages SEO alanları + JSON-LD areaServed.
 */

/** @return array<string, string> slug_token => display name (Türkçe) */
function mynak_faz2_district_slug_name_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $data = require dirname(__DIR__) . '/includes/llms_izmir_data.php';
    $districts = is_array($data['districts'] ?? null) ? $data['districts'] : [];
    $map = [];
    foreach ($districts as $name) {
        $token = mynak_faz2_district_name_to_slug((string) $name);
        if ($token !== '') {
            $map[$token] = (string) $name;
        }
    }

    return $map;
}

function mynak_faz2_district_name_to_slug(string $name): string
{
    $tr = ['ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç', ' '];
    $en = ['i', 'i', 'g', 'g', 'u', 'u', 's', 's', 'o', 'o', 'c', 'c', '-'];
    $n = str_replace($tr, $en, $name);
    $n = mb_strtolower($n, 'UTF-8');
    $n = preg_replace('/[^a-z0-9-]+/', '', $n) ?? $n;

    return (string) $n;
}

/** @return list<string> */
function mynak_faz2_district_slug_tokens(): array
{
    return array_keys(mynak_faz2_district_slug_name_map());
}

function mynak_faz2_service_kind_from_slug(string $slug): ?string
{
    $slug = mb_strtolower(trim($slug), 'UTF-8');
    if (preg_match('#-evden-eve-nakliyat$#u', $slug) || preg_match('#-evdeneve-nakliyat$#u', $slug)) {
        return 'evden_eve';
    }
    if (preg_match('#-asansor-kiralama$#u', $slug)) {
        return 'asansor';
    }
    if (preg_match('#-sepetli-vinc-kiralama$#u', $slug)) {
        return 'vinc';
    }

    return null;
}

function mynak_faz2_is_ilce_slug(string $slug): bool
{
    $slug = mb_strtolower(trim($slug), 'UTF-8');
    if ($slug === '' || $slug === 'izmir-evden-eve-nakliyat') {
        return false;
    }
    $kind = mynak_faz2_service_kind_from_slug($slug);
    if ($kind === null) {
        return false;
    }
    foreach (mynak_faz2_district_slug_tokens() as $token) {
        if (preg_match('#^' . preg_quote($token, '#') . '-(?:evden-eve-nakliyat|evdeneve-nakliyat|asansor-kiralama|sepetli-vinc-kiralama)$#u', $slug)) {
            return true;
        }
        if ($kind === 'asansor' && preg_match('#^izmir-' . preg_quote($token, '#') . '-asansor-kiralama$#u', $slug)) {
            return true;
        }
    }

    return false;
}

function mynak_faz2_district_name_from_slug(string $slug): ?string
{
    $slug = mb_strtolower(trim($slug), 'UTF-8');
    $map = mynak_faz2_district_slug_name_map();
    foreach (mynak_faz2_district_slug_tokens() as $token) {
        if (preg_match('#^' . preg_quote($token, '#') . '-(?:evden-eve-nakliyat|evdeneve-nakliyat|asansor-kiralama|sepetli-vinc-kiralama)$#u', $slug)) {
            return $map[$token] ?? null;
        }
        if (preg_match('#^izmir-' . preg_quote($token, '#') . '-asansor-kiralama$#u', $slug)) {
            return $map[$token] ?? null;
        }
    }

    return null;
}

function mynak_faz2_ensure_suffix(string $title): string
{
    $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
    if ($title === '') {
        return 'MY Nakliyat';
    }
    if (preg_match('/\|\s*MY\s+Nakliyat\s*$/iu', $title)) {
        return $title;
    }

    return rtrim($title, " \t-|") . ' | MY Nakliyat';
}

function mynak_faz2_clamp_meta(string $text): string
{
    require_once __DIR__ . '/mynak_meta_description.php';

    return mynak_meta_description_clamp($text, 160);
}

/**
 * @return array{seo_title: string, meta_description: string, h1: string, service_kind: string}
 */
function mynak_faz2_snippets_for_slug(string $slug): ?array
{
    $district = mynak_faz2_district_name_from_slug($slug);
    $kind = mynak_faz2_service_kind_from_slug($slug);
    if ($district === null || $kind === null) {
        return null;
    }

    return mynak_faz2_snippets_for_district($district, $kind);
}

/**
 * @return array{seo_title: string, meta_description: string, h1: string, service_kind: string}
 */
function mynak_faz2_snippets_for_district(string $district, string $kind): array
{
    switch ($kind) {
        case 'asansor':
            $h1 = $district . ' Asansör Kiralama';
            $seoTitle = mynak_faz2_ensure_suffix($district . ' Asansör Kiralama | İzmir');
            $meta = mynak_faz2_clamp_meta(
                $district . ' asansör kiralama hizmeti sunuyoruz. Güvenli yüksek kat taşıma ve sigortalı operasyon. Ücretsiz teklif alın.'
            );
            break;
        case 'vinc':
            $h1 = $district . ' Sepetli Vinç Kiralama';
            $seoTitle = mynak_faz2_ensure_suffix($district . ' Sepetli Vinç | İzmir');
            $meta = mynak_faz2_clamp_meta(
                $district . ' sepetli vinç kiralama hizmeti sunuyoruz. Profesyonel ekip ve güvenli taşıma. Hemen fiyat alın.'
            );
            break;
        case 'evden_eve':
        default:
            $h1 = $district . ' Evden Eve Nakliyat';
            $seoTitle = mynak_faz2_ensure_suffix($district . ' Evden Eve Nakliyat | Sigortalı Taşıma');
            if (function_exists('mb_strlen') ? mb_strlen($seoTitle) > 62 : strlen($seoTitle) > 62) {
                $seoTitle = mynak_faz2_ensure_suffix($district . ' Evden Eve Nakliyat');
            }
            $meta = mynak_faz2_clamp_meta(
                $district . ' evden eve nakliyat hizmeti sunuyoruz. Sigortalı, asansörlü taşıma ve ücretsiz ekspertiz. Hemen teklif alın.'
            );
            $kind = 'evden_eve';
            break;
    }

    return [
        'seo_title' => $seoTitle,
        'meta_description' => $meta,
        'h1' => $h1,
        'service_kind' => $kind,
    ];
}

/**
 * Title ile H1 uyumu: title kökü H1 ile aynı ilçe + hizmet türünü içermeli.
 */
function mynak_faz2_h1_title_aligned(string $h1, string $seoTitle): bool
{
    $h1 = mb_strtolower(trim($h1), 'UTF-8');
    $stem = mb_strtolower(trim(preg_replace('/\s*\|.*$/u', '', $seoTitle) ?? $seoTitle), 'UTF-8');
    if ($h1 === '' || $stem === '') {
        return false;
    }
    if ($stem === $h1) {
        return true;
    }
    similar_text($h1, $stem, $pct);

    return $pct >= 55.0;
}

/**
 * @param array<string, mixed> $locationVector
 * @return array<string, mixed>
 */
function mynak_faz2_enrich_location_vector(array $locationVector, string $slug): array
{
    if (!mynak_faz2_is_ilce_slug($slug)) {
        return $locationVector;
    }
    $district = mynak_faz2_district_name_from_slug($slug);
    if ($district === null) {
        return $locationVector;
    }
    $locationVector['area_served_mode'] = 'district_single';
    $locationVector['primary_district'] = $district;
    $locationVector['metro_center'] = 'İzmir';
    $locationVector['country'] = 'TR';

    return $locationVector;
}

/**
 * @return list<array{table: string, id: int, slug: string, title_col: string}>
 */
function mynak_faz2_collect_db_targets(mysqli $conn): array
{
    $out = [];
    $r = $conn->query('SELECT id, slug, seo_title, meta_description, ana_baslik AS title_col_val, ana_baslik FROM services WHERE status = 1');
    while ($row = $r->fetch_assoc()) {
        $slug = (string) ($row['slug'] ?? '');
        if (!mynak_faz2_is_ilce_slug($slug)) {
            continue;
        }
        $out[] = [
            'table' => 'services',
            'id' => (int) $row['id'],
            'slug' => $slug,
            'title_col' => 'ana_baslik',
        ];
    }
    $r = $conn->query('SELECT id, slug, seo_title, meta_description, title AS title_col_val, title FROM pages WHERE status = 1');
    while ($row = $r->fetch_assoc()) {
        $slug = (string) ($row['slug'] ?? '');
        if (!mynak_faz2_is_ilce_slug($slug)) {
            continue;
        }
        $out[] = [
            'table' => 'pages',
            'id' => (int) $row['id'],
            'slug' => $slug,
            'title_col' => 'title',
        ];
    }
    $r = $conn->query('SELECT id, slug, seo_title, meta_description, baslik FROM blog_posts WHERE durum = 3');
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $slug = (string) ($row['slug'] ?? '');
            if (!mynak_faz2_is_ilce_slug($slug)) {
                continue;
            }
            $out[] = [
                'table' => 'blog_posts',
                'id' => (int) $row['id'],
                'slug' => $slug,
                'title_col' => 'baslik',
            ];
        }
    }

    return $out;
}
