<?php
require_once 'includes/header.php';
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$page_title = "SEO URL Oluşturucu";

// Slug oluşturma fonksiyonu
function create_seo_slug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    
    // Türkçe karakterleri dönüştür
    $replace = array(
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        'Ç' => 'c', 'Ğ' => 'g', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u'
    );
    $string = strtr($string, $replace);
    
    // Özel karakterleri temizle
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

// Blog slug'larını güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_blog_slugs'])) {
    $updated_count = 0;
    $error_count = 0;
    
    // Slug'ı olmayan blog yazılarını getir
    $result = $conn->query("SELECT id, baslik FROM blog_posts WHERE slug IS NULL OR slug = ''");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $slug = create_seo_slug($row['baslik']);
            
            // Aynı slug varsa suffix ekle
            $check_stmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
            $check_stmt->bind_param("si", $slug, $row['id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            $suffix = 1;
            $original_slug = $slug;
            while ($check_result->num_rows > 0) {
                $slug = $original_slug . '-' . $suffix;
                $check_stmt->bind_param("si", $slug, $row['id']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $suffix++;
            }
            
            // Slug'ı güncelle
            $update_stmt = $conn->prepare("UPDATE blog_posts SET slug = ? WHERE id = ?");
            $update_stmt->bind_param("si", $slug, $row['id']);
            
            if ($update_stmt->execute()) {
                $updated_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    $success_message = "Blog slug'ları güncellendi: {$updated_count} başarılı, {$error_count} hata";
}

// Sayfa slug'larını güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_page_slugs'])) {
    $updated_count = 0;
    $error_count = 0;
    
    // Slug'ı olmayan sayfaları getir
    $result = $conn->query("SELECT id, title FROM pages WHERE slug IS NULL OR slug = ''");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $slug = create_seo_slug($row['title']);
            
            // Aynı slug varsa suffix ekle
            $check_stmt = $conn->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
            $check_stmt->bind_param("si", $slug, $row['id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            $suffix = 1;
            $original_slug = $slug;
            while ($check_result->num_rows > 0) {
                $slug = $original_slug . '-' . $suffix;
                $check_stmt->bind_param("si", $slug, $row['id']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $suffix++;
            }
            
            // Slug'ı güncelle
            $update_stmt = $conn->prepare("UPDATE pages SET slug = ? WHERE id = ?");
            $update_stmt->bind_param("si", $slug, $row['id']);
            
            if ($update_stmt->execute()) {
                $updated_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    $success_message = "Sayfa slug'ları güncellendi: {$updated_count} başarılı, {$error_count} hata";
}

// Blog yazılarının mevcut durumunu getir
$blog_check = $conn->query("SELECT 
    COUNT(*) as total_blogs,
    SUM(CASE WHEN slug IS NOT NULL AND slug != '' THEN 1 ELSE 0 END) as with_slug,
    SUM(CASE WHEN slug IS NULL OR slug = '' THEN 1 ELSE 0 END) as without_slug
    FROM blog_posts");
$blog_stats = $blog_check->fetch_assoc();

// Sayfaların mevcut durumunu getir
$page_check = $conn->query("SELECT 
    COUNT(*) as total_pages,
    SUM(CASE WHEN slug IS NOT NULL AND slug != '' THEN 1 ELSE 0 END) as with_slug,
    SUM(CASE WHEN slug IS NULL OR slug = '' THEN 1 ELSE 0 END) as without_slug
    FROM pages");
$page_stats = $page_check->fetch_assoc();
?>

<div class="row">
    <div class="col-12">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bx bx-link me-2"></i>SEO Dostu URL Oluşturucu</h5>
                <p class="text-muted small mt-2 mb-0">Blog yazıları ve sayfalar için SEO dostu slug'lar oluşturun</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Blog Yazıları -->
                    <div class="col-md-6">
                        <div class="card border-left-primary">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-file-text me-2"></i>Blog Yazıları</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h4 class="mb-0 text-primary"><?php echo $blog_stats['total_blogs']; ?></h4>
                                                <small>Toplam</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h4 class="mb-0 text-success"><?php echo $blog_stats['with_slug']; ?></h4>
                                                <small>Slug'lı</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h4 class="mb-0 text-danger"><?php echo $blog_stats['without_slug']; ?></h4>
                                                <small>Slug'sız</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($blog_stats['without_slug'] > 0): ?>
                                    <div class="alert alert-warning">
                                        <i class="bx bx-exclamation-triangle me-2"></i>
                                        <strong><?php echo $blog_stats['without_slug']; ?></strong> adet blog yazısının slug'ı eksik!
                                    </div>
                                    
                                    <form method="post">
                                        <button type="submit" name="update_blog_slugs" class="btn btn-primary btn-sm">
                                            <i class="bx bx-refresh me-1"></i> Blog Slug'larını Oluştur
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="bx bx-check-circle me-2"></i>
                                        Tüm blog yazılarının slug'ları mevcut!
                                    </div>
                                <?php endif; ?>
                                
                                <hr>
                                <h6>Örnek URL Yapısı:</h6>
                                <div class="small">
                                    <div class="text-danger">❌ Eski: <code>blog-detay.php?id=178</code></div>
                                    <div class="text-success">✅ Yeni: <code>blog/uzun-mesafe-tasima-planlamasi</code></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sayfalar -->
                    <div class="col-md-6">
                        <div class="card border-left-success">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-file me-2"></i>Sayfalar</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h4 class="mb-0 text-primary"><?php echo $page_stats['total_pages']; ?></h4>
                                                <small>Toplam</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h4 class="mb-0 text-success"><?php echo $page_stats['with_slug']; ?></h4>
                                                <small>Slug'lı</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h4 class="mb-0 text-danger"><?php echo $page_stats['without_slug']; ?></h4>
                                                <small>Slug'sız</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($page_stats['without_slug'] > 0): ?>
                                    <div class="alert alert-warning">
                                        <i class="bx bx-exclamation-triangle me-2"></i>
                                        <strong><?php echo $page_stats['without_slug']; ?></strong> adet sayfanın slug'ı eksik!
                                    </div>
                                    
                                    <form method="post">
                                        <button type="submit" name="update_page_slugs" class="btn btn-success btn-sm">
                                            <i class="bx bx-refresh me-1"></i> Sayfa Slug'larını Oluştur
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="bx bx-check-circle me-2"></i>
                                        Tüm sayfaların slug'ları mevcut!
                                    </div>
                                <?php endif; ?>
                                
                                <hr>
                                <h6>Örnek URL Yapısı:</h6>
                                <div class="small">
                                    <div class="text-success">✅ Yeni: <code>cesme-evden-eve-nakliyat</code></div>
                                    <div class="text-muted">Artık daha temiz URL'ler!</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- URL Örnekleri -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-world me-2"></i>URL Yapısı Örnekleri</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-danger">❌ Eski URL Yapısı (SEO Dostu Değil)</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <code>blog-detay.php?id=178</code><br>
                            <code>sayfa.php?id=25</code><br>
                            <code>galeri.php</code>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">✅ Yeni URL Yapısı (SEO Dostu)</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <code>blog/uzun-mesafe-tasima-planlamasi</code><br>
                            <code>cesme-evden-eve-nakliyat</code><br>
                            <code>galeri</code>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <h6><i class="bx bx-info-circle me-2"></i>Önemli Notlar</h6>
                    <ul class="mb-0">
                        <li><strong>Geriye Uyumluluk:</strong> Eski URL'ler çalışmaya devam eder</li>
                        <li><strong>301 Yönlendirme:</strong> Arama motorları için otomatik yönlendirme</li>
                        <li><strong>Sitemap Güncelleme:</strong> Slug'lar oluşturulduktan sonra sitemap'i yenileyin</li>
                        <li><strong>İç Linkler:</strong> Site içi linkleri yeni URL yapısıyla güncelleyin</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 