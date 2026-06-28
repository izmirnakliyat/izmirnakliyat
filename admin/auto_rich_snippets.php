<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'includes/header.php';
require_once 'includes/auto_blog_functions.php';
require_once '../includes/rich_snippets.php';

$page_title = 'Otomatik Rich Snippet Üretici';

ensurePageSchemasTable();

$openai_key = get_openai_api_key();

// İstatistik
$total_blogs    = (int)($conn->query("SELECT COUNT(*) FROM blog_posts")->fetch_row()[0] ?? 0);
$total_services = (int)($conn->query("SELECT COUNT(*) FROM services")->fetch_row()[0] ?? 0);
$done_blogs     = (int)($conn->query("SELECT COUNT(DISTINCT page_id) FROM page_schemas WHERE page_type='blog' AND status=1")->fetch_row()[0] ?? 0);
$done_services  = (int)($conn->query("SELECT COUNT(DISTINCT page_id) FROM page_schemas WHERE page_type='service' AND status=1")->fetch_row()[0] ?? 0);
$has_general    = (int)($conn->query("SELECT COUNT(*) FROM page_schemas WHERE page_type IN('global','homepage') AND status=1")->fetch_row()[0] ?? 0);
?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bx bx-code-alt text-success"></i> Otomatik Rich Snippet Üretici</h4>
            <p class="text-muted mb-0">Tüm sayfalar için Schema.org yapılandırılmış veri şemalarını AI ile otomatik oluşturun.</p>
        </div>
    </div>

    <?php if (!$openai_key): ?>
    <div class="alert alert-warning">
        <i class="bx bx-error-circle me-2"></i>
        OpenAI API anahtarı tanımlı değil. AI FAQPage şemaları oluşturmak için <a href="auto_blog.php" class="alert-link">API anahtarınızı girin</a>.
        Genel şemalar (Organization, LocalBusiness) API gerektirmez.
    </div>
    <?php endif; ?>

    <!-- İstatistik Kartları -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="bx bx-building-house" style="font-size:2.5rem; color:#198754;"></i>
                    </div>
                    <h6 class="text-muted mb-1">Genel Şemalar</h6>
                    <h3 class="mb-0 <?= $has_general > 0 ? 'text-success' : 'text-secondary' ?>">
                        <?= $has_general > 0 ? '<i class="bx bx-check-circle"></i> Oluşturuldu' : '<i class="bx bx-time-five"></i> Bekliyor' ?>
                    </h3>
                    <small class="text-muted">Organization + MovingCompany + WebSite</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="bx bx-news" style="font-size:2.5rem; color:#0d6efd;"></i>
                    </div>
                    <h6 class="text-muted mb-1">Blog FAQPage</h6>
                    <h3 class="mb-0 text-primary"><?= $done_blogs ?> / <?= $total_blogs ?></h3>
                    <div class="progress mt-2" style="height:6px;">
                        <div class="progress-bar bg-primary" style="width:<?= $total_blogs ? round($done_blogs / $total_blogs * 100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="bx bx-cube" style="font-size:2.5rem; color:#e67e22;"></i>
                    </div>
                    <h6 class="text-muted mb-1">Hizmet FAQPage</h6>
                    <h3 class="mb-0 text-warning"><?= $done_services ?> / <?= $total_services ?></h3>
                    <div class="progress mt-2" style="height:6px;">
                        <div class="progress-bar bg-warning" style="width:<?= $total_services ? round($done_services / $total_services * 100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="bx bx-check-shield" style="font-size:2.5rem; color:#6610f2;"></i>
                    </div>
                    <h6 class="text-muted mb-1">Toplam Şema</h6>
                    <h3 class="mb-0 text-purple" style="color:#6610f2;">
                        <?= $has_general + $done_blogs + $done_services ?>
                    </h3>
                    <small class="text-muted">Aktif sayfa şeması</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Genel Şemalar -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="bx bx-building-house text-success me-2"></i>Genel Site Şemaları</h5>
                <small class="text-muted">Organization, MovingCompany (LocalBusiness), WebSite — Site ayarlarından otomatik oluşturulur, AI gerekmez.</small>
            </div>
            <button class="btn btn-success" id="btn-gen-general">
                <i class="bx bx-refresh me-1"></i> Şimdi Oluştur / Güncelle
            </button>
        </div>
        <div class="card-body">
            <div class="row g-2 text-center">
                <div class="col-md-4">
                    <div class="p-3 rounded border bg-light">
                        <i class="bx bx-buildings fs-2 text-success"></i>
                        <p class="mb-0 fw-semibold mt-1">Organization</p>
                        <small class="text-muted">Şirket bilgileri, logo, iletişim</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded border bg-light">
                        <i class="bx bx-map fs-2 text-warning"></i>
                        <p class="mb-0 fw-semibold mt-1">MovingCompany</p>
                        <small class="text-muted">Nakliyat şirketi, adres, çalışma saatleri</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded border bg-light">
                        <i class="bx bx-globe fs-2 text-primary"></i>
                        <p class="mb-0 fw-semibold mt-1">WebSite</p>
                        <small class="text-muted">Site URL, arama aksiyonu</small>
                    </div>
                </div>
            </div>
            <div id="general-result" class="alert d-none mt-3 mb-0"></div>
        </div>
    </div>

    <!-- 2. Blog FAQPage -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="bx bx-question-mark text-primary me-2"></i>Blog Yazıları — FAQPage Şeması</h5>
                <small class="text-muted">AI, her blog yazısının içeriğini okuyarak 5-7 gerçekçi soru-cevap şeması üretir. Google arama sonuçlarında "Kişiler şunu da soruyor" alanında görünür.</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" id="btn-load-blogs">
                    <i class="bx bx-list-ul me-1"></i> Listeyi Göster
                </button>
                <button class="btn btn-primary" id="btn-gen-all-blogs" disabled>
                    <i class="bx bx-refresh me-1"></i> Tümü için Oluştur
                </button>
            </div>
        </div>
        <div id="blogs-panel" style="display:none;">
            <!-- İlerleme -->
            <div id="blog-progress-wrap" class="card-body border-bottom pb-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-semibold" id="blog-prog-text">İşleniyor...</span>
                    <span class="small text-muted" id="blog-prog-count">0 / 0</span>
                </div>
                <div class="progress mb-1" style="height:16px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="blog-prog-bar" style="width:0%">0%</div>
                </div>
                <div class="d-flex gap-3 small">
                    <span class="text-success"><i class="bx bx-check"></i> <strong id="b-ok">0</strong></span>
                    <span class="text-danger"><i class="bx bx-x"></i> <strong id="b-err">0</strong></span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="blogs-table">
                    <thead class="table-light">
                        <tr>
                            <th width="50">ID</th>
                            <th>Başlık</th>
                            <th width="120" class="text-center">FAQPage Durumu</th>
                            <th width="100" class="text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="blogs-tbody">
                        <tr><td colspan="4" class="text-center text-muted py-3">
                            <i class="bx bx-loader-alt bx-spin me-1"></i> Yükleniyor...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Hizmet FAQPage -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="bx bx-cube text-warning me-2"></i>Hizmet Sayfaları — FAQPage Şeması</h5>
                <small class="text-muted">Her hizmet sayfası için AI, hizmete özel soru-cevap şeması oluşturur.</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-warning" id="btn-load-services">
                    <i class="bx bx-list-ul me-1"></i> Listeyi Göster
                </button>
                <button class="btn btn-warning text-white" id="btn-gen-all-services" disabled>
                    <i class="bx bx-refresh me-1"></i> Tümü için Oluştur
                </button>
            </div>
        </div>
        <div id="services-panel" style="display:none;">
            <div id="svc-progress-wrap" class="card-body border-bottom pb-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-semibold" id="svc-prog-text">İşleniyor...</span>
                    <span class="small text-muted" id="svc-prog-count">0 / 0</span>
                </div>
                <div class="progress mb-1" style="height:16px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" id="svc-prog-bar" style="width:0%">0%</div>
                </div>
                <div class="d-flex gap-3 small">
                    <span class="text-success"><i class="bx bx-check"></i> <strong id="s-ok">0</strong></span>
                    <span class="text-danger"><i class="bx bx-x"></i> <strong id="s-err">0</strong></span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">ID</th>
                            <th>Hizmet Adı</th>
                            <th width="120" class="text-center">FAQPage Durumu</th>
                            <th width="100" class="text-center">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="services-tbody">
                        <tr><td colspan="4" class="text-center text-muted py-3">
                            <i class="bx bx-loader-alt bx-spin me-1"></i> Yükleniyor...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bilgi Kutusu -->
    <div class="alert alert-info">
        <h6 class="alert-heading"><i class="bx bx-info-circle me-1"></i>Rich Snippet Nedir, Ne İşe Yarar?</h6>
        <ul class="mb-0 small">
            <li><strong>FAQPage:</strong> Google arama sonuçlarında yazının altında soru-cevap akordiyon olarak görünür → CTR artışı sağlar.</li>
            <li><strong>MovingCompany:</strong> Yerel arama sonuçlarında bilgi panelinde şirket bilgilerinizi gösterir.</li>
            <li><strong>Organization:</strong> Google'ın şirketinizi bilgi grafiğine eklemesini sağlar.</li>
            <li><strong>WebSite:</strong> Arama kutusunun (Sitelinks Searchbox) görünmesini sağlayabilir.</li>
        </ul>
    </div>
