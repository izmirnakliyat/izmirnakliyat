<?php
$page_title = 'Hakkımızda Alanı';
require_once 'includes/header.php';

// Tekil about kaydını çek
$about = $conn->query("SELECT * FROM about ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = $_POST['baslik'];
    $aciklama = $_POST['aciklama'];
    $button1_text = $_POST['button1_text'];
    $button1_link = $_POST['button1_link'];
    $button2_text = $_POST['button2_text'];
    $button2_link = $_POST['button2_link'];
    $video_url = $_POST['video_url'];
    $video_cover = $about['video_cover'] ?? null;

    // Video kapak yükleme
    if (isset($_FILES['video_cover']) && $_FILES['video_cover']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/about/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file = $_FILES['video_cover'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        if (!in_array($file_ext, $allowed_exts)) {
            $error = "Sadece JPG, JPEG, PNG, WebP ve AVIF formatları desteklenmektedir.";
        } else {
            // Eski kapak sil
            if ($about && $about['video_cover']) {
                $old_cover_path = $upload_dir . $about['video_cover'];
                if (file_exists($old_cover_path)) {
                    unlink($old_cover_path);
                }
            }
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $video_cover = $new_filename;
            } else {
                $error = "Kapak resmi yüklenirken bir hata oluştu.";
            }
        }
    }

    if (!isset($error)) {
        if ($about) {
            $stmt = $conn->prepare("UPDATE about SET baslik=?, aciklama=?, button1_text=?, button1_link=?, button2_text=?, button2_link=?, video_url=?, video_cover=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $baslik, $aciklama, $button1_text, $button1_link, $button2_text, $button2_link, $video_url, $video_cover, $about['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO about (baslik, aciklama, button1_text, button1_link, button2_text, button2_link, video_url, video_cover) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $baslik, $aciklama, $button1_text, $button1_link, $button2_text, $button2_link, $video_url, $video_cover);
        }
        if ($stmt->execute()) {
            echo "<script>window.location.href = 'about_edit.php';</script>";
            exit;
        } else {
            $error = "Kayıt kaydedilirken bir hata oluştu.";
        }
    }
}
?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Hakkımızda Alanı</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="baslik" class="form-label">Başlık</label>
                        <input type="text" class="form-control" id="baslik" name="baslik" value="<?php echo htmlspecialchars($about['baslik'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="aciklama" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="aciklama" name="aciklama" rows="4" required><?php echo htmlspecialchars($about['aciklama'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="button1_text" class="form-label">1. Buton Başlık</label>
                        <input type="text" class="form-control" id="button1_text" name="button1_text" value="<?php echo htmlspecialchars($about['button1_text'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="button1_link" class="form-label">1. Buton Link</label>
                        <input type="text" class="form-control" id="button1_link" name="button1_link" value="<?php echo htmlspecialchars($about['button1_link'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="button2_text" class="form-label">2. Buton Başlık</label>
                        <input type="text" class="form-control" id="button2_text" name="button2_text" value="<?php echo htmlspecialchars($about['button2_text'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="button2_link" class="form-label">2. Buton Link</label>
                        <input type="text" class="form-control" id="button2_link" name="button2_link" value="<?php echo htmlspecialchars($about['button2_link'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="video_url" class="form-label">Video URL</label>
                        <input type="text" class="form-control" id="video_url" name="video_url" value="<?php echo htmlspecialchars($about['video_url'] ?? ''); ?>" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label for="video_cover" class="form-label">Video Kapak Resmi</label>
                        <?php if ($about && ($about['video_cover'] ?? null)): ?>
                            <div class="mb-2">
                                <img src="../uploads/about/<?php echo htmlspecialchars($about['video_cover']); ?>" alt="Mevcut kapak" style="max-width: 200px; height: auto; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="video_cover" name="video_cover" accept=".jpg,.jpeg,.png,.webp,.avif">
                        <small class="form-text text-muted">Desteklenen formatlar: JPG, JPEG, PNG, WebP, AVIF</small>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 