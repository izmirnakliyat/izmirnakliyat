<?php
// JSON header
header('Content-Type: application/json');

try {
    // Config dosyalarını include et
    if (is_file(__DIR__ . '/../config/config.php')) {
        require_once __DIR__ . '/../config/config.php';
    }

    if (!is_file(__DIR__ . '/../config/db.php')) {
        throw new Exception("Database config bulunamadı");
    }
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Database bağlantısını kontrol et
    if (!isset($conn) || !$conn) {
        throw new Exception("Database bağlantısı yok");
    }

    // POST kontrolü
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Sadece POST istekleri kabul edilir");
    }

    // Temel veri kontrolü
    if (empty($_POST)) {
        throw new Exception("POST verisi boş");
    }

    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || !is_string($postedCsrf)
        || $postedCsrf === ''
        || !hash_equals($_SESSION['csrf_token'], $postedCsrf)
    ) {
        throw new Exception('Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }

    // Form verilerini al
    $formData = $_POST;
    $formId = isset($formData['form_id']) ? (int)$formData['form_id'] : 0;
    $popupId = isset($formData['popup_id']) ? (int)$formData['popup_id'] : null;

    // En az bir ID olmalı
    if ($formId <= 0 && ($popupId === null || $popupId <= 0)) {
        throw new Exception("Form ID veya Popup ID gerekli");
    }

    // Form title belirle
    $formTitle = 'Genel Form';
    if ($formId > 0) {
        $stmt = $conn->prepare("SELECT title FROM forms WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $formId);
            $stmt->execute();
            $row = mysqli_stmt_fetch_assoc_first($stmt);
            $stmt->close();
            if ($row) {
                $formTitle = $row['title'];
            }
        }
    }

    // Gereksiz alanları temizle
    unset($formData['form_id']);
    unset($formData['popup_id']);
    unset($formData['popup_form']);
    unset($formData['csrf_token']);

    // JSON'a çevir
    $formDataJson = json_encode($formData, JSON_UNESCAPED_UNICODE);
    if ($formDataJson === false) {
        throw new Exception("JSON encoding hatası");
    }

    // IP ve user agent al
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Veritabanına kaydet
    if ($popupId && $popupId > 0) {
        // Popup ID var
        $sql = "INSERT INTO form_submissions (popup_id, form_id, form_title, form_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL prepare hatası: " . $conn->error);
        }
        $stmt->bind_param("iissss", $popupId, $formId, $formTitle, $formDataJson, $ipAddress, $userAgent);
    } else {
        // Sadece form ID
        $sql = "INSERT INTO form_submissions (form_id, form_title, form_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL prepare hatası: " . $conn->error);
        }
        $stmt->bind_param("issss", $formId, $formTitle, $formDataJson, $ipAddress, $userAgent);
    }

    // Execute
    if (!$stmt->execute()) {
        throw new Exception("SQL execute hatası: " . $stmt->error);
    }

    // Başarılı yanıt
    echo json_encode([
        'success' => true,
        'message' => 'Form başarıyla gönderildi.',
        'id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    // Hata logla
    error_log("Form submission error: " . $e->getMessage());
    
    // Hata yanıtı
    echo json_encode([
        'success' => false,
        'message' => 'Form gönderilirken bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>

