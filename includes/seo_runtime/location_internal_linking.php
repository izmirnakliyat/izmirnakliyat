<?php
declare(strict_types=1);

/** @return array<string,string> */
function mynak_district_page_labels(): array
{
    return [
        'aliaga-evden-eve-nakliyat' => 'Aliağa',
        'balcova-evden-eve-nakliyat' => 'Balçova',
        'bayındır-evden-eve-nakliyat-hizmetleri' => 'Bayındır',
        'bayrakli-evden-eve-nakliyat' => 'Bayraklı',
        'bergama-evden-eve-nakliyat' => 'Bergama',
        'beydag-evden-eve-nakliyat' => 'Beydağ',
        'bornova-evden-eve-nakliyat' => 'Bornova',
        'buca-evden-eve-nakliyat' => 'Buca',
        'cesme-evden-eve-nakliyat' => 'Çeşme',
        'cigli-evden-eve-nakliyat' => 'Çiğli',
        'dikili-evden-eve-nakliyat' => 'Dikili',
        'foca-evden-eve-nakliyat' => 'Foça',
        'gaziemir-evden-eve-nakliyat' => 'Gaziemir',
        'guzelbahce-evden-eve-nakliyat' => 'Güzelbahçe',
        'karabaglar-evden-eve-nakliyat' => 'Karabağlar',
        'karsiyaka-evden-eve-nakliyat' => 'Karşıyaka',
        'konak-evden-eve-nakliyat' => 'Konak',
        'menderes-evden-eve-nakliyat' => 'Menderes',
        'seferihisar-evden-eve-nakliyat' => 'Seferihisar',
        'torbali-evden-eve-nakliyat' => 'Torbalı',
        'urla-evdeneve-nakliyat' => 'Urla',
    ];
}

/** @return list<list<string>> */
function mynak_district_page_groups(): array
{
    return [
        ['aliaga-evden-eve-nakliyat', 'bergama-evden-eve-nakliyat', 'dikili-evden-eve-nakliyat', 'foca-evden-eve-nakliyat'],
        ['balcova-evden-eve-nakliyat', 'bayrakli-evden-eve-nakliyat', 'bornova-evden-eve-nakliyat', 'buca-evden-eve-nakliyat', 'cigli-evden-eve-nakliyat', 'gaziemir-evden-eve-nakliyat', 'guzelbahce-evden-eve-nakliyat', 'karabaglar-evden-eve-nakliyat', 'karsiyaka-evden-eve-nakliyat', 'konak-evden-eve-nakliyat'],
        ['bayındır-evden-eve-nakliyat-hizmetleri', 'beydag-evden-eve-nakliyat', 'torbali-evden-eve-nakliyat'],
        ['cesme-evden-eve-nakliyat', 'menderes-evden-eve-nakliyat', 'seferihisar-evden-eve-nakliyat', 'urla-evdeneve-nakliyat'],
    ];
}

/** @return array<string,string> */
function mynak_city_pair_page_labels(): array
{
    return [
        'izmir-istanbul' => 'İzmir–İstanbul nakliyat',
        'izmir-ankara' => 'İzmir–Ankara nakliyat',
        'izmir-bursa' => 'İzmir–Bursa nakliyat',
        'izmir-antalya' => 'İzmir–Antalya nakliyat',
        'izmir-mugla' => 'İzmir–Muğla nakliyat',
    ];
}

function mynak_location_link_href(string $slug): string
{
    $path = function_exists('mynak_public_path') ? mynak_public_path($slug) : '/' . ltrim($slug, '/');

    return htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
}

function mynak_district_anchor(string $sourceSlug, string $targetSlug, string $label): string
{
    $anchors = [
        $label . ' evden eve nakliyat hizmeti',
        $label . ' bölgesinde taşınma planı',
        'İzmir ' . $label . ' nakliyat rehberi',
    ];

    return $anchors[abs(crc32($sourceSlug . '|' . $targetSlug)) % count($anchors)];
}

/** @return list<string> */
function mynak_related_district_slugs(string $currentSlug): array
{
    $labels = mynak_district_page_labels();
    if (!isset($labels[$currentSlug])) {
        return [];
    }
    $sameRegion = [];
    foreach (mynak_district_page_groups() as $group) {
        if (in_array($currentSlug, $group, true)) {
            $sameRegion = array_values(array_diff($group, [$currentSlug]));
            break;
        }
    }
    $otherRegions = array_values(array_diff(array_keys($labels), $sameRegion, [$currentSlug]));
    $offset = abs(crc32($currentSlug)) % count($otherRegions);
    $rotated = array_merge(array_slice($otherRegions, $offset), array_slice($otherRegions, 0, $offset));

    return array_slice(array_values(array_unique(array_merge($sameRegion, $rotated))), 0, 6);
}

function mynak_location_internal_links_html(string $currentSlug): string
{
    $districts = mynak_district_page_labels();
    if (isset($districts[$currentSlug])) {
        $html = '<nav class="mynak-location-cluster mt-5 pt-4 border-top" aria-labelledby="mynak-nearby-districts">'
            . '<h2 id="mynak-nearby-districts" class="h4 mb-3">Yakın Bölgelerde Nakliyat Hizmetleri</h2>'
            . '<ul class="row row-cols-1 row-cols-md-2 g-2 list-unstyled mb-0">';
        foreach (mynak_related_district_slugs($currentSlug) as $targetSlug) {
            $html .= '<li class="col"><a href="' . mynak_location_link_href($targetSlug) . '">'
                . htmlspecialchars(mynak_district_anchor($currentSlug, $targetSlug, $districts[$targetSlug]), ENT_QUOTES, 'UTF-8')
                . '</a></li>';
        }
        $html .= '<li class="col"><a href="' . mynak_location_link_href('izmir-evden-eve-nakliyat')
            . '">İzmir geneli evden eve nakliyat hizmeti</a></li>';

        return $html . '</ul></nav>';
    }

    $cityPairs = mynak_city_pair_page_labels();
    if (!isset($cityPairs[$currentSlug])) {
        return '';
    }
    $html = '<nav class="mynak-location-cluster mt-5 pt-4 border-top" aria-labelledby="mynak-city-routes">'
        . '<h2 id="mynak-city-routes" class="h4 mb-3">İzmir Çıkışlı Popüler Taşıma Rotaları</h2>'
        . '<ul class="row row-cols-1 row-cols-md-2 g-2 list-unstyled mb-0">';
    foreach ($cityPairs as $targetSlug => $label) {
        if ($targetSlug === $currentSlug) {
            continue;
        }
        $html .= '<li class="col"><a href="' . mynak_location_link_href($targetSlug) . '">'
            . htmlspecialchars($label . ' hizmet kapsamı', ENT_QUOTES, 'UTF-8')
            . '</a></li>';
    }
    $html .= '<li class="col"><a href="' . mynak_location_link_href('sehirler-arasi-nakliyat')
        . '">81 ile şehirler arası nakliyat</a></li>';

    return $html . '</ul></nav>';
}
