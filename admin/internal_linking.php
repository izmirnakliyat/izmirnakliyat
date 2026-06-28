<?php
$page_title = 'Internal Linking Analizi';
require_once 'includes/header.php';

/**
 * İçerikten linkleri çıkaran fonksiyon
 */
function extractInternalLinks($content, $site_url) {
    $links = [];
    if (empty($content)) return $links;
    
    // href="..." içindeki linkleri bul
    preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $link) {
            // Sadece internal linkleri al
            if (strpos($link, $site_url) !== false || 
                (strpos($link, 'http') === false && strpos($link, '//') !== 0)) {
                // URL'yi normalize et
                $link = str_replace($site_url, '', $link);
                $link = '/' . ltrim($link, '/');
                if ($link !== '/' && !in_array($link, $links)) {
                    $links[] = $link;
                }
            }
        }
    }
    
    return $links;
}

/**
 * Slug'dan URL oluştur
 */
function buildUrl($type, $slug) {
    switch ($type) {
        case 'page':
            return '/' . $slug;
        case 'blog':
            return '/' . $slug;
        case 'service':
            return '/hizmet/' . $slug;
        default:
            return '/' . $slug;
    }
}

// Site URL'sini al
$site_url = SITE_URL;

// Tüm içerikleri topla
$all_content = [];
$link_map = []; // Hangi sayfa hangi sayfalara link veriyor
$incoming_links = []; // Hangi sayfa hangi sayfalardan link alıyor

// Blog yazıları
$blog_query = $conn->query("SELECT id, baslik, slug, icerik, durum FROM blog_posts ORDER BY created_at DESC");
if ($blog_query) {
    while ($row = $blog_query->fetch_assoc()) {
        $url = buildUrl('blog', $row['slug']);
        $links = extractInternalLinks($row['icerik'], $site_url);
        
        $all_content[] = [
            'id' => $row['id'],
            'type' => 'blog',
            'title' => $row['baslik'],
            'slug' => $row['slug'],
            'url' => $url,
            'status' => $row['durum'],
            'outgoing_links' => $links,
            'incoming_links' => [],
            'content_preview' => mb_substr(strip_tags($row['icerik']), 0, 200)
        ];
        
        $link_map[$url] = $links;
    }
}

// Sayfalar
$pages_query = $conn->query("SELECT id, title, slug, content, status FROM pages ORDER BY id DESC");
if ($pages_query) {
    while ($row = $pages_query->fetch_assoc()) {
        $url = buildUrl('page', $row['slug']);
        $links = extractInternalLinks($row['content'], $site_url);
        
        $all_content[] = [
            'id' => $row['id'],
            'type' => 'page',
            'title' => $row['title'],
            'slug' => $row['slug'],
            'url' => $url,
            'status' => $row['status'],
            'outgoing_links' => $links,
            'incoming_links' => [],
            'content_preview' => mb_substr(strip_tags($row['content']), 0, 200)
        ];
        
        $link_map[$url] = $links;
    }
}

// Hizmetler
$services_query = $conn->query("SELECT id, ana_baslik, slug, aciklama, status FROM services ORDER BY id DESC");
if ($services_query) {
    while ($row = $services_query->fetch_assoc()) {
        $slug = $row['slug'] ?? '';
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', 
                str_replace(['ı','ğ','ü','ş','ö','ç','İ','Ğ','Ü','Ş','Ö','Ç'], 
                            ['i','g','u','s','o','c','i','g','u','s','o','c'], $row['ana_baslik']))));
        }
        $url = buildUrl('service', $slug);
        $links = extractInternalLinks($row['aciklama'] ?? '', $site_url);
        
        $all_content[] = [
            'id' => $row['id'],
            'type' => 'service',
            'title' => $row['ana_baslik'],
            'slug' => $slug,
            'url' => $url,
            'status' => $row['status'],
            'outgoing_links' => $links,
            'incoming_links' => [],
            'content_preview' => mb_substr(strip_tags($row['aciklama'] ?? ''), 0, 200)
        ];
        
        $link_map[$url] = $links;
    }
}