</div>

<script>
var AJAX = 'ajax/generate_page_schema.php';

// ─── Genel Şemalar ───
$('#btn-gen-general').on('click', function() {
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Oluşturuluyor...');
    $.post(AJAX, { action: 'generate_general' }, function(d) {
        $btn.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> Şimdi Oluştur / Güncelle');
        var cls = d.success ? 'alert-success' : 'alert-danger';
        var icon = d.success ? 'bx-check-circle' : 'bx-error-circle';
        $('#general-result').removeClass('d-none').attr('class', 'alert mt-3 mb-0 ' + cls)
            .html('<i class="bx ' + icon + ' me-1"></i> ' + d.message);
        if (d.success) setTimeout(function(){ location.reload(); }, 1500);
    }, 'json').fail(function() {
        $btn.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> Şimdi Oluştur / Güncelle');
        $('#general-result').removeClass('d-none').attr('class', 'alert mt-3 mb-0 alert-danger').html('Sunucu hatası.');
    });
});

// ─── Blog Listesi ───
var blogsLoaded = false;
$('#btn-load-blogs').on('click', function() {
    if (!blogsLoaded) {
        loadBlogs();
    } else {
        $('#blogs-panel').slideToggle(200);
    }
});

function loadBlogs() {
    $('#blogs-panel').slideDown(200);
    $.post(AJAX, { action: 'list_blogs' }, function(d) {
        if (!d.success) return;
        blogsLoaded = true;
        var rows = '';
        d.posts.forEach(function(p) {
            rows += buildBlogRow(p);
        });
        $('#blogs-tbody').html(rows);
        $('#btn-gen-all-blogs').prop('disabled', false);
    }, 'json');
}

