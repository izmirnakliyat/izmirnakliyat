<?php
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/includes/functions.php';
}
if (!function_exists('mynak_blok_isle')) {
    require_once __DIR__ . '/includes/pipeline/page_content_blocks.php';
}

// Form gönderimi işleme
$form_submitted = false;
$form_success = false;
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_id'])) {
    $form_submitted = true;
    $form_id = intval($_POST['form_id']);
    $form_data = [];
    
    // CSRF koruması
    $postedTok = $_POST['csrf_token'] ?? '';
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || !is_string($postedTok)
        || !hash_equals($_SESSION['csrf_token'], $postedTok)
    ) {
        $form_errors[] = 'Güvenlik doğrulaması başarısız';
    } else {
        // Form bilgilerini al
        $stmt = $conn->prepare("SELECT * FROM forms WHERE id = ? AND status = 1");
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $form = mysqli_stmt_fetch_assoc_first($stmt);
        $stmt->close();
        
        if ($form) {
            $fields = json_decode($form['fields'], true) ?: [];
            
            // Alanları doğrula
            foreach ($fields as $field) {
                $field_name = $field['id'];
                $field_value = $_POST[$field_name] ?? '';
                
                // Zorunlu alan kontrolü
                if ($field['required'] && empty($field_value)) {
                    $form_errors[] = $field['label'] . ' alanı zorunludur';
                    continue;
                }
                
                // Email doğrulaması
                if ($field['type'] === 'email' && !empty($field_value) && !filter_var($field_value, FILTER_VALIDATE_EMAIL)) {
                    $form_errors[] = $field['label'] . ' geçerli bir email adresi olmalıdır';
                    continue;
                }
                
                // Telefon doğrulaması
                if ($field['type'] === 'tel' && !empty($field_value) && !preg_match('/^[\d\s\+\-\(\)]+$/', $field_value)) {
                    $form_errors[] = $field['label'] . ' geçerli bir telefon numarası olmalıdır';
                    continue;
                }
                
                // Dosya yükleme işlemi
                if ($field['type'] === 'file' && !empty($_FILES[$field_name]['name'])) {
                    $upload_dir = 'uploads/form_attachments/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
                    
                    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                        $form_errors[] = $field['label'] . ' için geçersiz dosya türü';
                        continue;
                    }
                    
                    if ($_FILES[$field_name]['size'] > 5 * 1024 * 1024) { // 5MB limit
                        $form_errors[] = $field['label'] . ' dosyası çok büyük (max 5MB)';
                        continue;
                    }
                    
                    $filename = time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES[$field_name]['tmp_name'], $upload_path)) {
                        $field_value = $filename;
                    } else {
                        $form_errors[] = $field['label'] . ' dosyası yüklenirken hata oluştu';
                        continue;
                    }
                }
                
                $form_data[$field_name] = $field_value;
            }
            
            // Hata yoksa veritabanına kaydet
            if (empty($form_errors)) {
                // form_submissions tablosunda form_id alanını kontrol et
                $check_column = $conn->query("SHOW COLUMNS FROM form_submissions LIKE 'form_id'");
                if ($check_column->num_rows == 0) {
                    $conn->query("ALTER TABLE form_submissions ADD COLUMN form_id INT(11) NULL AFTER id");
                }
                
                $submission_data = json_encode($form_data, JSON_UNESCAPED_UNICODE);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt = $conn->prepare("INSERT INTO form_submissions (form_id, form_data, ip_address, user_agent, submission_date, status) VALUES (?, ?, ?, ?, NOW(), 0)");
                $stmt->bind_param("isss", $form_id, $submission_data, $ip_address, $user_agent);
                
                if ($stmt->execute()) {
                    $form_success = true;
                    
                    // Email bildirimi gönder
                    if (!empty($form['email_notifications'])) {
                        $email_body = "Yeni form başvurusu alındı:\n\n";
                        $email_body .= "Form: " . $form['title'] . "\n";
                        $email_body .= "Tarih: " . date('d.m.Y H:i') . "\n\n";
                        
                        foreach ($form_data as $field_id => $value) {
                            $field = array_filter($fields, function($f) use ($field_id) {
                                return $f['id'] === $field_id;
                            });
                            $field = reset($field);
                            if ($field) {
                                $email_body .= $field['label'] . ": " . $value . "\n";
                            }
                        }
                        
                        $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        
                        @mail($form['email_notifications'], 'Yeni Form Başvurusu - ' . $form['title'], $email_body, $headers);
                    }
                } else {
                    $form_errors[] = 'Form gönderilirken bir hata oluştu';
                }
            }
        } else {
            $form_errors[] = 'Form bulunamadı';
        }
    }
}

