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
        $word_count = str_word_count(strip_tags($content));
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

    return [
        'issues' => $issues,
        'warnings' => $warnings,
        'success' => $success,
        'score' => min($score, 100),
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
            <!-- Bulk Actions -->
            <div class="bulk-actions d-none" id="bulkActions">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-bold" id="selectedCount">0</span> öğe seçildi
                    </div>
                    <div>
                        <button class="btn btn-primary btn-sm me-2" onclick="bulkFixWithAI()">
                            <i class="fas fa-magic me-1"></i> AI ile Toplu Düzelt
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                            <i class="fas fa-times me-1"></i> Seçimi Temizle
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
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-magic me-2"></i>AI ile SEO Düzeltme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                <button type="button" class="btn btn-primary" id="applyFixBtn" disabled onclick="applySeoFixV3()">
                    <i class="fas fa-check me-1"></i> Kaydet (V3)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Selected items tracking
    let selectedItems = new Set();

    // Update bulk actions visibility
    function updateBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');

        if (selectedItems.size > 0) {
            bulkActions.classList.remove('d-none');
            selectedCount.textContent = selectedItems.size;
        } else {
            bulkActions.classList.add('d-none');
        }
    }

    // Select all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.item-checkbox:not(#selectAll)');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            const key = cb.dataset.type + '-' + cb.dataset.id;
            if (this.checked) {
                selectedItems.add(key);
            } else {
                selectedItems.delete(key);
            }
        });
        updateBulkActions();
    });

    // Individual checkboxes
    document.querySelectorAll('.item-checkbox:not(#selectAll)').forEach(cb => {
        cb.addEventListener('change', function () {
            const key = this.dataset.type + '-' + this.dataset.id;
            if (this.checked) {
                selectedItems.add(key);
            } else {
                selectedItems.delete(key);
            }
            updateBulkActions();
        });
    });

    // Clear selection
    function clearSelection() {
        selectedItems.clear();
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        updateBulkActions();
    }

    // Tüm uyarıları göster
    function showAllWarnings(element, title) {
        try {
            const warnings = JSON.parse(element.dataset.warnings);
            if (!warnings || warnings.length === 0) return;

            let warningsList = warnings.map((w, i) => `${i + 1}. ${w}`).join('\n');

            // Modal oluştur
            let modalHtml = `
            <div class="modal fade" id="warningsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning bg-opacity-10">
                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Tüm Uyarılar</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <h6 class="mb-3">${title}</h6>
                            <ul class="list-group">
                                ${warnings.map(w => `
                                    <li class="list-group-item list-group-item-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>${w}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

            // Mevcut modal varsa kaldır
            const existingModal = document.getElementById('warningsModal');
            if (existingModal) existingModal.remove();

            // Modal ekle ve göster
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('warningsModal'));
            modal.show();

            // Modal kapanınca temizle
            document.getElementById('warningsModal').addEventListener('hidden.bs.modal', function () {
                this.remove();
            });
        } catch (e) {
            console.error('Uyarılar gösterilirken hata:', e);
        }
    }

    // Single item fix with AI
    function fixWithAiV2(type, id, title) {
        currentFixItem = { type, id, title };
        const modal = new bootstrap.Modal(document.getElementById('fixModal'));
        modal.show();

        document.getElementById('fixModalContent').innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <p>SEO içerikleri oluşturuluyor...</p>
            <small class="text-muted">"${title}" için analiz ediliyor</small>
        </div>
    `;
        document.getElementById('applyFixBtn').disabled = true;

        // AI ile SEO içeriği oluştur
        fetch('ajax/generate_seo_content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, id, action: 'generate' })
        })
            .then(response => response.json())
            .then(data => {
                console.log("AI RESPONSE:", data); // DEBUG
                alert("AI Yanıtı Geldi: " + JSON.stringify(data)); // DEBUG

                if (data.success) {
                    currentFixData = data;
                    console.log("Setting innerHTML..."); // DEBUG
                    document.getElementById('fixModalContent').innerHTML = `
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    SEO içerikleri ve metin iyileştirmeleri başarıyla oluşturuldu!
                </div>

                <ul class="nav nav-tabs mb-3" id="fixTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#meta-tab">Meta Etiketleri</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#content-tab">İçerik & Başlık</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="meta-tab">
                        <div class="mb-3">
                            <label class="form-label fw-bold">SEO Başlığı</label>
                            <input type="text" class="form-control" id="fix_seo_title" value="${data.seo_title || ''}" maxlength="70">
                            <small class="text-muted">Karakter: <span id="fix_title_count">0</span>/60</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Meta Açıklaması</label>
                            <textarea class="form-control" id="fix_meta_description" rows="3" maxlength="160">${data.meta_description || ''}</textarea>
                            <small class="text-muted">Karakter: <span id="fix_desc_count">0</span>/160</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Meta Anahtar Kelimeler</label>
                            <input type="text" class="form-control" id="fix_meta_keywords" value="${data.meta_keywords || ''}">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Odak Anahtar Kelime</label>
                            <input type="text" class="form-control" id="fix_focus_keyword" value="${data.focus_keyword || ''}">
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="content-tab">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sayfa Başlığı (H1)</label>
                            <input type="text" class="form-control" id="fix_h1_title" value="${data.h1_title || ''}">
                            <small class="text-muted">Sayfanın görünür ana başlığıdır.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Sayfa İçeriği (HTML)</label>
                            <textarea class="form-control" id="fix_content" rows="12" style="font-family: monospace; font-size: 13px;">${data.content || ''}</textarea>
                            <small class="text-muted">HTML formatında içerik. Boş bırakırsanız sadece meta etiketleri güncellenir.</small>
                        </div>
                    </div>
                </div>
            `;

                    // Karakter sayaçlarını güncelle
                    updateFixCounters();
                    document.getElementById('fix_seo_title').addEventListener('input', updateFixCounters);
                    document.getElementById('fix_meta_description').addEventListener('input', updateFixCounters);

                    document.getElementById('applyFixBtn').disabled = false;
                } else {
                    document.getElementById('fixModalContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${data.message || 'SEO içeriği oluşturulamadı. Lütfen OpenAI API anahtarınızı kontrol edin.'}
                </div>
                <p class="text-muted">API anahtarı ayarlamak için <a href="settings.php">Ayarlar</a> sayfasını ziyaret edin.</p>
            `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('fixModalContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Bir hata oluştu. Lütfen tekrar deneyin.
            </div>
        `;
            });
    }

    function updateFixCounters() {
        const titleEl = document.getElementById('fix_seo_title');
        const descEl = document.getElementById('fix_meta_description');
        if (titleEl) document.getElementById('fix_title_count').textContent = titleEl.value.length;
        if (descEl) document.getElementById('fix_desc_count').textContent = descEl.value.length;
    }

    let currentFixItem = null;
    let currentFixData = null;

    // Apply fix
    // Apply fix V3 (Renamed to bust cache)
    function applySeoFixV3() {
        if (!currentFixItem) return;

        const h1TitleEl = document.getElementById('fix_h1_title');
        const contentEl = document.getElementById('fix_content');

        const h1Val = h1TitleEl ? h1TitleEl.value : '';
        const contentVal = contentEl ? contentEl.value : '';

        // DEBUG: Alert values to ensure we are reading them
        alert("Saving V2...\nH1: " + h1Val + "\nContent Length: " + contentVal.length);

        const btn = document.getElementById('applyFixBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';

        const data = {
            type: currentFixItem.type,
            id: currentFixItem.id,
            action: 'apply',
            seo_title: document.getElementById('fix_seo_title').value,
            meta_description: document.getElementById('fix_meta_description').value,
            meta_keywords: document.getElementById('fix_meta_keywords').value,
            focus_keyword: document.getElementById('fix_focus_keyword').value,
            h1_title: h1Val,
            content: contentVal
        };

        fetch('ajax/apply_seo_fix.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('fixModal')).hide();
                    // Sayfayı yenile
                    location.reload();
                } else {
                    alert('Hata: ' + (result.message || 'Kayıt başarısız'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Uygula';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Uygula';
            });
    }

    // Bulk fix with AI
    function bulkFixWithAI() {
        if (selectedItems.size === 0) {
            alert('Lütfen düzeltilecek öğeleri seçin.');
            return;
        }

        if (!confirm(selectedItems.size + ' öğe için AI ile SEO içeriği oluşturulacak. Devam etmek istiyor musunuz?')) {
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('fixModal'));
        modal.show();

        const items = Array.from(selectedItems).map(key => {
            const [type, id] = key.split('-');
            return { type, id };
        });

        let processed = 0;
        const total = items.length;

        document.getElementById('fixModalContent').innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
            <p>Toplu SEO düzeltmesi yapılıyor...</p>
            <div class="progress mt-3" style="height: 20px;">
                <div class="progress-bar" id="bulkProgress" style="width: 0%">0/${total}</div>
            </div>
        </div>
    `;
        document.getElementById('applyFixBtn').style.display = 'none';

        // Process items one by one
        async function processNext() {
            if (processed >= total) {
                document.getElementById('fixModalContent').innerHTML = `
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h5>Toplu düzeltme tamamlandı!</h5>
                    <p>${total} öğe başarıyla güncellendi.</p>
                </div>
            `;
                setTimeout(() => location.reload(), 2000);
                return;
            }

            const item = items[processed];

            try {
                const response = await fetch('ajax/generate_seo_content.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...item, action: 'generate_and_apply' })
                });
                const data = await response.json();

                processed++;
                const percent = Math.round((processed / total) * 100);
                document.getElementById('bulkProgress').style.width = percent + '%';
                document.getElementById('bulkProgress').textContent = `${processed}/${total}`;

                // Process next item
                setTimeout(processNext, 500);
            } catch (error) {
                console.error('Error processing item:', error);
                processed++;
                setTimeout(processNext, 500);
            }
        }

        processNext();
    }

    // Refresh analysis
    function refreshAnalysis() {
        location.reload();
    }

    // Edit item
    function editItem(type, id) {
        let url = '';
        switch (type) {
            case 'blog': url = 'blog_edit.php?id=' + id; break;
            case 'page': url = 'page_edit.php?id=' + id; break;
            case 'service': url = 'service_edit.php?id=' + id; break;
        }
        if (url) window.location.href = url;
    }
</script>

<?php require_once 'includes/footer.php'; ?>