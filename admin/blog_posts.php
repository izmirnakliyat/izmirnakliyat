<?php
require_once 'includes/header.php';
require_once 'includes/auto_blog_functions.php';

$page_title = "Blog Yazıları";
$success = '';
$error = '';
$openai_key = get_openai_api_key();

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Önce kapak fotoğrafını sil
    $result = $conn->query("SELECT kapak_foto FROM blog_posts WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['kapak_foto'])) {
            $file_path = '../uploads/blog/' . $row['kapak_foto'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    // Sonra yazıyı sil
    if ($conn->query("DELETE FROM blog_posts WHERE id = $id")) {
        $success = "Blog yazısı başarıyla silindi.";
    } else {
        $error = "Blog yazısı silinirken bir hata oluştu.";
    }
}

// Blog yazılarını getir
$posts = $conn->query("SELECT p.*, c.ad as kategori_adi 
                      FROM blog_posts p 
                      LEFT JOIN blog_categories c ON p.kategori_id = c.id 
                      ORDER BY p.created_at DESC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Blog Yazıları</h5>
        <div class="d-flex gap-2">
            <a href="clean_code_fences.php" class="btn btn-outline-secondary btn-sm" title="```html gibi kalıntıları temizle">
                <i class='bx bx-code-block'></i> Kod Bloğu Temizle
            </a>
            <?php if ($openai_key): ?>
            <a href="duplicate_titles.php" class="btn btn-warning btn-sm">
                <i class='bx bx-duplicate'></i> Kopya Başlık Düzeltici
            </a>
            <?php endif; ?>
            <a href="blog_edit.php" class="btn btn-primary">
                <i class='bx bx-plus'></i> Yeni Yazı Ekle
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kapak</th>
                        <th>Başlık</th>
                        <th>Kategori</th>
                        <th>Etiketler</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                        <?php if ($openai_key): ?><th>AI</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($posts->num_rows > 0): ?>
                        <?php while ($post = $posts->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $post['id']; ?></td>
                                <td>
                                    <?php if (!empty($post['kapak_foto'])): ?>
                                        <img src="../uploads/blog/<?php echo htmlspecialchars($post['kapak_foto']); ?>" alt="<?php echo htmlspecialchars($post['baslik']); ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 6px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($post['baslik']); ?></td>
                                <td><?php echo htmlspecialchars($post['kategori_adi'] ?? 'Kategori yok'); ?></td>
                                <td><?php echo htmlspecialchars($post['etiketler']); ?></td>
                                <td>
                                    <?php $ds = (int) $post['durum']; ?>
                                    <?php
                                    $badge = 'secondary';
                                    if ($ds === MYNAK_BLOG_STATUS_PUBLISHED) $badge = 'success';
                                    elseif ($ds === MYNAK_BLOG_STATUS_EDITOR_QUEUE) $badge = 'warning';
                                    elseif ($ds === MYNAK_BLOG_STATUS_REVISION) $badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars(mynak_blog_status_label_tr($ds)); ?></span>
                                    <a href="blog_edit.php?id=<?php echo (int) $post['id']; ?>#blogForm" class="btn btn-sm btn-outline-secondary ms-1">Düzenle</a>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></td>
                                <td>
                                    <a href="blog_edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary"><i class='bx bx-edit'></i></a>
                                    <a href="?delete=<?php echo $post['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu blog yazısını silmek istediğinizden emin misiniz?');"><i class='bx bx-trash'></i></a>
                                </td>
                                <?php if ($openai_key): ?>
                                <td>
                                    <button class="btn btn-sm btn-outline-success btn-humanize"
                                        data-id="<?php echo $post['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($post['baslik'], ENT_QUOTES); ?>"
                                        title="AI ile insanlaştır (Google AI dedektörünü geç)">
                                        <i class='bx bx-user-voice'></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">Henüz blog yazısı eklenmemiş.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- İnsanlaştırma Modal -->
<div class="modal fade" id="humanizeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bx bx-user-voice me-2"></i>AI İnsanlaştırma</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="humanize-info">
                    <div class="alert alert-info mb-3">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Ne yapılacak?</strong><br>
                        Seçilen blog yazısının içeriği, Google'ın AI içerik dedektörlerini geçecek şekilde
                        doğal, insansı bir dile dönüştürülecek. Anlam ve SEO anahtar kelimeleri korunacak.
                    </div>
                    <p><strong>Blog:</strong> <span id="humanize-title" class="text-primary"></span></p>
                    <p class="text-muted small mb-0">
                        <i class="bx bx-time me-1"></i> Bu işlem 30–90 saniye sürebilir. Lütfen bekleyin.
                    </p>
                </div>
                <div id="humanize-progress" style="display:none;">
                    <div class="text-center py-3">
                        <div class="spinner-border text-success mb-3" style="width:3rem;height:3rem;"></div>
                        <p class="fw-semibold">AI içerik yeniden yazılıyor...</p>
                        <p class="text-muted small">İçerik insanlaştırılıyor, lütfen sayfayı kapatmayın.</p>
                    </div>
                </div>
                <div id="humanize-done" style="display:none;">
                    <div class="alert alert-success">
                        <i class="bx bx-check-circle me-2"></i>
                        <strong>Başarıyla insanlaştırıldı!</strong>
                    </div>
                    <p class="text-muted small"><strong>Önizleme:</strong></p>
                    <div id="humanize-preview" class="border rounded p-2 bg-light small text-muted"></div>
                    <p class="mt-2 small"><i class="bx bx-calculator me-1"></i>Yeni kelime sayısı: <strong id="humanize-word-count"></strong></p>
                </div>
                <div id="humanize-error" style="display:none;">
                    <div class="alert alert-danger">
                        <i class="bx bx-error me-2"></i>
                        <span id="humanize-error-msg"></span>
                    </div>
                </div>
                <input type="hidden" id="humanize-post-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-success" id="btn-start-humanize">
                    <i class="bx bx-play me-1"></i> İnsanlaştırmayı Başlat
                </button>
                <a href="#" class="btn btn-primary d-none" id="btn-edit-after-humanize" target="_blank">
                    <i class="bx bx-edit me-1"></i> Yazıyı Düzenle
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('.btn-humanize').on('click', function() {
        var id    = $(this).data('id');
        var title = $(this).data('title');

        $('#humanize-post-id').val(id);
        $('#humanize-title').text(title);
        $('#humanize-info').show();
        $('#humanize-progress').hide();
        $('#humanize-done').hide();
        $('#humanize-error').hide();
        $('#btn-start-humanize').show().prop('disabled', false).html('<i class="bx bx-play me-1"></i> İnsanlaştırmayı Başlat');
        $('#btn-edit-after-humanize').addClass('d-none').attr('href', 'blog_edit.php?id=' + id);

        $('#humanizeModal').modal('show');
    });

    $('#btn-start-humanize').on('click', function() {
        var postId = $('#humanize-post-id').val();
        if (!postId) return;

        $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> İşleniyor...');
        $('#humanize-info').hide();
        $('#humanize-progress').show();
        $('#humanize-done').hide();
        $('#humanize-error').hide();

        $.ajax({
            url: 'ajax/humanize_blog.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ post_id: parseInt(postId) }),
            dataType: 'json',
            timeout: 180000,
            success: function(data) {
                $('#humanize-progress').hide();
                if (data.success) {
                    $('#humanize-preview').text(data.preview || '');
                    $('#humanize-word-count').text(data.new_word_count + ' kelime');
                    $('#humanize-done').show();
                    $('#btn-start-humanize').hide();
                    $('#btn-edit-after-humanize').removeClass('d-none');
                } else {
                    $('#humanize-error-msg').text(data.message || 'Bilinmeyen hata.');
                    $('#humanize-error').show();
                    $('#btn-start-humanize').show().prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> Tekrar Dene');
                }
            },
            error: function(xhr, status) {
                $('#humanize-progress').hide();
                var msg = status === 'timeout' ? 'İstek zaman aşımına uğradı (180sn). Yazı çok uzun olabilir.' : (xhr.responseText || 'Sunucu hatası.');
                $('#humanize-error-msg').text(msg);
                $('#humanize-error').show();
                $('#btn-start-humanize').show().prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> Tekrar Dene');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 