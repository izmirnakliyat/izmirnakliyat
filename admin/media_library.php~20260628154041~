<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'includes/header.php';

$page_title = 'Medya Kütüphanesi';

// Tabloyu oluştur (yoksa)
$conn->query("CREATE TABLE IF NOT EXISTS `media_library` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `filename` varchar(255) NOT NULL,
    `original_name` varchar(255) DEFAULT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_type` varchar(100) DEFAULT NULL,
    `file_size` int(11) DEFAULT 0,
    `width` int(11) DEFAULT 0,
    `height` int(11) DEFAULT 0,
    `alt_text` varchar(255) DEFAULT NULL,
    `uploaded_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Yükleme dizini
$upload_dir = '../uploads/media/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Sayfalama
$per_page = 36;
$page     = max(1, (int)($_GET['page'] ?? 1));
$search   = trim($_GET['q'] ?? '');
$offset   = ($page - 1) * $per_page;

// Toplam sayı
$count_sql = "SELECT COUNT(*) as cnt FROM media_library";
$params    = [];
if ($search) {
    $count_sql .= " WHERE original_name LIKE ? OR alt_text LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like];
}
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['cnt'];
$total_pages = max(1, ceil($total / $per_page));

// Medya listesi
$list_sql = "SELECT * FROM media_library";
if ($search) {
    $list_sql .= " WHERE original_name LIKE ? OR alt_text LIKE ?";
}
$list_sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

$list_stmt = $conn->prepare($list_sql);
if ($search) {
    $like = '%' . $search . '%';
    $list_stmt->bind_param('ssii', $like, $like, $per_page, $offset);
} else {
    $list_stmt->bind_param('ii', $per_page, $offset);
}
$list_stmt->execute();
$media_items = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function format_file_size($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>

<div class="container-fluid">
    <!-- Başlık + Yükle butonu -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bx bx-images text-primary"></i> Medya Kütüphanesi</h4>
            <p class="text-muted mb-0"><?= $total ?> dosya &bull; Yeni resimler yükleyebilir, mevcut resimleri yönetebilirsiniz.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success" id="btn-import-media" title="Sitedeki mevcut tüm resimleri kütüphaneye aktar">
                <i class="bx bx-import me-1"></i> Mevcut Resimleri İçe Aktar
            </button>
            <button class="btn btn-primary" id="btn-open-uploader">
                <i class="bx bx-upload me-1"></i> Yeni Resim Yükle
            </button>
        </div>
    </div>

    <!-- İçe Aktarma Sonuç Alanı -->
    <div id="import-result" class="alert d-none mb-3" role="alert"></div>

    <!-- Yükleme Paneli (açılır/kapanır) -->
    <div id="upload-panel" class="card mb-4 border-primary" style="display:none;">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="bx bx-cloud-upload me-2"></i>Resim Yükle</span>
            <button type="button" class="btn-close btn-close-white" id="btn-close-uploader"></button>
        </div>
        <div class="card-body">
            <div id="drop-zone" class="border border-2 border-dashed rounded p-5 text-center"
                 style="border-color: #6c757d !important; cursor:pointer; transition: all .2s;"
                 ondragover="event.preventDefault(); this.style.borderColor='#0d6efd'; this.style.background='#f0f4ff';"
                 ondragleave="this.style.borderColor='#6c757d'; this.style.background='';"
                 ondrop="handleDrop(event);">
                <i class="bx bx-cloud-upload" style="font-size:3rem; color:#6c757d;"></i>
                <p class="mt-2 mb-1 fw-semibold">Resimleri buraya sürükleyin</p>
                <p class="text-muted small mb-3">veya</p>
                <label class="btn btn-outline-primary">
                    <i class="bx bx-folder-open me-1"></i> Bilgisayardan Seç
                    <input type="file" id="file-input" multiple accept="image/jpeg,image/png,image/webp,image/gif,image/avif" style="display:none;">
                </label>
                <p class="text-muted small mt-2 mb-0">JPG, PNG, WebP, GIF, AVIF &bull; Maksimum 10MB</p>
            </div>
            <!-- Yükleme İlerlemesi -->
            <div id="upload-progress-area" class="mt-3" style="display:none;"></div>
        </div>
    </div>

    <!-- Arama + Filtre -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Dosya adı veya alt metin ara..."
                               value="<?= htmlspecialchars($search) ?>">
                        <?php if ($search): ?>
                        <a href="media_library.php" class="btn btn-outline-secondary"><i class="bx bx-x"></i></a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">Ara</button>
                    </div>
                </div>
                <div class="col-md-6 text-end text-muted small">
                    Sayfa <?= $page ?> / <?= $total_pages ?>
                    &bull; <?= $total ?> dosya
                </div>
            </form>
        </div>
    </div>

    <!-- Medya Grid -->
    <?php if (empty($media_items)): ?>
    <div class="text-center py-5" id="empty-state">
        <i class="bx bx-images" style="font-size:4rem; color:#dee2e6;"></i>
        <h5 class="mt-3 text-muted"><?= $search ? 'Arama sonucu bulunamadı.' : 'Henüz medya yüklenmemiş.' ?></h5>
        <?php if (!$search): ?>
        <p class="text-muted">İlk resminizi yüklemek için "Yeni Resim Yükle" butonuna tıklayın,<br>ya da sitenizdeki mevcut resimleri içe aktarmak için <strong>"Mevcut Resimleri İçe Aktar"</strong> butonuna basın.</p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="row g-3" id="media-grid">
        <?php foreach ($media_items as $item): ?>
        <div class="col-6 col-md-3 col-lg-2 media-item" data-id="<?= $item['id'] ?>">
            <div class="card h-100 media-card shadow-sm" style="cursor:pointer; transition: all .15s;"
                 onclick="openMediaDetail(<?= $item['id'] ?>)"
                 onmouseover="this.style.boxShadow='0 4px 15px rgba(0,0,0,.2)'; this.style.transform='translateY(-2px)'"
                 onmouseout="this.style.boxShadow=''; this.style.transform=''">
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                     style="height:140px; overflow:hidden; border-radius: .375rem .375rem 0 0;">
                    <img src="<?= SITE_URL ?>/<?= htmlspecialchars($item['file_path']) ?>"
                         alt="<?= htmlspecialchars($item['alt_text'] ?? $item['original_name']) ?>"
                         style="max-width:100%; max-height:100%; object-fit:cover; width:100%; height:100%;"
                         loading="lazy"
                         onerror="this.parentElement.innerHTML='<i class=\'bx bx-image-alt\' style=\'font-size:2rem;color:#ccc\'></i>'">
                </div>
                <div class="card-body p-2">
                    <p class="card-text small text-truncate mb-0" title="<?= htmlspecialchars($item['original_name']) ?>">
                        <?= htmlspecialchars($item['original_name'] ?? $item['filename']) ?>
                    </p>
                    <p class="text-muted" style="font-size:10px; margin:0;">
                        <?= format_file_size($item['file_size']) ?>
                        <?php if ($item['width']): ?>&bull; <?= $item['width'] ?>×<?= $item['height'] ?><?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sayfalama -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>">
                    <i class="bx bx-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>
            <?php for ($i = max(1, $page-3); $i <= min($total_pages, $page+3); $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>">
                    <i class="bx bx-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Medya Detay Modal -->
<div class="modal fade" id="mediaDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-info-circle me-2"></i>Dosya Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6 text-center">
                        <img id="detail-img" src="" alt="" class="img-fluid rounded shadow-sm" style="max-height:350px;">
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th style="width:110px">Dosya Adı</th><td id="detail-name" class="text-break small"></td></tr>
                            <tr><th>Boyut</th><td id="detail-size"></td></tr>
                            <tr><th>Ölçüler</th><td id="detail-dims"></td></tr>
                            <tr><th>Tür</th><td id="detail-type"></td></tr>
                            <tr><th>Tarih</th><td id="detail-date"></td></tr>
                        </table>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">URL</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="detail-url" class="form-control font-monospace" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyUrl()" title="Kopyala">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alt Metin</label>
                            <input type="text" id="detail-alt" class="form-control form-control-sm"
                                   placeholder="SEO için açıklayıcı bir metin girin">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button class="btn btn-danger btn-sm" id="btn-delete-media">
                    <i class="bx bx-trash me-1"></i> Sil
                </button>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" id="btn-save-alt">
                        <i class="bx bx-save me-1"></i> Alt Metni Kaydet
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var SITE_URL     = <?= json_encode(SITE_URL) ?>;
var currentMediaId = null;

// Yükle paneli aç/kapat
$('#btn-open-uploader').on('click', function() {
    $('#upload-panel').slideDown(200);
    $(this).hide();
});
$('#btn-close-uploader').on('click', function() {
    $('#upload-panel').slideUp(200);
    $('#btn-open-uploader').show();
});

// Drag & Drop
function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#6c757d';
    e.currentTarget.style.background  = '';
    uploadFiles(e.dataTransfer.files);
}
$('#drop-zone').on('click', function(e) {
    if (!$(e.target).is('button, input, label')) {
        $('#file-input').trigger('click');
    }
});
$('#file-input').on('change', function() {
    uploadFiles(this.files);
});

function uploadFiles(files) {
    if (!files || !files.length) return;
    $('#upload-progress-area').html('').show();

    Array.from(files).forEach(function(file) {
        var id   = 'up_' + Date.now() + '_' + Math.random().toString(36).substr(2,5);
        var html = '<div id="' + id + '" class="d-flex align-items-center gap-2 mb-2 p-2 border rounded">' +
            '<i class="bx bx-image fs-4 text-primary"></i>' +
            '<div class="flex-grow-1 small text-truncate">' + file.name + '</div>' +
            '<div class="progress flex-grow-1" style="height:8px;"><div class="progress-bar progress-bar-striped progress-bar-animated bg-primary w-25" id="pb_' + id + '"></div></div>' +
            '<span class="text-muted small" id="st_' + id + '">Yükleniyor...</span></div>';
        $('#upload-progress-area').append(html);

        var fd = new FormData();
        fd.append('file', file);

        $.ajax({
            url: 'ajax/media_upload.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new XMLHttpRequest();
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        var pct = Math.round(e.loaded / e.total * 100);
                        $('#pb_' + id).css('width', pct + '%');
                    }
                };
                return xhr;
            },
            success: function(data) {
                if (data.success) {
                    $('#pb_' + id).removeClass('progress-bar-animated bg-primary').addClass('bg-success').css('width','100%');
                    $('#st_' + id).text('Yüklendi ✓').addClass('text-success');
                    prependMediaItem(data.item);
                } else {
                    $('#pb_' + id).removeClass('bg-primary').addClass('bg-danger');
                    $('#st_' + id).text('Hata: ' + data.message).addClass('text-danger');
                }
            },
            error: function() {
                $('#pb_' + id).removeClass('bg-primary').addClass('bg-danger');
                $('#st_' + id).text('Sunucu hatası').addClass('text-danger');
            }
        });
    });
}