function buildBlogRow(p) {
    var badge = p.has_schema > 0
        ? '<span class="badge bg-success"><i class="bx bx-check"></i> Oluşturuldu</span>'
        : '<span class="badge bg-secondary">Yok</span>';
    return '<tr id="brow-' + p.id + '">' +
        '<td class="text-muted small">' + p.id + '</td>' +
        '<td>' + escHtml(p.baslik) + '</td>' +
        '<td class="text-center" id="bstatus-' + p.id + '">' + badge + '</td>' +
        '<td class="text-center">' +
        '<button class="btn btn-sm btn-outline-primary btn-gen-blog" data-id="' + p.id + '" title="Bu yazı için FAQPage oluştur">' +
        '<i class="bx bx-magic-wand"></i></button></td></tr>';
}

$(document).on('click', '.btn-gen-blog', function() {
    var id = $(this).data('id');
    generateBlogFAQ([id]);
});

$('#btn-gen-all-blogs').on('click', function() {
    var ids = [];
    $('#blogs-tbody tr').each(function() {
        var id = $(this).find('.btn-gen-blog').data('id');
        if (id) ids.push(id);
    });
    if (!confirm(ids.length + ' blog yazısı için FAQPage şeması oluşturulacak. AI kullanılacak, biraz zaman alabilir. Devam?')) return;
    generateBlogFAQ(ids);
});

