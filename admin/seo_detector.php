<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once 'includes/header.php';

$page_title = "SEO Detector";

// Tüm içerikleri al
$blog_posts = [];
$pages = [];
$services = [];

// Blog yazıları
$blog_result = $conn->query("SELECT id, baslik, slug, seo_title, meta_description, meta_keywords, focus_keyword, seo_score, kapak_foto, icerik, durum, created_at FROM blog_posts ORDER BY created_at DESC");
if ($blog_result) {
    while ($row = $blog_result->fetch_assoc()) {
        $blog_posts[] = $row;
    }
}

// Sayfalar
$pages_result = $conn->query("SELECT id, title, slug, seo_title, meta_description, meta_keywords, focus_keyword, seo_score, content, status FROM pages ORDER BY id DESC");
if ($pages_result) {
    while ($row = $pages_result->fetch_assoc()) {
        $pages[] = $row;
    }
}

// Hizmetler (eğer tablo varsa)
$services_check = $conn->query("SHOW TABLES LIKE 'services'");
if ($services_check && $services_check->num_rows > 0) {
    // Sütun kontrolü
    $col_check = $conn->query("SHOW COLUMNS FROM services LIKE 'meta_description'");
    if ($col_check && $col_check->num_rows > 0) {
        $services_result = $conn->query("SELECT id, ana_baslik as title, slug, seo_title, meta_description, meta_keywords, focus_keyword, seo_score, status FROM services ORDER BY id DESC");
        if ($services_result) {
            while ($row = $services_result->fetch_assoc()) {
                $services[] = $row;
            }
        }
    }
}

// Türkçe kelime sayma fonksiyonu
function countTurkishWords($text) {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    if (empty($text)) return 0;
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
}

// SEO Analiz Fonksiyonu
function analyzeSeoIssues($item, $type = 'blog')
{
    $issues = [];
    $warnings = [];
    $success = [];
    $score = 0;

    // Başlık
    $title = $type === 'blog' ? ($item['baslik'] ?? '') : ($item['title'] ?? '');
    $title_len = mb_strlen($title);

    if (empty($title)) {
        $issues[] = 'Başlık eksik';
    } else {
        if ($title_len < 30) {
            $warnings[] = "Başlık kısa ({$title_len} karakter)";
        } elseif ($title_len > 70) {
            $warnings[] = "Başlık uzun ({$title_len} karakter)";
        } else {
            $success[] = 'Başlık ideal';
            $score += 15;
        }
    }

    // SEO Title
    if (empty($item['seo_title'])) {
        $warnings[] = 'SEO başlığı eksik';
    } else {
        $seo_len = mb_strlen($item['seo_title']);
        if ($seo_len >= 30 && $seo_len <= 60) {
            $success[] = 'SEO başlığı ideal';
            $score += 10;
        } else {
            $warnings[] = "SEO başlığı uygun değil ({$seo_len} karakter)";
        }
    }

    // Meta Description
    if (empty($item['meta_description'])) {
        $issues[] = 'Meta açıklaması eksik';
    } else {
        $desc_len = mb_strlen($item['meta_description']);
        if ($desc_len >= 120 && $desc_len <= 160) {
            $success[] = 'Meta açıklaması ideal';
            $score += 20;
        } elseif ($desc_len < 120) {
            $warnings[] = "Meta açıklaması kısa ({$desc_len} karakter)";
            $score += 10;
        } else {
            $warnings[] = "Meta açıklaması uzun ({$desc_len} karakter)";
            $score += 10;
        }
    }

    // Meta Keywords
    if (empty($item['meta_keywords'])) {
        $warnings[] = 'Meta anahtar kelimeleri eksik';
    } else {
        $success[] = 'Meta keywords var';
        $score += 5;
    }

    // Focus Keyword
    if (empty($item['focus_keyword'])) {
        $warnings[] = 'Odak anahtar kelime belirlenmemiş';
    } else {
        $score += 10;
        // Başlıkta var mı?
        if (stripos($title, $item['focus_keyword']) !== false) {
            $success[] = 'Odak kelime başlıkta var';
            $score += 10;
        } else {
            $warnings[] = 'Odak kelime başlıkta yok';
        }
    }

    // Slug
    if (empty($item['slug'])) {
        $issues[] = 'URL (slug) eksik';
    } else {
        $success[] = 'URL tanımlı';
        $score += 5;
    }

    // İçerik uzunluğu (sadece blog ve sayfa)
    $content = $type === 'blog' ? ($item['icerik'] ?? '') : ($item['content'] ?? '');
    if (!empty($content)) {
        $word_count = countTurkishWords($content);
        if ($word_count >= 300) {
            $success[] = "İçerik yeterli ({$word_count} kelime)";
            $score += 15;
        } elseif ($word_count >= 100) {
            $warnings[] = "İçerik biraz kısa ({$word_count} kelime)";
            $score += 8;
        } else {
            $warnings[] = "İçerik çok kısa ({$word_count} kelime)";
        }
    }

    // Görsel (blog için)
    if ($type === 'blog' && empty($item['kapak_foto'])) {
        $warnings[] = 'Kapak fotoğrafı eksik';
    } elseif ($type === 'blog') {
        $success[] = 'Kapak fotoğrafı var';
        $score += 10;
    }

    // Sayfa ve servis için max 90 puan (kapak foto yok), blog için 100
    $maxScore = ($type === 'blog') ? 100 : 90;
    
    // Yüzdeye çevir
    $percentage = round(($score / $maxScore) * 100);

    return [
        'issues' => $issues,
        'warnings' => $warnings,
        'success' => $success,
        'score' => min($percentage, 100),
        'issue_count' => count($issues),
        'warning_count' => count($warnings)
    ];
}

