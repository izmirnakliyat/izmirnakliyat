<?php
/**
 * Site için yardımcı fonksiyonlar
 * Bu dosya site genelinde kullanılan ortak fonksiyonları içerir
 */

require_once __DIR__ . '/blog_post_status.php';
require_once __DIR__ . '/mynak_gbp_sync.php';
require_once __DIR__ . '/mynak_seo_length_helpers.php';

/**
 * Ad-soyaddan baş harfleri döndürür.
 *
 * "Ahmet Yılmaz" → "AY", "Ali" → "A", "" → "?"
 */
function mynak_initials_from_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/u', $name);
    if ($parts === false || $parts === []) {
        return '?';
    }
    $initials = '';
    foreach ($parts as $part) {
        $ch = function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
        $initials .= function_exists('mb_strtoupper') ? mb_strtoupper($ch, 'UTF-8') : strtoupper($ch);
    }
    return $initials;
}

/**
 * header.php öncesi $site_settings kullanımı için (mysqli bağlı olmalı).
 *
 * @return array<string, string>
 */
function mynak_site_settings_bootstrap(mysqli $conn): array
{
    $out = [];
    $res = $conn->query('SELECT name, value FROM settings');
    if ($res) {
    while ($row = $res->fetch_assoc()) {
        $val = (string) ($row['value'] ?? '');
        $out[$row['name']] = mynak_normalize_db_text($val);
    }
    }
    return mynak_apply_contact_defaults_to_settings_array($out);
}

/**
 * Ayarlarda boş kalan kurumsal alanları MYNAK_CONTACT_* ile doldurur.
 *
 * @param array<string, string> $settings
 * @return array<string, string>
 */
function mynak_apply_contact_defaults_to_settings_array(array $settings): array
{
    foreach (mynak_contact_settings_defaults() as $key => $defaultVal) {
        if ($defaultVal === '') {
            continue;
        }
        $cur = isset($settings[$key]) ? trim((string) $settings[$key]) : '';
        if ($cur === '') {
            $settings[$key] = $defaultVal;
        }
    }
    return $settings;
}

/**
 * @return array<string, string>
 */
function mynak_contact_settings_defaults(): array
{
    $phone = defined('MYNAK_CONTACT_PHONE_DISPLAY') ? MYNAK_CONTACT_PHONE_DISPLAY : '+90 850 203 12 52';
    $wa = defined('MYNAK_CONTACT_WHATSAPP_DISPLAY') ? MYNAK_CONTACT_WHATSAPP_DISPLAY : $phone;
    $addr = defined('MYNAK_CONTACT_ADDRESS_DISPLAY') ? MYNAK_CONTACT_ADDRESS_DISPLAY : 'Seyhan, 653/2. Sk. :10 K:3, Buca/İzmir';
    $maps = defined('MYNAK_CONTACT_GOOGLE_MAPS_URL') ? MYNAK_CONTACT_GOOGLE_MAPS_URL : 'https://maps.app.goo.gl/KhjpeauhbhoXaupZ8';

    return [
        'phone1' => $phone,
        'whatsapp' => $wa,
        'address' => $addr,
        'google_maps_url' => $maps,
        'contact_map_embed' => mynak_contact_default_map_embed_html(),
    ];
}

function mynak_contact_default_map_embed_html(): string
{
    $src = defined('MYNAK_CONTACT_MAP_EMBED_SRC')
        ? MYNAK_CONTACT_MAP_EMBED_SRC
        : 'https://www.google.com/maps?q=Seyhan%2C%20653%2F2.%20Sk.%20%3A10%20K%3A3%2C%20Buca%2F%C4%B0zmir&hl=tr&z=17&output=embed';
    $esc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');

    return '<iframe src="' . $esc . '" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
}

