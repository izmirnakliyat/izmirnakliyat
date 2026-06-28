<?php
declare(strict_types=1);

/**
 * Kaynak dosyalarına yalın <a> linkleri → uygun medya etiketine dönüştür.
 * Semrush: "Resources formatted as page links" (34 sorun).
 *
 * Kural: <a href="dosya.jpg">metin</a> içinde iç <img> yoksa ve href bir resim/PDF ise:
 *   - Resim uzantısı → <img src="..." alt="metin" loading="lazy">
 *   - PDF → <a href="..." target="_blank" rel="noopener"> (download attribute eklenir)
 * Zaten <img> içeren <a> etiketleri (lightbox linkleri) dokunulmaz.
 */
function mynak_convert_resource_links_to_media(string $content): string
{
    if ($content === '') {
        return $content;
    }
    $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'bmp'];
    $pattern = '#<a\s[^>]*href\s*=\s*("|\')([^"\']+)\1[^>]*>((?:(?!</a>).)*)</a>#ius';

    return (string) preg_replace_callback($pattern, function (array $m) use ($imgExts): string {
        $full = $m[0];
        $href = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $inner = $m[3];

        // Zaten <img> içeriyorsa dokunma (lightbox pattern)
        if (stripos($inner, '<img') !== false) {
            return $full;
        }

        $urlPath = parse_url($href, PHP_URL_PATH);
        if ($urlPath === null || $urlPath === false) {
            return $full;
        }
        $ext = strtolower(pathinfo((string) $urlPath, PATHINFO_EXTENSION));

        if (in_array($ext, $imgExts, true)) {
            $alt = strip_tags(trim($inner));
            if ($alt === '' || mb_strlen($alt) > 120) {
                $alt = str_replace(['-', '_'], ' ', pathinfo($href, PATHINFO_FILENAME));
            }
            $altAttr = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
            $srcAttr = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
            return '<figure class="content-img-block"><img src="' . $srcAttr
                . '" alt="' . $altAttr
                . '" loading="lazy" decoding="async" style="max-width:100%;height:auto;border-radius:8px;"></figure>';
        }

        if ($ext === 'pdf') {
            $hrefAttr = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
            $label = trim(strip_tags($inner));
            if ($label === '') {
                $label = basename($href);
            }
            return '<a href="' . $hrefAttr . '" target="_blank" rel="noopener" download class="resource-download-link">'
                . '<i class="fas fa-file-pdf"></i> ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</a>';
        }

        return $full;
    }, $content);
}