// İstatistikleri hesapla
$total_items = count($blog_posts) + count($pages) + count($services);
$critical_issues = 0;
$warnings_total = 0;
$avg_score = 0;
$scores = [];

// Blog analizi
foreach ($blog_posts as &$post) {
    $analysis = analyzeSeoIssues($post, 'blog');
    $post['analysis'] = $analysis;
    $critical_issues += $analysis['issue_count'];
    $warnings_total += $analysis['warning_count'];
    $scores[] = $analysis['score'];
}

// Sayfa analizi
foreach ($pages as &$page) {
    $analysis = analyzeSeoIssues($page, 'page');
    $page['analysis'] = $analysis;
    $critical_issues += $analysis['issue_count'];
    $warnings_total += $analysis['warning_count'];
    $scores[] = $analysis['score'];
}

// Hizmet analizi
foreach ($services as &$service) {
    $analysis = analyzeSeoIssues($service, 'service');
    $service['analysis'] = $analysis;
    $critical_issues += $analysis['issue_count'];
    $warnings_total += $analysis['warning_count'];
    $scores[] = $analysis['score'];
}

$avg_score = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;

// Skora göre renk belirleme fonksiyonu
function getScoreClass($score)
{
    if ($score >= 80)
        return 'success';
    if ($score >= 50)
        return 'warning';
    return 'danger';
}
?>

