<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'includes/header.php';

$page_title = 'IndexNow - Arama Motoru Indexleme';

// IndexNow API Key kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_key') {
        $key = trim($_POST['indexnow_key'] ?? '');
        if (!empty($key)) {
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM settings WHERE name = 'indexnow_key'");
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row['cnt'] > 0) {
                $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'indexnow_key'");
            } else {
                $stmt = $conn->prepare("INSERT INTO settings (name, value) VALUES ('indexnow_key', ?)");
            }
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $success = "IndexNow API anahtarı kaydedildi.";
        } else {
            $error = "API anahtarı boş olamaz.";
        }
    }
}

// Mevcut ayarları al
$indexnow_key = '';
$key_result = $conn->query("SELECT value FROM settings WHERE name = 'indexnow_key' LIMIT 1");
if ($key_result && $row = $key_result->fetch_assoc()) {
    $indexnow_key = $row['value'];
}

// Tüm URL'leri hazırla (blog + sayfalar + hizmetler)
$all_urls = [];
$base = ($is_local ? 'http://localhost/mynakliyat' : 'https://www.mynakliyat.com.tr');

$all_urls[] = $base . '/';

// Blog yazıları
$blog_res = $conn->query("SELECT slug, updated_at FROM blog_posts WHERE durum = 3 ORDER BY updated_at DESC");
if ($blog_res) {
    while ($row = $blog_res->fetch_assoc()) {
        $all_urls[] = $base . '/' . $row['slug'];
    }
}

// Sayfalar
$page_res = $conn->query("SELECT slug FROM pages WHERE status = 1");
if ($page_res) {
    while ($row = $page_res->fetch_assoc()) {
        $all_urls[] = $base . '/' . $row['slug'];
    }
}

// Hizmetler
$srv_res = $conn->query("SELECT slug FROM services WHERE status = 1");
if ($srv_res) {
    while ($row = $srv_res->fetch_assoc()) {
        $all_urls[] = $base . '/' . $row['slug'];
    }
}

