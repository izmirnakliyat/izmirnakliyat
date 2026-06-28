<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blok_id = intval($_POST['blok_id']);
    
    // Blok bilgilerini al
    $stmt = $conn->prepare("SELECT * FROM contact_blocks WHERE id = ?");
    $stmt->bind_param('i', $blok_id);
    $stmt->execute();
    $row = mysqli_stmt_fetch_assoc_first($stmt);
    $stmt->close();

    if ($row) {
        $alanlar = explode(',', $row['alanlar']);
        $form_data = [];
        
        // Form verilerini topla
        foreach ($alanlar as $alan) {
            if (isset($_POST[$alan])) {
                $form_data[$alan] = htmlspecialchars($_POST[$alan]);
            }
        }
        
        // E-posta gönder
        $to = "info@mynakliyat.com.tr"; // Varsayılan e-posta adresi
        $subject = "İletişim Formu - " . $form_data['konu'] ?? 'Yeni Mesaj';
        
        $message = "Yeni bir iletişim formu mesajı alındı:\n\n";
        foreach ($form_data as $key => $value) {
            $message .= ucfirst($key) . ": " . $value . "\n";
        }
        
        $headers = "From: " . ($form_data['email'] ?? 'noreply@mynakliyat.com.tr') . "\r\n";
        $headers .= "Reply-To: " . ($form_data['email'] ?? 'noreply@mynakliyat.com.tr') . "\r\n";
        
        if (mail($to, $subject, $message, $headers)) {
            $_SESSION['success'] = $row['basarili_mesaji'];
        } else {
            $_SESSION['error'] = "Mesaj gönderilirken bir hata oluştu.";
        }
    } else {
        $_SESSION['error'] = "Geçersiz form bloğu.";
    }
    
    // Kullanıcıyı geri yönlendir
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    header("Location: index.php");
    exit;
}
?> 