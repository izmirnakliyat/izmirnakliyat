<?php
declare(strict_types=1);

/**
 * Üretim &lt;head&gt; JSON-LD çıktısı YALNIZCA schema_factory() (full_head_context) üzerinden üretilir.
 * Başka yollar (rich_snippets DB, şablon içi ham script) runtime head pipeline’ına bağlanmamalıdır.
 */
const MYNAK_PRODUCTION_JSONLD_EMITTER_RULE = 'schema_factory_only';

/**
 * JSON-LD MovingCompany / LocalBusiness / Organization düğümlerinde tutarlı ticari unvan.
 */
function mynak_schema_brand(): string
{
    return defined('MYNAK_BRAND_NAME') ? MYNAK_BRAND_NAME : 'MY Nakliyat';
}

/**
 * Onaylı kısa takma adlar (site_title SEO başlığı buraya konmaz).
 *
 * @return list<string>
 */
function mynak_schema_brand_aliases(): array
{
    return [
        'MY Nakliyat ®',
        'MY Nakliyat İzmir',
    ];
}

/**
 * Peer firma adı (MY Nakliyat ile bağımsız, ayrı tüzel kişilik).
 */
function mynak_schema_disambiguation_peer_name(): string
{
    return defined('MYNAK_DISAMBIGUATION_PEER_NAME')
        ? trim((string) MYNAK_DISAMBIGUATION_PEER_NAME)
        : '';
}

/**
 * Peer firma resmi sitesi.
 */
function mynak_schema_disambiguation_peer_url(): string
{
    return defined('MYNAK_DISAMBIGUATION_PEER_URL')
        ? trim((string) MYNAK_DISAMBIGUATION_PEER_URL)
        : '';
}

/**
 * Schema.org disambiguatingDescription — AI/arama sistemleri için ayrı entity sinyali.
 */
function mynak_schema_disambiguating_description(): string
{
    $peer = mynak_schema_disambiguation_peer_name();
    $peerUrl = mynak_schema_disambiguation_peer_url();
    if ($peer === '' || $peerUrl === '') {
        return '';
    }

    $brand = mynak_schema_brand();
    $peerHost = preg_replace('#^https?://#i', '', rtrim($peerUrl, '/'));

    return sprintf(
        '%s (mynakliyat.com.tr) bağımsız bir nakliyat firmasıdır. %s (%s) ile ortaklık, bağlılık, franchise veya aynı tüzel kişilik yoktur; iki ayrı şirkettir.',
        $brand,
        $peer,
        $peerHost !== null && $peerHost !== '' ? $peerHost : $peerUrl
    );
}

/**
 * MovingCompany / WebSite JSON-LD düğümüne marka kimliği + ayrım alanlarını uygular.
 *
 * @param array<string, mixed> $schema
 */
function mynak_schema_apply_brand_identity(array &$schema, bool $includeAlternateNames = true): void
{
    $aliases = mynak_schema_brand_aliases();
    if ($includeAlternateNames && $aliases !== []) {
        $schema['alternateName'] = count($aliases) === 1 ? $aliases[0] : $aliases;
    }

    $disambiguation = mynak_schema_disambiguating_description();
    if ($disambiguation !== '') {
        $schema['disambiguatingDescription'] = $disambiguation;
    }
}

/** Sayfa başlığı soneki — kısa marka adı (uzun site_title SEO başlığı değil). */
function mynak_brand_title_suffix(): string
{
    return ' | ' . mynak_schema_brand();
}

/**
 * Sayfa başlığı: yalnızca sayfa adı + marka; uzun site_title eklenmez.
 */
function mynak_build_page_title(string $stem): string
{
    $stem = trim($stem);
    $brand = mynak_schema_brand();
    if ($stem === '') {
        return $brand;
    }

    // Eski site_title soneklerini temizle (AI/marka karıştırmasını önler)
    $patterns = [
        '/\s*[-|–—]\s*İzmir Evden Eve Nakliyat\s*[-|–—]\s*MY Nakliyat\s*®?\s*Resmi Sitesi\s*$/iu',
        '/\s*[-|–—]\s*MY Nakliyat\s*®?\s*Resmi Sitesi\s*$/iu',
    ];
    foreach ($patterns as $pattern) {
        $cleaned = preg_replace($pattern, '', $stem);
        if (is_string($cleaned)) {
            $stem = trim($cleaned);
        }
    }

    $stem = preg_replace(
        '/\s*[|\-–—:]\s*Güvenilir Marka Ödüllü(?=\s*[|\-–—:]|$)/iu',
        '',
        $stem
    ) ?? $stem;
    $stem = preg_replace('/^Güvenilir Marka Ödüllü\s*[|\-–—:]?\s*/iu', '', $stem) ?? $stem;

    // Güvenlik ağı: DB'de kalmış kesik "…"/"..." son eki ve yarım kalan marka
    // parçası (ör. "… Ödüllü MY…") <title>'da çift marka ("MY… | MY Nakliyat")
    // ve kesik son üretiyordu. Marka kontrolünden ÖNCE temizle.
    $stem = preg_replace('/\s*(\x{2026}|\.{2,})\s*$/u', '', $stem) ?? $stem; // kesik "…"/"..."
    $stem = preg_replace('/\s*[|\-–—:]\s*MY$/u', '', $stem) ?? $stem;        // dangling "| MY"
    $stem = rtrim($stem, " \t\n\r\0\x0B|-–—:,");
    if ($stem === '') {
        return $brand;
    }

    if (preg_match('/\b' . preg_quote($brand, '/') . '\b/u', $stem)) {
        return $stem;
    }

    return $stem . mynak_brand_title_suffix();
}

/**
 * Layout pipeline sonrası title normalizasyonu.
 */
function mynak_normalize_public_page_title(string $title): string
{
    return mynak_build_page_title($title);
}

/** Logo img alt metni — site_title yerine marka adı. */
function mynak_logo_alt_text(): string
{
    return mynak_schema_brand() . ' Logo';
}
