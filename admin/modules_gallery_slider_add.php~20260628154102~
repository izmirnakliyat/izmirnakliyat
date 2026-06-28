<?php
require_once 'includes/header.php';
require_once '../config/db.php';

$success = '';
$error = '';

// Galerileri getir
$galleries = $conn->query("SELECT id, title FROM gallery ORDER BY title ASC");

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_adi = trim($_POST['blok_adi']);
    $kacli = (int)$_POST['kacli'];
    $resimler = isset($_POST['resimler']) ? $_POST['resimler'] : '';
    if (empty($blok_adi)) {
        $error = "Blok adı boş olamaz.";
    } elseif ($kacli <= 0) {
        $error = "Kaçlı gösterim sayısı 0'dan büyük olmalıdır.";
    } elseif (empty($resimler)) {
        $error = "Lütfen en az bir resim yükleyin.";
    } else {
        $stmt = $conn->prepare("INSERT INTO gallery_slider_blocks (blok_adi, kacli, resimler, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('sis', $blok_adi, $kacli, $resimler);
        if ($stmt->execute()) {
            $success = "Galeri blok başarıyla eklendi. Kısa Kod: <code>[blok:galeri_slider id=" . $conn->insert_id . "]</code>";
        } else {
            $error = "Galeri blok eklenirken bir hata oluştu: " . $conn->error;
        }
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Yeni Galeri Blok Ekle</h5>
        <a href="modules_gallery_slider.php" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Geri Dön
        </a>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" class="needs-validation" novalidate enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Blok Adı</label>
                    <input type="text" name="blok_adi" class="form-control" required>
                    <div class="invalid-feedback">Blok adı gereklidir.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kaçlı Gösterim</label>
                    <input type="number" name="kacli" class="form-control" value="3" min="1" max="6" required>
                    <div class="invalid-feedback">1-6 arası bir sayı girin.</div>
                </div>
            </div>
            <div class="col-md-12 mt-3">
                <label class="form-label">Resimler</label>
                <input type="file" id="gallery-upload" class="form-control" multiple accept="image/*">
                <div class="row g-2 mt-2" id="gallery-preview"></div>
                <input type="hidden" name="resimler" id="selected-images">
                <div class="invalid-feedback d-block" id="images-error" style="display:none;">Lütfen en az bir resim yükleyin.</div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form doğrulama
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Modern çoklu resim yükleme ve önizleme
$(document).ready(function() {
    let uploadedImages = [];
    $('#gallery-upload').on('change', function(e) {
        let files = e.target.files;
        if (!files.length) return;
        let formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }
        $.ajax({
            url: 'upload_gallery_image.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#gallery-preview').html('<div class="col-12 text-center"><i class="bx bx-loader-alt bx-spin"></i> Yükleniyor...</div>');
            },
            success: function(data) {
                if (data.success) {
                    data.files.forEach(function(img) {
                        uploadedImages.push(img);
                    });
                    renderPreview();
                    $('#images-error').hide();
                } else {
                    alert(data.error || 'Yükleme hatası!');
                }
            },
            error: function(xhr, status, error) {
                alert('Yükleme sırasında bir hata oluştu: ' + error);
                $('#gallery-preview').html('');
            }
        });
    });
    function renderPreview() {
        let html = '';
        uploadedImages.forEach(function(img, idx) {
            html += '<div class="col-3 col-md-2 position-relative">'+
                '<img src="../uploads/gallery/' + img + '" class="img-fluid border rounded mb-1">'+
                '<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-img" data-idx="'+idx+'" style="z-index:2;"><i class="bx bx-x"></i></button>'+
                '</div>';
        });
        $('#gallery-preview').html(html);
        $('#selected-images').val(JSON.stringify(uploadedImages));
    }
    $(document).on('click', '.remove-img', function() {
        let idx = $(this).data('idx');
        uploadedImages.splice(idx, 1);
        renderPreview();
    });
    // Form submit kontrolü
    $('form').on('submit', function(e) {
        if (!uploadedImages.length) {
            $('#images-error').show();
            e.preventDefault();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 