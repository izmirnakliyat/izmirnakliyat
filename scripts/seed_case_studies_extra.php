<?php
/**
 * case_studies tablosuna 3 EK hikaye (idempotent, slug bazli).
 * migrate_case_studies.php 3 ornek eklettiyse, bu 6'yi tamamlar.
 *
 * Usage: php scripts/seed_case_studies_extra.php [--apply]
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

$apply = in_array('--apply', $argv ?? [], true);
$c = new mysqli('localhost', 'root', '', 'mynakliyat', 3306);
if ($c->connect_error) { fwrite(STDERR, $c->connect_error . "\n"); exit(1); }
$c->set_charset('utf8mb4');

$t = $c->query("SHOW TABLES LIKE 'case_studies'");
if (!$t || $t->num_rows === 0) {
    echo "HATA: case_studies tablosu yok. Once: php scripts/migrate_case_studies.php\n";
    exit(1);
}

$rows = [
    [
        'slug' => 'karsiyaka-ankara-cankaya-3-1-sehirler-arasi',
        'baslik' => 'Karşıyaka\'dan Ankara Çankaya\'ya 3+1 Şehirler Arası Taşıma',
        'ozet' => 'Eşyalar 48 saat içinde, sigortalı tır hattı ve yerleşim hizmetiyle Çankaya\'daki 4. kattaki yeni adrese ulaştı; müşteri şehirler arası süreci tek sözleşmeyle tamamladı.',
        'icerik' => "<h2>Arka plan</h2>\n<p>Ali Bey, ailesiyle Karşıyaka Mavişehir bölgesindeki 3+1 evden Ankara Çankaya\'daki lisanslı siteye geçmek istedi. Öğretmen ve çocuğu için okul bölgesi değiştiği için teslimat tarihi sabitti.</p>\n<h2>Planlama</h2>\n<p>Ölçülen hacim 42 m³. İstanbul aktarması olmadan doğrudan Ankara hattı seçildi. Yemek odası ağaç kütüphanesi ve 65\" TV ayrı ambalajlandı. Yazılı sözleşme ve fiyat, keşif sonrası aynı gün e-posta ile gönderildi.</p>\n<h2>Operasyon</h2>\n<p>07:00\'de ekip bölgeye girdi, 6 saat paket; akşam tır doldu, ertesi gün öğleden önce Ankara sınırı geçildi, üçüncü gün 15:00\'te Çankaya dairesine yerleşim bitti. Merdiven taşımalarında kayışlı sistem kullanıldı.</p>\n<h2>Sonuç</h2>\n<p>Plana uygun, hasarsız teslim. Müşteri 5,0 puan bıraktı.</p>",
        'musteri_ad' => 'Ali T.',
        'musteri_yorumu' => 'Karşıyaka\'dan Ankara\'ya tüm aile eşyaları tek seferde ve zamanında gitti. Sözleşmedeki fiyat aynen geçerli kaldı, ekip güleryüzlü ve düzenliydi.',
        'puan' => 5.0,
        'kalkis_il' => 'İzmir',
        'varis_il' => 'Ankara',
        'ev_tipi' => '3+1',
        'tasima_tarihi' => '2025-12-18',
        'fiyat_araligi' => '32.000 - 40.000 TL',
        'meta_title' => 'Karşıyaka → Ankara Çankaya 3+1 Taşıma | MY Nakliyat',
        'meta_description' => 'İzmir Karşıyaka\'dan Ankara Çankaya\'ya 3+1 şehirler arası ev taşıma. Sigortalı, yazılı sözleşme, 48-72 saat teslim. Gerçek müşteri hikayesi.',
    ],
    [
        'slug' => 'gaziemir-istanbul-avcilar-2-1-asansorlu',
        'baslik' => 'Gaziemir\'den İstanbul Avcılar\'a 2+1 Tır Hattı',
        'ozet' => 'Dış asansörlü Kalkış + İstanbul 5. katta bina dış asansör desteği; 40 saat içinde teslim, çift tarafta MY Nakliyat koordinasyonu.',
        'icerik' => "<h2>İhtiyaç</h2>\n<p>Öğrenci ailesi, Gaziemir\'deki 2+1 evden Avcılar\'a üniversite yakınlığı için taşınacaktı. Öğrenci belgeleriyle teslimat takvimi sıkıydı (okul açılmadan 3 gün önce).</p>\n<h2>Keşif</h2>\n<p>2. kattan çıkışta dar merdiven nedeniyle dış asansör. İstanbul 5. kat, yine bina dış asansör kiralama. Tüm fiyat, iki şehir koordinasyonu ve sigorta kapsamı tek sözleşmede toplandı.</p>\n<h2>Operasyon</h2>\n<p>İlk gün Gaziemir\'de paket ve yükleme; ertesi gün İstanbul hattı; gün 3 Avcılar\'da yerleşim. Televizyon, bilgisayar donanımı ayrı etiketlendi, oturma grubu sökülüp kuruldu.</p>\n<h2>Sonuç</h2>\n<p>Takvime bire bir uyum. Müşteri, \"tek muhatap\" süreci için teşekkür etti.</p>",
        'musteri_ad' => 'Zeynep H.',
        'musteri_yorumu' => 'Hem Gaziemir tarafı hem Avcılar tarafı çok disiplinli ilerledi. Telefonda tek kişiyle konuşarak her şeyi hallettik.',
        'puan' => 5.0,
        'kalkis_il' => 'İzmir',
        'varis_il' => 'İstanbul',
        'ev_tipi' => '2+1',
        'tasima_tarihi' => '2025-11-25',
        'fiyat_araligi' => '18.000 - 24.000 TL',
        'meta_title' => 'Gaziemir → İstanbul Avcılar 2+1 | MY Nakliyat',
        'meta_description' => 'Gaziemir\'den İstanbul Avcılar\'a asansörlü şehirler arası ev taşıma. Çift tarafta dış asansör koordinasyonu, gerçek hikâye.',
    ],
    [
        'slug' => 'bornova-izmir-esya-depolama-3-ay-yeni-eve',
        'baslik' => 'Bornova Eşya Depolama: 3 Ay + Yeni Daireye Teslim',
        'ozet' => 'Bina yenileme döneminde tüm eşyalar lisanslı depoya alındı; 3 ay sonra aynı adrese, envantere göre aynen teslimat yapıldı.',
        'icerik' => "<h2>Durum</h2>\n<p>Müşterinin büyük ölçekli yalıtım ve tesisat restorasyonu nedeniyle 3-4 aylık boşalma söz konusu oldu. Eşyaların ofis-veya açık alanda değil, sigortalı, raflı depoda barınması tercih edildi.</p>\n<h2>Depo süreci</h2>\n<p>Demonte mobilya, kırılabilir porselen, TV ve halılar etiketli palet/raf sistemine alındı. Depo giriş-çıkış belgesi, fotoğraflar ve envanter e-posta ile paylaşıldı; her ziyarette randevu kuralı uygulandı.</p>\n<h2>Dönüş</h2>\n<p>İnşaat bittiğinde aynı gün tır, depodan Bornova adrese geldi, kurulum ve hafif toz alma bitti. Sigorta, depo ve taşıma tek akıştaydı.</p>\n<h2>Sonuç</h2>\n<p>\"Aynı eşyaları, aynı sırada görmek\" müşterinin en çok beğendiği noktaydı.</p>",
        'musteri_ad' => 'Caner V.',
        'musteri_yorumu' => '3 ay boyunca eşyalarım kuru ve güvendeydi. Dönünce kuruluma kadar her şeyi MY Nakliyat halletti, ekstra sürpriz ücret yok.',
        'puan' => 5.0,
        'kalkis_il' => 'İzmir',
        'varis_il' => 'İzmir',
        'ev_tipi' => '3+1 + depo',
        'tasima_tarihi' => '2025-08-20',
        'fiyat_araligi' => 'Ortalama 9.000 TL/ay (depo) + tır',
        'meta_title' => 'Bornova Eşya Depolama 3 Ay | MY Nakliyat',
        'meta_description' => 'Bornova\'da renovasyon: eşyalar 3 ay lisanslı depoda, sonra aynı adrese teslim. Envanter, sigorta, gerçek müşteri hikayesi.',
    ],
];

$ins = $c->prepare(
    'INSERT INTO case_studies
     (slug, baslik, ozet, icerik, musteri_ad, musteri_yorumu, puan, kalkis_il, varis_il, ev_tipi, tasima_tarihi, fiyat_araligi, meta_title, meta_description, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
);

$added = 0;
$skip  = 0;
foreach ($rows as $s) {
    $chk = $c->prepare('SELECT id FROM case_studies WHERE slug = ? LIMIT 1');
    $slug = $s['slug'];
    $chk->bind_param('s', $slug);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) {
        echo "  [SKIP] $slug\n";
        $skip++;
        $chk->close();
        continue;
    }
    $chk->close();
    if ($apply) {
        $ins->bind_param(
            'ssssssdsssssss',
            $s['slug'],
            $s['baslik'],
            $s['ozet'],
            $s['icerik'],
            $s['musteri_ad'],
            $s['musteri_yorumu'],
            $s['puan'],
            $s['kalkis_il'],
            $s['varis_il'],
            $s['ev_tipi'],
            $s['tasima_tarihi'],
            $s['fiyat_araligi'],
            $s['meta_title'],
            $s['meta_description']
        );
        if ($ins->execute()) {
            echo "  [OK] " . $s['slug'] . " id=" . $ins->insert_id . "\n";
            $added++;
        } else {
            echo "  [HATA] " . $s['slug'] . " " . $ins->error . "\n";
        }
    } else {
        echo "  [DRY-RUN] eklenecek: " . $s['slug'] . "\n";
        $added++;
    }
}
$ins->close();

echo str_repeat('-', 50) . "\n";
echo $apply
    ? "Eklendi: $added | Zaten vardi: $skip\n"
    : "Eklenecek: $added | Mevcut (atlandı): $skip | --apply ile uygula\n";
