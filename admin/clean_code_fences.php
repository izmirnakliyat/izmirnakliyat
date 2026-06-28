<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'includes/header.php';

$page_title = 'Kod Bloğu Temizleyici';

// Temizlenecek kalıplar:
// 1. ```html, ```php, ```json, ```markdown vb. satırları
// 2. Tek başına ``` satırları
// 3. Satır başı/sonu boşluklar
function has_code_fence($text) {
    return (bool)preg_match('/```[a-zA-Z0-9_\-]*/', $text);
}

function clean_code_fences($text) {
    // ```html, ```php vb. açılış etiketleri
    $text = preg_replace('/^```[a-zA-Z0-9_\-]*\s*\n?/m', '', $text);
    // Kapanış ``` 
    $text = preg_replace('/^```\s*$/m', '', $text);
    // Satır ortasında kalan ```
    $text = preg_replace('/```[a-zA-Z0-9_\-]*/m', '', $text);
    $text = preg_replace('/```/m', '', $text);
    // Baştaki/sondaki boş satırları temizle
    $text = trim($text);
    return $text;
}

// Tüm blog yazılarını tara, kod bloğu olanları bul
$affected = [];
$result = $conn->query("SELECT id, baslik, LEFT(icerik, 300) as icerik_preview, icerik FROM blog_posts ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (has_code_fence($row['icerik'])) {
            // İlk 200 karakter önizleme al (ham)
            $raw_start = htmlspecialchars(mb_substr(strip_tags($row['icerik_preview']), 0, 150));
            $affected[] = [
                'id'      => $row['id'],
                'baslik'  => $row['baslik'],
                'preview' => $raw_start,
            ];
        }
    }
}

