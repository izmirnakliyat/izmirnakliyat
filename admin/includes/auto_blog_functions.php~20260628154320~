<?php
// Otomatik Blog Modülü Yardımcı Fonksiyonlar

require_once __DIR__ . '/../../includes/blog_post_status.php';

function get_openai_api_key() {
    global $conn;
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'openai_api_key' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['value'] : '';
}

function save_openai_api_key($key) {
    global $conn;
    // Önce var mı kontrol et
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM settings WHERE name = 'openai_api_key'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['cnt'] > 0) {
        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'openai_api_key'");
        $stmt->bind_param("s", $key);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('openai_api_key', ?)");
        $stmt->bind_param("s", $key);
        $stmt->execute();
    }
}

function get_all_auto_blog_settings() {
    global $conn;
    $sql = "SELECT abs.*, c.ad as category_name FROM auto_blog_settings abs LEFT JOIN blog_categories c ON abs.category_id = c.id ORDER BY abs.id DESC";
    $result = $conn->query($sql);
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[] = $row;
    }
    return $settings;
}

function get_auto_blog_setting($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM auto_blog_settings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_all_categories() {
    global $conn;
    $result = $conn->query("SELECT id, ad FROM blog_categories ORDER BY ad ASC");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

/** Son add_auto_blog_setting hata metni (varsa). */
function ab_last_setting_error(): string
{
    return (string) ($GLOBALS['ab_setting_last_error'] ?? '');
}

/**
 * Canlı şema uyumu: manual_command, keywords TEXT, id AUTO_INCREMENT.
 */
function ensure_auto_blog_settings_schema(): void
{
    global $conn;
    static $done = false;
    if ($done) {
        return;
    }

    $tbl = $conn->query("SHOW TABLES LIKE 'auto_blog_settings'");
    if (!$tbl || $tbl->num_rows === 0) {
        $done = true;

        return;
    }

    if ($result = $conn->query("SHOW COLUMNS FROM auto_blog_settings LIKE 'manual_command'")) {
        if ($result->num_rows === 0) {
            @$conn->query("ALTER TABLE auto_blog_settings ADD COLUMN `manual_command` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL AFTER `keywords`");
        }
    }
    if ($col = $conn->query("SHOW COLUMNS FROM auto_blog_settings WHERE Field = 'keywords'")) {
        if ($row = $col->fetch_assoc()) {
            $type = strtolower((string) ($row['Type'] ?? ''));
            if (str_contains($type, 'varchar')) {
                @$conn->query('ALTER TABLE auto_blog_settings MODIFY keywords TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
            }
        }
    }

    // Canlıda id=0 satırı + AUTO_INCREMENT yok → Duplicate entry '0' for key PRIMARY
    if ($z = $conn->query("SELECT COUNT(*) AS c FROM auto_blog_settings WHERE id = 0")) {
        if ((int) ($z->fetch_assoc()['c'] ?? 0) > 0) {
            $mx = $conn->query('SELECT COALESCE(MAX(id), 0) AS m FROM auto_blog_settings');
            $next = (int) (($mx ? $mx->fetch_assoc()['m'] : 0) ?? 0) + 1;
            if ($next < 1) {
                $next = 1;
            }
            @$conn->query('UPDATE auto_blog_settings SET id = ' . $next . ' WHERE id = 0 LIMIT 1');
        }
    }
    if ($idCol = $conn->query("SHOW COLUMNS FROM auto_blog_settings WHERE Field = 'id'")) {
        if ($idRow = $idCol->fetch_assoc()) {
            $extra = strtolower((string) ($idRow['Extra'] ?? ''));
            if (!str_contains($extra, 'auto_increment')) {
                @$conn->query('ALTER TABLE auto_blog_settings MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY');
            }
        }
    }
    if ($ai = $conn->query('SELECT COALESCE(MAX(id), 0) + 1 AS n FROM auto_blog_settings')) {
        $nextAi = max(1, (int) ($ai->fetch_assoc()['n'] ?? 1));
        @$conn->query('ALTER TABLE auto_blog_settings AUTO_INCREMENT = ' . $nextAi);
    }

    $done = true;
}

function ab_category_exists(int $categoryId): bool
{
    global $conn;
    if ($categoryId <= 0) {
        return false;
    }
    $stmt = $conn->prepare('SELECT 1 FROM blog_categories WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res && $res->num_rows > 0;
}

function add_auto_blog_setting($data) {
    global $conn;
    $GLOBALS['ab_setting_last_error'] = '';
    ensure_auto_blog_settings_schema();

    $categoryId = (int) ($data['category_id'] ?? 0);
    if (!ab_category_exists($categoryId)) {
        $GLOBALS['ab_setting_last_error'] = 'Geçersiz category_id=' . $categoryId . ' (blog kategorisi yok).';

        return false;
    }

    $periodType = (string) ($data['period_type'] ?? 'daily');
    if (!in_array($periodType, ['daily', 'weekly', 'hourly'], true)) {
        $periodType = 'daily';
    }

    // Türkçe karakterleri korumak için UTF-8 encoding
    $keywords = mb_substr(mb_convert_encoding((string) $data['keywords'], 'UTF-8', 'auto'), 0, 2000);
    $manual_command = isset($data['manual_command'])
        ? mb_substr(mb_convert_encoding((string) $data['manual_command'], 'UTF-8', 'auto'), 0, 4000)
        : '';

    $stmt = $conn->prepare("INSERT INTO auto_blog_settings (category_id, keywords, manual_command, cover_image, min_words, max_words, post_count_per_period, period_type, post_time, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $GLOBALS['ab_setting_last_error'] = (string) $conn->error;

        return false;
    }
    $stmt->bind_param("isssiiissi",
        $categoryId,
        $keywords,
        $manual_command,
        $data['cover_image'],
        $data['min_words'],
        $data['max_words'],
        $data['post_count_per_period'],
        $periodType,
        $data['post_time'],
        $data['active']
    );
    if (!$stmt->execute()) {
        $GLOBALS['ab_setting_last_error'] = (string) ($stmt->error ?: $conn->error);

        return false;
    }

    return true;
}

function update_auto_blog_setting($id, $data) {
    global $conn;
    // Şema güvenliği: manual_command kolonu yoksa ekle
    if ($result = $conn->query("SHOW COLUMNS FROM auto_blog_settings LIKE 'manual_command'")) {
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE auto_blog_settings ADD COLUMN `manual_command` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL AFTER `keywords`");
        }
    }
    // Türkçe karakterleri korumak için UTF-8 encoding
    $keywords = mb_convert_encoding($data['keywords'], 'UTF-8', 'auto');
    $manual_command = isset($data['manual_command']) ? mb_convert_encoding($data['manual_command'], 'UTF-8', 'auto') : '';
    
    $stmt = $conn->prepare("UPDATE auto_blog_settings SET category_id=?, keywords=?, manual_command=?, cover_image=?, min_words=?, max_words=?, post_count_per_period=?, period_type=?, post_time=?, active=? WHERE id=?");
    $stmt->bind_param("isssiiissii",
        $data['category_id'],
        $keywords,
        $manual_command,
        $data['cover_image'],
        $data['min_words'],
        $data['max_words'],
        $data['post_count_per_period'],
        $data['period_type'],
        $data['post_time'],
        $data['active'],
        $id
    );
    return $stmt->execute();
}

function delete_auto_blog_setting($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM auto_blog_settings WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Veritabanı tablosunun karakter setini kontrol et ve düzelt
function ensure_utf8_encoding() {
    global $conn;
    
    // Tablo karakter setini kontrol et
    $result = $conn->query("SHOW CREATE TABLE auto_blog_settings");
    if ($result) {
        $row = $result->fetch_assoc();
        $create_table = $row['Create Table'];
        
        // Eğer tablo UTF-8 değilse, karakter setini değiştir
        if (strpos($create_table, 'utf8mb4') === false) {
            $conn->query("ALTER TABLE auto_blog_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }
    
    // Keywords sütununun karakter setini kontrol et
    $conn->query("ALTER TABLE auto_blog_settings MODIFY keywords TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}


// ═══════════════════════════════════════════════
// YENİ GÖREV SİSTEMİ FONKSİYONLARI
// ═══════════════════════════════════════════════

function ensure_auto_blog_tasks_table() {
    global $conn;
    $conn->query("CREATE TABLE IF NOT EXISTS `auto_blog_tasks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `topics` text NOT NULL COMMENT 'Her satırda bir konu',
        `schedule` varchar(50) NOT NULL DEFAULT 'daily' COMMENT 'hourly,twice_daily,daily,weekly',
        `articles_per_run` int(11) NOT NULL DEFAULT 1,
        `category_id` int(11) DEFAULT NULL,
        `tone` varchar(50) DEFAULT 'professional',
        `length` varchar(50) DEFAULT 'medium' COMMENT 'short|medium|long',
        `default_image` varchar(255) DEFAULT NULL,
        `status` tinyint(1) DEFAULT 1,
        `last_run` datetime DEFAULT NULL,
        `next_run` datetime DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function get_auto_blog_tasks() {
    global $conn;
    ensure_auto_blog_tasks_table();
    $res = $conn->query("SELECT t.*, c.ad as category_name
        FROM auto_blog_tasks t
        LEFT JOIN blog_categories c ON c.id = t.category_id
        ORDER BY t.id DESC");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function get_auto_blog_task($id) {
    global $conn;
    ensure_auto_blog_tasks_table();
    $stmt = $conn->prepare("SELECT * FROM auto_blog_tasks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function calculate_next_run($schedule, $from = null) {
    $from = $from ?: time();
    switch ($schedule) {
        case 'hourly':     return date('Y-m-d H:i:s', $from + 3600);
        case 'twice_daily':return date('Y-m-d H:i:s', $from + 43200);
        case 'weekly':     return date('Y-m-d H:i:s', $from + 604800);
        case 'daily':
        default:           return date('Y-m-d H:i:s', $from + 86400);
    }
}

function get_length_words($length) {
    switch ($length) {
        case 'short':  return ['min' => 600,  'max' => 800];
        case 'long':   return ['min' => 1500, 'max' => 2000];
        case 'medium':
        default:       return ['min' => 1000, 'max' => 1400];
    }
}

function get_tone_instruction($tone) {
    switch ($tone) {
        case 'casual':       return "Samimi, sıcak ve okuyucuya yakın bir dil kullan.";
        case 'academic':     return "Akademik, detaylı ve güvenilir kaynaklara dayalı bir ton kullan.";
        case 'seo':          return "SEO odaklı, anahtar kelime yoğunluğu yüksek, bilgilendirici bir ton kullan.";
        case 'storytelling': return "Hikaye anlatıcı, akıcı, sürükleyici ve okuyucuyu içine çeken bir ton kullan.";
        case 'professional':
        default:             return "Profesyonel, bilgilendirici ve güvenilir bir ton kullan.";
    }
}

/**
 * Ana blog yazısı üretim fonksiyonu - görev sistemi için
 * @return array ['success'=>bool, 'post_id'=>int, 'title'=>string, 'message'=>string]
 */
function generate_blog_post_from_task($task, $topic) {
    global $conn;
    $api_key = get_openai_api_key();
    $model   = get_openai_model();
    if (!$api_key) return ['success' => false, 'message' => 'OpenAI API anahtarı yok.'];

    $words       = get_length_words($task['length']);
    $tone_instr  = get_tone_instruction($task['tone']);
    $min_words   = $words['min'];
    $max_words   = $words['max'];
    $max_tokens  = min(4096, (int)($max_words * 1.5) + 500);

    $prompt = <<<PROMPT
Aşağıdaki konu hakkında SEO uyumlu, özgün ve bilgilendirici Türkçe bir blog yazısı yaz.

KONU: {$topic}

GEREKSINIMLER:
- Dil: Türkçe
- Kelime sayısı: {$min_words} ile {$max_words} kelime arasında (bu zorunlu)
- {$tone_instr}
- Yazı mutlaka H2 ve H3 başlıkları içermeli
- Giriş, gelişme ve sonuç bölümleri olmalı
- SEO uyumlu: anahtar kelime doğal olarak yerleştirilmeli
- Sadece HTML döndür (h2, h3, p, ul, li, strong etiketleri)
- Kod bloğu işaretleri (```) KULLANMA
- Yazının başına veya sonuna meta bilgisi, açıklama ekleme

İÇERİK YAPISI:
<h2>Giriş başlığı</h2>
<p>...</p>
<h2>Ana bölüm 1</h2>
<p>...</p>
<h3>Alt bölüm</h3>
...
<h2>Sonuç</h2>
<p>...</p>
PROMPT;

    $payload = [
        'model'       => $model,
        'messages'    => [
            ['role' => 'system', 'content' => 'Sen profesyonel bir Türkçe içerik yazarısın. SEO uyumlu, özgün ve uzun blog yazıları yazarsın. Sadece HTML formatında içerik döndürürsün.'],
            ['role' => 'user',   'content' => $prompt],
        ],
        'max_tokens'  => $max_tokens,
        'temperature' => 0.75,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key],
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    if ($cerr || $code !== 200) {
        $api_msg = json_decode($resp, true)['error']['message'] ?? 'HTTP ' . $code;
        return ['success' => false, 'message' => 'API hatası: ' . ($cerr ?: $api_msg)];
    }

    $data    = json_decode($resp, true);
    $content = trim($data['choices'][0]['message']['content'] ?? '');
    if (empty($content)) return ['success' => false, 'message' => 'API boş yanıt döndü.'];

    // Markdown temizle
    $content = preg_replace('/^```[a-zA-Z0-9_\-]*[ \t]*\r?\n/m', '', $content);
    $content = preg_replace('/^```[ \t]*\r?\n?/m', '', $content);
    $content = str_replace('```', '', $content);
    $content = trim($content);

    // Başlığı çıkar (ilk H1 veya H2)
    $title = $topic;
    if (preg_match('/<h[12][^>]*>(.*?)<\/h[12]>/i', $content, $m)) {
        $title = trim(strip_tags($m[1]));
        $content = preg_replace('/<h[12][^>]*>.*?<\/h[12]>/i', '', $content, 1);
        $content = trim($content);
    }

    // Slug oluştur
    require_once __DIR__ . '/../../includes/functions.php';
    $slug_base = slug_olustur($title);
    $slug = $slug_base;
    $suffix = 1;
    while ($conn->query("SELECT id FROM blog_posts WHERE slug = '{$conn->real_escape_string($slug)}'")->num_rows > 0) {
        $slug = $slug_base . '-' . $suffix++;
    }

    // Kapak fotoğrafı
    $kapak = $task['default_image'] ?? '';
    if (!empty($kapak) && strpos($kapak, 'media/') !== 0) {
        $kapak = 'media/' . ltrim($kapak, '/');
    }

    // DB'ye kaydet — EDİTÖR AKIŞI: durum=1 (editör kuyruğu)
    $cat = (int)($task['category_id'] ?? 0);
    $meta_desc = mb_substr(strip_tags($content), 0, 160);
    $default_status = auto_blog_get_default_status();
    $picked_author  = auto_blog_pick_author_id($cat);          // Kategori bazlı yazar
    $is_ai          = 1;                                       // AI üretimi
    $quality        = auto_blog_quality_score($content);

    $stmt = $conn->prepare(
        "INSERT INTO blog_posts
            (baslik, icerik, kapak_foto, kategori_id, slug, meta_description,
             durum, author_id, is_ai_generated, ai_quality_score,
             created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->bind_param(
        "sssissiiii",
        $title, $content, $kapak, $cat, $slug, $meta_desc,
        $default_status, $picked_author, $is_ai, $quality
    );
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'DB kayıt hatası: ' . $stmt->error];
    }
    $post_id = $conn->insert_id;

    $msg = $default_status === MYNAK_BLOG_STATUS_EDITOR_QUEUE
        ? 'Üretildi → Editör kuyruğuna alındı.'
        : 'Başarıyla oluşturuldu.';

    return ['success' => true, 'post_id' => $post_id, 'title' => $title, 'message' => $msg, 'status' => $default_status];
}

// ═══════════════════════════════════════════════
// EDİTÖR AKIŞI (Editorial Workflow) HELPERS
// ═══════════════════════════════════════════════

/**
 * Yeni üretilen AI yazılarının default durumunu döner.
 *   0 = Taslak
 *   1 = Editör kuyruğu
 *   3 = Yayında (doğrudan — önerilmez; settings’ten)
 */
function auto_blog_get_default_status(): int {
    global $conn;
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'auto_blog_default_status' LIMIT 1");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $val = $row ? (int) $row['value'] : MYNAK_BLOG_STATUS_EDITOR_QUEUE;
    if (in_array($val, [MYNAK_BLOG_STATUS_DRAFT, MYNAK_BLOG_STATUS_EDITOR_QUEUE, MYNAK_BLOG_STATUS_PUBLISHED], true)) {
        return $val;
    }
    return MYNAK_BLOG_STATUS_EDITOR_QUEUE;
}

/**
 * Editör akışı açık mı?
 */
function auto_blog_editorial_workflow_enabled(): bool {
    global $conn;
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'editorial_workflow_enabled' LIMIT 1");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && (int) $row['value'] === 1;
}

/**
 * Kategoriye göre AI üretiminde ön-atanacak yazarı seçer.
 *   Editör masasında değiştirilebilir.
 */
function auto_blog_pick_author_id(?int $category_id): ?int {
    global $conn;

    // Kategori bazlı eşleme (slug → yazar slug'ı):
    $catToAuthorSlug = [
        4  => 'nakliye-uzmani',      // Evden Eve Nakliyat
        5  => 'site-editoru',        // Haberler
        6  => 'nakliye-uzmani',      // Asansörlü Nakliyat
        7  => 'site-editoru',        // Duyurular
        8  => 'nakliye-uzmani',      // Ofis Taşıma
        9  => 'nakliye-uzmani',      // Sepetli Vinç
        10 => 'nakliye-uzmani',      // Parça Eşya
        11 => 'nakliye-ekspertizi',  // Şehirler Arası
        12 => 'nakliye-uzmani',      // Eşya Depolama
    ];
    $authorSlug = $catToAuthorSlug[(int) $category_id] ?? null;
    if (!$authorSlug) {
        // Fallback: settings.auto_blog_default_author_slug → authors tablosu
        $stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'auto_blog_default_author_slug' LIMIT 1");
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $authorSlug = $row && !empty($row['value']) ? $row['value'] : 'site-editoru';
    }
    $stmt = $conn->prepare("SELECT id FROM authors WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->bind_param('s', $authorSlug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return (int) $row['id'];

    // Son fallback: is_default=1
    $row = $conn->query("SELECT id FROM authors WHERE is_default = 1 AND status = 1 LIMIT 1")->fetch_assoc();
    return $row ? (int) $row['id'] : null;
}

/**
 * Yapı skoru (0–100) — ai_quality_score kolonu; CE editorial/QC ayrı.
 */
function auto_blog_quality_score(string $html): int
{
    if (!function_exists('mynak_ce_structure_score')) {
        require_once __DIR__ . '/../../includes/mynak_ce_quality_model.php';
    }

    return mynak_ce_structure_score($html);
}

// OpenAI Model yönetimi
function get_openai_model() {
    global $conn;
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'openai_model' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['value'] : 'gpt-4o-mini';
}

function save_openai_model($model) {
    global $conn;
    $allowed = ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'];
    if (!in_array($model, $allowed)) {
        $model = 'gpt-4o-mini';
    }
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM settings WHERE name = 'openai_model'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['cnt'] > 0) {
        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'openai_model'");
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('openai_model', ?)");
    }
    $stmt->bind_param("s", $model);
    return $stmt->execute();
}