function prependMediaItem(item) {
    // Boş mesajı kaldır
    $('#empty-state').remove();
    if (!$('#media-grid').length) {
        $('.container-fluid').append('<div class="row g-3" id="media-grid"></div>');
    }
    // URL: file_path varsa onu kullan, yoksa uploads/media/ fallback
    var imgUrl = item.file_path ? SITE_URL + '/' + item.file_path : SITE_URL + '/uploads/media/' + item.filename;

    var html = '<div class="col-6 col-md-3 col-lg-2 media-item" data-id="' + item.id + '">' +
        '<div class="card h-100 media-card shadow-sm" style="cursor:pointer;" onclick="openMediaDetail(' + item.id + ')"' +
        ' onmouseover="this.style.boxShadow=\'0 4px 15px rgba(0,0,0,.2)\'; this.style.transform=\'translateY(-2px)\'"' +
        ' onmouseout="this.style.boxShadow=\'\'; this.style.transform=\'\'">' +
        '<div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height:140px;overflow:hidden;border-radius:.375rem .375rem 0 0;">' +
        '<img src="' + imgUrl + '" style="max-width:100%;max-height:100%;object-fit:cover;width:100%;height:100%;" loading="lazy">' +
        '</div><div class="card-body p-2">' +
        '<p class="card-text small text-truncate mb-0">' + item.original_name + '</p>' +
        '<p class="text-muted" style="font-size:10px;margin:0;">' + item.file_size_str + '</p>' +
        '</div></div></div>';

    $('#media-grid').prepend(html);
}

