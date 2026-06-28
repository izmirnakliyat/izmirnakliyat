<?php
$page_title = 'Blog SEO Optimizasyonu';
require_once 'includes/header.php';
require_once 'includes/auto_blog_functions.php';

$apiKey = get_openai_api_key();
$aiAvailable = !empty($apiKey);

// Yedekleme tablosunu oluştur
$conn->query("CREATE TABLE IF NOT EXISTS blog_seo_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    original_content MEDIUMTEXT,
    original_seo_title VARCHAR(255),
    original_meta_description TEXT,
    original_meta_keywords TEXT,
    original_focus_keyword VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$siteUrl = SITE_URL;

// İç link için mevcut sayfaları ve blog yazılarını al
$internalPages = [];
$pagesResult = $conn->query("SELECT slug, title FROM pages WHERE status = 1");
if ($pagesResult) {
    while ($p = $pagesResult->fetch_assoc()) {
        $internalPages[] = ['url' => $siteUrl . '/' . $p['slug'], 'title' => $p['title']];
    }
}

// Tüm blog yazılarını analiz et
$posts = $conn->query("SELECT p.*, c.ad as kategori_adi 
                      FROM blog_posts p 
                      LEFT JOIN blog_categories c ON p.kategori_id = c.id 
                      ORDER BY p.created_at DESC");

$allPosts = [];
$totalScore = 0;
$goodCount = 0;
$mediumCount = 0;
$weakCount = 0;

while ($post = $posts->fetch_assoc()) {
    $analysis = analyzeBlogSeo($post, $siteUrl);
    $post['analysis'] = $analysis;
    $allPosts[] = $post;
    
    $totalScore += $analysis['score'];
    
    if ($analysis['score'] >= 70) $goodCount++;
    elseif ($analysis['score'] >= 40) $mediumCount++;
    else $weakCount++;
    
    $stmt = $conn->prepare("UPDATE blog_posts SET seo_score = ? WHERE id = ?");
    $stmt->bind_param("ii", $analysis['score'], $post['id']);
    $stmt->execute();
}

$totalPosts = count($allPosts);
$avgScore = $totalPosts > 0 ? round($totalScore / $totalPosts) : 0;
$needsOptimization = $mediumCount + $weakCount;

// Yedek sayılarını al
$backupCounts = [];
$bkResult = $conn->query("SELECT post_id, COUNT(*) as cnt FROM blog_seo_backups GROUP BY post_id");
if ($bkResult) {
    while ($bk = $bkResult->fetch_assoc()) {
        $backupCounts[$bk['post_id']] = $bk['cnt'];
    }
}

function analyzeBlogSeo($post, $siteUrl) {
    $content = $post['icerik'] ?? '';
    $plainText = strip_tags($content);
    $plainText = html_entity_decode($plainText, ENT_QUOTES, 'UTF-8');
    $plainText = preg_replace('/\s+/', ' ', trim($plainText));
    
    $issues = [];
    $score = 0;
    
    // Kelime sayısı
    $wordCount = empty($plainText) ? 0 : count(preg_split('/\s+/', $plainText, -1, PREG_SPLIT_NO_EMPTY));
    
    // H2 sayısı
    preg_match_all('/<h2[^>]*>/i', $content, $h2m);
    $h2Count = count($h2m[0]);
    
    // İç link sayısı
    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $linkm);
    $internalLinks = 0;
    foreach (($linkm[1] ?? []) as $link) {
        if (strpos($link, $siteUrl) !== false || (strpos($link, '/') === 0 && strpos($link, '//') !== 0)) {
            $internalLinks++;
        }
    }
    
    // Paragraf sayısı
    preg_match_all('/<p[^>]*>/i', $content, $pm);
    $paragraphCount = count($pm[0]);
    
    // Skor hesaplama
    // 1. Kelime (15 puan)
    if ($wordCount >= 800) { $score += 15; }
    elseif ($wordCount >= 500) { $score += 12; }
    elseif ($wordCount >= 300) { $score += 8; }
    else { $score += 3; $issues[] = 'Kelime sayısı düşük (min. 300)'; }
    
    // 2. H2 (15 puan)
    if ($h2Count >= 3) { $score += 15; }
    elseif ($h2Count >= 2) { $score += 10; }
    elseif ($h2Count >= 1) { $score += 5; }
    else { $issues[] = 'H2 başlık eksik'; }
    
    // 3. İç link (15 puan)
    if ($internalLinks >= 3) { $score += 15; }
    elseif ($internalLinks >= 2) { $score += 10; }
    elseif ($internalLinks >= 1) { $score += 5; }
    else { $issues[] = 'İç link yok'; }
    
    // 4. Paragraf (10 puan)
    if ($paragraphCount >= 5) { $score += 10; }
    elseif ($paragraphCount >= 3) { $score += 7; }
    elseif ($paragraphCount >= 1) { $score += 3; }
    else { $issues[] = 'Paragraf yapısı zayıf'; }
    
    // 5. Meta description (15 puan)
    $metaDesc = $post['meta_description'] ?? '';
    $metaLen = mb_strlen($metaDesc);
    if ($metaLen >= 120 && $metaLen <= 160) { $score += 15; }
    elseif ($metaLen > 0) { $score += 8; $issues[] = 'Meta açıklama uzunluğu ideal değil'; }
    else { $issues[] = 'Meta açıklama eksik'; }
    
    // 6. SEO title (15 puan)
    $seoTitle = $post['seo_title'] ?? '';
    $titleLen = mb_strlen($seoTitle);
    if ($titleLen >= 30 && $titleLen <= 60) { $score += 15; }
    elseif ($titleLen > 0) { $score += 8; $issues[] = 'SEO başlık uzunluğu ideal değil'; }
    else { $issues[] = 'SEO başlık eksik'; }
    
    // 7. Focus keyword (10 puan)
    $focusKw = $post['focus_keyword'] ?? '';
    if (!empty($focusKw)) {
        $kwInTitle = mb_stripos($post['baslik'], $focusKw) !== false;
        $kwInContent = mb_stripos($plainText, $focusKw) !== false;
        if ($kwInTitle && $kwInContent) { $score += 10; }
        elseif ($kwInContent || $kwInTitle) { $score += 5; $issues[] = 'Odak kelime başlık veya içerikte eksik'; }
        else { $score += 2; $issues[] = 'Odak kelime içerikte geçmiyor'; }
    } else {
        $issues[] = 'Odak kelime tanımlanmamış';
    }
    
    // 8. Görseller (5 puan)
    preg_match_all('/<img[^>]*>/i', $content, $imgm);
    $totalImg = count($imgm[0]);
    if ($totalImg > 0) {
        $withAlt = 0;
        foreach ($imgm[0] as $img) {
            if (preg_match('/alt=["\'][^"\']+["\']/i', $img)) $withAlt++;
        }
        if ($withAlt == $totalImg) { $score += 5; }
        elseif ($withAlt > 0) { $score += 3; $issues[] = 'Bazı görsellerde alt eksik'; }
        else { $issues[] = 'Görsellerde alt etiketi yok'; }
    } else {
        $score += 3;
    }
    
    return [
        'score' => min(100, $score),
        'word_count' => $wordCount,
        'h2_count' => $h2Count,
        'internal_links' => $internalLinks,
        'paragraph_count' => $paragraphCount,
        'issues' => $issues,
        'issue_count' => count($issues),
    ];
}
?>

<style>
.seo-header {
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 50%, #ff4757 100%);
    border-radius: 12px;
    padding: 25px 30px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.seo-header-left h2 { color: #fff; margin: 0 0 5px; font-size: 1.5rem; font-weight: 700; }
.seo-header-left p { color: rgba(255,255,255,0.85); margin: 0; font-size: 0.9rem; }
.seo-header-right .ai-badge {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    color: #fff;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1px solid rgba(255,255,255,0.3);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.ai-badge .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.ai-badge .dot.active { background: #2ecc71; box-shadow: 0 0 6px #2ecc71; }
.ai-badge .dot.inactive { background: #e74c3c; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 25px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid #f0f0f0;
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}
.stat-icon.blue { background: #e8f4fd; color: #3498db; }
.stat-icon.green { background: #e8f8f0; color: #2ecc71; }
.stat-icon.emerald { background: #d5f5e3; color: #27ae60; }
.stat-icon.red { background: #fdedec; color: #e74c3c; }
.stat-info h3 { margin: 0; font-size: 1.6rem; font-weight: 700; color: #2d3436; }
.stat-info span { font-size: 0.8rem; color: #636e72; }

.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}
.filter-tabs { display: flex; gap: 5px; }
.filter-tab {
    padding: 8px 18px;
    border-radius: 8px;
    border: 1px solid #dfe6e9;
    background: #fff;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
    color: #636e72;
}
.filter-tab:hover { border-color: #3498db; color: #3498db; }
.filter-tab.active { background: #2d3436; color: #fff; border-color: #2d3436; }
.btn-bulk-optimize {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}
.btn-bulk-optimize:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.3); }
.btn-bulk-optimize:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.seo-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.seo-table thead th {
    background: #f8f9fa;
    padding: 12px 15px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #636e72;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #f0f0f0;
    white-space: nowrap;
}
.seo-table tbody tr { border-bottom: 1px solid #f5f5f5; transition: background 0.15s; }
.seo-table tbody tr:hover { background: #f8f9fa; }
.seo-table tbody tr:last-child { border-bottom: none; }
.seo-table td { padding: 12px 15px; font-size: 0.85rem; vertical-align: middle; }

.seo-score-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.8rem;
    color: #fff;
}
.seo-score-badge.good { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.seo-score-badge.medium { background: linear-gradient(135deg, #f39c12, #e67e22); }
.seo-score-badge.weak { background: linear-gradient(135deg, #e74c3c, #c0392b); }

.post-title-cell { max-width: 300px; }
.post-title-cell .title { font-weight: 600; color: #2d3436; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 280px; }
.post-title-cell .category { font-size: 0.75rem; color: #b2bec3; margin-top: 2px; }

.metric-value { text-align: center; font-weight: 600; color: #2d3436; }
.metric-value.low { color: #e74c3c; }
.metric-value.mid { color: #f39c12; }
.metric-value.high { color: #2ecc71; }

.issue-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    padding: 0 8px;
    border-radius: 13px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
}
.issue-badge.none { background: #b2bec3; }
.issue-badge.some { background: #e74c3c; }

.backup-info { font-size: 0.8rem; color: #b2bec3; text-align: center; }
.backup-info.has { color: #2ecc71; font-weight: 600; }

.action-btns { display: flex; gap: 6px; white-space: nowrap; }
.action-btns .btn-act {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
    color: #fff;
}
.action-btns .btn-act:hover { transform: translateY(-2px); }
.action-btns .btn-edit { background: #2ecc71; }
.action-btns .btn-optimize { background: #3498db; }
.action-btns .btn-preview { background: #9b59b6; }
.action-btns .btn-restore { background: #e67e22; }

.optimize-progress {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
.optimize-progress.active { display: flex; }
.optimize-modal {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.optimize-modal h3 { margin: 0 0 10px; color: #2d3436; }
.optimize-modal p { color: #636e72; margin: 0 0 25px; font-size: 0.9rem; }
.progress-bar-wrap {
    background: #f0f0f0;
    border-radius: 8px;
    height: 12px;
    overflow: hidden;
    margin-bottom: 15px;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 8px;
    transition: width 0.3s;
    width: 0%;
}
.progress-text { font-size: 0.85rem; color: #636e72; }

@media (max-width: 992px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .stats-grid { grid-template-columns: 1fr; }
    .seo-header { flex-direction: column; gap: 15px; text-align: center; }
    .filter-bar { flex-direction: column; }
}
</style>

<div class="content-wrapper fade-in-up">

    <!-- Header -->
    <div class="seo-header">
        <div class="seo-header-left">
            <h2><i class='bx bx-search-alt-2'></i> Blog SEO Optimizasyonu</h2>
            <p>Blog yazılarınızın SEO sorunlarını tespit edin ve AI ile otomatik düzeltin</p>
        </div>
        <div class="seo-header-right">
            <?php if ($aiAvailable): ?>
                <span class="ai-badge"><span class="dot active"></span> AI Aktif (OpenAI)</span>
            <?php else: ?>
                <span class="ai-badge"><span class="dot inactive"></span> AI Pasif - <a href="auto_blog.php" style="color:#fff;text-decoration:underline;">API Key Gir</a></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class='bx bx-file'></i></div>
            <div class="stat-info">
                <h3><?php echo $totalPosts; ?></h3>
                <span>Toplam Yazı</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class='bx bx-check-circle'></i></div>
            <div class="stat-info">
                <h3><?php echo $avgScore; ?>%</h3>
                <span>Ortalama SEO</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon emerald"><i class='bx bx-like'></i></div>
            <div class="stat-info">
                <h3><?php echo $goodCount; ?></h3>
                <span>SEO İyi</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class='bx bx-error-circle'></i></div>
            <div class="stat-info">
                <h3><?php echo $weakCount; ?></h3>
                <span>SEO Zayıf</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">Tümü (<?php echo $totalPosts; ?>)</button>
            <button class="filter-tab" data-filter="weak">Zayıf (<?php echo $weakCount; ?>)</button>
            <button class="filter-tab" data-filter="medium">Orta (<?php echo $mediumCount; ?>)</button>
            <button class="filter-tab" data-filter="good">İyi (<?php echo $goodCount; ?>)</button>
        </div>
        <?php if ($aiAvailable && $needsOptimization > 0): ?>
        <button class="btn-bulk-optimize" id="bulkOptimizeBtn" onclick="bulkOptimize()">
            <i class='bx bx-edit'></i> Tüm Eksik Yazıları Toplu Optimize Et (<?php echo $needsOptimization; ?>)
        </button>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div style="overflow-x:auto;">
        <table class="seo-table">
            <thead>
                <tr>
                    <th style="text-align:center;">SEO</th>
                    <th>BAŞLIK</th>
                    <th style="text-align:center;">KELİME</th>
                    <th style="text-align:center;">H2</th>
                    <th style="text-align:center;">İÇ LİNK</th>
                    <th style="text-align:center;">PARAGRAF</th>
                    <th style="text-align:center;">SORUN</th>
                    <th style="text-align:center;">YEDEK</th>
                    <th style="text-align:center;">İŞLEM</th>
                </tr>
            </thead>
            <tbody id="seoTableBody">
                <?php foreach ($allPosts as $post): 
                    $a = $post['analysis'];
                    $scoreClass = $a['score'] >= 70 ? 'good' : ($a['score'] >= 40 ? 'medium' : 'weak');
                    $filterClass = $a['score'] >= 70 ? 'good' : ($a['score'] >= 40 ? 'medium' : 'weak');
                    $hasBackup = isset($backupCounts[$post['id']]);
                ?>
                <tr class="seo-row" data-filter="<?php echo $filterClass; ?>" data-id="<?php echo $post['id']; ?>">
                    <td style="text-align:center;">
                        <span class="seo-score-badge <?php echo $scoreClass; ?>"><?php echo $a['score']; ?></span>
                    </td>
                    <td class="post-title-cell">
                        <span class="title" title="<?php echo htmlspecialchars($post['baslik']); ?>"><?php echo htmlspecialchars($post['baslik']); ?></span>
                        <div class="category"><?php echo htmlspecialchars($post['kategori_adi'] ?? 'Kategorisiz'); ?></div>
                    </td>
                    <td class="metric-value <?php echo $a['word_count'] >= 300 ? 'high' : ($a['word_count'] >= 150 ? 'mid' : 'low'); ?>"><?php echo $a['word_count']; ?></td>
                    <td class="metric-value <?php echo $a['h2_count'] >= 2 ? 'high' : ($a['h2_count'] >= 1 ? 'mid' : 'low'); ?>"><?php echo $a['h2_count']; ?></td>
                    <td class="metric-value <?php echo $a['internal_links'] >= 2 ? 'high' : ($a['internal_links'] >= 1 ? 'mid' : 'low'); ?>"><?php echo $a['internal_links']; ?></td>
                    <td class="metric-value <?php echo $a['paragraph_count'] >= 3 ? 'high' : ($a['paragraph_count'] >= 1 ? 'mid' : 'low'); ?>"><?php echo $a['paragraph_count']; ?></td>
                    <td style="text-align:center;">
                        <?php if ($a['issue_count'] > 0): ?>
                            <span class="issue-badge some" title="<?php echo htmlspecialchars(implode(', ', $a['issues'])); ?>"><?php echo $a['issue_count']; ?></span>
                        <?php else: ?>
                            <span class="issue-badge none">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="backup-info <?php echo $hasBackup ? 'has' : ''; ?>">
                        <?php echo $hasBackup ? $backupCounts[$post['id']] . ' yedek' : '-'; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="blog_edit.php?id=<?php echo $post['id']; ?>" class="btn-act btn-edit" title="Düzenle"><i class='bx bx-edit-alt'></i></a>
                            <?php if ($aiAvailable): ?>
                            <button class="btn-act btn-optimize" title="AI ile Optimize Et" onclick="optimizePost(<?php echo $post['id']; ?>)"><i class='bx bx-brain'></i></button>
                            <?php endif; ?>
                            <a href="<?php echo $siteUrl; ?>/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="btn-act btn-preview" title="Önizle"><i class='bx bx-search-alt'></i></a>
                            <?php if ($hasBackup): ?>
                            <button class="btn-act btn-restore" title="Yedeği Geri Yükle" onclick="restoreBackup(<?php echo $post['id']; ?>)"><i class='bx bx-undo'></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Progress Modal -->
<div class="optimize-progress" id="optimizeProgress">
    <div class="optimize-modal">
        <h3 id="progressTitle"><i class='bx bx-brain'></i> SEO Optimizasyonu</h3>
        <p id="progressSubtitle">AI içerik analiz ediyor ve optimize ediyor...</p>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" id="progressBar"></div>
        </div>
        <div class="progress-text" id="progressText">Hazırlanıyor...</div>
    </div>
</div>

<script>
// Filtreleme
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        document.querySelectorAll('.seo-row').forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.filter === filter) ? '' : 'none';
        });
    });
});

// Tekil optimize
async function optimizePost(postId) {
    if (!confirm('Bu yazı AI ile optimize edilecek. İçerik değişecektir, yedek alınacaktır. Devam?')) return;
    
    showProgress('SEO Optimizasyonu', 'AI içerik analiz ediyor ve optimize ediyor...', 0);
    updateProgress(10, 'Yedek alınıyor...');
    
    try {
        const response = await fetch('ajax/blog_seo_optimize.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'optimize', post_id: postId })
        });
        
        updateProgress(50, 'AI optimize ediyor...');
        const data = await response.json();
        
        if (data.success) {
            updateProgress(100, 'Tamamlandı! Yeni skor: ' + data.new_score);
            setTimeout(() => {
                hideProgress();
                location.reload();
            }, 1500);
        } else {
            hideProgress();
            alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
        }
    } catch (err) {
        hideProgress();
        alert('Bağlantı hatası: ' + err.message);
    }
}

// Toplu optimize
async function bulkOptimize() {
    const rows = document.querySelectorAll('.seo-row[data-filter="weak"], .seo-row[data-filter="medium"]');
    const postIds = Array.from(rows).map(r => parseInt(r.dataset.id));
    
    if (postIds.length === 0) { alert('Optimize edilecek yazı yok.'); return; }
    if (!confirm(postIds.length + ' yazı AI ile optimize edilecek. Bu işlem biraz zaman alabilir. Devam?')) return;
    
    const btn = document.getElementById('bulkOptimizeBtn');
    btn.disabled = true;
    
    showProgress('Toplu SEO Optimizasyonu', postIds.length + ' yazı optimize ediliyor...', 0);
    
    let completed = 0;
    let errors = 0;
    
    for (let i = 0; i < postIds.length; i++) {
        const pct = Math.round(((i + 1) / postIds.length) * 100);
        updateProgress(pct, (i + 1) + ' / ' + postIds.length + ' yazı işleniyor...');
        
        try {
            const response = await fetch('ajax/blog_seo_optimize.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'optimize', post_id: postIds[i] })
            });
            const data = await response.json();
            if (data.success) { completed++; } else { errors++; }
        } catch (e) { errors++; }
        
        // Rate limiting
        if (i < postIds.length - 1) {
            await new Promise(r => setTimeout(r, 2000));
        }
    }
    
    updateProgress(100, 'Tamamlandı! ' + completed + ' başarılı, ' + errors + ' hata.');
    setTimeout(() => {
        hideProgress();
        location.reload();
    }, 2000);
}

// Yedek geri yükleme
async function restoreBackup(postId) {
    if (!confirm('Son yedek geri yüklenecek. Mevcut içerik değişecektir. Devam?')) return;
    
    try {
        const response = await fetch('ajax/blog_seo_optimize.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restore', post_id: postId })
        });
        const data = await response.json();
        if (data.success) {
            alert('Yedek başarıyla geri yüklendi!');
            location.reload();
        } else {
            alert('Hata: ' + (data.message || 'Geri yükleme başarısız'));
        }
    } catch (err) {
        alert('Bağlantı hatası: ' + err.message);
    }
}

function showProgress(title, subtitle, pct) {
    document.getElementById('progressTitle').innerHTML = '<i class="bx bx-brain"></i> ' + title;
    document.getElementById('progressSubtitle').textContent = subtitle;
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressText').textContent = 'Hazırlanıyor...';
    document.getElementById('optimizeProgress').classList.add('active');
}
function updateProgress(pct, text) {
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressText').textContent = text;
}
function hideProgress() {
    document.getElementById('optimizeProgress').classList.remove('active');
}
</script>

<?php require_once 'includes/footer.php'; ?>