// Incoming links hesapla
foreach ($all_content as &$item) {
    $item_url = $item['url'];
    foreach ($link_map as $source_url => $links) {
        if ($source_url !== $item_url) {
            foreach ($links as $link) {
                // URL eşleşmesi kontrol et
                if ($link === $item_url || 
                    strpos($link, $item['slug']) !== false ||
                    $link === '/' . $item['slug']) {
                    $item['incoming_links'][] = $source_url;
                }
            }
        }
    }
}
unset($item);

// İstatistikler
$total_content = count($all_content);
$orphan_pages = array_filter($all_content, fn($item) => count($item['incoming_links']) === 0);
$no_outgoing = array_filter($all_content, fn($item) => count($item['outgoing_links']) === 0);
$total_internal_links = array_sum(array_map(fn($item) => count($item['outgoing_links']), $all_content));
$avg_links_per_page = $total_content > 0 ? round($total_internal_links / $total_content, 1) : 0;

// Tip etiketleri
$type_labels = [
    'blog' => ['label' => 'Blog', 'color' => 'primary'],
    'page' => ['label' => 'Sayfa', 'color' => 'success'],
    'service' => ['label' => 'Hizmet', 'color' => 'info']
];
?>

<style>
.link-stat-card {
    border: none;
    border-radius: 15px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.link-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
.link-stat-card .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.link-stat-card .stat-number {
    font-size: 32px;
    font-weight: 700;
}

.link-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    margin: 2px;
}
.link-badge.incoming { background: #e8f5e9; color: #2e7d32; }
.link-badge.outgoing { background: #e3f2fd; color: #1565c0; }
.link-badge.orphan { background: #ffebee; color: #c62828; }

.content-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}
.content-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}
.content-card.orphan {
    border-left: 4px solid #dc3545;
}
.content-card.no-outgoing {
    border-left: 4px solid #ffc107;
}
.content-card.healthy {
    border-left: 4px solid #28a745;
}

.link-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    margin: 1px;
    text-decoration: none;
}
.link-pill:hover {
    opacity: 0.8;
}

.suggestion-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
}

