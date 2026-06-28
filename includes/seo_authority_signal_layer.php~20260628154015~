<?php
declare(strict_types=1);

/**
 * Otorite / güven sinyalleri — LLM dışa aktarımı (seo_as_llm_authority_export).
 * Üretim head yalnızca dosyayı yükler; asıl kullanım llms.php ve raporlama.
 *
 * @param array<string, array<string, mixed>> $defs
 * @param array<string, string> $site_settings
 * @return array<string, mixed>
 */
function seo_as_llm_authority_export(array $defs, array $site_settings): array
{
    unset($site_settings);

    $services_authority = [];
    foreach ($defs as $slug => $meta) {
        $slug = (string) $slug;
        $nav = is_array($meta) && isset($meta['nav_title']) ? (string) $meta['nav_title'] : $slug;
        $services_authority[$slug] = [
            'nav_title' => $nav,
            'authority_score' => 50,
            'content_depth_score' => 50,
            'trust_signal_score' => 50,
            'is_pillar_service' => true,
            'authority_multiplier' => '1',
            'priority_indexing_weight' => 0,
            'service_tier' => 'standard',
        ];
    }

    $slugs = array_keys($services_authority);
    $top_authority_pages = [];
    $content_depth_ranking = [];
    $limit = min(8, count($slugs));
    for ($i = 0; $i < $limit; $i++) {
        $s = $slugs[$i];
        $row = $services_authority[$s];
        $top_authority_pages[] = [
            'slug' => $s,
            'nav_title' => $row['nav_title'],
            'authority_score' => $row['authority_score'],
            'content_depth_score' => $row['content_depth_score'],
        ];
        $content_depth_ranking[] = [
            'slug' => $s,
            'content_depth_score' => $row['content_depth_score'],
        ];
    }

    return [
        'version' => 1,
        'trust_summary' => [
            'trust_signal_score' => 60,
            'has_contact_page' => true,
            'has_about_page' => true,
            'has_service_area_clarity' => true,
            'organization_schema_completeness' => 70,
        ],
        'services_authority' => $services_authority,
        'top_authority_pages' => $top_authority_pages,
        'content_depth_ranking' => $content_depth_ranking,
    ];
}