// Güvenli metin çıktısı için
function mynak_decode_html_entities(string $text): string
{
    if ($text === '') {
        return '';
    }

    static $legacyNamed = [
        '&c_cedil;' => 'ç', '&C_cedil;' => 'Ç',
        '&s_cedil;' => 'ş', '&S_cedil;' => 'Ş',
        '&g_breve;' => 'ğ', '&G_breve;' => 'Ğ',
        '&u_uml;' => 'ü', '&U_uml;' => 'Ü',
        '&o_uml;' => 'ö', '&O_uml;' => 'Ö',
        '&i_dotless;' => 'ı', '&I_dot;' => 'İ',
    ];
    if ($legacyNamed !== []) {
        $text = str_replace(array_keys($legacyNamed), array_values($legacyNamed), $text);
    }

    $prev = null;
    while ($prev !== $text) {
        $prev = $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    return $text;
}

/** Düz metin DB alanları (başlık, menü, ayar) — entity decode, HTML escape yok. */
function mynak_normalize_db_text(string $text): string
{
    return mynak_decode_html_entities($text);
}

/** WYSIWYG / gövde HTML — çift encode ve legacy entity düzeltmesi (tag yapısı korunur). */
function mynak_normalize_rich_html(string $html): string
{
    if ($html === '') {
        return '';
    }

    return mynak_decode_html_entities($html);
}

function mynak_esc_html(string $text): string
{
    return htmlspecialchars(mynak_decode_html_entities($text), ENT_QUOTES, 'UTF-8');
}

function guvenli_cikti($text) {
    return mynak_esc_html((string) $text);
}

/**
 * Oturumda CSRF token yoksa üretir (config.php ile aynı mantık).
 */
function mynak_ensure_csrf_token(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * process_form.php ve header.php ile hizalı csrf_token gizli alanı.
 */
function mynak_csrf_hidden_input(): string
{
    mynak_ensure_csrf_token();
    $token = $_SESSION['csrf_token'] ?? '';
    if (!is_string($token) || $token === '') {
        return '';
    }
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Şablonda zaten <h2> varken DB'deki bölüm main_heading içindeki h1–h6 etiketlerini kaldırır (iç metin kalır).
 */
function mynak_section_heading_inner_html(string $html): string
{
    if ($html === '') {
        return '';
    }

    return trim(preg_replace('/<\/?h[1-6][^>]*>/iu', '', $html));
}

/**
 * Anlamsız dosya adı / Sponsor N / Gallery Image / hash (68641b52e38bc) gibi alt adaylarını tespit eder.
 */
function mynak_is_meaningless_image_label(string $candidate, ?string $srcPath = null): bool
{
    $candidate = trim(mynak_decode_html_entities($candidate));
    if ($candidate === '') {
        return true;
    }
    if (preg_match('/^(gallery image|galeri görsel|logo|truck|yorum fotoğrafı|blog görseli)$/iu', $candidate)) {
        return true;
    }
    if (preg_match('/^sponsor\s+\d+$/iu', $candidate)) {
        return true;
    }
    if (preg_match('/^(IMG[-_]|DSC|DSCN|WP_|WA\d|PHOTO[-_])/iu', $candidate)) {
        return true;
    }
    if (preg_match('/\.(jpe?g|png|webp|gif|avif|svg)$/iu', $candidate)) {
        return true;
    }
    if (preg_match('/^[\d_-]+$/', $candidate)) {
        return true;
    }
    if (preg_match('/^[a-f0-9]{10,}$/i', $candidate)) {
        return true;
    }

    if ($srcPath !== null && $srcPath !== '') {
        $path = (string) (parse_url($srcPath, PHP_URL_PATH) ?? $srcPath);
        $stem = pathinfo($path, PATHINFO_FILENAME);
        $file = basename($path);
        if ($stem !== '' && (strcasecmp($candidate, $stem) === 0 || strcasecmp($candidate, $file) === 0)) {
            if (preg_match('/^[a-f0-9]{8,}$/i', $stem)) {
                return true;
            }
            if (preg_match('/^(IMG[-_]|DSC|WA\d)/iu', $stem)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Dosya yolundan okunabilir alt metin (hash / IMG_ önekleri elenir).
 */
function mynak_alt_label_from_src_path(?string $srcPath): string
{
    if ($srcPath === null || trim($srcPath) === '') {
        return '';
    }
    $path = (string) (parse_url($srcPath, PHP_URL_PATH) ?? $srcPath);
    $stem = pathinfo($path, PATHINFO_FILENAME);
    $stem = trim((string) $stem);
    if ($stem === '' || mynak_is_meaningless_image_label($stem, $srcPath)) {
        return '';
    }
    $stem = preg_replace('/[_-]+/u', ' ', $stem) ?? $stem;
    $stem = trim(preg_replace('/\s+/u', ' ', $stem) ?? $stem);

    return $stem;
}

/**
 * Anlamsız dosya adı / Sponsor N / Gallery Image gibi alt değerlerini bağlama uygun fallback ile değiştirir.
 * Zincir: image title/alt → page_title → dosya adı → fallback → MY Nakliyat
 */
function mynak_public_image_alt(string $candidate, string $fallback = '', ?string $srcPath = null, ?string $pageTitle = null): string
{
    $candidate = trim(mynak_decode_html_entities($candidate));
    if ($candidate !== '' && !mynak_is_meaningless_image_label($candidate, $srcPath)) {
        return $candidate;
    }

    $pageTitle = trim((string) ($pageTitle ?? ''));
    if ($pageTitle !== '') {
        return $pageTitle;
    }

    $fromFile = mynak_alt_label_from_src_path($srcPath);
    if ($fromFile !== '') {
        return $fromFile;
    }

    $fallback = trim($fallback);
    if ($fallback !== '') {
        return $fallback;
    }

    if (!empty($GLOBALS['mynak_img_alt_context']) && is_string($GLOBALS['mynak_img_alt_context'])) {
        $ctx = trim($GLOBALS['mynak_img_alt_context']);
        if ($ctx !== '') {
            return $ctx;
        }
    }

    return 'MY Nakliyat';
}

/** Sayfa/blog bağlamına göre varsayılan alt metin (içerik HTML filtresi). */
function mynak_img_alt_context_fallback(string $override = ''): string
{
    if (trim($override) !== '') {
        return trim($override);
    }
    if (!empty($GLOBALS['mynak_img_alt_context']) && is_string($GLOBALS['mynak_img_alt_context'])) {
        return trim($GLOBALS['mynak_img_alt_context']);
    }

    return 'MY Nakliyat — nakliyat ve taşımacılık görseli';
}

/**
 * Dekoratif tema ikonları (kamyon çizgisi, ok, pattern vb.).
 */
function mynak_is_decorative_image_src(string $src, ?string $class = null): bool
{
    $src = trim($src);
    $class = trim((string) $class);
    if ($class !== '' && preg_match('/\b(sh-truck|decorative|icon-only|emoji|smiley|arrow-icon|divider-icon)\b/i', $class)) {
        return true;
    }
    $path = strtolower((string) (parse_url($src, PHP_URL_PATH) ?? $src));
    $decorativeNeedles = [
        'truck.svg', 'truck.png', 'map-pattern', 'pattern.svg', 'sh-underline',
        'arrow.svg', 'chevron', '/img/shape', '/icons/', '/decor/',
    ];
    foreach ($decorativeNeedles as $needle) {
        if (str_contains($path, $needle)) {
            return true;
        }
    }

    return false;
}

/** Dekoratif ikonlar (kamyon çizgisi vb.) — ekran okuyucu atlar. */
function mynak_decorative_img_attrs(): string
{
    return 'alt="" role="presentation" aria-hidden="true"';
}

/** sh-truck / truck.svg dekoratif ikonu — CLS için sabit 36×36. */
function mynak_sh_truck_img_attrs(): string
{
    return mynak_decorative_img_attrs() . ' width="36" height="36"';
}

/**
 * Header/footer logo CLS: CSS genişliğine göre width/height HTML öznitelikleri.
 *
 * @param 'header'|'footer' $slot
 */
function mynak_site_logo_dimension_attrs(array $site_settings, string $slot = 'header'): string
{
    $displayWidth = ($slot === 'footer') ? 200 : 150;
    $intrinsicW = 854;
    $intrinsicH = 245;

    if ($slot === 'footer') {
        $filename = !empty($site_settings['logo_dark'])
            ? (string) $site_settings['logo_dark']
            : (string) ($site_settings['logo'] ?? 'logo.png');
        $fsPath = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/uploads/settings/' . $filename;
    } elseif (!empty($site_settings['logo_light'])) {
        $fsPath = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/uploads/settings/' . (string) $site_settings['logo_light'];
    } else {
        $fsPath = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/assets/img/logo-light.png';
    }

    if (is_file($fsPath)) {
        $size = @getimagesize($fsPath);
        if (is_array($size) && ($size[0] ?? 0) > 0 && ($size[1] ?? 0) > 0) {
            $intrinsicW = (int) $size[0];
            $intrinsicH = (int) $size[1];
        }
    }

    $displayHeight = (int) max(1, round($displayWidth * $intrinsicH / $intrinsicW));

    return 'width="' . $displayWidth . '" height="' . $displayHeight . '"';
}

/**
 * Slide türev yolu (örn. slides/foo-mob.webp).
 */
function mynak_slide_variant_path(string $dir, string $basename, string $type = 'mob'): string
{
    $dir = rtrim(str_replace('\\', '/', $dir), '/') . '/';
    if ($type === 'mob') {
        return $dir . $basename . '-mob.webp';
    }

    return $dir . $basename . '.' . $type;
}

/**
 * Slide dosyası uploads altında var mı (seo_upload_file_exists + yerel fallback).
 */
function mynak_slide_upload_relative_exists(string $dir, string $filename): bool
{
    $rel = rtrim(str_replace('\\', '/', $dir), '/') . '/' . ltrim(str_replace('\\', '/', $filename), '/');
    if (function_exists('seo_upload_file_exists') && seo_upload_file_exists($rel)) {
        return true;
    }
    $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__);

    return is_file($root . '/uploads/' . ltrim($rel, '/'));
}

/** @param 'mob'|string $type */
function mynak_slide_variant_exists(string $dir, string $basename, string $type = 'mob'): bool
{
    return mynak_slide_upload_relative_exists($dir, pathinfo(mynak_slide_variant_path($dir, $basename, $type), PATHINFO_BASENAME));
}

function mynak_slide_upload_public_path(string $dir, string $filename): string
{
    return 'uploads/' . trim(str_replace('\\', '/', $dir), '/') . '/' . ltrim(str_replace('\\', '/', $filename), '/');
}

function mynak_slide_upload_url(string $dir, string $filename): string
{
    $rel = rtrim(str_replace('\\', '/', $dir), '/') . '/' . ltrim(str_replace('\\', '/', $filename), '/');
    if (function_exists('seo_upload_url')) {
        return seo_upload_url($rel);
    }

    $path = 'uploads/' . trim(str_replace('\\', '/', $dir), '/') . '/' . ltrim(str_replace('\\', '/', $filename), '/');

    return rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/') . '/' . $path;
}

function mynak_slide_detect_upload_dir(string $original): string
{
    if (function_exists('seo_upload_file_exists') && seo_upload_file_exists('slides/' . $original)) {
        return 'slides/';
    }
    if (function_exists('seo_upload_file_exists') && seo_upload_file_exists('blog/' . $original)) {
        return 'blog/';
    }
    $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__);
    if (is_file($root . '/uploads/slides/' . $original)) {
        return 'slides/';
    }
    if (is_file($root . '/uploads/blog/' . $original)) {
        return 'blog/';
    }

    return 'slides/';
}

function mynak_slide_mime_from_filename(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'gif' => 'image/gif',
    ];

    return $map[$ext] ?? 'image/jpeg';
}

/**
 * Hero LCP: mobil + masaüstü preload linkleri (media senkron).
 */
function mynak_slide_lcp_preload_links(string $slide_dir, string $basename, string $original): string
{
    $links = [];

    $mobileHref = '';
    $mobileType = 'image/webp';
    if (mynak_slide_variant_exists($slide_dir, $basename, 'mob')) {
        $mobileHref = mynak_slide_upload_url($slide_dir, $basename . '-mob.webp');
    } elseif (mynak_slide_upload_relative_exists($slide_dir, $basename . '.webp')) {
        $mobileHref = mynak_slide_upload_url($slide_dir, $basename . '.webp');
    } elseif (mynak_slide_upload_relative_exists($slide_dir, $original)) {
        $mobileHref = mynak_slide_upload_url($slide_dir, $original);
        $mobileType = mynak_slide_mime_from_filename($original);
    }
    if ($mobileHref !== '') {
        $links[] = '<link rel="preload" as="image" fetchpriority="high" media="(max-width: 768px)" type="'
            . htmlspecialchars($mobileType, ENT_QUOTES, 'UTF-8') . '" href="'
            . htmlspecialchars($mobileHref, ENT_QUOTES, 'UTF-8') . '">';
    }

    $desktopHref = '';
    $desktopType = '';
    if (mynak_slide_upload_relative_exists($slide_dir, $basename . '.avif')) {
        $desktopHref = mynak_slide_upload_url($slide_dir, $basename . '.avif');
        $desktopType = 'image/avif';
    } elseif (mynak_slide_upload_relative_exists($slide_dir, $basename . '.webp')) {
        $desktopHref = mynak_slide_upload_url($slide_dir, $basename . '.webp');
        $desktopType = 'image/webp';
    } elseif (mynak_slide_upload_relative_exists($slide_dir, $original)) {
        $desktopHref = mynak_slide_upload_url($slide_dir, $original);
        $desktopType = mynak_slide_mime_from_filename($original);
    }
    if ($desktopHref !== '') {
        $typeAttr = $desktopType !== ''
            ? ' type="' . htmlspecialchars($desktopType, ENT_QUOTES, 'UTF-8') . '"'
            : '';
        $links[] = '<link rel="preload" as="image" fetchpriority="high" media="(min-width: 769px)"'
            . $typeAttr . ' href="' . htmlspecialchars($desktopHref, ENT_QUOTES, 'UTF-8') . '">';
    }

    return $links !== [] ? implode("\n    ", $links) : '';
}

/**
 * Hero <picture> kaynakları — preload ile aynı media/breakpoint mantığı.
 */
function mynak_slide_hero_picture_sources_html(string $slide_dir, string $basename): string
{
    $out = '';
    if (mynak_slide_variant_exists($slide_dir, $basename, 'mob')) {
        $out .= '<source media="(max-width: 768px)" srcset="'
            . htmlspecialchars(mynak_slide_upload_public_path($slide_dir, $basename . '-mob.webp'), ENT_QUOTES, 'UTF-8')
            . '" type="image/webp">' . "\n                                ";
    }
    if (mynak_slide_upload_relative_exists($slide_dir, $basename . '.avif')) {
        $out .= '<source media="(min-width: 769px)" srcset="'
            . htmlspecialchars(mynak_slide_upload_public_path($slide_dir, $basename . '.avif'), ENT_QUOTES, 'UTF-8')
            . '" type="image/avif">' . "\n                                ";
    }
    if (mynak_slide_upload_relative_exists($slide_dir, $basename . '.webp')) {
        $out .= '<source media="(min-width: 769px)" srcset="'
            . htmlspecialchars(mynak_slide_upload_public_path($slide_dir, $basename . '.webp'), ENT_QUOTES, 'UTF-8')
            . '" type="image/webp">' . "\n                                ";
    }

    return $out;
}

function mynak_slide_img_fallback_public_path(string $slide_dir, string $basename, string $original): string
{
    if (mynak_slide_upload_relative_exists($slide_dir, $basename . '.webp')) {
        return mynak_slide_upload_public_path($slide_dir, $basename . '.webp');
    }

    return mynak_slide_upload_public_path($slide_dir, $original);
}

/** Slide türev dosyalarını sil (.webp, .avif, -mob.webp). */
function mynak_slide_delete_derivatives(string $upload_dir, string $image_filename): void
{
    $base = pathinfo($image_filename, PATHINFO_FILENAME);
    foreach ([$base . '.webp', $base . '.avif', $base . '-mob.webp'] as $name) {
        $path = rtrim(str_replace('\\', '/', $upload_dir), '/') . '/' . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * @param resource|\GdImage $img_resource
 */
function mynak_slide_generate_mob_webp($img_resource, string $upload_dir, string $base_filename, int $maxWidth = 768, int $quality = 81): bool
{
    if (!function_exists('imagewebp') || (!is_object($img_resource) && !is_resource($img_resource))) {
        return false;
    }

    $w = imagesx($img_resource);
    $h = imagesy($img_resource);
    if ($w <= 0 || $h <= 0) {
        return false;
    }

    $target = $img_resource;
    $scaled = null;
    if ($w > $maxWidth) {
        $newH = (int) max(1, round($h * ($maxWidth / $w)));
        if (function_exists('imagescale')) {
            $scaled = @imagescale($img_resource, $maxWidth, $newH, IMG_BILINEAR_FIXED);
        }
        if ($scaled === false || $scaled === null) {
            $scaled = imagecreatetruecolor($maxWidth, $newH);
            if ($scaled !== false) {
                imagecopyresampled($scaled, $img_resource, 0, 0, 0, 0, $maxWidth, $newH, $w, $h);
            }
        }
        if ($scaled !== false && $scaled !== null) {
            $target = $scaled;
        }
    }

    $mobPath = rtrim(str_replace('\\', '/', $upload_dir), '/') . '/' . $base_filename . '-mob.webp';
    $ok = imagewebp($target, $mobPath, $quality);
    if ($scaled !== null && $scaled !== false && $scaled !== $img_resource) {
        imagedestroy($scaled);
    }

    return $ok;
}

/**
 * Tek slide görseli için {basename}-mob.webp üretir (yoksa veya $force).
 *
 * @return array{ok: bool, skipped: bool, path: string, message: string, bytes: int}
 */
function mynak_slide_ensure_mob_webp(string $slides_subdir, string $image_filename, bool $force = false): array
{
    $image_filename = ltrim(str_replace('\\', '/', $image_filename), '/');
    if ($image_filename === '' || str_contains($image_filename, '..')) {
        return ['ok' => false, 'skipped' => false, 'path' => '', 'message' => 'Geçersiz dosya adı', 'bytes' => 0];
    }

    $slides_subdir = rtrim(str_replace('\\', '/', $slides_subdir), '/') . '/';
    $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__);
    $upload_dir = $root . '/uploads/' . ltrim($slides_subdir, '/');
    $original_path = $upload_dir . $image_filename;

    if (!is_file($original_path)) {
        return [
            'ok' => false,
            'skipped' => false,
            'path' => '',
            'message' => 'Orijinal bulunamadı: uploads/' . $slides_subdir . $image_filename,
            'bytes' => 0,
        ];
    }

    $base = pathinfo($image_filename, PATHINFO_FILENAME);
    $mob_path = $upload_dir . $base . '-mob.webp';

    if (!$force && is_file($mob_path) && (int) filesize($mob_path) > 0) {
        return [
            'ok' => true,
            'skipped' => true,
            'path' => $mob_path,
            'message' => 'Zaten mevcut',
            'bytes' => (int) filesize($mob_path),
        ];
    }

    $img = mynak_slide_load_gd_resource($original_path, pathinfo($image_filename, PATHINFO_EXTENSION));
    if ($img === null) {
        return [
            'ok' => false,
            'skipped' => false,
            'path' => '',
            'message' => 'GD kaynak açılamadı (format/Imagick yok olabilir)',
            'bytes' => 0,
        ];
    }

    $ok = mynak_slide_generate_mob_webp($img, $upload_dir, $base);
    imagedestroy($img);

    if (!$ok || !is_file($mob_path)) {
        return [
            'ok' => false,
            'skipped' => false,
            'path' => '',
            'message' => 'mob.webp yazılamadı (GD imagewebp kapalı olabilir)',
            'bytes' => 0,
        ];
    }

    return [
        'ok' => true,
        'skipped' => false,
        'path' => $mob_path,
        'message' => 'Oluşturuldu',
        'bytes' => (int) filesize($mob_path),
    ];
}

/**
 * Tüm slide kayıtları için -mob.webp toplu üretim.
 *
 * @return array{stats: array{created: int, skipped: int, failed: int}, results: list<array<string, mixed>>, error: string}
 */
function mynak_slide_batch_generate_mob_webp(mysqli $conn, bool $force = false): array
{
    $results = [];
    $stats = ['created' => 0, 'skipped' => 0, 'failed' => 0];

    $res = $conn->query('SELECT id, title, image FROM slides WHERE image IS NOT NULL AND image != "" ORDER BY order_number ASC');
    if (!$res) {
        return ['stats' => $stats, 'results' => [], 'error' => (string) $conn->error];
    }

    while ($row = $res->fetch_assoc()) {
        $image = trim((string) ($row['image'] ?? ''));
        if ($image === '' || str_contains($image, '/') || str_contains($image, '\\')) {
            continue;
        }

        $dir = mynak_slide_detect_upload_dir($image);
        $r = mynak_slide_ensure_mob_webp($dir, $image, $force);
        $r['slide_id'] = (int) ($row['id'] ?? 0);
        $r['title'] = (string) ($row['title'] ?? '');
        $r['image'] = $image;
        $r['mob_name'] = pathinfo($image, PATHINFO_FILENAME) . '-mob.webp';
        $results[] = $r;

        if ($r['ok'] && !empty($r['skipped'])) {
            $stats['skipped']++;
        } elseif ($r['ok']) {
            $stats['created']++;
        } else {
            $stats['failed']++;
        }
    }
    $res->free();

    return ['stats' => $stats, 'results' => $results, 'error' => ''];
}

/**
 * @return resource|\GdImage|null
 */
function mynak_slide_load_gd_resource(string $path, string $ext)
{
    switch (strtolower($ext)) {
        case 'png':
            return @imagecreatefrompng($path) ?: null;
        case 'jpg':
        case 'jpeg':
            return @imagecreatefromjpeg($path) ?: null;
        case 'webp':
            return function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null;
        case 'avif':
            return function_exists('imagecreatefromavif') ? (@imagecreatefromavif($path) ?: null) : null;
        default:
            return null;
    }
}

/**
 * Şablonda <img> için hazır alt veya dekoratif öznitelik dizisi döndürür.
 */
function mynak_img_alt_attr(string $candidate, string $fallback, ?string $src = null, ?string $class = null): string
{
    if ($src !== null && mynak_is_decorative_image_src($src, $class)) {
        return mynak_decorative_img_attrs();
    }

    return 'alt="' . htmlspecialchars(mynak_public_image_alt($candidate, $fallback, $src), ENT_QUOTES, 'UTF-8') . '"';
}

/**
 * HTML içeriğindeki tüm <img> alt niteliklerini normalize eder (blog/hizmet gövdesi, bloklar).
 */
function mynak_normalize_html_img_alts(string $html, ?string $fallback = null): string
{
    if ($html === '' || stripos($html, '<img') === false) {
        return $html;
    }
    $fallback = $fallback ?? mynak_img_alt_context_fallback();

    return (string) preg_replace_callback('/<img\b([^>]*)>/iu', static function (array $m) use ($fallback): string {
        $attrs = (string) ($m[1] ?? '');
        if (!preg_match('/\bsrc=(["\'])(.*?)\1/is', $attrs, $sm)) {
            return $m[0];
        }
        $src = (string) ($sm[2] ?? '');
        $class = '';
        if (preg_match('/\bclass=(["\'])(.*?)\1/is', $attrs, $cm)) {
            $class = (string) ($cm[2] ?? '');
        }
        $existingAlt = '';
        if (preg_match('/\balt=(["\'])(.*?)\1/is', $attrs, $am)) {
            $existingAlt = (string) ($am[2] ?? '');
        }

        if (mynak_is_decorative_image_src($src, $class)) {
            $attrs = preg_replace('/\balt=(["\']).*?\1/is', '', $attrs);
            $attrs = preg_replace('/\brole=(["\']).*?\1/is', '', $attrs);
            $attrs = preg_replace('/\baria-hidden=(["\']).*?\1/is', '', $attrs);

            return '<img' . $attrs . ' ' . mynak_decorative_img_attrs() . '>';
        }

        $newAlt = htmlspecialchars(mynak_public_image_alt($existingAlt, $fallback, $src), ENT_QUOTES, 'UTF-8');
        if (preg_match('/\balt=(["\']).*?\1/is', $attrs)) {
            $attrs = preg_replace('/\balt=(["\']).*?\1/is', 'alt="' . $newAlt . '"', $attrs);
        } else {
            $attrs .= ' alt="' . $newAlt . '"';
        }

        return '<img' . $attrs . '>';
    }, $html);
}

/**
 * HTML gövdesindeki <img> etiketlerine LCP-aware lazy loading uygular (mynak_blok_isle son adım).
 * İlk görsel, mevcut loading/eager/fetchpriority=high ve dekoratif img'ler muaf tutulur.
 */
function mynak_normalize_html_img_loading(string $html): string
{
    if ($html === '' || stripos($html, '<img') === false) {
        return $html;
    }

    $imgIndex = 0;

    return (string) preg_replace_callback('/<img\b([^>]*)>/iu', static function (array $m) use (&$imgIndex): string {
        $isFirst = ($imgIndex === 0);
        $imgIndex++;

        $attrs = (string) ($m[1] ?? '');

        if ($isFirst) {
            return $m[0];
        }

        if (preg_match('/\bloading\s*=/i', $attrs)) {
            return $m[0];
        }

        if (preg_match('/\bfetchpriority\s*=\s*(["\'])high\1/i', $attrs)
            || preg_match('/\bloading\s*=\s*(["\'])eager\1/i', $attrs)) {
            return $m[0];
        }

        $src = '';
        $class = '';
        if (preg_match('/\bsrc=(["\'])(.*?)\1/is', $attrs, $sm)) {
            $src = (string) ($sm[2] ?? '');
        }
        if (preg_match('/\bclass=(["\'])(.*?)\1/is', $attrs, $cm)) {
            $class = (string) ($cm[2] ?? '');
        }

        if ($src !== '' && mynak_is_decorative_image_src($src, $class)) {
            return $m[0];
        }

        if (!preg_match('/\bdecoding\s*=/i', $attrs)) {
            $attrs .= ' decoding="async"';
        }
        $attrs .= ' loading="lazy"';

        return '<img' . $attrs . '>';
    }, $html);
}

/**
 * Blog kapak fotoğrafı URL'sini döndürür.
 * Medya kütüphanesinden seçilenler 'media/' prefix ile saklanır.
 * @param string $kapak_foto  DB'deki kapak_foto değeri
 * @param string $prefix      Göreli yol için ön ek (örn. '../' veya '')
 * @return string
 */
function blog_kapak_url($kapak_foto, $prefix = '') {
    if (empty($kapak_foto)) return '';
    // media/ ile başlıyorsa → uploads/media/filename.jpg
    if (strpos($kapak_foto, 'media/') === 0) {
        return $prefix . 'uploads/' . $kapak_foto;
    }
    // Eski kayıtlar → uploads/blog/filename.jpg
    return $prefix . 'uploads/blog/' . $kapak_foto;
}

/**
 * Blog kapak fotoğrafı tam SITE_URL ile döndürür (canonical, OG image vb.)
 * Dosya yoksa veya boşsa yerel placeholder (assets/images/default.webp).
 */
function blog_kapak_full_url($kapak_foto) {
    $placeholder = function_exists('seo_default_placeholder_image_url')
        ? seo_default_placeholder_image_url()
        : ((defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/assets/images/default.webp');
    $kapak_foto = trim((string) $kapak_foto);
    if ($kapak_foto === '') {
        return $placeholder;
    }
    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    $rel = strpos($kapak_foto, 'media/') === 0 ? $kapak_foto : ('blog/' . $kapak_foto);
    if (function_exists('seo_upload_file_exists') && !seo_upload_file_exists($rel)) {
        return $placeholder;
    }

    return $base . '/uploads/' . $rel;
}

/**
 * Render-blocking olmayan CSS: preload + onload ile stylesheet uygulanır (Lighthouse FCP).
 */
function mynak_link_stylesheet_deferred($href)
{
    $esc = htmlspecialchars((string) $href, ENT_QUOTES, 'UTF-8');

    return '<link rel="preload" href="' . $esc . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n"
        . '<noscript><link rel="stylesheet" href="' . $esc . '"></noscript>' . "\n";
}

// Kısa metin oluşturma (özet için)
function metin_kisalt($text, $limit = 150) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    
    $text = substr($text, 0, $limit);
    $text = substr($text, 0, strrpos($text, ' '));
    return $text . '...';
}

// SEO dostu URL slug oluşturma
function slug_olustur($text) {
    if ($text === null || $text === '') {
        return 'n-a';
    }
    $text = (string) $text;
    // Türkçe karakterleri değiştir
    $turkce_karakterler = array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç');
    $ingilizce_karakterler = array('i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c');
    $text = str_replace($turkce_karakterler, $ingilizce_karakterler, $text);
    // Bozuk UTF-8 / iconv hatası → PHP 8'de strtolower(false) 5xx üretmesin
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($converted !== false && $converted !== '') {
        $text = strtolower($converted);
    } else {
        $text = mb_strtolower($text, 'UTF-8');
    }
    // Sadece a-z, 0-9 ve tire kalsın (Unicode harfleri de temizle)
    $text = preg_replace('/[^a-z0-9-]+/u', '-', $text);
    // Birden fazla tireyi teke indir
    $text = preg_replace('/-+/', '-', $text);
    // Baştaki ve sondaki tireleri sil
    $text = trim($text, '-');
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

/**
 * İstek URL’sinde (QUERY_STRING) page= / tag= / kategori= var mı?
 * Rewrite ile blog.php’ye enjekte edilen $_GET anahtarlarını hariç tutar; yalnızca gerçek sorgu dizesine bakar.
 */
function mynak_blog_query_string_has_seo_param_keys(): bool
{
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($qs === '') {
        return false;
    }
    $q = [];
    parse_str($qs, $q);
    if (isset($q['page']) && trim((string) $q['page']) !== '' && (int) $q['page'] >= 1) {
        return true;
    }
    if (isset($q['tag']) && trim((string) $q['tag']) !== '') {
        return true;
    }
    if (isset($q['kategori']) && trim((string) $q['kategori']) !== '') {
        return true;
    }
    return false;
}

/**
 * Slug ile tam eşleşen ilk etiket metnini döndürür; yoksa null.
 * Prepared LIMIT/OFFSET partileri + üst satır sınırı; ilk eşleşmede durur (ek yük yok).
 *
 * @param mysqli $conn
 * @param mixed $slug
 * @return string|null
 */
function mynak_find_tag_by_slug($conn, $slug)
{
    static $cache = [];
    $slug = trim((string) $slug);
    if ($slug === '' || !($conn instanceof mysqli)) {
        return null;
    }
    if (array_key_exists($slug, $cache)) {
        return $cache[$slug];
    }
    $batch = 250;
    $maxRows = 25000;
    $offset = 0;
    $sql = "SELECT etiketler FROM blog_posts WHERE durum = 3 AND etiketler IS NOT NULL AND etiketler != '' LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        while ($offset < $maxRows) {
            $lim = $batch;
            $off = $offset;
            if (!mysqli_stmt_bind_params_safe($stmt, 'ii', [$lim, $off])) {
                break;
            }
            if (!$stmt->execute()) {
                break;
            }
            $rows = mysqli_stmt_fetch_all_assoc($stmt);
            if ($rows === []) {
                break;
            }
            foreach ($rows as $row) {
                $raw = isset($row['etiketler']) ? (string) $row['etiketler'] : '';
                if ($raw === '') {
                    continue;
                }
                foreach (explode(',', $raw) as $tag) {
                    $tag = trim($tag);
                    if ($tag !== '' && slug_olustur($tag) === $slug) {
                        $stmt->close();
                        $cache[$slug] = $tag;
                        return $tag;
                    }
                }
            }
            if (count($rows) < $batch) {
                break;
            }
            $offset += $batch;
        }
        $stmt->close();
    } else {
        while ($offset < $maxRows) {
            $lim = (int) $batch;
            $off = (int) $offset;
            $sqlFallback = 'SELECT etiketler FROM blog_posts WHERE durum = 3 AND etiketler IS NOT NULL AND etiketler != \'\' LIMIT '
                . $lim . ' OFFSET ' . $off;
            $tags_result = $conn->query($sqlFallback);
            if (!$tags_result) {
                break;
            }
            if ($tags_result->num_rows === 0) {
                $tags_result->free();
                break;
            }
            while ($row = $tags_result->fetch_assoc()) {
                foreach (explode(',', (string) ($row['etiketler'] ?? '')) as $tag) {
                    $tag = trim($tag);
                    if ($tag !== '' && slug_olustur($tag) === $slug) {
                        $tags_result->free();
                        $cache[$slug] = $tag;
                        return $tag;
                    }
                }
            }
            $tags_result->free();
            $offset += $batch;
        }
    }
    $cache[$slug] = null;
    return null;
}

/**
 * mysqli_stmt SELECT sonucunu dizi olarak döndürür.
 * mysqlnd yoksa get_result() tanımsız/false olur; blog ve diğer sayfalarda 500 önlenir.
 */
function mysqli_stmt_fetch_all_assoc(mysqli_stmt $stmt) {
    $rows = [];
    if (!($stmt instanceof mysqli_stmt)) {
        return $rows;
    }
    if (function_exists('mysqli_stmt_get_result')) {
        $result = @mysqli_stmt_get_result($stmt);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        return $rows;
    }
    if (!@$stmt->store_result()) {
        return $rows;
    }
    $meta = $stmt->result_metadata();
    if (!$meta) {
        return $rows;
    }
    $fields = [];
    $row = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = $field->name;
        $bind[] = &$row[$field->name];
    }
    $meta->free();
    if ($fields === [] || !call_user_func_array([$stmt, 'bind_result'], $bind)) {
        return $rows;
    }
    while ($stmt->fetch()) {
        $copy = [];
        foreach ($fields as $f) {
            $copy[$f] = $row[$f];
        }
        $rows[] = $copy;
    }
    return $rows;
}

/** Tek satırlık SELECT (LIMIT 1); mysqlnd gerektirmez. PHP 7.0 uyumlu (dönüş tipi yok). */
function mysqli_stmt_fetch_assoc_first(mysqli_stmt $stmt)
{
    $rows = mysqli_stmt_fetch_all_assoc($stmt);
    return isset($rows[0]) ? $rows[0] : null;
}

/**
 * mysqli_stmt::bind_param için referans güvenli bağlama.
 * PHP 7.x’te bind_param('ss', ...$dizi) “cannot pass by reference” fatal verir; slug-router’dan gelen
 * filtreli blog (/blog/etiket/, /blog/kategori/) boş params olmayan sorgularda 500 üretirdi.
 */
function mysqli_stmt_bind_params_safe(mysqli_stmt $stmt, string $types, array $params): bool
{
    if ($types === '' && $params === []) {
        return true;
    }
    if (strlen($types) !== count($params)) {
        return false;
    }
    $bind = [$types];
    foreach (array_keys($params) as $k) {
        $bind[] = &$params[$k];
    }
    return (bool) call_user_func_array([$stmt, 'bind_param'], $bind);
}

/**
 * /blog?kategori=ID → /blog/kategori/{slug}/ (Semrush 301 zinciri).
 */
function mynak_rewrite_blog_kategori_in_url($url)
{
    if (!is_string($url) || $url === '' || !defined('SITE_URL')) {
        return $url;
    }
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return $url;
    }
    $parts = parse_url(trim($url));
    if ($parts === false || empty($parts['query'])) {
        return $url;
    }
    parse_str($parts['query'], $q);
    if (empty($q['kategori']) || (int) $q['kategori'] <= 0) {
        return $url;
    }
    $path = isset($parts['path']) ? '/' . trim((string) $parts['path'], '/') : '/';
    if ($path === '/') {
        $path = '/';
    }
    if (function_exists('mynak_site_path_from_request_path')) {
        $path = mynak_site_path_from_request_path($path);
    }
    $lower = strtolower($path);
    if ($lower !== '/blog' && $lower !== '/blog.php') {
        return $url;
    }
    $kid = (int) $q['kategori'];
    $stmt = $conn->prepare('SELECT slug FROM blog_categories WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return $url;
    }
    $stmt->bind_param('i', $kid);
    $stmt->execute();
    $rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();
    if (empty($rows) || empty($rows[0]['slug'])) {
        return $url;
    }
    $slug = (string) $rows[0]['slug'];
    unset($q['kategori']);
    $qs = '';
    if ($q !== []) {
        $qs = '?' . http_build_query($q);
    }
    $frag = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
    $pathOut = '/blog/kategori/' . rawurlencode($slug) . '/';

    if (!empty($parts['scheme']) && !empty($parts['host'])) {
        $host = strtolower((string) $parts['host']);
        if (preg_match('/(^|\.)mynakliyat\.com\.tr$/i', $host)) {
            return rtrim(SITE_URL, '/') . $pathOut . $qs . $frag;
        }

        return $url;
    }

    return $pathOut . $qs . $frag;
}

/**
 * İçerik HTML’inde href değerlerini normalize eder (DB’deki eski apex / teklif-al / blog?kategori=).
 */
function mynak_normalize_html_href_attributes($html)
{
    if ($html === null || $html === '' || !function_exists('normalize_internal_link_url')) {
        return $html;
    }
    return preg_replace_callback('#\bhref\s*=\s*("|\')((?:(?!\1).)*)\1#iu', function ($m) {
        $q = $m[1];
        $raw = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('#^(mailto:|tel:|javascript:)#i', $raw)) {
            return $m[0];
        }
        $new = normalize_internal_link_url($raw);

        return 'href=' . $q . htmlspecialchars($new, ENT_QUOTES, 'UTF-8') . $q;
    }, $html);
}

/**
 * Menü/footer iç linklerde 301 zincirini azaltır (Semrush "permanent redirects"):
 * mynakliyat.com.tr → SITE_URL (www), galeri.php/blog.php → uzantısız, teklif-al → teklif-alin.
 */
function normalize_internal_link_url($url)
{
    if (!defined('SITE_URL')) {
        return $url;
    }
    $url = trim((string) $url);
    if ($url === '') {
        return $url;
    }
    if ($url[0] === '#') {
        return $url;
    }
    if (preg_match('#^(mailto:|tel:|javascript:)#i', $url)) {
        return $url;
    }
    $base = rtrim(SITE_URL, '/');
    if (preg_match('#^https?://(www\.)?mynakliyat\.com\.tr#i', $url)) {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }
        $path = $parts['path'] ?? '/';
        $qs = isset($parts['query']) ? '?' . $parts['query'] : '';
        $frag = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        $out = mynak_rewrite_blog_kategori_in_url($base . internal_canonical_site_path($path) . $qs . $frag);
        if (function_exists('mynak_web_url_from_site_path')) {
            return mynak_web_url_from_site_path($out);
        }
        return $out;
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    $frag = '';
    if (strpos($url, '#') !== false) {
        $p = explode('#', $url, 2);
        $url = $p[0];
        $frag = '#' . $p[1];
    }
    $qs = '';
    if (strpos($url, '?') !== false) {
        $p = explode('?', $url, 2);
        $url = $p[0];
        $qs = '?' . $p[1];
    }
    $path = $url;
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    $canon = internal_canonical_site_path($path) . $qs . $frag;
    $out = mynak_rewrite_blog_kategori_in_url($canon);
    if (function_exists('mynak_web_url_from_site_path')) {
        return mynak_web_url_from_site_path($out);
    }
    return $out;
}

/**
 * Şablonda sayfa başlığı için tek <h1> (banner) kullanılırken içerik/bloklardaki <h1> → <h2> (çoklu H1 SEO).
 * script/style blokları geçici çıkarılır; içlerindeki &lt;h1&gt; string'leri bozulmaz.
 */
function demote_inline_h1_to_h2($html)
{
    if ($html === null || $html === '') {
        return $html;
    }
    $placeholders = [];
    $i = 0;
    $html = preg_replace_callback('#<script\b[^>]*>.*?</script>#is', function ($m) use (&$placeholders, &$i) {
        $key = '<!--__H1DEM_' . $i++ . '__-->';
        $placeholders[$key] = $m[0];
        return $key;
    }, $html);
    $html = preg_replace_callback('#<style\b[^>]*>.*?</style>#is', function ($m) use (&$placeholders, &$i) {
        $key = '<!--__H1DEM_' . $i++ . '__-->';
        $placeholders[$key] = $m[0];
        return $key;
    }, $html);
    $html = preg_replace('/<h1(\s[^>]*)?>/iu', '<h2$1>', $html);
    $html = preg_replace('/<\/h1>/iu', '</h2>', $html);
    if ($placeholders !== []) {
        $html = str_replace(array_keys($placeholders), array_values($placeholders), $html);
    }
    return $html;
}

function internal_canonical_site_path($path)
{
    if ($path === '' || $path === '/') {
        return '/';
    }
    $path = '/' . trim($path, '/');
    if (stripos($path, '/admin') === 0 || preg_match('#^/(ajax|uploads|assets|config|includes)(/|$)#i', $path)) {
        return $path;
    }
    $lower = strtolower($path);
    static $exact = [
        '/galeri.php' => '/galeri',
        '/blog.php' => '/blog',
        '/index.php' => '/',
        '/iletisim.php' => '/iletisim',
        '/teklif-al' => '/teklif-alin',
    ];
    if (isset($exact[$lower])) {
        return $exact[$lower];
    }
    if ($lower === '/teklif-al/') {
        return '/teklif-alin';
    }
    if (preg_match('#^/([a-z0-9\-]+)\.php$#i', $path, $m)) {
        return '/' . $m[1];
    }
    // Tek segment + sonda / → slash'siz (.htaccess 301; örn. /sayfa/ → /sayfa)
    if (preg_match('#^/([a-z0-9\-]+)/$#i', $path, $m)) {
        $keepSlash = ['admin', 'uploads', 'assets', 'ajax', 'cache', 'wp-content', 'scripts', 'config', 'includes', 'logs', 'cron'];
        if (!in_array(strtolower($m[1]), $keepSlash, true)) {
            return '/' . $m[1];
        }
    }

    return $path;
}

/**
 * Footer / yan menü için kısa link etiketi: SEO başlığındaki "|" sonrasını atar; $truncate false ise kısaltma yok (CSS line-clamp ile).
 */
function footer_short_link_label($title, $slug = '', $maxLen = 52, bool $truncate = true)
{
    $maxLen = max(20, (int) $maxLen);
    $title = trim((string) $title);
    if ($title !== '' && strpos($title, '|') !== false) {
        $title = trim(explode('|', $title, 2)[0]);
    }
    $title = preg_replace('/\s+/u', ' ', $title);
    if ($title === '' && $slug !== '') {
        $title = mb_convert_case(str_replace(['-', '_'], ' ', $slug), MB_CASE_TITLE, 'UTF-8');
    }
    if ($truncate) {
        if (function_exists('mb_strlen') && mb_strlen($title, 'UTF-8') > $maxLen) {
            $cut = max(1, $maxLen - 1);
            $title = mb_substr($title, 0, $cut, 'UTF-8') . '…';
        } elseif ($title !== '' && strlen($title) > $maxLen) {
            $title = substr($title, 0, max(1, $maxLen - 1)) . '…';
        }
    }

    return $title;
}

/** Footer: telefon metninden tel:+90… URI (0850 / 0xx uyumlu) */
function footer_tel_uri(string $phone): string
{
    $d = preg_replace('/\D+/', '', $phone);
    if ($d === '') {
        return '#';
    }
    if (str_starts_with($d, '90') && strlen($d) >= 12) {
        return 'tel:+' . $d;
    }
    if (str_starts_with($d, '0')) {
        return 'tel:+90' . substr($d, 1);
    }
    if (strlen($d) === 10) {
        return 'tel:+90' . $d;
    }

    return 'tel:+' . $d;
}

/**
 * Footer menü öğesi URL’sinden karşılaştırma anahtarı (tekrar linkleri elemek için).
 */
function footer_menu_link_path_key(string $url): string
{
    $n = normalize_internal_link_url($url);
    if ($n === '' || $n[0] === '#') {
        return '';
    }
    $base = rtrim((string) SITE_URL, '/');
    if (preg_match('#^https?://#i', $n)) {
        if (stripos($n, $base) !== 0) {
            return '';
        }
        $rest = substr($n, strlen($base)) ?: '/';
        $path = parse_url($rest, PHP_URL_PATH);
        if ($path === null || $path === '') {
            $path = strtok(ltrim($rest, '/'), '?#');
            $path = $path !== false && $path !== '' ? '/' . $path : '/';
        }
    } else {
        $path = strtok($n, '?#') ?: '/';
    }
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . ltrim((string) $path, '/');
    }

    return strtolower(rtrim(internal_canonical_site_path($path), '/')) ?: '/';
}

/** WhatsApp tıklama linki: 0… / 90… → wa.me/90… */
function footer_whatsapp_wa_uri(string $raw): string
{
    $d = preg_replace('/\D+/', '', $raw);
    if ($d === '') {
        return '#';
    }
    if (str_starts_with($d, '90') && strlen($d) >= 12) {
        return 'https://wa.me/' . $d;
    }
    if (str_starts_with($d, '0')) {
        return 'https://wa.me/90' . substr($d, 1);
    }
    if (strlen($d) === 10) {
        return 'https://wa.me/90' . $d;
    }

    return 'https://wa.me/' . $d;
}

/** Footer / liste için WhatsApp numarasını okunaklı göster (wa.me linki aynı kalır). */
function footer_whatsapp_display_label($raw)
{
    $raw = trim((string) $raw);
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === '' || strlen($digits) < 10) {
        return $raw !== '' ? $raw : $digits;
    }
    if (str_starts_with($digits, '90') && strlen($digits) >= 12) {
        $n = substr($digits, 2);
    } elseif (str_starts_with($digits, '0')) {
        $n = substr($digits, 1);
    } else {
        $n = $digits;
    }
    if (strlen($n) === 10) {
        return '+90 ' . sprintf('%s %s %s %s', substr($n, 0, 3), substr($n, 3, 3), substr($n, 6, 2), substr($n, 8, 2));
    }

    return $raw;
}

