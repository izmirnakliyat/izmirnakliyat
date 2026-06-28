<?php
/**
 * Rich Snippets / Structured Data — UYUMLULUK ve ADMIN katmanı.
 *
 * DEPRECATED — ÜRETİM HEAD: Bu dosyadaki JSON-LD üreten fonksiyonlar (getRichSnippets, getBlogPostSchema,
 * getBreadcrumbSchema) canlı site &lt;head&gt; pipeline’ında (seo_runtime_document_head) KULLANILMAZ.
 * Üretim JSON-LD yalnızca includes/seo_runtime.php içindeki schema_factory() ile basılır (MYNAK_PRODUCTION_JSONLD_EMITTER_RULE).
 * Admin araçları / eski betikler bu yardımcıları çağırmaya devam edebilir; tema ve header’a bağlamayın.
 */

/**
 * Veritabanından gelen LD+JSON'da @type Organization iken yerel işletme alanları
 * (openingHours, geo, priceRange) bazı doğrulayıcıları hata verdirir; MovingCompany'a taşınır.
 * inLanguage Organization/LocalBusiness/MovingCompany üzerinde geçerli değildir (WebSite vb. için).
 */
function rich_snippets_sanitize_schema_node(array &$s): void
{
    if (!isset($s['@type']) || !is_string($s['@type'])) {
        return;
    }
    $t = $s['@type'];

    if ($t === 'Organization') {
        $hasLocal = !empty($s['openingHoursSpecification']) || !empty($s['openingHours'])
            || !empty($s['priceRange']) || !empty($s['geo']);
        if ($hasLocal) {
            $s['@type'] = 'MovingCompany';
        } else {
            unset($s['openingHoursSpecification'], $s['openingHours'], $s['priceRange'], $s['geo']);
        }
        unset($s['inLanguage']);
    }

    $t = $s['@type'] ?? '';
    if ($t === 'MovingCompany' || $t === 'LocalBusiness') {
        unset($s['inLanguage']);
    }
}

function rich_snippets_sanitize_decoded(&$data): void
{
    if (!is_array($data)) {
        return;
    }
    $keys = array_keys($data);
    $isList = $keys === range(0, count($data) - 1);
    if ($isList) {
        foreach ($data as &$item) {
            if (is_array($item)) {
                rich_snippets_sanitize_decoded($item);
            }
        }
        unset($item);
        return;
    }

    if (isset($data['@type'])) {
        rich_snippets_sanitize_schema_node($data);
    }
    foreach ($data as &$v) {
        if (!is_array($v)) {
            continue;
        }
        $vk = array_keys($v);
        if ($vk === range(0, count($v) - 1)) {
            foreach ($v as &$item) {
                if (is_array($item)) {
                    rich_snippets_sanitize_decoded($item);
                }
            }
            unset($item);
        } else {
            rich_snippets_sanitize_decoded($v);
        }
    }
    unset($v);
}

/**
 * @param mixed $t schema.org @type (string, URL veya dizi)
 */