.filter-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 10px 20px;
    border-bottom: 2px solid transparent;
}
.filter-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background: transparent;
}
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bx bx-link text-primary me-2"></i>Internal Linking Analizi
            </h1>
            <p class="text-muted mb-0">Sitenizin iç link yapısını analiz edin ve SEO'nuzu güçlendirin</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="refreshAnalysis()">
                <i class="bx bx-refresh me-1"></i> Yenile
            </button>
        </div>
    </div>
    
    <!-- İstatistikler -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card link-stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bx bx-file"></i>
                        </div>
                        <div>
                            <div class="stat-number text-primary"><?php echo $total_content; ?></div>
                            <div class="text-muted small">Toplam İçerik</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card link-stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bx bx-link"></i>
                        </div>
                        <div>
                            <div class="stat-number text-success"><?php echo $total_internal_links; ?></div>
                            <div class="text-muted small">Toplam Internal Link</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card link-stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bx bx-unlink"></i>
                        </div>
                        <div>
                            <div class="stat-number text-danger"><?php echo count($orphan_pages); ?></div>
                            <div class="text-muted small">Orphan Sayfa</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card link-stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bx bx-stats"></i>
                        </div>
                        <div>
                            <div class="stat-number text-warning"><?php echo $avg_links_per_page; ?></div>
                            <div class="text-muted small">Ort. Link/Sayfa</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Uyarılar -->
    <?php if (count($orphan_pages) > 0): ?>
    <div class="alert alert-danger mb-4">
        <div class="d-flex align-items-center">
            <i class="bx bx-error-circle fs-4 me-2"></i>
            <div>
                <strong><?php echo count($orphan_pages); ?> Orphan Sayfa Tespit Edildi!</strong>
                <p class="mb-0 small">Bu sayfalar hiçbir yerden link almıyor. Google bu sayfaları bulmakta zorlanabilir.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tab Filtreleri -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs filter-tabs" id="linkTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all-tab">
                        <i class="bx bx-list-ul me-1"></i> Tümü
                        <span class="badge bg-secondary ms-1"><?php echo $total_content; ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#orphan-tab">
                        <i class="bx bx-error me-1"></i> Orphan Sayfalar
                        <span class="badge bg-danger ms-1"><?php echo count($orphan_pages); ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#no-outgoing-tab">
                        <i class="bx bx-link-external me-1"></i> Link Vermeyen
                        <span class="badge bg-warning ms-1"><?php echo count($no_outgoing); ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#suggestions-tab">
                        <i class="bx bx-bulb me-1"></i> AI Önerileri
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content">
                <!-- Tüm İçerikler -->
                <div class="tab-pane fade show active" id="all-tab">
                    <?php foreach ($all_content as $item): 
                        $card_class = count($item['incoming_links']) === 0 ? 'orphan' : 
                                     (count($item['outgoing_links']) === 0 ? 'no-outgoing' : 'healthy');
                    ?>
                    <div class="content-card <?php echo $card_class; ?> p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-<?php echo $type_labels[$item['type']]['color']; ?> me-2">
                                        <?php echo $type_labels[$item['type']]['label']; ?>
                                    </span>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <small class="text-muted"><?php echo $item['url']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="link-badge incoming">
                                    <i class="bx bx-down-arrow-alt me-1"></i>
                                    <?php echo count($item['incoming_links']); ?> gelen
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="link-badge outgoing">
                                    <i class="bx bx-up-arrow-alt me-1"></i>
                                    <?php echo count($item['outgoing_links']); ?> giden
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="showLinkDetails('<?php echo $item['type']; ?>', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?>')">
                                    <i class="bx bx-show"></i> Detay
                                </button>
                                <button class="btn btn-sm btn-outline-success" 
                                        onclick="getSuggestions('<?php echo $item['type']; ?>', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?>')">
                                    <i class="bx bx-bulb"></i> Öneri
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Orphan Sayfalar -->
                <div class="tab-pane fade" id="orphan-tab">
                    <?php if (count($orphan_pages) === 0): ?>
                    <div class="text-center py-5">
                        <i class="bx bx-check-circle text-success fs-1 mb-3"></i>
                        <h5>Tebrikler!</h5>
                        <p class="text-muted">Orphan sayfa bulunmuyor. Tüm sayfalarınız en az bir yerden link alıyor.</p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning mb-4">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Orphan Sayfa Nedir?</strong> Sitenizdeki hiçbir sayfadan link almayan sayfalardır. 
                        Bu sayfalar Google tarafından keşfedilemeyebilir veya önemsiz olarak değerlendirilebilir.
                    </div>
                    <?php foreach ($orphan_pages as $item): ?>
                    <div class="content-card orphan p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-<?php echo $type_labels[$item['type']]['color']; ?> me-2">
                                        <?php echo $type_labels[$item['type']]['label']; ?>
                                    </span>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <small class="text-muted"><?php echo $item['url']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <span class="link-badge orphan">
                                    <i class="bx bx-error-circle me-1"></i> Link Almıyor
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <button class="btn btn-sm btn-success" 
                                        onclick="getSuggestions('<?php echo $item['type']; ?>', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?>')">
                                    <i class="bx bx-bulb me-1"></i> Link Önerisi Al
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Link Vermeyen -->
                <div class="tab-pane fade" id="no-outgoing-tab">
                    <?php if (count($no_outgoing) === 0): ?>
                    <div class="text-center py-5">
                        <i class="bx bx-check-circle text-success fs-1 mb-3"></i>
                        <h5>Harika!</h5>
                        <p class="text-muted">Tüm sayfalarınız en az bir internal link içeriyor.</p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <i class="bx bx-info-circle me-2"></i>
                        Bu sayfalar hiç internal link içermiyor. İlgili içeriklere link ekleyerek kullanıcı deneyimini ve SEO'yu iyileştirebilirsiniz.
                    </div>
                    <?php foreach ($no_outgoing as $item): ?>
                    <div class="content-card no-outgoing p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-<?php echo $type_labels[$item['type']]['color']; ?> me-2">
                                        <?php echo $type_labels[$item['type']]['label']; ?>
                                    </span>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <small class="text-muted"><?php echo $item['url']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <span class="link-badge" style="background:#fff3cd;color:#856404;">
                                    <i class="bx bx-link-external me-1"></i> Link Vermiyor
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <button class="btn btn-sm btn-primary" 
                                        onclick="getSuggestions('<?php echo $item['type']; ?>', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?>')">
                                    <i class="bx bx-bulb me-1"></i> Link Önerisi Al
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- AI Önerileri -->
                <div class="tab-pane fade" id="suggestions-tab">
                    <div class="text-center py-5" id="suggestionsPlaceholder">
                        <i class="bx bx-bulb text-warning fs-1 mb-3"></i>
                        <h5>AI Link Önerileri</h5>
                        <p class="text-muted">Bir içerik seçip "Öneri" butonuna tıklayarak AI destekli link önerileri alabilirsiniz.</p>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button class="btn btn-warning" onclick="getAllSuggestions()">
                                <i class="bx bx-magic-wand me-1"></i> Öneri Listesi Al
                            </button>
                            <button class="btn btn-danger btn-lg" onclick="autoFixAllOrphans()">
                                <i class="bx bx-bot me-1"></i> Tümünü AI ile Otomatik Düzelt
                                <span class="badge bg-light text-danger ms-2"><?php echo count($orphan_pages); ?></span>
                            </button>
                        </div>
                        <p class="text-muted small mt-3">
                            <i class="bx bx-info-circle"></i> 
                            "Otomatik Düzelt" butonu orphan sayfalara uygun içeriklerden otomatik link ekler.
                        </p>
                    </div>
                    <div id="suggestionsContent" style="display:none;"></div>
                    
                    <!-- Progress Bar -->
                    <div id="autoFixProgress" style="display:none;" class="mt-4">
                        <div class="card">
                            <div class="card-header bg-danger bg-opacity-10">
                                <h6 class="mb-0"><i class="bx bx-bot me-2"></i>Otomatik Düzeltme İşlemi</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span id="progressText">Hazırlanıyor...</span>
                                    <span id="progressPercent">0%</span>
                                </div>
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" 
                                         id="progressBar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div id="progressLog" class="bg-dark text-light p-3 rounded" 
                                     style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                </div>
                                <div class="mt-3 text-center" id="progressActions" style="display:none;">
                                    <button class="btn btn-success" onclick="location.reload()">
                                        <i class="bx bx-refresh me-1"></i> Sayfayı Yenile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Link Detay Modal -->
