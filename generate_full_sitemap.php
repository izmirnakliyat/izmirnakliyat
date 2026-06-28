<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/sitemap_build.php';

$site_url = rtrim(SITE_URL, '/');
$projectRoot = __DIR__;

echo "<h2>🗺️ Tam Sitemap Oluşturuluyor... (Görsel + LLM + indeks)</h2>";

$built = sitemap_build_main_urlset($conn, $site_url, []);
$sitemap_content = $built['xml'];
$url_count = $built['url_count'];

echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "✅ URL seti üretildi: <strong>{$url_count}</strong> benzersiz adres (çiftler elendi).";
echo "</div>";

// Sitemap dosyasını kaydet
$sitemap_path = $projectRoot . '/sitemap.xml';
$sitemap_index_path = $projectRoot . '/sitemap-index.xml';
// Dosya izinlerini aşmak için deneme
if (file_exists($sitemap_path) && !is_writable($sitemap_path)) {
    chmod($sitemap_path, 0755);
}

$success = file_put_contents($sitemap_path, $sitemap_content);
$indexXml = sitemap_build_index_xml($site_url, $projectRoot);
$indexOk = file_put_contents($sitemap_index_path, $indexXml);

if ($success) {
    echo "<div style='background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Sitemap Başarıyla Oluşturuldu!</h3>";
    echo "<strong>📊 Toplam URL:</strong> $url_count<br>";
    echo "<strong>📁 Dosyalar:</strong> sitemap.xml" . ($indexOk ? ", sitemap-index.xml" : "") . "<br>";
    echo "<strong>📏 Boyut:</strong> " . number_format(strlen($sitemap_content)) . " karakter<br>";
    echo "<strong>🕐 Oluşturma:</strong> " . date('Y-m-d H:i:s') . "<br>";
    echo "<div style='margin-top: 15px;'>";
    echo "<a href='sitemap.xml' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🗺️ sitemap.xml</a>";
    echo "<a href='sitemap-index.xml' target='_blank' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📑 sitemap-index</a>";
    echo "<a href='https://www.google.com/ping?sitemap=" . urlencode($site_url . '/sitemap-index.xml') . "' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📡 Google ping (indeks)</a>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "❌ Sitemap kaydedilemedi! Dosya izinlerini kontrol edin.";
    echo "</div>";
}
?>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }
</style>