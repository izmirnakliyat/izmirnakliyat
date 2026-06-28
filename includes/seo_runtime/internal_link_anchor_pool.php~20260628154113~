<?php
declare(strict_types=1);
/**
 * Internal link anchor havuzu — admin tarafından elle yazılmış anchor metin
 * havuzundan, hedef slug + kaynak slug çiftine göre DETERMİNİSTİK rotasyon.
 * Havuz boşsa null döner; çağıran mevcut nav_title/context fallback'ine düşer.
 *
 * Kullanım: seo_rt_anchor_pool_pick($targetSlug, $sourceSlug).
 */

if (defined('MYNAK_SEO_ANCHOR_POOL_LOADED')) {
    return;
}
define('MYNAK_SEO_ANCHOR_POOL_LOADED', true);

/**
 * Havuzun tamamını tek sorguyla önbelleğe alır (request ömrü).
 *
 * @return array<string, list<array{anchor:string,weight:int}>>
 */
function seo_rt_anchor_pool_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];

    global $conn;
    if (!($conn instanceof mysqli)) {
        return $cache;
    }

    $res = @$conn->query("SELECT target_slug, anchor_text, weight FROM internal_link_anchors WHERE active = 1");
    if (!$res) {
        return $cache;
    }

    while ($row = $res->fetch_assoc()) {
        $slug = trim((string) ($row['target_slug'] ?? ''), '/');
        $anchor = trim((string) ($row['anchor_text'] ?? ''));
        if ($slug === '' || $anchor === '') {
            continue;
        }
        $w = (int) ($row['weight'] ?? 1);
        if ($w < 1) {
            $w = 1;
        }
        if (!isset($cache[$slug])) {
            $cache[$slug] = [];
        }
        $cache[$slug][] = ['anchor' => $anchor, 'weight' => $w];
    }

    return $cache;
}

/**
 * Hedef slug için havuzdan anchor seç. Rotasyon deterministik: kaynak slug
 * farklı olduğunda farklı anchor çıkma olasılığı artar (aynı kaynak-hedef
 * çiftinde stabil kalır → URL/cache üzerinde tutarlı).
 */
function seo_rt_anchor_pool_pick(string $targetSlug, string $sourceSlug = ''): ?string
{
    $targetSlug = trim($targetSlug, '/');
    if ($targetSlug === '') {
        return null;
    }
    $pool = seo_rt_anchor_pool_all();
    if (!isset($pool[$targetSlug]) || $pool[$targetSlug] === []) {
        return null;
    }

    $items = $pool[$targetSlug];
    $total = 0;
    foreach ($items as $it) {
        $total += (int) $it['weight'];
    }
    if ($total < 1) {
        return null;
    }

    $seed = $sourceSlug !== '' ? abs(crc32($sourceSlug . '|' . $targetSlug)) : abs(crc32($targetSlug));
    $pick = $seed % $total;
    $acc = 0;
    foreach ($items as $it) {
        $acc += (int) $it['weight'];
        if ($pick < $acc) {
            return (string) $it['anchor'];
        }
    }

    return (string) $items[0]['anchor'];
}

/**
 * İstatistik — admin ekran bilgisi için (hedef başına kaç anchor).
 *
 * @return array<string,int>
 */
function seo_rt_anchor_pool_counts(): array
{
    $pool = seo_rt_anchor_pool_all();
    $out = [];
    foreach ($pool as $slug => $items) {
        $out[$slug] = count($items);
    }
    return $out;
}
