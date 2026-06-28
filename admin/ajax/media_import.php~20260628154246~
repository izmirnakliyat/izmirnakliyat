<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

require_once '../includes/init.php';

header('Content-Type: application/json; charset=utf-8');
@set_time_limit(300);

// İçe aktarılacak klasörler (temp, form_attachments, media hariç)
$scan_dirs = [
    'blog', 'auto_blog_covers', 'gallery', 'gallery_slider',
    'slides', 'services', 'about', 'content', 'editor',
    'testimonials', 'sponsors', 'popup', 'team', 'kids'
];

$allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'bmp'];

$imported = 0;
$skipped  = 0;
$errors   = [];

foreach ($scan_dirs as $dir) {
    $full_dir = '../../uploads/' . $dir . '/';
    if (!is_dir($full_dir)) continue;

    $files = scandir($full_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) continue;

        $file_path = 'uploads/' . $dir . '/' . $file;

        // Zaten kayıtlı mı?
        $chk = $conn->prepare("SELECT id FROM media_library WHERE file_path = ? LIMIT 1");
        $chk->bind_param("s", $file_path);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $chk->close();
            $skipped++;
            continue;
        }
        $chk->close();

        // Dosya bilgilerini al
        $full_path  = $full_dir . $file;
        $file_size  = filesize($full_path);
        $mime       = mime_content_type($full_path);
        $width = 0; $height = 0;
        $img_info = @getimagesize($full_path);
        if ($img_info) {
            $width  = $img_info[0];
            $height = $img_info[1];
        }

        // DB'ye ekle
        $stmt = $conn->prepare("INSERT INTO media_library (filename, original_name, file_path, file_type, file_size, width, height, alt_text, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, '', NOW())");
        $stmt->bind_param("ssssiii", $file, $file, $file_path, $mime, $file_size, $width, $height);
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors[] = $file . ': ' . $conn->error;
        }
        $stmt->close();
    }
}

echo json_encode([
    'success'  => true,
    'imported' => $imported,
    'skipped'  => $skipped,
    'errors'   => $errors,
    'message'  => "$imported resim içe aktarıldı, $skipped zaten mevcuttu."
]);
