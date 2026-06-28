<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// Hata raporlamasını kapat
error_reporting(0);
ini_set('display_errors', 0);

// JSON header'ı ekle
header('Content-Type: application/json');

try {
    if (!isset($_FILES['images'])) {
        throw new Exception('Dosya yüklenemedi.');
    }

    $uploaded_files = [];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        $file_type = $_FILES['images']['type'][$key];
        $file_size = $_FILES['images']['size'][$key];

        // Dosya tipi ve boyut kontrolü
        if (!in_array($file_type, $allowed_types)) {
            continue;
        }
        if ($file_size > $max_size) {
            continue;
        }

        // Benzersiz dosya adı oluştur
        $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = '../uploads/gallery/' . $new_filename;

        // Dosyayı yükle
        if (move_uploaded_file($tmp_name, $upload_path)) {
            $uploaded_files[] = $new_filename;
        }
    }

    if (empty($uploaded_files)) {
        throw new Exception('Hiçbir dosya yüklenemedi.');
    }

    // Başarılı yanıt
    echo json_encode([
        'success' => true,
        'files' => $uploaded_files
    ]);

} catch (Exception $e) {
    // Hata yanıtı
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 