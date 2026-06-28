<?php
declare(strict_types=1);

/**
 * Microsoft Clarity (https://clarity.microsoft.com/) — yalnız site ayarları açıkken.
 * $site_settings: clarity_enabled = '1', clarity_project_id = proje kısa kimliği.
 */

/**
 * Açık ve geçerli proje varsa <link rel="preconnect"> + resmi izleme snippet'ı; yoksa boş dize.
 */
function mynak_microsoft_clarity_head_markup(array $site_settings): string
{
    if (empty($site_settings['clarity_enabled']) || (string) $site_settings['clarity_enabled'] !== '1') {
        return '';
    }
    $raw = trim((string) ($site_settings['clarity_project_id'] ?? ''));
    if ($raw === '' || !preg_match('/^[a-z0-9]{3,32}$/i', $raw)) {
        return '';
    }
    $id = strtolower($raw);
    $idJs = json_encode($id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    if ($idJs === false) {
        return '';
    }
    $out = '<link rel="dns-prefetch" href="https://www.clarity.ms">' . "\n    "
        . '<link rel="preconnect" href="https://www.clarity.ms" crossorigin>' . "\n    "
        . '<script type="text/javascript">'
        . '(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};'
        . 't=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;'
        . 'y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);'
        . '})(window, document, "clarity", "script", ' . $idJs . ');'
        . '</script>';

    return $out;
}
