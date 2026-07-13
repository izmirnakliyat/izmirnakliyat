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
            'seo_title' => 'İzmir Evden Eve Nakliyat | Yazılı Teklif | MY Nakliyat',
            'meta_description' => 'İzmir evden eve nakliyat için hizmet kapsamı, bina erişimi, ambalaj, takvim ve güvence seçenekleri yazılı teklif aşamasında açıklanır.',
        ],
        'izmir-evden-eve-nakliyat' => [
            'seo_title' => 'İzmir Evden Eve Nakliyat | Yazılı Teklif | MY Nakliyat',
            'meta_description' => 'İzmir evden eve nakliyat için eşya envanteri, araç, asansör gereksinimi ve fiyatı etkileyen koşullar talebe göre değerlendirilir.',
            'h1' => 'İzmir Evden Eve Nakliyat',
        ],
        'sehirlerarasi-nakliyat' => [
            'seo_title' => 'Şehirlerarası Nakliyat | İzmir Çıkışlı | MY Nakliyat',
            'meta_description' => 'Şehirlerarası Nakliyat | İzmir Çıkışlı için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
            'h1' => 'Şehirlerarası Nakliyat Hizmeti',
        ],
        'asansorlu-nakliyat' => [
            'seo_title' => 'Asansörlü Nakliyat İzmir | Yüksek Kat | MY Nakliyat',
            'meta_description' => 'Asansörlü Nakliyat İzmir | Yüksek Kat için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
            'h1' => 'İzmir Asansörlü Nakliyat',
        ],
        'esya-depolama' => [
            'seo_title' => 'Eşya Depolama İzmir | Güvenli Depo | MY Nakliyat',
            'meta_description' => 'Eşya Depolama İzmir | Güvenli Depo için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
            'h1' => 'İzmir Eşya Depolama',
        ],
        'kurumsal-nakliye-ofis-tasima' => [
            'seo_title' => 'İzmir Ofis Taşımacılığı | Kurumsal | MY Nakliyat',
            'meta_description' => 'İzmir Ofis Taşımacılığı | Kurumsal için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
            'h1' => 'İzmir Ofis Taşımacılığı',
        ],
        'izmir-evden-eve-nakliyat-yorumlari' => [
            'seo_title' => 'İzmir Nakliyat Yorumları | Google Profili | MY Nakliyat',
            'meta_description' => 'MY Nakliyat için yayımlanan müşteri deneyimlerini ve bağlantılı Google İşletme Profili değerlendirmelerini inceleyin.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-2026' => [
            'seo_title' => 'İzmir Nakliyat Fiyatları 2026 | Net Ücret | MY Nakliyat',
            'meta_description' => 'İzmir Nakliyat Fiyatları 2026 | Net Ücret için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-2025' => [
            'seo_title' => 'İzmir Nakliyat Fiyatları 2025 | Rehber | MY Nakliyat',
            'meta_description' => 'İzmir Nakliyat Fiyatları 2025 | Rehber için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-ev-tasima-fiyatlari' => [
            'seo_title' => 'İzmir Ev Taşıma Fiyatları 2026 | Rehber | MY Nakliyat',
            'meta_description' => 'İzmir Ev Taşıma Fiyatları 2026 | Rehber için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
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
            'meta_description' => 'Eşya Depolama Fiyatları 2026 | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'sehirler-arasi-nakliyat-fiyatlari-2026' => [
            'seo_title' => 'Şehirlerarası Ücretler 2026 | İzmir | MY Nakliyat',
            'meta_description' => 'Şehirlerarası Ücretler 2026 | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-evden-eve-tasimacilik-fiyatlari' => [
            'seo_title' => 'Evden Eve Fiyatları İzmir | Güncel | MY Nakliyat',
            'meta_description' => 'Evden Eve Fiyatları İzmir | Güncel için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-evden-eve-nakliye-fiyatlari-2025' => [
            'seo_title' => 'Evden Eve Fiyatları 2025 | İzmir | MY Nakliyat',
            'meta_description' => 'Evden Eve Fiyatları 2025 | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari' => [
            'seo_title' => 'İzmir Nakliyat Fiyat Listesi | Güncel | MY Nakliyat',
            'meta_description' => 'İzmir Nakliyat Fiyat Listesi | Güncel için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlar' => [
            'seo_title' => 'İzmir Nakliyat Ücretleri | 2026 | MY Nakliyat',
            'meta_description' => 'İzmir Nakliyat Ücretleri | 2026 için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'asansorlu-nakliyat-fiyatlari' => [
            'seo_title' => 'Asansörlü Nakliyat Fiyatları | İzmir | MY Nakliyat',
            'meta_description' => 'İzmir asansörlü nakliyat fiyatlarını kat ve eşya hacmine göre açıklıyoruz. Güvenli yüksek kat taşıma. Teklif alın.',
        ],
        'asansorlu-ev-tasima-fiyatlari' => [
            'seo_title' => 'Asansörlü Ev Taşıma Fiyatları | İzmir | MY Nakliyat',
            'meta_description' => 'Asansörlü Ev Taşıma Fiyatları | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'sehirler-arasi-nakliyat-fiyatlari' => [
            'seo_title' => 'Şehirlerarası Nakliyat Ücretleri | MY Nakliyat',
            'meta_description' => 'Şehirlerarası Nakliyat Ücretleri için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'parca-esya-tasima-fiyatlari-2026-ucretler-nasil-belirlenir' => [
            'seo_title' => 'Parça Eşya Fiyatları 2026 | İzmir | MY Nakliyat',
            'meta_description' => 'Parça Eşya Fiyatları 2026 | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'piyano-tasima-fiyatlari' => [
            'seo_title' => 'Piyano Taşıma Fiyatları İzmir | 2026 | MY Nakliyat',
            'meta_description' => 'Piyano Taşıma Fiyatları İzmir | 2026 için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-istanbul-nakliyat-fiyatlari' => [
            'seo_title' => 'İzmir İstanbul Nakliyat Fiyatları | MY Nakliyat',
            'meta_description' => 'İzmir İstanbul Nakliyat Fiyatları için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-nakliye-fiyatlari' => [
            'seo_title' => 'İzmir Nakliye Fiyatları | Şeffaf | MY Nakliyat',
            'meta_description' => 'İzmir Nakliye Fiyatları | Şeffaf için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-de-nakliye-ucretleri-uygun-fiyatli-tasimacilik-icin-bilmeniz-gerekenler' => [
            'seo_title' => 'İzmir Nakliye Ücretleri Rehberi | MY Nakliyat',
            'meta_description' => 'İzmir Nakliye Ücretleri Rehberi için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'karsiyaka-evden-eve-nakliyat-fiyatlari-uygun-fiyatlarla-tasinmanin-yollari' => [
            'seo_title' => 'Karşıyaka Nakliyat Fiyatları | İzmir | MY Nakliyat',
            'meta_description' => 'Karşıyaka Nakliyat Fiyatları | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-ve-firma-secimi' => [
            'seo_title' => 'Nakliyat Fiyatları ve Firma Seçimi | MY Nakliyat',
            'meta_description' => 'Nakliyat Fiyatları ve Firma Seçimi için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'nakliyat-fiyatlarini-etkileyen-faktorler' => [
            'seo_title' => 'Nakliyat Fiyatını Etkileyen Faktörler | MY Nakliyat',
            'meta_description' => 'Nakliyat fiyatlarını etkileyen mesafe, kat ve eşya kriterlerini açıklıyoruz. İzmir\'de şeffaf ücret planı için teklif alın.',
        ],
        'evden-eve-nakliyat-fiyatlarini-etkileyen-faktorler' => [
            'seo_title' => 'Evden Eve Fiyat Faktörleri | İzmir | MY Nakliyat',
            'meta_description' => 'Evden Eve Fiyat Faktörleri | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'evden-eve-nakliyat-fiyatlari-nasil-belirlenir' => [
            'seo_title' => 'Evden Eve Fiyat Nasıl Belirlenir? | MY Nakliyat',
            'meta_description' => 'Evden Eve Fiyat Nasıl Belirlenir? için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'evden-eve-nakliyat-fiyatlari-neye-gore-degisir' => [
            'seo_title' => 'Evden Eve Fiyatları Neye Göre Değişir? | MY Nakliyat',
            'meta_description' => 'Evden eve nakliyat ücretlerinin değişim nedenlerini açıklıyoruz. İzmir\'de şeffaf fiyat için teklif isteyin.',
        ],
        'evden-eve-nakliyat-maliyetleri-fiyat-rehberi' => [
            'seo_title' => 'Evden Eve Maliyet Rehberi 2026 | MY Nakliyat',
            'meta_description' => 'Evden Eve Maliyet Rehberi 2026 için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-ev-tasima-fiyatlari-nedir' => [
            'seo_title' => 'İzmir Ev Taşıma Ücreti Nedir? | MY Nakliyat',
            'meta_description' => 'İzmir Ev Taşıma Ücreti Nedir? için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-evden-eve-nakliyat-fiyatlari-sabit-mi-degisken-mi' => [
            'seo_title' => 'Nakliyat Fiyatları Sabit mi? | İzmir | MY Nakliyat',
            'meta_description' => 'Nakliyat Fiyatları Sabit mi? | İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-uygun-fiyatli-nakliyat' => [
            'seo_title' => 'Uygun Fiyatlı Nakliyat İzmir | MY Nakliyat',
            'meta_description' => 'Uygun Fiyatlı Nakliyat İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'izmir-icin-uygun-fiyatli-evden-eve-tasinma-secenekleri' => [
            'seo_title' => 'Uygun Evden Eve Taşıma İzmir | MY Nakliyat',
            'meta_description' => 'Uygun Evden Eve Taşıma İzmir için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
        ],
        'musteri-yorumlari-ile-en-iyi-nakliyat-firmasi-secimi' => [
            'seo_title' => 'Yorumlarla Nakliyat Firması Seçimi | MY Nakliyat',
            'meta_description' => 'Yorumlarla Nakliyat Firması Seçimi için hizmet kapsamı, fiyatı etkileyen koşullar ve yazılı teklif süreci açıklanır. Talebinize göre teklif alın.',
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
    'site_title' => faz1_ensure_suffix('İzmir Evden Eve Nakliyat | Yazılı Teklif'),
    'global_title_suffix' => 'MY Nakliyat',
    'global_meta_description' => faz1_clamp_meta('İzmir evden eve ve şehirler arası nakliyat hizmetlerinde kapsam, fiyat ve güvence seçenekleri talebe göre yazılı teklifte belirtilir.'),
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
