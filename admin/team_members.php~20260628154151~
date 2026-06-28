<?php
require_once 'includes/header.php';

$page_title = "Eğitmen Kadrosu";
$success = '';
$error = '';

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Önce resmi sil
    $result = $conn->query("SELECT foto FROM team_members WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $file_path = '../uploads/team/' . $row['foto'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    // Sonra eğitmeni sil
    if ($conn->query("DELETE FROM team_members WHERE id = $id")) {
        $success = "Eğitmen başarıyla silindi.";
    } else {
        $error = "Eğitmen silinirken bir hata oluştu: " . $conn->error;
    }
}

// Durum değiştir
if (isset($_POST['toggle_status'])) {
    $member_id = (int)$_POST['team_id'];
    $stmt = $conn->prepare("UPDATE team_members SET durum = NOT durum WHERE id = ?");
    $stmt->bind_param("i", $member_id);
    if ($stmt->execute()) {
        $success = "Durum güncellendi.";
    } else {
        $error = "Durum güncellenirken bir hata oluştu: " . $stmt->error;
    }
}

// Sıralama değiştir
if (isset($_POST['update_order'])) {
    $member_id = (int)$_POST['team_id'];
    $new_order = (int)$_POST['order_number'];
    
    $stmt = $conn->prepare("UPDATE team_members SET order_number = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_order, $member_id);
    if ($stmt->execute()) {
        $success = "Sıralama güncellendi.";
    } else {
        $error = "Sıralama güncellenirken bir hata oluştu: " . $stmt->error;
    }
}

// Eğitmenleri getir
$members = $conn->query("SELECT * FROM team_members ORDER BY order_number ASC, id DESC");
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Eğitmen Kadrosu</h5>
        <a href="team_edit.php" class="btn btn-primary">
            <i class='bx bx-plus'></i> Yeni Eğitmen Ekle
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
                        <th>ID</th>
                        <th>Resim</th>
                        <th>Ad</th>
                        <th>Ünvan</th>
                        <th>Durum</th>
                        <th>Sıralama</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($members->num_rows > 0): ?>
                        <?php while ($member = $members->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $member['id']; ?></td>
                                <td>
                                    <?php if (!empty($member['foto'])): ?>
                                        <img src="../uploads/team/<?php echo htmlspecialchars($member['foto']); ?>" alt="<?php echo htmlspecialchars($member['ad']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?php echo strtoupper(substr($member['ad'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($member['ad']); ?></td>
                                <td><?php echo htmlspecialchars($member['unvan']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="team_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $member['durum'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $member['durum'] ? 'Aktif' : 'Pasif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="team_id" value="<?php echo $member['id']; ?>">
                                        <input type="number" name="order_number" value="<?php echo $member['order_number']; ?>" class="form-control form-control-sm" style="width: 70px;">
                                        <button type="submit" name="update_order" class="btn btn-sm btn-primary ms-2">
                                            <i class='bx bx-save'></i>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($member['created_at'])); ?></td>
                                <td>
                                    <a href="team_edit.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="?delete=<?php echo $member['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu eğitmeni silmek istediğinizden emin misiniz?');">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">Henüz eğitmen eklenmemiş.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #f0f0f0;
    color: #888;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
}
</style>

<?php require_once 'includes/footer.php'; ?> 