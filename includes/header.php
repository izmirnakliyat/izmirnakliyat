<?php
declare(strict_types=1);
/**
 * Sunum: yalnızca HTML/çıktı. Veri = $mynak_layout (pipeline) veya önceden extract edilmiş değişkenler.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/pipeline/public_layout_context.php';

if (!isset($mynak_layout) || !is_array($mynak_layout)) {
    $layoutInject = [
        'allow_indexing' => isset($allow_indexing) ? (bool) $allow_indexing : true,
        'page' => isset($page) && is_array($page) ? $page : null,
        'blog' => isset($blog) && is_array($blog) ? $blog : null,
        'canonical_override' => isset($canonical_override) ? (string) $canonical_override : '',
        'meta_robots' => $meta_robots ?? null,
        'mynak_lcp_preload_href' => isset($mynak_lcp_preload_href) ? (string) $mynak_lcp_preload_href : '',
        'mynak_lcp_preload_type' => isset($mynak_lcp_preload_type) ? (string) $mynak_lcp_preload_type : '',
        'mynak_seo_fallback_content' => !empty($mynak_seo_fallback_content),
    ];
    if (isset($page_title)) {
        $layoutInject['page_title'] = $page_title;
    }
    if (isset($page_meta_description)) {
        $layoutInject['page_meta_description'] = $page_meta_description;
    }
    if (isset($hide_title_suffix)) {
        $layoutInject['hide_title_suffix'] = $hide_title_suffix;
    }
    $mynak_layout = mynak_public_layout_context($conn, $layoutInject);
}

extract($mynak_layout, EXTR_OVERWRITE);

if (!isset($mynak_og) || !is_array($mynak_og)) {
    $mynak_og = [
        'image' => '',
        'type' => 'website',
        'site_name' => defined('MYNAK_BRAND_NAME') ? (string) MYNAK_BRAND_NAME : 'MY Nakliyat',
        'title' => '',
        'description' => '',
        'url' => '',
        'twitter_card' => 'summary_large_image',
    ];
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    $__mynakCanonLink = isset($canonical) && is_string($canonical) ? trim($canonical) : '';
    $__mynakRobotsStr = isset($meta_robots) && is_string($meta_robots) ? strtolower($meta_robots) : '';
    if ($__mynakCanonLink !== '' && strpos($__mynakRobotsStr, 'noindex') === false) {
        header('Link: <' . $__mynakCanonLink . '>; rel="canonical"', false);
        header('Link: <' . $__mynakCanonLink . '>; rel="alternate"; hreflang="tr-TR"', false);
        header('Link: <' . $__mynakCanonLink . '>; rel="alternate"; hreflang="x-default"', false);
    }
    unset($__mynakCanonLink, $__mynakRobotsStr);
}

$site_settings = is_array($site_settings ?? null) ? $site_settings : [];
$current_page = isset($current_page) && is_string($current_page) && $current_page !== '' ? $current_page : 'index';
$services = isset($services) && is_array($services) ? $services : [];
$menuTree = isset($menuTree) && is_array($menuTree) ? $menuTree : [];
$menuItems = isset($menuItems) && is_array($menuItems) ? $menuItems : [];
$header_buttons = isset($header_buttons) && is_array($header_buttons) ? $header_buttons : [];
$slides = isset($slides) && is_array($slides) ? $slides : [];
$gallery_images = isset($gallery_images) && is_array($gallery_images) ? $gallery_images : [];
$favicon = isset($site_settings['favicon']) ? (string) $site_settings['favicon'] : '';
?>
<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="tr">
<!--<![endif]-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
    $mynak_google_fonts_href = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap';
    ?>
    <link rel="preload" as="style" href="<?php echo htmlspecialchars($mynak_google_fonts_href, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($mynak_google_fonts_href, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <?php
    if (!function_exists('mynak_microsoft_clarity_head_markup')) {
        require_once __DIR__ . '/mynak_clarity.php';
    }
    echo mynak_microsoft_clarity_head_markup($site_settings);
    ?>
    <script>window.MYNAK_BASE=<?php echo json_encode($mynak_base_path, JSON_UNESCAPED_UNICODE); ?>;</script>
    <meta name="description" content="<?php echo mynak_esc_html((string) $seo_description); ?>">
    <link rel="icon"
        href="<?php echo $favicon !== '' ? UPLOAD_PATH . 'settings/' . htmlspecialchars($favicon) : ''; ?>"
        type="image/x-icon">
    <?php
    $mynak_meta_author = defined('MYNAK_BRAND_NAME') ? (string) MYNAK_BRAND_NAME : 'MY Nakliyat';
    ?>
    <meta name="author" content="<?php echo htmlspecialchars($mynak_meta_author, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php echo $meta_robots_tag_html; ?>

    <title><?php echo mynak_esc_html((string) $page_title); ?></title>
    <?php
    if (!empty($hero_lcp_preload_html)) {
        echo $hero_lcp_preload_html . "\n    ";
    } elseif ($hero_lcp_preload_href !== '') {
        $t = $hero_lcp_preload_type !== '' ? ' type="' . htmlspecialchars($hero_lcp_preload_type, ENT_QUOTES, 'UTF-8') . '"' : '';
        echo '<link rel="preload" as="image" fetchpriority="high"' . $t . ' href="' . htmlspecialchars($hero_lcp_preload_href, ENT_QUOTES, 'UTF-8') . '">';
    }
    ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical); ?>">
    <link rel="alternate" hreflang="tr-TR" href="<?php echo htmlspecialchars($canonical); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($canonical); ?>">
    <?php
    if (!empty($mynak_og['image']) && is_string($mynak_og['image'])) {
        $og = $mynak_og;
        $ogTitle = $og['title'] !== '' ? $og['title'] : $page_title;
        $ogDesc = $og['description'] !== '' ? $og['description'] : $seo_description;
        $ogUrl = $og['url'] !== '' ? $og['url'] : $canonical;
        $ogType = $og['type'] ?? 'website';
        $ogSite = $og['site_name'] ?? SITE_NAME;
        $twCard = $og['twitter_card'] ?? 'summary_large_image';
        echo '<meta property="og:site_name" content="' . htmlspecialchars($ogSite, ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
        echo '<meta property="og:locale" content="tr_TR">' . "\n    ";
        echo '<meta property="og:type" content="' . htmlspecialchars((string) $ogType, ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
        echo '<meta property="og:title" content="' . mynak_esc_html((string) $ogTitle) . '">' . "\n    ";
        echo '<meta property="og:description" content="' . mynak_esc_html((string) $ogDesc) . '">' . "\n    ";
        echo '<meta property="og:url" content="' . htmlspecialchars((string) $ogUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
        echo '<meta property="og:image" content="' . htmlspecialchars($og['image'], ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
        echo '<meta name="twitter:card" content="' . htmlspecialchars((string) $twCard, ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
        echo '<meta name="twitter:title" content="' . mynak_esc_html((string) $ogTitle) . '">' . "\n    ";
        echo '<meta name="twitter:description" content="' . mynak_esc_html((string) $ogDesc) . '">' . "\n    ";
        echo '<meta name="twitter:image" content="' . htmlspecialchars($og['image'], ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
    }
    $mynakContentNode = null;
    if (isset($blog) && is_array($blog) && (!empty($blog['id']) || isset($blog['slug']))) {
        $mynakContentNode = $blog;
    } elseif (isset($page) && is_array($page) && (!empty($page['id']) || isset($page['slug']))) {
        $mynakContentNode = $page;
    }
    if ($mynakContentNode !== null) {
        $mynakPublishedRaw = (string) ($mynakContentNode['created_at'] ?? '');
        $mynakModifiedRaw = (string) ($mynakContentNode['updated_at'] ?? '');
        if ($mynakModifiedRaw === '') {
            $mynakModifiedRaw = $mynakPublishedRaw;
        }
        $mynakPublishedTs = $mynakPublishedRaw !== '' ? strtotime($mynakPublishedRaw) : false;
        $mynakModifiedTs = $mynakModifiedRaw !== '' ? strtotime($mynakModifiedRaw) : false;
        if ($mynakPublishedTs !== false) {
            echo '<meta property="article:published_time" content="' . htmlspecialchars(date('c', $mynakPublishedTs), ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
        }
        if ($mynakModifiedTs !== false) {
            echo '<meta property="article:modified_time" content="' . htmlspecialchars(date('c', $mynakModifiedTs), ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
            echo '<meta property="og:updated_time" content="' . htmlspecialchars(date('c', $mynakModifiedTs), ENT_QUOTES, 'UTF-8') . '">';
        }
        unset($mynakPublishedRaw, $mynakModifiedRaw, $mynakPublishedTs, $mynakModifiedTs);
    }
    unset($mynakContentNode);
    ?>

    <link rel="shortcut icon" type="image/x-icon" href="<?php echo ASSET_PATH; ?>img/favicon.png">

    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>css/bootstrap.min.css">
    <?php
    // animate + keyframe-animation: scroll-trigger animasyonları — ilk render için gerekli değil
    echo mynak_link_stylesheet_deferred(ASSET_PATH . 'css/animate.min.css');
    echo mynak_link_stylesheet_deferred(ASSET_PATH . 'css/keyframe-animation.min.css');
    ?>
    <!-- Font Awesome 6 Free CDN (ikonlar üst bölümde; Pro CSS aşağıda ertelenir) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <?php
    echo mynak_link_stylesheet_deferred(ASSET_PATH . 'lib/font-awesome-pro/css/fontawesome.min.css');
    echo mynak_link_stylesheet_deferred(ASSET_PATH . 'css/logistic-icons.min.css');
    echo mynak_link_stylesheet_deferred(ASSET_PATH . 'css/odometer.min.css');
    echo mynak_link_stylesheet_deferred(ASSET_PATH . 'css/nice-select.min.css');
    ?>
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>css/swiper.min.css">
    <?php echo mynak_link_stylesheet_deferred(ASSET_PATH . 'css/venobox.min.css'); ?>
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>css/slider.min.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>css/common-style.min.css">
    <link rel="stylesheet" href="<?php echo ASSET_PATH; ?>css/main.min.css">

    <?php
    /**
     * SSOT guard (regresyon önleme): Canlı &lt;head&gt; içinde JSON-LD yalnızca $structured_head_markup ile basılır
     * (seo_runtime_pipeline_structured_head_markup → schema_factory). Bu bloğa ek script type="application/ld+json" KONULMAMALI.
     * @see MYNAK_PRODUCTION_JSONLD_EMITTER_RULE in includes/seo_runtime.php
     */
    ?>
    <!-- JSON-LD: schema_factory (canonical_seo_pipeline_core → jsonld_type_set) -->
    <?php echo $structured_head_markup; ?>
    <style>
        /* Google Translate üst bandı ve butonlarını gizle */
        iframe.goog-te-banner-frame,
        .goog-te-banner-frame,
        .goog-te-banner-frame.skiptranslate,
        body>.skiptranslate,
        html>.skiptranslate,
        #goog-gt-tt,
        .goog-te-balloon-frame,
        .goog-te-menu-frame,
        .goog-te-menu2,
        .goog-te-menu-value,
        .goog-te-gadget-simple {
            display: none !important;
            height: 0 !important;
            visibility: hidden !important;
        }

        body {
            top: 0 !important;
            margin-top: 0 !important;
        }

        .VIpgJd-ZVi9od-l4eHX-hSRGPd,
        .VIpgJd-ZVi9od-ORHb-OEVmcd {
            display: none !important;
        }

        /* Google gadget yalnızca gizli kök elemanda; header satırında bayrak menüsü kullanılır */
        .menu-right-item .goog-te-gadget,
        .menu-right-item .goog-te-combo,
        .lang-switcher .goog-te-gadget,
        .lang-switcher .goog-te-combo,
        .lang-dropdown .goog-te-gadget,
        .lang-dropdown .goog-te-combo {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .menu-right-item .lang-switcher,
        .menu-right-item .lang-dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            gap: 6px;
            white-space: nowrap;
        }

        .lang-dropdown-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .lang-dropdown-btn img {
            width: 24px;
            height: 18px;
            border: 1px solid #eee;
            border-radius: 2px;
            margin-right: 4px;
        }

        .lang-dropdown-btn .arrow {
            font-size: 12px;
            margin-left: 2px;
            color: #333;
        }

        .lang-dropdown-content {
            display: none;
            position: absolute;
            top: 110%;
            right: auto;
            left: auto;
            min-width: 40px;
            width: max-content;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            z-index: 100;
            padding: 4px 0;
        }

        .lang-dropdown-content.show {
            display: block;
        }

        .lang-dropdown-content a {
            display: flex;
            align-items: center;
            padding: 6px 10px;
            color: #222;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.15s;
            gap: 6px;
        }

        .lang-dropdown-content a img {
            width: 30px;
            height: 20px;
            border: 1px solid #eee;
            border-radius: 2px;
        }

        .lang-dropdown-content a:hover {
            background: #f5f5f5;
        }

        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.4);
        }

        .popup .popup-content {
            position: relative;
            z-index: 2;
            background: #fff;
            border-radius: 8px;
            max-width: 480px;
            width: 95vw;
            margin: auto;
            padding: 32px 24px 24px 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        .popup .close-popup {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            z-index: 10;
            color: #333;
            padding: 0;
            line-height: 1;
        }

        @media (max-width: 600px) {
            .popup .popup-content {
                padding: 16px 6px 16px 6px;
            }
        }

        .popup.active,
        .popup[style*='display: flex'] {
            display: flex !important;
        }

        #google_translate_element {
            display: none !important;
        }

        /* Mobile Bottom Menu Styles */
        .mobile-bottom-menu {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: #fff;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            z-index: 999;
            display: none;
            /* Varsayılan olarak gizli */
        }

        .mobile-bottom-menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            height: 60px;
            color: #fff;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
        }

        .mobile-bottom-menu-item i {
            font-size: 20px;
            margin-bottom: 4px;
        }

        @media (max-width: 767px) {
            .mobile-bottom-menu {
                display: flex !important;
                /* Mobilde kesinlikle göster */
                justify-content: space-around;
                align-items: center;
            }

            /* Mobilde header'daki dil ve butonları gizle */
            .lang-switcher,
            .lang-dropdown,
            .header-buttons {
                display: none !important;
            }
        }

        /* Mobil Menü Stilleri */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 300px;
            height: 100vh;
            background: #fff;
            z-index: 9999;
            transition: left 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: var(--primary-color);
        }

        .mobile-logo img {
            height: 40px;
            width: auto;
        }

        .close-menu {
            width: 30px;
            height: 30px;
            position: relative;
            cursor: pointer;
        }

        .close-menu span {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #fff;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
        }

        .close-menu span:first-child {
            transform: translateY(-50%) rotate(45deg);
        }

        .close-menu span:last-child {
            transform: translateY(-50%) rotate(-45deg);
        }

        .mobile-menu-content {
            height: calc(100vh - 80px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .mobile-menu-list {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
        }

        .mobile-menu-list li {
            border-bottom: 1px solid #eee;
            position: relative;
        }

        .mobile-menu-list li a {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }

        .mobile-menu-list li a:hover {
            background: #f5f5f5;
        }

        .submenu-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .mobile-submenu {
            display: none;
            background: #f9f9f9;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mobile-submenu li a {
            padding-left: 40px;
        }

        .mobile-menu-bottom {
            padding: 20px;
            border-top: 1px solid #eee;
            background: #f9f9f9;
        }

        .mobile-buttons {
            margin-bottom: 15px;
        }

        .mobile-buttons .btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            text-align: center;
            background: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }

        .mobile-lang {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .mobile-lang a {
            display: block;
        }

        .mobile-lang img {
            width: 30px;
            height: 20px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        /* Welcome Popup Stilleri */
        .welcome-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
        }

        .welcome-popup-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .welcome-popup-content {
            position: relative;
            z-index: 2;
            background: #fff;
            border-radius: 12px;
            max-width: 500px;
            width: 90vw;
            margin: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .welcome-popup-header {
            position: relative;
            padding: 15px;
            text-align: right;
        }

        .welcome-close-popup {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            padding: 5px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .welcome-close-popup:hover {
            background: #f0f0f0;
            color: #333;
        }

        .welcome-popup-body {
            padding: 0 30px 30px 30px;
        }

        .welcome-popup-image {
            text-align: center;
            margin-bottom: 20px;
        }

        .welcome-popup-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            max-height: 200px;
            object-fit: cover;
        }

        .welcome-popup-text h3 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
        }

        .welcome-popup-text p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
            text-align: center;
        }

        .welcome-popup-btn {
            display: block;
            width: 100%;
            padding: 12px 24px;
            background: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .welcome-popup-btn:hover {
            background: #d00000;
            color: #fff;
            text-decoration: none;
        }

        @media (max-width: 767px) {
            .welcome-popup-content {
                width: 95vw;
                margin: 20px;
            }

            .welcome-popup-body {
                padding: 0 20px 20px 20px;
            }

            .welcome-popup-text h3 {
                font-size: 20px;
            }

            .welcome-popup-text p {
                font-size: 14px;
            }
        }

        @media (max-width: 768px) {
            #header-search-box {
                width: 98vw !important;
                max-width: 98vw !important;
                padding: 18px 4vw 10px 4vw !important;
                border-radius: 0 0 18px 18px !important;
            }

            .header-search-wrap {
                display: none !important;
            }
        }
    </style>
    <?php
    if (!empty($mynak_head_stylesheets) && is_array($mynak_head_stylesheets)) {
        foreach ($mynak_head_stylesheets as $mynak_hs) {
            if (is_string($mynak_hs) && $mynak_hs !== '') {
                echo '<link rel="stylesheet" href="' . htmlspecialchars($mynak_hs, ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
            }
        }
    }
    ?>
</head>

<body>
    <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->

    <!-- Google Translate elementi (gizli değil, header.php'deki gibi) -->
    <div id="google_translate_element"></div>

    <!-- Mobile Bottom Menu -->
    <?php if (!empty($mobile_menu_items)): ?>
        <div class="mobile-bottom-menu">
            <?php foreach ($mobile_menu_items as $item): ?>
                <a href="<?php echo htmlspecialchars($item['link']); ?>" class="mobile-bottom-menu-item"
                    target="<?php echo htmlspecialchars($item['target']); ?>"
                    style="background-color: <?php echo htmlspecialchars($item['bg_color']); ?>;">
                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <span><?php echo mynak_esc_html((string) ($item['title'] ?? '')); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <header class="main-header">
        <div class="container">
            <div class="main-header-wapper">
                <div class="site-logo">
                    <a href="<?php echo htmlspecialchars(normalize_internal_link_url('/index.php')); ?>">
                        <?php if (!empty($site_settings['logo_light'])): ?>
                            <img src="<?php echo UPLOAD_PATH; ?>settings/<?php echo htmlspecialchars($site_settings['logo_light']); ?>"
                                <?php echo mynak_site_logo_dimension_attrs($site_settings, 'header'); ?>
                                <?php echo mynak_img_alt_attr('', function_exists('mynak_logo_alt_text') ? mynak_logo_alt_text() : 'MY Nakliyat Logo', UPLOAD_PATH . 'settings/' . ($site_settings['logo_light'] ?? '')); ?> />
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars(ASSET_PATH . 'img/logo-light.png', ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo mynak_site_logo_dimension_attrs($site_settings, 'header'); ?>
                                <?php echo mynak_img_alt_attr('', function_exists('mynak_logo_alt_text') ? mynak_logo_alt_text() : 'MY Nakliyat Logo', ASSET_PATH . 'img/logo-light.png'); ?> />
                        <?php endif; ?>
                    </a>
                </div>
                <div class="main-header-info">
                    <div class="top-header">
                        <ul class="top-left">
                            <?php if (!empty($site_settings['phone1'])): ?>
                                <li><i class="fa-regular fa-phone"></i><a
                                        href="<?php echo htmlspecialchars(footer_tel_uri($site_settings['phone1']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($site_settings['phone1']); ?></a>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($site_settings['email'])): ?>
                                <li><i class="fa-regular fa-envelope-dot"></i><a
                                        href="mailto:<?php echo htmlspecialchars($site_settings['email']); ?>"><?php echo htmlspecialchars($site_settings['email']); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <div class="top-right">
                            <ul class="header-social-share">
                                <?php if (!empty($site_settings['facebook'])): ?>
                                    <li><a href="https://facebook.com/<?php echo htmlspecialchars($site_settings['facebook']); ?>"
                                            target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a></li>
                                <?php endif; ?>
                                <?php if (!empty($site_settings['twitter'])): ?>
                                    <li><a href="https://twitter.com/<?php echo htmlspecialchars($site_settings['twitter']); ?>"
                                            target="_blank" rel="noopener"><i class="fa-brands fa-x-twitter"></i></a></li>
                                <?php endif; ?>
                                <?php if (!empty($site_settings['instagram'])): ?>
                                    <li><a href="https://instagram.com/<?php echo htmlspecialchars($site_settings['instagram']); ?>"
                                            target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a></li>
                                <?php endif; ?>
                                <?php if (!empty($site_settings['tiktok'])): ?>
                                    <li><a href="https://tiktok.com/@<?php echo htmlspecialchars($site_settings['tiktok']); ?>"
                                            target="_blank" rel="noopener"><i class="fa-brands fa-tiktok"></i></a></li>
                                <?php endif; ?>
                                <?php if (!empty($site_settings['linkedin'])): ?>
                                    <li><a href="https://linkedin.com/company/<?php echo htmlspecialchars($site_settings['linkedin']); ?>"
                                            target="_blank" rel="noopener"><i class="fa-brands fa-linkedin-in"></i></a></li>
                                <?php endif; ?>
                                <?php if (!empty($site_settings['youtube'])): ?>
                                    <li><a href="https://youtube.com/<?php echo htmlspecialchars($site_settings['youtube']); ?>"
                                            target="_blank" rel="noopener"><i class="fa-brands fa-youtube"></i></a></li>
                                <?php endif; ?>
                                <?php if (!empty($site_settings['website'])): ?>
                                    <li><a href="<?php echo htmlspecialchars($site_settings['website']); ?>" target="_blank"
                                            rel="noopener"><i class="fa-solid fa-globe"></i></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <!--/.top-header-->
                    <div class="header-menu-wrap">
                        <nav aria-label="Ana Menü" style="display:contents">
                        <ul class="nav-menu">
                            <?php
                            function renderMainMenu($items)
                            {
                                foreach ($items as $item) {
                                    echo '<li' . (!empty($item['children']) ? ' class="has-dropdown"' : '') . '>';
                                    $titleTrim = trim((string) ($item['title'] ?? ''));
                                    if ($titleTrim !== '' && strcasecmp($titleTrim, 'Blog') === 0 && function_exists('mynak_blog_href_path')) {
                                        $url = mynak_blog_href_path('');
                                    } else {
                                        $url = normalize_internal_link_url($item['url'] ?? '');
                                    }
                                    echo '<a href="' . htmlspecialchars($url) . '" target="' . htmlspecialchars($item['target']) . '">' . mynak_esc_html((string) ($item['title'] ?? '')) . '</a>';

                                    if (!empty($item['children'])) {
                                        echo '<ul class="dropdown">';
                                        renderMainMenu($item['children']);
                                        echo '</ul>';
                                    }

                                    echo '</li>';
                                }
                            }
                            renderMainMenu($menuTree);
                            ?>
                        </ul>
                        </nav>
                        <div class="menu-right-item">
                            <div class="mobile-menu-icon">
                                <i class="fa-regular fa-ellipsis-vertical"></i>
                            </div>
                            <div class="header-buttons">
                                <?php foreach ($header_buttons as $button): ?>
                                    <?php if ($button['type'] == 'popup'): ?>
                                        <?php if (strtolower(trim($button['title'])) == 'ön kayıt'): ?>
                                            <a href="<?php echo htmlspecialchars(normalize_internal_link_url('/kayit.php'), ENT_QUOTES, 'UTF-8'); ?>" class="default-btn"><?php if (!empty($button['icon'])): ?><i
                                                        class="<?php echo htmlspecialchars($button['icon']); ?>"></i><?php endif; ?><?php echo htmlspecialchars($button['title']); ?></a>
                                        <?php else: ?>
                                            <a href="#" class="default-btn open-popup"
                                                data-popup-id="<?php echo $button['popup_id']; ?>"><?php if (!empty($button['icon'])): ?><i
                                                        class="<?php echo htmlspecialchars($button['icon']); ?>"></i><?php endif; ?><?php echo htmlspecialchars($button['title']); ?></a>
                                        <?php endif; ?>
                                    <?php elseif ($button['type'] == 'popup_form'): ?>
                                        <a href="#" class="default-btn open-popup-form"
                                            data-form-id="<?php echo $button['form_id'] ?? 0; ?>"><?php if (!empty($button['icon'])): ?><i
                                                    class="<?php echo htmlspecialchars($button['icon']); ?>"></i><?php endif; ?><?php echo htmlspecialchars($button['title']); ?></a>
                                    <?php else: ?>
                                        <?php $url = normalize_internal_link_url($button['url'] ?? ''); ?>
                                        <a href="<?php echo htmlspecialchars($url); ?>" class="default-btn"
                                            target="<?php echo htmlspecialchars($button['target']); ?>"><?php if (!empty($button['icon'])): ?><i
                                                    class="<?php echo htmlspecialchars($button['icon']); ?>"></i><?php endif; ?><?php echo htmlspecialchars($button['title']); ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($show_languages && !empty($languages)): ?>
                                <div class="lang-switcher">
                                    <button class="lang-dropdown-btn" type="button" aria-label="Dil seçin">
                                        <img id="active-lang-flag"
                                            src="<?php echo !empty($languages) ? htmlspecialchars($languages[0]['flag']) : '/assets/img/flags/tr.png'; ?>"
                                            alt="<?php echo htmlspecialchars(!empty($languages) ? (string) $languages[0]['name'] : 'Türkçe', ENT_QUOTES, 'UTF-8'); ?>">
                                        <span class="arrow">&#9660;</span>
                                    </button>
                                    <div class="lang-dropdown-content">
                                        <?php foreach ($languages as $language): ?>
                                            <a href="#" data-lang="<?php echo $language['code']; ?>">
                                                <img src="<?php echo htmlspecialchars($language['flag']); ?>"
                                                    alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) $language['name'], (string) $language['name'], (string) $language['flag'])); ?>">
                                                <?php echo htmlspecialchars($language['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        const btn = document.querySelector('.lang-dropdown-btn');
                                        const content = document.querySelector('.lang-dropdown-content');
                                        if (!btn || !content) {
                                            return;
                                        }
                                        btn.addEventListener('click', function (e) {
                                            e.preventDefault();
                                            content.classList.toggle('show');
                                        });
                                        document.addEventListener('click', function (e) {
                                            if (!btn.contains(e.target) && !content.contains(e.target)) {
                                                content.classList.remove('show');
                                            }
                                        });
                                        if (window.translator && typeof window.translator.updateDropdownFlag === 'function') {
                                            const activeLang = localStorage.getItem('preferredLanguage') || 'tr';
                                            window.translator.updateDropdownFlag(activeLang);
                                        }
                                    });
                                </script>
                            <?php endif; ?>
                            <div class="header-search-wrap">
                                <button id="header-search-btn" type="button" aria-label="Site içi arama">
                                    <i class="fa fa-search" aria-hidden="true"></i>
                                </button>
                                <div id="header-search-overlay"
                                    style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);z-index:9998;">
                                </div>
                                <div id="header-search-box"
                                    style="display:none;position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);z-index:9999;background:#fff;border-radius:18px;box-shadow:0 8px 48px #0004;padding:36px 36px 18px 36px;width:600px;max-width:98vw;">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <input type="text" id="header-search-input"
                                            placeholder="Aramak istediğiniz kelime..."
                                            style="flex:1;padding:16px 18px;border:1px solid #eee;border-radius:10px;font-size:18px;">
                                        <button id="header-search-close" type="button" aria-label="Aramayı kapat"
                                            style="background:none;border:none;font-size:28px;cursor:pointer;line-height:1;">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="header-search-results"
                                        style="margin-top:18px;max-height:400px;overflow-y:auto;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--/.header-menu-wrap-->
                </div>
            </div>
        </div>
    </header>
    <!--/.main-header-->

    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <a href="<?php echo htmlspecialchars(normalize_internal_link_url('/index.php')); ?>">
                    <?php if (!empty($site_settings['logo_light'])): ?>
                        <img src="<?php echo UPLOAD_PATH; ?>settings/<?php echo htmlspecialchars($site_settings['logo_light']); ?>"
                            <?php echo mynak_site_logo_dimension_attrs($site_settings, 'header'); ?>
                            <?php echo mynak_img_alt_attr('', function_exists('mynak_logo_alt_text') ? mynak_logo_alt_text() : 'MY Nakliyat Logo', UPLOAD_PATH . 'settings/' . ($site_settings['logo_light'] ?? '')); ?>>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars(ASSET_PATH . 'img/logo-light.png', ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo mynak_site_logo_dimension_attrs($site_settings, 'header'); ?>
                            <?php echo mynak_img_alt_attr('', function_exists('mynak_logo_alt_text') ? mynak_logo_alt_text() : 'MY Nakliyat Logo', ASSET_PATH . 'img/logo-light.png'); ?>>
                    <?php endif; ?>
                </a>
            </div>
            <div class="close-menu">
                <span></span>
                <span></span>
            </div>
        </div>
        <div class="mobile-menu-content">
            <nav aria-label="Ana Menü (Mobil)" style="display:contents">
            <ul class="mobile-menu-list">
                <?php
                function renderMobileMenu($items)
                {
                    foreach ($items as $item) {
                        echo '<li' . (!empty($item['children']) ? ' class="has-submenu"' : '') . '>';
                        $titleTrim = trim((string) ($item['title'] ?? ''));
                        if ($titleTrim !== '' && strcasecmp($titleTrim, 'Blog') === 0 && function_exists('mynak_blog_href_path')) {
                            $url = mynak_blog_href_path('');
                        } else {
                            $url = normalize_internal_link_url($item['url'] ?? '');
                        }
                        echo '<a href="' . htmlspecialchars($url) . '" target="' . htmlspecialchars($item['target']) . '">' . htmlspecialchars($item['title']) . '</a>';

                        if (!empty($item['children'])) {
                            echo '<span class="submenu-toggle"><i class="fas fa-chevron-down"></i></span>';
                            echo '<ul class="mobile-submenu">';
                            renderMobileMenu($item['children']);
                            echo '</ul>';
                        }

                        echo '</li>';
                    }
                }
                renderMobileMenu($menuTree);
                ?>
            </ul>
            </nav>
            <div class="mobile-menu-bottom">
                <?php if (!empty($header_buttons)): ?>
                    <div class="mobile-buttons">
                        <?php foreach ($header_buttons as $button): ?>
                            <?php if ($button['type'] == 'popup'): ?>
                                <?php if (strtolower(trim($button['title'])) == 'ön kayıt'): ?>
                                    <a href="<?php echo htmlspecialchars(normalize_internal_link_url('/kayit.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn"><?php echo htmlspecialchars($button['title']); ?></a>
                                <?php else: ?>
                                    <a href="#" class="btn open-popup"
                                        data-popup-id="<?php echo $button['popup_id']; ?>"><?php echo htmlspecialchars($button['title']); ?></a>
                                <?php endif; ?>
                            <?php elseif ($button['type'] == 'popup_form'): ?>
                                <a href="#" class="btn open-popup-form"
                                    data-form-id="<?php echo $button['form_id'] ?? 0; ?>"><?php echo htmlspecialchars($button['title']); ?></a>
                            <?php else: ?>
                                <?php $url = normalize_internal_link_url($button['url'] ?? ''); ?>
                                <a href="<?php echo htmlspecialchars($url); ?>" class="btn"
                                    target="<?php echo htmlspecialchars($button['target']); ?>"><?php echo htmlspecialchars($button['title']); ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_languages && !empty($languages)): ?>
                    <div class="mobile-lang">
                        <?php foreach ($languages as $language): ?>
                            <a href="#" data-lang="<?php echo $language['code']; ?>">
                                <img src="<?php echo htmlspecialchars($language['flag']); ?>"
                                    alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) $language['name'], (string) $language['name'], (string) $language['flag'])); ?>">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="popup-search-box">
        <div class="box-inner-wrap d-flex align-items-center">
            <form id="form" action="#" method="get" role="search">
                <input id="popup-search" type="text" name="s" placeholder="Type keywords here...">
                <button id="popup-search-button" type="submit" name="submit">
                    <i class="fa-sharp fa-light fa-magnifying-glass"></i>
                </button>
            </form>
            <div class="search-close"><i class="fa-regular fa-xmark"></i></div>
        </div>
    </div>
    <!--/.popupsearch-box-->

    <div id="searchbox-overlay"></div>

    <?php // Popuplar için HTML (header_buttons ile ilişkili) ?>
    <?php if (!empty($popups)): ?>
        <?php foreach ($popups as $popup): ?>
            <div id="popup<?php echo $popup['id']; ?>" class="popup" style="display: none;">
                <div class="popup-overlay"></div>
                <div class="popup-content">
                    <div class="popup-header" style="position: relative;">
                        <h3 style="margin-right: 32px;"><?php echo htmlspecialchars($popup['title']); ?></h3>
                        <button class="close-popup" aria-label="Kapat"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="popup-body">
                        <?php
                        $content_data = json_decode($popup['content'], true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($content_data['type']) && $content_data['type'] === 'form') {
                            $form_elements = $content_data['elements'] ?? [];
                            $settings = $content_data['settings'] ?? [];
                            $intro_text = $settings['intro_text'] ?? 'Lütfen aşağıdaki formu doldurarak bizimle iletişime geçin.';
                            $submit_text = $settings['submit_text'] ?? 'Gönder';
                            echo '<div class="popup-form">';
                            echo '<p>' . htmlspecialchars($intro_text) . '</p>';
                            echo '<form class="popup-contact-form" id="popupForm' . $popup['id'] . '" data-popup-id="' . $popup['id'] . '">';
                            echo '<input type="hidden" name="popup_id" value="' . $popup['id'] . '" class="notranslate">';
                            $csrfPop = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
                            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfPop, ENT_QUOTES, 'UTF-8') . '">';
                            foreach ($form_elements as $element) {
                                $type = $element['type'] ?? 'text';
                                $label = $element['label'] ?? '';
                                $name = $element['name'] ?? '';
                                $placeholder = $element['placeholder'] ?? '';
                                $required = isset($element['required']) && $element['required'] ? 'required' : '';
                                $options = $element['options'] ?? [];
                                $form_group_class = 'form-group';
                                if ($type === 'agreement') {
                                    $form_group_class .= ' consent';
                                }
                                echo '<div class="' . $form_group_class . '">';
                                switch ($type) {
                                    case 'textarea':
                                        if (!empty($label)) {
                                            echo '<label class="form-label" for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
                                        }
                                        echo '<textarea class="form-control" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . ' rows="3"></textarea>';
                                        break;
                                    case 'select':
                                        if (!empty($label)) {
                                            echo '<label class="form-label" for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
                                        }
                                        echo '<select class="form-control" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $required . '>';
                                        echo '<option value="">' . htmlspecialchars($placeholder) . '</option>';
                                        foreach ($options as $option) {
                                            echo '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                                        }
                                        echo '</select>';
                                        break;
                                    case 'checkbox':
                                        if (!empty($options)) {
                                            if (!empty($label)) {
                                                echo '<label class="form-label">' . htmlspecialchars($label) . '</label>';
                                            }
                                            foreach ($options as $option) {
                                                echo '<div class="form-check">';
                                                echo '<input class="form-check-input" type="checkbox" id="' . htmlspecialchars($name . '_' . md5($option)) . '" name="' . htmlspecialchars($name) . '[]" value="' . htmlspecialchars($option) . '">';
                                                echo '<label class="form-check-label" for="' . htmlspecialchars($name . '_' . md5($option)) . '">' . htmlspecialchars($option) . '</label>';
                                                echo '</div>';
                                            }
                                        } else {
                                            echo '<div class="form-check">';
                                            echo '<input class="form-check-input" type="checkbox" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $required . '>';
                                            echo '<label class="form-check-label" for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
                                            echo '</div>';
                                        }
                                        break;
                                    case 'radio':
                                        if (!empty($label)) {
                                            echo '<label class="form-label">' . htmlspecialchars($label) . '</label>';
                                        }
                                        foreach ($options as $option) {
                                            echo '<div class="form-check">';
                                            echo '<input class="form-check-input" type="radio" id="' . htmlspecialchars($name . '_' . md5($option)) . '" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($option) . '" ' . $required . '>';
                                            echo '<label class="form-check-label" for="' . htmlspecialchars($name . '_' . md5($option)) . '">' . htmlspecialchars($option) . '</label>';
                                            echo '</div>';
                                        }
                                        break;
                                    case 'file':
                                        if (!empty($label)) {
                                            echo '<label class="form-label" for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
                                        }
                                        echo '<input type="file" class="form-control" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $required . '>';
                                        break;
                                    case 'agreement':
                                        $agreementText = isset($element['agreementText']) ? $element['agreementText'] : '';
                                        echo '<div class="form-check">';
                                        echo '<input class="form-check-input" type="checkbox" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $required . '>';
                                        echo '<label class="form-check-label agreement-text" for="' . htmlspecialchars($name) . '">' . $agreementText . '</label>';
                                        echo '</div>';
                                        break;
                                    default:
                                        if (!empty($label)) {
                                            echo '<label class="form-label" for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
                                        }
                                        echo '<input type="' . $type . '" class="form-control" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . '>';
                                        break;
                                }
                                echo '</div>';
                            }
                            echo '<div class="form-actions">';
                            echo '<button type="submit" class="btn btn-primary">' . htmlspecialchars($submit_text) . '</button>';
                            echo '</div>';
                            $success_message = $settings['success_message'] ?? 'Mesajınız başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.';
                            echo '<div class="form-success-message mt-3" style="display: none;">';
                            echo '<div class="success-icon"><i class="fas fa-check-circle"></i></div>';
                            echo '<h4>Başvurunuz Alındı!</h4>';
                            echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                            echo '</div>';
                            echo '</form>';
                            echo '</div>';
                        } else {
                            echo $popup['content'];
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php // Welcome popup için HTML ?>
    <?php if ($welcome_popup): ?>
        <div id="welcome-popup" class="welcome-popup" style="display: none;">
            <div class="welcome-popup-overlay"></div>
            <div class="welcome-popup-content">
                <div class="welcome-popup-header">
                    <button class="welcome-close-popup" aria-label="Kapat"><i class="fas fa-times"></i></button>
                </div>
                <div class="welcome-popup-body">
                    <?php if (!empty($welcome_popup['image'])): ?>
                        <div class="welcome-popup-image">
                            <img src="<?php echo UPLOAD_PATH; ?>popup/<?php echo htmlspecialchars($welcome_popup['image']); ?>"
                                alt="<?php echo htmlspecialchars(mynak_public_image_alt((string) ($welcome_popup['title'] ?? ''), 'MY Nakliyat duyuru görseli', UPLOAD_PATH . 'popup/' . ($welcome_popup['image'] ?? ''))); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="welcome-popup-text">
                        <h3><?php echo htmlspecialchars($welcome_popup['title']); ?></h3>
                        <?php if (!empty($welcome_popup['description'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($welcome_popup['description'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($welcome_popup['button_link'])): ?>
                            <a href="<?php echo htmlspecialchars($welcome_popup['button_link']); ?>" class="welcome-popup-btn">
                                <?php echo htmlspecialchars($welcome_popup['button_text']); ?>
                            </a>
                        <?php else: ?>
                            <button class="welcome-popup-btn" onclick="closeWelcomePopup()">
                                <?php echo htmlspecialchars($welcome_popup['button_text']); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Popup işlemleri
            document.querySelectorAll('.open-popup').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var popupId = this.getAttribute('data-popup-id');
                    var popup = document.getElementById('popup' + popupId);
                    if (popup) popup.style.display = 'flex';
                });
            });
            document.querySelectorAll('.popup .close-popup, .popup .popup-overlay').forEach(function (el) {
                el.addEventListener('click', function () {
                    var popup = this.closest('.popup');
                    if (popup) popup.style.display = 'none';
                });
            });

            // Mobil menü işlemleri
            const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
            const mobileMenu = document.querySelector('.mobile-menu');
            const closeMenu = document.querySelector('.close-menu');

            if (mobileMenuIcon && mobileMenu) {
                mobileMenuIcon.addEventListener('click', function (e) {
                    e.stopPropagation();
                    mobileMenu.classList.add('active');
                    mobileMenu.style.left = '0';
                    document.body.style.overflow = 'hidden';
                });
            }

            if (closeMenu && mobileMenu) {
                closeMenu.addEventListener('click', function () {
                    mobileMenu.classList.remove('active');
                    mobileMenu.style.left = '';
                    document.body.style.overflow = '';
                });
            }

            document.addEventListener('click', function (e) {
                if (mobileMenu && mobileMenu.classList.contains('active') && !mobileMenu.contains(e.target)) {
                    mobileMenu.classList.remove('active');
                    mobileMenu.style.left = '';
                    document.body.style.overflow = '';
                }
            });

            // Mobil menüde submenu toggle
            document.querySelectorAll('.submenu-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    const parent = this.parentElement;
                    const submenu = parent.querySelector('.mobile-submenu');
                    if (submenu) {
                        parent.classList.toggle('open');
                        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                    }
                });
            });

            // Welcome popup işlemleri
            <?php if ($welcome_popup): ?>
                const welcomePopup = document.getElementById('welcome-popup');
                const welcomeCloseBtn = document.querySelector('.welcome-close-popup');
                const welcomeOverlay = document.querySelector('.welcome-popup-overlay');

                // Welcome popup'ı göster
                function showWelcomePopup() {
                    if (welcomePopup) {
                        welcomePopup.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    }
                }

                // Welcome popup'ı kapat
                function closeWelcomePopup() {
                    if (welcomePopup) {
                        welcomePopup.style.display = 'none';
                        document.body.style.overflow = '';
                        // Popup'ı bir daha gösterme
                        localStorage.setItem('welcome_popup_shown', 'true');
                    }
                }

                // Welcome popup kapatma olayları
                if (welcomeCloseBtn) {
                    welcomeCloseBtn.addEventListener('click', closeWelcomePopup);
                }
                if (welcomeOverlay) {
                    welcomeOverlay.addEventListener('click', closeWelcomePopup);
                }

                // Sayfa yüklendiğinde welcome popup'ı göster
                const showDelay = <?php echo $welcome_popup['show_delay'] ?? 2000; ?>;
                const popupShown = localStorage.getItem('welcome_popup_shown');

                if (!popupShown) {
                    setTimeout(showWelcomePopup, showDelay);
                }
            <?php endif; ?>
        });
    </script>
    <script src="<?php echo PUBLIC_JS_PATH; ?>translator.min.js" defer></script>
    <script src="<?php echo PUBLIC_JS_PATH; ?>main.min.js" defer></script>
    <script>
        // HEADER SEARCH KUTUSU
        const __UPLOAD_BASE = <?php echo json_encode(UPLOAD_PATH, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const __IMG_DEFAULT = <?php echo json_encode(function_exists('seo_default_placeholder_image_url') ? seo_default_placeholder_image_url() : '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const searchBtn = document.getElementById('header-search-btn');
        const searchBox = document.getElementById('header-search-box');
        const searchOverlay = document.getElementById('header-search-overlay');
        const searchInput = document.getElementById('header-search-input');
        const searchClose = document.getElementById('header-search-close');
        const searchResults = document.getElementById('header-search-results');
        if (searchBtn && searchBox && searchInput && searchClose && searchOverlay) {
            function openSearchBox() {
                searchBox.style.display = 'block';
                searchOverlay.style.display = 'block';
                searchInput.focus();
            }
            function closeSearchBox() {
                searchBox.style.display = 'none';
                searchOverlay.style.display = 'none';
                searchInput.value = '';
                searchResults.innerHTML = '';
            }
            searchBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                openSearchBox();
            });
            searchClose.addEventListener('click', closeSearchBox);
            searchOverlay.addEventListener('click', closeSearchBox);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeSearchBox();
            });
            // AJAX ile arama
            let searchTimeout;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                if (query.length < 2) {
                    searchResults.innerHTML = '';
                    return;
                }
                searchTimeout = setTimeout(function () {
                    var _base = (typeof window.MYNAK_BASE === 'string' ? window.MYNAK_BASE : '');
                    fetch(_base + '/ajax/search_blog.php?q=' + encodeURIComponent(query))
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.results.length > 0) {
                                searchResults.innerHTML = data.results.map(item =>
                                    `<a href="${_base}/${item.slug}" style="display:flex;align-items:center;gap:16px;padding:12px 0;color:#0056b3;text-decoration:none;border-bottom:1px solid #f2f2f2;">
                                    <img src="${item.kapak_foto ? (item.kapak_foto.startsWith('media/') ? __UPLOAD_BASE + item.kapak_foto : __UPLOAD_BASE + 'blog/' + item.kapak_foto) : __IMG_DEFAULT}" alt="${(item.baslik || 'MY Nakliyat blog yazısı').replace(/"/g, '&quot;')}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;background:#f5f5f5;">
                                    <span style="font-size:18px;">${item.baslik}</span>
                                </a>`
                                ).join('');
                            } else {
                                searchResults.innerHTML = '<div style="color:#888;font-size:17px;">Sonuç bulunamadı.</div>';
                            }
                        })
                        .catch(() => {
                            searchResults.innerHTML = '<div style="color:#888;font-size:17px;">Bir hata oluştu.</div>';
                        });
                }, 250);
            });
        }
    </script>