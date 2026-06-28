<?php
/**
 * Otomatik Blog ayarları — Content Engine Faz 1 (manuel üretim + QC).
 */
declare(strict_types=1);

$page_title = 'Otomatik Blog (Content Engine)';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auto_blog_functions.php';
require_once dirname(__DIR__) . '/includes/mynak_local_seo_content_engine.php';
require_once dirname(__DIR__) . '/includes/mynak_ce_production.php';
require_once dirname(__DIR__) . '/includes/auto_blog_ce_adapter.php';

if (!hasPermission('blog_view')) {
    echo '<div class="alert alert-danger">Bu sayfaya erişim yetkiniz yok.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

mynak_ce_ensure_settings($conn);
$settings = get_all_auto_blog_settings();
$categories = get_all_categories();
$quota = ab_ce_quota_status($conn);
$openai_ok = get_openai_api_key() !== '';
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><i class="bx bx-bot"></i> Otomatik Blog — Content Engine</h4>
            <p class="text-muted small mb-0">
                Tek motor: CE + QC gate. Günlük kota <?php echo (int) $quota['used']; ?>/<?php echo (int) $quota['max']; ?>
                (kalan <?php echo (int) $quota['remaining']; ?>).
                <a href="blog_review.php">Editör kuyruğu</a> ·
                <a href="blog_bulk_refresh.php">Toplu yenileme</a> ·
                <a href="content_roadmap.php">Intent Roadmap</a>
            </p>
        </div>
        <button type="button" class="btn btn-primary" id="add-setting-btn">
            <i class="bx bx-plus"></i> Yeni ayar
        </button>
    </div>

    <?php if (!$openai_ok): ?>
    <div class="alert alert-warning">OpenAI API anahtarı eksik. <a href="blog_bulk_refresh.php">Toplu yenileme</a> panelinden ekleyin.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Kategori</th>
                        <th>Anahtar kelimeler</th>
                        <th>Kelime</th>
                        <th>Aktif</th>
                        <th class="text-end">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($settings)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Henüz ayar yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($settings as $s): ?>
                    <tr>
                        <td><?php echo (int) $s['id']; ?></td>
                        <td><?php echo htmlspecialchars((string) ($s['category_name'] ?? '—')); ?></td>
                        <td class="small"><?php echo htmlspecialchars(mb_substr((string) $s['keywords'], 0, 80)); ?></td>
                        <td><?php echo (int) $s['min_words']; ?>–<?php echo (int) $s['max_words']; ?></td>
                        <td><?php echo !empty($s['active']) ? '<span class="badge bg-success">Evet</span>' : '<span class="badge bg-secondary">Hayır</span>'; ?></td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-setting-btn" data-id="<?php echo (int) $s['id']; ?>">Düzenle</button>
                            <button type="button" class="btn btn-sm btn-success generate-now-btn" data-id="<?php echo (int) $s['id']; ?>">CE Üret</button>
                            <button type="button" class="btn btn-sm btn-outline-info preview-now-btn" data-id="<?php echo (int) $s['id']; ?>">Önizleme</button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-setting-btn" data-id="<?php echo (int) $s['id']; ?>">Sil</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="setting-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div class="bg-white rounded shadow p-4" style="max-width:560px;width:95%;max-height:90vh;overflow:auto;">
        <div class="d-flex justify-content-between mb-3">
            <h5 class="mb-0">Ayar</h5>
            <button type="button" id="close-modal-btn" class="btn-close"></button>
        </div>
        <form id="auto-blog-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="id" value="">
            <div class="mb-2">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['ad']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Anahtar kelimeler (virgülle)</label>
                <textarea name="keywords" class="form-control" rows="2" required></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Ek bağlam (manuel_command — promptu ezmez)</label>
                <textarea name="manual_command" class="form-control" rows="2" placeholder="Opsiyonel kısa not"></textarea>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6"><label class="form-label">Min kelime</label><input type="number" name="min_words" class="form-control" value="1400"></div>
                <div class="col-6"><label class="form-label">Max kelime</label><input type="number" name="max_words" class="form-control" value="1800"></div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-4"><label class="form-label">Günlük adet</label><input type="number" name="post_count_per_period" class="form-control" value="1"></div>
                <div class="col-4"><label class="form-label">Periyot</label>
                    <select name="period_type" class="form-select">
                        <option value="daily">daily</option>
                        <option value="weekly">weekly</option>
                        <option value="hourly">hourly</option>
                    </select>
                </div>
                <div class="col-4"><label class="form-label">Saat</label><input type="text" name="post_time" class="form-control" value="09:00"></div>
            </div>
            <div class="mb-2">
                <label class="form-label">Kapak</label>
                <input type="file" name="cover_image" class="form-control">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="active" id="activeChk" checked>
                <label class="form-check-label" for="activeChk">Aktif</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">Kaydet</button>
        </form>
    </div>
</div>

<div id="preview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center;">
    <div class="bg-white rounded shadow p-4" style="max-width:800px;width:95%;max-height:92vh;overflow:auto;">
        <div class="d-flex justify-content-between mb-2">
            <h5 class="mb-0">Önizleme + QC</h5>
            <button type="button" id="close-preview-btn" class="btn-close"></button>
        </div>
        <div id="preview-qc" class="small mb-2"></div>
        <h6 id="preview-title"></h6>
        <div id="preview-body" class="border rounded p-3 small" style="max-height:360px;overflow:auto;"></div>
        <div class="mt-3 d-flex gap-2">
            <button type="button" id="preview-publish-btn" class="btn btn-success" disabled>Kuyruğa kaydet</button>
            <span id="preview-hint" class="small text-muted align-self-center"></span>
        </div>
    </div>
</div>

<script src="assets/js/auto_blog.js"></script>
<script>
(function() {
    var previewData = null;
    document.querySelectorAll('.preview-now-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var fd = new FormData();
            fd.append('id', id);
            fd.append('preview_mode', '1');
            btn.disabled = true;
            fetch('ajax/auto_blog_generate.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (!d.success || !d.preview) {
                        alert(d.message || 'Önizleme alınamadı');
                        return;
                    }
                    previewData = d.preview;
                    previewData.setting_id = id;
                    document.getElementById('preview-title').textContent = d.preview.baslik || '';
                    document.getElementById('preview-body').innerHTML = d.preview.icerik || '';
                    var qc = d.qc || d.preview.qc || {};
                    document.getElementById('preview-qc').innerHTML = qc.pass
                        ? '<span class="badge bg-success">QC PASS</span> → editör kuyruğu'
                        : '<span class="badge bg-danger">QC FAIL</span> → revize (durum=2)';
                    var pub = document.getElementById('preview-publish-btn');
                    pub.disabled = false;
                    document.getElementById('preview-hint').textContent = 'Kayıt QC sonucuna göre durum atanır.';
                    document.getElementById('preview-modal').style.display = 'flex';
                })
                .finally(function() { btn.disabled = false; });
        });
    });
    document.getElementById('close-preview-btn').onclick = function() {
        document.getElementById('preview-modal').style.display = 'none';
    };
    document.getElementById('preview-publish-btn').onclick = function() {
        if (!previewData) return;
        var fd = new FormData();
        fd.append('action', 'publish');
        fd.append('baslik', previewData.baslik);
        fd.append('icerik', previewData.icerik_raw || previewData.icerik);
        fd.append('slug', previewData.slug);
        fd.append('kategori_id', previewData.kategori_id);
        fd.append('etiketler', previewData.etiketler || '');
        fd.append('kapak_foto', previewData.kapak_foto || '');
        fd.append('setting_id', previewData.setting_id || '');
        fd.append('qc_pass', (previewData.qc && previewData.qc.pass) ? '1' : '0');
        fetch('ajax/auto_blog_publish.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                alert(d.message || (d.success ? 'OK' : 'Hata'));
                if (d.success && d.review_url) location.href = d.review_url;
            });
    };
    document.getElementById('auto-blog-form').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        fetch('ajax/auto_blog_save.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                alert(d.message || (d.success ? 'Kaydedildi' : 'Hata'));
                if (d.success) location.reload();
            });
    };
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
