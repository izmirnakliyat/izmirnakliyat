<?php
declare(strict_types=1);

/**
 * Otorite / güven sinyalleri — LLM dışa aktarımı (seo_as_*).
 *
 * KURAL: Tahmini, sabit veya rastgele skor ÜRETİLMEZ. Yalnızca koddan
 * (cluster grafiği, hizmet SSOT, areaServed listesi) ve DB'den (services.icerik
 * gerçek kelime sayısı, ayarlardaki gerçek GBP puanı) doğrulanabilen sinyaller
 * kullanılır. Güvenilir veri yoksa metrik null / "hesaplanamadı" olarak bırakılır.
 *
 * Üretim head yalnızca dosyayı yükler; asıl kullanım llms.php ve raporlama.
 */

/**
 * Gerçek, doğrulanabilen güven sinyalleri (kod grafiği + isteğe bağlı DB ayarları).
 *
 * @param array<string, string> $site_settings
 * @return array<string, mixed>
 */
function seo_as_real_trust_signals(array $site_settings): array
{
    $defs = function_exists('seo_rt_pillar_cluster_definitions') ? seo_rt_pillar_cluster_definitions() : [];
    $districts = function_exists('seo_ei_izmir_district_names_local_pack_order')
        ? seo_ei_izmir_district_names_local_pack_order() : [];
    $primaryCount = function_exists('seo_rt_primary_service_graph_slugs')
        ? count(seo_rt_primary_service_graph_slugs()) : 0;
    $pillarHub = function_exists('seo_rt_money_page_pillar_slug') ? seo_rt_money_page_pillar_slug() : '';

    // İletişim bilgisi: DB ayarı varsa oradan, yoksa config sabitleri (ikisi de gerçek/doğrulanabilir).
    $hasPhone = trim((string) ($site_settings['phone1'] ?? '')) !== ''
        || (defined('MYNAK_CONTACT_PHONE_DISPLAY') && trim((string) MYNAK_CONTACT_PHONE_DISPLAY) !== '');
    $hasEmail = trim((string) ($site_settings['email'] ?? '')) !== ''
        || defined('ADMIN_EMAIL');
    $hasAddress = trim((string) ($site_settings['address'] ?? '')) !== ''
        || (defined('MYNAK_CONTACT_ADDRESS_DISPLAY') && trim((string) MYNAK_CONTACT_ADDRESS_DISPLAY) !== '');

    // aggregateRating: yalnızca gerçek veri (puan + yorum sayısı) varsa; yoksa hesaplanamadı.
    $ratingVal = (float) ($site_settings['google_place_rating'] ?? 0);
    $ratingCount = (int) ($site_settings['google_total_reviews'] ?? 0);
    $aggregateRating = ($ratingVal > 0 && $ratingCount > 0)
        ? ['rating_value' => $ratingVal, 'review_count' => $ratingCount]
        : 'hesaplanamadı';

    return [
        'has_contact_page' => isset($defs['iletisim']),
        'has_about_page' => isset($defs['hakkimizda']),
        'has_pricing_page' => isset($defs['fiyat']),
        'has_quote_flow' => isset($defs['teklif-alin']),
        'has_service_area_clarity' => count($districts) > 0,
        'service_area_district_count' => count($districts),
        'pillar_hub_slug' => $pillarHub,
        'primary_service_count' => $primaryCount,
        'has_public_phone' => $hasPhone,
        'has_public_email' => $hasEmail,
        'has_public_address' => $hasAddress,
        'aggregate_rating' => $aggregateRating,
    ];
}

/**
 * services.icerik üzerinden GERÇEK kelime sayıları (DB). DB/kolon yoksa boş döner
 * (metrik "hesaplanamadı" olarak işaretlenir; tahmini değer üretilmez).
 *
 * @param list<string> $graphSlugs
 * @return array<string, int>
 */
