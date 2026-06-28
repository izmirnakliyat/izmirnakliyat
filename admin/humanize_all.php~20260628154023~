<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'includes/header.php';
require_once 'includes/auto_blog_functions.php';

$page_title = 'AI İnsanlaştırma Modülü';

// humanized_at sütunu yoksa oluştur
$col_check = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'humanized_at'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE blog_posts ADD COLUMN humanized_at DATETIME DEFAULT NULL");
}

// Tüm blog yazılarını getir (insanlaştırma durumu dahil)
$posts = $conn->query("SELECT id, baslik, slug, updated_at, humanized_at,
    LENGTH(icerik) as icerik_len 
    FROM blog_posts 
    ORDER BY humanized_at ASC, id ASC")->fetch_all(MYSQLI_ASSOC);

$total         = count($posts);
$done_count    = count(array_filter($posts, fn($p) => !empty($p['humanized_at'])));
$pending_count = $total - $done_count;
$openai_key    = get_openai_api_key();
?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><i class="bx bx-user-voice text-warning"></i> AI İnsanlaştırma Modülü</h4>
            <p class="text-muted mb-0">Blog yazılarını Google AI dedektörlerini geçecek şekilde doğal, insansı bir dile dönüştürür.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" id="btn-select-all">
                <i class="bx bx-check-square me-1"></i> Tümünü Seç
            </button>
            <button class="btn btn-warning text-white" id="btn-humanize-selected" disabled>
                <i class="bx bx-user-voice me-1"></i> Seçilileri İnsanlaştır
            </button>
            <button class="btn btn-primary" id="btn-humanize-pending">
                <i class="bx bx-refresh me-1"></i> Edilmemişleri İnsanlaştır
                <span class="badge bg-white text-primary ms-1"><?= $pending_count ?></span>
            </button>
            <button class="btn btn-danger" id="btn-humanize-all">
                <i class="bx bx-refresh me-1"></i> Tümünü Yeniden İnsanlaştır
                <span class="badge bg-white text-danger ms-1"><?= $total ?></span>
            </button>
        </div>
    </div>

    <!-- İstatistik -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="h4 mb-0 text-success"><?= $done_count ?></div>
                <small class="text-muted">İnsanlaştırıldı</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="h4 mb-0 text-warning"><?= $pending_count ?></div>
                <small class="text-muted">Bekliyor</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="h4 mb-0 text-primary"><?= $total ?></div>
                <small class="text-muted">Toplam Yazı</small>
            </div>
        </div>
    </div>

    <!-- Filtre -->
    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">Tümü (<?= $total ?>)</button>
        <button class="btn btn-sm btn-outline-warning filter-btn" data-filter="pending">Bekleyenler (<?= $pending_count ?>)</button>
        <button class="btn btn-sm btn-outline-success filter-btn" data-filter="done">İnsanlaştırıldı (<?= $done_count ?>)</button>
    </div>

    <?php if (!$openai_key): ?>
    <div class="alert alert-danger">
        <i class="bx bx-error-circle me-2"></i>
        OpenAI API anahtarı tanımlı değil. Lütfen <a href="auto_blog.php">Otomatik Blog Modülü</a> sayfasından API anahtarınızı girin.
    </div>
    <?php endif; ?>

    <!-- İlerleme Paneli -->
    <div id="progress-panel" class="card border-warning mb-4" style="display:none;">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <span><i class="bx bx-loader-alt bx-spin me-2"></i><strong>İşlem Sürüyor...</strong></span>
            <button class="btn btn-sm btn-outline-dark" id="btn-stop-process">
                <i class="bx bx-stop-circle me-1"></i> Durdur
            </button>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-1">
                <span class="small fw-semibold" id="progress-text">Hazırlanıyor...</span>
                <span class="small text-muted" id="progress-count">0 / 0</span>
            </div>
            <div class="progress mb-2" style="height:20px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                     id="progress-bar" role="progressbar" style="width:0%">0%</div>
            </div>
            <div class="d-flex gap-3 small text-muted" id="progress-stats">
                <span><i class="bx bx-check text-success"></i> Başarılı: <strong id="cnt-success">0</strong></span>
                <span><i class="bx bx-x text-danger"></i> Hata: <strong id="cnt-error">0</strong></span>
                <span><i class="bx bx-skip-next text-secondary"></i> Atlanan: <strong id="cnt-skip">0</strong></span>
            </div>
        </div>
    </div>

    <!-- Sonuç Özeti -->
    <div id="result-summary" class="alert d-none mb-4" role="alert"></div>

    <!-- Blog Listesi -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold"><i class="bx bx-list-ul me-1"></i> Blog Yazıları (<?= $total ?>)</span>
            <div class="input-group input-group-sm" style="max-width:280px;">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" id="search-input" class="form-control" placeholder="Başlık ara...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="posts-table">
                <thead class="table-light">
                    <tr>
                        <th width="40"><input type="checkbox" id="check-all" class="form-check-input"></th>
                        <th width="60">ID</th>
                        <th>Başlık</th>
                        <th width="100" class="text-center">İçerik</th>
                        <th width="150" class="text-center">İnsanlaştırma</th>
                        <th width="120" class="text-center">İşlem Durumu</th>
                        <th width="100" class="text-center">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post):
                        $is_done = !empty($post['humanized_at']);
                        $row_cls = $is_done ? 'table-success' : '';
                        $data_status = $is_done ? 'done' : 'pending';
                    ?>
                    <tr id="row-<?= $post['id'] ?>"
                        data-title="<?= htmlspecialchars(strtolower($post['baslik'])) ?>"
                        data-status="<?= $data_status ?>"
                        class="<?= $row_cls ?>">
                        <td><input type="checkbox" class="form-check-input post-checkbox" value="<?= $post['id'] ?>"></td>
                        <td class="text-muted small"><?= $post['id'] ?></td>
                        <td>
                            <span class="fw-semibold"><?= htmlspecialchars($post['baslik']) ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border small">
                                ~<?= number_format(round($post['icerik_len'] / 5)) ?> kelime
                            </span>
                        </td>
                        <td class="text-center small">
                            <?php if ($is_done): ?>
                                <span class="text-success fw-semibold">
                                    <i class="bx bx-check-circle"></i>
                                    <?= date('d.m.Y H:i', strtotime($post['humanized_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center status-cell" id="status-<?= $post['id'] ?>">
                            <?php if ($is_done): ?>
                                <span class="badge bg-success"><i class="bx bx-check"></i> Tamamlandı</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Bekliyor</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm <?= $is_done ? 'btn-outline-secondary' : 'btn-outline-warning' ?> btn-humanize-single"
                                    data-id="<?= $post['id'] ?>"
                                    title="<?= $is_done ? 'Yeniden insanlaştır' : 'Bu yazıyı insanlaştır' ?>">
                                <i class="bx bx-user-voice"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
var SITE_URL    = <?= json_encode(SITE_URL) ?>;
var stopProcess = false;

// Arama
$('#search-input').on('input', function() {
    var q = $(this).val().toLowerCase().trim();
    $('#posts-table tbody tr').each(function() {
        $(this).toggle(!q || $(this).data('title').includes(q));
    });
});

// Filtre butonları
$('.filter-btn').on('click', function() {
    $('.filter-btn').removeClass('active');
    $(this).addClass('active');
    var f = $(this).data('filter');
    $('#posts-table tbody tr').each(function() {
        if (f === 'all') $(this).show();
        else $(this).toggle($(this).data('status') === f);
    });
});

// Tümünü seç / kaldır
$('#check-all, #btn-select-all').on('click', function() {
    var allChecked = $('.post-checkbox:visible').length === $('.post-checkbox:visible:checked').length;
    $('.post-checkbox:visible').prop('checked', !allChecked);
    $('#check-all').prop('checked', !allChecked);
    updateSelectedBtn();
});
$('#posts-table').on('change', '.post-checkbox', updateSelectedBtn);

function updateSelectedBtn() {
    var cnt = $('.post-checkbox:checked').length;
    $('#btn-humanize-selected').prop('disabled', cnt === 0)
        .html('<i class="bx bx-user-voice me-1"></i> Seçilileri İnsanlaştır (' + cnt + ')');
}

// Seçilileri insanlaştır
$('#btn-humanize-selected').on('click', function() {
    var ids = [];
    $('.post-checkbox:checked').each(function() { ids.push(parseInt($(this).val())); });
    if (!ids.length) return;
    if (!confirm(ids.length + ' yazı insanlaştırılacak. Bu işlem uzun sürebilir. Devam edilsin mi?')) return;
    runHumanize(ids);
});

// Edilmemişleri insanlaştır
$('#btn-humanize-pending').on('click', function() {
    var ids = [];
    $('#posts-table tbody tr[data-status="pending"]').each(function() {
        if ($(this).is(':visible')) ids.push(parseInt($(this).find('.post-checkbox').val()));
    });
    if (!ids.length) { alert('Bekleyen yazı yok!'); return; }
    if (!confirm(ids.length + ' insanlaştırılmamış yazı işlenecek. Devam?')) return;
    runHumanize(ids);
});

// Tümünü yeniden insanlaştır
$('#btn-humanize-all').on('click', function() {
    var ids = [];
    $('#posts-table tbody tr:visible').each(function() {
        ids.push(parseInt($(this).find('.post-checkbox').val()));
    });
    if (!confirm(ids.length + ' yazı insanlaştırılacak. Bu işlem uzun sürebilir. Devam edilsin mi?')) return;
    runHumanize(ids);
});

// Tekil insanlaştır
$(document).on('click', '.btn-humanize-single', function() {
    var id = parseInt($(this).data('id'));
    runHumanize([id]);
});

// Durdur
$('#btn-stop-process').on('click', function() {
    stopProcess = true;
    $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Durduruluyor...');
});

// Ana işlem fonksiyonu
function runHumanize(ids) {
    stopProcess = false;
    var total    = ids.length;
    var success  = 0;
    var errors   = 0;
    var skipped  = 0;
    var current  = 0;

    $('#progress-panel').show();
    $('#result-summary').addClass('d-none');
    $('#btn-humanize-all, #btn-humanize-selected').prop('disabled', true);
    $('#btn-stop-process').prop('disabled', false)
        .html('<i class="bx bx-stop-circle me-1"></i> Durdur');

    // Tüm satırları "Sırada" yap
    ids.forEach(function(id) {
        setStatus(id, 'queue', 'Sırada');
    });

    function processNext(idx) {
        if (idx >= total || stopProcess) {
            // Bitti
            finishProcess(success, errors, skipped, stopProcess);
            return;
        }

        var id = ids[idx];
        current = idx + 1;

        // Progress güncelle
        var pct = Math.round((idx / total) * 100);
        updateProgress(pct, current, total, success, errors, skipped,
            'İşleniyor: #' + id);
        setStatus(id, 'processing', '<i class="bx bx-loader-alt bx-spin"></i> İşleniyor');

        // İsteği gönder
        $.ajax({
            url: 'ajax/humanize_blog.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ post_id: id }),
            dataType: 'json',
            timeout: 200000,
            success: function(data) {
                if (data.success) {
                    success++;
                    setStatus(id, 'success',
                        '<i class="bx bx-check-circle text-success"></i> Tamamlandı' +
                        (data.new_word_count ? ' (~' + data.new_word_count + ' kelime)' : ''));
                } else {
                    errors++;
                    setStatus(id, 'error',
                        '<i class="bx bx-error-circle text-danger"></i> ' +
                        (data.message || 'Hata'));
                }
            },
            error: function(xhr) {
                errors++;
                setStatus(id, 'error',
                    '<i class="bx bx-error-circle text-danger"></i> Bağlantı hatası');
            },
            complete: function() {
                // Sıradaki — 500ms bekle (API rate limit)
                setTimeout(function() {
                    processNext(idx + 1);
                }, 500);
            }
        });
    }

    processNext(0);
}

function updateProgress(pct, current, total, success, errors, skipped, text) {
    $('#progress-bar').css('width', pct + '%').text(pct + '%');
    $('#progress-count').text(current + ' / ' + total);
    $('#progress-text').text(text);
    $('#cnt-success').text(success);
    $('#cnt-error').text(errors);
    $('#cnt-skip').text(skipped);
}

function setStatus(id, type, html) {
    var badges = {
        'queue':      'bg-secondary',
        'processing': 'bg-warning text-dark',
        'success':    'bg-success',
        'error':      'bg-danger'
    };
    var cls = badges[type] || 'bg-secondary';
    $('#status-' + id).html('<span class="badge ' + cls + ' small">' + html + '</span>');

    if (type === 'success') {
        var $row = $('#row-' + id);
        $row.addClass('table-success').data('status', 'done').attr('data-status', 'done');
        // İnsanlaştırma tarihini güncelle (anlık saat)
        var now = new Date();
        var pad = n => String(n).padStart(2,'0');
        var ts  = pad(now.getDate())+'.'+pad(now.getMonth()+1)+'.'+now.getFullYear()+' '+pad(now.getHours())+':'+pad(now.getMinutes());
        $row.find('td:eq(4)').html('<span class="text-success fw-semibold"><i class="bx bx-check-circle"></i> ' + ts + '</span>');
        $row.find('.btn-humanize-single').removeClass('btn-outline-warning').addClass('btn-outline-secondary').attr('title','Yeniden insanlaştır');
    } else if (type === 'error') {
        $('#row-' + id).addClass('table-danger');
    }
}

function finishProcess(success, errors, skipped, stopped) {
    var pct = 100;
    $('#progress-bar').css('width', '100%').text('100%')
        .removeClass('progress-bar-animated bg-warning')
        .addClass(errors > 0 ? 'bg-warning' : 'bg-success');

    $('#progress-panel .card-header')
        .removeClass('bg-warning text-dark')
        .addClass(errors > 0 ? 'bg-warning text-dark' : 'bg-success text-white');
    $('#progress-panel .card-header span')
        .html('<i class="bx bx-check-circle me-2"></i><strong>' +
              (stopped ? 'İşlem durduruldu.' : 'İşlem tamamlandı.') + '</strong>');
    $('#btn-stop-process').hide();

    var cls  = errors > 0 ? 'alert-warning' : 'alert-success';
    var icon = errors > 0 ? 'bx-error-circle' : 'bx-check-circle';
    var msg  = stopped ? 'İşlem durduruldu. ' : '';
    msg += '<strong>' + success + ' yazı</strong> başarıyla insanlaştırıldı.';
    if (errors > 0)   msg += ' <strong>' + errors  + ' hata</strong> oluştu.';
    if (skipped > 0)  msg += ' ' + skipped + ' yazı atlandı.';

    $('#result-summary').removeClass('d-none').addClass('alert ' + cls)
        .html('<i class="bx ' + icon + ' me-2"></i>' + msg);

    $('#btn-humanize-all, #btn-humanize-selected').prop('disabled', false);
    updateSelectedBtn();
}
</script>

<?php require_once 'includes/footer.php'; ?>
