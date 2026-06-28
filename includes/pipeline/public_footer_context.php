<?php
declare(strict_types=1);

require_once __DIR__ . '/../functions.php';

/**
 * Footer şablonu verisi — DB burada; footer.php yalnızca sunum.
 *
 * @param array<string, string> $site_settings Boşsa settings tablosundan okunur.
 * @return array{
 *   footer_google_maps_url: string,
 *   footer_categories: list<array<string, mixed>>,
 *   footer_quick_links: list<array{label: string, href: string}>,
 *   footer_category_title_fix: array<string, string>,
 *   seo_hub_pages: list<array<string, mixed>>,
 *   footer_map: string
 * }
 */
function mynak_public_footer_context(mysqli $conn, array $site_settings = []): array
{
    if ($site_settings === []) {
        $settings_result = $conn->query('SELECT name, value FROM settings');
        if ($settings_result && $settings_result->num_rows > 0) {
            while ($row = $settings_result->fetch_assoc()) {
                $site_settings[$row['name']] = $row['value'];
            }
        }
    }

    $footer_google_maps_url = trim((string) ($site_settings['google_maps_url'] ?? ''));
    if ($footer_google_maps_url === '') {
        $footer_google_maps_url = defined('MYNAK_CONTACT_GOOGLE_MAPS_URL')
            ? MYNAK_CONTACT_GOOGLE_MAPS_URL
            : 'https://maps.app.goo.gl/KhjpeauhbhoXaupZ8';
    }

    $footer_categories = [];
    $cat_result = $conn->query('SELECT * FROM footer_menu_categories WHERE status = 1 ORDER BY position ASC, id ASC LIMIT 2');
    if ($cat_result && $cat_result->num_rows > 0) {
        while ($category = $cat_result->fetch_assoc()) {
            $category_id = (int) $category['id'];
            $category['menu_items'] = [];
            $items_stmt = $conn->prepare('SELECT * FROM footer_menu_items WHERE category_id = ? AND status = 1 ORDER BY order_number ASC, id ASC');
            if ($items_stmt) {
                $items_stmt->bind_param('i', $category_id);
                $items_stmt->execute();
                $items = mysqli_stmt_fetch_all_assoc($items_stmt);
                $items_stmt->close();
                foreach ($items as $item) {
                    $category['menu_items'][] = $item;
                }
            }
            $footer_categories[] = $category;
        }
    }

    $footer_paths_used = [];
    foreach ($footer_categories as $fc) {
        foreach ($fc['menu_items'] ?? [] as $mi) {
            $k = footer_menu_link_path_key($mi['url'] ?? '');
            if ($k !== '') {
                $footer_paths_used[$k] = true;
            }
        }
    }

    $footer_category_title_fix = [
        'Bizim' => 'Kurumsal',
        'Biziz' => 'Hakkımızda',
    ];

    $footer_quick_candidates = [
        ['Ana Sayfa', normalize_internal_link_url('/')],
        ['Blog', normalize_internal_link_url('/blog')],
        ['Teklif Alın', normalize_internal_link_url('/teklif-alin')],
        ['Taşınma listesi (ücretsiz)', normalize_internal_link_url('/tasinma-kontrol-listesi')],
    ];
    $footer_quick_links = [];
    foreach ($footer_quick_candidates as $pair) {
        $k = footer_menu_link_path_key($pair[1]);
        if ($k !== '' && !empty($footer_paths_used[$k])) {
            continue;
        }
        $footer_quick_links[] = ['label' => $pair[0], 'href' => $pair[1]];
    }
    if ($footer_quick_links === []) {
        $footer_quick_links = [
            ['label' => 'Ana Sayfa', 'href' => normalize_internal_link_url('/')],
            ['label' => 'Teklif Alın', 'href' => normalize_internal_link_url('/teklif-alin')],
        ];
    }

    $seo_hub_pages = [];
    $hub_q = @$conn->query("SELECT title, slug FROM pages WHERE status = 1 AND slug IS NOT NULL AND slug != '' AND slug NOT IN ('blog') ORDER BY (CASE WHEN slug LIKE '%-nakliyat' THEN 0 ELSE 1 END), id DESC LIMIT 12");
    if ($hub_q) {
        while ($row = $hub_q->fetch_assoc()) {
            $seo_hub_pages[] = $row;
        }
    }

    $footer_map = '';
    $map_result = $conn->query("SELECT value FROM settings WHERE name = 'contact_map_embed' LIMIT 1");
    if ($map_result && $map_row = $map_result->fetch_assoc()) {
        $footer_map = trim((string) $map_row['value']);
    }
    if ($footer_map === '' && function_exists('mynak_contact_default_map_embed_html')) {
        $footer_map = mynak_contact_default_map_embed_html();
    }

    return [
        'footer_google_maps_url' => $footer_google_maps_url,
        'footer_categories' => $footer_categories,
        'footer_quick_links' => $footer_quick_links,
        'footer_category_title_fix' => $footer_category_title_fix,
        'seo_hub_pages' => $seo_hub_pages,
        'footer_map' => $footer_map,
    ];
}
