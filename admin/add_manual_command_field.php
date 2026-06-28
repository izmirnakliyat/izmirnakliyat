<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// Auto Blog tablosuna manuel komut alanı ekleme script'i
echo "<h2>Auto Blog Tablosuna Manuel Komut Alanı Ekleme</h2>";

try {
    // Önce tabloyu kontrol et
    $check_table = $conn->query("SHOW TABLES LIKE 'auto_blog_settings'");
    if ($check_table->num_rows == 0) {
        echo "❌ auto_blog_settings tablosu bulunamadı!<br>";
        exit;
    }
    
    echo "✅ auto_blog_settings tablosu mevcut<br>";
    
    // Manuel komut alanının var olup olmadığını kontrol et
    $check_column = $conn->query("SHOW COLUMNS FROM auto_blog_settings LIKE 'manual_command'");
    
    if ($check_column->num_rows == 0) {
        // Alan yoksa ekle
        $add_column = "ALTER TABLE auto_blog_settings ADD COLUMN manual_command TEXT NULL AFTER keywords";
        
        if ($conn->query($add_column)) {
            echo "✅ 'manual_command' alanı başarıyla eklendi!<br>";
        } else {
            echo "❌ Alan ekleme hatası: " . $conn->error . "<br>";
            exit;
        }
    } else {
        echo "ℹ️ 'manual_command' alanı zaten mevcut<br>";
    }
    
    // Tablo yapısını göster
    echo "<h3>Güncel Tablo Yapısı:</h3>";
    $columns = $conn->query("SHOW COLUMNS FROM auto_blog_settings");
    echo "<ul>";
    while ($column = $columns->fetch_assoc()) {
        echo "<li><strong>" . $column['Field'] . "</strong> - " . $column['Type'] . " " . ($column['Null'] == 'YES' ? '(NULL)' : '(NOT NULL)') . "</li>";
    }
    echo "</ul>";
    
    echo "<br><strong>✅ İşlem tamamlandı! Artık manuel komut özelliğini kullanabilirsiniz.</strong>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage();
}

echo '<br><br><a href="auto_blog_add.php" class="btn btn-primary">Auto Blog Ekle Sayfasına Git</a>';
echo '<br><a href="auto_blog.php" class="btn btn-secondary">Auto Blog Ana Sayfaya Git</a>';
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}
.btn {
    display: inline-block;
    padding: 8px 16px;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin: 5px;
}
.btn:hover {
    background: #005a8b;
}
.btn-secondary {
    background: #6c757d;
}
</style>