<div class="modal fade" id="linkDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-link me-2"></i>Link Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="linkDetailContent">
                <div class="text-center py-4">
                    <i class="bx bx-loader-alt bx-spin fs-1"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Öneri Modal -->
<div class="modal fade" id="suggestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title"><i class="bx bx-bulb text-warning me-2"></i>AI Link Önerileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="suggestionContent">
                <div class="text-center py-4">
                    <i class="bx bx-loader-alt bx-spin fs-1 text-warning"></i>
                    <p class="mt-2">AI önerileri hazırlanıyor...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="editPageBtn" style="display:none;" onclick="editTargetPage()">
                    <i class="bx bx-edit me-1"></i> Hedef Sayfayı Düzenle
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Sayfa yenile
function refreshAnalysis() {
    location.reload();
}

// Link detaylarını göster
function showLinkDetails(type, id, title) {
    const modal = new bootstrap.Modal(document.getElementById('linkDetailModal'));
    modal.show();
    
    document.getElementById('linkDetailContent').innerHTML = `
        <div class="text-center py-4">
            <i class="bx bx-loader-alt bx-spin fs-1"></i>
            <p class="mt-2">Yükleniyor...</p>
        </div>
    `;
    
    fetch('ajax/get_link_details.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type, id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = `
                <h6 class="mb-3">${title}</h6>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-success bg-opacity-10">
                                <i class="bx bx-down-arrow-alt text-success me-1"></i>
                                Gelen Linkler (${data.incoming.length})
                            </div>
                            <div class="card-body" style="max-height:300px;overflow-y:auto;">
                                ${data.incoming.length > 0 ? 
                                    data.incoming.map(link => `
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-success me-2">${link.type}</span>
                                            <small>${link.title}</small>
                                        </div>
                                    `).join('') : 
                                    '<p class="text-muted mb-0">Gelen link yok</p>'
                                }
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary bg-opacity-10">
                                <i class="bx bx-up-arrow-alt text-primary me-1"></i>
                                Giden Linkler (${data.outgoing.length})
                            </div>
                            <div class="card-body" style="max-height:300px;overflow-y:auto;">
                                ${data.outgoing.length > 0 ? 
                                    data.outgoing.map(link => `
                                        <div class="mb-2">
                                            <small class="text-muted">${link}</small>
                                        </div>
                                    `).join('') : 
                                    '<p class="text-muted mb-0">Giden link yok</p>'
                                }
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('linkDetailContent').innerHTML = html;
        } else {
            document.getElementById('linkDetailContent').innerHTML = `
                <div class="alert alert-danger">${data.message || 'Bir hata oluştu'}</div>
            `;
        }
    })
    .catch(error => {
        document.getElementById('linkDetailContent').innerHTML = `
            <div class="alert alert-danger">Bir hata oluştu: ${error.message}</div>
        `;
    });
}

