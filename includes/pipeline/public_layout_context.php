<?php
declare(strict_types=1);

/**
 * Public layout verisi (header/footer şablonları): DB + SEO head bağlamı.
 * Sunum katmanı bu diziyi extract eder; şablonda sorgu yok.
 */
function mynak_public_layout_track_visitor(mysqli $conn): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }
    // Oturum başladıysa: sayfa başına INSERT yerine oturumda tek yazım (DB yükü ↓)
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['mynak_visitor_tracked'])) {
        return;
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $pageVisited = $_SERVER['REQUEST_URI'] ?? '';
    $visit_time = date('Y-m-d H:i:s');
    // Tablo kurulumu deploy/admin ile yapılır; runtime’da SHOW/CREATE yok
    $stmt = $conn->prepare('INSERT INTO visitors (ip_address, user_agent, referrer, page_visited, visit_time) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('sssss', $ip_address, $user_agent, $referrer, $pageVisited, $visit_time);
    try {
        if ($stmt->execute() && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['mynak_visitor_tracked'] = 1;
        }
    } catch (Throwable $e) {
        error_log('trackVisitor: ' . $e->getMessage());
    }
    $stmt->close();
}

function mynak_public_layout_nav_bundle_cache_file(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'public_layout_settings_menus.json';
}

/**
 * Ayarlar + menü + üst butonlar önbelleği için DB parmak izi (TTL yanında anında bayatlama).
 *
 * @return array{sc:int,sv:int,mc:int,msig:int,hbc:int,hbsig:int}|null
 */
function mynak_public_layout_nav_bundle_fingerprint(mysqli $conn): ?array
{
    $sql = 'SELECT
      (SELECT COUNT(*) FROM settings) AS sc,
      (SELECT IFNULL(SUM(CHAR_LENGTH(COALESCE(settings.value, \'\'))), 0) FROM settings) AS sv,
      (SELECT COUNT(*) FROM menus) AS mc,
      (SELECT IFNULL(SUM(menus.id * 7919 + menus.menu_order * 13 + menus.status * 3
        + CHAR_LENGTH(COALESCE(menus.title, \'\')) + CHAR_LENGTH(COALESCE(menus.url, \'\'))
        + IFNULL(menus.parent_id, 0) * 17), 0) FROM menus) AS msig,
      (SELECT COUNT(*) FROM header_buttons) AS hbc,
      (SELECT IFNULL(SUM(header_buttons.id * 8101 + header_buttons.status * 19
        + CHAR_LENGTH(COALESCE(header_buttons.title, \'\')) + IFNULL(header_buttons.popup_id, 0) * 23
        + IFNULL(header_buttons.form_id, 0) * 29 + CHAR_LENGTH(COALESCE(header_buttons.url, \'\'))), 0) FROM header_buttons) AS hbsig';
    $res = @$conn->query($sql);
    if (!$res) {
        return null;
    }
    $row = $res->fetch_assoc();
    if (!$row) {
        return null;
    }

    return [
        'sc' => (int) ($row['sc'] ?? 0),
        'sv' => (int) ($row['sv'] ?? 0),
        'mc' => (int) ($row['mc'] ?? 0),
        'msig' => (int) ($row['msig'] ?? 0),
        'hbc' => (int) ($row['hbc'] ?? 0),
        'hbsig' => (int) ($row['hbsig'] ?? 0),
    ];
}

function mynak_public_layout_invalidate_nav_cache(): void
{
    $f = mynak_public_layout_nav_bundle_cache_file();
    if (is_file($f)) {
        @unlink($f);
    }
}

/**
 * @param list<int> $ids
 * @return list<array<string, mixed>>
 */
function mynak_popups_fetch_by_ids(mysqli $conn, array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($i) {
        return $i > 0;
    })));
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = 'SELECT id, title, content, status FROM popups WHERE status = 1 AND id IN (' . $placeholders . ')';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if (!function_exists('mysqli_stmt_bind_params_safe') || !mysqli_stmt_bind_params_safe($stmt, $types, $ids)) {
        $stmt->close();

        return [];
    }
    if (!$stmt->execute()) {
        $stmt->close();

        return [];
    }
    $rows = function_exists('mysqli_stmt_fetch_all_assoc') ? mysqli_stmt_fetch_all_assoc($stmt) : [];
    $stmt->close();

    return $rows;
}

/**
 * Ayarlar + menü + header_buttons: istek içi önbellek + dosya (fingerprint + üst TTL).
 *
 * @return array{0: array<string, string>, 1: list<array<string, mixed>>, 2: list<array<string, mixed>>}
 */
function mynak_public_layout_load_settings_and_menus(mysqli $conn): array
{
    static $requestCache = null;
    if ($requestCache !== null) {
        return $requestCache;
    }

    $maxAge = 43200;
    $file = mynak_public_layout_nav_bundle_cache_file();
    $fpNow = mynak_public_layout_nav_bundle_fingerprint($conn);

    if ($fpNow !== null && is_readable($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false && $raw !== '') {
            $j = json_decode($raw, true);
            if (
                is_array($j) && isset($j['version'], $j['built_at'], $j['fp'], $j['site_settings'], $j['menuItems'], $j['header_buttons'])
                && (int) $j['version'] >= 2
                && is_array($j['site_settings']) && is_array($j['menuItems']) && is_array($j['header_buttons'])
                && is_array($j['fp'])
            ) {
                $age = time() - (int) $j['built_at'];
                $fp = $j['fp'];
                $match = (int) ($fp['sc'] ?? -1) === $fpNow['sc']
                    && (int) ($fp['sv'] ?? -1) === $fpNow['sv']
                    && (int) ($fp['mc'] ?? -1) === $fpNow['mc']
                    && (int) ($fp['msig'] ?? -1) === $fpNow['msig']
                    && (int) ($fp['hbc'] ?? -1) === $fpNow['hbc']
                    && (int) ($fp['hbsig'] ?? -1) === $fpNow['hbsig'];
                if ($match && $age >= 0 && $age <= $maxAge) {
                    /** @var array<string, string> $ss */
                    $ss = $j['site_settings'];
                    /** @var list<array<string, mixed>> $mi */
                    $mi = $j['menuItems'];
                    /** @var list<array<string, mixed>> $hb */
                    $hb = $j['header_buttons'];
                    if (function_exists('mynak_sanitize_nav_url_string')) {
                        foreach ($mi as $k => $row) {
                            if (isset($row['url']) && is_string($row['url'])) {
                                $mi[$k]['url'] = mynak_sanitize_nav_url_string($row['url']);
                            }
                        }
                        foreach ($hb as $k => $row) {
                            if (isset($row['url']) && is_string($row['url'])) {
                                $hb[$k]['url'] = mynak_sanitize_nav_url_string($row['url']);
                            }
                        }
                    }
                    $requestCache = [$ss, $mi, $hb];

                    return $requestCache;
                }
            }
        }
    }

    $site_settings = [];
    $settings_result = $conn->query('SELECT name, value FROM settings');
    if ($settings_result && $settings_result->num_rows > 0) {
        while ($row = $settings_result->fetch_assoc()) {
            $site_settings[(string) $row['name']] = (string) $row['value'];
        }
    }

    $menuItems = [];
    $menu_result = $conn->query(
        'SELECT id, parent_id, title, url, target, menu_order, status FROM menus WHERE status = 1 ORDER BY parent_id ASC, menu_order ASC'
    );
    if ($menu_result && $menu_result->num_rows > 0) {
        while ($row = $menu_result->fetch_assoc()) {
            if (function_exists('mynak_sanitize_nav_url_string') && isset($row['url']) && is_string($row['url'])) {
                $row['url'] = mynak_sanitize_nav_url_string($row['url']);
            }
            $menuItems[] = $row;
        }
    }

    $header_buttons = [];
    $btn_sql = 'SELECT id, title, type, url, target, popup_id, form_id, status FROM header_buttons WHERE status = 1 ORDER BY id ASC';
    $btn_result = $conn->query($btn_sql);
    if ($btn_result && $btn_result->num_rows > 0) {
        while ($row = $btn_result->fetch_assoc()) {
            if (function_exists('mynak_sanitize_nav_url_string') && isset($row['url']) && is_string($row['url'])) {
                $row['url'] = mynak_sanitize_nav_url_string($row['url']);
            }
            $header_buttons[] = $row;
        }
    }

    $fpWrite = mynak_public_layout_nav_bundle_fingerprint($conn) ?? $fpNow ?? [
        'sc' => 0, 'sv' => 0, 'mc' => 0, 'msig' => 0, 'hbc' => 0, 'hbsig' => 0,
    ];

    $requestCache = [$site_settings, $menuItems, $header_buttons];
    $payload = [
        'version' => 2,
        'built_at' => time(),
        'fp' => $fpWrite,
        'site_settings' => function_exists('mynak_redact_settings_for_json_cache')
            ? mynak_redact_settings_for_json_cache($site_settings)
            : $site_settings,
        'menuItems' => $menuItems,
        'header_buttons' => $header_buttons,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json !== false && function_exists('mynak_cache_file_put_contents_locked')) {
        mynak_cache_file_put_contents_locked($file, $json);
    } elseif ($json !== false) {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, $json, LOCK_EX);
    }

    return $requestCache;
}

function mynak_public_layout_build_menu_tree(array $items, $parent_id = null): array
{
    $result = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent_id) {
            $children = mynak_public_layout_build_menu_tree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $result[] = $item;
        }
    }
    return $result;
}

/**
 * @param array<string, mixed> $inject allow_indexing, page, blog, page_title?, page_meta_description?, hide_title_suffix?, canonical_override?, meta_robots?, mynak_lcp_preload_href?, mynak_lcp_preload_type?, mynak_seo_fallback_content?
 * @return array<string, mixed>
 */
function mynak_public_layout_context(mysqli $conn, array $inject = []): array
{
    $page = isset($inject['page']) && is_array($inject['page']) ? $inject['page'] : null;
    $blog = isset($inject['blog']) && is_array($inject['blog']) ? $inject['blog'] : null;
    $allow_indexing = array_key_exists('allow_indexing', $inject) ? (bool) $inject['allow_indexing'] : true;

    $trimRel = trim(seo_runtime_compute_rel_path_from_request_uri($_SERVER['REQUEST_URI'] ?? '/'), '/');
    if ($trimRel === '' || strcasecmp($trimRel, 'index.php') === 0) {
        $current_page = 'index';
    } else {
        $parts = explode('/', $trimRel);
        $first = $parts[0] ?? 'index';
        $current_page = (string) preg_replace('/\.php$/i', '', $first);
        if ($current_page === '') {
            $current_page = 'index';
        }
    }

    if (array_key_exists('page_title', $inject)) {
        $page_title = $inject['page_title'];
    }
    if (array_key_exists('page_meta_description', $inject)) {
        $page_meta_description = $inject['page_meta_description'];
    }
    if (array_key_exists('hide_title_suffix', $inject)) {
        $hide_title_suffix = $inject['hide_title_suffix'];
    }
    $canonical_override = isset($inject['canonical_override']) ? (string) $inject['canonical_override'] : '';
    $meta_robots = array_key_exists('meta_robots', $inject) ? $inject['meta_robots'] : null;
    $mynak_lcp_preload_href = isset($inject['mynak_lcp_preload_href']) ? (string) $inject['mynak_lcp_preload_href'] : '';
    $mynak_lcp_preload_type = isset($inject['mynak_lcp_preload_type']) ? (string) $inject['mynak_lcp_preload_type'] : '';
    $mynak_seo_fallback_content = !empty($inject['mynak_seo_fallback_content']);

    mynak_public_layout_track_visitor($conn);

    [$site_settings, $menuItems, $header_buttons] = mynak_public_layout_load_settings_and_menus($conn);

    $show_languages = !empty($site_settings['show_languages']);
    $languages = [];
    if ($show_languages) {
        $languages_result = $conn->query('SELECT id, code, name, flag FROM languages WHERE status = 1 ORDER BY id ASC');
        if ($languages_result && $languages_result->num_rows > 0) {
            while ($row = $languages_result->fetch_assoc()) {
                $languages[] = $row;
            }
        }
    }

    $menuTree = mynak_public_layout_build_menu_tree($menuItems);

    $settings = [];
    foreach (['show_languages', 'primary_language'] as $sk) {
        if (isset($site_settings[$sk])) {
            $settings[$sk] = $site_settings[$sk];
        }
    }
    $primary_language = isset($settings['primary_language']) ? $settings['primary_language'] : 'tr';

    $default_seo_title = !empty($site_settings['site_title']) ? $site_settings['site_title'] : 'MY Nakliyat | Güvenilir Taşıma Ödüllü Firma';
    $seo_description = 'İzmir MY Nakliyat Evden Eve Nakliyat Firması';
    $header_scripts = '';
    $body_start_scripts = '';
    $body_end_scripts = '';
    $body_after_footer_scripts = '';
    $seo_settings = [];
    foreach ($site_settings as $sk_name => $sk_val) {
        if (str_starts_with((string) $sk_name, 'global_')) {
            $seo_settings[(string) $sk_name] = (string) $sk_val;
        }
    }
    $brandName = function_exists('mynak_schema_brand')
        ? mynak_schema_brand()
        : (defined('MYNAK_BRAND_NAME') ? (string) MYNAK_BRAND_NAME : 'MY Nakliyat');
    $title_suffix = function_exists('mynak_brand_title_suffix')
        ? mynak_brand_title_suffix()
        : (' | ' . $brandName);
    if (isset($seo_settings['global_title_suffix']) && trim((string) $seo_settings['global_title_suffix']) !== '') {
        $candidate = trim((string) $seo_settings['global_title_suffix']);
        $candidate = preg_replace('/\s*®\s*Resmi Sitesi.*$/iu', '', $candidate) ?? $candidate;
        $candidate = trim($candidate);
        $suffixLen = function_exists('mb_strlen') ? mb_strlen($candidate) : strlen($candidate);
        if (stripos($candidate, 'izmir evden eve') === false && $suffixLen <= 28) {
            $title_suffix = (str_starts_with($candidate, ' | ') || str_starts_with($candidate, ' - '))
                ? $candidate
                : ' | ' . ltrim($candidate, " \t\n\r\0\x0B-|");
        }
    }
    $mynak_header_rel = seo_runtime_compute_rel_path_from_request_uri($_SERVER['REQUEST_URI'] ?? '/');
    $mynak_header_get = isset($_GET) && is_array($_GET) ? $_GET : [];
    $mynak_header_page = $page;
    $mynak_header_blog = $blog;
    $mynak_seo_pipeline = canonical_seo_pipeline_core([
        'relPath' => $mynak_header_rel,
        'get' => $mynak_header_get,
        'page' => $mynak_header_page,
        'blog' => $mynak_header_blog,
        'site_settings' => $site_settings,
    ]);
    if (function_exists('seo_runtime_trace_record_page_type_decision')) {
        seo_runtime_trace_record_page_type_decision($mynak_seo_pipeline['decision']);
    }
    $mynak_canonical_page_type = (string) ($mynak_seo_pipeline['page_type'] ?? 'global');
    $page_type = $mynak_canonical_page_type;

    if (!isset($page_title)) {
        if ($mynak_canonical_page_type === 'home') {
            $page_title = $default_seo_title;
        } else {
            $trim = trim($mynak_header_rel, '/');
            $parts = $trim === '' ? [] : explode('/', $trim);
            $last = $parts !== [] ? $parts[count($parts) - 1] : 'page';
            $page_name = ucfirst(str_replace(['-', '_'], ' ', $last));
            $page_title = $page_name . $title_suffix;
        }
    } else {
        $should_hide_suffix = (isset($hide_title_suffix) && $hide_title_suffix)
            || in_array($mynak_canonical_page_type, ['blog_post', 'blog', 'service', 'global', 'about', 'contact', 'team'], true);

        if ($should_hide_suffix) {
        } elseif (!preg_match('/\b' . preg_quote($brandName, '/') . '\b/u', (string) $page_title)) {
            $page_title = $page_title . $title_suffix;
        }
    }
    if (function_exists('mynak_normalize_public_page_title')) {
        $page_title = mynak_normalize_public_page_title((string) $page_title);
    }
    if (isset($seo_settings['global_meta_description']) && !empty($seo_settings['global_meta_description'])) {
        $seo_description = $seo_settings['global_meta_description'];
    }
    if (isset($seo_settings['global_header_scripts']) && !empty($seo_settings['global_header_scripts'])) {
        $header_scripts = $seo_settings['global_header_scripts'];
    }
    if (isset($seo_settings['global_body_start_scripts']) && !empty($seo_settings['global_body_start_scripts'])) {
        $body_start_scripts = $seo_settings['global_body_start_scripts'];
    }
    if (isset($seo_settings['global_body_end_scripts']) && !empty($seo_settings['global_body_end_scripts'])) {
        $body_end_scripts = $seo_settings['global_body_end_scripts'];
    }
    if (isset($seo_settings['global_after_footer_scripts']) && !empty($seo_settings['global_after_footer_scripts'])) {
        $body_after_footer_scripts = $seo_settings['global_after_footer_scripts'];
    }

    if (isset($page_meta_description) && !empty($page_meta_description)) {
        $seo_description = $page_meta_description;
    }

    // Meta açıklama uzunluk tutarlılığı (merkezi/runtime):
    // - Uzun açıklamaları kelime sınırında 160'a kırpar (Google zaten ~160'ta keser).
    // - Açıklama boşsa veya zayıf global varsayılan kaldıysa, sayfa başlığından
    //   anlamlı bir açıklama üretir (çok kısa/eksik meta sorununu giderir).
    if (!function_exists('mynak_meta_description_clamp')) {
        $__metaHelper = __DIR__ . '/../mynak_meta_description.php';
        if (is_readable($__metaHelper)) {
            require_once $__metaHelper;
        }
        unset($__metaHelper);
    }
    if (function_exists('mynak_meta_description_clamp')) {
        $__desc = trim((string) $seo_description);
        $__weakDefaultDesc = 'İzmir MY Nakliyat Evden Eve Nakliyat Firması';
        if (($__desc === '' || $__desc === $__weakDefaultDesc) && function_exists('mynak_default_meta_description_for_page')) {
            $__slugForMeta = mb_strtolower(trim((string) $mynak_header_rel, '/'));
            $__desc = mynak_default_meta_description_for_page((string) $page_title, $__slugForMeta);
            unset($__slugForMeta);
        }
        $seo_description = mynak_meta_description_clamp($__desc, 160);
        unset($__desc, $__weakDefaultDesc);
    }

    $mobile_menu_items = [];
    $mobile_menu_result = $conn->query(
        'SELECT id, title, icon, link, target, bg_color, order_number FROM mobile_bottom_menu WHERE status = 1 ORDER BY order_number ASC, id ASC LIMIT 5'
    );
    if ($mobile_menu_result && $mobile_menu_result->num_rows > 0) {
        while ($row = $mobile_menu_result->fetch_assoc()) {
            if (function_exists('mynak_sanitize_nav_url_string') && isset($row['link']) && is_string($row['link'])) {
                $row['link'] = mynak_sanitize_nav_url_string($row['link']);
            }
            $mobile_menu_items[] = $row;
        }
    }

    $popup_ids = [];
    if (!empty($header_buttons)) {
        foreach ($header_buttons as $button) {
            if ($button['type'] == 'popup' && !empty($button['popup_id'])) {
                $popup_ids[] = $button['popup_id'];
            }
        }
    }
    $popups = !empty($popup_ids) ? mynak_popups_fetch_by_ids($conn, $popup_ids) : [];

    $welcome_popup = null;
    try {
        $welcome_popup_result = @$conn->query(
            'SELECT id, title, description, image, button_text, button_link, show_delay FROM welcome_popup WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
        );
        if ($welcome_popup_result && $welcome_popup_result->num_rows > 0) {
            $welcome_popup = $welcome_popup_result->fetch_assoc();
            if (
                is_array($welcome_popup)
                && !empty($welcome_popup['button_link'])
                && is_string($welcome_popup['button_link'])
                && function_exists('mynak_sanitize_nav_url_string')
            ) {
                $welcome_popup['button_link'] = mynak_sanitize_nav_url_string($welcome_popup['button_link']);
            }
        }
    } catch (Throwable $e) {
        error_log('welcome_popup: ' . $e->getMessage());
    }

    $sections_status = [];
    $sections_result = $conn->query('SELECT section_name, is_active FROM homepage_sections');
    if ($sections_result) {
        while ($row = $sections_result->fetch_assoc()) {
            $sections_status[$row['section_name']] = $row['is_active'];
        }
    }

    $default_sections = [
        'hero_slider' => 1,
        'campus_cards' => 1,
        'about_section' => 1,
        'gallery_section' => 1,
        'blog_section' => 1,
        'features_section' => 1,
        'kids_section' => 1,
        'team_section' => 1,
    ];

    foreach ($default_sections as $section => $default_status) {
        if (!isset($sections_status[$section])) {
            $sections_status[$section] = $default_status;
        }
    }

    $slides = [];
    $slides_q = $conn->query('SELECT id, title, subtitle, button1_text, button1_link, button2_text, button2_link, image, bg_color, order_number FROM slides WHERE status = 1 ORDER BY order_number ASC');
    if ($slides_q && $slides_q->num_rows > 0) {
        while ($row = $slides_q->fetch_assoc()) {
            $slides[] = $row;
        }
    }

    $hero_lcp_preload_href = '';
    $hero_lcp_preload_type = '';
    $hero_lcp_preload_html = '';
    if ($current_page === 'index' && function_exists('seo_upload_file_exists') && function_exists('seo_upload_url')) {
        $first_slide_image = null;
        if (!empty($slides) && isset($slides[0]['image']) && $slides[0]['image'] !== '') {
            $first_slide_image = (string) $slides[0]['image'];
        } else {
            $hs = $conn->query('SELECT image FROM slides WHERE status = 1 ORDER BY order_number ASC LIMIT 1');
            if ($hs && $hs->num_rows > 0) {
                $fr = $hs->fetch_assoc();
                $first_slide_image = !empty($fr['image']) ? (string) $fr['image'] : null;
            }
        }
        if ($first_slide_image !== null && $first_slide_image !== '' && function_exists('mynak_slide_lcp_preload_links')) {
            $bn = pathinfo($first_slide_image, PATHINFO_FILENAME);
            $lcp_dir = function_exists('mynak_slide_detect_upload_dir')
                ? mynak_slide_detect_upload_dir($first_slide_image)
                : 'slides/';
            $hero_lcp_preload_html = mynak_slide_lcp_preload_links($lcp_dir, $bn, $first_slide_image);
            if (mynak_slide_upload_relative_exists($lcp_dir, $bn . '.webp')) {
                $hero_lcp_preload_href = seo_upload_url($lcp_dir . $bn . '.webp');
                $hero_lcp_preload_type = 'image/webp';
            } elseif (mynak_slide_upload_relative_exists($lcp_dir, $bn . '.avif')) {
                $hero_lcp_preload_href = seo_upload_url($lcp_dir . $bn . '.avif');
                $hero_lcp_preload_type = 'image/avif';
            } elseif (seo_upload_file_exists($lcp_dir . $first_slide_image)) {
                $hero_lcp_preload_href = seo_upload_url($lcp_dir . $first_slide_image);
                $hero_lcp_preload_type = mynak_slide_mime_from_filename($first_slide_image);
            }
        }
    }

    if ($hero_lcp_preload_href === '' && $mynak_lcp_preload_href !== '') {
        $tpl = trim($mynak_lcp_preload_href);
        if ($tpl !== '') {
            $hero_lcp_preload_href = $tpl;
            $hero_lcp_preload_type = '';
            if ($mynak_lcp_preload_type !== '') {
                $hero_lcp_preload_type = trim($mynak_lcp_preload_type);
            } else {
                $p = (string) (parse_url($tpl, PHP_URL_PATH) ?? '');
                $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'avif' => 'image/avif', 'gif' => 'image/gif'];
                if (isset($map[$ext])) {
                    $hero_lcp_preload_type = $map[$ext];
                }
            }
        }
    }

    $services = [];
    $services_q = $conn->query(
        'SELECT id, ust_baslik, ana_baslik, aciklama, foto, link, order_number FROM services WHERE status = 1 ORDER BY order_number ASC, id DESC'
    );
    if ($services_q && $services_q->num_rows > 0) {
        while ($row = $services_q->fetch_assoc()) {
            foreach (['ust_baslik', 'ana_baslik', 'aciklama'] as $sf) {
                if (isset($row[$sf]) && is_string($row[$sf]) && function_exists('mynak_normalize_db_text')) {
                    $row[$sf] = mynak_normalize_db_text($row[$sf]);
                }
            }
            $services[] = $row;
        }
    }

    $about = null;
    $about_result = $conn->query(
        'SELECT id, baslik, aciklama, button1_text, button1_link, button2_text, button2_link, video_url, video_cover FROM about ORDER BY id DESC LIMIT 1'
    );
    if ($about_result && $about_result->num_rows > 0) {
        $about = $about_result->fetch_assoc();
    }

    $kids_section = null;
    $kids_result = $conn->query(
        'SELECT id, title, description, button_text, button_link, background_color, image, is_active FROM kids_section WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
    );
    if ($kids_result && $kids_result->num_rows > 0) {
        $kids_section = $kids_result->fetch_assoc();
    }

    $gallery_images = [];
    $gallery_q = $conn->query('SELECT id, title, image, order_number, cover FROM gallery WHERE status = 1 ORDER BY order_number ASC, id DESC LIMIT 12');
    if ($gallery_q && $gallery_q->num_rows > 0) {
        while ($row = $gallery_q->fetch_assoc()) {
            $gallery_images[] = $row;
        }
    }

    $__seo = seo_runtime_document_head(seo_runtime_head_template_context([
        'allow_indexing' => $allow_indexing,
        'canonical_override' => $canonical_override,
        'page' => $page,
        'blog' => $blog,
        'page_title' => $page_title,
        'seo_description' => $seo_description,
        'meta_robots_preset' => (is_string($meta_robots) && $meta_robots !== '') ? $meta_robots : null,
        'site_settings' => $site_settings,
        'seo_fallback_content' => $mynak_seo_fallback_content,
        'canonical_seo_pipeline' => $mynak_seo_pipeline,
    ]));
    $page_title = $__seo['page_title'];
    $seo_description = $__seo['seo_description'];
    if (function_exists('mynak_normalize_db_text')) {
        $page_title = mynak_normalize_db_text((string) $page_title);
        $seo_description = mynak_normalize_db_text((string) $seo_description);
    }
    $relPath = $__seo['relPath'];
    $canonical = $__seo['canonical'];
    $canonical_origin = $__seo['canonical_origin'];
    $meta_robots = $__seo['meta_robots'];
    $meta_robots_tag_html = $__seo['meta_robots_tag_html'];
    $mynak_base_path = $__seo['mynak_base_path'];
    $structured_head_markup = $__seo['structured_head_markup'];
    if (isset($__seo['seo_pipeline']) && is_array($__seo['seo_pipeline'])) {
        $mynak_seo_pipeline = $__seo['seo_pipeline'];
    }

    require_once dirname(__DIR__) . '/mynak_open_graph.php';
    $mynak_og = mynak_open_graph_build([
        'site_settings' => $site_settings,
        'blog' => $blog,
        'page' => $page,
        'hero_lcp_preload_href' => $hero_lcp_preload_href,
        'canonical' => $canonical,
        'page_title' => $page_title,
        'seo_description' => $seo_description,
        'page_type' => $mynak_canonical_page_type,
    ]);

    return [
        'current_page' => $current_page,
        'site_settings' => $site_settings,
        'menuItems' => $menuItems,
        'menuTree' => $menuTree,
        'header_buttons' => $header_buttons,
        'show_languages' => $show_languages,
        'languages' => $languages,
        'settings' => $settings,
        'primary_language' => $primary_language,
        'default_seo_title' => $default_seo_title,
        'seo_description' => $seo_description,
        'header_scripts' => $header_scripts,
        'body_start_scripts' => $body_start_scripts,
        'body_end_scripts' => $body_end_scripts,
        'body_after_footer_scripts' => $body_after_footer_scripts,
        'seo_settings' => $seo_settings,
        'title_suffix' => $title_suffix,
        'mynak_header_rel' => $mynak_header_rel,
        'mynak_header_get' => $mynak_header_get,
        'mynak_header_page' => $mynak_header_page,
        'mynak_header_blog' => $mynak_header_blog,
        'mynak_seo_pipeline' => $mynak_seo_pipeline,
        'mynak_canonical_page_type' => $mynak_canonical_page_type,
        'page_type' => $page_type,
        'page_title' => $page_title,
        'mobile_menu_items' => $mobile_menu_items,
        'popups' => $popups,
        'welcome_popup' => $welcome_popup,
        'sections_status' => $sections_status,
        'slides' => $slides,
        'hero_lcp_preload_href' => $hero_lcp_preload_href,
        'hero_lcp_preload_type' => $hero_lcp_preload_type,
        'hero_lcp_preload_html' => $hero_lcp_preload_html,
        'services' => $services,
        'about' => $about,
        'kids_section' => $kids_section,
        'gallery_images' => $gallery_images,
        'canonical_override' => $canonical_override,
        'relPath' => $relPath,
        'canonical' => $canonical,
        'canonical_origin' => $canonical_origin,
        'meta_robots' => $meta_robots,
        'meta_robots_tag_html' => $meta_robots_tag_html,
        'mynak_base_path' => $mynak_base_path,
        'structured_head_markup' => $structured_head_markup,
        'mynak_og' => $mynak_og,
    ];
}
