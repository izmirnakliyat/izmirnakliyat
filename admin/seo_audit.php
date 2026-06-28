<?php
$page_title = 'SEO Audit';
require_once 'includes/header.php';

// SEO audit fonksiyonları
function checkTitleTags($conn) {
    $issues = [];
    $score = 0;
    
    // Blog posts without titles
    $result = $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE (baslik = '' OR baslik IS NULL) AND durum = 3");
    if ($result) {
        $missing_titles = $result->fetch_assoc()['count'];
        if ($missing_titles > 0) {
            $issues[] = "$missing_titles blog yazısının başlığı eksik";
        } else {
            $score += 20;
        }
    }
    
    // Pages without titles
    $result = $conn->query("SELECT COUNT(*) as count FROM pages WHERE (title = '' OR title IS NULL) AND status = 1");
    if ($result) {
        $missing_page_titles = $result->fetch_assoc()['count'];
        if ($missing_page_titles > 0) {
            $issues[] = "$missing_page_titles sayfanın başlığı eksik";
        } else {
            $score += 20;
        }
    }
    
    return ['score' => min(40, $score), 'issues' => $issues];
}

function checkMetaDescriptions($conn) {
    $issues = [];
    $score = 0;
    
    // Check if global meta description exists
    $result = $conn->query("SELECT value FROM settings WHERE name = 'global_meta_description'");
    if ($result && $result->num_rows > 0) {
        $meta_desc = $result->fetch_assoc()['value'];
        if (!empty($meta_desc)) {
            $score += 15;
        } else {
            $issues[] = "Global meta description tanımlanmamış";
        }
    } else {
        $issues[] = "Global meta description tanımlanmamış";
    }
    
    return ['score' => $score, 'issues' => $issues];
}

function checkSchemaMarkup($conn) {
    $issues = [];
    $score = 0;
    
    // Check if schema markup exists
    $result = $conn->query("SELECT COUNT(*) as count FROM settings WHERE name LIKE 'schema_%'");
    if ($result) {
        $schema_count = $result->fetch_assoc()['count'];
        if ($schema_count > 0) {
            $score += 20;
        } else {
            $issues[] = "Schema markup tanımlanmamış - arama motorları için yapılandırılmış veri eksik";
        }
    }
    
    return ['score' => $score, 'issues' => $issues];
}

function checkSitemap() {
    $issues = [];
    $score = 0;
    
    if (file_exists('../sitemap.xml')) {
        $score += 15;
    } else {
        $issues[] = "Sitemap.xml dosyası bulunamadı";
    }
    
    return ['score' => $score, 'issues' => $issues];
}

function checkRobotsTxt() {
    $issues = [];
    $score = 0;
    
    if (file_exists('../robots.txt')) {
        $content = file_get_contents('../robots.txt');
        if (!empty($content)) {
            $score += 10;
        } else {
            $issues[] = "Robots.txt dosyası boş";
        }
    } else {
        $issues[] = "Robots.txt dosyası bulunamadı";
    }
    
    return ['score' => $score, 'issues' => $issues];
}

function checkOpenGraph($conn) {
    $issues = [];
    $score = 0;
    
    $og_settings = ['og_title', 'og_description', 'og_image'];
    $missing = [];
    
    foreach ($og_settings as $setting) {
        $result = $conn->query("SELECT value FROM settings WHERE name = '$setting'");
        if (!$result || $result->num_rows == 0 || empty($result->fetch_assoc()['value'])) {
            $missing[] = $setting;
        }
    }
    
    if (empty($missing)) {
        $score += 15;
    } else {
        $issues[] = "Open Graph etiketleri eksik: " . implode(', ', $missing);
    }
    
    return ['score' => $score, 'issues' => $issues];
}

// Audit çalıştır
$audit_results = [
    'title_tags' => checkTitleTags($conn),
    'meta_descriptions' => checkMetaDescriptions($conn),
    'schema_markup' => checkSchemaMarkup($conn),
    'sitemap' => checkSitemap(),
    'robots_txt' => checkRobotsTxt(),
    'open_graph' => checkOpenGraph($conn)
];