var stopBlogs = false;
function generateBlogFAQ(ids) {
    stopBlogs = false;
    var total = ids.length, ok = 0, err = 0, i = 0;
    $('#blog-progress-wrap').show();
    $('#btn-gen-all-blogs').prop('disabled', true);

    function next() {
        if (i >= total || stopBlogs) {
            $('#blog-prog-bar').css('width', '100%').text('100%').removeClass('progress-bar-animated');
            $('#btn-gen-all-blogs').prop('disabled', false);
            return;
        }
        var id = ids[i]; i++;
        var pct = Math.round((i / total) * 100);
        $('#blog-prog-bar').css('width', pct + '%').text(pct + '%');
        $('#blog-prog-count').text(i + ' / ' + total);
        $('#blog-prog-text').text('İşleniyor: #' + id);
        $('#bstatus-' + id).html('<span class="badge bg-warning text-dark"><i class="bx bx-loader-alt bx-spin"></i></span>');

        $.ajax({ url: AJAX, type: 'POST', data: { action: 'generate_blog_faq', page_id: id },
            dataType: 'json', timeout: 120000,
            success: function(d) {
                if (d.success) {
                    ok++; $('#b-ok').text(ok);
                    $('#bstatus-' + id).html('<span class="badge bg-success"><i class="bx bx-check"></i> Oluşturuldu</span>');
                    $('#brow-' + id).addClass('table-success');
                } else {
                    err++; $('#b-err').text(err);
                    $('#bstatus-' + id).html('<span class="badge bg-danger" title="' + escHtml(d.message) + '">Hata</span>');
                }
                setTimeout(next, 300);
            },
            error: function() { err++; $('#b-err').text(err); setTimeout(next, 300); }
        });
    }
    next();
}

// ─── Hizmet Listesi ───
var servicesLoaded = false;
$('#btn-load-services').on('click', function() {
    if (!servicesLoaded) {
        loadServices();
    } else {
        $('#services-panel').slideToggle(200);
    }
});

function loadServices() {
    $('#services-panel').slideDown(200);
    $.post(AJAX, { action: 'list_services' }, function(d) {
        if (!d.success) return;
        servicesLoaded = true;
        var rows = '';
        d.services.forEach(function(s) {
            rows += buildSvcRow(s);
        });
        $('#services-tbody').html(rows);
        $('#btn-gen-all-services').prop('disabled', false);
    }, 'json');
}

function buildSvcRow(s) {
    var badge = s.has_schema > 0
        ? '<span class="badge bg-success"><i class="bx bx-check"></i> Oluşturuldu</span>'
        : '<span class="badge bg-secondary">Yok</span>';
    return '<tr id="srow-' + s.id + '">' +
        '<td class="text-muted small">' + s.id + '</td>' +
        '<td>' + escHtml(s.baslik) + '</td>' +
        '<td class="text-center" id="sstatus-' + s.id + '">' + badge + '</td>' +
        '<td class="text-center">' +
        '<button class="btn btn-sm btn-outline-warning btn-gen-svc" data-id="' + s.id + '">' +
        '<i class="bx bx-magic-wand"></i></button></td></tr>';
}

$(document).on('click', '.btn-gen-svc', function() {
    var id = $(this).data('id');
    generateServiceFAQ([id]);
});

$('#btn-gen-all-services').on('click', function() {
    var ids = [];
    $('#services-tbody tr').each(function() {
        var id = $(this).find('.btn-gen-svc').data('id');
        if (id) ids.push(id);
    });
    if (!confirm(ids.length + ' hizmet için FAQPage şeması oluşturulacak. Devam?')) return;
    generateServiceFAQ(ids);
});

function generateServiceFAQ(ids) {
    var total = ids.length, ok = 0, err = 0, i = 0;
    $('#svc-progress-wrap').show();
    $('#btn-gen-all-services').prop('disabled', true);

    function next() {
        if (i >= total) {
            $('#svc-prog-bar').css('width', '100%').text('100%').removeClass('progress-bar-animated');
            $('#btn-gen-all-services').prop('disabled', false);
            return;
        }
        var id = ids[i]; i++;
        var pct = Math.round((i / total) * 100);
        $('#svc-prog-bar').css('width', pct + '%').text(pct + '%');
        $('#svc-prog-count').text(i + ' / ' + total);
        $('#sstatus-' + id).html('<span class="badge bg-warning text-dark"><i class="bx bx-loader-alt bx-spin"></i></span>');

        $.ajax({ url: AJAX, type: 'POST', data: { action: 'generate_service_faq', page_id: id },
            dataType: 'json', timeout: 120000,
            success: function(d) {
                if (d.success) {
                    ok++; $('#s-ok').text(ok);
                    $('#sstatus-' + id).html('<span class="badge bg-success"><i class="bx bx-check"></i> Oluşturuldu</span>');
                    $('#srow-' + id).addClass('table-success');
                } else {
                    err++; $('#s-err').text(err);
                    $('#sstatus-' + id).html('<span class="badge bg-danger" title="' + escHtml(d.message) + '">Hata</span>');
                }
                setTimeout(next, 300);
            },
            error: function() { err++; setTimeout(next, 300); }
        });
    }
    next();
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once 'includes/footer.php'; ?>
