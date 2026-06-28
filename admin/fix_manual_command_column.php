<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// Manuel komut alanını auto_blog_settings tablosuna ekleyen script
echo "<h2>🔧 Auto Blog Tablosu Düzeltme</h2>";

try {
    // Bağlantı kontrolü
    if (!$conn) {
        throw new Exception("Database bağlantısı yok!");
    }
    
    echo "✅ Database bağlantısı başarılı<br>";
    
    // Tabloyu kontrol et
    $check_table = $conn->query("SHOW TABLES LIKE 'auto_blog_settings'");
    if ($check_table->num_rows == 0) {
        echo "❌ auto_blog_settings tablosu bulunamadı!<br>";
        
        // Tablo yoksa oluştur
        $create_table = "CREATE TABLE `auto_blog_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `category_id` int(11) NOT NULL,
            `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `manual_command` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
            `cover_image` varchar(255) DEFAULT NULL,
            `min_words` int(11) DEFAULT 300,
            `max_words` int(11) DEFAULT 800,
            `post_count_per_period` int(11) DEFAULT 1,
            `period_type` enum('daily','weekly','hourly') DEFAULT 'daily',
            `post_time` varchar(100) DEFAULT '10:00',
            `active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($create_table)) {
            echo "✅ auto_blog_settings tablosu oluşturuldu!<br>";
        } else {
            throw new Exception("Tablo oluşturma hatası: " . $conn->error);
        }
    } else {
        echo "✅ auto_blog_settings tablosu mevcut<br>";
        
        // manual_command alanını kontrol et
        $check_column = $conn->query("SHOW COLUMNS FROM auto_blog_settings LIKE 'manual_command'");
        
        if ($check_column->num_rows == 0) {
            echo "⚠️ manual_command alanı bulunamadı, ekleniyor...<br>";
            
            // Alanı ekle
            $add_column = "ALTER TABLE auto_blog_settings ADD COLUMN `manual_command` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL AFTER `keywords`";
            
            if ($conn->query($add_column)) {
                echo "✅ manual_command alanı başarıyla eklendi!<br>";
            } else {
                throw new Exception("Alan ekleme hatası: " . $conn->error);
            }
        } else {
            echo "ℹ️ manual_command alanı zaten mevcut<br>";
        }
    }
    
    // Tablo yapısını kontrol et
    echo "<h3>📋 Güncel Tablo Yapısı:</h3>";
    $columns = $conn->query("SHOW COLUMNS FROM auto_blog_settings");
    
    if ($columns && $columns->num_rows > 0) {
        echo "<table style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Alan Adı</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Veri Tipi</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Null</th>";
        echo "<th style='border: 1px solid #dee2e6; padding: 8px;'>Default</th>";
        echo "</tr>";
        
        while ($column = $columns->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . htmlspecialchars($column['Default']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test sorgusu
    echo "<h3>🧪 Test Sorgusu:</h3>";
    $test_query = "SELECT COUNT(*) as total FROM auto_blog_settings";
    $test_result = $conn->query($test_query);
    
    if ($test_result) {
        $row = $test_result->fetch_assoc();
        echo "✅ Tabloda toplam <strong>" . $row['total'] . "</strong> kayıt var<br>";
    } else {
        echo "❌ Test sorgusu başarısız: " . $conn->error . "<br>";
    }
    
    echo "<br><div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>🎉 İşlem Tamamlandı!</strong><br>";
    echo "Manuel komut özelliği artık kullanılabilir. Auto blog ekleme sayfasına gidebilirsiniz.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo '<br><div style="margin: 20px 0;">';
echo '<a href="auto_blog_add.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">Auto Blog Ekle</a>';
echo '<a href="auto_blog.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Auto Blog Listesi</a>';
echo '</div>';
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    margin: 20px;
    line-height: 1.6;
    background: #f8f9fa;
}
h2, h3 {
    color: #333;
    margin-top: 30px;
}
table {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    background: white;
}
</style>
