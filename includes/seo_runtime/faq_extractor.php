<?php
declare(strict_types=1);

/**
 * Hizmet ve blog HTML gövdesinden FAQPage (Schema.org) için soru-cevap
 * çiftlerini çıkarır. H1..H4 başlıkları taranır; başlık SORU formatında ise
 * (? ile biten ya da "neden/nasıl/ne zaman/kim/..." ile başlayan) ve
 * hemen altında cevap metni varsa eşleşme kabul edilir.
 *
 * Not (2026-04): Admin'den eklenen services.icerik içeriklerinde bölüm
 * başlıkları çoğunlukla H1 seviyesinde tutulduğu için H1 tarama kapsamına
 * alındı (question-filter zaten H1'i genel başlık olarak kabul etmez —
 * yalnızca soru işareti veya soru kelimesi taşıyanlar geçer).
 *
 * Görsel çıktıyı değiştirmez; yalnızca <head> JSON-LD için veri üretir.
 */

if (defined('MYNAK_SEO_FAQ_EXTRACTOR_LOADED')) {
    return;
}
define('MYNAK_SEO_FAQ_EXTRACTOR_LOADED', true);

/**
 * @return list<array{question:string,answer:string}>
 */
function seo_runtime_extract_faq_pairs_from_html(string $html, int $maxItems = 10): array
{
    $html = trim($html);
    if ($html === '') {
        return [];
    }

    if (!preg_match_all(
        '#<(h[1-4])(?:\s[^>]*)?>(.*?)</\1>(.*?)(?=<h[1-4]|$)#is',
        $html,
        $m,
        PREG_SET_ORDER
    )) {
        return [];
    }

    $pairs = [];
    foreach ($m as $block) {
        $q = seo_runtime_faq_plain_text((string) ($block[2] ?? ''));
        $body = (string) ($block[3] ?? '');
        if ($q === '' || !seo_runtime_faq_is_question($q)) {
            continue;
        }
        $a = seo_runtime_faq_extract_first_answer($body);
        if ($a === '') {
            continue;
        }
        $pairs[] = ['question' => $q, 'answer' => $a];
        if (count($pairs) >= max(1, $maxItems)) {
            break;
        }
    }

    return $pairs;
}

function seo_runtime_faq_plain_text(string $html): string
{
    $t = strip_tags($html);
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace('/\s+/u', ' ', $t);

    return trim((string) $t);
}

function seo_runtime_faq_is_question(string $q): bool
{
    if ($q === '') {
        return false;
    }
    if (substr($q, -1) === '?') {
        return true;
    }
    $q = mb_strtolower($q, 'UTF-8');
    $starts = ['neden ', 'nasıl ', 'nasil ', 'ne zaman ', 'kim ', 'kaç ', 'kac ', 'hangi ', 'nerede ', 'nereden ', 'nereye ', 'kimler ', 'mı', 'mi', 'mu', 'mü'];
    foreach ($starts as $k) {
        if (strpos($q, $k) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Başlığı takip eden ilk anlamlı paragraf/liste metnini çıkarır.
 */
function seo_runtime_faq_extract_first_answer(string $body): string
{
    $body = trim($body);
    if ($body === '') {
        return '';
    }
    $answer = '';
    if (preg_match('#<p\b[^>]*>(.*?)</p>#is', $body, $pm)) {
        $answer = seo_runtime_faq_plain_text((string) $pm[1]);
    }
    if ($answer === '' && preg_match('#<(?:ul|ol)\b[^>]*>(.*?)</(?:ul|ol)>#is', $body, $lm)) {
        if (preg_match_all('#<li\b[^>]*>(.*?)</li>#is', (string) $lm[1], $liMatch)) {
            $items = array_map('seo_runtime_faq_plain_text', $liMatch[1]);
            $items = array_filter($items, static fn($x) => $x !== '');
            $answer = implode(' ', $items);
        }
    }
    if ($answer === '') {
        $answer = seo_runtime_faq_plain_text($body);
    }
    if (mb_strlen($answer, 'UTF-8') > 900) {
        $answer = rtrim(mb_substr($answer, 0, 897, 'UTF-8')) . '...';
    }

    return $answer;
}

/**
 * FAQPage JSON-LD dizisi üret (boşsa null).
 *
 * @param list<array{question:string,answer:string}> $pairs
 * @return array<string,mixed>|null
 */
function seo_runtime_faq_page_ld(array $pairs): ?array
{
    if ($pairs === []) {
        return null;
    }
    $main = [];
    foreach ($pairs as $p) {
        $q = trim((string) ($p['question'] ?? ''));
        $a = trim((string) ($p['answer'] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $main[] = [
            '@type' => 'Question',
            'name' => $q,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $a,
            ],
        ];
    }
    if ($main === []) {
        return null;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $main,
    ];
}

/**
 * Görünür gövdeyle hizalı HTML — FAQPage JSON-LD çıkarımı için.
 * - Hizmet / statik şablon: ilçe lede (varsa) + mynak_blok_isle(icerik); DB ham metni tek başına değil.
 * - Blog: yalnızca mynak_blok_isle(icerik); üstteki AIO kutusu hariç (orada ayrı HowTo script var).
 *
 * @param 'service'|'blog_post' $pageType
 */
function seo_runtime_faq_html_for_ld_extraction(string $pageType, ?array $page, ?array $blog): string
{
    if ($pageType === 'blog_post') {
        if (!is_array($blog) || empty($blog['icerik'])) {
            return '';
        }
        $raw = (string) $blog['icerik'];
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli && function_exists('mynak_blok_isle')) {
            return mynak_blok_isle($GLOBALS['conn'], $raw);
        }

        return $raw;
    }
    if ($pageType === 'service') {
        if (!is_array($page) || empty($page['content'])) {
            return '';
        }
        $raw = (string) $page['content'];
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli && function_exists('mynak_blok_isle')) {
            $raw = mynak_blok_isle($GLOBALS['conn'], $raw);
        }
        $slug = isset($page['slug']) ? trim((string) $page['slug'], '/') : '';
        if ($slug !== '' && !function_exists('mynak_ilce_unique_opening_html')) {
            $ilceFile = dirname(__DIR__) . '/pipeline/ilce_unique_opening.php';
            if (is_readable($ilceFile)) {
                require_once $ilceFile;
            }
        }
        if ($slug !== '' && function_exists('mynak_ilce_unique_opening_html')) {
            $prefix = mynak_ilce_unique_opening_html($slug);
            if ($prefix !== '') {
                return $prefix . $raw;
            }
        }

        return $raw;
    }

    return '';
}
