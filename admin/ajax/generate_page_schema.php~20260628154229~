<?php
/**
 * Admin: page_schemas tablosu / AI ile şema üretimi.
 * DEPRECATED — NOT USED IN PRODUCTION HEAD. Canlı site JSON-LD yalnızca schema_factory() (seo_runtime) ile basılır;
 * bu endpoint çıktısı runtime head pipeline’ına bağlanmamalıdır.
 */
@set_time_limit(120);
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';
require_once '../includes/auto_blog_functions.php';
require_once '../../includes/rich_snippets.php';

header('Content-Type: application/json; charset=utf-8');

ensurePageSchemasTable();

$action    = $_POST['action']    ?? '';
$page_type = $_POST['page_type'] ?? '';
$page_id   = (int)($_POST['page_id'] ?? 0);

// Site ayarlarını çek
function getSiteSettings() {
    global $conn;
    $res = $conn->query("SELECT name, value FROM settings");
    $s = [];
    if ($res) while ($r = $res->fetch_assoc()) $s[$r['name']] = $r['value'];
    return $s;
}

// Şemayı kaydet (upsert)
function saveSchema($page_type, $page_id_val, $schema_type, $schema_json) {
    global $conn;
    if ($page_id_val) {
        $stmt = $conn->prepare("INSERT INTO page_schemas (page_type, page_id, schema_type, schema_json, status)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE schema_json = VALUES(schema_json), updated_at = NOW(), status = 1");
        $stmt->bind_param("siis", $page_type, $page_id_val, $schema_type, $schema_json);
    } else {
        $stmt = $conn->prepare("INSERT INTO page_schemas (page_type, page_id, schema_type, schema_json, status)
            VALUES (?, NULL, ?, ?, 1)
            ON DUPLICATE KEY UPDATE schema_json = VALUES(schema_json), updated_at = NOW(), status = 1");
        $stmt->bind_param("sss", $page_type, $schema_type, $schema_json);
    }
    return $stmt->execute();
}

// AI ile FAQPage üret
function generateFAQWithAI($title, $content) {
    $api_key = get_openai_api_key();
    $model   = get_openai_model();
    if (!$api_key) return null;

    $plain = strip_tags($content);
    $plain = preg_replace('/\s+/', ' ', $plain);
    if (mb_strlen($plain) > 6000) $plain = mb_substr($plain, 0, 6000);

    $prompt = <<<PROMPT
Aşağıdaki içerikten, kullanıcıların gerçekten sorabileceği 5-7 adet özgün soru-cevap çifti oluştur.
Sadece geçerli JSON döndür. Açıklama veya markdown ekleme.

Çıktı formatı (JSON-LD FAQPage):
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Soru metni?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Cevap metni (en az 2 cümle, bilgilendirici)."
      }
    }
  ]
}

BAŞLIK: {$title}

İÇERİK:
{$plain}
PROMPT;

    $post_data = [
        'model'    => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Sen SEO uzmanı bir Türkçe içerik editörüsün. Verilen içerikten schema.org FAQPage JSON-LD üretirsin. Sadece geçerli JSON döndürürsün.'],
            ['role' => 'user',   'content' => $prompt],
        ],
        'max_tokens'  => 1500,
        'temperature' => 0.5,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($post_data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key],
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    $data    = json_decode($resp, true);
    $content = trim($data['choices'][0]['message']['content'] ?? '');

    // Markdown temizle
    $content = preg_replace('/^```[a-z]*\s*/i', '', $content);
    $content = preg_replace('/```\s*$/', '', $content);
    $content = trim($content);

    // JSON geçerliliğini doğrula
    $decoded = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

