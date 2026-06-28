<?php
require_once 'includes/header.php';
require_once '../config/config.php';
require_once '../config/db.php';

// Sayfa başlığını ayarla
$page_title = "SEO Yönetimi";

// SEO Ayarlarını Kaydetme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_seo'])) {
    // Varsayılan değerler
    $global_title_suffix = $_POST['global_title_suffix'] ?? '';
    $global_meta_description = $_POST['global_meta_description'] ?? '';
    $global_meta_keywords = $_POST['global_meta_keywords'] ?? '';
    $global_header_scripts = $_POST['global_header_scripts'] ?? '';
    $global_body_start_scripts = $_POST['global_body_start_scripts'] ?? '';
    $global_body_end_scripts = $_POST['global_body_end_scripts'] ?? '';
    $global_after_footer_scripts = $_POST['global_after_footer_scripts'] ?? '';
    
    // SQL Injection koruması
    $global_title_suffix = $conn->real_escape_string($global_title_suffix);
    $global_meta_description = $conn->real_escape_string($global_meta_description);
    $global_meta_keywords = $conn->real_escape_string($global_meta_keywords);
    $global_header_scripts = $conn->real_escape_string($global_header_scripts);
    $global_body_start_scripts = $conn->real_escape_string($global_body_start_scripts);
    $global_body_end_scripts = $conn->real_escape_string($global_body_end_scripts);
    $global_after_footer_scripts = $conn->real_escape_string($global_after_footer_scripts);
    
    // Önce tüm SEO ayarlarını getir
    $settings_to_update = [
        'global_title_suffix' => $global_title_suffix,
        'global_meta_description' => $global_meta_description,
        'global_meta_keywords' => $global_meta_keywords,
        'global_header_scripts' => $global_header_scripts,
        'global_body_start_scripts' => $global_body_start_scripts,
        'global_body_end_scripts' => $global_body_end_scripts,
        'global_after_footer_scripts' => $global_after_footer_scripts
    ];
    
    // Tüm ayarları veritabanına ekle/güncelle
    $success = true;
    foreach ($settings_to_update as $name => $value) {
        // Ayarın var olup olmadığını kontrol et
        $check_sql = "SELECT * FROM settings WHERE name = '$name'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Ayar var, güncelle
            $update_sql = "UPDATE settings SET value = '$value' WHERE name = '$name'";
            if (!$conn->query($update_sql)) {
                $success = false;
                $error_message = "Hata: " . $conn->error;
                break;
            }
        } else {
            // Ayar yok, ekle
            $insert_sql = "INSERT INTO settings (name, value) VALUES ('$name', '$value')";
            if (!$conn->query($insert_sql)) {
                $success = false;
                $error_message = "Hata: " . $conn->error;
                break;
            }
        }
    }
    
    // AJAX isteği var mı kontrol et
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($is_ajax) {
        // AJAX yanıtı
        header('Content-Type: application/json');
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'SEO ayarları başarıyla kaydedildi!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $error_message]);
        }
        exit;
    } else {
        // Normal form submit - yönlendirme
        if ($success) {
            // Yönlendirme ile sayfayı yenile
            header("Location: seo_management.php?saved=1");
            exit;
        }
    }
}

// Başarı mesajını URL parametresinden al
if (isset($_GET['saved']) && $_GET['saved'] == '1') {
    $success_message = "SEO ayarları başarıyla kaydedildi!";
}

// Sitemap ve Robots.txt mesajlarını ekle
if (isset($_GET['sitemap_generated']) && $_GET['sitemap_generated'] == '1') {
    $success_message = isset($_GET['message']) ? $_GET['message'] : "Sitemap başarıyla oluşturuldu!";
}

if (isset($_GET['sitemap_error']) && $_GET['sitemap_error'] == '1') {
    $error_message = isset($_GET['message']) ? $_GET['message'] : "Sitemap oluşturulurken bir hata oluştu!";
}

if (isset($_GET['robots_updated']) && $_GET['robots_updated'] == '1') {
    $success_message = isset($_GET['message']) ? $_GET['message'] : "Robots.txt dosyası başarıyla güncellendi!";
}

if (isset($_GET['robots_error']) && $_GET['robots_error'] == '1') {
    $error_message = isset($_GET['message']) ? $_GET['message'] : "Robots.txt güncellenirken bir hata oluştu!";
}

