<?php
/**
 * Madde 5 — case_studies (Musteri Hikayeleri) tablosu (idempotent CREATE).
 *
 * Sutunlar:
 *   id              INT AUTO_INCREMENT PRIMARY KEY
 *   slug            VARCHAR(190) UNIQUE
 *   baslik          VARCHAR(200)         — UI baslik
 *   ozet            VARCHAR(300)         — kart ozeti / meta description fallback
 *   icerik          MEDIUMTEXT           — uzun anlatim (HTML)
 *   musteri_ad      VARCHAR(120)         — musteri adi (kisaltilabilir)
 *   musteri_yorumu  TEXT                 — Review schema icin alintilanir
 *   puan            DECIMAL(2,1)         — 1.0 - 5.0 (review rating)
 *   kalkis_il       VARCHAR(60)
 *   varis_il        VARCHAR(60)
 *   ev_tipi         VARCHAR(40)          — '2+1', '3+1', 'Ofis' vb.
 *   tasima_tarihi   DATE
 *   fiyat_araligi   VARCHAR(60)          — '8.000-12.000 TL' gibi
 *   gorsel          VARCHAR(255)         — opsiyonel ana gorsel
 *   meta_title      VARCHAR(180)
 *   meta_description VARCHAR(255)
 *   status          TINYINT(1) NOT NULL DEFAULT 1
 *   created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 *   updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 */

declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
require __DIR__ . '/../config/db.php';

$exists = $conn->query("SHOW TABLES LIKE 'case_studies'");
if ($exists && $exists->num_rows > 0) {
    echo "skip: case_studies tablosu zaten mevcut.\n";
} else {
    $sql = "CREATE TABLE case_studies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(190) NOT NULL,
        baslik VARCHAR(200) NOT NULL,
        ozet VARCHAR(300) NULL,
        icerik MEDIUMTEXT NULL,
        musteri_ad VARCHAR(120) NULL,
        musteri_yorumu TEXT NULL,
        puan DECIMAL(2,1) NULL DEFAULT 5.0,
        kalkis_il VARCHAR(60) NULL,
        varis_il VARCHAR(60) NULL,
        ev_tipi VARCHAR(40) NULL,
        tasima_tarihi DATE NULL,
        fiyat_araligi VARCHAR(60) NULL,
        gorsel VARCHAR(255) NULL,
        meta_title VARCHAR(180) NULL,
        meta_description VARCHAR(255) NULL,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_slug (slug),
        KEY idx_status_created (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "OK: case_studies tablosu olusturuldu.\n";
    } else {
        echo "HATA: " . $conn->error . "\n";
        exit(1);
    }
}

$count = 0;
$r = $conn->query("SELECT COUNT(*) c FROM case_studies");
if ($r && ($x = $r->fetch_assoc())) { $count = (int) $x['c']; }
echo "Mevcut kayit sayisi: $count\n";

