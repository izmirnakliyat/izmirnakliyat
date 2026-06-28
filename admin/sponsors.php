<?php
$page_title = 'Referans/Sponsor Resimleri Yönetimi';
require_once 'includes/header.php';

$success = '';
$error = '';

// Sponsor silme işlemi
if (isset($_GET['delete'])) {
    $sponsor_id = (int)$_GET['delete'];
    
    // Önce sponsorun resmini sil
    $stmt = $conn->prepare("SELECT image FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($sponsor = $result->fetch_assoc()) {
        if ($sponsor['image']) {
            $image_path = "../uploads/sponsors/" . $sponsor['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    
    // Sonra sponsoru sil
    $stmt = $conn->prepare("DELETE FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $sponsor_id);
    if ($stmt->execute()) {
        $success = "Referans resmi başarıyla silindi.";
    } else {
        $error = "Referans resmi silinirken bir hata oluştu.";
    }
}

// Sponsor durumunu güncelleme
if (isset($_POST['toggle_status'])) {
    $sponsor_id = (int)$_POST['sponsor_id'];
    $stmt = $conn->prepare("UPDATE sponsors SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $sponsor_id);
    if ($stmt->execute()) {
        $success = "Referans durumu güncellendi.";
    } else {
        $error = "Durum güncellenirken bir hata oluştu.";
    }
}

// Sponsorları listele
$sponsors = $conn->query("SELECT * FROM sponsors ORDER BY order_number ASC, id DESC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Referans/Sponsor Resimleri</h5>
        <a href="sponsor_edit.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni Referans
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
                        <th>Resim</th>
                        <th>Ad</th>
                        <th>Link</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sponsors && $sponsors->num_rows > 0): ?>
                        <?php while ($sponsor = $sponsors->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $sponsor['order_number']; ?></td>
                                <td>
                                    <?php if ($sponsor['image']): ?>
                                        <img src="../uploads/sponsors/<?php echo $sponsor['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($sponsor['name']); ?>"
                                             style="max-width: 100px; height: auto;">
                                    <?php else: ?>
                                        <div style="width: 100px; height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                            <i class='bx bx-image'></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($sponsor['name']); ?></td>
                                <td>
                                    <?php if ($sponsor['link']): ?>
                                        <a href="<?php echo htmlspecialchars($sponsor['link']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($sponsor['link']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Link yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="sponsor_id" value="<?php echo $sponsor['id']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $sponsor['status'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $sponsor['status'] ? 'Aktif' : 'Pasif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="sponsor_edit.php?id=<?php echo $sponsor['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="?delete=<?php echo $sponsor['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu referans resmini silmek istediğinizden emin misiniz?');">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Henüz referans resmi eklenmemiş.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 