// Dosya uzantısı kontrolü
function dosya_uzantisi_kontrol($filename, $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_extensions);
}

// Tarih formatını değiştirme
function tarih_formatla($date, $format = 'd.m.Y') {
    return date($format, strtotime($date));
}

// Meta açıklaması oluşturma
function meta_aciklama_olustur($content, $limit = 160) {
    // HTML etiketlerini temizle
    $text = strip_tags($content);
    // Kısalt
    return metin_kisalt($text, $limit);
}

// Aktif menü sınıfını döndürme
function aktif_menu($current_page, $menu_link) {
    if ($current_page == $menu_link) {
        return 'active';
    }
    return '';
}

// Dosya boyutunu okunabilir formata dönüştürme
function dosya_boyutu_formatla($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// TinyMCE içeriğinde açık kalan HTML etiketlerini otomatik kapat
function html_etiketlerini_duzelt($content) {
    if (empty($content)) return $content;
    $content = mynak_normalize_rich_html((string) $content);
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // UTF-8 desteği için sarmalayıcı ekle
    $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $body = $doc->getElementsByTagName('div')->item(0);
    $new_content = '';
    if ($body) {
        foreach ($body->childNodes as $child) {
            $new_content .= $doc->saveHTML($child);
        }
    } else {
        $new_content = $doc->saveHTML();
    }
    libxml_clear_errors();
    return $new_content;
}

// TinyMCE içeriğindeki resimleri düzenleme
function icerik_isle($content) {
    // Debug log dosyası
    $log_file = __DIR__ . '/../logs/debug.log';
    file_put_contents($log_file, "icerik_isle çağrıldı\n", FILE_APPEND);
    
    // TinyMCE bazen göreceli URL kullanabilir, bunları tam URL'ye dönüştür
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    
    // src="/uploads/ ile başlayan etiketleri düzelt (başında slash varsa)
    $content = preg_replace('/(src=["\'])\/uploads\//i', '$1' . $base_url . '/uploads/', $content);
    
    // src="uploads/ ile başlayan etiketleri düzelt (başında slash yoksa)
    $content = preg_replace('/(src=["\'])uploads\//i', '$1' . $base_url . '/uploads/', $content);
    
    // srcset attribute: make each URL absolute
    $content = preg_replace_callback('/srcset=(["\'])([^"\']+)\1/i', function($matches) use ($base_url) {
        $quote = $matches[1];
        $srcset = $matches[2];
        $parts = array_map('trim', explode(',', $srcset));
        foreach ($parts as &$part) {
            // Each part like: url [descriptor]
            $segments = preg_split('/\s+/', $part, 2);
            $url = $segments[0];
            $descriptor = isset($segments[1]) ? $segments[1] : '';
            if (stripos($url, 'http://') !== 0 && stripos($url, 'https://') !== 0 && stripos($url, $base_url) !== 0) {
                if (substr($url, 0, 1) === '/') {
                    $url = $base_url . $url;
                } else {
                    $url = $base_url . '/' . $url;
                }
            }
            $part = trim($url . (empty($descriptor) ? '' : ' ' . $descriptor));
        }
        $new = implode(', ', $parts);
        return 'srcset=' . $quote . $new . $quote;
    }, $content);

    // href attributes that point to uploads: make absolute
    $content = preg_replace('/(href=["\'])\/?uploads\//i', '$1' . $base_url . '/uploads/', $content);

    // CSS inline url() that point to uploads: make absolute
    $content = preg_replace('/(url\()\/?uploads\//i', '$1' . $base_url . '/uploads/', $content);

    // Eğer src değeri zaten http:// veya https:// ile başlıyorsa, değiştirme yapma
    // Eğer değilse ve site URL'i ile başlamıyorsa, site URL'ini ekle
    $content = preg_replace_callback('/<img[^>]*src=(["\'])(?!https?:\/\/)(?!'. preg_quote($base_url, '/') . '\/)([^"\']+)(["\'])[^>]*>/i', 
        function($matches) use ($base_url) {
            $quote = $matches[1];
            $src = $matches[2];
            
            // Eğer src "/" ile başlıyorsa, base_url'e ekle
            if (substr($src, 0, 1) === '/') {
                return str_replace("src=$quote$src$quote", "src=$quote$base_url$src$quote", $matches[0]);
            } else {
                // Eğer göreli bir yolsa, base_url + "/" ekle
                return str_replace("src=$quote$src$quote", "src=$quote$base_url/$src$quote", $matches[0]);
            }
        }, 
        $content
    );
    
    $content = html_etiketlerini_duzelt($content);
    return $content;
}

// TinyMCE içeriğindeki base64 kodlu resimleri sunucuya yükleme
function base64_to_image($content, $type = 'content') {
    // Debug log dosyası
    $log_file = __DIR__ . '/../logs/debug.log';
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0777, true);
    }
    
    // Log başlat
    file_put_contents($log_file, "--- Base64 İşleme Başlatıldı: " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
    
    // İçerikte base64 var mı kontrol et
    $has_base64 = preg_match('/data:image\/([a-zA-Z]+);base64,/i', $content);
    file_put_contents($log_file, "İçerikte base64 bulundu mu?: " . ($has_base64 ? "Evet" : "Hayır") . "\n", FILE_APPEND);
    
    // Eğer içerikte base64 yoksa direkt döndür
    if (!$has_base64) {
        file_put_contents($log_file, "Base64 bulunamadı, orijinal içerik döndürülüyor.\n", FILE_APPEND);
        return $content;
    }
    
    // Base64 resimlerini bul ve değiştir
    $pattern = '/<img[^>]*src=[\'"](data:image\/([a-zA-Z]+);base64,([^\'"]+))[\'"][^>]*>/i';
    
    // Klasör yolu
    $date_folder = 'uploads/content/' . date('Y-m');
    $absolute_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $date_folder;
    
    // Log klasör bilgisi
    file_put_contents($log_file, "Klasör yolu: " . $date_folder . "\n", FILE_APPEND);
    file_put_contents($log_file, "Tam yol: " . $absolute_path . "\n", FILE_APPEND);
    file_put_contents($log_file, "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n", FILE_APPEND);
    
    // Klasör oluştur
    if (!file_exists($absolute_path)) {
        $create_result = mkdir($absolute_path, 0777, true);
        file_put_contents($log_file, "Klasör oluşturma sonucu: " . ($create_result ? "Başarılı" : "Başarısız") . "\n", FILE_APPEND);
        
        // Oluşturma başarısız olduysa, hata nedenini log'a ekle
        if (!$create_result) {
            file_put_contents($log_file, "Klasör oluşturma hatası: " . error_get_last()['message'] . "\n", FILE_APPEND);
            
            // Üst klasörleri kontrol et
            $parent_dir = dirname($absolute_path);
            file_put_contents($log_file, "Üst klasör: " . $parent_dir . " - Var mı?: " . (file_exists($parent_dir) ? "Evet" : "Hayır") . "\n", FILE_APPEND);
            file_put_contents($log_file, "Üst klasör yazılabilir mi?: " . (is_writable($parent_dir) ? "Evet" : "Hayır") . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents($log_file, "Klasör zaten mevcut\n", FILE_APPEND);
    }
    
    // Klasör yazılabilir mi kontrol et
    $is_writable = is_writable($absolute_path);
    file_put_contents($log_file, "Klasör yazılabilir mi?: " . ($is_writable ? "Evet" : "Hayır") . "\n", FILE_APPEND);
    
    if (!$is_writable) {
        // Klasör yazılabilir değilse izinleri düzeltmeyi dene
        $chmod_result = chmod($absolute_path, 0777);
        file_put_contents($log_file, "İzin değiştirme sonucu: " . ($chmod_result ? "Başarılı" : "Başarısız") . "\n", FILE_APPEND);
        
        // İzinleri tekrar kontrol et
        $is_writable = is_writable($absolute_path);
        file_put_contents($log_file, "İzin değiştirme sonrası klasör yazılabilir mi?: " . ($is_writable ? "Evet" : "Hayır") . "\n", FILE_APPEND);
    }
    
    // İçeriği işle ve base64 resimleri değiştir
    $processed_content = preg_replace_callback($pattern, function($matches) use ($date_folder, $type, $log_file, $absolute_path) {
        file_put_contents($log_file, "Bir base64 resmi bulundu.\n", FILE_APPEND);
        
        $base64_string = $matches[3];
        $image_type = $matches[2];
        
        // Base64 bilgisi log
        $base64_length = strlen($base64_string);
        file_put_contents($log_file, "Base64 uzunluğu: " . $base64_length . " karakter\n", FILE_APPEND);
        file_put_contents($log_file, "Resim tipi: " . $image_type . "\n", FILE_APPEND);
        
        // Geçerli uzantı kontrolü
        if (!in_array(strtolower($image_type), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            file_put_contents($log_file, "Geçersiz resim tipi, jpg olarak düzeltiliyor.\n", FILE_APPEND);
            $image_type = 'jpg'; // Varsayılan olarak JPG kullan
        }
        
        // Dosya adı oluştur
        $filename = uniqid($type . '_') . '.' . $image_type;
        $file_path = $date_folder . '/' . $filename;
        $absolute_file_path = $absolute_path . '/' . $filename;
        
        file_put_contents($log_file, "Oluşturulan dosya adı: " . $filename . "\n", FILE_APPEND);
        file_put_contents($log_file, "Tam dosya yolu: " . $absolute_file_path . "\n", FILE_APPEND);
        
        // Base64 kodunu çöz
        $image_data = base64_decode($base64_string);
        
        if ($image_data === false) {
            file_put_contents($log_file, "Base64 kodu çözülemedi!\n", FILE_APPEND);
            return $matches[0]; // Başarısız olursa orijinal etiketi döndür
        }
        
        // Dosyaya kaydet
        $save_result = file_put_contents($absolute_file_path, $image_data);
        
        if ($save_result === false) {
            file_put_contents($log_file, "Dosya kaydedilemedi! Hata: " . error_get_last()['message'] . "\n", FILE_APPEND);
            
            // Hata durumunda daha fazla bilgi topla
            $dir_perms = substr(sprintf('%o', fileperms($absolute_path)), -4);
            file_put_contents($log_file, "Klasör izinleri: " . $dir_perms . "\n", FILE_APPEND);
            file_put_contents($log_file, "PHP çalışma kullanıcısı: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Bilinmiyor') . "\n", FILE_APPEND);
            
            return $matches[0]; // Başarısız olursa orijinal etiketi döndür
        }
        
        file_put_contents($log_file, "Dosya başarıyla kaydedildi: " . $save_result . " byte\n", FILE_APPEND);
        
        // Base URL oluştur
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $new_url = $base_url . '/' . $file_path;
        
        file_put_contents($log_file, "Yeni resim URL'si: " . $new_url . "\n", FILE_APPEND);
        
        // Webp formatına dönüştürme dene
        if (in_array(strtolower($image_type), ['jpg', 'jpeg', 'png']) && function_exists('imagewebp')) {
            $webp_filename = str_replace('.' . $image_type, '.webp', $filename);
            $webp_path = $absolute_path . '/' . $webp_filename;
            
            try {
                if ($image_type == 'png') {
                    $img = @imagecreatefrompng($absolute_file_path);
                } else {
                    $img = @imagecreatefromjpeg($absolute_file_path);
                }
                
                if ($img) {
                    // Alfa kanalını koru (PNG için)
                    if ($image_type == 'png') {
                        imagepalettetotruecolor($img);
                        imagealphablending($img, true);
                        imagesavealpha($img, true);
                    }
                    
                    imagewebp($img, $webp_path, 80);
                    imagedestroy($img);
                    file_put_contents($log_file, "WebP versiyonu oluşturuldu: " . $webp_path . "\n", FILE_APPEND);
                } else {
                    file_put_contents($log_file, "WebP dönüşümü için resim okunamadı\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                file_put_contents($log_file, "WebP dönüşüm hatası: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
        
        // Orijinal img etiketindeki src değerini değiştir
        $original_tag = $matches[0];
        $new_tag = str_replace($matches[1], $new_url, $original_tag);
        
        file_put_contents($log_file, "Orijinal etiket: " . substr($original_tag, 0, 50) . "...\n", FILE_APPEND);
        file_put_contents($log_file, "Yeni etiket: " . substr($new_tag, 0, 50) . "...\n", FILE_APPEND);
        
        return $new_tag;
    }, $content);
    
    // Değişikliğin başarılı olup olmadığını kontrol et
    $success = ($processed_content !== $content);
    file_put_contents($log_file, "İçerik başarıyla işlendi mi?: " . ($success ? "Evet" : "Hayır") . "\n", FILE_APPEND);
    file_put_contents($log_file, "--- Base64 İşleme Tamamlandı ---\n\n", FILE_APPEND);
    
    return $processed_content;
}

// Resim URL'lerini doğrudan düzeltme - yeni fonksiyon
function resim_url_duzelt($url) {
    // Base URL'yi belirleyelim
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    
    // URL zaten tam URL ise değiştirme
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }
    
    // URL "/" ile başlıyorsa
    if (substr($url, 0, 1) === '/') {
        return $base_url . $url;
    }
    
    // Göreceli URL ise
    return $base_url . '/' . $url;
}

// TinyMCE içeriği dönüştürme - Tamamen yeniden düzenlenmiş sürüm
function icerik_donustur($content) {
    // Debug log dosyası
    $log_file = __DIR__ . '/../logs/debug.log';
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0777, true);
    }
    
    file_put_contents($log_file, "=== İçerik Dönüştürme Başlatıldı: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
    
    // İçerik uzunluğunu log'a ekle
    $content_length = strlen($content);
    file_put_contents($log_file, "İçerik uzunluğu: " . $content_length . " karakter\n", FILE_APPEND);
    
    // İçerik boşsa hemen dön
    if (empty($content)) {
        file_put_contents($log_file, "İçerik boş! Sonlandırılıyor.\n", FILE_APPEND);
        return '';
    }
    
    // Base URL (tam domain)
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    
    try {
        // DOM işleme için libxml hata raporlamasını devre dışı bırak
        $previous_value = libxml_use_internal_errors(true);
        
        // HTML içeriğini DOM'a yükle - özel sarmalama ile
        $dom = new DOMDocument('1.0', 'UTF-8');
        
        // HTML5 uyumluluğu için karakter kodlaması
        $wrapped_content = '<div>' . $content . '</div>';
        $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Tüm resim etiketlerini bul
        $images = $dom->getElementsByTagName('img');
        
        file_put_contents($log_file, "Toplam " . $images->length . " resim etiketi bulundu.\n", FILE_APPEND);
        
        // Resimleri işle
        $modified = false;
        foreach ($images as $img) {
            // 1. Adım: Base64 resimleri işle
            $src = $img->getAttribute('src');
            
            if (strpos($src, 'data:image/') === 0) {
                file_put_contents($log_file, "Base64 resmi bulundu, işleniyor...\n", FILE_APPEND);
                $new_src = process_base64_image($src, $log_file);
                if ($new_src !== $src) {
                    $img->setAttribute('src', $new_src);
                    $modified = true;
                }
            } 
            // 2. Adım: URL yollarını düzelt
            else {
                // URLs that don't start with http:// or https://
                if (strpos($src, 'http://') !== 0 && strpos($src, 'https://') !== 0) {
                    $fixed_src = resim_url_duzelt($src);
                    if ($fixed_src !== $src) {
                        file_put_contents($log_file, "Resim yolu düzeltildi: $src -> $fixed_src\n", FILE_APPEND);
                        $img->setAttribute('src', $fixed_src);
                        $modified = true;
                    }
                }
            }
            
            // Alt özniteliği yoksa ekle
            if (!$img->hasAttribute('alt') || empty($img->getAttribute('alt'))) {
                $alt_text = pathinfo($src, PATHINFO_FILENAME);
                $alt_text = str_replace(['_', '-'], ' ', $alt_text);
                $alt_text = ucwords($alt_text);
                $img->setAttribute('alt', $alt_text);
                $modified = true;
            }
            
            // Mobil uyumluluk için class ekle (eğer yoksa)
            if (!$img->hasAttribute('class') || strpos($img->getAttribute('class'), 'img-fluid') === false) {
                $current_class = $img->getAttribute('class');
                $new_class = empty($current_class) ? 'img-fluid' : $current_class . ' img-fluid';
                $img->setAttribute('class', $new_class);
                $modified = true;
            }
        }
        
        // Değişiklik yapıldıysa HTML'i geri al
        if ($modified) {
            // İçeriği al (sarmalayıcı div'i kaldır)
            $body = $dom->getElementsByTagName('div')->item(0);
            $new_content = '';
            
            if ($body) {
                $children = $body->childNodes;
                foreach ($children as $child) {
                    $new_content .= $dom->saveHTML($child);
                }
            } else {
                // Div bulunamazsa tüm içeriği al
                $new_content = $dom->saveHTML();
            }
            
            file_put_contents($log_file, "İçerik değiştirildi.\n", FILE_APPEND);
            $content = $new_content;
        } else {
            file_put_contents($log_file, "İçerikte değişiklik yapılmadı.\n", FILE_APPEND);
        }
        
        // libxml hata raporlamasını eski haline getir
        libxml_use_internal_errors($previous_value);
    } catch (Exception $e) {
        file_put_contents($log_file, "DOM işleme hatası: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    file_put_contents($log_file, "=== İçerik Dönüştürme Tamamlandı ===\n\n", FILE_APPEND);
    
    return $content;
}

// Base64 görüntülerini işleme - yardımcı fonksiyon
function process_base64_image($src, $log_file) {
    if (strpos($src, 'data:image/') !== 0) {
        return $src;
    }
    
    // Base64 bilgilerini ayır
    $data_parts = explode(',', $src);
    if (count($data_parts) < 2) {
        file_put_contents($log_file, "Geçersiz base64 formatı.\n", FILE_APPEND);
        return $src;
    }
    
    // Mime türünü al
    $mime_parts = explode(';', $data_parts[0]);
    $mime_type = str_replace('data:', '', $mime_parts[0]);
    
    // Görüntü türünü belirle
    $extension = 'jpg'; // Varsayılan
    if ($mime_type == 'image/png') {
        $extension = 'png';
    } elseif ($mime_type == 'image/gif') {
        $extension = 'gif';
    } elseif ($mime_type == 'image/webp') {
        $extension = 'webp';
    }
    
    // Base64 kodunu çöz
    $base64_string = $data_parts[1];
    $image_data = base64_decode($base64_string);
    
    if ($image_data === false) {
        file_put_contents($log_file, "Base64 kodu çözülemedi.\n", FILE_APPEND);
        return $src;
    }
    
    // Klasör yolu
    $date_folder = 'uploads/content/' . date('Y-m');
    $absolute_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $date_folder;
    
    // Klasörleri oluştur
    if (!file_exists($absolute_path)) {
        $created = false;
        
        // Ana dizinleri oluştur
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads')) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/uploads', 0777);
        }
        
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/content')) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/uploads/content', 0777);
        }
        
        // Tarih dizinini oluştur
        $created = mkdir($absolute_path, 0777, true);
        
        if (!$created) {
            file_put_contents($log_file, "Klasör oluşturulamadı: $absolute_path\n", FILE_APPEND);
            
            // Alternatif: Direkt uploads klasörünü kullan
            $date_folder = 'uploads';
            $absolute_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
            
            if (!file_exists($absolute_path)) {
                mkdir($absolute_path, 0777);
            }
        }
    }
    
    // Dosya adı oluştur
    $filename = 'img_' . uniqid() . '.' . $extension;
    $file_path = $date_folder . '/' . $filename;
    $absolute_file_path = $absolute_path . '/' . $filename;
    
    // Dosyaya kaydet
    $saved = file_put_contents($absolute_file_path, $image_data);
    
    if ($saved === false) {
        file_put_contents($log_file, "Dosya kaydedilemedi: $absolute_file_path\n", FILE_APPEND);
        return $src;
    }
    
    // Dosya izinlerini ayarla
    chmod($absolute_file_path, 0644);
    
    // Base URL ve tam URL
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $full_url = $base_url . '/' . $file_path;
    
    file_put_contents($log_file, "Base64 resmi dosyaya dönüştürüldü: $full_url\n", FILE_APPEND);
    
    return $full_url;
}

/**
 * Gömülü form — uygulama: includes/pipeline/page_content_blocks.php
 *
 * @param int $form_id Form ID
 * @param array $custom_settings Özel ayarlar (opsiyonel)
 */
function render_embedded_form($form_id, $custom_settings = []) {
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return '<div class="alert alert-warning">Form yüklenemedi.</div>';
    }
    require_once __DIR__ . '/pipeline/page_content_blocks.php';
    return mynak_render_embedded_form($conn, (int) $form_id, $custom_settings);
}

/**
 * Sayfa gövdesi kısa kodları — DB işlemi pipeline üzerinden.
 */
function blok_isle($content) {
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return demote_inline_h1_to_h2(mynak_normalize_html_href_attributes(html_etiketlerini_duzelt($content)));
    }
    require_once __DIR__ . '/pipeline/page_content_blocks.php';
    return mynak_blok_isle($conn, $content);
}
?>
