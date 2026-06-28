<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once 'includes/header.php';
require_once 'includes/auto_blog_functions.php';

$page_title = 'Kopya Başlık Düzeltici';

// Kopya başlıkları bul
$duplicates = [];
$dup_result = $conn->query("
    SELECT baslik, COUNT(*) as adet, GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ',') as ids,
           GROUP_CONCAT(slug ORDER BY id ASC SEPARATOR '||') as slugs
    FROM blog_posts
    GROUP BY baslik
    HAVING COUNT(*) > 1
    ORDER BY adet DESC
");
if ($dup_result) {
    while ($row = $dup_result->fetch_assoc()) {
        $row['id_list']   = explode(',', $row['ids']);
        $row['slug_list'] = explode('||', $row['slugs']);
        $duplicates[] = $row;
    }
}

// Tüm blog yazılarının da kısa listesi
$total_result = $conn->query("SELECT COUNT(*) as cnt FROM blog_posts");
$total_posts  = $total_result ? $total_result->fetch_assoc()['cnt'] : 0;
$dup_count    = count($duplicates);
$openai_key   = get_openai_api_key();
$openai_model = get_openai_model();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bx bx-duplicate text-warning"></i> Kopya Başlık Düzeltici</h4>
            <p class="text-muted mb-0">Aynı başlığa sahip blog yazılarını tespit eder ve AI ile yeni benzersiz başlıklar üretir.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-secondary fs-6"><?= $total_posts ?> Toplam Yazı</span>
            <span class="badge <?= $dup_count > 0 ? 'bg-danger' : 'bg-success' ?> fs-6">
                <?= $dup_count ?> Kopya Grup
            </span>
        </div>
    </div>

    <!-- Sonuç bildirimi -->
    <div id="fix-result" class="mb-3" style="display:none;"></div>

    <?php if (!$openai_key): ?>
    <div class="alert alert-warning">
        <i class="bx bx-info-circle me-2"></i>
        OpenAI API anahtarı tanımlı değil.
        <a href="auto_blog.php" class="alert-link">Otomatik Blog Modülü</a>'nden API anahtarı girin.
    </div>
    <?php endif; ?>

    <?php if (empty($duplicates)): ?>
    <div class="alert alert-success">
        <i class="bx bx-check-circle me-2"></i>
        <strong>Harika!</strong> Hiç kopya başlık bulunamadı. Tüm blog yazılarınızın başlıkları benzersiz.
    </div>
    <?php else: ?>

    <!-- Özet ve Toplu İşlem -->
    <div class="card mb-4 border-warning">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1"><i class="bx bx-error-circle text-warning me-2"></i>
                        <strong><?= $dup_count ?> grupta kopya başlık tespit edildi.</strong>
                    </h6>
                    <p class="text-muted mb-0 small">
                        Her kopya başlık için AI (<?= htmlspecialchars($openai_model) ?>) benzersiz, SEO dostu yeni başlıklar üretecek
                        ve URL slug'larını otomatik güncelleyecektir.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-warning" id="btn-fix-all"
                        <?= !$openai_key ? 'disabled title="Önce API anahtarı girin"' : '' ?>>
                        <i class="bx bx-magic-wand me-1"></i> Tümünü AI ile Düzelt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Kopya Başlık Tablosu -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Kopya Başlıklar</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="dup-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Kopya Başlık</th>
                            <th style="width:80px">Adet</th>
                            <th>Blog Yazıları (ID → Slug)</th>
                            <th style="width:160px">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($duplicates as $i => $dup): ?>
                        <tr id="dup-row-<?= $i ?>">
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($dup['baslik']) ?></div>
                                <div id="new-title-preview-<?= $i ?>" class="text-success small mt-1" style="display:none;"></div>
                            </td>
                            <td>
                                <span class="badge bg-danger"><?= $dup['adet'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($dup['id_list'] as $idx => $pid): ?>
                                    <a href="blog_edit.php?id=<?= (int)$pid ?>" target="_blank"
                                       class="badge bg-light text-dark border text-decoration-none" style="font-size:11px;">
                                        #<?= (int)$pid ?>
                                        <?php if (!empty($dup['slug_list'][$idx])): ?>
                                        <span class="text-muted">/<?= htmlspecialchars(mb_substr($dup['slug_list'][$idx], 0, 30)) ?>...</span>
                                        <?php endif; ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary btn-fix-single"
                                    data-index="<?= $i ?>"
                                    data-title="<?= htmlspecialchars($dup['baslik'], ENT_QUOTES) ?>"
                                    data-ids="<?= htmlspecialchars($dup['ids']) ?>"
                                    <?= !$openai_key ? 'disabled' : '' ?>>
                                    <i class="bx bx-magic-wand"></i> Düzelt
                                </button>
                                <div class="spinner-border spinner-border-sm text-primary ms-1 d-none" id="spin-<?= $i ?>"></div>
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
var DUP_DATA = <?= json_encode(array_map(function($d) {
    return ['baslik' => $d['baslik'], 'ids' => $d['ids'], 'index' => null];
}, $duplicates)) ?>;

function showResult(type, msg) {
    var cls = type === 'success' ? 'alert-success' : 'alert-danger';
    var icon = type === 'success' ? 'bx-check-circle' : 'bx-error';
    $('#fix-result')
        .removeClass('alert-success alert-danger')
        .addClass('alert ' + cls)
        .html('<i class="bx ' + icon + ' me-2"></i>' + msg)
        .show();
    $('html, body').animate({ scrollTop: 0 }, 400);
}

function fixSingle(index, title, ids, btn) {
    var $btn = $(btn);
    var $spin = $('#spin-' + index);
    $btn.prop('disabled', true);
    $spin.removeClass('d-none');
    $('#new-title-preview-' + index).hide();

    $.ajax({
        url: 'ajax/fix_duplicate_title.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ title: title, ids: ids }),
        dataType: 'json',
        timeout: 90000,
        success: function(data) {
            if (data.success) {
                $('#new-title-preview-' + index)
                    .html('<i class="bx bx-check-circle me-1"></i><strong>Düzeltildi:</strong> ' + data.message)
                    .show();
                $btn.html('<i class="bx bx-check"></i> Düzeltildi').addClass('btn-success').removeClass('btn-outline-primary');
                // Satırı yeşile boyamak
                $('#dup-row-' + index).addClass('table-success');
            } else {
                showResult('danger', 'Hata (#' + index + '): ' + data.message);
                $btn.prop('disabled', false);
            }
        },
        error: function(xhr, status) {
            var msg = status === 'timeout' ? 'Zaman aşımı.' : (xhr.responseText || 'Sunucu hatası.');
            showResult('danger', 'Hata: ' + msg);
            $btn.prop('disabled', false);
        },
        complete: function() {
            $spin.addClass('d-none');
        }
    });
}

$(function() {
    // Tek tek düzelt
    $('.btn-fix-single').on('click', function() {
        var index = $(this).data('index');
        var title = $(this).data('title');
        var ids   = $(this).data('ids');
        fixSingle(index, title, ids, this);
    });

    // Tümünü düzelt
    $('#btn-fix-all').on('click', function() {
        if (!confirm('Tüm kopya başlıklar AI ile düzeltilecek. Bu işlem birkaç dakika sürebilir. Devam?')) return;
        var $rows = $('.btn-fix-single:not(:disabled)');
        var total = $rows.length;
        var done  = 0;

        $('#btn-fix-all').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Düzeltiliyor (' + total + ')...');

        function fixNext(i) {
            if (i >= $rows.length) {
                showResult('success', 'Tüm kopya başlıklar düzeltildi! (' + done + '/' + total + ')');
                $('#btn-fix-all').html('<i class="bx bx-check-circle me-1"></i> Tamamlandı').addClass('btn-success').removeClass('btn-warning');
                return;
            }
            var $btn   = $($rows[i]);
            var index  = $btn.data('index');
            var title  = $btn.data('title');
            var ids    = $btn.data('ids');

            fixSingle(index, title, ids, $btn[0]);

            // Bir sonraki düzeltmeyi 2sn bekle (rate limit)
            setTimeout(function() {
                done++;
                fixNext(i + 1);
            }, 2000);
        }

        fixNext(0);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
