<?php
/**
 * SEO Alanları Migration Scripti
 * Blog yazıları ve sayfalar için SEO alanlarını ekler
 */

require_once __DIR__ . '/includes/require_admin_web.php';

$messages = [];
$errors = [];

// Blog Posts tablosuna SEO alanları ekle
$blog_columns = [
    'seo_title' => "ALTER TABLE blog_posts ADD COLUMN seo_title VARCHAR(70) NULL AFTER slug",
    'meta_description' => "ALTER TABLE blog_posts ADD COLUMN meta_description VARCHAR(160) NULL AFTER seo_title",
    'meta_keywords' => "ALTER TABLE blog_posts ADD COLUMN meta_keywords VARCHAR(255) NULL AFTER meta_description",
    'og_title' => "ALTER TABLE blog_posts ADD COLUMN og_title VARCHAR(95) NULL AFTER meta_keywords",
    'og_description' => "ALTER TABLE blog_posts ADD COLUMN og_description VARCHAR(200) NULL AFTER og_title",
    'og_image' => "ALTER TABLE blog_posts ADD COLUMN og_image VARCHAR(500) NULL AFTER og_description",
    'canonical_url' => "ALTER TABLE blog_posts ADD COLUMN canonical_url VARCHAR(500) NULL AFTER og_image",
    'focus_keyword' => "ALTER TABLE blog_posts ADD COLUMN focus_keyword VARCHAR(100) NULL AFTER canonical_url",
    'seo_score' => "ALTER TABLE blog_posts ADD COLUMN seo_score INT DEFAULT 0 AFTER focus_keyword"
];

// Pages tablosuna eksik SEO alanları ekle
$pages_columns = [
    'seo_title' => "ALTER TABLE pages ADD COLUMN seo_title VARCHAR(70) NULL AFTER slug",
    'og_title' => "ALTER TABLE pages ADD COLUMN og_title VARCHAR(95) NULL AFTER meta_keywords",
    'og_description' => "ALTER TABLE pages ADD COLUMN og_description VARCHAR(200) NULL AFTER og_title",
    'og_image' => "ALTER TABLE pages ADD COLUMN og_image VARCHAR(500) NULL AFTER og_description",
    'canonical_url' => "ALTER TABLE pages ADD COLUMN canonical_url VARCHAR(500) NULL AFTER og_image",
    'focus_keyword' => "ALTER TABLE pages ADD COLUMN focus_keyword VARCHAR(100) NULL AFTER canonical_url",
    'seo_score' => "ALTER TABLE pages ADD COLUMN seo_score INT DEFAULT 0 AFTER focus_keyword"
];

// Services tablosuna SEO alanları ekle
$services_columns = [
    'seo_title' => "ALTER TABLE services ADD COLUMN seo_title VARCHAR(70) NULL",
    'meta_description' => "ALTER TABLE services ADD COLUMN meta_description VARCHAR(160) NULL",
    'meta_keywords' => "ALTER TABLE services ADD COLUMN meta_keywords VARCHAR(255) NULL",
    'slug' => "ALTER TABLE services ADD COLUMN slug VARCHAR(255) NULL",
    'focus_keyword' => "ALTER TABLE services ADD COLUMN focus_keyword VARCHAR(100) NULL",
    'seo_score' => "ALTER TABLE services ADD COLUMN seo_score INT DEFAULT 0"
];

echo "<html><head><title>SEO Alanları Migration</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";
echo "<div class='container py-5'>";
echo "<h1 class='mb-4'><i class='fas fa-database'></i> SEO Alanları Migration</h1>";

// Sütun var mı kontrol eden fonksiyon
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Blog Posts tablosu
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-primary text-white'><h5 class='mb-0'>Blog Posts Tablosu</h5></div>";
echo "<div class='card-body'>";

foreach ($blog_columns as $column => $sql) {
    if (columnExists($conn, 'blog_posts', $column)) {
        echo "<div class='alert alert-info py-2'><i class='fas fa-check-circle'></i> <strong>$column</strong> sütunu zaten mevcut.</div>";
    } else {
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success py-2'><i class='fas fa-plus-circle'></i> <strong>$column</strong> sütunu başarıyla eklendi.</div>";
        } else {
            echo "<div class='alert alert-danger py-2'><i class='fas fa-times-circle'></i> <strong>$column</strong> eklenirken hata: " . $conn->error . "</div>";
        }
    }
}
echo "</div></div>";