// Mevcut öneri item'ı
let currentSuggestionItem = null;

// AI Önerileri al
function getSuggestions(type, id, title) {
    currentSuggestionItem = { type, id, title };
    const modal = new bootstrap.Modal(document.getElementById('suggestionModal'));
    modal.show();
    
    document.getElementById('suggestionContent').innerHTML = `
        <div class="text-center py-4">
            <i class="bx bx-loader-alt bx-spin fs-1 text-warning"></i>
            <p class="mt-2">AI önerileri hazırlanıyor...</p>
            <small class="text-muted">"${title}" için analiz yapılıyor</small>
        </div>
    `;
    document.getElementById('editPageBtn').style.display = 'none';
    
    fetch('ajax/get_link_suggestions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type, id, title })
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', text);
                throw new Error('Sunucu geçersiz yanıt döndürdü');
            }
        });
    })
    .then(data => {
        if (data.success) {
            let html = `
                <div class="alert alert-info mb-3">
                    <i class="bx bx-info-circle me-1"></i>
                    <strong>"${title}"</strong> sayfasına şu içeriklerden link verebilirsiniz:
                </div>
                <p class="text-muted small mb-3">
                    <i class="bx bx-bulb text-warning"></i> 
                    Aşağıdaki sayfaları düzenleyerek "<strong>${title}</strong>" sayfasına link ekleyin.
                </p>
            `;
            
            if (data.suggestions && data.suggestions.length > 0) {
                data.suggestions.forEach((suggestion, index) => {
                    const editUrl = suggestion.type === 'blog' ? 'blog_edit.php?id=' + suggestion.id :
                                   (suggestion.type === 'page' ? 'page_edit.php?id=' + suggestion.id : 
                                    'service_edit.php?id=' + suggestion.id);
                    html += `
                        <div class="suggestion-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span class="badge bg-${suggestion.type === 'blog' ? 'primary' : (suggestion.type === 'page' ? 'success' : 'info')} me-2">
                                        ${suggestion.type === 'blog' ? 'Blog' : (suggestion.type === 'page' ? 'Sayfa' : 'Hizmet')}
                                    </span>
                                    <strong>${suggestion.title}</strong>
                                    <p class="text-muted small mb-1 mt-1">${suggestion.url}</p>
                                    <p class="mb-0 small"><strong>Önerilen Anchor Text:</strong> <code>"${suggestion.anchor_text}"</code></p>
                                    <p class="mb-0 small text-muted">${suggestion.reason}</p>
                                </div>
                                <div class="btn-group-vertical">
                                    <a href="${editUrl}" class="btn btn-sm btn-success" target="_blank">
                                        <i class="bx bx-edit"></i> Düzenle
                                    </a>
                                    <button class="btn btn-sm btn-outline-primary mt-1" onclick="copyLinkHtml('${suggestion.url}', '${suggestion.anchor_text}')">
                                        <i class="bx bx-copy"></i> Kopyala
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                // Hedef sayfayı düzenleme butonu göster
                document.getElementById('editPageBtn').style.display = 'inline-block';
            } else {
                html += `
                    <div class="text-center py-4">
                        <i class="bx bx-search text-muted fs-1"></i>
                        <p class="text-muted mt-2">Bu içerik için uygun link önerisi bulunamadı.</p>
                        <small class="text-muted">İçeriğinize anahtar kelimeler ekleyerek daha iyi sonuçlar alabilirsiniz.</small>
                    </div>
                `;
            }
            
            document.getElementById('suggestionContent').innerHTML = html;
        } else {
            document.getElementById('suggestionContent').innerHTML = `
                <div class="alert alert-danger">${data.message || 'Bir hata oluştu'}</div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('suggestionContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="bx bx-error-circle me-2"></i>
                Bir hata oluştu: ${error.message}
            </div>
            <p class="text-muted small">Lütfen sayfayı yenileyip tekrar deneyin.</p>
        `;
    });
}

// Hedef sayfayı düzenle
function editTargetPage() {
    if (!currentSuggestionItem) return;
    
    let editUrl = '';
    switch(currentSuggestionItem.type) {
        case 'blog': editUrl = 'blog_edit.php?id=' + currentSuggestionItem.id; break;
        case 'page': editUrl = 'page_edit.php?id=' + currentSuggestionItem.id; break;
        case 'service': editUrl = 'service_edit.php?id=' + currentSuggestionItem.id; break;
    }
    if (editUrl) window.open(editUrl, '_blank');
}

// Link HTML'ini kopyala
function copyLinkHtml(url, anchorText) {
    const html = `<a href="${url}">${anchorText}</a>`;
    navigator.clipboard.writeText(html).then(() => {
        alert('Link HTML\'i kopyalandı!');
    });
}

// Tüm orphan sayfaları otomatik düzelt
async function autoFixAllOrphans() {
    if (!confirm('Bu işlem <?php echo count($orphan_pages); ?> orphan sayfaya otomatik link ekleyecek.\n\nBu işlem birkaç dakika sürebilir. Devam etmek istiyor musunuz?')) {
        return;
    }
    
    // UI'ı hazırla
    document.getElementById('suggestionsPlaceholder').style.display = 'none';
    document.getElementById('suggestionsContent').style.display = 'none';
    document.getElementById('autoFixProgress').style.display = 'block';
    
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');
    const progressLog = document.getElementById('progressLog');
    const progressActions = document.getElementById('progressActions');
    
    progressLog.innerHTML = '';
    
    function log(message, type = 'info') {
        const colors = { info: '#17a2b8', success: '#28a745', error: '#dc3545', warning: '#ffc107' };
        const icons = { info: 'ℹ️', success: '✅', error: '❌', warning: '⚠️' };
        const time = new Date().toLocaleTimeString('tr-TR');
        progressLog.innerHTML += `<div style="color:${colors[type]}">[${time}] ${icons[type]} ${message}</div>`;
        progressLog.scrollTop = progressLog.scrollHeight;
    }
    
    log('Otomatik düzeltme işlemi başlatılıyor...', 'info');
    
    try {
        // İlk olarak orphan sayfaları al
        log('Orphan sayfalar analiz ediliyor...', 'info');
        
        const response = await fetch('ajax/auto_fix_orphan_links.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'start' })
        });
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Parse error:', text);
            throw new Error('Sunucu geçersiz yanıt döndürdü');
        }
        
        if (!data.success) {
            throw new Error(data.message || 'İşlem başlatılamadı');
        }
        
        const orphanPages = data.orphan_pages;
        const total = orphanPages.length;
        
        log(`Toplam ${total} orphan sayfa tespit edildi.`, 'info');
        
        let fixed = 0;
        let failed = 0;
        
        // Her orphan sayfa için işlem yap
        for (let i = 0; i < orphanPages.length; i++) {
            const page = orphanPages[i];
            const percent = Math.round(((i + 1) / total) * 100);
            
            progressBar.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
            progressText.textContent = `İşleniyor: ${page.title} (${i + 1}/${total})`;
            
            log(`[${i + 1}/${total}] "${page.title}" için link aranıyor...`, 'info');
            
            try {
                const fixResponse = await fetch('ajax/auto_fix_orphan_links.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'fix',
                        type: page.type,
                        id: page.id,
                        title: page.title,
                        url: page.url
                    })
                });
                
                const fixText = await fixResponse.text();
                let fixData;
                try {
                    fixData = JSON.parse(fixText);
                } catch (e) {
                    log(`"${page.title}" - JSON hatası`, 'error');
                    failed++;
                    continue;
                }
                
                if (fixData.success && fixData.links_added > 0) {
                    log(`"${page.title}" - ${fixData.links_added} link eklendi: ${fixData.sources.join(', ')}`, 'success');
                    fixed++;
                } else if (fixData.success && fixData.links_added === 0) {
                    log(`"${page.title}" - Uygun kaynak bulunamadı`, 'warning');
                } else {
                    log(`"${page.title}" - ${fixData.message || 'Hata'}`, 'error');
                    failed++;
                }
            } catch (err) {
                log(`"${page.title}" - Hata: ${err.message}`, 'error');
                failed++;
            }
            
            // Rate limiting - her işlemden sonra kısa bekle
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        
        // Tamamlandı
        progressBar.style.width = '100%';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-success');
        progressText.textContent = 'Tamamlandı!';
        progressPercent.textContent = '100%';
        
        log('', 'info');
        log('═══════════════════════════════════════', 'info');
        log(`İşlem tamamlandı! Toplam: ${total}, Düzeltilen: ${fixed}, Başarısız: ${failed}`, fixed > 0 ? 'success' : 'warning');
        log('═══════════════════════════════════════', 'info');
        
        progressActions.style.display = 'block';
        
    } catch (error) {
        log(`Kritik hata: ${error.message}`, 'error');
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
        progressActions.style.display = 'block';
    }
}

// Tüm orphan sayfalar için öneri al
function getAllSuggestions() {
    document.getElementById('suggestionsPlaceholder').style.display = 'none';
    document.getElementById('suggestionsContent').style.display = 'block';
    document.getElementById('suggestionsContent').innerHTML = `
        <div class="text-center py-4">
            <i class="bx bx-loader-alt bx-spin fs-1 text-warning"></i>
            <p class="mt-2">Tüm orphan sayfalar için öneriler hazırlanıyor...</p>
        </div>
    `;
    
    fetch('ajax/get_all_link_suggestions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', text);
                throw new Error('Sunucu geçersiz yanıt döndürdü');
            }
        });
    })
    .then(data => {
        if (data.success && data.results) {
            let html = '<div class="alert alert-info mb-3"><i class="bx bx-info-circle me-1"></i> Aşağıdaki sayfalara link alabilmesi için önerilen kaynaklardan link ekleyin.</div>';
            
            data.results.forEach(result => {
                const targetEditUrl = result.type === 'blog' ? 'blog_edit.php?id=' + result.id :
                                     (result.type === 'page' ? 'page_edit.php?id=' + result.id : 
                                      'service_edit.php?id=' + result.id);
                html += `
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${result.title}</strong>
                                <span class="badge bg-danger ms-2">Orphan</span>
                            </div>
                            <a href="${targetEditUrl}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bx bx-edit"></i> Düzenle
                            </a>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-2">Bu sayfaya şu kaynaklardan link verebilirsiniz:</p>
                `;
                
                if (result.suggestions && result.suggestions.length > 0) {
                    result.suggestions.forEach(suggestion => {
                        const editUrl = suggestion.type === 'blog' ? 'blog_edit.php?id=' + suggestion.id :
                                       (suggestion.type === 'page' ? 'page_edit.php?id=' + suggestion.id : 
                                        'service_edit.php?id=' + suggestion.id);
                        html += `
                            <div class="suggestion-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-secondary me-1">${suggestion.type === 'blog' ? 'Blog' : (suggestion.type === 'page' ? 'Sayfa' : 'Hizmet')}</span>
                                        <strong>${suggestion.title}</strong>
                                        <p class="mb-0 small">Anchor: <code>"${suggestion.anchor_text}"</code></p>
                                    </div>
                                    <div class="btn-group">
                                        <a href="${editUrl}" class="btn btn-sm btn-success" target="_blank" title="Düzenle">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-primary" onclick="copyLinkHtml('${suggestion.url}', '${suggestion.anchor_text}')" title="Kopyala">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html += '<p class="text-muted">Bu sayfa için uygun öneri bulunamadı.</p>';
                }
                
                html += '</div></div>';
            });
            
            document.getElementById('suggestionsContent').innerHTML = html || '<p class="text-muted">Öneri bulunamadı</p>';
        } else {
            document.getElementById('suggestionsContent').innerHTML = `
                <div class="alert alert-warning">${data.message || 'Öneri bulunamadı'}</div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('suggestionsContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="bx bx-error-circle me-2"></i>
                Bir hata oluştu: ${error.message}
            </div>
            <p class="text-muted small">Lütfen sayfayı yenileyip tekrar deneyin.</p>
        `;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>