function seo_as_service_word_counts(array $graphSlugs): array
{
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli) || empty($graphSlugs)) {
        return [];
    }

    $graphByPublic = [];
    foreach ($graphSlugs as $g) {
        $pub = function_exists('seo_rt_public_url_slug_for_graph_slug')
            ? seo_rt_public_url_slug_for_graph_slug((string) $g) : (string) $g;
        $graphByPublic[$pub] = (string) $g;
    }

    // services tablosu / icerik kolonu yoksa prepare false döner (mysqli_report OFF) → sessiz geç.
    $stmt = @$conn->prepare('SELECT slug, icerik FROM services WHERE status = 1');
    if ($stmt === false) {
        return [];
    }

    $counts = [];
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res instanceof mysqli_result) {
            while ($row = $res->fetch_assoc()) {
                $slug = (string) ($row['slug'] ?? '');
                if (!isset($graphByPublic[$slug])) {
                    continue;
                }
                $text = trim(strip_tags((string) ($row['icerik'] ?? '')));
                if ($text === '') {
                    $counts[$graphByPublic[$slug]] = 0;
                    continue;
                }
                $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
                $counts[$graphByPublic[$slug]] = is_array($parts) ? count($parts) : 0;
            }
        }
    }
    $stmt->close();

    return $counts;
}

/**
 * Otorite + güven dışa aktarımı — YALNIZCA doğrulanabilir sinyaller.
 * Roller cluster grafiğinden; içerik derinliği gerçek DB kelime sayısından türetilir.
 *
 * @param array<string, array<string, mixed>> $defs
 * @param array<string, string> $site_settings
 * @return array<string, mixed>
 */
function seo_as_llm_authority_export(array $defs, array $site_settings): array
{
    $moneyPillar = function_exists('seo_rt_money_page_pillar_slug') ? seo_rt_money_page_pillar_slug() : '';
    $primary = function_exists('seo_rt_primary_service_graph_slugs') ? seo_rt_primary_service_graph_slugs() : [];
    $primarySet = array_fill_keys($primary, true);

    $serviceSlugs = [];
    foreach ($defs as $slug => $meta) {
        $slug = (string) $slug;
        $pillar = is_array($meta) ? ($meta['pillar'] ?? null) : null;
        if ($slug === $moneyPillar || $pillar === $moneyPillar) {
            $serviceSlugs[] = $slug;
        }
    }

    $wordCounts = seo_as_service_word_counts($serviceSlugs);

    $services_authority = [];
    foreach ($defs as $slug => $meta) {
        $slug = (string) $slug;
        $nav = is_array($meta) && isset($meta['nav_title']) ? (string) $meta['nav_title'] : $slug;
        $pillar = is_array($meta) ? ($meta['pillar'] ?? null) : null;
        $isService = ($slug === $moneyPillar) || ($pillar === $moneyPillar);

        if ($slug === $moneyPillar) {
            $role = 'money_pillar';
        } elseif (isset($primarySet[$slug])) {
            $role = 'primary_service';
        } elseif ($isService) {
            $role = 'supporting_service';
        } else {
            $role = 'non_service';
        }

        $services_authority[$slug] = [
            'nav_title' => $nav,
            'role' => $role,
            'is_pillar_service' => $slug === $moneyPillar,
            'is_primary_service' => isset($primarySet[$slug]),
            'content_word_count' => array_key_exists($slug, $wordCounts) ? $wordCounts[$slug] : null,
        ];
    }

    $depth = [];
    foreach ($services_authority as $slug => $row) {
        if (is_int($row['content_word_count'])) {
            $depth[] = [
                'slug' => $slug,
                'nav_title' => $row['nav_title'],
                'content_word_count' => $row['content_word_count'],
            ];
        }
    }
    usort($depth, static fn(array $a, array $b): int => $b['content_word_count'] <=> $a['content_word_count']);

    return [
        'version' => 2,
        'methodology' => 'Yalnızca koddan/DB\'den doğrulanabilen sinyaller; tahmini/sabit/rastgele skor yok. '
            . 'Doğrulanamayan metrik null ("hesaplanamadı") bırakılır.',
        'trust_signals' => seo_as_real_trust_signals($site_settings),
        'content_depth_available' => !empty($depth),
        'services_authority' => $services_authority,
        'content_depth_ranking' => $depth,
    ];
}

/**
 * /llms.txt "trust_signals_block" bölümü için okunabilir güven sinyali bloğu.
 * seo_as_real_trust_signals ile aynı SSOT (kod tekrarı yok).
 *
 * @param array<string, string> $site_settings
 * @return array<string, mixed>
 */
function seo_llms_trust_signals_readable_block(array $site_settings): array
{
    return seo_as_real_trust_signals($site_settings);
}