$affected_count = count($affected);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bx bx-code-block text-danger"></i> Kod Bloğu Temizleyici
            </h4>
            <p class="text-muted mb-0">
                Blog yazılarındaki <code>```html</code>, <code>```php</code>, <code>```</code> gibi 
                yapay zeka kaynaklı markdown kalıntılarını temizler.
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge <?= $affected_count > 0 ? 'bg-danger' : 'bg-success' ?> fs-6">
                <?= $affected_count ?> Kirli Yazı
            </span>
        </div>
    </div>

    <!-- Sonuç bildirimi -->
    <div id="clean-result" class="mb-3" style="display:none;"></div>

    <?php if ($affected_count === 0): ?>
    <div class="alert alert-success">
        <i class="bx bx-check-circle me-2"></i>
        <strong>Temiz!</strong> Hiçbir blog yazısında kod bloğu kalıntısı bulunamadı.
    </div>
    <?php else: ?>

    <!-- Özet & Toplu Temizleme -->
    <div class="card mb-4 border-danger">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1">
                        <i class="bx bx-error-circle text-danger me-2"></i>
                        <strong><?= $affected_count ?> blog yazısında</strong> kod bloğu kalıntısı tespit edildi.
                    </h6>
                    <p class="text-muted mb-0 small">
                        Genellikle <code>```html</code>, <code>```php</code>, <code>```markdown</code> veya tek başına 
                        <code>```</code> şeklinde yazı başında ya da ortasında görünür. 
                        Temizleme işlemi geri alınamaz.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-danger" id="btn-clean-all">
                        <i class="bx bx-eraser me-1"></i> Tümünü Temizle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- İlerleme çubuğu (toplu işlem için) -->
    <div id="clean-progress" class="mb-3" style="display:none;">
        <div class="d-flex justify-content-between mb-1">
            <small class="fw-semibold" id="progress-label">İşleniyor...</small>
            <small id="progress-text">0 / <?= $affected_count ?></small>
        </div>
        <div class="progress" style="height:10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                 id="progress-bar" style="width:0%"></div>
        </div>
    </div>

    <!-- Tablo -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Kod Bloğu İçeren Yazılar</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">ID</th>
                            <th>Başlık</th>
                            <th>İçerik Başlangıcı</th>
                            <th style="width:130px">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="affected-table-body">
                        <?php foreach ($affected as $item): ?>
                        <tr id="row-<?= $item['id'] ?>">
                            <td class="text-muted fw-semibold"><?= $item['id'] ?></td>
                            <td>
                                <a href="blog_edit.php?id=<?= $item['id'] ?>" target="_blank"
                                   class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($item['baslik']) ?>
                                </a>
                            </td>
                            <td>
                                <code class="text-danger small"><?= $item['preview'] ?></code>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger btn-clean-single"
                                    data-id="<?= $item['id'] ?>">
                                    <i class="bx bx-eraser"></i> Temizle
                                </button>
                                <span class="spinner-border spinner-border-sm text-danger d-none" id="spin-<?= $item['id'] ?>"></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
var TOTAL = <?= $affected_count ?>;
var cleaned = 0;

function showResult(type, msg) {
    var cls  = type === 'success' ? 'alert-success' : 'alert-danger';
    var icon = type === 'success' ? 'bx-check-circle' : 'bx-error';
    $('#clean-result')
        .removeClass('alert-success alert-danger')
        .addClass('alert ' + cls)
        .html('<i class="bx ' + icon + ' me-2"></i>' + msg)
        .show();
    $('html, body').animate({ scrollTop: 0 }, 300);
}

function cleanSingle(postId, callback) {
    var $btn  = $('#row-' + postId + ' .btn-clean-single');
    var $spin = $('#spin-' + postId);

    $btn.prop('disabled', true);
    $spin.removeClass('d-none');

    $.ajax({
        url: 'ajax/clean_code_fence.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ post_id: postId }),
        dataType: 'json',
        timeout: 30000,
        success: function(data) {
            if (data.success) {
                $('#row-' + postId)
                    .addClass('table-success')
                    .find('td:nth-child(3)')
                    .html('<span class="text-success small"><i class="bx bx-check-circle me-1"></i>Temizlendi</span>');
                $btn.html('<i class="bx bx-check"></i>').addClass('btn-success').removeClass('btn-outline-danger');
                cleaned++;
            } else {
                $btn.prop('disabled', false);
                showResult('danger', '#' + postId + ' hatası: ' + data.message);
            }
        },
        error: function(xhr) {
            $btn.prop('disabled', false);
            showResult('danger', 'Sunucu hatası: ' + (xhr.responseText || 'Bilinmeyen hata'));
        },
        complete: function() {
            $spin.addClass('d-none');
            if (typeof callback === 'function') callback();
        }
    });
}

$(function() {
    // Tek temizle
    $(document).on('click', '.btn-clean-single', function() {
        var id = parseInt($(this).data('id'));
        cleanSingle(id);
    });

    // Tümünü temizle
    $('#btn-clean-all').on('click', function() {
        if (!confirm('Tüm ' + TOTAL + ' yazıdaki kod bloğu kalıntıları temizlenecek. Bu işlem geri alınamaz. Devam?')) return;

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Temizleniyor...');
        $('#clean-progress').show();

        var $rows = $('.btn-clean-single:not(.btn-success)');
        var ids   = $rows.map(function() { return parseInt($(this).data('id')); }).get();
        var total = ids.length;
        var done  = 0;

        function next(i) {
            if (i >= ids.length) {
                var pct = 100;
                $('#progress-bar').css('width', pct + '%');
                $('#progress-text').text(done + ' / ' + total);
                $('#progress-label').text('Tamamlandı!');
                showResult('success', '<strong>Tümü temizlendi!</strong> ' + done + ' blog yazısındaki kod bloğu kalıntıları kaldırıldı.');
                $btn.html('<i class="bx bx-check-circle me-1"></i> Tamamlandı').addClass('btn-success').removeClass('btn-danger');
                setTimeout(function() { $('#clean-progress').hide(); }, 3000);
                return;
            }
            cleanSingle(ids[i], function() {
                done++;
                var pct = Math.round((done / total) * 100);
                $('#progress-bar').css('width', pct + '%');
                $('#progress-text').text(done + ' / ' + total);
                $('#progress-label').text(done + '. yazı temizlendi...');
                setTimeout(function() { next(i + 1); }, 200);
            });
        }

        next(0);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