// Detay Modal
function openMediaDetail(id) {
    currentMediaId = id;
    $.get('ajax/media_list.php?id=' + id, function(data) {
        if (!data.item) return;
        var item = data.item;
        // file_path öncelikli, yoksa uploads/media/ fallback
        var url = item.file_path
            ? SITE_URL + '/' + item.file_path
            : SITE_URL + '/uploads/media/' + item.filename;
        $('#detail-img').attr('src', url);
        $('#detail-name').text(item.original_name || item.filename);
        $('#detail-size').text(item.file_size_str || '—');
        $('#detail-dims').text(item.width && item.height ? item.width + '×' + item.height + ' px' : '—');
        $('#detail-type').text(item.file_type || '—');
        $('#detail-date').text(item.created_at);
        $('#detail-url').val(url);
        $('#detail-alt').val(item.alt_text || '');
        $('#mediaDetailModal').modal('show');
    }, 'json');
}

function copyUrl() {
    var url = $('#detail-url').val();
    navigator.clipboard.writeText(url).then(function() {
        var btn = $('#detail-url').next();
        btn.html('<i class="bx bx-check text-success"></i>');
        setTimeout(function() { btn.html('<i class="bx bx-copy"></i>'); }, 2000);
    });
}

// Alt metin kaydet
$('#btn-save-alt').on('click', function() {
    if (!currentMediaId) return;
    var alt  = $('#detail-alt').val();
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Kaydediliyor...');
    $.post('ajax/media_list.php', { action: 'update_alt', id: currentMediaId, alt_text: alt }, function(data) {
        $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Alt Metni Kaydet');
        if (data.success) {
            $btn.addClass('btn-outline-success').removeClass('btn-success');
            setTimeout(function() { $btn.addClass('btn-success').removeClass('btn-outline-success'); }, 1000);
        }
    }, 'json');
});

