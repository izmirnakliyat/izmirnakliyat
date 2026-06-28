<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Aktif popup formları al
    $stmt = $conn->prepare("SELECT id, title, settings FROM forms WHERE status = 1 AND settings LIKE '%\"popup_enabled\":true%'");
    $stmt->execute();
    $popup_rows = mysqli_stmt_fetch_all_assoc($stmt);
    $stmt->close();

    $forms = [];
    foreach ($popup_rows as $row) {
        $settings = json_decode($row['settings'], true);
        
        // Popup ayarlarını kontrol et
        if (isset($settings['popup_enabled']) && $settings['popup_enabled']) {
            $forms[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'settings' => $settings
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'forms' => $forms
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Popup formları yüklenirken hata oluştu: ' . $e->getMessage()
    ]);
}
?>

