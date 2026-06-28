<?php
declare(strict_types=1);

/**
 * FAZ 1 — P0 SEO snippet uygulama (meta title + description).
 *
 *   php scripts/apply_faz1_seo_snippets.php           # dry-run
 *   php scripts/apply_faz1_seo_snippets.php --apply   # uygula
 */

$faz1WebRun = defined('MYNAK_FAZ1_SEO_WEB') && MYNAK_FAZ1_SEO_WEB === true;
if (PHP_SAPI !== 'cli' && !$faz1WebRun) {
    http_response_code(403);
    exit("CLI only.\n");
}

$root = dirname(__DIR__);
$apply = $faz1WebRun
    ? (defined('MYNAK_FAZ1_SEO_APPLY') && MYNAK_FAZ1_SEO_APPLY === true)
    : in_array('--apply', $argv ?? [], true);
if (!$faz1WebRun) {
    require_once $root . '/config/db.php';
}

$ts = date('Ymd_His');
$backupDir = $root . '/logs/backups/faz1_seo_' . $ts;
$reportPath = $root . '/logs/faz1_seo_apply_' . $ts . '.json';

/** @return array<string, array{seo_title: string, meta_description: string, h1?: string}> */
function faz1_meta_map(): array
{
    return [
        '' => [
            'seo_title' => 'İzmir Evden Eve Nakliyat | Sigortalı Taşıma | MY Nakliyat',
            'meta_description' => 'İzmir\'de evden eve ve şehirlerarası nakliyat hizmeti sunuyoruz. Sigortalı, asansörlü profesyonel taşıma ve ücretsiz ekspertiz. Hemen teklif alın.',
        ],
        'izmir-evden-eve-nakliyat' => [
            'seo_title' => 'İzmir Evden Eve Nakliyat | Ücretsiz Ekspertiz | MY Nakliyat',
            'meta_description' => 'İzmir evden eve nakliyat hizmeti sunuyoruz. Sigortalı ve asansörlü taşıma, net fiyat ve planlı süreç. Ücretsiz keşif için teklif alın.',
            'h1' => 'İzmir Evden Eve Nakliyat',
        ],
        'sehirlerarasi-nakliyat' => [
            'seo_title' => 'Şehirlerarası Nakliyat | İzmir Çıkışlı | MY Nakliyat',
            'meta_description' => 'İzmir çıkışlı şehirlerarası nakliyat hizmeti sunuyoruz. Sigortalı eşya taşıma, sabitleme ve zamanında teslimat. Hemen teklif alın.',
            'h1' => 'Şehirlerarası Nakliyat Hizmeti',
        ],
        'asansorlu-nakliyat' => [
            'seo_title' => 'Asansörlü Nakliyat İzmir | Yüksek Kat | MY Nakliyat',
            'meta_description' => 'İzmir\'de asansörlü nakliyat hizmeti sunuyoruz. Yüksek katlı binalarda sigortalı ve hızlı eşya taşıma. Ücretsiz ekspertiz alın.',
            'h1' => 'İzmir Asansörlü Nakliyat',
        ],
        'esya-depolama' => [
            'seo_title' => 'Eşya Depolama İzmir | Güvenli Depo | MY Nakliyat',
            'meta_description' => 'İzmir\'de eşya depolama hizmeti sunuyoruz. Sigortalı, iklim kontrollü depolama ve esnek süreler. Fiyat için teklif alın.',
            'h1' => 'İzmir Eşya Depolama',
        ],
        'kurumsal-nakliye-ofis-tasima' => [
            'seo_title' => 'İzmir Ofis Taşımacılığı | Kurumsal | MY Nakliyat',
            'meta_description' => 'İzmir\'de ofis taşımacılığı hizmeti sunuyoruz. Sigortalı kurumsal nakliyat ve planlı taşıma süreci. Hemen teklif alın.',
            'h1' => 'İzmir Ofis Taşımacılığı',
        ],
        'izmir-evden-eve-nakliyat-yorumlari' => [
            'seo_title' => 'İzmir Nakliyat Yorumları | 5,0 Google Puan | MY Nakliyat',
            'meta_description' => 'İzmir evden eve nakliyat müşteri yorumlarını inceleyin. Gerçek Google değerlendirmeleri ve sigortalı taşıma deneyimleri. Güvenle teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-2026' => [
            'seo_title' => 'İzmir Nakliyat Fiyatları 2026 | Net Ücret | MY Nakliyat',
            'meta_description' => '2026 İzmir evden eve nakliyat fiyatlarını şeffaf tabloyla sunuyoruz. Daire tipi, kat ve mesafe kriterleri. Ücretsiz fiyat alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-2025' => [
            'seo_title' => 'İzmir Nakliyat Fiyatları 2025 | Rehber | MY Nakliyat',
            'meta_description' => '2025 İzmir evden eve nakliyat fiyat rehberi sunuyoruz. Ücret kriterleri ve sigortalı taşıma bilgisi. Güncel teklif alın.',
        ],
        'izmir-ev-tasima-fiyatlari' => [
            'seo_title' => 'İzmir Ev Taşıma Fiyatları 2026 | Rehber | MY Nakliyat',
            'meta_description' => 'İzmir ev taşıma fiyatlarını etkileyen kriterleri açıklıyoruz. Sigortalı taşıma ve ücretsiz ekspertiz. Hemen hesaplayın.',
        ],
        '2026-sehirler-arasi-nakliyat-fiyatlari-guncel-rehber' => [
            'seo_title' => 'Şehirlerarası Fiyatlar 2026 | İzmir | MY Nakliyat',
            'meta_description' => '2026 şehirlerarası nakliyat ücret rehberi sunuyoruz. Mesafe, hacim ve sigorta kalemleri. İzmir çıkışlı teklif alın.',
        ],
        'izmir-mobil-asansor-kiralama-fiyatlari' => [
            'seo_title' => 'Mobil Asansör Fiyatları İzmir | 2026 | MY Nakliyat',
            'meta_description' => 'İzmir mobil asansör kiralama fiyatlarını gün ve kat sayısına göre sunuyoruz. Güvenli yük taşıma. Hemen fiyat alın.',
        ],
        'izmir-esya-depolama-fiyatlari-2026' => [
            'seo_title' => 'Eşya Depolama Fiyatları 2026 | İzmir | MY Nakliyat',
            'meta_description' => '2026 İzmir eşya depolama fiyatlarını m² ve süreye göre açıklıyoruz. Sigortalı depo ve esnek planlar. Teklif isteyin.',
        ],
        'sehirler-arasi-nakliyat-fiyatlari-2026' => [
            'seo_title' => 'Şehirlerarası Ücretler 2026 | İzmir | MY Nakliyat',
            'meta_description' => 'İzmir çıkışlı 2026 şehirlerarası nakliyat fiyat rehberi sunuyoruz. Sigortalı taşıma ve şeffaf ücret. Teklif alın.',
        ],
        'izmir-evden-eve-tasimacilik-fiyatlari' => [
            'seo_title' => 'Evden Eve Fiyatları İzmir | Güncel | MY Nakliyat',
            'meta_description' => 'İzmir evden eve taşımacılık fiyatlarını daire ve hizmet kapsamına göre açıklıyoruz. Profesyonel sigortalı taşıma.',
        ],
        'izmir-evden-eve-nakliye-fiyatlari-2025' => [
            'seo_title' => 'Evden Eve Fiyatları 2025 | İzmir | MY Nakliyat',
            'meta_description' => '2025 İzmir evden eve nakliye fiyat bilgilerini şeffaf şekilde sunuyoruz. Ücretsiz ekspertiz ve sigortalı taşıma.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari' => [
            'seo_title' => 'İzmir Nakliyat Fiyat Listesi | Güncel | MY Nakliyat',
            'meta_description' => 'İzmir nakliyat fiyat listesini güncel kriterlerle sunuyoruz. Ücretsiz ekspertiz ve sigortalı evden eve taşıma.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlar' => [
            'seo_title' => 'İzmir Nakliyat Ücretleri | 2026 | MY Nakliyat',
            'meta_description' => 'İzmir nakliyat ücretlerini şeffaf şekilde paylaşıyoruz. Evden eve sigortalı taşıma için ücretsiz fiyat alın.',
        ],
        'asansorlu-nakliyat-fiyatlari' => [
            'seo_title' => 'Asansörlü Nakliyat Fiyatları | İzmir | MY Nakliyat',
            'meta_description' => 'İzmir asansörlü nakliyat fiyatlarını kat ve eşya hacmine göre açıklıyoruz. Güvenli yüksek kat taşıma. Teklif alın.',
        ],
        'asansorlu-ev-tasima-fiyatlari' => [
            'seo_title' => 'Asansörlü Ev Taşıma Fiyatları | İzmir | MY Nakliyat',
            'meta_description' => 'İzmir asansörlü ev taşıma ücretlerini net kriterlerle sunuyoruz. Profesyonel ve sigortalı taşıma. Fiyat alın.',
        ],
        'sehirler-arasi-nakliyat-fiyatlari' => [
            'seo_title' => 'Şehirlerarası Nakliyat Ücretleri | MY Nakliyat',
            'meta_description' => 'Şehirlerarası nakliyat fiyatlarını mesafe ve hacme göre açıklıyoruz. İzmir çıkışlı sigortalı taşıma. Teklif alın.',
        ],
        'parca-esya-tasima-fiyatlari-2026-ucretler-nasil-belirlenir' => [
            'seo_title' => 'Parça Eşya Fiyatları 2026 | İzmir | MY Nakliyat',
            'meta_description' => '2026 parça eşya taşıma ücretlerini mesafe ve parça sayısına göre sunuyoruz. İzmir genelinde sigortalı taşıma.',
        ],
        'piyano-tasima-fiyatlari' => [
            'seo_title' => 'Piyano Taşıma Fiyatları İzmir | 2026 | MY Nakliyat',
            'meta_description' => 'İzmir piyano taşıma fiyatlarını enstrüman tipine göre açıklıyoruz. Sigortalı profesyonel taşıma. Teklif alın.',
        ],
        'izmir-istanbul-nakliyat-fiyatlari' => [
            'seo_title' => 'İzmir İstanbul Nakliyat Fiyatları | MY Nakliyat',
            'meta_description' => 'İzmir–İstanbul nakliyat fiyatlarını şeffaf kriterlerle sunuyoruz. Sigortalı şehirlerarası taşıma. Hemen teklif alın.',
        ],
        'izmir-nakliye-fiyatlari' => [
            'seo_title' => 'İzmir Nakliye Fiyatları | Şeffaf | MY Nakliyat',
            'meta_description' => 'İzmir nakliye fiyatlarını hizmet türüne göre açıklıyoruz. Evden eve ve şehirlerarası sigortalı taşıma. Teklif alın.',
        ],
        'izmir-de-nakliye-ucretleri-uygun-fiyatli-tasimacilik-icin-bilmeniz-gerekenler' => [
            'seo_title' => 'İzmir Nakliye Ücretleri Rehberi | MY Nakliyat',
            'meta_description' => 'İzmir nakliye ücretlerini belirleyen faktörleri açıklıyoruz. Uygun fiyatlı sigortalı taşıma için ücretsiz ekspertiz alın.',
        ],
        'karsiyaka-evden-eve-nakliyat-fiyatlari-uygun-fiyatlarla-tasinmanin-yollari' => [
            'seo_title' => 'Karşıyaka Nakliyat Fiyatları | İzmir | MY Nakliyat',
            'meta_description' => 'Karşıyaka evden eve nakliyat fiyatlarını şeffaf şekilde sunuyoruz. Sigortalı taşıma ve ücretsiz keşif. Teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-ve-firma-secimi' => [
            'seo_title' => 'Nakliyat Fiyatları ve Firma Seçimi | MY Nakliyat',
            'meta_description' => 'İzmir evden eve nakliyat fiyatları ve güvenilir firma seçimi rehberi sunuyoruz. Sigortalı taşıma kriterleri. Teklif alın.',
        ],
        'nakliyat-fiyatlarini-etkileyen-faktorler' => [
            'seo_title' => 'Nakliyat Fiyatını Etkileyen Faktörler | MY Nakliyat',
            'meta_description' => 'Nakliyat fiyatlarını etkileyen mesafe, kat ve eşya kriterlerini açıklıyoruz. İzmir\'de şeffaf ücret planı için teklif alın.',
        ],
        'evden-eve-nakliyat-fiyatlarini-etkileyen-faktorler' => [
            'seo_title' => 'Evden Eve Fiyat Faktörleri | İzmir | MY Nakliyat',
            'meta_description' => 'Evden eve nakliyat fiyatlarını belirleyen unsurları açıklıyoruz. İzmir\'de sigortalı taşıma için ücretsiz fiyat alın.',
        ],
        'evden-eve-nakliyat-fiyatlari-nasil-belirlenir' => [
            'seo_title' => 'Evden Eve Fiyat Nasıl Belirlenir? | MY Nakliyat',
            'meta_description' => 'Evden eve nakliyat fiyatlarının nasıl hesaplandığını adım adım açıklıyoruz. İzmir\'de ücretsiz ekspertiz alın.',
        ],
        'evden-eve-nakliyat-fiyatlari-neye-gore-degisir' => [
            'seo_title' => 'Evden Eve Fiyatları Neye Göre Değişir? | MY Nakliyat',
            'meta_description' => 'Evden eve nakliyat ücretlerinin değişim nedenlerini açıklıyoruz. İzmir\'de şeffaf fiyat için teklif isteyin.',
        ],
        'evden-eve-nakliyat-maliyetleri-fiyat-rehberi' => [
            'seo_title' => 'Evden Eve Maliyet Rehberi 2026 | MY Nakliyat',
            'meta_description' => '2026 evden eve nakliyat maliyet rehberi sunuyoruz. Bütçe planı, sigorta ve taşıma kalemleri. Ücretsiz teklif alın.',
        ],
        'izmir-ev-tasima-fiyatlari-nedir' => [
            'seo_title' => 'İzmir Ev Taşıma Ücreti Nedir? | MY Nakliyat',
            'meta_description' => 'İzmir ev taşıma ücretlerinin nasıl oluştuğunu açıklıyoruz. Sigortalı profesyonel taşıma için fiyat alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-sabit-mi-degisken-mi' => [
            'seo_title' => 'Nakliyat Fiyatları Sabit mi? | İzmir | MY Nakliyat',
            'meta_description' => 'İzmir evden eve nakliyat fiyatlarının sabit ve değişken kalemlerini açıklıyoruz. Net teklif için ücretsiz keşif alın.',
        ],
        'izmir-uygun-fiyatli-nakliyat' => [
            'seo_title' => 'Uygun Fiyatlı Nakliyat İzmir | MY Nakliyat',
            'meta_description' => 'İzmir\'de uygun fiyatlı nakliyat seçeneklerini sigorta ve hizmet kapsamıyla açıklıyoruz. Ücretsiz teklif alın.',
        ],
        'izmir-icin-uygun-fiyatli-evden-eve-tasinma-secenekleri' => [
            'seo_title' => 'Uygun Evden Eve Taşıma İzmir | MY Nakliyat',
            'meta_description' => 'İzmir\'de uygun fiyatlı evden eve taşıma seçeneklerini karşılaştırmalı anlatıyoruz. Sigortalı taşıma için teklif alın.',
        ],
        'musteri-yorumlari-ile-en-iyi-nakliyat-firmasi-secimi' => [
            'seo_title' => 'Yorumlarla Nakliyat Firması Seçimi | MY Nakliyat',
            'meta_description' => 'Müşteri yorumlarıyla güvenilir nakliyat firması seçimi rehberi sunuyoruz. İzmir\'de sigortalı taşıma için teklif alın.',
        ],
        'izmir-asansor-kiralama-hizmetleri-ve-fiyatlari' => [
            'seo_title' => 'İzmir Asansör Kiralama Fiyatları | MY Nakliyat',
            'meta_description' => 'İzmir asansör kiralama fiyatlarını hizmet süresine göre açıklıyoruz. Güvenli eşya taşıma. Hemen teklif alın.',
        ],
        'izmir-sepetli-vinc-kiralama-uygun-fiyatlarla-hizmetinizde' => [
            'seo_title' => 'Sepetli Vinç Kiralama İzmir | MY Nakliyat',
            'meta_description' => 'İzmir sepetli vinç kiralama hizmeti ve fiyat bilgisi sunuyoruz. Güvenli yüksekten taşıma. Teklif alın.',
        ],
    ];
}

function faz1_ensure_suffix(string $title): string
{
    $title = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
    if ($title === '') {
        return 'MY Nakliyat';
    }
    if (preg_match('/\|\s*MY\s+Nakliyat\s*$/iu', $title)) {
        return $title;
    }
    if (preg_match('/\bMY\s+Nakliyat\b/iu', $title)) {
        $title = preg_replace('/\s*®?\s*Resmi Sitesi.*$/iu', '', $title) ?? $title;
        $title = preg_replace('/\s*[-|–—]\s*MY Nakliyat.*$/iu', '', $title) ?? $title;
    }

    return rtrim($title, " \t-|") . ' | MY Nakliyat';
}

function faz1_clamp_meta(string $text): string
{
    require_once dirname(__DIR__) . '/includes/mynak_meta_description.php';

    return mynak_meta_description_clamp($text, 160);
}

$metaMap = faz1_meta_map();
$stats = [
    'urls_targeted' => count($metaMap) + 2,
    'titles_updated' => 0,
    'descriptions_updated' => 0,
    'h1_updated' => 0,
    'settings_updated' => 0,
    'services_updated' => 0,
    'pages_updated' => 0,
    'blog_updated' => 0,
    'duplicates_title_before' => 0,
    'duplicates_desc_before' => 0,
    'duplicates_title_after' => 0,
    'duplicates_desc_after' => 0,
    'changes' => [],
    'backup_dir' => $backupDir,
    'files_to_upload' => [],
];

// Duplicate scan before
$allTitles = [];
$allDescs = [];
foreach (['services' => 'seo_title', 'pages' => 'seo_title', 'blog_posts' => 'seo_title'] as $t => $c) {
    $status = $t === 'blog_posts' ? 'durum=3' : 'status=1';
    $r = $conn->query("SELECT slug, $c, meta_description FROM $t WHERE $status");
    while ($row = $r->fetch_assoc()) {
        $ti = mb_strtolower(trim((string) ($row[$c] ?? '')));
        $de = mb_strtolower(trim((string) ($row['meta_description'] ?? '')));
        if ($ti !== '') {
            $allTitles[$ti] = ($allTitles[$ti] ?? 0) + 1;
        }
        if ($de !== '') {
            $allDescs[$de] = ($allDescs[$de] ?? 0) + 1;
        }
    }
}
$stats['duplicates_title_before'] = count(array_filter($allTitles, static fn($n) => $n > 1));
$stats['duplicates_desc_before'] = count(array_filter($allDescs, static fn($n) => $n > 1));

if ($apply && !is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

function faz1_backup_table(mysqli $conn, string $table, string $where, string $backupDir, bool $apply): void
{
    if (!$apply) {
        return;
    }
    $r = $conn->query("SELECT * FROM $table WHERE $where");
    $rows = [];
    while ($row = $r->fetch_assoc()) {
        $rows[] = $row;
    }
    file_put_contents($backupDir . '/' . $table . '.json', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// Settings backup + update
$settingsBackup = [];
$settingKeys = ['site_title', 'global_title_suffix', 'global_meta_description'];
$r = $conn->query("SELECT name, value FROM settings WHERE name IN ('" . implode("','", $settingKeys) . "')");
while ($row = $r->fetch_assoc()) {
    $settingsBackup[$row['name']] = $row['value'];
}
$newSettings = [
    'site_title' => faz1_ensure_suffix('İzmir Evden Eve Nakliyat | Sigortalı Taşıma'),
    'global_title_suffix' => 'MY Nakliyat',
    'global_meta_description' => faz1_clamp_meta('İzmir\'de evden eve ve şehirlerarası nakliyat hizmeti sunuyoruz. Sigortalı profesyonel taşıma. Ücretsiz ekspertiz için teklif alın.'),
];
if ($apply) {
    file_put_contents($backupDir . '/settings.json', json_encode($settingsBackup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
foreach ($newSettings as $key => $val) {
    if (($settingsBackup[$key] ?? '') !== $val) {
        $stats['changes'][] = ['type' => 'settings', 'key' => $key, 'old' => $settingsBackup[$key] ?? '', 'new' => $val];
        if ($apply) {
            $stmt = $conn->prepare('UPDATE settings SET value = ? WHERE name = ?');
            $stmt->bind_param('ss', $val, $key);
            $stmt->execute();
            $stmt->close();
        }
        $stats['settings_updated']++;
    }
}

// Slug updates
foreach ($metaMap as $slug => $meta) {
    if ($slug === '') {
        continue;
    }
    $seoTitle = faz1_ensure_suffix($meta['seo_title']);
    $metaDesc = faz1_clamp_meta($meta['meta_description']);
    $h1 = $meta['h1'] ?? null;

    $updated = false;

    foreach (['services' => ['title' => 'ana_baslik', 'status' => 'status=1'], 'pages' => ['title' => 'title', 'status' => 'status=1']] as $table => $cfg) {
        $stmt = $conn->prepare("SELECT id, slug, seo_title, meta_description, {$cfg['title']} AS h1col FROM $table WHERE slug = ? AND {$cfg['status']} LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            continue;
        }
        faz1_backup_table($conn, $table, "slug='" . $conn->real_escape_string($slug) . "'", $backupDir, $apply);
        $sets = [];
        $types = '';
        $vals = [];
        if ((string) ($row['seo_title'] ?? '') !== $seoTitle) {
            $sets[] = 'seo_title=?';
            $types .= 's';
            $vals[] = $seoTitle;
            $stats['titles_updated']++;
        }
        if ((string) ($row['meta_description'] ?? '') !== $metaDesc) {
            $sets[] = 'meta_description=?';
            $types .= 's';
            $vals[] = $metaDesc;
            $stats['descriptions_updated']++;
        }
        if ($h1 !== null && (string) ($row['h1col'] ?? '') !== $h1) {
            $sets[] = "{$cfg['title']}=?";
            $types .= 's';
            $vals[] = $h1;
            $stats['h1_updated']++;
        }
        if ($sets !== []) {
            $vals[] = (int) $row['id'];
            $types .= 'i';
            $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $sets) . ' WHERE id=?';
            $stats['changes'][] = ['type' => $table, 'slug' => $slug, 'seo_title' => $seoTitle, 'meta_description' => $metaDesc, 'h1' => $h1];
            if ($apply) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                $stmt->close();
            }
            $stats[$table === 'services' ? 'services_updated' : 'pages_updated']++;
            $updated = true;
        }
        break;
    }

    if ($updated) {
        continue;
    }

    $stmt = $conn->prepare('SELECT id, slug, seo_title, meta_description FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1');
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        $stats['changes'][] = ['type' => 'missing', 'slug' => $slug];
        continue;
    }
    faz1_backup_table($conn, 'blog_posts', "slug='" . $conn->real_escape_string($slug) . "'", $backupDir, $apply);
    $sets = [];
    $types = '';
    $vals = [];
    if ((string) ($row['seo_title'] ?? '') !== $seoTitle) {
        $sets[] = 'seo_title=?';
        $types .= 's';
        $vals[] = $seoTitle;
        $stats['titles_updated']++;
    }
    if ((string) ($row['meta_description'] ?? '') !== $metaDesc) {
        $sets[] = 'meta_description=?';
        $types .= 's';
        $vals[] = $metaDesc;
        $stats['descriptions_updated']++;
    }
    if ($sets !== []) {
        $vals[] = (int) $row['id'];
        $types .= 'i';
        $stats['changes'][] = ['type' => 'blog_posts', 'slug' => $slug, 'seo_title' => $seoTitle, 'meta_description' => $metaDesc];
        if ($apply) {
            $stmt = $conn->prepare('UPDATE blog_posts SET ' . implode(',', $sets) . ' WHERE id=?');
            $stmt->bind_param($types, ...$vals);
            $stmt->execute();
            $stmt->close();
        }
        $stats['blog_updated']++;
    }
}

file_put_contents($reportPath, json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo ($apply ? 'UYGULANDI' : 'DRY-RUN') . "\n";
echo "Rapor: $reportPath\n";
echo "Titles: {$stats['titles_updated']} | Descriptions: {$stats['descriptions_updated']} | H1: {$stats['h1_updated']}\n";
echo "Services: {$stats['services_updated']} | Pages: {$stats['pages_updated']} | Blog: {$stats['blog_updated']} | Settings: {$stats['settings_updated']}\n";