if ($count === 0) {
    $samples = [
        [
            'slug' => 'izmir-bornova-istanbul-kadikoy-3-1-tasima',
            'baslik' => 'İzmir Bornova\'dan İstanbul Kadıköy\'e 3+1 Ev Taşıma',
            'ozet' => 'Bornova\'dan Kadıköy\'e iki çocuklu bir ailenin ev eşyası, asansörlü apartmandan asansörsüz binaya 26 saatte sigortalı şekilde teslim edildi.',
            'icerik' => "<h2>Müşteri Talebi</h2>\n<p>Mehmet Bey, Bornova\'da yaşayan iki çocuklu bir ailenin evini İstanbul Kadıköy\'e taşıtmak istedi. Hassas eşyalar (yemek takımı, çocuk piyanosu, akvaryum) ve montajlı bir gardırop bulunuyordu.</p>\n<h2>Ekspertiz Süreci</h2>\n<p>Ücretsiz ekspertizimiz Bornova\'daki dairede yapıldı. 36 m³ hacim, 1 piyano, 1 akvaryum (boşaltılarak), 2 kişilik yatak başlığı (sökmesiz) tespit edildi. Yazılı teklif aynı gün iletildi.</p>\n<h2>Taşıma Günü</h2>\n<p>Sabah 07:00\'de Bornova\'daki adrese 5 kişilik kadrolu ekibimiz, 1 büyük tır ve asansör destek aracıyla ulaştı. Ambalajlama 4 saatte tamamlandı; piyano için özel pamuklu kılıf, akvaryum için darbe emici köpük kullanıldı.</p>\n<h2>İstanbul Teslimi</h2>\n<p>Aynı tır 26 saat sonra Kadıköy adresine ulaştı. Asansörsüz 4. kata yük, taşıyıcı kayışlarla halatlamadan, merdivenden ekiple çıkarıldı. Eşyaların yerleştirilmesi ve gardırop montajı tamamlandığında saat 19:00 olmuştu.</p>\n<h2>Sonuç</h2>\n<p>Hiçbir parça hasar görmedi, tüm süreç sigorta kapsamındaydı. Müşteriden 5 yıldız puan alındı.</p>",
            'musteri_ad' => 'Mehmet K.',
            'musteri_yorumu' => 'Bornova\'dan Kadıköy\'e taşımak büyük bir karardı. Ekip sabah erken geldi, hiçbir eşyamız zarar görmedi, piyano bile hasarsız ulaştı. Yazılı fiyat aynen geçerli oldu, gizli bir maliyet çıkmadı. Tavsiye ederim.',
            'puan' => 5.0,
            'kalkis_il' => 'İzmir',
            'varis_il' => 'İstanbul',
            'ev_tipi' => '3+1',
            'tasima_tarihi' => '2025-09-12',
            'fiyat_araligi' => '38.000 - 45.000 TL',
            'meta_title' => 'İzmir Bornova → İstanbul Kadıköy 3+1 Taşıma | Müşteri Hikayesi',
            'meta_description' => 'İzmir Bornova\'dan İstanbul Kadıköy\'e sigortalı, hasarsız ve zamanında 3+1 ev taşıma hikayesi. Yazılı teklif, kadrolu ekip, gerçek müşteri yorumu.',
        ],
        [
            'slug' => 'karsiyaka-buca-2-1-asansorlu-tasima',
            'baslik' => 'Karşıyaka\'dan Buca\'ya 2+1 Asansörlü Şehir İçi Taşıma',
            'ozet' => 'Karşıyaka\'da 5. kattan Buca\'da 2. kata, asansör destekli, tek günde sigortalı şehir içi taşıma örneği.',
            'icerik' => "<h2>İhtiyaç</h2>\n<p>Selin Hanım, Karşıyaka Bostanlı\'daki 2+1 dairesini Buca Şirinyer\'e taşıtmak istedi. Çıkış adresi 5. kat ancak asansör küçük; bu nedenle dış asansör desteği gerekiyordu.</p>\n<h2>Çözüm</h2>\n<p>Ekspertizde 18 m³ hacim ölçüldü. Buzdolabı, çamaşır makinesi ve LCD TV için ayrı koruyucu paketleme planlandı. Kalkış adresine 12 metrelik dış asansör konumlandırıldı; eşyalar 2 saatte indirildi.</p>\n<h2>Sonuç</h2>\n<p>Aynı gün öğleden sonra Buca adresine teslimat yapıldı. Eşyalar yerleştirildi, mobilyalar tekrar monte edildi. Toplam süre 8 saat oldu.</p>",
            'musteri_ad' => 'Selin Y.',
            'musteri_yorumu' => 'Aynı gün içinde tüm taşımam tamamlandı. Asansör desteği sayesinde merdivenle uğraşmadık. Buzdolabımı ve çamaşır makinemi özenle paketlediler.',
            'puan' => 5.0,
            'kalkis_il' => 'İzmir',
            'varis_il' => 'İzmir',
            'ev_tipi' => '2+1',
            'tasima_tarihi' => '2025-10-04',
            'fiyat_araligi' => '6.500 - 8.500 TL',
            'meta_title' => 'Karşıyaka → Buca 2+1 Asansörlü Taşıma | Müşteri Hikayesi',
            'meta_description' => 'Karşıyaka\'dan Buca\'ya 5. kattan 2. kata, dış asansör destekli, aynı gün şehir içi ev taşıma hikayesi. Sigortalı, profesyonel, gerçek müşteri yorumu.',
        ],
        [
            'slug' => 'konak-ofis-tasima-25-personel',
            'baslik' => 'Konak\'tan Bayraklı\'ya 25 Personellik Ofis Taşıma',
            'ozet' => 'Hafta sonu 36 saatte tamamlanan, kesintisiz iş günü güvencesi verdiğimiz kurumsal ofis taşıma örneği.',
            'icerik' => "<h2>Talep</h2>\n<p>Mali müşavirlik şirketi, Konak\'taki ofisini Bayraklı\'daki yeni binaya taşımak istedi. 25 çalışan, 30+ masa, 60 sandalye, 2 toplantı masası, 8 evrak dolabı, sunucu odası içeriyordu. Pazartesi sabahı çalışma kesintisiz başlamalıydı.</p>\n<h2>Plan</h2>\n<p>Cuma gece 19:00\'da paketleme, Cumartesi tüm gün taşıma, Pazar yerleştirme + IT kurulumu planlandı. Sunucu odası özel hassas paketleme + ayrı araç ile Cuma gece taşındı.</p>\n<h2>Uygulama</h2>\n<p>Her masa numaralandırılmış kutuyla işaretlendi; kişisel eşyalar çalışan adına etiketlendi. Pazar akşamı tüm masalar yerine yerleştirildi, network kabloları çekildi.</p>\n<h2>Sonuç</h2>\n<p>Pazartesi 09:00\'da firma tam kadro çalışmaya başladı. Hiçbir sandalye, evrak ya da bilgisayar zarar görmedi.</p>",
            'musteri_ad' => 'Onur D. (Şirket Operasyon Müdürü)',
            'musteri_yorumu' => 'Hafta sonu içinde 25 personellik ofisi sıfır iş günü kaybıyla taşıdılar. Sunucu odası dahil hiçbir cihazda problem olmadı. Kurumsal taşımacılıkta tavsiye ederim.',
            'puan' => 5.0,
            'kalkis_il' => 'İzmir',
            'varis_il' => 'İzmir',
            'ev_tipi' => 'Ofis',
            'tasima_tarihi' => '2025-11-09',
            'fiyat_araligi' => '85.000 - 110.000 TL',
            'meta_title' => 'Konak → Bayraklı 25 Personellik Ofis Taşıma | Müşteri Hikayesi',
            'meta_description' => '25 personellik ofisin Konak\'tan Bayraklı\'ya hafta sonu içinde, sıfır iş günü kaybı ile taşınma hikayesi. Sunucu odası dahil sigortalı kurumsal taşıma.',
        ],
    ];

    $stmt = $conn->prepare("INSERT INTO case_studies
        (slug, baslik, ozet, icerik, musteri_ad, musteri_yorumu, puan, kalkis_il, varis_il, ev_tipi, tasima_tarihi, fiyat_araligi, meta_title, meta_description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

    foreach ($samples as $s) {
        $stmt->bind_param(
            'ssssssdsssssss',
            $s['slug'], $s['baslik'], $s['ozet'], $s['icerik'],
            $s['musteri_ad'], $s['musteri_yorumu'], $s['puan'],
            $s['kalkis_il'], $s['varis_il'], $s['ev_tipi'],
            $s['tasima_tarihi'], $s['fiyat_araligi'],
            $s['meta_title'], $s['meta_description']
        );
        if ($stmt->execute()) {
            echo "OK: " . $s['slug'] . " eklendi (id=" . $stmt->insert_id . ")\n";
        } else {
            echo "HATA: " . $s['slug'] . " -> " . $stmt->error . "\n";
        }
    }
    $stmt->close();
} else {
    echo "Sample veri ekleme atlandi (mevcut kayit var).\n";
}

echo "\nFinal:\n";
$r = $conn->query("SELECT id, slug, baslik, status FROM case_studies ORDER BY id");
while ($x = $r->fetch_assoc()) {
    echo "  #{$x['id']} | {$x['slug']} | {$x['baslik']} | status={$x['status']}\n";
}
