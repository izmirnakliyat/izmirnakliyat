<?php
$page_title = "Fotoğraf Galerisi";
require_once 'includes/header.php';

// Yetki kontrolü
if (!hasPermission('gallery_view')) {
    echo "<div class='alert alert-danger'>Bu sayfayı görüntüleme yetkiniz bulunmamaktadır.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Veritabanı tablosunu oluştur (yoksa)
$tableCheck = $conn->query("SHOW TABLES LIKE 'gallery'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE gallery (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NULL,
        image VARCHAR(255) NOT NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        order_number INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTable);
}

// Resim silme işlemi
if (isset($_GET['delete']) && hasPermission('gallery_delete')) {
    $id = (int)$_GET['delete'];
    // Önce resmi bul
    $stmt = $conn->prepare("SELECT image FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Dosyayı sil
        $image_path = "../uploads/gallery/" . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    // Kaydı sil
    $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Resim başarıyla silindi.</div>";
    } else {
        echo "<div class='alert alert-danger'>Resim silinirken bir hata oluştu: " . $conn->error . "</div>";
    }
}

// Durum değiştirme işlemi
if (isset($_POST['toggle_status'])) {
    $id = (int)$_POST['gallery_id'];
    $stmt = $conn->prepare("UPDATE gallery SET status = 1 - status WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Durum başarıyla güncellendi.</div>";
    } else {
        echo "<div class='alert alert-danger'>Durum güncellenirken bir hata oluştu: " . $conn->error . "</div>";
    }
}

// Sıralama değiştirme işlemi
if (isset($_POST['change_order'])) {
    $id = (int)$_POST['gallery_id'];
    $order_number = (int)$_POST['order_number'];
    
    $stmt = $conn->prepare("UPDATE gallery SET order_number = ? WHERE id = ?");
    $stmt->bind_param("ii", $order_number, $id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Sıralama başarıyla güncellendi.</div>";
    } else {
        echo "<div class='alert alert-danger'>Sıralama güncellenirken bir hata oluştu: " . $conn->error . "</div>";
    }
}

// Galeri listesini çek
$query = "SELECT * FROM gallery ORDER BY order_number ASC, id DESC";
$result = $conn->query($query);
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Fotoğraf Galerisi</h6>
        <div>
            <?php if (hasPermission('gallery_add')): ?>
            <a href="gallery_upload.php" class="btn btn-sm btn-primary">
                <i class="bx bx-upload"></i> Resim Yükle
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Sıra</th>
                        <th>Görsel</th>
                        <th>Başlık</th>
                        <th>Durum</th>
                        <th>Eklenme Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if (hasPermission('gallery_edit')): ?>
                                    <form method="post">
                                        <input type="hidden" name="gallery_id" value="<?php echo $row['id']; ?>">
                                        <input type="number" name="order_number" value="<?php echo $row['order_number']; ?>" class="form-control form-control-sm" style="width: 60px;">
                                        <button type="submit" name="change_order" class="btn btn-sm btn-info mt-1">
                                            <i class="bx bx-save"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <?php echo $row['order_number']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <img src="../uploads/gallery/<?php echo htmlspecialchars($row['image']); ?>" 
                                         alt="Galeri Resmi" width="80" height="50" 
                                         style="object-fit: cover; border-radius: 6px;">
                                </td>
                                <td><?php echo htmlspecialchars($row['title'] ?? '-'); ?></td>
                                <td>
                                    <?php if (hasPermission('gallery_edit')): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="gallery_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $row['status'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo $row['status'] ? 'Aktif' : 'Pasif'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-<?php echo $row['status'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $row['status'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php if (hasPermission('gallery_edit')): ?>
                                    <a href="gallery_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (hasPermission('gallery_delete')): ?>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu resmi silmek istediğinize emin misiniz?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Henüz galeri resmi eklenmemiş.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Turkish.json"
        },
        "order": [[ 0, "asc" ]] // Sıralama numarasına göre sırala
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 