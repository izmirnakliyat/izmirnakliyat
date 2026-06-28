<?php
$page_title = 'Slayt Yönetimi';
require_once 'includes/header.php';
require_once '../includes/functions.php';

// Slayt silme işlemi
if (isset($_GET['delete'])) {
    $slide_id = (int)$_GET['delete'];
    
    // Önce slaytın resimlerini sil
    $stmt = $conn->prepare("SELECT image, image2 FROM slides WHERE id = ?");
    $stmt->bind_param("i", $slide_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($slide = $result->fetch_assoc()) {
        // 1. resmi sil
        if ($slide['image']) {
            $image_path = "../uploads/slides/" . $slide['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            mynak_slide_delete_derivatives('../uploads/slides/', (string) $slide['image']);
        }
        
        // 2. resmi sil
        if ($slide['image2']) {
            $image2_path = "../uploads/slides/" . $slide['image2'];
            if (file_exists($image2_path)) {
                unlink($image2_path);
            }
            mynak_slide_delete_derivatives('../uploads/slides/', (string) $slide['image2']);
        }
    }
    
    // Sonra slaytı sil
    $stmt = $conn->prepare("DELETE FROM slides WHERE id = ?");
    $stmt->bind_param("i", $slide_id);
    if ($stmt->execute()) {
        $success = "Slayt başarıyla silindi.";
    } else {
        $error = "Slayt silinirken bir hata oluştu.";
    }
}

// Slayt durumunu güncelleme
if (isset($_POST['toggle_status'])) {
    $slide_id = (int)$_POST['slide_id'];
    $stmt = $conn->prepare("UPDATE slides SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $slide_id);
    if ($stmt->execute()) {
        $success = "Slayt durumu güncellendi.";
    } else {
        $error = "Durum güncellenirken bir hata oluştu.";
    }
}

// Slaytları listele
$slides = $conn->query("SELECT * FROM slides ORDER BY order_number ASC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Slaytlar</h5>
        <a href="slide_edit.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni Slayt
        </a>
    </div>
    <div class="card-body">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Sıra</th>
                        <th>1. Görsel (Ana)</th>
                        <th>2. Görsel (Sağdan)</th>
                        <th>Başlık</th>
                        <th>Alt Başlık</th>
                        <th>Butonlar</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($slides->num_rows > 0): ?>
                        <?php while ($slide = $slides->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $slide['order_number']; ?></td>
                                <td>
                                    <?php if ($slide['image']): ?>
                                        <?php
                                        $image_path = "../uploads/slides/" . $slide['image'];
                                        $image_ext = strtolower(pathinfo($slide['image'], PATHINFO_EXTENSION));
                                        $webp_path = str_replace(['.jpg', '.jpeg', '.png', '.avif'], '.webp', $image_path);
                                        $avif_path = str_replace(['.jpg', '.jpeg', '.png', '.webp'], '.avif', $image_path);
                                        ?>
                                        <picture>
                                            <?php if (file_exists($avif_path)): ?>
                                                <source srcset="../uploads/slides/<?php echo str_replace(['.jpg', '.jpeg', '.png', '.webp'], '.avif', $slide['image']); ?>" type="image/avif">
                                            <?php endif; ?>
                                            <?php if (file_exists($webp_path)): ?>
                                                <source srcset="../uploads/slides/<?php echo str_replace(['.jpg', '.jpeg', '.png', '.avif'], '.webp', $slide['image']); ?>" type="image/webp">
                                            <?php endif; ?>
                                            <img src="../uploads/slides/<?php echo $slide['image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($slide['title']); ?>"
                                                 style="width: 80px; height: 50px; object-fit: cover;">
                                        </picture>
                                    <?php else: ?>
                                        <span class="text-muted">Yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($slide['image2']): ?>
                                        <?php
                                        $image2_path = "../uploads/slides/" . $slide['image2'];
                                        $image2_ext = strtolower(pathinfo($slide['image2'], PATHINFO_EXTENSION));
                                        $webp2_path = str_replace(['.jpg', '.jpeg', '.png', '.avif'], '.webp', $image2_path);
                                        $avif2_path = str_replace(['.jpg', '.jpeg', '.png', '.webp'], '.avif', $image2_path);
                                        ?>
                                        <picture>
                                            <?php if (file_exists($avif2_path)): ?>
                                                <source srcset="../uploads/slides/<?php echo str_replace(['.jpg', '.jpeg', '.png', '.webp'], '.avif', $slide['image2']); ?>" type="image/avif">
                                            <?php endif; ?>
                                            <?php if (file_exists($webp2_path)): ?>
                                                <source srcset="../uploads/slides/<?php echo str_replace(['.jpg', '.jpeg', '.png', '.avif'], '.webp', $slide['image2']); ?>" type="image/webp">
                                            <?php endif; ?>
                                            <img src="../uploads/slides/<?php echo $slide['image2']; ?>" 
                                                 alt="<?php echo htmlspecialchars($slide['title']); ?> - 2. görsel"
                                                 style="width: 80px; height: 50px; object-fit: cover;">
                                        </picture>
                                    <?php else: ?>
                                        <span class="text-muted">Yok</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($slide['title']); ?></td>
                                <td><?php echo htmlspecialchars($slide['subtitle']); ?></td>
                                <td>
                                    <?php if ($slide['button1_text']): ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($slide['button1_text']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($slide['button2_text']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($slide['button2_text']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $slide['status'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $slide['status'] ? 'Aktif' : 'Pasif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="slide_edit.php?id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="?delete=<?php echo $slide['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu slaytı silmek istediğinizden emin misiniz?');">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Henüz slayt eklenmemiş.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 