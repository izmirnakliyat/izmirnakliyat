<?php
$page_title = 'Hizmetler Yönetimi';
require_once 'includes/header.php';

// Hizmet silme
if (isset($_GET['delete'])) {
    $service_id = (int)$_GET['delete'];
    // Foto sil
    $stmt = $conn->prepare("SELECT foto FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($service = $result->fetch_assoc()) {
        $foto_path = "../uploads/services/" . $service['foto'];
        if ($service['foto'] && file_exists($foto_path)) {
            unlink($foto_path);
        }
    }
    // Kayıt sil
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    if ($stmt->execute()) {
        $success = "Hizmet başarıyla silindi.";
    } else {
        $error = "Hizmet silinirken bir hata oluştu.";
    }
}

// Durum değiştir
if (isset($_POST['toggle_status'])) {
    $service_id = (int)$_POST['service_id'];
    $stmt = $conn->prepare("UPDATE services SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    if ($stmt->execute()) {
        $success = "Durum güncellendi.";
    } else {
        $error = "Durum güncellenirken bir hata oluştu.";
    }
}

// Hizmetleri listele
$services = $conn->query("SELECT * FROM services ORDER BY order_number ASC, id DESC");
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Hizmetler</h5>
        <a href="service_edit.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni Hizmet
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
                        <th>Foto</th>
                        <th>Üst Başlık</th>
                        <th>Ana Başlık</th>
                        <th>Açıklama</th>
                        <th>Arkaplan</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($services->num_rows > 0): ?>
                        <?php while ($service = $services->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $service['order_number']; ?></td>
                                <td>
                                    <?php if ($service['foto']): ?>
                                        <img src="../uploads/services/<?php echo htmlspecialchars($service['foto']); ?>" alt="<?php echo htmlspecialchars($service['ana_baslik']); ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 6px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($service['ust_baslik']); ?></td>
                                <td><?php echo htmlspecialchars($service['ana_baslik']); ?></td>
                                <td><?php echo htmlspecialchars(mb_strimwidth($service['aciklama'], 0, 60, '...')); ?></td>
                                <td><span style="display:inline-block;width:32px;height:20px;background:<?php echo htmlspecialchars($service['bg_color']); ?>;border-radius:4px;"></span></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $service['status'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $service['status'] ? 'Aktif' : 'Pasif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="service_edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary"><i class='bx bx-edit'></i></a>
                                    <a href="?delete=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu hizmeti silmek istediğinizden emin misiniz?');"><i class='bx bx-trash'></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">Henüz hizmet eklenmemiş.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 