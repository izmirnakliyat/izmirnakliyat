<?php
require_once __DIR__ . '/includes/require_admin_web.php';

echo "<h1>📝 Sayfalara Meta Açıklaması ve Anahtar Kelimeler Ekleme</h1>";
echo "<p>Bu script pages tablosuna meta_description ve meta_keywords kolonlarını ekler ve mevcut sayfalar için otomatik meta oluşturur.</p>";

try {
    // 1. Kolonları ekle
    echo "<h3>1. Veritabanı Kolonları Ekleniyor...</h3>";
    
    $alter_sql = "ALTER TABLE pages 
                  ADD COLUMN IF NOT EXISTS meta_description TEXT,
                  ADD COLUMN IF NOT EXISTS meta_keywords VARCHAR(500)";
    
    if ($conn->query($alter_sql)) {
        echo "<p style='color: green;'>✅ Meta kolonları başarıyla eklendi/kontrol edildi.</p>";
    } else {
        echo "<p style='color: red;'>❌ Kolon ekleme hatası: " . $conn->error . "</p>";
    }
    
    // 2. Mevcut sayfaları getir
    echo "<h3>2. Mevcut Sayfalar için Meta Bilgileri Oluşturuluyor...</h3>";
    
    $pages_result = $conn->query("SELECT id, title, content, slug FROM pages WHERE (meta_description IS NULL OR meta_description = '') OR (meta_keywords IS NULL OR meta_keywords = '')");
    
    if ($pages_result && $pages_result->num_rows > 0) {
        while ($page = $pages_result->fetch_assoc()) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>📄 " . htmlspecialchars($page['title']) . "</h4>";
            
            // Meta açıklaması oluştur
            $meta_description = '';
            if (!empty($page['content'])) {
                // HTML etiketlerini temizle
                $clean_content = strip_tags($page['content']);
                // İlk 150 karakteri al
                $meta_description = substr($clean_content, 0, 150);
                if (strlen($clean_content) > 150) {
                    $meta_description .= '...';
                }
            }
            
            // Boşsa varsayılan açıklama
            if (empty($meta_description)) {
                $meta_description = "MY Nakliyat - " . $page['title'] . " hakkında detaylı bilgiler. Güvenilir marka ödüllü tek firma.";
            }
            
            // Anahtar kelimeleri oluştur
            $meta_keywords = [];
            
            // Başlıktan anahtar kelimeler
            $title_words = explode(' ', strtolower($page['title']));
            $stop_words = ['ve', 'ile', 'için', 'den', 'dan', 'in', 'un', 'ün', 'a', 'e', 'i', 'o', 'u', 'ı', 'ü', 'ö', 'bir', 'bu', 'şu', 'o'];
            
            foreach ($title_words as $word) {
                $word = trim($word, '.,!?;:');
                if (strlen($word) > 2 && !in_array($word, $stop_words)) {
                    $meta_keywords[] = $word;
                }
            }
            
            // Sabit anahtar kelimeler
            $base_keywords = ['my nakliyat', 'nakliyat', 'evden eve nakliyat', 'güvenilir nakliyat', 'izmir nakliyat'];
            $meta_keywords = array_merge($meta_keywords, $base_keywords);
            
            // Sayfa özel anahtar kelimeleri
            $slug = $page['slug'];
            switch (true) {
                case strpos($slug, 'hakkimizda') !== false:
                case strpos($slug, 'hakkinda') !== false:
                    $meta_keywords = array_merge($meta_keywords, ['hakkımızda', 'kurumsal', 'şirket', 'firma bilgileri']);
                    break;
                case strpos($slug, 'iletisim') !== false:
                case strpos($slug, 'contact') !== false:
                    $meta_keywords = array_merge($meta_keywords, ['iletişim', 'adres', 'telefon', 'ulaşım']);
                    break;
                case strpos($slug, 'hizmet') !== false:
                case strpos($slug, 'service') !== false:
                    $meta_keywords = array_merge($meta_keywords, ['hizmetler', 'ofis taşıma', 'asansör kiralama']);
                    break;
                case strpos($slug, 'fiyat') !== false:
                case strpos($slug, 'price') !== false:
                    $meta_keywords = array_merge($meta_keywords, ['fiyat', 'ücret', 'tarife', 'hesaplama']);
                    break;
                case strpos($slug, 'galeri') !== false:
                case strpos($slug, 'gallery') !== false:
                    $meta_keywords = array_merge($meta_keywords, ['galeri', 'fotoğraflar', 'resimler', 'çalışmalarımız']);
                    break;
                default:
                    $meta_keywords = array_merge($meta_keywords, ['profesyonel', 'kaliteli', 'güvenli']);
                    break;
            }
            
            // Tekrarları kaldır ve birleştir
            $meta_keywords = array_unique($meta_keywords);
            $meta_keywords_string = implode(', ', array_slice($meta_keywords, 0, 10)); // Maksimum 10 anahtar kelime
            
            // Veritabanını güncelle
            $update_sql = "UPDATE pages SET meta_description = ?, meta_keywords = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $meta_description, $meta_keywords_string, $page['id']);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Meta bilgileri başarıyla güncellendi</p>";
                echo "<strong>Meta Açıklaması:</strong> " . htmlspecialchars($meta_description) . "<br>";
                echo "<strong>Anahtar Kelimeler:</strong> " . htmlspecialchars($meta_keywords_string) . "<br>";
            } else {
                echo "<p style='color: red;'>❌ Güncelleme hatası: " . $stmt->error . "</p>";
            }
            
            echo "</div>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Güncellenmesi gereken sayfa bulunamadı veya tüm sayfalar zaten meta bilgilerine sahip.</p>";
    }
    
    echo "<h3>3. İşlem Tamamlandı!</h3>";
    echo "<p style='color: green; font-weight: bold;'>✅ Tüm meta bilgileri başarıyla eklendi/güncellendi.</p>";
    echo "<p><a href='pages.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Sayfa Yönetimine Dön</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}
?> 