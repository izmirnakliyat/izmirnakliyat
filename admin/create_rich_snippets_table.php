<?php
require_once __DIR__ . '/includes/require_admin_web.php';

// Rich snippets tablosunu oluştur
$create_table_sql = "CREATE TABLE IF NOT EXISTS `rich_snippets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type` varchar(100) NOT NULL COMMENT 'Schema türü (Organization, LocalBusiness, Article, vb.)',
    `name` varchar(255) NOT NULL COMMENT 'Snippet adı',
    `data` text NOT NULL COMMENT 'JSON-LD schema verisi',
    `page_type` varchar(50) NOT NULL COMMENT 'Hangi sayfa türünde gösterilecek',
    `status` tinyint(1) DEFAULT 1 COMMENT '1: aktif, 0: pasif',
    `created_at` datetime DEFAULT current_timestamp(),
    `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_page_type_status` (`page_type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rich snippets / structured data yönetimi';";

if ($conn->query($create_table_sql) === TRUE) {
    echo "Rich snippets tablosu başarıyla oluşturuldu!<br>";
} else {
    echo "Tablo oluşturma hatası: " . $conn->error . "<br>";
}

// Örnek organizasyon verisi ekle
$sample_organization = '{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "MY Nakliyat",
  "url": "https://www.mynakliyat.com.tr",
  "logo": "https://www.mynakliyat.com.tr/uploads/settings/logo.png",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+90-XXX-XXX-XXXX",
    "contactType": "customer service"
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "İzmir",
    "addressCountry": "TR"
  },
  "description": "MY Nakliyat; evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya taşıma ve şehirler arası nakliyat hizmetleri sunan İzmir merkezli taşıma firmasıdır."
}';

$stmt = $conn->prepare("INSERT INTO rich_snippets (type, name, data, page_type, status) VALUES (?, ?, ?, ?, ?)");
$type = 'Organization';
$name = 'MY Nakliyat - Ana Organizasyon';
$page_type = 'global';
$status = 1;

$stmt->bind_param("ssssi", $type, $name, $sample_organization, $page_type, $status);

if ($stmt->execute()) {
    echo "Örnek organizasyon verisi eklendi!<br>";
} else {
    echo "Örnek veri ekleme hatası: " . $stmt->error . "<br>";
}

echo "<br><a href='rich_snippets.php'>Rich Snippets yönetimine git</a>";
?>

