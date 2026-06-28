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
    $tail = ' Ücretsiz keşif, rota ve zaman planı. Evden eve, ofis, eşya depolama, asansörlü taşımacılık. MY Nakliyat İzmir.';

    $base = $t . '.' . $tail;
    if ($slug !== '' && str_contains($slug, 'tavsiy')) {
        $base = $t . '. Taşınma öncesi kontrol listesi, erişim ve planlama. MY Nakliyat İzmir.';
    } elseif ($slug !== '' && str_contains($slug, 'rehber')) {
        $base = $t . '. Nakliyat rehberi; planlama ve güvenli taşıma ipuçları. MY Nakliyat İzmir.';
    }

    return mynak_meta_description_clamp($base, 160);
}
