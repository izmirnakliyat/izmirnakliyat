<?php
require_once 'includes/header.php';

// Dizinleri kontrol et ve oluştur
$target_dir = "../uploads/team/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
}

// Düzenleme mi, yeni mi?
$member = [
    'id' => '',
    'ad' => '',
    'unvan' => '',
    'aciklama' => '',
    'foto' => '',
    'linkedin' => '',
    'twitter' => '',
    'instagram' => '',
    'email' => '',
    'durum' => 1,
    'order_number' => 0
];

$page_title = "Yeni Eğitmen Ekle";
$success = '';
$error = '';

// Düzenleme için verileri getir
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM team_members WHERE id = $id");
    
    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        $page_title = "Eğitmen Düzenle: " . $member['ad'];
    } else {
        $error = "Eğitmen bulunamadı!";
    }
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member['ad'] = trim($_POST['ad']);
    $member['unvan'] = trim($_POST['unvan']);
    $member['aciklama'] = trim($_POST['aciklama']);
    $member['linkedin'] = trim($_POST['linkedin']);
    $member['twitter'] = trim($_POST['twitter']);
    $member['instagram'] = trim($_POST['instagram']);
    $member['email'] = trim($_POST['email']);
    $member['durum'] = isset($_POST['durum']) ? 1 : 0;
    $member['order_number'] = (int)$_POST['order_number'];
    
    // Validasyon
    if (empty($member['ad'])) {
        $error = "Eğitmen adı boş olamaz!";
    } else if (empty($member['unvan'])) {
        $error = "Ünvan boş olamaz!";
    } else {
        // Resim yükleme
        $upload_image = false;
        $new_image_name = '';
        
        if (isset($_FILES['foto']) && $_FILES['foto']['name'] != '') {
            $file_name = $_FILES['foto']['name'];
            $file_size = $_FILES['foto']['size'];
            $file_tmp = $_FILES['foto']['tmp_name'];
            
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $extensions = array("jpeg", "jpg", "png", "webp");
            
            if (in_array($file_ext, $extensions)) {
                if ($file_size < 5242880) { // 5MB
                    $new_image_name = uniqid() . '.' . $file_ext;
                    $upload_image = true;
                } else {
                    $error = "Dosya boyutu 5MB'dan küçük olmalıdır.";
                }
            } else {
                $error = "Sadece JPEG, JPG, PNG ve WEBP dosyaları yükleyebilirsiniz.";
            }
        }
        
        if (empty($error)) {
            try {
                if (!empty($member['id'])) {
                    // Güncelleme
                    $sql = "UPDATE team_members SET 
                            ad = ?, 
                            unvan = ?, 
                            aciklama = ?, 
                            linkedin = ?, 
                            twitter = ?, 
                            instagram = ?, 
                            email = ?, 
                            durum = ?, 
                            order_number = ?";
                    
                    $params = [$member['ad'], $member['unvan'], $member['aciklama'], 
                              $member['linkedin'], $member['twitter'], $member['instagram'], 
                              $member['email'], $member['durum'], $member['order_number']];
                    
                    $types = "sssssssis";
                    
                    if ($upload_image) {
                        $sql .= ", foto = ?";
                        $params[] = $new_image_name;
                        $types .= "s";
                        
                        // Eski resmi sil
                        if (!empty($member['foto'])) {
                            $old_file = $target_dir . $member['foto'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $member['id'];
                    $types .= "i";
                    
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Hazırlama hatası: " . $conn->error);
                    }
                    
                    $stmt->bind_param($types, ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Yürütme hatası: " . $stmt->error);
                    }
                    
                    $success = "Eğitmen başarıyla güncellendi.";
                } else {
                    // Yeni ekleme
                    $sql = "INSERT INTO team_members (ad, unvan, aciklama, foto, linkedin, twitter, instagram, email, durum, order_number) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $params = [$member['ad'], $member['unvan'], $member['aciklama'], 
                              ($upload_image ? $new_image_name : ''), 
                              $member['linkedin'], $member['twitter'], $member['instagram'], 
                              $member['email'], $member['durum'], $member['order_number']];
                    
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Hazırlama hatası: " . $conn->error);
                    }
                    
                    $stmt->bind_param("ssssssssii", ...$params);
                    if (!$stmt->execute()) {
                        throw new Exception("Yürütme hatası: " . $stmt->error);
                    }
                    
                    $member['id'] = $stmt->insert_id;
                    $success = "Eğitmen başarıyla eklendi.";
                }
                
                // Resmi yükle
                if ($upload_image) {
                    if (!move_uploaded_file($file_tmp, $target_dir . $new_image_name)) {
                        throw new Exception("Dosya yüklenirken bir hata oluştu.");
                    }
                    
                    $member['foto'] = $new_image_name;
                }
                
                // Başarılı ise 2 saniye bekleyip listeye yönlendir
                if (!empty($success)) {
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "team_members.php";
                        }, 2000);
                    </script>';
                }
            } catch (Exception $e) {
                $error = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo $page_title; ?></h5>
        <a href="team_members.php" class="btn btn-secondary">
            <i class='bx bx-arrow-back'></i> Listeye Dön
        </a>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="teamForm">
            <div class="row">
                <div class="col-md-8">
                    <!-- Temel Bilgiler -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Temel Bilgiler</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ad" class="form-label">Adı Soyadı <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="ad" name="ad" value="<?php echo htmlspecialchars($member['ad']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="unvan" class="form-label">Ünvan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="unvan" name="unvan" value="<?php echo htmlspecialchars($member['unvan']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="aciklama" class="form-label">Kısa Açıklama</label>
                                <textarea class="form-control" id="aciklama" name="aciklama" rows="4"><?php echo htmlspecialchars($member['aciklama']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sosyal Medya Bilgileri -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Sosyal Medya Bilgileri</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="linkedin" class="form-label">LinkedIn Profili</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bxl-linkedin'></i></span>
                                    <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/username" value="<?php echo htmlspecialchars($member['linkedin']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="twitter" class="form-label">X/Twitter Profili</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bxl-twitter'></i></span>
                                    <input type="url" class="form-control" id="twitter" name="twitter" placeholder="https://x.com/username" value="<?php echo htmlspecialchars($member['twitter']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="instagram" class="form-label">Instagram Profili</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bxl-instagram'></i></span>
                                    <input type="url" class="form-control" id="instagram" name="instagram" placeholder="https://instagram.com/username" value="<?php echo htmlspecialchars($member['instagram']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bx-envelope'></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="ornek@mail.com" value="<?php echo htmlspecialchars($member['email']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Profil Fotoğrafı -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Profil Fotoğrafı</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 text-center">
                                <div class="profile-image-preview mb-3">
                                    <?php if (!empty($member['foto'])): ?>
                                        <img src="../uploads/team/<?php echo htmlspecialchars($member['foto']); ?>" alt="<?php echo htmlspecialchars($member['ad']); ?>" class="img-thumbnail" style="max-width: 100%; height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="img-placeholder">
                                            <i class='bx bx-user-circle'></i>
                                            <span>Fotoğraf Yok</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="foto" class="form-label">Profil Fotoğrafı Yükle</label>
                                    <input class="form-control" type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
                                    <div class="form-text">Önerilen boyut: Kare formatta, en az 300x300 piksel</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ayarlar -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Ayarlar</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="durum" name="durum" <?php echo $member['durum'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="durum">Aktif</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="order_number" class="form-label">Sıralama</label>
                                <input type="number" class="form-control" id="order_number" name="order_number" value="<?php echo $member['order_number']; ?>" min="0">
                                <div class="form-text">Küçük değerler daha önce görüntülenir.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kaydet Butonu -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-save'></i> Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.img-placeholder {
    width: 100%;
    height: 200px;
    background-color: #f8f9fa;
    border: 1px dashed #ccc;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #888;
}

.img-placeholder i {
    font-size: 48px;
    margin-bottom: 10px;
}
</style>

<?php require_once 'includes/footer.php'; ?> 