// ─────────────────────────────────────────────
// Genel şemalar (Organization, LocalBusiness, WebSite) — AI yok
// ─────────────────────────────────────────────
if ($action === 'generate_general') {
    $s = getSiteSettings();
    $name     = defined('MYNAK_BRAND_NAME') ? MYNAK_BRAND_NAME : 'MY Nakliyat';
    $phone    = $s['phone1']      ?? $s['phone2'] ?? '';
    $address  = $s['address']     ?? '';
    $email    = $s['email']       ?? '';
    $logo_url = SITE_URL . '/uploads/settings/' . ($s['logo_light'] ?? 'logo.png');
    $lat      = $s['map_lat']     ?? '';
    $lng      = $s['map_lng']     ?? '';

    // Organization
    $org = [
        "@context"     => "https://schema.org",
        "@type"        => "Organization",
        "name"         => $name,
        "url"          => SITE_URL,
        "logo"         => ["@type" => "ImageObject", "url" => $logo_url],
        "contactPoint" => [
            "@type"       => "ContactPoint",
            "telephone"   => $phone,
            "contactType" => "customer service",
            "availableLanguage" => "Turkish"
        ],
    ];
    if ($email)   $org["email"] = $email;
    if ($address) $org["address"] = ["@type" => "PostalAddress", "streetAddress" => $address, "addressCountry" => "TR"];
    saveSchema('global', null, 'Organization', json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    // LocalBusiness
    $lb = [
        "@context"    => "https://schema.org",
        "@type"       => "MovingCompany",
        "name"        => $name,
        "url"         => SITE_URL,
        "image"       => $logo_url,
        "telephone"   => $phone,
        "priceRange"  => "$$",
        "address"     => ["@type" => "PostalAddress", "streetAddress" => $address, "addressCountry" => "TR"],
        "openingHoursSpecification" => [
            ["@type" => "OpeningHoursSpecification", "dayOfWeek" => ["Monday","Tuesday","Wednesday","Thursday","Friday"], "opens" => "08:00", "closes" => "19:00"],
            ["@type" => "OpeningHoursSpecification", "dayOfWeek" => "Saturday", "opens" => "09:00", "closes" => "17:00"]
        ],
    ];
    if ($lat && $lng) $lb["geo"] = ["@type" => "GeoCoordinates", "latitude" => $lat, "longitude" => $lng];
    if ($email) $lb["email"] = $email;
    saveSchema('global', null, 'MovingCompany', json_encode($lb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    // WebSite
    $ws = [
        "@context"        => "https://schema.org",
        "@type"           => "WebSite",
        "name"            => $name,
        "url"             => SITE_URL,
        "description"     => $s['site_description'] ?? $s['short_description'] ?? "Profesyonel nakliyat hizmetleri",
        "inLanguage"      => "tr",
        "potentialAction" => [
            "@type"       => "SearchAction",
            "target"      => ["@type" => "EntryPoint", "urlTemplate" => SITE_URL . "/blog?search={search_term_string}"],
            "query-input" => "required name=search_term_string"
        ],
    ];
    saveSchema('homepage', null, 'WebSite', json_encode($ws, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'message' => 'Organization, MovingCompany ve WebSite şemaları oluşturuldu.']);
    exit;
}

// ─────────────────────────────────────────────
// Blog yazısı için AI FAQPage
// ─────────────────────────────────────────────
if ($action === 'generate_blog_faq' && $page_id) {
    $stmt = $conn->prepare("SELECT id, baslik, icerik FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $page_id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) { echo json_encode(['success' => false, 'message' => 'Blog yazısı bulunamadı.']); exit; }

    $faq_json = generateFAQWithAI($post['baslik'], $post['icerik']);
    if (!$faq_json) { echo json_encode(['success' => false, 'message' => 'AI yanıt vermedi veya geçersiz JSON üretildi.']); exit; }

    saveSchema('blog', $page_id, 'FAQPage', $faq_json);
    echo json_encode(['success' => true, 'message' => 'FAQPage şeması oluşturuldu.', 'schema' => $faq_json]);
    exit;
}

// ─────────────────────────────────────────────
// Hizmet için AI FAQPage
// ─────────────────────────────────────────────
if ($action === 'generate_service_faq' && $page_id) {
    $stmt = $conn->prepare("SELECT id, ana_baslik, aciklama FROM services WHERE id = ?");
    $stmt->bind_param("i", $page_id);
    $stmt->execute();
    $svc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$svc) { echo json_encode(['success' => false, 'message' => 'Hizmet bulunamadı.']); exit; }

    $faq_json = generateFAQWithAI($svc['ana_baslik'], $svc['aciklama'] ?? $svc['ana_baslik']);
    if (!$faq_json) { echo json_encode(['success' => false, 'message' => 'AI yanıt vermedi.']); exit; }

    saveSchema('service', $page_id, 'FAQPage', $faq_json);
    echo json_encode(['success' => true, 'message' => 'Hizmet FAQPage şeması oluşturuldu.', 'schema' => $faq_json]);
    exit;
}

// ─────────────────────────────────────────────
// Tüm blog yazıları için toplu oluştur (ID listesi döndür)
// ─────────────────────────────────────────────
if ($action === 'list_blogs') {
    $rows = $conn->query("SELECT bp.id, bp.baslik,
        (SELECT COUNT(*) FROM page_schemas ps WHERE ps.page_type='blog' AND ps.page_id=bp.id AND ps.status=1) as has_schema
        FROM blog_posts bp ORDER BY bp.id ASC")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'posts' => $rows]);
    exit;
}

if ($action === 'list_services') {
    $rows = $conn->query("SELECT s.id, s.ana_baslik as baslik,
        (SELECT COUNT(*) FROM page_schemas ps WHERE ps.page_type='service' AND ps.page_id=s.id AND ps.status=1) as has_schema
        FROM services s ORDER BY s.id ASC")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'services' => $rows]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz action: ' . $action]);