function rich_snippets_type_includes_faqpage($t): bool
{
    if ($t === 'FAQPage' || $t === 'https://schema.org/FAQPage') {
        return true;
    }
    if (is_array($t)) {
        foreach ($t as $x) {
            if (rich_snippets_type_includes_faqpage($x)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Çözümlenmiş JSON-LD kökünde FAQPage var mı (Google: sayfa başına tek FAQPage).
 */
function rich_snippets_decoded_contains_faqpage($decoded): bool
{
    if (!is_array($decoded)) {
        return false;
    }
    if (rich_snippets_type_includes_faqpage($decoded['@type'] ?? null)) {
        return true;
    }
    if (!empty($decoded['@graph']) && is_array($decoded['@graph'])) {
        foreach ($decoded['@graph'] as $node) {
            if (is_array($node) && rich_snippets_type_includes_faqpage($node['@type'] ?? null)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @graph içinde birden fazla FAQPage varsa yalnız ilki kalır (GSC: alan yineleniyor).
 */
function rich_snippets_dedupe_faqpage_in_graph(array &$decoded): void
{
    if (empty($decoded['@graph']) || !is_array($decoded['@graph'])) {
        return;
    }
    $kept = false;
    $newGraph = [];
    foreach ($decoded['@graph'] as $node) {
        if (!is_array($node)) {
            $newGraph[] = $node;
            continue;
        }
        if (rich_snippets_type_includes_faqpage($node['@type'] ?? null)) {
            if ($kept) {
                continue;
            }
            $kept = true;
        }
        $newGraph[] = $node;
    }
    $decoded['@graph'] = $newGraph;
}

/**
 * @deprecated NOT USED IN PRODUCTION HEAD. DB tabanlı JSON-LD; admin/legacy. Head için schema_factory kullanılır.
 */
function getRichSnippets($page_types = 'global', $additional_data = [], array $exclude_snippet_types = [], bool $skip_all_faqpage_json_ld = false)
{
    global $conn;

    $snippets_html = '';

    // Önce tablo var mı kontrol et
    $table_check = $conn->query("SHOW TABLES LIKE 'rich_snippets'");
    if (!$table_check || $table_check->num_rows == 0) {
        // Tablo yoksa sessizce boş döndür
        return '';
    }

    if (!is_array($page_types)) {
        $page_types = [$page_types];
    }
    $page_types = array_values(array_unique(array_filter($page_types, static function ($t) {
        return is_string($t) && $t !== '';
    })));
    if ($page_types === []) {
        return '';
    }

    $exclude_upper = array_map('strtoupper', $exclude_snippet_types);

    $placeholders = implode(',', array_fill(0, count($page_types), '?'));
    $sql = 'SELECT * FROM rich_snippets WHERE status = 1 AND (page_type IN (' . $placeholders . ") OR page_type = 'global') ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return '';
    }
    $typesStr = str_repeat('s', count($page_types));
    $bindArgs = array_merge([$typesStr], $page_types);
    $refs = [];
    foreach ($bindArgs as $k => $_) {
        $refs[$k] = &$bindArgs[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $stmt->execute();
    if (function_exists('mysqli_stmt_fetch_all_assoc')) {
        $snippet_rows = mysqli_stmt_fetch_all_assoc($stmt);
    } else {
        $snippet_rows = [];
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $snippet_rows[] = $row;
            }
        }
    }
    $stmt->close();

    $faq_ld_emitted = false;

    if (!empty($snippet_rows)) {
        foreach ($snippet_rows as $snippet) {
            $stype = strtoupper((string) ($snippet['type'] ?? ''));
            if ($exclude_upper !== [] && in_array($stype, $exclude_upper, true)) {
                continue;
            }

            $schema_data = $snippet['data'];

            // Dinamik verileri değiştir (varsa)
            if (!empty($additional_data)) {
                foreach ($additional_data as $key => $value) {
                    $schema_data = str_replace('{{' . $key . '}}', $value, $schema_data);
                }
            }

            // JSON geçerliliğini kontrol et
            $decoded = json_decode($schema_data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $is_faq_block = rich_snippets_decoded_contains_faqpage($decoded);
                if ($skip_all_faqpage_json_ld && $is_faq_block) {
                    continue;
                }
                if ($is_faq_block && $faq_ld_emitted) {
                    continue;
                }
                if ($is_faq_block) {
                    rich_snippets_dedupe_faqpage_in_graph($decoded);
                }
                rich_snippets_sanitize_decoded($decoded);
                $enc = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($enc !== false) {
                    if ($is_faq_block) {
                        $faq_ld_emitted = true;
                    }
                    $snippets_html .= '<script type="application/ld+json">' . "\n";
                    $snippets_html .= $enc . "\n";
                    $snippets_html .= '</script>' . "\n";
                }
            }
        }
    }

    return $snippets_html;
}

/**
 * BlogPosting parçası (schema_factory parça modu ile aynı mantık). Tek başına head’e basılmamalıdır.
 *
 * @deprecated NOT USED IN PRODUCTION HEAD pipeline. Canlı şablonda structured_head_markup / schema_factory kullanılır.
 */
function getBlogPostSchema($post_data)
{
    if (empty($post_data)) {
        return '';
    }
    if (!function_exists('schema_factory') || !function_exists('canonical_seo_pipeline_core') || !function_exists('flex_content_resolver')) {
        return '';
    }

    global $site_settings;
    $ss = (isset($site_settings) && is_array($site_settings)) ? $site_settings : [];

    $slug = trim((string) ($post_data['slug'] ?? ''), '/');
    $rel = $slug !== '' ? '/' . $slug : '/';
    $pipeline = canonical_seo_pipeline_core([
        'relPath' => $rel,
        'get' => [],
        'page' => null,
        'blog' => $post_data,
        'site_settings' => $ss,
    ]);
    $origin = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
    $canonical = $slug !== '' ? $origin . '/' . rawurlencode($slug) : $origin . '/';
    $mcid = $origin !== '' ? $origin . '/#mynak-moving-company' : '';

    $flex = flex_content_resolver([
        'blog' => $post_data,
        'site_settings' => $ss,
        'page_title' => '',
        'canonical_page_type' => 'blog_post',
    ]);

    return schema_factory('blog_post', $pipeline, $flex, $canonical, $origin, $ss, $mcid, null, $post_data, null);
}

/**
 * BreadcrumbList JSON-LD (Google Rich Results / Search Central).
 *
 * @deprecated NOT USED IN PRODUCTION HEAD. Breadcrumb üretim yolu: schema_factory(full_head) → mynak_breadcrumb_schema_from_items.
 *
 * @param list<array{name:string,url:string}> $breadcrumbs
 */
function getBreadcrumbSchema($breadcrumbs)
{
    require_once __DIR__ . '/breadcrumb_jsonld.php';
    $schema = mynak_breadcrumb_schema_from_items($breadcrumbs);
    if ($schema === null) {
        return '';
    }
    if (function_exists('seo_runtime_ld_script_from_array')) {
        return seo_runtime_ld_script_from_array($schema);
    }
    $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return '';
    }

    return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
}

// -------------------------------------------------------
// Sayfa bazlı şemalar (page_schemas tablosu) — DEPRECATED for production head; yalnızca admin/AI araçları.
// Ön yüz JSON-LD: schema_factory (MYNAK_PRODUCTION_JSONLD_EMITTER_RULE). Bu tablo runtime head’e bağlanmamalıdır.
// -------------------------------------------------------
function ensurePageSchemasTable()
{
    global $conn;
    static $checked = false;
    if ($checked) return;
    $conn->query("CREATE TABLE IF NOT EXISTS `page_schemas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `page_type` varchar(50) NOT NULL COMMENT 'blog, service, homepage',
        `page_id`   int(11) DEFAULT NULL,
        `schema_type` varchar(100) NOT NULL,
        `schema_json` longtext NOT NULL,
        `status` tinyint(1) DEFAULT 1,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_page_schema` (`page_type`, `page_id`, `schema_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $checked = true;
}
