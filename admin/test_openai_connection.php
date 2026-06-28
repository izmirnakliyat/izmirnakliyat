<?php
// OpenAI bağlantı testi - Localhost vs Canlı Sunucu
require_once '../config/db.php';
require_once 'includes/auto_blog_functions.php';

// HTML çıktısı için
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OpenAI Bağlantı Testi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .test-item { margin: 10px 0; padding: 8px; border-left: 3px solid #ddd; background: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 OpenAI API Bağlantı Testi</h1>
        <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i:s'); ?></p>
        
        <?php
        echo "<div class='section'>";
        echo "<h2>📋 Sistem Bilgileri</h2>";
        
        // 1. Temel sistem bilgileri
        echo "<div class='test-item'>";
        echo "<strong>🔧 Sunucu Bilgileri:</strong><br>";
        echo "PHP Version: " . phpversion() . "<br>";
        echo "Host: " . $_SERVER['HTTP_HOST'] . "<br>";
        echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor') . "<br>";
        echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
        echo "</div>";
        
        // 2. CURL kontrolü
        echo "<div class='test-item'>";
        if (function_exists('curl_init')) {
            echo "<span class='success'>✅ CURL Extension: Yüklü</span><br>";
            $curl_version = curl_version();
            echo "CURL Version: " . $curl_version['version'] . "<br>";
            echo "SSL Version: " . $curl_version['ssl_version'] . "<br>";
            echo "Protocols: " . implode(', ', $curl_version['protocols']) . "<br>";
        } else {
            echo "<span class='error'>❌ CURL Extension: Yüklü değil!</span><br>";
        }
        echo "</div>";
        
        // 3. OpenSSL kontrolü
        echo "<div class='test-item'>";
        if (extension_loaded('openssl')) {
            echo "<span class='success'>✅ OpenSSL Extension: Yüklü</span><br>";
            echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "<br>";
        } else {
            echo "<span class='error'>❌ OpenSSL Extension: Yüklü değil!</span><br>";
        }
        echo "</div>";
        
        echo "</div>";
        
        // 4. API Key kontrolü
        echo "<div class='section'>";
        echo "<h2>🔑 API Key Kontrolü</h2>";
        
        $api_key = get_openai_api_key();
        if (!$api_key) {
            echo "<div class='test-item'><span class='error'>❌ OpenAI API Key bulunamadı!</span></div>";
            echo "<p><strong>Çözüm:</strong> Admin panelinden OpenAI API Key'inizi girin.</p>";
            echo "</div></div></body></html>";
            exit;
        }
        
        echo "<div class='test-item'>";
        echo "<span class='success'>✅ API Key bulundu</span><br>";
        echo "Key Preview: " . substr($api_key, 0, 10) . "..." . substr($api_key, -4) . "<br>";
        echo "Key Length: " . strlen($api_key) . " karakter<br>";
        echo "</div>";
        echo "</div>";
        
        // 5. Ağ bağlantısı testi
        echo "<div class='section'>";
        echo "<h2>🌐 Ağ Bağlantısı Testi</h2>";
        
        // Basit HTTP testi
        echo "<div class='test-item'>";
        echo "<strong>🔍 HTTP Bağlantı Testi:</strong><br>";
        
        $test_url = "https://httpbin.org/get";
        $ch_test = curl_init($test_url);
        curl_setopt($ch_test, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_test, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch_test, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_test, CURLOPT_USERAGENT, 'MyNakliyat-Test/1.0');
        
        $test_response = curl_exec($ch_test);
        $test_http_code = curl_getinfo($ch_test, CURLINFO_HTTP_CODE);
        $test_error = curl_error($ch_test);
        curl_close($ch_test);
        
        if ($test_http_code === 200 && !$test_error) {
            echo "<span class='success'>✅ HTTP Bağlantısı: Başarılı</span><br>";
        } else {
            echo "<span class='error'>❌ HTTP Bağlantısı: Başarısız</span><br>";
            echo "HTTP Code: $test_http_code<br>";
            echo "Error: $test_error<br>";
        }
        echo "</div>";
        
        echo "</div>";
        
        // 6. OpenAI API testi
        echo "<div class='section'>";
        echo "<h2>🤖 OpenAI API Testi</h2>";
        
        echo "<div class='test-item'>";
        echo "<strong>📡 API Endpoint Test:</strong><br>";
        
        $api_url = "https://api.openai.com/v1/models";
        $ch_api = curl_init($api_url);
        curl_setopt($ch_api, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_api, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch_api, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch_api, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch_api, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch_api, CURLOPT_USERAGENT, 'MyNakliyat-Test/1.0');
        curl_setopt($ch_api, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        
        $api_response = curl_exec($ch_api);
        $api_http_code = curl_getinfo($ch_api, CURLINFO_HTTP_CODE);
        $api_error = curl_error($ch_api);
        $api_info = curl_getinfo($ch_api);
        curl_close($ch_api);
        
        echo "HTTP Code: $api_http_code<br>";
        echo "Connect Time: " . round($api_info['connect_time'], 2) . " saniye<br>";
        echo "Total Time: " . round($api_info['total_time'], 2) . " saniye<br>";
        
        if ($api_http_code === 200 && !$api_error) {
            echo "<span class='success'>✅ OpenAI API: Erişilebilir</span><br>";
            $models_data = json_decode($api_response, true);
            if (isset($models_data['data'])) {
                echo "Kullanılabilir Model Sayısı: " . count($models_data['data']) . "<br>";
            }
        } else {
            echo "<span class='error'>❌ OpenAI API: Erişim hatası</span><br>";
            if ($api_error) {
                echo "CURL Error: $api_error<br>";
            }
            if ($api_response) {
                echo "API Response: " . substr($api_response, 0, 200) . "...<br>";
            }
        }
        echo "</div>";
        
        // 7. Chat Completions testi
        if ($api_http_code === 200) {
            echo "<div class='test-item'>";
            echo "<strong>💬 Chat Completions Test:</strong><br>";
            
            $chat_ch = curl_init('https://api.openai.com/v1/chat/completions');
            $chat_data = [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => 'Test mesajı: Bu bir bağlantı testidir. Lütfen "Bağlantı başarılı!" diye kısaca yanıtlayın.']
                ],
                'max_tokens' => 50,
                'temperature' => 0.1
            ];
            
            curl_setopt($chat_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chat_ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($chat_ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($chat_ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($chat_ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($chat_ch, CURLOPT_USERAGENT, 'MyNakliyat-Test/1.0');
            curl_setopt($chat_ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ]);
            curl_setopt($chat_ch, CURLOPT_POSTFIELDS, json_encode($chat_data));
            
            $chat_response = curl_exec($chat_ch);
            $chat_http_code = curl_getinfo($chat_ch, CURLINFO_HTTP_CODE);
            $chat_error = curl_error($chat_ch);
            curl_close($chat_ch);
            
            echo "HTTP Code: $chat_http_code<br>";
            
            if ($chat_http_code === 200 && !$chat_error) {
                echo "<span class='success'>✅ Chat API: Çalışıyor</span><br>";
                $chat_data_response = json_decode($chat_response, true);
                if (isset($chat_data_response['choices'][0]['message']['content'])) {
                    echo "AI Yanıtı: " . $chat_data_response['choices'][0]['message']['content'] . "<br>";
                    echo "<span class='success'>🎉 Otomatik blog sistemi kullanıma hazır!</span><br>";
                }
            } else {
                echo "<span class='error'>❌ Chat API: Hata</span><br>";
                if ($chat_error) {
                    echo "CURL Error: $chat_error<br>";
                }
                echo "Response: " . substr($chat_response, 0, 300) . "...<br>";
            }
            echo "</div>";
        }
        
        echo "</div>";
        
        // 8. Tanı ve Öneriler
        echo "<div class='section'>";
        echo "<h2>🔍 Tanı ve Öneriler</h2>";
        
        if ($api_http_code !== 200 || $api_error) {
            echo "<div class='test-item error'>";
            echo "<h3>❌ Sorun Tespit Edildi</h3>";
            echo "<strong>Olası Nedenler:</strong><br>";
            echo "• Hosting provider external API çağrılarını engelliyor<br>";
            echo "• Firewall api.openai.com domain'ini blokluyor<br>";
            echo "• SSL sertifika sorunu<br>";
            echo "• API key hatalı veya süresi dolmuş<br>";
            echo "• Sunucu CURL ayarları yetersiz<br><br>";
            
            echo "<strong>Çözüm Önerileri:</strong><br>";
            echo "1. Hosting provider'ınıza external API izinleri sorun<br>";
            echo "2. OpenAI API key'inizi yenileyin<br>";
            echo "3. Sunucu firewall ayarlarını kontrol edin<br>";
            echo "4. PHP CURL ayarlarını güncelleyin<br>";
            echo "</div>";
        } else {
            echo "<div class='test-item success'>";
            echo "<h3>✅ Sistem Hazır</h3>";
            echo "OpenAI API bağlantısı başarılı! Otomatik blog sistemi çalışmaya hazır.";
            echo "</div>";
        }
        
        echo "</div>";
        
        // Debug bilgileri
        echo "<div class='section'>";
        echo "<h2>🐛 Debug Bilgileri</h2>";
        echo "<pre>";
        echo "=== CURL Info ===\n";
        print_r($api_info);
        echo "\n=== PHP Info ===\n";
        echo "PHP Version: " . phpversion() . "\n";
        echo "CURL Version: " . curl_version()['version'] . "\n";
        echo "OpenSSL: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "\n";
        echo "Allow URL fopen: " . (ini_get('allow_url_fopen') ? 'Yes' : 'No') . "\n";
        echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Bilinmiyor') . "\n";
        echo "</pre>";
        echo "</div>";
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 4px;">
            <strong>ℹ️ Not:</strong> Bu test localhost ve canlı sunucu arasındaki farkları tespit etmek için tasarlanmıştır. 
            Eğer localhost'ta çalışıyor ama canlı sunucuda çalışmıyorsa, yukarıdaki hata mesajları sorunu çözmenize yardımcı olacaktır.
        </div>
    </div>
</body>
</html>