// Toplam skor hesapla
$total_score = 0;
$max_score = 40 + 15 + 20 + 15 + 10 + 15; // 115
foreach ($audit_results as $result) {
    $total_score += $result['score'];
}
$percentage = round(($total_score / $max_score) * 100);

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1><i class="bx bx-search-alt"></i> SEO Audit</h1>
                <p class="text-muted">Sitenizin SEO durumunu kapsamlı analiz edin</p>
            </div>
            
            <!-- Genel Skor -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="display-1 <?php 
                                echo $percentage >= 80 ? 'text-success' : 
                                    ($percentage >= 60 ? 'text-warning' : 'text-danger'); 
                            ?>"><?php echo $percentage; ?></div>
                            <h5>SEO Skoru</h5>
                            <div class="progress">
                                <div class="progress-bar <?php 
                                    echo $percentage >= 80 ? 'bg-success' : 
                                        ($percentage >= 60 ? 'bg-warning' : 'bg-danger'); 
                                ?>" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $total_score; ?>/<?php echo $max_score; ?> puan</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bx bx-info-circle"></i> Audit Özeti</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($percentage >= 80): ?>
                            <div class="alert alert-success">
                                <strong>Mükemmel!</strong> Sitenizin SEO durumu oldukça iyi. Küçük iyileştirmelerle daha da iyi hale getirebilirsiniz.
                            </div>
                            <?php elseif ($percentage >= 60): ?>
                            <div class="alert alert-warning">
                                <strong>İyi!</strong> Sitenizin SEO durumu iyi ancak bazı alanlar iyileştirme gerektiriyor.
                            </div>
                            <?php else: ?>
                            <div class="alert alert-danger">
                                <strong>Dikkat!</strong> Sitenizin SEO durumu kritik düzeyde. Acil iyileştirmeler yapmanız gerekiyor.
                            </div>
                            <?php endif; ?>
                            
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <h6 class="text-success"><?php echo array_sum(array_column($audit_results, 'score')); ?></h6>
                                        <small>Başarılı Kontroller</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <h6 class="text-danger"><?php echo array_sum(array_map(function($r) { return count($r['issues']); }, $audit_results)); ?></h6>
                                        <small>Sorunlar</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <h6 class="text-primary">6</h6>
                                        <small>Kontrol Edilen Alan</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detaylı Audit Sonuçları -->
            <div class="row">
                <!-- Title Tags -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6><i class="bx bx-text"></i> Başlık Etiketleri</h6>
                            <span class="badge <?php echo $audit_results['title_tags']['score'] > 30 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $audit_results['title_tags']['score']; ?>/40
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($audit_results['title_tags']['issues'])): ?>
                                <div class="text-success">
                                    <i class="bx bx-check-circle"></i> Tüm sayfalar ve blog yazıları uygun başlıklara sahip
                                </div>
                            <?php else: ?>
                                <?php foreach ($audit_results['title_tags']['issues'] as $issue): ?>
                                <div class="text-danger mb-2">
                                    <i class="bx bx-x-circle"></i> <?php echo $issue; ?>
                                </div>
                                <?php endforeach; ?>
                                <a href="blog_posts.php" class="btn btn-sm btn-primary">Düzelt</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Meta Descriptions -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6><i class="bx bx-text"></i> Meta Açıklamalar</h6>
                            <span class="badge <?php echo $audit_results['meta_descriptions']['score'] > 10 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $audit_results['meta_descriptions']['score']; ?>/15
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($audit_results['meta_descriptions']['issues'])): ?>
                                <div class="text-success">
                                    <i class="bx bx-check-circle"></i> Meta açıklamalar tanımlanmış
                                </div>
                            <?php else: ?>
                                <?php foreach ($audit_results['meta_descriptions']['issues'] as $issue): ?>
                                <div class="text-danger mb-2">
                                    <i class="bx bx-x-circle"></i> <?php echo $issue; ?>
                                </div>
                                <?php endforeach; ?>
                                <a href="seo_management.php" class="btn btn-sm btn-primary">Düzelt</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Schema Markup -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6><i class="bx bx-code-alt"></i> Schema Markup</h6>
                            <span class="badge <?php echo $audit_results['schema_markup']['score'] > 15 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $audit_results['schema_markup']['score']; ?>/20
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($audit_results['schema_markup']['issues'])): ?>
                                <div class="text-success">
                                    <i class="bx bx-check-circle"></i> Schema markup tanımlanmış
                                </div>
                            <?php else: ?>
                                <?php foreach ($audit_results['schema_markup']['issues'] as $issue): ?>
                                <div class="text-danger mb-2">
                                    <i class="bx bx-x-circle"></i> <?php echo $issue; ?>
                                </div>
                                <?php endforeach; ?>
                                <span class="text-muted small">Schema düzenleyici kaldırıldı</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sitemap -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6><i class="bx bx-sitemap"></i> Sitemap</h6>
                            <span class="badge <?php echo $audit_results['sitemap']['score'] > 10 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $audit_results['sitemap']['score']; ?>/15
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($audit_results['sitemap']['issues'])): ?>
                                <div class="text-success">
                                    <i class="bx bx-check-circle"></i> Sitemap mevcut
                                </div>
                            <?php else: ?>
                                <?php foreach ($audit_results['sitemap']['issues'] as $issue): ?>
                                <div class="text-danger mb-2">
                                    <i class="bx bx-x-circle"></i> <?php echo $issue; ?>
                                </div>
                                <?php endforeach; ?>
                                <a href="sitemap_generator.php" class="btn btn-sm btn-primary">Düzelt</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Robots.txt -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6><i class="bx bx-file-blank"></i> Robots.txt</h6>
                            <span class="badge <?php echo $audit_results['robots_txt']['score'] > 5 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $audit_results['robots_txt']['score']; ?>/10
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($audit_results['robots_txt']['issues'])): ?>
                                <div class="text-success">
                                    <i class="bx bx-check-circle"></i> Robots.txt mevcut ve dolu
                                </div>
                            <?php else: ?>
                                <?php foreach ($audit_results['robots_txt']['issues'] as $issue): ?>
                                <div class="text-danger mb-2">
                                    <i class="bx bx-x-circle"></i> <?php echo $issue; ?>
                                </div>
                                <?php endforeach; ?>
                                <a href="seo_management.php#robots" class="btn btn-sm btn-primary">Düzelt</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Open Graph -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6><i class="bx bxl-facebook"></i> Open Graph</h6>
                            <span class="badge <?php echo $audit_results['open_graph']['score'] > 10 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $audit_results['open_graph']['score']; ?>/15
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($audit_results['open_graph']['issues'])): ?>
                                <div class="text-success">
                                    <i class="bx bx-check-circle"></i> Open Graph etiketleri tanımlanmış
                                </div>
                            <?php else: ?>
                                <?php foreach ($audit_results['open_graph']['issues'] as $issue): ?>
                                <div class="text-danger mb-2">
                                    <i class="bx bx-x-circle"></i> <?php echo $issue; ?>
                                </div>
                                <?php endforeach; ?>
                                <a href="social_media_tags.php" class="btn btn-sm btn-primary">Düzelt</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Öneriler -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bx bx-bulb"></i> SEO İyileştirme Önerileri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Temel SEO</h6>
                            <ul class="small">
                                <li>Tüm sayfalarda benzersiz title etiketleri kullanın</li>
                                <li>Meta description yazın (150-160 karakter)</li>
                                <li>H1, H2, H3 başlık etiketlerini doğru kullanın</li>
                                <li>Alt etiketlerini resimler için tanımlayın</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Teknik SEO</h6>
                            <ul class="small">
                                <li>XML sitemap oluşturun ve güncel tutun</li>
                                <li>Robots.txt dosyasını optimize edin</li>
                                <li>Schema markup ekleyin</li>
                                <li>Sayfa hızını optimize edin</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>İçerik SEO</h6>
                            <ul class="small">
                                <li>Anahtar kelime araştırması yapın</li>
                                <li>Uzun kuyruklu anahtar kelimeler kullanın</li>
                                <li>İç bağlantı stratejisi geliştirin</li>
                                <li>Sosyal medya etiketleri ekleyin</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.display-1 {
    font-size: 4rem;
    font-weight: bold;
}
</style>

<?php require_once 'includes/footer.php'; ?> 