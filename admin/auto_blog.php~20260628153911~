<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once '../config/config.php';
require_once '../config/db.php';
include 'includes/init.php';
include 'includes/auto_blog_functions.php';

$page_title = 'AI Makale Sistemi';

ensure_auto_blog_tasks_table();

// API Key kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['openai_api_key'])) {
    save_openai_api_key(trim($_POST['openai_api_key']));
    $key_success = 'API anahtarı kaydedildi!';
}
// Model kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['openai_model'])) {
    save_openai_model($_POST['openai_model']);
    $model_success = 'Model kaydedildi: ' . htmlspecialchars($_POST['openai_model']);
}

$openai_api_key = get_openai_api_key();
$openai_model   = get_openai_model();
$categories     = get_all_categories();
$tasks          = get_auto_blog_tasks();

// Görev düzenleme
$edit_task = null;
if (isset($_GET['edit'])) {
    $edit_task = get_auto_blog_task((int)$_GET['edit']);
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bx bx-bot" style="color:#6610f2;"></i>
                <strong style="color:#6610f2;">AI Makale Sistemi</strong>
            </h4>
            <p class="text-muted mb-0">Akıllı, SEO uyumlu ve benzersiz içerik üretimi</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" id="btn-run-all-tasks">
                <i class="bx bx-play me-1"></i> Görevleri Şimdi Çalıştır
            </button>
            <a href="blog_posts.php" class="btn btn-outline-secondary">
                <i class="bx bx-list-ul me-1"></i> Blog Yazıları
            </a>
        </div>
    </div>

    <!-- Çalıştırma İlerleme Paneli -->
    <div id="run-panel" class="card border-success mb-4" style="display:none;">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span><i class="bx bx-loader-alt bx-spin me-2"></i><strong id="run-panel-title">Görevler çalışıyor...</strong></span>
            <button class="btn btn-sm btn-outline-light" id="btn-stop-run">Durdur</button>
        </div>
        <div class="card-body">
            <div class="progress mb-2" style="height:18px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="run-progress-bar" style="width:0%">0%</div>
            </div>
            <div id="run-log" class="bg-dark text-success rounded p-3 font-monospace small" style="max-height:200px; overflow-y:auto; min-height:60px;"></div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($key_success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bx bx-check-circle me-1"></i><?= $key_success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- SOL PANEL: Görev Formu -->
        <div class="col-lg-5">
            <!-- API Key Kartı -->
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header py-2" style="background:linear-gradient(135deg,#6610f2,#0d6efd); color:white;">
                    <h6 class="mb-0"><i class="bx bx-key me-1"></i> OpenAI Bağlantısı</h6>
                </div>
                <div class="card-body py-2">
                    <div class="row g-2">
                        <div class="col-8">
                            <form id="openai-key-form" class="d-flex gap-2">
                                <input type="<?= $openai_api_key ? 'password' : 'text' ?>"
                                       name="openai_api_key" id="openai_api_key" class="form-control form-control-sm"
                                       value="<?= $openai_api_key ? str_repeat('*', 20) : '' ?>"
                                       placeholder="sk-..." autocomplete="off"
                                       <?= $openai_api_key ? 'readonly' : '' ?>>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-edit-key" title="Düzenle"><i class="bx bx-edit"></i></button>
                            </form>
                        </div>
                        <div class="col-4">
                            <form method="POST" class="d-flex gap-1">
                                <select name="openai_model" class="form-select form-select-sm">
                                    <?php foreach(['gpt-4o-mini'=>'4o-mini','gpt-4o'=>'4o','gpt-4-turbo'=>'4-turbo','gpt-3.5-turbo'=>'3.5'] as $v=>$l): ?>
                                    <option value="<?=$v?>" <?=$openai_model===$v?'selected':''?>><?=$l?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary" title="Kaydet"><i class="bx bx-save"></i></button>
                            </form>
                        </div>
                    </div>
                    <div id="key-alert" class="mt-1"></div>
                </div>
            </div>

            <!-- Görev Formu -->
            <div class="card border-0 shadow-sm" id="task-form-card">
                <div class="card-header py-2" style="background:linear-gradient(135deg,#6610f2,#0d6efd); color:white;">
                    <h6 class="mb-0" id="form-card-title">
                        <i class="bx bx-plus-circle me-1"></i> <?= $edit_task ? 'Görevi Düzenle' : 'Yeni Görev' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form id="task-form">
                        <input type="hidden" id="task_id" value="<?= $edit_task ? $edit_task['id'] : '' ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Görev Adı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="task_name" placeholder="Örn: Günlük SEO Makaleleri"
                                   value="<?= htmlspecialchars($edit_task['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Konular <span class="text-danger">*</span>
                                <span class="text-muted fw-normal">(Her satıra bir konu)</span>
                            </label>
                            <textarea class="form-control font-monospace" id="task_topics" rows="6"
                                      placeholder="İstanbul evden eve nakliyat&#10;Ankara taşımacılık fiyatları&#10;Eşya paketleme teknikleri&#10;Nakliyat sigortası nedir"
                                      required><?= htmlspecialchars($edit_task['topics'] ?? '') ?></textarea>
                            <div class="form-text"><i class="bx bx-shuffle text-primary"></i> Her çalışmada rastgele konu seçer — <span id="topic-count">0</span> konu</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Sıklık</label>
                                <select class="form-select" id="task_schedule">
                                    <option value="hourly"      <?= ($edit_task['schedule'] ?? '') === 'hourly'      ? 'selected':'' ?>>Saatlik</option>
                                    <option value="twice_daily" <?= ($edit_task['schedule'] ?? 'daily') === 'twice_daily' ? 'selected':'' ?>>Günde 2 Kez</option>
                                    <option value="daily"       <?= ($edit_task['schedule'] ?? 'daily') === 'daily'  ? 'selected':'' ?>>Günlük</option>
                                    <option value="weekly"      <?= ($edit_task['schedule'] ?? '') === 'weekly'      ? 'selected':'' ?>>Haftalık</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Makale/Çalışma</label>
                                <input type="number" class="form-control" id="task_articles" min="1" max="10"
                                       value="<?= $edit_task['articles_per_run'] ?? 1 ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Kategori</label>
                            <select class="form-select" id="task_category">
                                <option value="">Kategori Seç</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($edit_task['category_id'] ?? '') == $cat['id'] ? 'selected':'' ?>>
                                    <?= htmlspecialchars($cat['ad']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Ton</label>
                                <select class="form-select" id="task_tone">
                                    <option value="professional" <?= ($edit_task['tone'] ?? 'professional') === 'professional' ? 'selected':'' ?>>Profesyonel</option>
                                    <option value="casual"       <?= ($edit_task['tone'] ?? '') === 'casual'       ? 'selected':'' ?>>Samimi</option>
                                    <option value="academic"     <?= ($edit_task['tone'] ?? '') === 'academic'     ? 'selected':'' ?>>Akademik</option>
                                    <option value="seo"          <?= ($edit_task['tone'] ?? '') === 'seo'          ? 'selected':'' ?>>SEO Odaklı</option>
                                    <option value="storytelling" <?= ($edit_task['tone'] ?? '') === 'storytelling' ? 'selected':'' ?>>Hikaye Anlatıcı</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Uzunluk</label>
                                <select class="form-select" id="task_length">
                                    <option value="short"  <?= ($edit_task['length'] ?? '') === 'short'  ? 'selected':'' ?>>Kısa (600-800 k.)</option>
                                    <option value="medium" <?= ($edit_task['length'] ?? 'medium') === 'medium' ? 'selected':'' ?>>Orta (1000-1400 k.)</option>
                                    <option value="long"   <?= ($edit_task['length'] ?? '') === 'long'   ? 'selected':'' ?>>Uzun (1500-2000 k.)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Varsayılan Görsel</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm" id="task_image" readonly
                                       placeholder="Medya kütüphanesinden seç..."
                                       value="<?= htmlspecialchars($edit_task['default_image'] ?? '') ?>">
                                <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" id="btn-pick-img">
                                    <i class="bx bx-images me-1"></i> Seç
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-clear-img" title="Temizle"><i class="bx bx-x"></i></button>
                            </div>
                            <div id="img-preview" class="mt-2" style="<?= empty($edit_task['default_image']) ? 'display:none' : '' ?>">
                                <img src="<?= $edit_task && $edit_task['default_image'] ? SITE_URL . '/uploads/' . $edit_task['default_image'] : '' ?>"
                                     alt="" style="max-height:80px; border-radius:4px; border:2px solid #0d6efd;">
                            </div>
                            <div class="form-text">Her makalede bu görsel kapak fotoğrafı olarak kullanılır.</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="task_status"
                                       <?= ($edit_task['status'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="task_status">Aktif</label>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($edit_task): ?>
                                <a href="auto_blog.php" class="btn btn-outline-secondary btn-sm">İptal</a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary" id="btn-save-task">
                                    <i class="bx bx-save me-1"></i> <?= $edit_task ? 'Güncelle' : 'Kaydet' ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cron Job Bilgisi -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header py-2 bg-dark text-white">
                    <h6 class="mb-0 font-monospace"><i class="bx bx-terminal me-1"></i> Cron Job Kurulumu</h6>
                </div>
                <div class="card-body py-2">
                    <p class="text-muted small mb-2">Görevlerin otomatik çalışması için cPanel'den cron ekleyin. Manuel test için yukarıdaki butonu kullanın.</p>
                    <div class="input-group input-group-sm">
                        <code class="form-control bg-dark text-success font-monospace small" id="cron-line" style="border-color:#333;">
                            0 9,18 * * * php <?= str_replace('\\', '/', realpath('../')) ?>/cron/run-ai-tasks.php
                        </code>
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('cron-line').textContent.trim())">
                            <i class="bx bx-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ PANEL: Zamanlanmış Görevler -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-2" style="background:linear-gradient(135deg,#6610f2,#0d6efd); color:white;">
                    <h6 class="mb-0">
                        <i class="bx bx-time me-1"></i> Zamanlanmış Görevler
                        <span class="badge bg-white text-dark ms-2"><?= count($tasks) ?></span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($tasks)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-calendar-x" style="font-size:3rem;"></i>
                        <p class="mt-2">Henüz görev oluşturulmadı.<br>Sol panelden ilk görevinizi ekleyin.</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush" id="task-list">
                        <?php foreach($tasks as $t):
                            $topic_count = count(array_filter(array_map('trim', explode("\n", $t['topics']))));
                            $sched_labels = ['hourly'=>'Saatlik','twice_daily'=>'Günde 2x','daily'=>'Günlük','weekly'=>'Haftalık'];
                            $tone_labels  = ['professional'=>'Profesyonel','casual'=>'Samimi','academic'=>'Akademik','seo'=>'SEO','storytelling'=>'Hikaye'];
                            $len_labels   = ['short'=>'Kısa','medium'=>'Orta','long'=>'Uzun'];
                        ?>
                        <div class="list-group-item list-group-item-action px-3 py-3" id="task-row-<?= $t['id'] ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold"><?= htmlspecialchars($t['name']) ?></span>
                                        <?php if($t['status']): ?>
                                        <span class="badge bg-success" style="font-size:10px;">AKT</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size:10px;">PASİF</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 small text-muted">
                                        <span><i class="bx bx-time-five"></i> <?= $sched_labels[$t['schedule']] ?? $t['schedule'] ?></span>
                                        <span><i class="bx bx-file"></i> <?= $t['articles_per_run'] ?> makale/çalışma</span>
                                        <span><i class="bx bx-list-ul"></i> <?= $topic_count ?> konu</span>
                                        <span><i class="bx bx-palette"></i> <?= $tone_labels[$t['tone']] ?? $t['tone'] ?></span>
                                        <span><i class="bx bx-text"></i> <?= $len_labels[$t['length']] ?? $t['length'] ?></span>
                                        <?php if($t['default_image']): ?>
                                        <span class="badge bg-light text-dark border"><i class="bx bx-image"></i> Sabit görsel</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-3 small mt-1">
                                        <?php if($t['last_run']): ?>
                                        <span class="text-muted"><i class="bx bx-history"></i> Son: <?= date('d.m H:i', strtotime($t['last_run'])) ?></span>
                                        <?php endif; ?>
                                        <?php if($t['next_run']): ?>
                                        <span class="text-primary"><i class="bx bx-calendar"></i> Sonraki: <?= date('d.m H:i', strtotime($t['next_run'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-1 ms-2">
                                    <button class="btn btn-sm btn-success btn-run-task" data-id="<?= $t['id'] ?>" title="Şimdi çalıştır">
                                        <i class="bx bx-play"></i>
                                    </button>
                                    <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-task" data-id="<?= $t['id'] ?>" title="Sil">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Görev ilerleme -->
                            <div class="task-progress mt-2" id="task-prog-<?= $t['id'] ?>" style="display:none;">
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%" id="task-prog-bar-<?= $t['id'] ?>"></div>
                                </div>
                                <div class="small text-muted mt-1" id="task-prog-text-<?= $t['id'] ?>">Hazırlanıyor...</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var SITE_URL = <?= json_encode(SITE_URL) ?>;
var stopRun  = false;

// Topic sayacı
function updateTopicCount() {
    var lines = $('#task_topics').val().split('\n').filter(l => l.trim() !== '');
    $('#topic-count').text(lines.length);
}
$('#task_topics').on('input', updateTopicCount);
updateTopicCount();

// Görsel seçici
$('#btn-pick-img').on('click', function() {
    if (typeof MediaPicker !== 'undefined') {
        MediaPicker.open(function(item) {
            // item.filename = sadece dosya adı, item.url = tam URL
            var path = item.file_path ? item.file_path.replace('uploads/', '') : 'media/' + item.filename;
            $('#task_image').val(path);
            $('#img-preview').show().find('img').attr('src', item.url);
        });
    }
});
$('#btn-clear-img').on('click', function() {
    $('#task_image').val('');
    $('#img-preview').hide().find('img').attr('src','');
});

// API key düzenle
$('#btn-edit-key').on('click', function() {
    var $inp = $('#openai_api_key');
    $inp.attr('type','text').val('').removeAttr('readonly').focus();
    $(this).after('<button type="button" class="btn btn-sm btn-success" id="btn-save-key"><i class="bx bx-save"></i></button>');
    $(this).remove();
});
$(document).on('click', '#btn-save-key', function() {
    var key = $('#openai_api_key').val().trim();
    if (!key) return;
    $.post('', { openai_api_key: key }, function() { location.reload(); });
});

// Görev kaydet
$('#task-form').on('submit', function(e) {
    e.preventDefault();
    var topics = $('#task_topics').val().trim();
    if (!topics) { alert('En az bir konu girin.'); return; }

    var $btn = $('#btn-save-task');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Kaydediliyor...');

    $.ajax({
        url: 'ajax/save_auto_task.php',
        type: 'POST',
        data: {
            task_id:          $('#task_id').val(),
            name:             $('#task_name').val(),
            topics:           topics,
            schedule:         $('#task_schedule').val(),
            articles_per_run: $('#task_articles').val(),
            category_id:      $('#task_category').val(),
            tone:             $('#task_tone').val(),
            length:           $('#task_length').val(),
            default_image:    $('#task_image').val(),
            status:           $('#task_status').is(':checked') ? 1 : 0,
        },
        dataType: 'json',
        success: function(d) {
            if (d.success) {
                location.href = 'auto_blog.php';
            } else {
                alert('Hata: ' + d.message);
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Kaydet');
            }
        },
        error: function() {
            alert('Sunucu hatası.');
            $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Kaydet');
        }
    });
});

// Görev sil
$(document).on('click', '.btn-delete-task', function() {
    if (!confirm('Bu görevi silmek istediğinize emin misiniz?')) return;
    var id = $(this).data('id');
    $.post('ajax/delete_auto_task.php', { task_id: id }, function(d) {
        if (d.success) {
            $('#task-row-' + id).fadeOut(300, function() { $(this).remove(); });
        } else {
            alert('Silme hatası: ' + d.message);
        }
    }, 'json');
});

// Tek görevi çalıştır
$(document).on('click', '.btn-run-task', function() {
    var id = $(this).data('id');
    runTask(id);
});

// Tüm görevleri çalıştır
$('#btn-run-all-tasks').on('click', function() {
    var ids = [];
    $('.btn-run-task').each(function() { ids.push($(this).data('id')); });
    if (!ids.length) { alert('Henüz görev yok.'); return; }
    if (!confirm(ids.length + ' görev çalıştırılacak. Devam?')) return;
    stopRun = false;
    runTasksSequential(ids, 0);
    $('#run-panel').slideDown(200);
});
$('#btn-stop-run').on('click', function() { stopRun = true; });

function runTasksSequential(ids, idx) {
    if (idx >= ids.length || stopRun) {
        $('#run-panel .card-header span').html('<i class="bx bx-check-circle me-2"></i><strong>Tamamlandı!</strong>');
        $('#run-progress-bar').css('width','100%').removeClass('progress-bar-animated');
        $('#btn-run-all-tasks').prop('disabled', false);
        return;
    }
    var pct = Math.round((idx / ids.length) * 100);
    $('#run-progress-bar').css('width', pct + '%').text(pct + '%');
    runTask(ids[idx], function() {
        setTimeout(function() { runTasksSequential(ids, idx + 1); }, 500);
    });
}

function runTask(taskId, onDone) {
    var $btn  = $('.btn-run-task[data-id="' + taskId + '"]');
    var $prog = $('#task-prog-' + taskId);
    var $bar  = $('#task-prog-bar-' + taskId);
    var $txt  = $('#task-prog-text-' + taskId);

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');
    $prog.show();
    $bar.css('width', '30%');
    $txt.text('AI içerik üretiyor...');

    $.ajax({
        url: 'ajax/run_auto_task.php',
        type: 'POST',
        data: { task_id: taskId },
        dataType: 'json',
        timeout: 300000,
        success: function(d) {
            $btn.prop('disabled', false).html('<i class="bx bx-play"></i>');
            if (d.success) {
                $bar.css('width','100%').removeClass('progress-bar-animated bg-success').addClass('bg-success');
                $txt.html('<i class="bx bx-check text-success"></i> ' + d.message);
                // Log
                (d.posts || []).forEach(function(p) {
                    logRun('✅ "' + p.title + '" oluşturuldu (ID: ' + p.post_id + ')');
                });
                // Next run güncelle
                if (d.next_run) {
                    var $row = $('#task-row-' + taskId);
                    $row.find('.text-primary').html('<i class="bx bx-calendar"></i> Sonraki: ' + d.next_run);
                }
            } else {
                $bar.css('width','100%').removeClass('bg-success progress-bar-animated').addClass('bg-danger');
                $txt.html('<i class="bx bx-error text-danger"></i> ' + d.message);
                logRun('❌ Görev #' + taskId + ' hata: ' + d.message);
            }
        },
        error: function(xhr) {
            $btn.prop('disabled', false).html('<i class="bx bx-play"></i>');
            $bar.css('width','100%').addClass('bg-danger').removeClass('progress-bar-animated');
            $txt.html('<i class="bx bx-error text-danger"></i> Sunucu hatası / Zaman aşımı');
            logRun('❌ Görev #' + taskId + ' sunucu hatası.');
        },
        complete: function() { if (onDone) onDone(); }
    });
}

function logRun(msg) {
    var $log = $('#run-log');
    var d    = new Date();
    var ts   = d.getHours() + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
    $log.append('<div>[' + ts + '] ' + msg + '</div>');
    $log.scrollTop($log[0].scrollHeight);
}
</script>

<?php include 'includes/footer.php'; ?>