// Doğrudan erişim kontrolü: slug-router üzerinden gelmiyorsa SEO dostu URL'ye 301 yönlendir
if (!isset($conn)) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/includes/functions.php';

    if (isset($_GET['slug']) && !empty($_GET['slug'])) {
        $slug = trim((string) $_GET['slug']);
        require_once __DIR__ . '/includes/pipeline/city_pair_landings.php';
        if (mynak_city_pair_landing_data($slug) !== null) {
            header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($slug)), true, 301);
            exit;
        }
        if (in_array($slug, ['tasinma-kontrol-listesi', 'tasinma-kontrol-listesi-icerik', 'rehber-google-isletme-gonderileri'], true)) {
            header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($slug)), true, 301);
            exit;
        }
        $rq = $conn->prepare('SELECT slug FROM pages WHERE slug = ? AND status = 1 LIMIT 1');
        if ($rq) {
            $rq->bind_param('s', $slug);
            $rq->execute();
            $rrow = mysqli_stmt_fetch_assoc_first($rq);
            $rq->close();
            if ($rrow) {
                header('Location: ' . mynak_abs_url_from_public_path(mynak_public_path($rrow['slug'])), true, 301);
                exit;
            }
        }
    }

    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>404</title></head><body><h1>404 - İçerik Bulunamadı</h1></body></html>';
    exit;
}

// Eğer $page değişkeni henüz set edilmemişse (slug-router.php'den gelmiyorsa)
if (!isset($page) || empty($page)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>404</title></head><body><h1>404 - İçerik Bulunamadı</h1><p>Aradığınız sayfa bulunamadı.</p></body></html>';
    exit;
}

$__pageSlug = function_exists('mb_strtolower')
    ? mb_strtolower(trim((string) ($page['slug'] ?? '')), 'UTF-8')
    : strtolower(trim((string) ($page['slug'] ?? '')));

if (!isset($site_settings) || !is_array($site_settings)) {
    if (!function_exists('mynak_site_settings_bootstrap')) {
        require_once __DIR__ . '/includes/functions.php';
    }
    $site_settings = mynak_site_settings_bootstrap($conn);
}

require_once __DIR__ . '/includes/mynak_meta_description.php';
$__ilceBoostFile = __DIR__ . '/includes/pipeline/ilce_pricing_boost.php';
if (is_file($__ilceBoostFile)) {
    require_once $__ilceBoostFile;
    if (function_exists('mynak_ilce_pricing_boost_has') && mynak_ilce_pricing_boost_has($__pageSlug)) {
        if (!isset($mynak_head_stylesheets) || !is_array($mynak_head_stylesheets)) {
            $mynak_head_stylesheets = [];
        }
        $mynak_head_stylesheets[] = ASSET_PATH . 'css/pricing-landing.css';
    }
}

// Sayfa verisi çekildikten hemen sonra ekle:
if (!empty($page['seo_title'])) {
    $page_title = mynak_normalize_db_text(trim((string) $page['seo_title']));
    if (function_exists('mynak_normalize_public_page_title')) {
        $page_title = mynak_normalize_public_page_title($page_title);
    }
} elseif (isset($page['title'])) {
    $page_title = function_exists('mynak_build_page_title')
        ? mynak_build_page_title(mynak_normalize_db_text((string) $page['title']))
        : mynak_normalize_db_text(trim((string) $page['title']));
}

if ($__pageSlug === 'hakkimizda') {
    $page_title = function_exists('mynak_build_page_title')
        ? mynak_build_page_title('Hakkımızda')
        : 'Hakkımızda | MY Nakliyat';
}

// SEO meta bilgilerini ayarla
$page_meta_description = '';
$page_meta_keywords = '';

if (!empty($page['meta_description'])) {
    $page_meta_description = mynak_meta_description_clamp(mynak_normalize_db_text(trim((string) $page['meta_description'])), 160);
} else {
    $page_meta_description = mynak_default_meta_description_for_page(
        mynak_normalize_db_text((string) ($page['title'] ?? '')),
        $__pageSlug
    );
}

