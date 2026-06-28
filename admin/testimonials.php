<?php
$page_title = 'Müşteri Yorumları Yönetimi';
require_once 'includes/header.php';

$success = '';
$error = '';

// Yorum silme işlemi
if (isset($_GET['delete'])) {
    $testimonial_id = (int)$_GET['delete'];
    
    // Önce yorumun resmini sil
    $stmt = $conn->prepare("SELECT image FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $testimonial_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($testimonial = $result->fetch_assoc()) {
        if ($testimonial['image']) {
            $image_path = "../uploads/testimonials/" . $testimonial['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    
    // Sonra yorumu sil
    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $testimonial_id);
    if ($stmt->execute()) {
        $success = "Müşteri yorumu başarıyla silindi.";
    } else {
        $error = "Yorum silinirken bir hata oluştu.";
    }
}

// Yorum durumunu güncelleme
if (isset($_POST['toggle_status'])) {
    $testimonial_id = (int)$_POST['testimonial_id'];
    $stmt = $conn->prepare("UPDATE testimonials SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $testimonial_id);
    if ($stmt->execute()) {
        $success = "Yorum durumu güncellendi.";
    } else {
        $error = "Durum güncellenirken bir hata oluştu.";
    }
}

// Yorumları listele
$testimonials = $conn->query("SELECT * FROM testimonials ORDER BY order_number ASC, id DESC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Müşteri Yorumları</h5>
        <a href="testimonial_edit.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni Yorum
        </a>
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
                        <th>Sıra</th>
                        <th>Fotoğraf</th>
                        <th>Ad Soyad</th>
                        <th>Pozisyon</th>
                        <th>Şirket</th>
                        <th>Yorum</th>
                        <th>Puan</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($testimonials && $testimonials->num_rows > 0): ?>
                        <?php while ($testimonial = $testimonials->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $testimonial['order_number']; ?></td>
                                <td>
                                    <?php if ($testimonial['image']): ?>
                                        <img src="../uploads/testimonials/<?php echo $testimonial['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($testimonial['name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                            <i class='bx bx-user'></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($testimonial['name']); ?></td>
                                <td><?php echo htmlspecialchars($testimonial['position']); ?></td>
                                <td><?php echo htmlspecialchars($testimonial['company']); ?></td>
                                <td>
                                    <?php 
                                    $content = strip_tags($testimonial['content']);
                                    echo strlen($content) > 50 ? substr($content, 0, 50) . '...' : $content;
                                    ?>
                                </td>
                                <td>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-sharp fa-solid fa-star <?php echo $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $testimonial['status'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $testimonial['status'] ? 'Aktif' : 'Pasif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="testimonial_edit.php?id=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="?delete=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu yorumu silmek istediğinizden emin misiniz?');">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">Henüz müşteri yorumu eklenmemiş.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 