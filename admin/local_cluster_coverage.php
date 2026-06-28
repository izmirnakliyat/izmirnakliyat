<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/seo_runtime.php';

$page_title = 'İlçe Kapsama Haritası';

// İzmir ilçe listesi (includes/llms_izmir_data.php kanonik kaynak)
$izmirData = require __DIR__ . '/../includes/llms_izmir_data.php';
$districts = is_array($izmirData['districts'] ?? null) ? $izmirData['districts'] : [];

// Türkçe → ASCII slug çevirici (llms_izmir_data içindeki "Karşıyaka" → "karsiyaka")
if (!function_exists('mynak_district_to_slug')) {
    function mynak_district_to_slug(string $name): string
    {
        $tr = ['ı','İ','ğ','Ğ','ü','Ü','ş','Ş','ö','Ö','ç','Ç',' '];
        $en = ['i','i','g','g','u','u','s','s','o','o','c','c','-'];
        $n = str_replace($tr, $en, $name);
        $n = mb_strtolower($n, 'UTF-8');
        $n = preg_replace('/[^a-z0-9-]+/', '', $n);
        return (string) $n;
    }
}

// Hizmet türleri ve slug son ekleri
$services = [
    'evden-eve-nakliyat' => 'Evden Eve',
    'asansorlu-nakliyat' => 'Asansörlü',
    'esya-depolama' => 'Depolama',
    'ofis-tasimaciligi' => 'Ofis',
];

// Mevcut sayfa/hizmet slug → id + içerik uzunluğu
$existingSlugs = [];
$contentLen = [];
$slugKind = [];
$slugId = [];
$res = $conn->query("SELECT id, slug, LENGTH(content) AS len FROM pages WHERE status = 1");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $s = trim((string) $r['slug']);
        $existingSlugs[$s] = true;
        $contentLen[$s] = (int) $r['len'];
        $slugKind[$s] = 'page';
        $slugId[$s] = (int) $r['id'];
    }
}
// Hizmet detay içeriği services.icerik'te tutulur (2026-04 migration); yoksa aciklama'ya düş.
$res = $conn->query("SELECT id, slug, LENGTH(COALESCE(icerik, aciklama)) AS len FROM services WHERE status = 1");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $s = trim((string) $r['slug']);
        if (isset($existingSlugs[$s])) {
            continue;
        }
        $existingSlugs[$s] = true;
        $contentLen[$s] = (int) $r['len'];
        $slugKind[$s] = 'service';
        $slugId[$s] = (int) $r['id'];
    }
}

$matrix = [];
$summary = ['covered' => 0, 'missing' => 0, 'weak' => 0];
foreach ($districts as $d) {
    $slugBase = mynak_district_to_slug($d);
    $row = ['name' => $d, 'slug_base' => $slugBase, 'cells' => []];
    foreach ($services as $svcSuffix => $svcLabel) {
        $candidates = [
            $slugBase . '-' . $svcSuffix,
        ];
        $foundSlug = null;
        foreach ($candidates as $c) {
            if (isset($existingSlugs[$c])) {
                $foundSlug = $c;
                break;
            }
        }
        if ($foundSlug === null) {
            $row['cells'][$svcSuffix] = ['state' => 'missing'];
            $summary['missing']++;
        } else {
            $len = $contentLen[$foundSlug] ?? 0;
            $state = $len < 6000 ? 'weak' : 'covered';
            $row['cells'][$svcSuffix] = [
                'state' => $state,
                'slug' => $foundSlug,
                'len' => $len,
            ];
            if ($state === 'weak') {
                $summary['weak']++;
            } else {
                $summary['covered']++;
            }
        }
    }
    $matrix[] = $row;
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">İzmir İlçe × Hizmet Kapsama Haritası</h5>
        <div>
            <span class="badge bg-success"><?php echo $summary['covered']; ?> sağlam</span>
            <span class="badge bg-warning text-dark"><?php echo $summary['weak']; ?> zayıf (&lt; 6KB)</span>
            <span class="badge bg-danger"><?php echo $summary['missing']; ?> eksik</span>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info small">
            <strong>Nasıl okunur?</strong>
            Her satır bir ilçe. Her sütun bir hizmet tipi. <span class="badge bg-success">Sağlam</span>:
            sayfa var ve içerik 6KB üstü. <span class="badge bg-warning text-dark">Zayıf</span>:
            sayfa var ama içerik kısa — genişletmek ister misin? <span class="badge bg-danger">Eksik</span>:
            hiç sayfa yok — yeni sayfa açma fırsatı. <br>
            Bu rapor <strong>içerik ÜRETMEZ</strong>; sadece gösterir. Sayfa oluşturma işi Sayfalar ekranından
            elle yapılır (böylece kalite kontrol sende kalır). Slug önerisi: <code>{ilce}-{hizmet}</code>.
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>İlçe</th>
                        <?php foreach ($services as $suffix => $label): ?>
                            <th class="text-center"><?php echo htmlspecialchars($label); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrix as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                <div class="text-muted small"><code><?php echo htmlspecialchars($row['slug_base']); ?></code></div>
                            </td>
                            <?php foreach ($services as $suffix => $label): $cell = $row['cells'][$suffix]; ?>
                                <td class="text-center">
                                    <?php if ($cell['state'] === 'covered' || $cell['state'] === 'weak'):
                                        $kind = $slugKind[$cell['slug']] ?? 'page';
                                        $sid = $slugId[$cell['slug']] ?? 0;
                                        $editHref = $kind === 'service'
                                            ? 'service_edit.php?id=' . $sid
                                            : 'page_edit.php?id=' . $sid;
                                        $btnClass = $cell['state'] === 'covered' ? 'btn-success' : 'btn-warning text-dark';
                                        $btnIcon = $cell['state'] === 'covered' ? 'bx-check' : 'bx-edit';
                                        $tail = $cell['state'] === 'covered' ? '' : ' — zayıf';
                                    ?>
                                        <a href="<?php echo htmlspecialchars($editHref); ?>" class="btn btn-sm <?php echo $btnClass; ?>" title="Düzenle">
                                            <i class='bx <?php echo $btnIcon; ?>'></i>
                                            <span class="small">/<?php echo htmlspecialchars($cell['slug']); ?></span>
                                            <div class="small"><?php echo number_format($cell['len']); ?> B<?php echo $tail; ?></div>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <span class="badge bg-danger">eksik</span>
                                            <div class="mt-1"><code><?php echo htmlspecialchars($row['slug_base'] . '-' . $suffix); ?></code></div>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-light small mt-3 mb-0">
            <strong>Not:</strong> Bu ekran, mevcut <code>pages</code> ve <code>services</code> tablolarındaki kayıtları
            ilçe × hizmet matrisine yansıtır. Slug deseni farklı (örn. <code>urla-evdeneve-nakliyat</code>) olan
            kayıtlar "eksik" görünebilir; bu durumda sayfayı açıp slug'ı kanonik desene
            (<code>{ilce}-{hizmet}</code>) çevirmek ve eski slug'ı <code>slug_redirects</code>/301 ile
            taşımak önerilir.
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
