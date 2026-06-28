<?php
$page_title = 'Form Oluşturucu';
require_once 'includes/header.php';
require_once '../config/db.php';

// Form tablosu oluştur
$create_forms_table = "CREATE TABLE IF NOT EXISTS `forms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text,
    `fields` longtext NOT NULL,
    `settings` text,
    `email_notifications` text,
    `success_message` text,
    `redirect_url` varchar(255),
    `status` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($create_forms_table);

// Silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM forms WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Form başarıyla silindi.";
    } else {
        $error = "Form silinirken bir hata oluştu: " . $conn->error;
    }
}

// Formları listele
$forms_result = $conn->query("SELECT * FROM forms ORDER BY created_at DESC");
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Form Yönetimi /</span> Form Oluşturucu
    </h4>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Formlarım</h5>
                    <a href="form_builder_edit.php" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Yeni Form Oluştur
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($forms_result && $forms_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Form Adı</th>
                                        <th>Başlık</th>
                                        <th>Oluşturma Tarihi</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($form = $forms_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($form['name']); ?></strong>
                                                <br><small class="text-muted">ID: <?php echo $form['id']; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($form['title']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($form['created_at'])); ?></td>
                                            <td>
                                                <?php if ($form['status']): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Pasif</span>
                                                <?php endif; ?>
                                                
                                                <?php 
                                                $settings = json_decode($form['settings'] ?? '{}', true);
                                                $form_type = $settings['form_type'] ?? 'embed';
                                                $isPopup = (isset($settings['popup_enabled']) && $settings['popup_enabled']) || $form_type === 'popup';
                                                ?>
                                                
                                                <?php if ($isPopup): ?>
                                                    <br><span class="badge bg-warning text-dark mt-1"><i class="bx bx-window-alt"></i> Popup</span>
                                                    <?php if (isset($settings['step_by_step']) && $settings['step_by_step']): ?>
                                                        <span class="badge bg-purple mt-1">Adım Adım</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <br><span class="badge bg-primary mt-1"><i class="bx bx-code-block"></i> Gömülü</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $settings = json_decode($form['settings'] ?? '{}', true);
                                                $isPopup = isset($settings['popup_enabled']) && $settings['popup_enabled'];
                                                ?>
                                                
                                                <div class="btn-group" role="group">
                                                    <a href="form_builder_edit.php?id=<?php echo $form['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($isPopup): ?>
                                                        <a href="../popup_form.php?id=<?php echo $form['id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning" title="Popup Önizle" target="_blank">
                                                            <i class="bx bx-popup"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="form_preview.php?id=<?php echo $form['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="Önizle" target="_blank">
                                                            <i class="bx bx-show"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            onclick="copyShortcode(<?php echo $form['id']; ?>)" title="Kodu Kopyala">
                                                        <i class="bx bx-copy"></i>
                                                    </button>
                                                    <a href="form_submissions.php?form_id=<?php echo $form['id']; ?>" 
                                                       class="btn btn-sm btn-outline-success" title="Başvurular">
                                                        <i class="bx bx-envelope"></i>
                                                    </a>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Bu formu silmek istediğinizden emin misiniz?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $form['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bx bx-file" style="font-size: 4rem; color: #ddd;"></i>
                            <h5 class="mt-3 text-muted">Henüz form oluşturmadınız</h5>
                            <p class="text-muted">İlk formunuzu oluşturmak için yukarıdaki butona tıklayın.</p>
                            <a href="form_builder_edit.php" class="btn btn-primary">
                                <i class="bx bx-plus"></i> İlk Formumu Oluştur
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Shortcode Modal -->
    <div class="modal fade" id="shortcodeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bx bx-code"></i> Form Kısa Kodu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle"></i> Bu formu sayfa içeriklerine, blog yazılarına veya herhangi bir yere gömebilirsiniz.
                    </div>
                    
                    <!-- Kısa Kod -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bx bx-code-block"></i> Sayfa/Blog İçeriğinde Kullanım (Önerilen)</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">Bu kısa kodu sayfa veya blog içeriğine yapıştırın:</p>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" id="shortcodeInput" readonly>
                                <button class="btn btn-primary" type="button" onclick="copyToClipboard('shortcodeInput')">
                                    <i class="bx bx-copy"></i> Kopyala
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PHP Kullanımı -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bx bxl-php"></i> PHP Dosyalarında Kullanım</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">PHP dosyalarınızda direkt form render etmek için:</p>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light font-monospace" id="phpCodeInput" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('phpCodeInput')">
                                    <i class="bx bx-copy"></i> Kopyala
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- iFrame Kullanımı -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bx bx-code-curly"></i> HTML/iFrame ile Kullanım</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2">Harici siteler veya iframe içinde göstermek için:</p>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light font-monospace" id="iframeCodeInput" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('iframeCodeInput')">
                                    <i class="bx bx-copy"></i> Kopyala
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyShortcode(formId) {
    // Kısa kod
    const shortcode = `[form id=${formId}]`;
    document.getElementById('shortcodeInput').value = shortcode;
    
    // PHP kodu
    const phpCode = `<?php echo render_embedded_form(${formId}); ?>`;
    document.getElementById('phpCodeInput').value = phpCode;
    
    // iFrame kodu
    const siteUrl = window.location.origin;
    const iframeCode = `<iframe src="${siteUrl}/form_display.php?id=${formId}&embed=1" width="100%" height="500" frameborder="0"></iframe>`;
    document.getElementById('iframeCodeInput').value = iframeCode;
    
    const modal = new bootstrap.Modal(document.getElementById('shortcodeModal'));
    modal.show();
}

function copyToClipboard(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    input.setSelectionRange(0, 99999); // Mobil için
    
    // Modern API kullan
    if (navigator.clipboard) {
        navigator.clipboard.writeText(input.value);
    } else {
        document.execCommand('copy');
    }
    
    // Toast bildirimi göster
    showToast('Kod kopyalandı!', 'success');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bx bx-check-circle me-2"></i>${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    document.body.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?> 