<?php
$page_title = "Resim Yükleme";
require_once 'includes/header.php';

// Yetki kontrolü
if (!hasPermission('gallery_add')) {
    echo "<div class='alert alert-danger'>Bu işlemi gerçekleştirme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Klasör kontrolü ve oluşturma
$upload_dir = "../uploads/gallery/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$success_count = 0;
$error_count = 0;
$error_messages = [];

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Durum ayarları
    $status = isset($_POST['status']) ? 1 : 0;
    $order_number = isset($_POST['order_number']) ? (int)$_POST['order_number'] : 0;
    
    // Çoklu resim yükleme
    if (!empty($_FILES['images']['name'][0])) {
        $file_count = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['images']['error'][$i] === 0) {
                $file_name = $_FILES['images']['name'][$i];
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_type = $_FILES['images']['type'][$i];
                
                // Sadece görüntü dosyalarını kabul et
                if (strpos($file_type, 'image/') !== 0) {
                    $error_count++;
                    $error_messages[] = "Dosya '$file_name' bir görüntü dosyası değil.";
                    continue;
                }
                
                // Dosya adını benzersiz yap
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = time() . '_' . uniqid() . '_' . $i . '.' . $file_ext;
                $target_file = $upload_dir . $new_file_name;
                
                // Dosyayı yükle
                if (move_uploaded_file($file_tmp, $target_file)) {
                    // Veritabanına ekle
                    $stmt = $conn->prepare("INSERT INTO gallery (title, image, status, order_number) VALUES (?, ?, ?, ?)");
                    $auto_title = pathinfo($file_name, PATHINFO_FILENAME); // Dosya adını otomatik başlık olarak kullan
                    $stmt->bind_param("ssii", $auto_title, $new_file_name, $status, $order_number);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $error_messages[] = "Dosya '$file_name' veritabanına eklenirken hata: " . $conn->error;
                    }
                } else {
                    $error_count++;
                    $error_messages[] = "Dosya '$file_name' yüklenirken hata oluştu.";
                }
            } else if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $error_count++;
                $error_messages[] = "Dosya yükleme hatası (kod: " . $_FILES['images']['error'][$i] . ")";
            }
        }
    }
    
    // Sonuç mesajları
    if ($success_count > 0) {
        echo "<div class='alert alert-success'>$success_count resim başarıyla yüklendi.</div>";
        echo "<script>setTimeout(function() { window.location.href = 'gallery.php'; }, 2000);</script>";
    }
    
    if ($error_count > 0) {
        echo "<div class='alert alert-danger'>$error_count resim yüklenirken hata oluştu.</div>";
        if (!empty($error_messages)) {
            echo "<div class='alert alert-warning'>";
            echo "<ul>";
            foreach ($error_messages as $msg) {
                echo "<li>$msg</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Resim Yükleme</h6>
        <a href="gallery.php" class="btn btn-sm btn-secondary">
            <i class="bx bx-arrow-back"></i> Galeriye Dön
        </a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <p><strong>Resim yükleme hakkında:</strong></p>
                    <ul>
                        <li>Bir seferde birden fazla resim yükleyebilirsiniz.</li>
                        <li>Resim seçme kutusundan CTRL tuşuna basarak çoklu seçim yapabilirsiniz.</li>
                        <li>Yalnızca JPG, PNG, GIF ve WEBP formatları desteklenmektedir.</li>
                        <li>Resimlerin başlığı, dosya adından otomatik olarak alınacaktır.</li>
                        <li>Yüklediğiniz resimlerin satır sayısı, galeri ayarlarından belirlenebilir.</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="images" class="form-label">Resimler</label>
                        <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple required>
                        <small class="text-muted">Birden fazla resim seçmek için CTRL tuşuna basılı tutarak seçim yapabilirsiniz.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_number" class="form-label">Sıralama</label>
                                <input type="number" class="form-control" id="order_number" name="order_number" value="0" min="0">
                                <small class="text-muted">Düşük numaralar önce gösterilir</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 mt-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" checked>
                                    <label class="form-check-label" for="status">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-upload"></i> Resimleri Yükle
                </button>
            </div>
        </form>
        
        <div id="preview-container" class="mt-4 d-none">
            <h5>Önizleme</h5>
            <div id="image-preview" class="d-flex flex-wrap gap-2"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Resim önizleme
        const fileInput = document.getElementById('images');
        const previewContainer = document.getElementById('preview-container');
        const imagePreview = document.getElementById('image-preview');
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                previewContainer.classList.remove('d-none');
                imagePreview.innerHTML = '';
                
                for (let i = 0; i < this.files.length; i++) {
                    if (i > 9) {
                        // 10'dan fazla resim varsa kalan sayıyı göster
                        const remainingCount = this.files.length - 10;
                        const countElement = document.createElement('div');
                        countElement.className = 'preview-count';
                        countElement.textContent = `+${remainingCount} daha`;
                        countElement.style.padding = '8px 16px';
                        countElement.style.background = '#f0f0f0';
                        countElement.style.borderRadius = '4px';
                        countElement.style.marginTop = '10px';
                        imagePreview.appendChild(countElement);
                        break;
                    }
                    
                    const file = this.files[i];
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const imgContainer = document.createElement('div');
                            imgContainer.style.width = '100px';
                            imgContainer.style.height = '100px';
                            imgContainer.style.overflow = 'hidden';
                            imgContainer.style.borderRadius = '4px';
                            imgContainer.style.border = '1px solid #ddd';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'cover';
                            img.alt = file.name;
                            
                            imgContainer.appendChild(img);
                            imagePreview.appendChild(imgContainer);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                }
                
                console.log(`${this.files.length} dosya seçildi`);
            } else {
                previewContainer.classList.add('d-none');
                imagePreview.innerHTML = '';
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 