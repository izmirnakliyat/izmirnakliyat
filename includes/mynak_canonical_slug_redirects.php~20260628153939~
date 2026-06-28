<?php
declare(strict_types=1);

/**
 * İç rekabet (cannibalization) — duplicate fiyat/hizmet slug → kanonik hedef 301.
 *
 * @return array<string, string> eski slug (lowercase) => kanonik slug
 */
function mynak_seo_cannibalization_redirect_map(): array
{
    return [
        'izmir-evden-eve-nakliyat-fiyatlari-2025' => 'izmir-evden-eve-nakliyat-fiyatlari-2026',
        'izmir-evden-eve-nakliye-fiyatlari-2025' => 'izmir-evden-eve-nakliyat-fiyatlari-2026',
        'izmir-evden-eve-tasimacilik-fiyatlari' => 'izmir-evden-eve-nakliyat-fiyatlari-2026',
        'izmir-ev-tasima-fiyatlari' => 'izmir-evden-eve-nakliyat-fiyatlari-2026',
        'sehirler-arasi-nakliyat-fiyatlari-2026' => '2026-sehirler-arasi-nakliyat-fiyatlari-guncel-rehber',
        'izmir-evden-eve-nakliyat-hizmeti' => 'izmir-evden-eve-nakliyat',
        'izmir-ev-tasima-firmalari' => 'izmir-evden-eve-nakliyat',
        'izmir-evden-eve-nakliyat-platformu' => 'izmir-evden-eve-nakliyat',
        'en-iyi-izmir-evden-eve-nakliyat-firmalari' => 'izmir-evden-eve-nakliyat-yorumlari',
        'my-nakliyat-evden-eve-nakliyat' => 'izmir-evden-eve-nakliyat',
        'izmir-nakliyat-firmalari' => 'izmir-evden-eve-nakliyat',
        // Blog/thin içerik → kanonik hizmet sayfası (iç rekabet / cannibalization)
        'sehir-ici-nakliyat' => 'sehirici-nakliyat',
        'profesyonel-ve-ozenli-sehir-ici-nakliyat' => 'sehirici-nakliyat',
    ];
}

function mynak_fc_try_cannibalization_slug_redirect(string $slug): void
{
    if ($slug === '' || str_contains($slug, '/')) {
        return;
    }

    $map = mynak_seo_cannibalization_redirect_map();
    $slugLower = strtolower($slug);
    if (!isset($map[$slugLower])) {
        return;
    }

    $target = $map[$slugLower];
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