<style>
    /* SEO Detector Styles */
    .seo-stats-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .seo-stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .seo-stats-card .card-body {
        padding: 25px;
    }

    .seo-stats-card .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .seo-stats-card .stat-number {
        font-size: 36px;
        font-weight: 700;
        line-height: 1;
    }

    .seo-stats-card .stat-label {
        font-size: 14px;
        color: #6c757d;
        margin-top: 5px;
    }

    .score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        color: #fff;
        margin: 0 auto 15px;
    }

    .content-table {
        border-radius: 10px;
        overflow: hidden;
    }

    .content-table th {
        background: #f8f9fa;
        font-weight: 600;
        border: none;
    }

    .content-table td {
        vertical-align: middle;
        border-color: #f0f0f0;
    }

    .seo-score-badge {
        min-width: 50px;
        padding: 8px 12px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 20px;
    }

    .issue-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        margin-right: 5px;
        margin-bottom: 5px;
    }

    .issue-badge.critical {
        background: #ffebee;
        color: #c62828;
    }

    .issue-badge.warning {
        background: #fff8e1;
        color: #f57f17;
    }

    .issue-badge.success {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .quick-action-btn {
        padding: 5px 10px;
        font-size: 12px;
        border-radius: 5px;
    }

    .filter-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 20px;
    }

    .filter-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 12px 20px;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }

    .filter-tabs .nav-link:hover {
        color: #0d6efd;
        border-color: transparent;
    }

    .filter-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
        background: transparent;
    }

    .bulk-actions {
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .item-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    /* Animation for analysis */
    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }

        100% {
            opacity: 1;
        }
    }

    .analyzing {
        animation: pulse 1.5s infinite;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-search-plus text-primary me-2"></i>SEO Detector</h1>
            <p class="text-muted mb-0">Tüm içeriklerinizin SEO durumunu analiz edin ve eksiklikleri giderin.</p>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="refreshAnalysis()">
                <i class="fas fa-sync-alt me-1"></i> Yenile
            </button>
            <a href="add_seo_fields.php" class="btn btn-secondary">
                <i class="fas fa-database me-1"></i> Migration
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card seo-stats-card bg-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <div class="stat-number text-primary"><?php echo $total_items; ?></div>
                            <div class="stat-label">Toplam İçerik</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card seo-stats-card bg-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div>
                            <div class="stat-number text-danger"><?php echo $critical_issues; ?></div>
                            <div class="stat-label">Kritik Sorun</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card seo-stats-card bg-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="stat-number text-warning"><?php echo $warnings_total; ?></div>
                            <div class="stat-label">Uyarı</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card seo-stats-card bg-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div
                            class="stat-icon bg-<?php echo getScoreClass($avg_score); ?> bg-opacity-10 text-<?php echo getScoreClass($avg_score); ?> me-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <div class="stat-number text-<?php echo getScoreClass($avg_score); ?>">
                                <?php echo $avg_score; ?>%
                            </div>
                            <div class="stat-label">Ortalama SEO Skoru</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Tabs -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs filter-tabs" id="contentTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all-content">
                        <i class="fas fa-layer-group me-1"></i> Tümü
                        <span class="badge bg-secondary ms-1"><?php echo $total_items; ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#blog-content">
                        <i class="fas fa-blog me-1"></i> Blog Yazıları
                        <span class="badge bg-primary ms-1"><?php echo count($blog_posts); ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#page-content">
                        <i class="fas fa-file me-1"></i> Sayfalar
                        <span class="badge bg-success ms-1"><?php echo count($pages); ?></span>
                    </button>
                </li>
                <?php if (count($services) > 0): ?>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#service-content">
                            <i class="fas fa-cogs me-1"></i> Hizmetler
                            <span class="badge bg-info ms-1"><?php echo count($services); ?></span>
                        </button>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#issues-content">
                        <i class="fas fa-bug me-1"></i> Sorunlar
                        <span class="badge bg-danger ms-1"><?php echo $critical_issues; ?></span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <!-- Quick Select Buttons -->
            <div class="mb-3 d-flex gap-2 flex-wrap">
                <button class="btn btn-warning btn-sm" onclick="selectByScore('warning')">
                    <i class="fas fa-check-square me-1"></i> Sarıları Seç (50-79%)
                </button>
                <button class="btn btn-danger btn-sm" onclick="selectByScore('danger')">
                    <i class="fas fa-check-square me-1"></i> Kırmızıları Seç (&lt;50%)
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="selectByScore('all')">
                    <i class="fas fa-check-square me-1"></i> Tümünü Seç
                </button>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions d-none" id="bulkActions">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="fw-bold" id="selectedCount">0</span> öğe seçildi
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm" onclick="bulkFixWithAI(85)">
                            <i class="fas fa-magic me-1"></i> AI ile Düzelt (Min %85)
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="bulkFixWithAI(100)">
                            <i class="fas fa-rocket me-1"></i> Maks Düzelt (%100)
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                            <i class="fas fa-times me-1"></i> Temizle
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="contentTabsContent">
                <!-- All Content Tab -->
                <div class="tab-pane fade show active" id="all-content">
                    <?php
                    $all_items = [];
                    foreach ($blog_posts as $item) {
                        $item['_type'] = 'blog';
                        $item['_title'] = $item['baslik'];
                        $all_items[] = $item;
                    }
                    foreach ($pages as $item) {
                        $item['_type'] = 'page';
                        $item['_title'] = $item['title'];
                        $all_items[] = $item;
                    }
                    foreach ($services as $item) {
                        $item['_type'] = 'service';
                        $item['_title'] = $item['title'];
                        $all_items[] = $item;
                    }
                    // Sort by score ascending (worst first)
                    usort($all_items, function ($a, $b) {
                        return ($a['analysis']['score'] ?? 0) - ($b['analysis']['score'] ?? 0);
                    });
                    ?>
                    <?php include 'includes/seo_content_table.php'; ?>
                </div>

                <!-- Blog Tab -->
                <div class="tab-pane fade" id="blog-content">
                    <?php
                    $all_items = [];
                    foreach ($blog_posts as $item) {
                        $item['_type'] = 'blog';
                        $item['_title'] = $item['baslik'];
                        $all_items[] = $item;
                    }
                    usort($all_items, function ($a, $b) {
                        return ($a['analysis']['score'] ?? 0) - ($b['analysis']['score'] ?? 0);
                    });
                    ?>
                    <?php include 'includes/seo_content_table.php'; ?>
                </div>

                <!-- Pages Tab -->
                <div class="tab-pane fade" id="page-content">
                    <?php
                    $all_items = [];
                    foreach ($pages as $item) {
                        $item['_type'] = 'page';
                        $item['_title'] = $item['title'];
                        $all_items[] = $item;
                    }
                    usort($all_items, function ($a, $b) {
                        return ($a['analysis']['score'] ?? 0) - ($b['analysis']['score'] ?? 0);
                    });
                    ?>
                    <?php include 'includes/seo_content_table.php'; ?>
                </div>

                <?php if (count($services) > 0): ?>
                    <!-- Services Tab -->
                    <div class="tab-pane fade" id="service-content">
                        <?php
                        $all_items = [];
                        foreach ($services as $item) {
                            $item['_type'] = 'service';
                            $item['_title'] = $item['title'];
                            $all_items[] = $item;
                        }
                        usort($all_items, function ($a, $b) {
                            return ($a['analysis']['score'] ?? 0) - ($b['analysis']['score'] ?? 0);
                        });
                        ?>
                        <?php include 'includes/seo_content_table.php'; ?>
                    </div>
                <?php endif; ?>

                <!-- Issues Tab -->
                <div class="tab-pane fade" id="issues-content">
                    <?php
                    $all_items = [];
                    foreach ($blog_posts as $item) {
                        if ($item['analysis']['issue_count'] > 0) {
                            $item['_type'] = 'blog';
                            $item['_title'] = $item['baslik'];
                            $all_items[] = $item;
                        }
                    }
                    foreach ($pages as $item) {
                        if ($item['analysis']['issue_count'] > 0) {
                            $item['_type'] = 'page';
                            $item['_title'] = $item['title'];
                            $all_items[] = $item;
                        }
                    }
                    foreach ($services as $item) {
                        if ($item['analysis']['issue_count'] > 0) {
                            $item['_type'] = 'service';
                            $item['_title'] = $item['title'];
                            $all_items[] = $item;
                        }
                    }
                    usort($all_items, function ($a, $b) {
                        return $b['analysis']['issue_count'] - $a['analysis']['issue_count'];
                    });
                    ?>
                    <?php if (count($all_items) > 0): ?>
                        <?php include 'includes/seo_content_table.php'; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                            <h4 class="text-success">Tebrikler!</h4>
                            <p class="text-muted">Kritik SEO sorunu bulunamadı.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fix Modal -->
<div class="modal fade" id="fixModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-magic me-2"></i>AI ile SEO Düzeltme</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="fixModalContent">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                        <p>SEO içerikleri oluşturuluyor...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" id="applyFixBtn" style="display:none;" onclick="saveSeoBilgileri()">
                    <i class="fas fa-save me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedItems = new Set();
let currentFixItem = null;

// Checkbox işlemleri
document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        if (this.id === 'selectAll') {
            document.querySelectorAll('.item-checkbox:not(#selectAll)').forEach(c => {
                c.checked = this.checked;
                const key = c.dataset.type + '-' + c.dataset.id;
                this.checked ? selectedItems.add(key) : selectedItems.delete(key);
            });
        } else {
            const key = this.dataset.type + '-' + this.dataset.id;
            this.checked ? selectedItems.add(key) : selectedItems.delete(key);
        }
        updateBulkActions();
    });
});