// Mevcut resimleri içe aktar
$('#btn-import-media').on('click', function() {
    if (!confirm('Sitedeki tüm mevcut resimler (blog, galeri, slayt, hizmet vb.) medya kütüphanesine aktarılacak. Devam edilsin mi?')) return;
    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Aktarılıyor...');
    $('#import-result').removeClass('d-none alert-success alert-danger alert-warning').addClass('alert-info').html('<i class="bx bx-loader-alt bx-spin me-1"></i> Resimler taranıyor, lütfen bekleyin...');

    $.ajax({
        url: 'ajax/media_import.php',
        type: 'POST',
        dataType: 'json',
        timeout: 120000,
        success: function(data) {
            $btn.prop('disabled', false).html('<i class="bx bx-import me-1"></i> Mevcut Resimleri İçe Aktar');
            if (data.success) {
                var cls = data.imported > 0 ? 'alert-success' : 'alert-warning';
                var icon = data.imported > 0 ? 'bx-check-circle' : 'bx-info-circle';
                $('#import-result')
                    .removeClass('d-none alert-info')
                    .addClass(cls)
                    .html('<i class="bx ' + icon + ' me-1"></i> <strong>' + data.message + '</strong>' +
                          (data.errors.length ? '<br><small class="text-danger">Hatalar: ' + data.errors.join(', ') + '</small>' : ''));
                if (data.imported > 0) {
                    setTimeout(function() { location.reload(); }, 1500);
                }
            } else {
                $('#import-result').removeClass('d-none alert-info').addClass('alert-danger').html('<i class="bx bx-error me-1"></i> Hata oluştu.');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-import me-1"></i> Mevcut Resimleri İçe Aktar');
            $('#import-result').removeClass('d-none alert-info').addClass('alert-danger').html('<i class="bx bx-error me-1"></i> Sunucu hatası oluştu.');
        }
    });
});

// Sil
$('#btn-delete-media').on('click', function() {
    if (!currentMediaId) return;
    if (!confirm('Bu dosya kalıcı olarak silinecek. Devam?')) return;
    var $btn = $(this);
    $btn.prop('disabled', true);
    $.post('ajax/media_delete.php', { id: currentMediaId }, function(data) {
        if (data.success) {
            $('#mediaDetailModal').modal('hide');
            $('.media-item[data-id="' + currentMediaId + '"]').fadeOut(300, function() { $(this).remove(); });
            currentMediaId = null;
        } else {
            alert('Silme hatası: ' + data.message);
            $btn.prop('disabled', false);
        }
    }, 'json');
});
</script>

<?php require_once 'includes/footer.php'; ?>
