<?php
require_once '../config/db.php';
require_once 'includes/init.php';

// Hata yakalama fonksiyonu
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'success' => false,
        'message' => "PHP Hatası: $errstr",
        'details' => "Dosya: $errfile, Satır: $errline"
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// PHP hata yakalayıcıyı ayarla
set_error_handler('handleError');

// Tüm hataları göster
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Admin login kontrolü
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    // Resim indirme fonksiyonu
    function downloadImage($url, $save_path) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data) {
            $upload_dir = dirname(dirname($save_path));
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            file_put_contents($save_path, $data);
            return true;
        }
        return false;
    }

    // Slug üretici
    function generateSlug($str) {
        $str = mb_strtolower($str, 'UTF-8');
        $str = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $str);
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        $str = trim($str, '-');
        return $str;
    }

    $response = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Upload klasörü kontrolü
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!file_exists($uploadDir)) {
            if (!@mkdir($uploadDir, 0777, true)) {
                throw new Exception('Upload klasörü oluşturulamadı: ' . error_get_last()['message']);
            }
        }
        
        if (!is_writable($uploadDir)) {
            throw new Exception('Upload klasörü yazılabilir değil. Mevcut izinler: ' . substr(sprintf('%o', fileperms($uploadDir)), -4));
        }

        // Dosya yükleme kontrolü
        if (!isset($_FILES['xml_file'])) {
            throw new Exception('Dosya gönderilmedi');
        }

        if ($_FILES['xml_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = array(
                UPLOAD_ERR_INI_SIZE => 'Dosya boyutu php.ini\'deki upload_max_filesize değerini aşıyor',
                UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu HTML formundaki MAX_FILE_SIZE değerini aşıyor',
                UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
                UPLOAD_ERR_NO_FILE => 'Dosya yüklenmedi',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör eksik',
                UPLOAD_ERR_CANT_WRITE => 'Disk\'e yazılamadı',
                UPLOAD_ERR_EXTENSION => 'PHP uzantısı dosya yüklemesini durdurdu'
            );
            $errorMessage = isset($uploadErrors[$_FILES['xml_file']['error']]) 
                ? $uploadErrors[$_FILES['xml_file']['error']] 
                : 'Bilinmeyen yükleme hatası: ' . $_FILES['xml_file']['error'];
            throw new Exception($errorMessage);
        }

        $uploadedFile = $uploadDir . basename($_FILES['xml_file']['name']);
        
        // Dosya yükleme denemesi
        if (!@move_uploaded_file($_FILES['xml_file']['tmp_name'], $uploadedFile)) {
            throw new Exception('Dosya taşıma hatası: ' . error_get_last()['message']);
        }

        $xml_file = $uploadedFile;
        
        // XML işleme
        if (!file_exists($xml_file)) {
            throw new Exception('Yüklenen dosya bulunamadı');
        }

        $xml = @simplexml_load_file($xml_file);
        if ($xml === false) {
            throw new Exception('XML dosyası geçerli değil: ' . libxml_get_last_error()->message);
        }

        $namespace = $xml->getNamespaces(true);
        $count = 0;

        foreach ($xml->channel->item as $item) {
            $wp = $item->children($namespace['wp']);
            $content = $item->children($namespace['content']);
            
            if ((string)$wp->post_type === 'post') {
                $title = (string)$item->title;
                $post_content = (string)$content->encoded;
                $post_date = (string)$wp->post_date;
                $slug = generateSlug($title);

                // Etiketleri topla
                $tags = [];
                $kategori_adi = null;
                foreach ($item->category as $category) {
                    $domain = (string)$category['domain'];
                    if ($domain === 'post_tag') {
                        $tags[] = (string)$category;
                    } elseif ($domain === 'category') {
                        $kategori_adi = trim((string)$category);
                    }
                }
                $tags_str = implode(', ', $tags);

                // Kategori işlemleri
                $kategori_id = null;
                if ($kategori_adi) {
                    // Kategori var mı kontrol et, yoksa ekle
                    $stmt = $conn->prepare("SELECT id FROM blog_categories WHERE ad = ? LIMIT 1");
                    $stmt->execute([$kategori_adi]);
                    $row = $stmt->get_result()->fetch_assoc();
                    if ($row) {
                        $kategori_id = $row['id'];
                    } else {
                        $slug_kat = generateSlug($kategori_adi);
                        $stmt_insert = $conn->prepare("INSERT INTO blog_categories (ad, slug) VALUES (?, ?)");
                        $stmt_insert->execute([$kategori_adi, $slug_kat]);
                        $kategori_id = $conn->insert_id;
                    }
                }

                // Kapak fotoğrafını bul ve indir
                $thumbnail_url = '';
                foreach ($item->children($namespace['wp'])->postmeta as $meta) {
                    if ((string)$meta->meta_key === '_thumbnail_id') {
                        $thumbnail_id = (string)$meta->meta_value;
                        // XML'de thumbnail ID'ye sahip görseli bul
                        foreach ($xml->channel->item as $attachment) {
                            $wp_attachment = $attachment->children($namespace['wp']);
                            if ((string)$wp_attachment->post_type === 'attachment' && 
                                (string)$wp_attachment->post_id === $thumbnail_id) {
                                $thumbnail_url = (string)$wp_attachment->attachment_url;
                                break;
                            }
                        }
                        break;
                    }
                }

                // Kapak fotoğrafını indir
                $cover_image = '';
                if ($thumbnail_url) {
                    $filename = uniqid() . '_' . basename($thumbnail_url);
                    $save_path = dirname(__DIR__) . '/uploads/blog/' . $filename;
                    if (downloadImage($thumbnail_url, $save_path)) {
                        $cover_image = basename($filename);
                    }
                }
                
                // İçerikteki resimleri indir
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $post_content_utf8 = htmlspecialchars_decode(htmlentities($post_content, ENT_QUOTES, 'UTF-8'));
                $dom->loadHTML('<?xml encoding="utf-8"?>' . $post_content_utf8, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                
                $images = $dom->getElementsByTagName('img');
                foreach ($images as $image) {
                    $src = $image->getAttribute('src');
                    $filename = basename($src);
                    $new_filename = uniqid() . '_' . $filename;
                    $save_path = dirname(__DIR__) . '/uploads/blog/' . $new_filename;
                    if (downloadImage($src, $save_path)) {
                        $post_content = str_replace($src, basename($new_filename), $post_content);
                    }
                }
                
                // Veritabanına ekle
                $stmt = $conn->prepare("INSERT INTO blog_posts (baslik, icerik, slug, created_at, durum, kapak_foto, etiketler, kategori_id) VALUES (?, ?, ?, ?, 3, ?, ?, ?)");
                $stmt->execute([$title, $post_content, $slug, $post_date, $cover_image, $tags_str, $kategori_id]);
                $count++;
            }
        }

        $response = ['success' => true, 'message' => "{$count} blog yazısı başarıyla aktarıldı."];

    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
} catch (Error $e) {
    $response = ['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()];
}

// Yanıt döndür
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>WordPress Blog Aktarımı</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .import-box { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px #0001; padding: 32px; }
        .import-box h2 { font-size: 1.5rem; margin-bottom: 1.5rem; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
        #result { margin-top: 20px; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="import-box">
    <h2>WordPress Blog Aktarımı</h2>
    <form id="importForm" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="xml_file" class="form-label">XML Dosyası Seç</label>
            <input type="file" class="form-control" id="xml_file" name="xml_file" accept=".xml" required>
            <small class="form-text text-muted">İzin verilen maksimum dosya boyutu: <?php echo ini_get('upload_max_filesize'); ?></small>
        </div>
        <button type="submit" class="btn btn-primary">İçeriği Aktar</button>
    </form>
    <div id="result"></div>
</div>

<script>
document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    
    // Yükleme başladı
    document.getElementById('result').innerHTML = '<div class="alert alert-info">Dosya yükleniyor...</div>';
    
    fetch('import_wordpress.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'}">${data.message}</div>`;
        if (data.details) {
            resultDiv.innerHTML += `<div class="alert alert-info">${data.details}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('result').innerHTML = '<div class="alert alert-danger">Bir hata oluştu: ' + error.message + '</div>';
    });
});
</script>
</body>
</html>