function updateBulkActions() {
    const ba = document.getElementById('bulkActions');
    const sc = document.getElementById('selectedCount');
    if (selectedItems.size > 0) {
        ba.classList.remove('d-none');
        sc.textContent = selectedItems.size;
    } else {
        ba.classList.add('d-none');
    }
}

function clearSelection() {
    selectedItems.clear();
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
    updateBulkActions();
}

function showAllWarnings(el, title) {
    try {
        const w = JSON.parse(el.dataset.warnings);
        alert(title + '\n\n' + w.join('\n'));
    } catch(e) {}
}

// AI ile Düzelt
function fixWithAiV2(type, id, title) {
    currentFixItem = { type, id, title };
    
    const modal = new bootstrap.Modal(document.getElementById('fixModal'));
    modal.show();
    
    document.getElementById('applyFixBtn').style.display = 'none';
    document.getElementById('fixModalContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3"></div>
            <p>SEO içerikleri oluşturuluyor...</p>
            <small class="text-muted">"${title}"</small>
        </div>
    `;
    
    fetch('ajax/generate_seo_content.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({type, id, action: 'generate'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Ek bilgileri sakla
            currentFixItem.wordCount = data.word_count || 0;
            currentFixItem.hasImage = data.has_image || false;
            currentFixItem.hasSlug = data.has_slug || false;
            
            document.getElementById('fixModalContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sayfa Başlığı (H1) <span class="badge bg-success">+15p</span></label>
                            <input type="text" class="form-control" id="fix_h1_title" value="${escapeHtml(data.h1_title || title)}" oninput="updateScorePreview()">
                            <small class="text-muted">Karakter: <span id="h1_len">0</span>/70 (ideal: 30-70)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Odak Anahtar Kelime <span class="badge bg-success">+10p (+10 H1'de)</span></label>
                            <input type="text" class="form-control" id="fix_focus_keyword" value="${escapeHtml(data.focus_keyword || '')}" oninput="updateScorePreview()">
                            <small id="focus_status" class="text-muted"></small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">SEO Başlığı <span class="badge bg-success">+10p</span></label>
                    <input type="text" class="form-control" id="fix_seo_title" value="${escapeHtml(data.seo_title || '')}" maxlength="70" oninput="updateScorePreview()">
                    <small class="text-muted">Karakter: <span id="seo_len">0</span>/60 (ideal: 30-60)</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Meta Açıklaması <span class="badge bg-success">+20p</span></label>
                    <textarea class="form-control" id="fix_meta_description" rows="3" maxlength="200" oninput="updateScorePreview()">${escapeHtml(data.meta_description || '')}</textarea>
                    <small class="text-muted">Karakter: <span id="desc_len">0</span>/160 (ideal: 120-160)</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Anahtar Kelimeler <span class="badge bg-success">+5p</span></label>
                    <input type="text" class="form-control" id="fix_meta_keywords" value="${escapeHtml(data.meta_keywords || '')}" oninput="updateScorePreview()">
                </div>
                
                <div class="card bg-light mt-3">
                    <div class="card-body py-2">
                        <div class="row text-center" id="scoreDetails">
                        </div>
                    </div>
                </div>
                
                <div class="alert mt-3" id="scorePreview">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><strong>Tahmini Skor:</strong> <span id="estScore" class="fs-4">0</span>%</div>
                        <div id="missingPoints" class="text-end small"></div>
                    </div>
                </div>
            `;
            
            updateScorePreview();
            document.getElementById('applyFixBtn').style.display = 'inline-block';
        } else {
            document.getElementById('fixModalContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${data.message || 'SEO içeriği oluşturulamadı.'}
                </div>
            `;
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('fixModalContent').innerHTML = `
            <div class="alert alert-danger">Bir hata oluştu.</div>
        `;
    });
}

// Skor önizleme
function updateScorePreview() {
    let score = 0;
    let maxScore = 90; // Sayfa ve servis için max 90
    let details = [];
    let missing = [];
    
    const contentType = currentFixItem?.type || 'page';
    if (contentType === 'blog') {
        maxScore = 100; // Blog için kapak foto dahil 100
    }
    
    const h1 = document.getElementById('fix_h1_title')?.value || '';
    const seoTitle = document.getElementById('fix_seo_title')?.value || '';
    const metaDesc = document.getElementById('fix_meta_description')?.value || '';
    const keywords = document.getElementById('fix_meta_keywords')?.value || '';
    const focus = document.getElementById('fix_focus_keyword')?.value || '';
    
    // H1 kontrolü (+15)
    const h1Len = h1.length;
    document.getElementById('h1_len').textContent = h1Len;
    if (h1Len >= 30 && h1Len <= 70) {
        score += 15;
        details.push('<span class="text-success">H1 ✓</span>');
        document.getElementById('h1_len').className = 'text-success fw-bold';
    } else {
        missing.push('H1 (30-70 kar)');
        details.push('<span class="text-danger">H1 ✗</span>');
        document.getElementById('h1_len').className = 'text-danger';
    }
    
    // SEO Title kontrolü (+10)
    const seoLen = seoTitle.length;
    document.getElementById('seo_len').textContent = seoLen;
    if (seoLen >= 30 && seoLen <= 60) {
        score += 10;
        details.push('<span class="text-success">SEO ✓</span>');
        document.getElementById('seo_len').className = 'text-success fw-bold';
    } else {
        missing.push('SEO başlık (30-60)');
        details.push('<span class="text-danger">SEO ✗</span>');
        document.getElementById('seo_len').className = 'text-danger';
    }
    
    // Meta Description kontrolü (+20 veya +10)
    const descLen = metaDesc.length;
    document.getElementById('desc_len').textContent = descLen;
    if (descLen >= 120 && descLen <= 160) {
        score += 20;
        details.push('<span class="text-success">Meta ✓</span>');
        document.getElementById('desc_len').className = 'text-success fw-bold';
    } else if (descLen > 0) {
        score += 10;
        missing.push('Meta (120-160)');
        details.push('<span class="text-warning">Meta ~</span>');
        document.getElementById('desc_len').className = 'text-warning';
    } else {
        missing.push('Meta açıklama');
        details.push('<span class="text-danger">Meta ✗</span>');
        document.getElementById('desc_len').className = 'text-danger';
    }
    
    // Keywords kontrolü (+5)
    if (keywords.trim()) {
        score += 5;
        details.push('<span class="text-success">Keys ✓</span>');
    } else {
        missing.push('Anahtar kelimeler');
        details.push('<span class="text-danger">Keys ✗</span>');
    }
    
    // Focus keyword kontrolü (+10)
    if (focus.trim()) {
        score += 10;
        details.push('<span class="text-success">Odak ✓</span>');
        
        // H1'de var mı? (+10)
        if (h1.toLowerCase().includes(focus.toLowerCase())) {
            score += 10;
            details.push('<span class="text-success">H1\'de ✓</span>');
            document.getElementById('focus_status').innerHTML = '<span class="text-success">✓ H1\'de mevcut</span>';
        } else {
            missing.push('Odak H1\'de yok');
            details.push('<span class="text-danger">H1\'de ✗</span>');
            document.getElementById('focus_status').innerHTML = '<span class="text-danger">✗ H1\'de yok</span>';
        }
    } else {
        missing.push('Odak kelime');
        details.push('<span class="text-danger">Odak ✗</span>');
        document.getElementById('focus_status').textContent = '';
    }
    
    // Slug (+5) - genellikle var
    const hasSlug = currentFixItem?.hasSlug !== false;
    if (hasSlug) {
        score += 5;
        details.push('<span class="text-success">URL ✓</span>');
    } else {
        missing.push('URL/Slug');
        details.push('<span class="text-danger">URL ✗</span>');
    }
    
    // İçerik (+15 veya +8) - veritabanından
    const wordCount = currentFixItem?.wordCount || 0;
    if (wordCount >= 300) {
        score += 15;
        details.push('<span class="text-success">İçerik ✓</span>');
    } else if (wordCount >= 100) {
        score += 8;
        missing.push('İçerik kısa (' + wordCount + ')');
        details.push('<span class="text-warning">İçerik ~</span>');
    } else {
        missing.push('İçerik (' + wordCount + ' kelime)');
        details.push('<span class="text-danger">İçerik ✗</span>');
    }
    
    // Kapak foto (+10) - sadece blog için
    if (contentType === 'blog') {
        if (currentFixItem?.hasImage) {
            score += 10;
            details.push('<span class="text-success">Foto ✓</span>');
        } else {
            missing.push('Kapak fotoğrafı');
            details.push('<span class="text-danger">Foto ✗</span>');
        }
    }
    
    // Detayları göster
    document.getElementById('scoreDetails').innerHTML = details.map(d => 
        `<div class="col">${d}</div>`
    ).join('');
    
    // Yüzde hesapla (içerik türüne göre max puana oranlı)
    const percentage = Math.round((score / maxScore) * 100);
    
    // Eksikleri göster
    if (missing.length > 0) {
        document.getElementById('missingPoints').innerHTML = 
            '<span class="text-danger"><b>Eksikler:</b> ' + missing.join(', ') + '</span>';
    } else {
        document.getElementById('missingPoints').innerHTML = '<span class="text-success">Tüm kriterler tamam! 🎉</span>';
    }
    
    // Toplam skor göster
    const scoreText = contentType === 'blog' 
        ? percentage + '%' 
        : percentage + '% <small class="text-muted">(maks ' + maxScore + 'p - foto yok)</small>';
    document.getElementById('estScore').innerHTML = scoreText;
    
    const preview = document.getElementById('scorePreview');
    if (percentage >= 80) {
        preview.className = 'alert alert-success mt-3';
    } else if (percentage >= 50) {
        preview.className = 'alert alert-warning mt-3';
    } else {
        preview.className = 'alert alert-danger mt-3';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Kaydet
function saveSeoBilgileri() {
    if (!currentFixItem) return;
    
    const btn = document.getElementById('applyFixBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';
    
    const data = {
        type: currentFixItem.type,
        id: currentFixItem.id,
        seo_title: document.getElementById('fix_seo_title')?.value || '',
        meta_description: document.getElementById('fix_meta_description')?.value || '',
        meta_keywords: document.getElementById('fix_meta_keywords')?.value || '',
        focus_keyword: document.getElementById('fix_focus_keyword')?.value || '',
        h1_title: document.getElementById('fix_h1_title')?.value || '',
        og_title: document.getElementById('fix_seo_title')?.value || '',
        og_description: document.getElementById('fix_meta_description')?.value || ''
    };
    
    fetch('ajax/apply_seo_fix.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            document.getElementById('fixModalContent').innerHTML = `
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-3x mb-3 d-block"></i>
                    <h5>SEO güncellendi!</h5>
                    <p>Yeni Skor: <strong>${result.score}%</strong></p>
                </div>
            `;
            btn.style.display = 'none';
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Hata: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Kaydet';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Bir hata oluştu');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Kaydet';
    });
}

// Skora göre seç
function selectByScore(scoreType) {
    clearSelection();
    
    document.querySelectorAll('.item-checkbox:not(#selectAll)').forEach(cb => {
        const row = cb.closest('tr');
        const badge = row.querySelector('.seo-score-badge');
        if (!badge) return;
        
        const scoreText = badge.textContent.trim();
        const score = parseInt(scoreText);
        
        let shouldSelect = false;
        if (scoreType === 'all') {
            shouldSelect = true;
        } else if (scoreType === 'warning' && score >= 50 && score < 80) {
            shouldSelect = true;
        } else if (scoreType === 'danger' && score < 50) {
            shouldSelect = true;
        }
        
        if (shouldSelect) {
            cb.checked = true;
            const key = cb.dataset.type + '-' + cb.dataset.id;
            selectedItems.add(key);
        }
    });
    
    updateBulkActions();
    
    if (selectedItems.size === 0) {
        alert('Bu kritere uyan içerik bulunamadı.');
    }
}

// Toplu düzelt
function bulkFixWithAI(targetScore = 85) {
    if (selectedItems.size === 0) {
        alert('Lütfen öğe seçin');
        return;
    }
    if (!confirm(selectedItems.size + ' öğe için AI ile SEO düzeltmesi yapılacak.\nHedef: Minimum %' + targetScore + '\n\nDevam edilsin mi?')) return;
    
    const modal = new bootstrap.Modal(document.getElementById('fixModal'));
    modal.show();
    document.getElementById('applyFixBtn').style.display = 'none';
    
    const items = Array.from(selectedItems).map(k => {
        const [type, id] = k.split('-');
        return {type, id};
    });
    
    let done = 0;
    let success = 0;
    let failed = 0;
    const total = items.length;
    
    document.getElementById('fixModalContent').innerHTML = `
        <div class="py-3">
            <div class="d-flex justify-content-between mb-2">
                <span>Hedef: <strong>Min %${targetScore}</strong></span>
                <span id="prog">${done}/${total}</span>
            </div>
            <div class="progress mb-3" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" id="pbar" style="width:0%"></div>
            </div>
            <div id="statusLog" class="small" style="max-height: 200px; overflow-y: auto;"></div>
        </div>
    `;
    
    const statusLog = document.getElementById('statusLog');
    
    async function next() {
        if (done >= total) {
            document.getElementById('fixModalContent').innerHTML = `
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-3x mb-3 d-block"></i>
                    <h5>Toplu Düzeltme Tamamlandı!</h5>
                    <p class="mb-0">
                        <span class="badge bg-success">${success} Başarılı</span>
                        ${failed > 0 ? '<span class="badge bg-danger ms-2">' + failed + ' Başarısız</span>' : ''}
                    </p>
                </div>
            `;
            setTimeout(() => location.reload(), 2000);
            return;
        }
        
        const item = items[done];
        statusLog.innerHTML = `<div class="text-primary"><i class="fas fa-spinner fa-spin me-1"></i> İşleniyor: ${item.type} #${item.id}...</div>` + statusLog.innerHTML;
        
        try {
            const response = await fetch('ajax/generate_seo_content.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({...item, action: 'generate_and_apply', target_score: targetScore})
            });
            const result = await response.json();
            
            if (result.success) {
                const newScore = result.score || 0;
                success++;
                statusLog.innerHTML = `<div class="text-success"><i class="fas fa-check me-1"></i> ${item.type} #${item.id}: %${newScore}</div>` + statusLog.innerHTML.replace(/<div class="text-primary">.*?<\/div>/, '');
            } else {
                failed++;
                statusLog.innerHTML = `<div class="text-danger"><i class="fas fa-times me-1"></i> ${item.type} #${item.id}: Hata</div>` + statusLog.innerHTML.replace(/<div class="text-primary">.*?<\/div>/, '');
            }
        } catch(e) {
            failed++;
            statusLog.innerHTML = `<div class="text-danger"><i class="fas fa-times me-1"></i> ${item.type} #${item.id}: Hata</div>` + statusLog.innerHTML.replace(/<div class="text-primary">.*?<\/div>/, '');
        }
        
        done++;
        document.getElementById('prog').textContent = done + '/' + total;
        document.getElementById('pbar').style.width = Math.round(done/total*100) + '%';
        
        // Biraz bekle ve devam et
        setTimeout(next, 500);
    }
    
    next();
}

function refreshAnalysis() { location.reload(); }

function editItem(type, id) {
    const urls = {blog: 'blog_edit.php?id=', page: 'page_edit.php?id=', service: 'service_edit.php?id='};
    if (urls[type]) location.href = urls[type] + id;
}
</script>

<?php require_once 'includes/footer.php'; ?>