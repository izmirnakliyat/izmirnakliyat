<?php
declare(strict_types=1);
/**
 * Production SEO runtime — Phase 3 modüler yapı.
 *
 * Orchestrator: istek normalize → sayfa tipi pipeline → canonical/robots → JSON-LD (schema_factory).
 * Tarihsel not: Eski tekil `seo_runtime.legacy.monolith.php` yedeği kaldırıldı; kaynak `includes/seo_runtime/` modülleri.
 *
 * Modül dizini: includes/seo_runtime/
 */
$mynakSeoRt = __DIR__ . '/seo_runtime';

require_once $mynakSeoRt . '/constants.php';
require_once __DIR__ . '/seo_runtime_trace.php';
require_once __DIR__ . '/seo_entity_intent_graph.php';
require_once __DIR__ . '/seo_flex_content_layer.php';

require_once $mynakSeoRt . '/paths.php';
require_once $mynakSeoRt . '/robots_meta.php';
require_once $mynakSeoRt . '/sitemap_allow.php';
require_once $mynakSeoRt . '/redirects_early.php';

require_once $mynakSeoRt . '/internal_linking.php';
require_once $mynakSeoRt . '/pipeline_page_type.php';
require_once $mynakSeoRt . '/internal_link_anchor_pool.php';
require_once $mynakSeoRt . '/faq_extractor.php';
require_once $mynakSeoRt . '/jsonld_encode_and_schema.php';
require_once $mynakSeoRt . '/canonical_document_head.php';

require_once __DIR__ . '/seo_authority_signal_layer.php';
