<?php
/**
 * Semrush / PageSpeed: üretim öncesi bir kez çalıştırın:
 *   php scripts/build-public-min-assets.php
 * .min.css / .min.js dosyalarını üretir (kaynak dosyalar korunur).
 */

declare(strict_types=1);

$root = dirname(__DIR__);

function minify_css(string $css): string
{
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
    return trim($css);
}

/**
 * Tek tırnak, çift tırnak ve şablon literal içinde değilken // ve /* yorumlarını kaldırır.
 * ${ ... } içinde sadece süslü parantez derinliği (popup-forms / translator için yeterli).
 */
function strip_js_comments(string $js): string
{
    $out = '';
    $n = strlen($js);
    $i = 0;
    $state = 'code';

    while ($i < $n) {
        $c = $js[$i];

        if ($state === 'code') {
            if ($c === '/' && $i + 1 < $n) {
                if ($js[$i + 1] === '/') {
                    while ($i < $n && $js[$i] !== "\n" && $js[$i] !== "\r") {
                        $i++;
                    }
                    continue;
                }
                if ($js[$i + 1] === '*') {
                    $i += 2;
                    while ($i + 1 < $n && !($js[$i] === '*' && $js[$i + 1] === '/')) {
                        $i++;
                    }
                    $i += 2;
                    continue;
                }
            }
            if ($c === "'") {
                $state = 'sq';
                $out .= $c;
                $i++;
                continue;
            }
            if ($c === '"') {
                $state = 'dq';
                $out .= $c;
                $i++;
                continue;
            }
            if ($c === '`') {
                $state = 'bt';
                $out .= $c;
                $i++;
                continue;
            }
            $out .= $c;
            $i++;
            continue;
        }

        if ($state === 'sq') {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $n) {
                $out .= $js[$i + 1];
                $i += 2;
                continue;
            }
            if ($c === "'") {
                $state = 'code';
            }
            $i++;
            continue;
        }

        if ($state === 'dq') {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $n) {
                $out .= $js[$i + 1];
                $i += 2;
                continue;
            }
            if ($c === '"') {
                $state = 'code';
            }
            $i++;
            continue;
        }

        if ($state === 'bt') {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $n) {
                $out .= $js[$i + 1];
                $i += 2;
                continue;
            }
            if ($c === '$' && $i + 1 < $n && $js[$i + 1] === '{') {
                $out .= '{';
                $i += 2;
                $depth = 1;
                while ($i < $n && $depth > 0) {
                    $cc = $js[$i];
                    if ($cc === '{') {
                        $depth++;
                    } elseif ($cc === '}') {
                        $depth--;
                    }
                    $out .= $cc;
                    $i++;
                }
                continue;
            }
            if ($c === '`') {
                $state = 'code';
            }
            $i++;
            continue;
        }
    }

    return $out;
}

function minify_js_whitespace(string $js): string
{
    $js = strip_js_comments($js);
    $js = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $js);
    $js = preg_replace('/\s+/', ' ', $js);
    return trim($js);
}

$cssPairs = [
    'assets/css/keyframe-animation.css',
    'assets/css/nice-select.css',
    'assets/css/slider.css',
    'assets/css/common-style.css',
    'assets/css/main.css',
];

foreach ($cssPairs as $rel) {
    $src = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dst = preg_replace('/\.css$/', '.min.css', $src);
    if (!is_readable($src)) {
        fwrite(STDERR, "Skip missing: $rel\n");
        continue;
    }
    $out = minify_css(file_get_contents($src) ?: '');
    file_put_contents($dst, $out);
    echo "Wrote " . basename($dst) . " (" . strlen($out) . " bytes)\n";
}

$jsFiles = [
    'assets/js/mailchimp.js',
    'assets/js/quote-form.js',
    'assets/js/main.js',
    'assets/js/popup-forms.js',
    'js/translator.js',
    'js/main.js',
];

foreach ($jsFiles as $rel) {
    $src = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dst = preg_replace('/\.js$/', '.min.js', $src);
    if (!is_readable($src)) {
        fwrite(STDERR, "Skip missing: $rel\n");
        continue;
    }
    $out = minify_js_whitespace(file_get_contents($src) ?: '');
    file_put_contents($dst, $out);
    echo "Wrote " . str_replace('\\', '/', $rel) . " -> " . basename($dst) . " (" . strlen($out) . " bytes)\n";
}

echo "Done.\n";
