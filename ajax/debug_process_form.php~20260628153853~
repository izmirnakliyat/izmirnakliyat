<?php
require_once '../config/config.php';
require_once '../config/db.php';

if (!function_exists('mynak_allow_dev_debug_tools') || !mynak_allow_dev_debug_tools()) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Not found.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// JSON yanıtı için header
header('Content-Type: application/json');

// Debug log fonksiyonu
function debug_log($message) {
    $logDir = '../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    error_log(date('Y-m-d H:i:s') . ' - FORM DEBUG: ' . $message . PHP_EOL, 3, '../logs/form_debug.log');
}

debug_log("=== Form işlemi başladı ===");
debug_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
debug_log('POST keys: ' . implode(',', array_keys($_POST)));

try {
    // POST isteği değilse hata döndür
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        debug_log("HATA: POST metodu değil");
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz istek metodu.'
        ]);
        exit;
    }

    // Form verilerini al
    $formData = $_POST;
    $popupId = isset($formData['popup_id']) ? (int)$formData['popup_id'] : null;
    $formId = isset($formData['form_id']) ? (int)$formData['form_id'] : null;
    $isPopupForm = isset($formData['popup_form']) && $formData['popup_form'] == '1';

    debug_log("Form ID: $formId, Popup ID: $popupId, Is Popup Form: " . ($isPopupForm ? 'Yes' : 'No'));

    // Form ID veya Popup ID kontrolü
    if ($formId <= 0 && $popupId <= 0) {
        debug_log("HATA: Geçersiz form/popup ID");
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz form veya popup ID.'
        ]);
        exit;
    }

    // Form builder formu için bilgileri al
    $formTitle = '';
    $formFields = [];
    $emailNotifications = '';

    if ($formId > 0) {
        debug_log("Form builder formu sorgulanıyor: $formId");
        $stmt = $conn->prepare("SELECT title, fields, email_notifications FROM forms WHERE id = ? AND status = 1");
        $stmt->bind_param("i", $formId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            debug_log("HATA: Form bulunamadı: $formId");
            echo json_encode([
                'success' => false,
                'message' => 'Form bulunamadı.'
            ]);
            exit;
        }
        
        $form = $result->fetch_assoc();
        $formTitle = $form['title'];
        $formFields = json_decode($form['fields'], true) ?: [];
        $emailNotifications = $form['email_notifications'];
        
        debug_log("Form bulundu: " . $formTitle);
    } else {
        debug_log("Popup formu sorgulanıyor: $popupId");
        $stmt = $conn->prepare("SELECT title FROM popups WHERE id = ?");
        $stmt->bind_param("i", $popupId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            debug_log("HATA: Popup bulunamadı: $popupId");
            echo json_encode([
                'success' => false,
                'message' => 'Popup bulunamadı.'
            ]);
            exit;
        }
        
        $popup = $result->fetch_assoc();
        $formTitle = $popup['title'];
        debug_log("Popup bulundu: " . $formTitle);
    }

    // Form verilerini temizle
    unset($formData['popup_id']);
    unset($formData['form_id']);
    unset($formData['popup_form']);

    debug_log('Temizlenmiş alan sayısı: ' . (string) count($formData));

    // Dosya attachments varsa işle
    $attachments = [];
    if (!empty($_FILES)) {
        debug_log("Dosya yükleme işlemi başlıyor");
        // Dosya işleme kodu buraya...
    }

    // Form verilerini JSON'a dönüştür
    $formDataJson = json_encode($formData, JSON_UNESCAPED_UNICODE);
    debug_log("JSON Form Data: " . $formDataJson);

    // IP ve kullanıcı bilgilerini al
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    debug_log("IP: $ipAddress, User Agent: " . substr($userAgent, 0, 100));

    // Verileri kaydet
    if ($popupId !== null && $popupId > 0) {
        debug_log("Popup ID ile kayıt yapılıyor");
        $stmt = $conn->prepare("INSERT INTO form_submissions (popup_id, form_id, form_title, form_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iissss", $popupId, $formId, $formTitle, $formDataJson, $ipAddress, $userAgent);
    } else {
        debug_log("Sadece form ID ile kayıt yapılıyor");
        $stmt = $conn->prepare("INSERT INTO form_submissions (form_id, form_title, form_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $formId, $formTitle, $formDataJson, $ipAddress, $userAgent);
    }

    if ($stmt->execute()) {
        $insertId = $conn->insert_id;
        debug_log("Form başarıyla kaydedildi. ID: $insertId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Form başarıyla gönderildi.',
            'submission_id' => $insertId
        ]);
    } else {
        debug_log("SQL HATA: " . $stmt->error);
        throw new Exception("Form kaydedilemedi: " . $stmt->error);
    }

} catch (Exception $e) {
    debug_log("EXCEPTION: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Form işlenirken bir hata oluştu: ' . $e->getMessage()
    ]);
}

debug_log("=== Form işlemi tamamlandı ===");
?>
