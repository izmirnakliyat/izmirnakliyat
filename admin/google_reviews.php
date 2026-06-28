<?php
$page_title = 'Google Yorumlar';
require_once 'includes/header.php';

// Tablo oluştur
$conn->query("CREATE TABLE IF NOT EXISTS `google_reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `author_name` varchar(255) NOT NULL,
    `author_photo_url` varchar(500) DEFAULT NULL,
    `rating` tinyint(1) NOT NULL DEFAULT 5,
    `text` text,
    `relative_time` varchar(100) DEFAULT NULL,
    `time` int(11) DEFAULT NULL,
    `review_photos` text DEFAULT NULL,
    `is_local_guide` tinyint(1) DEFAULT 0,
    `review_count` int(11) DEFAULT 0,
    `status` tinyint(1) DEFAULT 1,
    `fetched_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Ayarları getir
function getGoogleSetting($conn, $name) {
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? $row['value'] : '';
}

function saveGoogleSetting($conn, $name, $value) {
    $eName = $conn->real_escape_string($name);
    $eValue = $conn->real_escape_string($value);

    $exists = $conn->query("SELECT id FROM settings WHERE name = '$eName' LIMIT 1");
    if ($exists && $exists->num_rows > 0) {
        $conn->query("UPDATE settings SET value = '$eValue' WHERE name = '$eName'");
    } else {
        $ok = $conn->query("INSERT INTO settings (name, value, description) VALUES ('$eName', '$eValue', '')");
        if (!$ok) {
            $ok = $conn->query("INSERT INTO settings (name, value) VALUES ('$eName', '$eValue')");
        }
    }
}

$api_key = getGoogleSetting($conn, 'google_places_api_key');
$place_id = getGoogleSetting($conn, 'google_place_id');
$maps_url = getGoogleSetting($conn, 'google_maps_url');
$last_sync = getGoogleSetting($conn, 'google_reviews_last_sync');

$message = '';
$message_type = '';

// Her iki butonda da form alanlarını kaydet ve kullan
if (isset($_POST['save_settings']) || isset($_POST['fetch_reviews'])) {
    $api_key = trim($_POST['api_key'] ?? '');
    $place_id = trim($_POST['place_id'] ?? '');
    $maps_url = trim($_POST['maps_url'] ?? '');

    saveGoogleSetting($conn, 'google_places_api_key', $api_key);
    saveGoogleSetting($conn, 'google_place_id', $place_id);
    saveGoogleSetting($conn, 'google_maps_url', $maps_url);

    if (isset($_POST['save_settings'])) {
        $message = 'Ayarlar başarıyla kaydedildi.';
        $message_type = 'success';
    }
}

// Google'dan yorumları çek
if (isset($_POST['fetch_reviews'])) {
    if (empty($api_key) || empty($place_id)) {
        $message = 'API Key ve Place ID zorunludur.';
        $message_type = 'danger';
    } else {
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?'
            . 'place_id=' . urlencode($place_id)
            . '&fields=name,rating,reviews,user_ratings_total,url'
            . '&reviews_sort=newest'
            . '&language=tr'
            . '&key=' . urlencode($api_key);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $message = 'cURL hatası: ' . $curl_error;
            $message_type = 'danger';
        } else {
            $data = json_decode($response, true);

            if (isset($data['status']) && $data['status'] === 'OK' && isset($data['result']['reviews'])) {
                $reviews = $data['result']['reviews'];
                $place_name = $data['result']['name'] ?? '';
                $place_rating = $data['result']['rating'] ?? 0;
                $total_reviews = $data['result']['user_ratings_total'] ?? 0;

                if (!empty($data['result']['url'])) {
                    saveGoogleSetting($conn, 'google_maps_url', $data['result']['url']);
                    $maps_url = $data['result']['url'];
                }

                saveGoogleSetting($conn, 'google_place_name', $place_name);
                saveGoogleSetting($conn, 'google_place_rating', $place_rating);
                saveGoogleSetting($conn, 'google_total_reviews', $total_reviews);

                $inserted = 0;
                $now = date('Y-m-d H:i:s');

                foreach ($reviews as $review) {
                    $author = $review['author_name'] ?? 'Anonim';
                    $photo = $review['profile_photo_url'] ?? '';
                    $rating = $review['rating'] ?? 5;
                    $text = $review['text'] ?? '';
                    $rel_time = $review['relative_time_description'] ?? '';
                    $time = $review['time'] ?? 0;

                    // Aynı yazar + aynı zaman = aynı yorum kontrolü
                    $check = $conn->prepare("SELECT id FROM google_reviews WHERE author_name = ? AND time = ? LIMIT 1");
                    $check->bind_param("si", $author, $time);
                    $check->execute();
                    $exists = $check->get_result()->num_rows > 0;
                    $check->close();

                    if (!$exists) {
                        $stmt = $conn->prepare("INSERT INTO google_reviews (author_name, author_photo_url, rating, text, relative_time, time, is_local_guide, status, fetched_at) VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?)");
                        $stmt->bind_param("ssissis", $author, $photo, $rating, $text, $rel_time, $time, $now);
                        $stmt->execute();
                        $stmt->close();
                        $inserted++;
                    }
                }

                saveGoogleSetting($conn, 'google_reviews_last_sync', $now);
                $last_sync = $now;
                $message = count($reviews) . ' yorum bulundu, ' . $inserted . ' yeni yorum eklendi. İşletme: ' . $place_name . ' (' . $place_rating . '/5, ' . $total_reviews . ' yorum)';
                $message_type = 'success';
            } else {
                $error_msg = $data['error_message'] ?? ($data['status'] ?? 'Bilinmeyen hata');
                $message = 'Google API hatası: ' . $error_msg;
                $message_type = 'danger';
            }
        }
    }
}

// Yorum durumunu değiştir
if (isset($_POST['toggle_status'])) {
    $rid = (int)$_POST['review_id'];
    $conn->query("UPDATE google_reviews SET status = IF(status=1, 0, 1) WHERE id = $rid");
    $message = 'Yorum durumu güncellendi.';
    $message_type = 'info';
}

// Yorum sil
if (isset($_POST['delete_review'])) {
    $rid = (int)$_POST['review_id'];
    $conn->query("DELETE FROM google_reviews WHERE id = $rid");
    $message = 'Yorum silindi.';
    $message_type = 'warning';
}

// Mevcut yorumları getir
$reviews_result = $conn->query("SELECT * FROM google_reviews ORDER BY time DESC");
$review_count = $reviews_result ? $reviews_result->num_rows : 0;
?>

<div class="content-wrapper">
    <div class="container-fluid py-4">

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                <i class="bx bx-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'error-circle' : 'info-circle') ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Ayarlar -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bx bx-cog me-2"></i>Google Places API Ayarları</h5>
                <?php if ($last_sync): ?>
                    <span class="badge bg-success">Son senkronizasyon: <?= htmlspecialchars($last_sync) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Google Places API Key</label>
                            <input type="text" name="api_key" class="form-control" value="<?= htmlspecialchars($api_key) ?>" placeholder="AIzaSy...">
                            <small class="text-muted">Google Cloud Console > APIs & Services > Credentials</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Google Place ID</label>
                            <input type="text" name="place_id" class="form-control" value="<?= htmlspecialchars($place_id) ?>" placeholder="ChIJ...">
                            <small class="text-muted"><a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Place ID Finder</a></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Google Maps Linki</label>
                            <input type="text" name="maps_url" class="form-control" value="<?= htmlspecialchars($maps_url) ?>" placeholder="https://maps.google.com/...">
                            <small class="text-muted">"Haritalarda İncele" butonu için (otomatik doldurulur)</small>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Ayarları Kaydet
                        </button>
                        <button type="submit" name="fetch_reviews" class="btn btn-success">
                            <i class="bx bx-refresh me-1"></i> Google'dan Yorumları Çek
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Yorumlar Listesi -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bx bx-message-square-dots me-2"></i>Cache'lenmiş Yorumlar (<?= $review_count ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($review_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="reviewsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Yazar</th>
                                    <th>Puan</th>
                                    <th>Yorum</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th width="130">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $reviews_result->fetch_assoc()): ?>
                                    <tr class="<?= $r['status'] ? '' : 'table-secondary' ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($r['author_photo_url'])): ?>
                                                    <img src="<?= htmlspecialchars($r['author_photo_url']) ?>" class="rounded-circle" width="32" height="32" alt="">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:14px;font-weight:600;">
                                                        <?= mb_strtoupper(mb_substr($r['author_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($r['author_name']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bx bxs-star <?= $i <= $r['rating'] ? 'text-warning' : 'text-muted' ?>" style="font-size:14px;"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td><span class="d-inline-block text-truncate" style="max-width:300px;"><?= htmlspecialchars($r['text']) ?></span></td>
                                        <td class="text-nowrap small"><?= htmlspecialchars($r['relative_time']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $r['status'] ? 'success' : 'secondary' ?>"><?= $r['status'] ? 'Aktif' : 'Pasif' ?></span>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-<?= $r['status'] ? 'secondary' : 'success' ?>" title="<?= $r['status'] ? 'Pasif Yap' : 'Aktif Yap' ?>">
                                                    <i class="bx bx-<?= $r['status'] ? 'hide' : 'show' ?>"></i>
                                                </button>
                                                <button type="submit" name="delete_review" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')" title="Sil">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-message-square-dots" style="font-size:4rem;"></i>
                        <p class="mt-3">Henüz yorum yok. Yukarıdan "Google'dan Yorumları Çek" butonuna tıklayın.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#reviewsTable').length && $.fn.DataTable) {
        $('#reviewsTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json' },
            pageLength: 25,
            order: [[3, 'desc']]
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