$all_urls = array_values(array_unique($all_urls));
$url_count = count($all_urls);
?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bx bx-search-alt-2 text-primary"></i> IndexNow Yönetimi</h4>
            <p class="text-muted mb-0">Yandex, Bing ve diğer IndexNow destekli arama motorlarına URL bildirimi yapın.</p>
        </div>
        <div class="badge bg-info fs-6"><?= $url_count ?> URL hazır</div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bx bx-check-circle me-2"></i><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="bx bx-error me-2"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- API Key Kartı -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-key me-2"></i>IndexNow API Anahtarı</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_key">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">API Anahtarı</label>
                            <input type="text" name="indexnow_key" class="form-control font-monospace"
                                value="<?= htmlspecialchars($indexnow_key) ?>"
                                placeholder="Örn: a1b2c3d4e5f6...">
                            <div class="form-text">
                                <a href="https://www.indexnow.org/documentation" target="_blank">IndexNow</a>'dan ücretsiz alabilirsiniz.<br>
                                Bing Webmaster Tools &rarr; URL Submissions &rarr; IndexNow bölümünden API key alın.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Anahtarı Kaydet
                        </button>
                    </form>

                    <?php if (!empty($indexnow_key)): ?>
                    <hr>
                    <div class="alert alert-success mb-0">
                        <i class="bx bx-check-circle me-2"></i><strong>API Anahtarı Aktif</strong><br>
                        <code class="text-success"><?= htmlspecialchars(substr($indexnow_key, 0, 8)) ?>...<?= htmlspecialchars(substr($indexnow_key, -4)) ?></code>
                    </div>
                    <?php else: ?>
                    <hr>
                    <div class="alert alert-warning mb-0">
                        <i class="bx bx-info-circle me-2"></i>API anahtarı henüz girilmemiş.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Desteklenen Motorlar -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-globe me-2"></i>Arama Motorları</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center indexnow-engine-card" data-engine="bing">
                                <i class="bx bx-globe fs-3 text-primary mb-2"></i>
                                <div class="fw-bold">Microsoft Bing</div>
                                <div class="text-muted small">api.indexnow.org</div>
                                <div class="mt-2">
                                    <span class="badge bg-success engine-status">Hazır</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center indexnow-engine-card" data-engine="yandex">
                                <i class="bx bx-search fs-3 text-danger mb-2"></i>
                                <div class="fw-bold">Yandex</div>
                                <div class="text-muted small">yandex.com/indexnow</div>
                                <div class="mt-2">
                                    <span class="badge bg-success engine-status">Hazır</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center indexnow-engine-card" data-engine="seznam">
                                <i class="bx bx-globe fs-3 text-warning mb-2"></i>
                                <div class="fw-bold">Seznam</div>
                                <div class="text-muted small">search.seznam.cz</div>
                                <div class="mt-2">
                                    <span class="badge bg-success engine-status">Hazır</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center indexnow-engine-card" data-engine="naver">
                                <i class="bx bx-globe fs-3 text-success mb-2"></i>
                                <div class="fw-bold">Naver</div>
                                <div class="text-muted small">searchadvisor.naver.com</div>
                                <div class="mt-2">
                                    <span class="badge bg-success engine-status">Hazır</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gönderme Paneli -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bx bx-send me-2"></i>URL Bildir</h6>
            <span class="badge bg-secondary"><?= $url_count ?> URL</span>
        </div>
        <div class="card-body">
            <!-- Sonuç alanı -->
            <div id="indexnow-result" class="mb-3" style="display:none;"></div>

            <!-- İlerleme çubuğu -->
            <div id="indexnow-progress" class="mb-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold" id="progress-label">İşleniyor...</small>
                    <small id="progress-percent">0%</small>
                </div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="progress-bar" style="width:0%"></div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Tüm URL'leri Gönder -->
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <i class="bx bx-globe fs-1 text-primary mb-2"></i>
                            <h6>Tüm URL'leri Gönder</h6>
                            <p class="text-muted small"><?= $url_count ?> URL tüm motorlara bildirilir</p>
                            <button class="btn btn-primary w-100" id="btn-submit-all"
                                <?= empty($indexnow_key) ? 'disabled title="Önce API anahtarını girin"' : '' ?>>
                                <i class="bx bx-send me-1"></i> Tümünü Gönder
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sadece Blog Gönder -->
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <i class="bx bx-news fs-1 text-info mb-2"></i>
                            <h6>Sadece Blog URL'leri</h6>
                            <p class="text-muted small">Aktif blog yazılarını bildir</p>
                            <button class="btn btn-info text-white w-100" id="btn-submit-blog"
                                <?= empty($indexnow_key) ? 'disabled' : '' ?>>
                                <i class="bx bx-send me-1"></i> Blogları Gönder
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Manuel URL -->
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <i class="bx bx-link-alt fs-1 text-success mb-2"></i>
                            <h6>Manuel URL Gönder</h6>
                            <div class="input-group mt-2">
                                <input type="text" id="manual-url" class="form-control form-control-sm"
                                    placeholder="https://..." value="<?= $base ?>/">
                            </div>
                            <button class="btn btn-success w-100 mt-2" id="btn-submit-manual"
                                <?= empty($indexnow_key) ? 'disabled' : '' ?>>
                                <i class="bx bx-send me-1"></i> URL Gönder
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- URL Listesi -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Tespit Edilen URL'ler</h6>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#urlListCollapse">
                <i class="bx bx-chevron-down"></i> Göster/Gizle
            </button>
        </div>
        <div class="collapse" id="urlListCollapse">
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr><th>#</th><th>URL</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_urls as $i => $url): ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td><a href="<?= htmlspecialchars($url) ?>" target="_blank" class="text-break"><?= htmlspecialchars($url) ?></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var INDEXNOW_KEY  = <?= json_encode($indexnow_key) ?>;
var ALL_URLS      = <?= json_encode($all_urls) ?>;
var SITE_BASE     = <?= json_encode($base) ?>;

