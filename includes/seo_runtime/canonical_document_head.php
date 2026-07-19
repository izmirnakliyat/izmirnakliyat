<?php
declare(strict_types=1);
/** Auto-split from seo_runtime.php — Phase 3 modular SEO runtime. */

/**
 * Canonical, relPath, sayfalama metinleri, tek robots &lt;meta&gt;, MovingCompany JSON-LD, MYNAK base path.
 * Kararların tamamı bu dosyada; header yalnızca dönen değerleri basar.
 *
 * @param array<string,mixed> $ctx (canonical_seo_pipeline önceden üretildiyse ctx içinde iletilir; tek decide + trace)
 * @return array{page_title:string,seo_description:string,relPath:string,canonical:string,canonical_origin:string,page_number:int,meta_robots:?string,meta_robots_tag_html:string,legacy_readonly_placeholder:string,mynak_base_path:string,structured_head_markup:string,seo_pipeline:array}
 * legacy_readonly_placeholder: Boş string; eski moving_company_ld_json anahtarının yanıltıcı adı kaldırıldı. Tüm üretim JSON-LD structured_head_markup içindedir (schema_factory).
 */
function seo_runtime_document_head(array $ctx): array
{
    $request_uri = (string) ($ctx['request_uri'] ?? '/');
    $get = isset($ctx['get']) && is_array($ctx['get']) ? $ctx['get'] : [];
    $allow_indexing = !empty($ctx['allow_indexing']);
    $canonical_override = (string) ($ctx['canonical_override'] ?? '');
    $page = isset($ctx['page']) && is_array($ctx['page']) ? $ctx['page'] : null;
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;
    $page_title = (string) ($ctx['page_title'] ?? '');
    $seo_description = (string) ($ctx['seo_description'] ?? '');
    $presetRaw = $ctx['meta_robots_preset'] ?? null;
    $presetMr = is_string($presetRaw) && $presetRaw !== '' ? $presetRaw : null;
    $site_settings = isset($ctx['site_settings']) && is_array($ctx['site_settings']) ? $ctx['site_settings'] : [];
    $seoFallbackContent = !empty($ctx['seo_fallback_content']);

    $qpos = strpos($request_uri, '?');
    $clean_path = $qpos !== false ? substr($request_uri, 0, $qpos) : $request_uri;

    $page_number = 1;
    if (preg_match('/\/sayfa\/(\d+)/', $clean_path, $matches)) {
        $page_number = (int) $matches[1];
    } elseif (isset($get['page']) && is_numeric($get['page'])) {
        $page_number = (int) $get['page'];
    }

    if ($page_number > 1) {
        $page_suffix = " - Sayfa $page_number";
        if (strpos($page_title, "Sayfa $page_number") === false) {
            $page_title .= $page_suffix;
        }
        $seo_description .= $page_suffix;
    }

    $canonical_origin = (function_exists('mynak_abs_url_from_public_path') && function_exists('mynak_public_path'))
        ? rtrim(mynak_abs_url_from_public_path(mynak_public_path('')), '/')
        : (defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '');
    $relPath = seo_runtime_compute_rel_path_from_request_uri($request_uri);

    $canonical_path_part = ($relPath === '/' || $relPath === '') ? '/' : $relPath;
    $canonical = $canonical_origin . $canonical_path_part;
    if ($page_number > 1 && strpos($clean_path, '/sayfa/') === false) {
        $canonical .= '?page=' . $page_number;
    }

    $canonical_computed = $canonical;

    if ($canonical_override !== '') {
        $canonical = $canonical_override;
    } elseif ($page !== null && !empty($page['canonical_url'])) {
        $canonical = trim((string) $page['canonical_url']);
    } elseif ($blog !== null && !empty($blog['canonical_url'])) {
        $canonical = trim((string) $blog['canonical_url']);
    }

    $hasDbCanon = $canonical_override !== ''
        || ($page !== null && !empty(trim((string) ($page['canonical_url'] ?? ''))))
        || ($blog !== null && !empty(trim((string) ($blog['canonical_url'] ?? ''))));

    if ($hasDbCanon) {
        $cp = parse_url($canonical);
        if (is_array($cp) && !empty($cp['scheme']) && !empty($cp['host'])) {
            $canonical = $cp['scheme'] . '://' . $cp['host'] . ($cp['path'] ?? '/');
        }
    }

    if ($canonical_override === ''
        && (($page !== null && !empty(trim((string) ($page['canonical_url'] ?? ''))))
            || ($blog !== null && !empty(trim((string) ($blog['canonical_url'] ?? '')))))) {
        if (seo_rt_canonical_path_key($canonical) !== seo_rt_canonical_path_key($canonical_computed)) {
            $canonical = $canonical_computed;
        }
    }

    $isFrontHome = ($relPath === '/' || $relPath === '');

    $mr = seo_runtime_document_meta_robots([
        'relPath' => $relPath,
        'get' => $get,
        'allow_indexing' => $allow_indexing,
        'is_front_home' => $isFrontHome,
        'meta_robots_preset' => $presetMr,
        'seo_fallback_content' => $seoFallbackContent,
    ]);

    $mr = seo_runtime_apply_discover_robots_directive($mr);
    $meta_robots_tag_html = '<meta name="robots" content="' . htmlspecialchars($mr, ENT_QUOTES, 'UTF-8') . '">';

    $pipelineCtx = [
        'relPath' => $relPath,
        'get' => $get,
        'page' => $page,
        'blog' => $blog,
        'site_settings' => $site_settings,
    ];
    $pipeline = isset($ctx['canonical_seo_pipeline']) && is_array($ctx['canonical_seo_pipeline'])
        ? $ctx['canonical_seo_pipeline']
        : canonical_seo_pipeline_core($pipelineCtx);
    if (!isset($ctx['canonical_seo_pipeline']) && function_exists('seo_runtime_trace_record_page_type_decision')) {
        seo_runtime_trace_record_page_type_decision($pipeline['decision']);
    }
    $moving_company_at_id = rtrim($canonical_origin, '/') . '/#organization';
    $legacy_readonly_placeholder = '';

    $mynak_base_path = (function_exists('mynak_url_path_prefix') && defined('SITE_URL'))
        ? mynak_url_path_prefix()
        : '';

    $decision = $pipeline['decision'];
    $pageTypeFallbackReason = isset($decision['fallback_reason']) && is_string($decision['fallback_reason'])
        ? $decision['fallback_reason']
        : null;
    $pageTypeResolved = (string) ($pipeline['page_type'] ?? 'global');

    $structured_head_markup = seo_runtime_pipeline_structured_head_markup(
        $relPath,
        $canonical,
        $canonical_origin,
        [
            'relPath' => $relPath,
            'get' => $get,
            'page' => $page,
            'blog' => $blog,
            'site_settings' => $site_settings,
            'page_title' => $page_title,
            'moving_company_at_id' => $moving_company_at_id,
        ],
        $pipeline
    );

    seo_runtime_trace_record_head(
        $canonical,
        $mr,
        $seoFallbackContent,
        $meta_robots_tag_html !== '',
        $pageTypeResolved,
        $pageTypeFallbackReason
    );

    return [
        'page_title' => $page_title,
        'seo_description' => $seo_description,
        'relPath' => $relPath,
        'canonical' => $canonical,
        'canonical_origin' => $canonical_origin,
        'page_number' => $page_number,
        'meta_robots' => $mr,
        'meta_robots_tag_html' => $meta_robots_tag_html,
        'legacy_readonly_placeholder' => $legacy_readonly_placeholder,
        'mynak_base_path' => $mynak_base_path,
        'structured_head_markup' => $structured_head_markup,
        'seo_pipeline' => $pipeline,
    ];
}
