<?php
require_once '../../config/db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../includes/init.php';
include '../includes/auto_blog_functions.php';
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false];

// Debug log
error_log("Auto Blog Save - POST data: " . print_r($_POST, true));
error_log("Auto Blog Save - FILES data: " . print_r($_FILES, true));

if (!isset($_POST['action'])) {
    error_log("Auto Blog Save - Action not found in POST data");
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek! Action bulunamadı.']);
    exit;
}

function handle_cover_upload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) return '';
    $target_dir = '../../uploads/auto_blog_covers/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $filename = 'cover_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $target_path = $target_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return 'uploads/auto_blog_covers/' . $filename;
    }
    return '';
}

switch ($_POST['action']) {
    case 'add':
        $cover = handle_cover_upload($_FILES['cover_image'] ?? null);
        // Türkçe karakterleri korumak için UTF-8 encoding
        $keywords = mb_convert_encoding($_POST['keywords'], 'UTF-8', 'auto');
        $manual_command = !empty($_POST['manual_command']) ? mb_convert_encoding($_POST['manual_command'], 'UTF-8', 'auto') : '';
        
        $data = [
            'category_id' => $_POST['category_id'],
            'keywords' => $keywords,
            'manual_command' => $manual_command,
            'cover_image' => $cover,
            'min_words' => $_POST['min_words'],
            'max_words' => $_POST['max_words'],
            'post_count_per_period' => $_POST['post_count_per_period'],
            'period_type' => $_POST['period_type'],
            'post_time' => $_POST['post_time'],
            'active' => isset($_POST['active']) ? 1 : 0
        ];
        if (add_auto_blog_setting($data)) {
            $response = ['success'=>true, 'message'=>'Ayar eklendi!'];
        } else {
            $response['message'] = 'Kayıt başarısız!';
        }
        break;
    case 'edit':
        $id = intval($_POST['id']);
        $old = get_auto_blog_setting($id);
        $cover = $old['cover_image'];
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $cover = handle_cover_upload($_FILES['cover_image']);
        }
        // Türkçe karakterleri korumak için UTF-8 encoding
        $keywords = mb_convert_encoding($_POST['keywords'], 'UTF-8', 'auto');
        $manual_command = !empty($_POST['manual_command']) ? mb_convert_encoding($_POST['manual_command'], 'UTF-8', 'auto') : '';
        
        $data = [
            'category_id' => $_POST['category_id'],
            'keywords' => $keywords,
            'manual_command' => $manual_command,
            'cover_image' => $cover,
            'min_words' => $_POST['min_words'],
            'max_words' => $_POST['max_words'],
            'post_count_per_period' => $_POST['post_count_per_period'],
            'period_type' => $_POST['period_type'],
            'post_time' => $_POST['post_time'],
            'active' => isset($_POST['active']) ? 1 : 0
        ];
        if (update_auto_blog_setting($id, $data)) {
            $response = ['success'=>true, 'message'=>'Ayar güncellendi!'];
        } else {
            global $conn;
            $response['message'] = 'Güncelleme başarısız! ' . $conn->error;
        }
        break;
    case 'delete':
        $id = intval($_POST['id']);
        if (delete_auto_blog_setting($id)) {
            $response = ['success'=>true, 'message'=>'Ayar silindi!'];
        } else {
            global $conn;
            $response['message'] = 'Silme başarısız! ' . $conn->error;
        }
        break;
    case 'get':
        $id = intval($_POST['id']);
        $setting = get_auto_blog_setting($id);
        if ($setting) {
            echo json_encode(['success'=>true, 'setting'=>$setting]);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Ayar bulunamadı!']);
        }
        exit;
    default:
        $response['message'] = 'Bilinmeyen işlem!';
}

echo json_encode($response); 