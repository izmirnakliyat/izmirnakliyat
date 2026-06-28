<?php
declare(strict_types=1);

/**
 * Hafif (vendor-less) HTML → Markdown dönüştürücü.
 *
 * LLM bot'ların (`Accept: text/markdown`) işlemesi için sayfa içeriğini
 * temiz, başlık-listeleme korumalı markdown'a çevirir.
 *
 * Kapsam:
 *   - h1..h6 başlıklar
 *   - paragraflar
 *   - güçlü/italik vurgular
 *   - kod blokları (<pre>, <code>)
 *   - bağlantılar
 *   - sıralı/sırasız listeler
 *   - blockquote
 *   - resimler
 *   - tablolar (basit dönüşüm)
 *   - <br>, <hr>
 *
 * Idempotent yükleme.
 */

if (defined('MYNAK_HTML_TO_MARKDOWN_LOADED')) {
    return;
}
define('MYNAK_HTML_TO_MARKDOWN_LOADED', true);

/**
 * Public API.
 */
function mynak_html_to_markdown(string $html): string
{
    $html = (string) $html;
    if ($html === '') {
        return '';
    }

    // Script/style/iframe çıkar
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
    $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;

    $dom = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $wrapped = '<?xml encoding="UTF-8"><div>' . $html . '</div>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root instanceof DOMNode) {
        return mynak_md_strip_to_plain($html);
    }

    $md = mynak_md_render_children($root);
    $md = (string) preg_replace("/[ \t]+\n/", "\n", $md);
    $md = (string) preg_replace("/\n{3,}/", "\n\n", $md);
    return trim($md);
}

function mynak_md_render_children(DOMNode $node): string
{
    $out = '';
    foreach ($node->childNodes as $child) {
        $out .= mynak_md_render($child);
    }
    return $out;
}

function mynak_md_render(DOMNode $node): string
{
    if ($node->nodeType === XML_TEXT_NODE) {
        $t = (string) $node->nodeValue;
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = (string) preg_replace('/\s+/u', ' ', $t);
        return $t;
    }
    if (!($node instanceof DOMElement)) {
        return '';
    }

    $tag = strtolower($node->tagName);
    switch ($tag) {
        case 'h1':
            return "\n\n# " . trim(mynak_md_inline($node)) . "\n\n";
        case 'h2':
            return "\n\n## " . trim(mynak_md_inline($node)) . "\n\n";
        case 'h3':
            return "\n\n### " . trim(mynak_md_inline($node)) . "\n\n";
        case 'h4':
            return "\n\n#### " . trim(mynak_md_inline($node)) . "\n\n";
        case 'h5':
            return "\n\n##### " . trim(mynak_md_inline($node)) . "\n\n";
        case 'h6':
            return "\n\n###### " . trim(mynak_md_inline($node)) . "\n\n";

        case 'p':
            $t = trim(mynak_md_inline($node));
            return $t === '' ? '' : "\n\n" . $t . "\n\n";

        case 'br':
            return "  \n";
        case 'hr':
            return "\n\n---\n\n";

        case 'strong':
        case 'b':
            return '**' . trim(mynak_md_inline($node)) . '**';
        case 'em':
        case 'i':
            return '*' . trim(mynak_md_inline($node)) . '*';

        case 'a':
            $href = trim((string) $node->getAttribute('href'));
            $txt = trim(mynak_md_inline($node));
            if ($href === '' || $txt === '') {
                return $txt;
            }
            return '[' . $txt . '](' . $href . ')';

        case 'img':
            $src = trim((string) $node->getAttribute('src'));
            $alt = trim((string) $node->getAttribute('alt'));
            if ($src === '') {
                return '';
            }
            return "\n\n![" . $alt . '](' . $src . ")\n\n";

        case 'code':
            // Inline code (içeride <pre> yoksa)
            $parent = $node->parentNode;
            if ($parent instanceof DOMElement && strtolower($parent->tagName) === 'pre') {
                return $node->textContent;
            }
            return '`' . trim($node->textContent) . '`';

        case 'pre':
            return "\n\n```\n" . rtrim($node->textContent) . "\n```\n\n";

        case 'ul':
        case 'ol':
            return mynak_md_list($node, $tag === 'ol');

        case 'li':
            // li doğrudan render edilirse içeriğini düz dön
            return mynak_md_inline($node);

        case 'blockquote':
            $inner = mynak_md_render_children($node);
            $lines = preg_split('/\r?\n/', trim($inner)) ?: [];
            $lines = array_map(static fn($l) => '> ' . $l, $lines);
            return "\n\n" . implode("\n", $lines) . "\n\n";

        case 'table':
            return mynak_md_table($node);

        case 'div':
        case 'span':
        case 'section':
        case 'article':
        case 'main':
        case 'header':
        case 'footer':
        case 'aside':
        case 'nav':
        case 'figure':
        case 'figcaption':
            return mynak_md_render_children($node);

        default:
            return mynak_md_render_children($node);
    }
}

function mynak_md_inline(DOMNode $node): string
{
    $t = mynak_md_render_children($node);
    return (string) preg_replace('/\s+/u', ' ', $t);
}

function mynak_md_list(DOMElement $node, bool $ordered): string
{
    $out = "\n\n";
    $i = 1;
    foreach ($node->childNodes as $child) {
        if (!($child instanceof DOMElement) || strtolower($child->tagName) !== 'li') {
            continue;
        }
        $marker = $ordered ? ($i++ . '.') : '-';
        $text = trim(mynak_md_render_children($child));
        $text = (string) preg_replace('/\s+/u', ' ', $text);
        if ($text === '') {
            continue;
        }
        $out .= $marker . ' ' . $text . "\n";
    }
    return $out . "\n";
}

function mynak_md_table(DOMElement $node): string
{
    $rows = [];
    $headerRow = null;
    foreach ($node->getElementsByTagName('tr') as $tr) {
        $cells = [];
        foreach ($tr->childNodes as $c) {
            if (!($c instanceof DOMElement)) {
                continue;
            }
            $tag = strtolower($c->tagName);
            if ($tag === 'td' || $tag === 'th') {
                $val = trim(mynak_md_inline($c));
                $val = (string) preg_replace('/\|/u', '\\|', $val);
                $cells[] = $val;
                if ($tag === 'th' && $headerRow === null) {
                    $headerRow = true;
                }
            }
        }
        if ($cells !== []) {
            $rows[] = $cells;
        }
    }
    if ($rows === []) {
        return '';
    }
    $colCount = max(array_map('count', $rows));
    foreach ($rows as &$r) {
        while (count($r) < $colCount) {
            $r[] = '';
        }
    }
    unset($r);
    $head = array_shift($rows);
    $sep = array_fill(0, $colCount, '---');
    $out = "\n\n| " . implode(' | ', $head) . " |\n| " . implode(' | ', $sep) . " |\n";
    foreach ($rows as $r) {
        $out .= '| ' . implode(' | ', $r) . " |\n";
    }
    return $out . "\n";
}

function mynak_md_strip_to_plain(string $html): string
{
    $t = strip_tags($html);
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $t));
}