// Pages tablosu
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-success text-white'><h5 class='mb-0'>Pages Tablosu</h5></div>";
echo "<div class='card-body'>";

foreach ($pages_columns as $column => $sql) {
    if (columnExists($conn, 'pages', $column)) {
        echo "<div class='alert alert-info py-2'><i class='fas fa-check-circle'></i> <strong>$column</strong> sütunu zaten mevcut.</div>";
    } else {
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success py-2'><i class='fas fa-plus-circle'></i> <strong>$column</strong> sütunu başarıyla eklendi.</div>";
        } else {
            echo "<div class='alert alert-danger py-2'><i class='fas fa-times-circle'></i> <strong>$column</strong> eklenirken hata: " . $conn->error . "</div>";
        }
    }
}
echo "</div></div>";

// Services tablosu - eğer varsa
$table_check = $conn->query("SHOW TABLES LIKE 'services'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<div class='card mb-4'>";
    echo "<div class='card-header bg-warning'><h5 class='mb-0'>Services Tablosu</h5></div>";
    echo "<div class='card-body'>";
    
    foreach ($services_columns as $column => $sql) {
        if (columnExists($conn, 'services', $column)) {
            echo "<div class='alert alert-info py-2'><i class='fas fa-check-circle'></i> <strong>$column</strong> sütunu zaten mevcut.</div>";
        } else {
            if ($conn->query($sql)) {
                echo "<div class='alert alert-success py-2'><i class='fas fa-plus-circle'></i> <strong>$column</strong> sütunu başarıyla eklendi.</div>";
            } else {
                echo "<div class='alert alert-danger py-2'><i class='fas fa-times-circle'></i> <strong>$column</strong> eklenirken hata: " . $conn->error . "</div>";
            }
        }
    }
    echo "</div></div>";
}

// SEO Ayarları tablosu oluştur
$seo_settings_sql = "CREATE TABLE IF NOT EXISTS seo_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'><h5 class='mb-0'>SEO Ayarları Tablosu</h5></div>";
echo "<div class='card-body'>";

if ($conn->query($seo_settings_sql)) {
    echo "<div class='alert alert-success py-2'><i class='fas fa-check-circle'></i> SEO ayarları tablosu oluşturuldu/kontrol edildi.</div>";
    
    // Varsayılan ayarları ekle
    $default_settings = [
        ['openai_api_key', '', 'OpenAI API Anahtarı'],
        ['seo_auto_generate', '0', 'Otomatik SEO içerik oluşturma'],
        ['default_og_image', '', 'Varsayılan Open Graph görseli'],
        ['site_name_suffix', ' - MY Nakliyat', 'Başlık soneki'],
        ['min_content_length', '300', 'Minimum içerik uzunluğu (kelime)'],
        ['min_title_length', '30', 'Minimum başlık uzunluğu'],
        ['max_title_length', '60', 'Maksimum başlık uzunluğu'],
        ['min_description_length', '120', 'Minimum açıklama uzunluğu'],
        ['max_description_length', '160', 'Maksimum açıklama uzunluğu']
    ];
    
    $insert_stmt = $conn->prepare("INSERT IGNORE INTO seo_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    foreach ($default_settings as $setting) {
        $insert_stmt->bind_param("sss", $setting[0], $setting[1], $setting[2]);
        $insert_stmt->execute();
    }
    echo "<div class='alert alert-success py-2'><i class='fas fa-cog'></i> Varsayılan SEO ayarları eklendi.</div>";
} else {
    echo "<div class='alert alert-danger py-2'><i class='fas fa-times-circle'></i> Tablo oluşturulurken hata: " . $conn->error . "</div>";
}
echo "</div></div>";

echo "<div class='alert alert-success'>";
echo "<h4><i class='fas fa-check-circle'></i> Migration Tamamlandı!</h4>";
echo "<p class='mb-0'>Tüm SEO alanları başarıyla eklendi. Artık SEO Detector'ı kullanabilirsiniz.</p>";
echo "</div>";

echo "<div class='mt-4'>";
echo "<a href='seo_detector.php' class='btn btn-primary btn-lg'><i class='fas fa-search'></i> SEO Detector'a Git</a> ";
echo "<a href='dashboard.php' class='btn btn-secondary btn-lg'><i class='fas fa-home'></i> Dashboard'a Dön</a>";
echo "</div>";

echo "</div></body></html>";
?>