function showResult(type, msg) {
    var cls = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
    var icon = type === 'success' ? 'bx-check-circle' : (type === 'warning' ? 'bx-info-circle' : 'bx-error');
    $('#indexnow-result')
        .removeClass('d-none alert-success alert-warning alert-danger')
        .addClass('alert ' + cls)
        .html('<i class="bx ' + icon + ' me-2"></i>' + msg)
        .show();
}

function setProgress(pct, label) {
    $('#indexnow-progress').show();
    $('#progress-bar').css('width', pct + '%');
    $('#progress-percent').text(pct + '%');
    if (label) $('#progress-label').text(label);
}

function resetProgress() {
    $('#indexnow-progress').hide();
    $('#progress-bar').css('width', '0%');
}

function submitUrls(urls, btnEl) {
    if (!INDEXNOW_KEY) {
        showResult('danger', 'Önce IndexNow API anahtarını girin!');
        return;
    }
    if (!urls.length) {
        showResult('warning', 'Bildirilecek URL bulunamadı.');
        return;
    }

    $(btnEl).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Gönderiliyor...');
    $('#indexnow-result').hide();
    setProgress(10, 'İstek hazırlanıyor...');

    $.ajax({
        url: 'ajax/indexnow_submit.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ key: INDEXNOW_KEY, urls: urls }),
        dataType: 'json',
        timeout: 60000,
        success: function(data) {
            setProgress(100, 'Tamamlandı');
            if (data.success) {
                var details = '';
                if (data.results) {
                    $.each(data.results, function(engine, res) {
                        var icon = res.success ? '✅' : '❌';
                        details += '<br>' + icon + ' <strong>' + engine + '</strong>: ' + res.message;
                    });
                }
                showResult('success', '<strong>Başarılı!</strong> ' + data.message + details);
            } else {
                showResult('danger', data.message || 'Bir hata oluştu.');
            }
        },
        error: function(xhr, status) {
            setProgress(0, '');
            if (status === 'timeout') {
                showResult('warning', 'İstek zaman aşımına uğradı. URL sayısı fazla olabilir, tekrar deneyin.');
            } else {
                showResult('danger', 'Sunucu hatası: ' + (xhr.responseText || status));
            }
        },
        complete: function() {
            setTimeout(resetProgress, 3000);
            $(btnEl).prop('disabled', false).html($(btnEl).data('orig-html'));
        }
    });
}

$(function() {
    // Orijinal buton içeriklerini kaydet
    $('button[id^="btn-"]').each(function() { $(this).data('orig-html', $(this).html()); });

    $('#btn-submit-all').on('click', function() {
        if (!confirm('Tüm ' + ALL_URLS.length + ' URL tüm arama motorlarına bildirilecek. Devam?')) return;
        submitUrls(ALL_URLS, this);
    });

    $('#btn-submit-blog').on('click', function() {
        var blogUrls = ALL_URLS.filter(u => u.includes('/blog/') || (u !== SITE_BASE + '/' && !u.includes('/hizmet') && !u.includes('/sayfa')));
        // Daha akıllı: tüm URL'leri filtrele, blog gibi görünenleri al
        $.ajax({
            url: 'ajax/indexnow_submit.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ key: INDEXNOW_KEY, type: 'blog' }),
            dataType: 'json',
            timeout: 60000,
            success: function(data) {
                submitUrls(data.urls || [], '#btn-submit-blog');
            },
            error: function() {
                // fallback: ALL_URLS
                submitUrls(ALL_URLS, '#btn-submit-blog');
            }
        });
        submitUrls(ALL_URLS, this);
    });

    $('#btn-submit-manual').on('click', function() {
        var url = $('#manual-url').val().trim();
        if (!url) { showResult('danger', 'URL boş olamaz.'); return; }
        submitUrls([url], this);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
