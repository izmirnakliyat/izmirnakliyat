<?php
declare(strict_types=1);

/**
 * GSC 404 audit (2026-05-29) — onaylanmış tek segment slug 301 eşlemeleri.
 * Eski WordPress / slug değişikliği URL'leri → güncel yayında blog slug.
 */

/**
 * @return array<string, string> eski slug => yeni slug
 */
function mynak_gsc_404_slug_redirect_map(): array
{
    return [
        'izmir-parca-esya-tasima-hizmetleri' => 'izmir-parca-esya-tasima-hizmeti',
        'izmir-nakliyat-sektorunde-2025-trendleri-ve-akilli-tasima-donemi' => 'izmir-nakliyat-sektorunde-2025-trendleri-akilli-tasima-donemi-basliyor',
        'mobilya-ve-beyaz-esya-tasimaciligi-rehberi' => 'mobilya-beyaz-esya-tasimaciligi-rehberi',
        'evden-eve-nakliyat-maliyetleri-ve-fiyat-rehberi-2026' => 'evden-eve-nakliyat-maliyetleri-fiyat-rehberi',
        'evdeki-birkac-esyayi-tasitmak-isteyenlere-ozel-parca-esya-tasima-rehberi' => 'evdeki-birkac-esyayi-tasitmak-isteyenlere-ozel-parca-esya-tasima',
        'izmir-ev-tasima-' => 'izmir-ev-tasima',
        'sigortali-ev-tasima-hizmeti-veren-en-iyi-firmalar-2026' => 'sigortali-ev-tasima-hizmeti-veren-en-iyi-firmalar',
        'izmir-kurumsal-tasimacilik-hizmetlerinde-yenilikci-teknolojiler-ve-trendler' => 'kurumsal-tasimacilik-hizmetlerinde-yeni-teknolojiler-ve-trendler',
        'izmir-nakliye-' => 'izmir-nakliye',
        'bornova-evden-eve-nakliye-i-cin-sorunsuz-hizmet' => 'bornova-evden-eve-nakliye-icin-sorunsuz-hizmet',
        'i-zmir-tasimacilik' => 'izmir-tasimacilik',
        'i-zmir-evden-eve-nakliyat-i-cin-dogru-hizmetleri-kesfedin' => 'izmir-evden-eve-nakliyat-icin-dogru-hizmetleri-kesfedin',
        'izmir-evden-eve-nakliye-firmalari' => 'izmir-evden-eve-nakliyat-firmalari',
        'izmir-evden-eve-tasima-icin-en-iyi-zamanlar' => 'izmir-evden-eve-tasima-icin-en-iyi-zamanlar-nelerdir',
        'uzman-nakliyat-ekibi-ile-sorunsuz-tasinma-i-cin-i-puclari' => 'uzman-nakliyat-ekibi-ile-sorunsuz-tasinma-icin-ipuclari',
        'i-zmir-evden-eve-tasima-surecinde-bilmeniz-gerekenler' => 'izmir-evden-eve-tasima-surecinde-bilmeniz-gerekenler',
    ];
}

/**
 * Eşleşme varsa 301 gönderir ve çıkar.
 */
function mynak_fc_try_gsc_404_slug_redirect(mysqli $conn, string $slug): void
{
    if ($slug === '' || str_contains($slug, '/')) {
        return;
    }

    $map = mynak_gsc_404_slug_redirect_map();
    if (!isset($map[$slug])) {
        return;
    }

    $target = $map[$slug];
    $stmt = $conn->prepare('SELECT slug FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('s', $target);
    $stmt->execute();
    $rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();
    if (empty($rows[0]['slug'])) {
        return;
    }

    $loc = mynak_abs_url_from_public_path(mynak_public_path($target));
    if (function_exists('seo_runtime_trace_record_redirect')) {
        seo_runtime_trace_record_redirect(
            (function_exists('mynak_request_scheme_for_trace') ? mynak_request_scheme_for_trace() : 'https')
                . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
            $loc,
            301
        );
    }
    header('Location: ' . $loc, true, 301);
    exit;
}
