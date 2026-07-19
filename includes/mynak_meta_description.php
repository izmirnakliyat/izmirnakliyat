<?php
declare(strict_types=1);

/**
 * Meta açıklama uzunluğu: arama sonuçları / OG için ~155–160 karakter üst sınır.
 */
function mynak_meta_description_clamp(string $text, int $max = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max);
    $last = mb_strrpos($cut, ' ');
    if ($last !== false && $last > 24) {
        $cut = mb_substr($cut, 0, $last);
    }
    $cut = rtrim($cut, " \t.,;:");

    return $cut . '…';
}

/**
 * DB’de meta yokken: lede/ilk paragraftan türetmek yerine kısa niyet + hizmet + marka cümlesi.
 * Slug, ileride bölge modeli (cluster) ayrımı için iletilir; şu an yalnızca bağıl tutarlılık.
 *
 * @param string $title Sayfa H1’e yakın başlık
 * @param string $slug  mb_strtolower slug
 */
function mynak_default_meta_description_for_page(string $title, string $slug = ''): string
{
    $title = trim($title);
    if ($title === '') {
        $title = 'Hizmet';
    }
    $t = $title;
    if (mb_strlen($t) > 52) {
        $t = mb_substr($t, 0, 49) . '…';
    }
    $slug = trim($slug);

    // Slug-tabanlı ayrıştırma: farklı slug segmentleri benzersiz meta üretir (duplicate önleme)
    if ($slug !== '' && str_contains($slug, 'tavsiy')) {
        $base = $t . '. Taşınma öncesi kontrol listesi, erişim ve planlama. MY Nakliyat İzmir.';
    } elseif ($slug !== '' && str_contains($slug, 'rehber')) {
        $base = $t . '. Nakliyat rehberi; planlama ve güvenli taşıma ipuçları. MY Nakliyat İzmir.';
    } elseif ($slug !== '' && str_contains($slug, 'fiyat')) {
        $base = $t . '. Güncel nakliyat fiyatları, oda sayısına göre ücret karşılaştırması. MY Nakliyat İzmir.';
    } elseif ($slug !== '' && str_contains($slug, 'depolama')) {
        $base = $t . '. Güvenli eşya depolama hizmeti, sigortalı ve kameralı depolar. MY Nakliyat İzmir.';
    } elseif ($slug !== '' && str_contains($slug, 'asansor')) {
        $base = $t . '. Asansörlü taşıma hizmeti, yüksek katlara güvenli eşya transferi. MY Nakliyat İzmir.';
    } else {
        $slugSegment = '';
        if ($slug !== '') {
            $parts = explode('-', $slug);
            $slugSegment = implode(' ', array_slice($parts, 0, 3));
        }
        $tail = $slugSegment !== ''
            ? '. ' . mb_convert_case($slugSegment, MB_CASE_TITLE, 'UTF-8') . ' — ücretsiz keşif, rota ve zaman planı. MY Nakliyat İzmir.'
            : '. Ücretsiz keşif, rota ve zaman planı. Evden eve, ofis, eşya depolama, asansörlü taşımacılık. MY Nakliyat İzmir.';
        $base = $t . $tail;
    }

    return mynak_meta_description_clamp($base, 160);
}