// SEO Ayarlarını getir
$seo_settings = [];
$seo_sql = "SELECT * FROM settings WHERE name LIKE 'global_%'";
$seo_result = $conn->query($seo_sql);

if ($seo_result && $seo_result->num_rows > 0) {
    while ($row = $seo_result->fetch_assoc()) {
        $seo_settings[$row['name']] = $row['value'];
    }
}
?>

<div class="row">
    <div class="col-12">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Global SEO Ayarları</h5>
                <p class="text-muted small mt-2 mb-0">Bu ayarlar tüm sayfalarda geçerli olacaktır. Örneğin, Google Analytics veya Facebook Pixel gibi izleme kodları.</p>
            </div>
            <div class="card-body">
                <form method="post" action="seo_management.php" id="seoForm">
                    <div class="mb-3">
                        <label for="global_title_suffix" class="form-label">Sayfa Başlığı Soneki</label>
                        <input type="text" class="form-control" id="global_title_suffix" name="global_title_suffix" value="<?php echo isset($seo_settings['global_title_suffix']) ? htmlspecialchars($seo_settings['global_title_suffix']) : ''; ?>" maxlength="60">
                        <div class="form-text">Tüm sayfa başlıklarının sonuna eklenecek metin (örn: " - MY Nakliyat")</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="global_meta_description" class="form-label">Varsayılan Meta Açıklaması</label>
                        <textarea class="form-control" id="global_meta_description" name="global_meta_description" rows="3" maxlength="160"><?php echo isset($seo_settings['global_meta_description']) ? htmlspecialchars($seo_settings['global_meta_description']) : ''; ?></textarea>
                        <div class="form-text">Sayfanın kendi meta açıklaması yoksa, varsayılan olarak bu metin kullanılacak</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="global_meta_keywords" class="form-label">Varsayılan Meta Anahtar Kelimeleri</label>
                        <textarea class="form-control" id="global_meta_keywords" name="global_meta_keywords" rows="2"><?php echo isset($seo_settings['global_meta_keywords']) ? htmlspecialchars($seo_settings['global_meta_keywords']) : ''; ?></textarea>
                        <div class="form-text">Virgülle ayırarak yazın (örn: okul, eğitim, kolej)</div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Global Script Kodları</h5>
                        <p class="text-muted small mb-3">Bu kodlar tüm sayfalara otomatik olarak eklenecektir</p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="global_header_scripts" class="form-label">Header Scriptleri (head etiketinin içine)</label>
                        <textarea class="form-control font-monospace" id="global_header_scripts" name="global_header_scripts" rows="5"><?php echo isset($seo_settings['global_header_scripts']) ? htmlspecialchars($seo_settings['global_header_scripts']) : ''; ?></textarea>
                        <div class="form-text">Google Analytics, Meta Pixel, arama motoru doğrulama etiketleri vb. kodları buraya ekleyin</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="global_body_start_scripts" class="form-label">Body Başlangıç Scriptleri (body etiketinden hemen sonra)</label>
                        <textarea class="form-control font-monospace" id="global_body_start_scripts" name="global_body_start_scripts" rows="5"><?php echo isset($seo_settings['global_body_start_scripts']) ? htmlspecialchars($seo_settings['global_body_start_scripts']) : ''; ?></textarea>
                        <div class="form-text">Body etiketinden hemen sonra yüklenmesi gereken scriptler için kullanın</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="global_body_end_scripts" class="form-label">Body Bitiş Scriptleri (body etiketinden önce)</label>
                        <textarea class="form-control font-monospace" id="global_body_end_scripts" name="global_body_end_scripts" rows="5"><?php echo isset($seo_settings['global_body_end_scripts']) ? htmlspecialchars($seo_settings['global_body_end_scripts']) : ''; ?></textarea>
                        <div class="form-text">Body etiketinden hemen önce yüklenmesi gereken scriptler için kullanın</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="global_after_footer_scripts" class="form-label">Footer Sonrası Scriptler (HTML'in en sonunda)</label>
                        <textarea class="form-control font-monospace" id="global_after_footer_scripts" name="global_after_footer_scripts" rows="5"><?php echo isset($seo_settings['global_after_footer_scripts']) ? htmlspecialchars($seo_settings['global_after_footer_scripts']) : ''; ?></textarea>
                        <div class="form-text">HTML'in en sonunda çalıştırılması gereken scriptler için kullanın</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i> Bu SEO kodlarını ekledikten sonra, sunucuda JS/CSS minify, gzip sıkıştırma, browser önbelleği gibi performans iyileştirmeleri yapmanızı öneririz.
                    </div>
                    
                    <div class="mb-3">
                        <input type="hidden" name="save_seo" value="1">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Ayarları Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">SEO Yardımcısı</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-left-primary">
                            <div class="card-body">
                                <h6>Google Analytics 4 Kodu</h6>
                                <p class="small text-muted">Google Analytics kodunu Header Scripts alanına ekleyin:</p>
                                <pre class="bg-light p-3 mt-2"><code>&lt;!-- Google Analytics (GA4) --&gt;
&lt;script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"&gt;&lt;/script&gt;
&lt;script&gt;
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
&lt;/script&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-left-primary">
                            <div class="card-body">
                                <h6>Facebook (Meta) Pixel Kodu</h6>
                                <p class="small text-muted">Meta Pixel kodunu Header Scripts alanına ekleyin:</p>
                                <pre class="bg-light p-3 mt-2"><code>&lt;!-- Meta Pixel Code --&gt;
&lt;script&gt;
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', 'XXXXXXXXXXXXXXXXXX'); 
fbq('track', 'PageView');
&lt;/script&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-left-primary">
                            <div class="card-body">
                                <h6>Google Tag Manager</h6>
                                <p class="small text-muted">Header Scripts ve Body Start Scripts alanlarına ekleyin:</p>
                                <pre class="bg-light p-3 mt-2"><code># Header Scripts alanı için:
&lt;!-- Google Tag Manager --&gt;
&lt;script&gt;(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-XXXXXXX');&lt;/script&gt;
&lt;!-- End Google Tag Manager --&gt;

# Body Start Scripts alanı için:
&lt;!-- Google Tag Manager (noscript) --&gt;
&lt;noscript&gt;&lt;iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXXX"
height="0" width="0" style="display:none;visibility:hidden"&gt;&lt;/iframe&gt;&lt;/noscript&gt;
&lt;!-- End Google Tag Manager (noscript) --&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-left-primary">
                            <div class="card-body">
                                <h6>Google Ads Dönüşüm İzleme</h6>
                                <p class="small text-muted">Body End Scripts alanına ekleyin:</p>
                                <pre class="bg-light p-3 mt-2"><code>&lt;!-- Google Ads Conversion Tracking --&gt;
&lt;script&gt;
  gtag('config', 'AW-XXXXXXXXXX');
  
  // Dönüşüm sayfasında bu kodu ekleyin
  // gtag('event', 'conversion', {'send_to': 'AW-XXXXXXXXXX/YYYYYYYYYYYYYYYYYYY'});
&lt;/script&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sitemap Oluşturma ve İndeksleme Araçları -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bx bx-sitemap me-2"></i>Sitemap ve İndeksleme Araçları</h5>
                <p class="text-muted small mt-2 mb-0">Sitenizin arama motorlarında daha iyi indexlenmesi için araçlar</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Sitemap Oluşturma -->
                    <div class="col-lg-6">
                        <div class="card border-left-success mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-file me-2"></i>XML Sitemap Oluşturma</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    XML Sitemap, arama motorlarının sitenizi daha etkili bir şekilde taramasına yardımcı olur. Aşağıdaki seçenekleri kullanarak XML sitemap dosyanızı oluşturabilirsiniz.
                                </p>
                                
                                <form id="generateSitemapForm" method="post" action="sitemap_generator.php">
                                    <div class="mb-3">
                                        <label class="form-label">Sitemap'e Dahil Edilecek İçerikler:</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_pages" id="include_pages" value="1" checked>
                                            <label class="form-check-label" for="include_pages">Sayfalar</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_blog" id="include_blog" value="1" checked>
                                            <label class="form-check-label" for="include_blog">Blog Yazıları</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_categories" id="include_categories" value="1" checked>
                                            <label class="form-check-label" for="include_categories">Kategoriler</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="include_images" id="include_images" value="1" checked>
                                            <label class="form-check-label" for="include_images">Görseller (image-sitemap.xml)</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Öncelik Ayarları:</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="priority_homepage" class="form-label small">Ana Sayfa Önceliği:</label>
                                                <select class="form-select form-select-sm" id="priority_homepage" name="priority_homepage">
                                                    <option value="1.0" selected>1.0 (En Yüksek)</option>
                                                    <option value="0.9">0.9</option>
                                                    <option value="0.8">0.8</option>
                                                    <option value="0.7">0.7</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="priority_other" class="form-label small">Diğer Sayfalar Önceliği:</label>
                                                <select class="form-select form-select-sm" id="priority_other" name="priority_other">
                                                    <option value="0.8">0.8</option>
                                                    <option value="0.7" selected>0.7</option>
                                                    <option value="0.6">0.6</option>
                                                    <option value="0.5">0.5</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 d-flex justify-content-between align-items-end">
                                        <div>
                                            <button type="submit" class="btn btn-success btn-sm" id="generateSitemapBtn">
                                                <i class="bx bx-cog me-1"></i> Sitemap Oluştur
                                            </button>
                                        </div>
                                        <div>
                                            <a href="../sitemap.xml" target="_blank" class="btn btn-outline-primary btn-sm" id="viewSitemapBtn">
                                                <i class="bx bx-show me-1"></i> Mevcut Sitemap'i Görüntüle
                                            </a>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="alert alert-info mt-3 mb-0 small">
                                    <i class="bx bx-info-circle me-2"></i> Sitemap oluşturulduktan sonra <code>sitemap.xml</code> dosyası web sitenizin kök dizinine kaydedilir.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    
                    <!-- Robots.txt Düzenleme -->
                    <div class="col-12">
                        <div class="card border-left-warning">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-file-blank me-2"></i>Robots.txt Düzenleme</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    Robots.txt dosyası, arama motoru botlarının sitenizin hangi bölümlerine erişebileceğini kontrol eder. Bu dosyayı düzenleyerek bazı klasör veya sayfaları indexlemeden hariç tutabilirsiniz.
                                </p>
                                
                                <form id="robotsTxtForm" method="post" action="update_robots_txt.php">
                                    <div class="mb-3">
                                        <label for="robotsTxt" class="form-label">Robots.txt İçeriği:</label>
                                        <textarea class="form-control font-monospace" id="robotsTxt" name="robots_content" rows="12"><?php
                                        $robots_path = $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';
                                        if (file_exists($robots_path)) {
                                            echo htmlspecialchars(file_get_contents($robots_path));
                                        } else {
                                            echo "User-agent: *\nDisallow: /admin/\nDisallow: /config/\nDisallow: /includes/\n\nSitemap: https://" . $_SERVER['HTTP_HOST'] . "/sitemap.xml\nSitemap: https://" . $_SERVER['HTTP_HOST'] . "/image-sitemap.xml";
                                        }
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3 d-flex justify-content-between align-items-center">
                                        <button type="submit" class="btn btn-warning" id="saveRobotsTxtBtn">
                                            <i class="bx bx-save me-1"></i> Robots.txt Dosyasını Kaydet
                                        </button>
                                        <a href="../robots.txt" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bx bx-show me-1"></i> Mevcut Robots.txt'yi Görüntüle
                                        </a>
                                    </div>
                                    
                                    <div class="alert alert-info small mb-0">
                                        <h6 class="alert-heading"><i class="bx bx-bulb me-2"></i>Örnek Robots.txt Kuralları</h6>
                                        <hr>
                                        <p>Aşağıdaki örnekler robots.txt dosyanızı oluştururken yardımcı olabilir:</p>
                                        <pre class="bg-light p-2 mt-2 mb-0"><code># Tüm botlara izin ver
User-agent: *
Allow: /

# Yönetim panelini engelle
Disallow: /admin/
Disallow: /config/
Disallow: /includes/

# Sitemap bildirimi
Sitemap: https://example.com/sitemap.xml</code></pre>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Karakter sayacı
        $('#global_title_suffix, #global_meta_description').on('input', function() {
            const maxLength = $(this).attr('maxlength');
            const currentLength = $(this).val().length;
            
            let formText = $(this).next('.form-text');
            if (formText.length) {
                let originalText = formText.data('original-text');
                if (!originalText) {
                    originalText = formText.text();
                    formText.data('original-text', originalText);
                }
                
                if (currentLength > 0) {
                    formText.text(`${originalText} (${currentLength}/${maxLength})`);
                } else {
                    formText.text(originalText);
                }
            }
        });
        
        // Stil eklentileri
        $('textarea.font-monospace').each(function() {
            $(this).css('font-family', 'SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace');
            $(this).css('font-size', '13px');
        });
        
        // Form submit önleme ve AJAX ile gönderme
        $('#seoForm').submit(function(e) {
            e.preventDefault();
            
            // Form verilerini al
            var formData = $(this).serialize() + '&save_seo=1&_t=' + new Date().getTime();
            
            // Kaydetme düğmesini devre dışı bırak
            $('button[type="submit"]').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Kaydediliyor...');
            
            $.ajax({
                type: "POST",
                url: "seo_management.php",
                data: formData,
                cache: false,
                success: function(response) {
                    // Başarı mesajını göster ve sayfayı yeniden yükle
                    $('<div class="alert alert-success">SEO ayarları başarıyla kaydedildi!</div>')
                        .prependTo('.col-12');
                    
                    // 1 saniye sonra sayfayı yeniden yükle
                    setTimeout(function() {
                        window.location.href = 'seo_management.php?saved=1&t=' + new Date().getTime();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    // Kaydetme düğmesini yeniden etkinleştir
                    $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Ayarları Kaydet');
                    
                    // Hata mesajını göster
                    $('<div class="alert alert-danger">Bir hata oluştu: ' + error + '</div>')
                        .prependTo('.col-12')
                        .delay(3000)
                        .fadeOut(500, function() {
                            $(this).remove();
                        });
                }
            });
        });
        
        // Sitemap ve İndeksleme Araçları JS
        
        // Sitemap URL'sini kopyalama
        $('#copySitemapUrl').on('click', function() {
            var copyText = document.getElementById("sitemapUrl");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            
            $(this).html('<i class="bx bx-check"></i>');
            setTimeout(function() {
                $('#copySitemapUrl').html('<i class="bx bx-copy"></i>');
            }, 2000);
        });
        
        // Sitemap oluşturma formu AJAX
        $('#generateSitemapForm').submit(function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            $('#generateSitemapBtn').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Oluşturuluyor...');
            
            console.log('Sitemap oluşturma formu gönderiliyor...');
            console.log('Form verileri:', formData);
            console.log('Checkbox değerleri:', {
                include_pages: $('#include_pages').is(':checked'),
                include_blog: $('#include_blog').is(':checked'), 
                include_categories: $('#include_categories').is(':checked'),
                include_images: $('#include_images').is(':checked')
            });
            
            $.ajax({
                type: "POST",
                url: "sitemap_generator.php",
                data: formData,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Sitemap yanıtı:', response);
                    try {
                        if (response.status === 'success') {
                            // Başarı mesajı
                            $('<div class="alert alert-success">' + response.message + '</div>')
                                .insertAfter('#generateSitemapForm')
                                .delay(5000)
                                .fadeOut(500, function() {
                                    $(this).remove();
                                });
                        } else {
                            // Hata mesajı
                            $('<div class="alert alert-danger">' + response.message + '</div>')
                                .insertAfter('#generateSitemapForm')
                                .delay(5000)
                                .fadeOut(500, function() {
                                    $(this).remove();
                                });
                        }
                    } catch (e) {
                        console.error('JSON parse hatası:', e);
                        // Genel hata mesajı
                        $('<div class="alert alert-danger">İşlem sırasında bir hata oluştu.</div>')
                            .insertAfter('#generateSitemapForm')
                            .delay(5000)
                            .fadeOut(500, function() {
                                $(this).remove();
                            });
                    }
                    
                    // Butonu sıfırla
                    $('#generateSitemapBtn').prop('disabled', false).html('<i class="bx bx-cog me-1"></i> Sitemap Oluştur');
                },
                error: function(xhr, status, error) {
                    console.error('AJAX hatası:', xhr.responseText);
                    console.error('Hata kodu:', xhr.status);
                    console.error('Hata durumu:', status);
                    console.error('Hata:', error);
                    
                    // Detaylı hata mesajını göster
                    var errorMessage = 'Bir hata oluştu';
                    try {
                        var responseObj = JSON.parse(xhr.responseText);
                        if (responseObj && responseObj.message) {
                            errorMessage = responseObj.message;
                        }
                    } catch (e) {
                        errorMessage = 'Sunucu yanıtı işlenirken hata oluştu: ' + error + '<br>Ham yanıt: ' + xhr.responseText;
                    }
                    
                    // Hata mesajını göster
                    $('<div class="alert alert-danger">' + errorMessage + '</div>')
                        .insertAfter('#generateSitemapForm')
                        .delay(5000)
                        .fadeOut(500, function() {
                            $(this).remove();
                        });
                    
                    // Butonu sıfırla
                    $('#generateSitemapBtn').prop('disabled', false).html('<i class="bx bx-cog me-1"></i> Sitemap Oluştur');
                }
            });
        });
        
        // Robots.txt düzenleme AJAX
        $('#robotsTxtForm').submit(function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            $('#saveRobotsTxtBtn').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Kaydediliyor...');
            
            console.log('Robots.txt düzenleme formu gönderiliyor...');
            console.log('Form verileri:', formData);
            
            $.ajax({
                type: "POST",
                url: "update_robots_txt.php",
                data: formData,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Robots.txt yanıtı:', response);
                    try {
                        if (response.status === 'success') {
                            // Başarı mesajı
                            $('<div class="alert alert-success">' + response.message + '</div>')
                                .insertAfter('#robotsTxtForm')
                                .delay(5000)
                                .fadeOut(500, function() {
                                    $(this).remove();
                                });
                        } else {
                            // Hata mesajı
                            $('<div class="alert alert-danger">' + response.message + '</div>')
                                .insertAfter('#robotsTxtForm')
                                .delay(5000)
                                .fadeOut(500, function() {
                                    $(this).remove();
                                });
                        }
                    } catch (e) {
                        console.error('JSON parse hatası:', e);
                        // Genel hata mesajı
                        $('<div class="alert alert-danger">İşlem sırasında bir hata oluştu.</div>')
                            .insertAfter('#robotsTxtForm')
                            .delay(5000)
                            .fadeOut(500, function() {
                                $(this).remove();
                            });
                    }
                    
                    // Butonu sıfırla
                    $('#saveRobotsTxtBtn').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Robots.txt Dosyasını Kaydet');
                },
                error: function(xhr, status, error) {
                    console.error('AJAX hatası:', xhr.responseText);
                    console.error('Hata kodu:', xhr.status);
                    console.error('Hata durumu:', status);
                    console.error('Hata:', error);
                    
                    // Detaylı hata mesajını göster
                    var errorMessage = 'Bir hata oluştu';
                    try {
                        var responseObj = JSON.parse(xhr.responseText);
                        if (responseObj && responseObj.message) {
                            errorMessage = responseObj.message;
                        }
                    } catch (e) {
                        errorMessage = 'Sunucu yanıtı işlenirken hata oluştu: ' + error + '<br>Ham yanıt: ' + xhr.responseText;
                    }
                    
                    // Hata mesajını göster
                    $('<div class="alert alert-danger">' + errorMessage + '</div>')
                        .insertAfter('#robotsTxtForm')
                        .delay(5000)
                        .fadeOut(500, function() {
                            $(this).remove();
                        });
                    
                    // Butonu sıfırla
                    $('#saveRobotsTxtBtn').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Robots.txt Dosyasını Kaydet');
                }
            });
        });
        
        // Sitemap URL'sini kopyala
        $('#copySitemapUrl').on('click', function() {
            var sitemapUrl = $('#sitemapUrl')[0];
            sitemapUrl.select();
            sitemapUrl.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Toast mesaj göster
            $(this).html('<i class="bx bx-check"></i>').addClass('btn-success').removeClass('btn-outline-secondary');
            setTimeout(() => {
                $(this).html('<i class="bx bx-copy"></i>').removeClass('btn-success').addClass('btn-outline-secondary');
            }, 2000);
        });

        // Yalnızca Sitemap URL kopyalama
        $('#copySitemapUrl').on('click', function() {
            var copyText = document.getElementById("sitemapUrl");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(function() {
                $(this).html('<i class="bx bx-check"></i>').addClass('btn-success').removeClass('btn-outline-secondary');
                setTimeout(() => {
                    $(this).html('<i class="bx bx-copy"></i>').removeClass('btn-success').addClass('btn-outline-secondary');
                }, 2000);
            });
        });
        
    });
</script>

<?php require_once 'includes/footer.php'; ?> 