if (!empty($page['meta_keywords'])) {
    $page_meta_keywords = $page['meta_keywords'];
} else {
    // Varsayılan anahtar kelimeler
    $page_meta_keywords = "my nakliyat, nakliyat, evden eve nakliyat, " . strtolower($page['title']);
}

// Debug için
// // echo "<!-- DEBUG: Sayfa başlığı: $page_title -->";
// // echo "<!-- DEBUG: Meta açıklama: $page_meta_description -->";
// // echo "<!-- DEBUG: Meta keywords: $page_meta_keywords -->";

require_once __DIR__ . '/includes/header.php';
?>

<main id="content">
<!-- Sayfa Banner -->
<section class="page-banner" style="background-color: #f8f9fa; padding: 80px 0 40px;">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1><?php echo mynak_esc_html((string) ($page['title'] ?? '')); ?></h1>
            </div>
        </div>
    </div>
</section>

<!-- Sayfa İçeriği -->
<section class="page-content py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-md-5">
                        <div class="page-text">
                            <?php
                            $__ilceOpenFile = __DIR__ . '/includes/pipeline/ilce_unique_opening.php';
                            if (is_file($__ilceOpenFile)) {
                                require_once $__ilceOpenFile;
                                if (function_exists('mynak_ilce_unique_opening_html')) {
                                    echo mynak_ilce_unique_opening_html($__pageSlug);
                                }
                            }
                            $content = $page['content'] ?? '';
                            // Banner'da tek <h1>; gövde + kısa kodlar pipeline içinde h1→h2 normalize edilir
                            $GLOBALS['mynak_img_alt_context'] = trim((string) ($page['title'] ?? '')) . ' — MY Nakliyat';
                            echo mynak_blok_isle($conn, $content);
                            unset($GLOBALS['mynak_img_alt_context']);
                            if (function_exists('mynak_ilce_pricing_boost_html')) {
                                echo mynak_ilce_pricing_boost_html($__pageSlug);
                            }
                            ?>
                        </div>
                        <?php
                        $__mynakUpdatedRaw = !empty($page['updated_at'])
                            ? (string) $page['updated_at']
                            : (string) ($page['created_at'] ?? '');
                        $__mynakUpdatedTs = $__mynakUpdatedRaw !== '' ? strtotime($__mynakUpdatedRaw) : false;
                        if ($__mynakUpdatedTs !== false):
                            $__mynakUpdatedIso = date('c', $__mynakUpdatedTs);
                            $__mynakUpdatedTr = date('d.m.Y', $__mynakUpdatedTs);
                        ?>
                        <div class="page-meta mt-5 text-muted">
                            <small>Son güncelleme: <time datetime="<?php echo htmlspecialchars($__mynakUpdatedIso, ENT_QUOTES, 'UTF-8'); ?>" itemprop="dateModified"><?php echo $__mynakUpdatedTr; ?></time></small>
                        </div>
                        <?php endif; ?>
                        <?php
                        if (function_exists('seo_runtime_cluster_context_links_html') && isset($mynak_seo_pipeline) && is_array($mynak_seo_pipeline)) {
                            echo seo_runtime_cluster_context_links_html($page, $mynak_seo_pipeline);
                        }
                        if (function_exists('seo_runtime_primary_services_links_html')) {
                            echo seo_runtime_primary_services_links_html($__pageSlug);
                        }
                        ?>
                    </div>
                </div>

                <?php
                // E-E-A-T trust badges — yalnizca Hakkimizda sayfasinda gorunur.
                // AI Overview / ChatGPT / Perplexity icin gorunur "kanit" katmani.
                if (in_array($__pageSlug, ['hakkimizda', 'hakkimda', 'about', 'about-us'], true)):
                    $__trustBadges = [
                        ['icon' => 'bi-patch-check-fill',   'title' => 'ISO 9001 Belgeli (2024)',     'sub' => 'ISO 9001 Belgeli ilk nakliye firması'],
                        ['icon' => 'bi-award-fill',         'title' => 'Güvenilir Marka Ödüllü',     'sub' => '2018, 2020, 2022 — Güvenilir Marka Ödülleri'],
                        ['icon' => 'bi-trophy-fill',        'title' => 'Şehirler Arası Nakliye Ödülü','sub' => '2023 — En İyi Şehirler Arası Nakliyat Firması'],
                        ['icon' => 'bi-star-fill',          'title' => '5,0 / 5 — 270+ Yorum',        'sub' => 'Google Business Profile (doğrulanmış)'],
                        ['icon' => 'bi-shield-check',       'title' => 'Sigortalı Taşıma',            'sub' => 'Nakliye sigortası + yazılı sözleşme'],
                        ['icon' => 'bi-file-earmark-text',  'title' => 'Yazılı Teklif Garantisi',     'sub' => 'Keşif sonrası net fiyat, gizli ücret yok'],
                        ['icon' => 'bi-people-fill',        'title' => 'Kadrolu Profesyonel Ekip',    'sub' => 'MY Nakliyat logolu araç + uzman ekip'],
                        ['icon' => 'bi-geo-alt-fill',       'title' => 'İzmir + 81 İl Hizmet',        'sub' => 'İzmir 30 ilçe + Türkiye geneli'],
                        ['icon' => 'bi-medal-fill',         'title' => 'Türkiye Altın Marka (2016)',  'sub' => '2016 Türkiye Altın Marka Ödülü'],
                    ];
                ?>
                <div class="card border-0 shadow-sm mt-4 mynak-trust-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                            <h2 class="h4 mb-0 mynak-trust-title">Neden MY Nakliyat?</h2>
                            <span class="badge rounded-pill mynak-trust-badge-pill"><i class="bi bi-google me-1"></i>Google'da 5,0 ★ — 270+ yorum</span>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($__trustBadges as $b): ?>
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <div class="d-flex align-items-start mynak-trust-item h-100">
                                        <div class="mynak-trust-icon flex-shrink-0">
                                            <i class="bi <?php echo htmlspecialchars($b['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                        </div>
                                        <div class="ms-3">
                                            <div class="fw-semibold mynak-trust-item-title"><?php echo htmlspecialchars($b['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($b['sub'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 pt-3 border-top small text-muted">
                            Tüm taşımalar yazılı sözleşme + nakliye sigortası kapsamındadır. Detay için
                            <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . '/belgelerimiz', ENT_QUOTES, 'UTF-8'); ?>">Belgelerimiz</a>
                            ve <a href="<?php echo htmlspecialchars(rtrim((string) SITE_URL, '/') . '/iletisim', ENT_QUOTES, 'UTF-8'); ?>">İletişim</a> sayfalarını ziyaret edebilirsiniz.
                        </div>
                    </div>
                </div>
                <?php
                // AboutPage JSON-LD: yalnizca <head> schema_factory (MYNAK_PRODUCTION_JSONLD_EMITTER_RULE) — cift script yok.
                endif; ?>

                <?php
                // Ekibimiz — team_members; Person JSON-LD yalnizca <head> schema_factory (page_type=team)
                if ($__pageSlug === 'ekibimiz') {
                    if (!function_exists('mynak_initials_from_name')) {
                        require_once __DIR__ . '/includes/functions.php';
                    }
                    $teamRows = [];
                    if (isset($conn) && $conn instanceof mysqli) {
                        $tq = $conn->query(
                            "SELECT id, ad, unvan, aciklama, foto, email FROM team_members WHERE durum = 1 ORDER BY order_number ASC, id ASC"
                        );
                        if ($tq) {
                            while ($tr = $tq->fetch_assoc()) {
                                $teamRows[] = $tr;
                            }
                        }
                    }
                    $__absTeam = rtrim((string) SITE_URL, '/');
                    ?>
                <div class="card border-0 shadow-sm mt-4 mynak-team-card">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 mb-2 mynak-trust-title">Yönetim &amp; operasyon ekibi</h2>
                        <p class="text-muted small mb-4">MY Nakliyat’ın İzmir ve şehirler arası operasyonlarını yöneten temel ekip üyeleri. Fotoğraflar yönetim panelinden güncellenebilir.</p>
                        <div class="row g-4">
                            <?php if ($teamRows === []): ?>
                                <p class="text-muted mb-0">Ekip profilleri yükleniyor. Lütfen kısa süre sonra yenileyin.</p>
                            <?php else: ?>
                                <?php foreach ($teamRows as $tm): ?>
                                    <?php
                                    $imgPath = trim((string) ($tm['foto'] ?? ''));
                                    $localTeam = $imgPath !== ''
                                        ? (__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imgPath))
                                        : '';
                                    $hasImg = $imgPath !== '' && $localTeam !== '' && is_file($localTeam);
                                    $nameTeam = (string) $tm['ad'];
                                    $ini = mynak_initials_from_name($nameTeam);
                                    ?>
                                    <div class="col-12 col-sm-6 col-lg-4">
                                        <div class="mynak-team-tile h-100">
                                            <div class="mynak-team-photo-wrap mb-3">
                                                <?php if ($hasImg): ?>
                                                    <img src="<?php echo htmlspecialchars($__absTeam . '/' . str_replace('\\', '/', $imgPath), ENT_QUOTES, 'UTF-8'); ?>"
                                                         alt="<?php echo htmlspecialchars(mynak_public_image_alt($nameTeam . ' — ' . (string) $tm['unvan'], $nameTeam . ' — MY Nakliyat ekibi', $__absTeam . '/' . $imgPath), ENT_QUOTES, 'UTF-8'); ?>"
                                                         class="mynak-team-img" width="200" height="200" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                    <div class="mynak-team-initials" aria-hidden="true"><?php echo htmlspecialchars($ini, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="fw-semibold mynak-trust-item-title"><?php echo htmlspecialchars($nameTeam, ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="small text-primary mb-2"><?php echo htmlspecialchars((string) $tm['unvan'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php if (!empty($tm['aciklama'])): ?>
                                                <p class="small text-muted mb-0"><?php echo nl2br(htmlspecialchars(strip_tags((string) $tm['aciklama']), ENT_QUOTES, 'UTF-8')); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                }
                ?>

            </div>
        </div>
    </div>
</section>

<style>
.mynak-trust-card { border-radius: 14px; }
.mynak-trust-title { color: #1a2238; }
.mynak-trust-badge-pill {
    background: linear-gradient(135deg, #1a73e8 0%, #4285f4 100%);
    color: #fff;
    font-weight: 500;
    padding: 8px 14px;
    font-size: 13px;
}
.mynak-trust-item {
    padding: 14px;
    border-radius: 10px;
    background: #fafbff;
    border: 1px solid #eef1fa;
    transition: transform .15s ease, box-shadow .15s ease;
}
.mynak-trust-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(26, 115, 232, .12);
}
.mynak-trust-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
    color: #e08e00;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}
.mynak-trust-item-title { color: #1a2238; line-height: 1.3; }
.mynak-team-card { border-radius: 14px; }
.mynak-team-tile { padding: 10px 4px; }
.mynak-team-photo-wrap { display: flex; justify-content: center; }
.mynak-team-img {
    width: 160px;
    height: 160px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #eef1fa;
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
.mynak-team-initials {
    width: 160px;
    height: 160px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    font-weight: 700;
    color: #1a73e8;
    background: linear-gradient(135deg, #e8f0fe 0%, #f5f7fb 100%);
    border: 1px solid #d2e3fc;
}
</style>

</main>
<style>
.page-text {
    line-height: 1.8;
    font-size: 16px;
}
.page-text h2,
.page-text h3,
.page-text h4 {
    margin-top: 30px;
    margin-bottom: 15px;
}
.page-text p {
    margin-bottom: 20px;
}
.page-text img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
}
.page-text ul,
.page-text ol {
    margin-bottom: 20px;
    padding-left: 20px;
}
.page-text blockquote {
    border-left: 4px solid var(--primary-color);
    padding-left: 20px;
    margin: 20px 0;
    font-style: italic;
    color: #555;
}
.page-text table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
.page-text table th,
.page-text table td {
    border: 1px solid #ddd;
    padding: 10px;
}
.page-text table th {
    background-color: #f8f9fa;
}
.page-text pre {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 15px;
    overflow-x: auto;
}
.page-text figure {
    margin: 20px 0;
    text-align: center;
}
.page-text figure img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}
.page-text figure figcaption {
    color: #666;
    font-size: 14px;
    margin-top: 8px;
}
.page-text .mynak-video-watch .mynak-video-frame iframe {
    width: 100%;
    height: 100%;
    border: 0;
    border-radius: 8px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 