<?php
declare(strict_types=1);

/** DB entity migration — dashboard üzerinden (ayrı URL / WAF sorunu yok). */
if (isset($_GET['db_entity_clean'])) {
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');
    @ignore_user_abort(true);

    require_once __DIR__ . '/includes/require_admin_web.php';

    $apply = (string) $_GET['db_entity_clean'] === 'apply';
    $projectRoot = dirname(__DIR__);
    $lib = $projectRoot . '/includes/mynak_db_clean_lib.php';

    if (!is_file($lib)) {
        header('Content-Type: text/html; charset=UTF-8', true, 500);
        echo '<!doctype html><html lang="tr"><body style="font-family:sans-serif;padding:2rem">';
        echo '<h1>Dosya eksik</h1>';
        echo '<p>FTP ile yukleyin: <code>includes/mynak_db_clean_lib.php</code></p>';
        echo '<p><a href="dashboard.php">Dashboard</a></p></body></html>';
        exit;
    }

    require_once $projectRoot . '/includes/functions.php';
    require_once $lib;

    /** @var mysqli $conn */
    $conn->set_charset('utf8mb4');
    $conn->query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    mynak_db_clean_execute($conn, $apply, false, $projectRoot);
    exit;
}

$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once 'includes/permissions.php';

// İstatistikleri getir
$stats = [
    'users' => 0,
    'pages' => 0,
    'blog_posts' => 0,
    'services' => 0,
    'form_submissions' => 0,
    'daily_visitors' => 0,
    'total_visitors' => 0
];

// Tabloları ve sayıları güvenli bir şekilde kontrol et
function safeCountQuery($conn, $table, $condition = "1=1") {
    // Tablo varlığını kontrol et
    $tableCheck = $conn->query("SHOW TABLES LIKE '$table'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM `$table` WHERE $condition");
        if ($result) {
            return $result->fetch_assoc()['total'];
        }
    }
    return 0;
}

// Toplam kullanıcı sayısı
$stats['users'] = safeCountQuery($conn, 'users', 'status = 1');

// Toplam sayfa sayısı
$stats['pages'] = safeCountQuery($conn, 'pages', 'status = 1');

// Toplam blog yazısı sayısı
$stats['blog_posts'] = safeCountQuery($conn, 'blog_posts', 'durum = 3');

// Toplam hizmet sayısı
$stats['services'] = safeCountQuery($conn, 'services', 'status = 1');

// Toplam form başvuru sayısı
$stats['form_submissions'] = safeCountQuery($conn, 'form_submissions', '1=1');

// Ziyaretçi istatistikleri
$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');

// Günlük tekil ziyaretçi sayısı
$visitorTable = $conn->query("SHOW TABLES LIKE 'visitors'");
if ($visitorTable && $visitorTable->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE visit_time BETWEEN '$todayStart' AND '$todayEnd'");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['daily_visitors'] = $row['count'];
    }
    
    // Toplam tekil ziyaretçi sayısı
    $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as count FROM visitors");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_visitors'] = $row['count'];
    }
    
    // Son 7 günün ziyaretçi sayıları
    $visitor_stats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $date_start = $date . ' 00:00:00';
        $date_end = $date . ' 23:59:59';
        
        $result = $conn->query("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE visit_time BETWEEN '$date_start' AND '$date_end'");
        if ($result && $row = $result->fetch_assoc()) {
            $visitor_stats[date('d/m', strtotime($date))] = $row['count'];
        } else {
            $visitor_stats[date('d/m', strtotime($date))] = 0;
        }
    }
    
    // Referans kaynakları
    $referrer_stats = [];
    $result = $conn->query("
        SELECT 
            CASE 
                WHEN referrer = '' OR referrer IS NULL THEN 'Doğrudan Giriş'
                -- Arama motoru botları
                WHEN referrer LIKE '%google%' AND referrer LIKE '%bot%' THEN 'Google Bot'
                WHEN referrer LIKE '%yandex%' AND referrer LIKE '%bot%' THEN 'Yandex Bot'
                WHEN referrer LIKE '%bing%' AND referrer LIKE '%bot%' THEN 'Bing Bot'
                WHEN referrer LIKE '%baidu%' AND referrer LIKE '%bot%' THEN 'Baidu Bot'
                WHEN referrer LIKE '%yahoo%' AND referrer LIKE '%bot%' THEN 'Yahoo Bot'
                WHEN referrer LIKE '%bot%' THEN 'Diğer Botlar'
                -- Organik arama sonuçları
                WHEN referrer LIKE '%google%' AND (referrer LIKE '%organic%' OR referrer LIKE '%search%') THEN 'Google Organik'
                WHEN referrer LIKE '%yandex%' AND (referrer LIKE '%organic%' OR referrer LIKE '%search%') THEN 'Yandex Organik'
                WHEN referrer LIKE '%bing%' AND (referrer LIKE '%organic%' OR referrer LIKE '%search%') THEN 'Bing Organik'
                WHEN referrer LIKE '%yahoo%' AND (referrer LIKE '%organic%' OR referrer LIKE '%search%') THEN 'Yahoo Organik'
                WHEN referrer LIKE '%duckduckgo%' THEN 'DuckDuckGo Organik'
                -- Sosyal medya
                WHEN referrer LIKE '%facebook%' OR referrer LIKE '%fb.com%' THEN 'Facebook'
                WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                WHEN referrer LIKE '%twitter%' OR referrer LIKE '%t.co%' OR referrer LIKE '%x.com%' THEN 'Twitter'
                WHEN referrer LIKE '%youtube%' OR referrer LIKE '%youtu.be%' THEN 'Youtube'
                WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                WHEN referrer LIKE '%pinterest%' THEN 'Pinterest'
                WHEN referrer LIKE '%tiktok%' THEN 'TikTok'
                WHEN referrer LIKE '%whatsapp%' THEN 'WhatsApp'
                WHEN referrer LIKE '%telegram%' THEN 'Telegram'
                -- Genel arama motorları
                WHEN referrer LIKE '%google%' THEN 'Google'
                WHEN referrer LIKE '%yandex%' THEN 'Yandex'
                WHEN referrer LIKE '%bing%' THEN 'Bing'
                WHEN referrer LIKE '%yahoo%' THEN 'Yahoo'
                -- Diğer kaynaklar
                ELSE 'Diğer Kaynaklar'
            END as source,
            COUNT(*) as count
        FROM visitors 
        GROUP BY source
        ORDER BY count DESC
        LIMIT 15
    ");
    
if ($result) {
        while ($row = $result->fetch_assoc()) {
            $referrer_stats[$row['source']] = $row['count'];
        }
    }
} else {
    // Ziyaretçi tablosu yoksa site_stats tablosunu dene
    $statsTable = $conn->query("SHOW TABLES LIKE 'site_stats'");
    if ($statsTable && $statsTable->num_rows > 0) {
        $result = $conn->query("SELECT daily_visitors, total_visitors FROM site_stats ORDER BY id DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $stats['daily_visitors'] = $row['daily_visitors'] ?? 0;
            $stats['total_visitors'] = $row['total_visitors'] ?? 0;
        }
    }
}

// Son eklenen kullanıcılar
$recent_users = [];
$usersTable = $conn->query("SHOW TABLES LIKE 'users'");
if ($usersTable && $usersTable->num_rows > 0) {
    $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_users[] = $row;
        }
    }
}

// Son blog yazıları
$recent_posts = [];
$postsTable = $conn->query("SHOW TABLES LIKE 'blog_posts'");
if ($postsTable && $postsTable->num_rows > 0) {
    $result = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 3");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_posts[] = $row;
        }
    }
}

// Son aktiviteler
$recent_activities = [];

// Kullanıcılar tablosunu kontrol et
$usersTable = $conn->query("SHOW TABLES LIKE 'users'");
if ($usersTable && $usersTable->num_rows > 0) {
    $result = $conn->query("SELECT 'Kullanıcı' as type, username as title, created_at as date FROM users ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = $row;
        }
    }
}

// Blog yazıları tablosunu kontrol et
$blogTable = $conn->query("SHOW TABLES LIKE 'blog_posts'");
if ($blogTable && $blogTable->num_rows > 0) {
    // Sütun ismini kontrol et
    $blogColumns = $conn->query("SHOW COLUMNS FROM blog_posts LIKE 'title'");
    $titleField = ($blogColumns && $blogColumns->num_rows > 0) ? 'title' : 'baslik';
    
    $result = $conn->query("SELECT 'Blog' as type, $titleField as title, created_at as date FROM blog_posts ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = $row;
        }
    }
}

// Sayfalar tablosunu kontrol et
$pagesTable = $conn->query("SHOW TABLES LIKE 'pages'");
if ($pagesTable && $pagesTable->num_rows > 0) {
    // Sütun ismini kontrol et
    $pagesColumns = $conn->query("SHOW COLUMNS FROM pages LIKE 'title'");
    $titleField = ($pagesColumns && $pagesColumns->num_rows > 0) ? 'title' : 'baslik';
    
    $result = $conn->query("SELECT 'Sayfa' as type, $titleField as title, created_at as date FROM pages ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_activities[] = $row;
        }
    }
}

// Son form başvuruları
$recent_forms = [];
$formsTable = $conn->query("SHOW TABLES LIKE 'form_submissions'");
if ($formsTable && $formsTable->num_rows > 0) {
    // Önce sütunları kontrol et
    $columnsResult = $conn->query("SHOW COLUMNS FROM form_submissions");
    $columns = [];
    if ($columnsResult) {
        while ($column = $columnsResult->fetch_assoc()) {
            $columns[] = $column['Field'];
        }
    }
    
    // Popup tablosunu kontrol et
    $popupTable = $conn->query("SHOW TABLES LIKE 'popups'");
    $joinPopup = $popupTable && $popupTable->num_rows > 0;
    
    // Form başvurularını çek
    $sql = "SELECT fs.*, " . ($joinPopup ? "p.title as popup_title" : "NULL as popup_title") . "
            FROM form_submissions fs " .
            ($joinPopup ? "LEFT JOIN popups p ON fs.popup_id = p.id " : "") .
            "ORDER BY 
                CASE 
                    WHEN fs.created_at IS NOT NULL AND fs.created_at != '0000-00-00 00:00:00' THEN fs.created_at
                    WHEN fs.submission_date IS NOT NULL AND fs.submission_date != '0000-00-00 00:00:00' THEN fs.submission_date
                    ELSE '1970-01-01 00:00:00'
                END DESC 
             LIMIT 5";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Tarih kontrolü ve düzeltmesi
            $submissionDate = null;
            
            // Tarih sütununun varlığını kontrol et
            if (isset($row['created_at']) && !empty($row['created_at']) && $row['created_at'] != '0000-00-00 00:00:00') {
                $submissionDate = $row['created_at'];
            } else if (isset($row['submission_date']) && !empty($row['submission_date']) && $row['submission_date'] != '0000-00-00 00:00:00') {
                $submissionDate = $row['submission_date'];
            } else {
                $submissionDate = date('Y-m-d H:i:s');
            }
            
            $row['submission_date'] = $submissionDate;
            
            // Form verilerini ayrıştır
            if (!empty($row['form_data'])) {
                $form_data = json_decode($row['form_data'], true);
                if (is_array($form_data)) {
                    foreach ($form_data as $key => $value) {
                        $key_lower = strtolower($key);
                        if (in_array($key_lower, ['name', 'full_name', 'fullname', 'ad_soyad', 'adsoyad', 'ad']) && !isset($row['name'])) {
                            $row['name'] = $value;
                        } elseif (in_array($key_lower, ['email', 'e_posta', 'eposta', 'mail']) && !isset($row['email'])) {
                            $row['email'] = $value;
                        }
                    }
                }
            }
            
            $recent_forms[] = $row;
            
            // Aktivitelere de ekle
            $activity_title = $row['name'] ?? $row['email'] ?? 'Form Başvurusu';
            $recent_activities[] = [
                'type' => 'Form',
                'title' => $activity_title,
                'date' => $submissionDate
            ];
        }
    }
}

// Aktiviteleri tarihe göre sırala
usort($recent_activities, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Sadece ilk 10 aktiviteyi göster
$recent_activities = array_slice($recent_activities, 0, 10);

// SEO skor paneli — salt-okuma metrikler (15 dk cache). ?refresh_seo=1 ile zorla güncelle.
// Not: includes/seo_runtime.php burada yüklenmez (çok modül; hata/bellek riski). Metrik kodu seo_runtime’a bağlı değildir.
require_once __DIR__ . '/includes/dashboard_seo_metrics.php';
require_once __DIR__ . '/includes/dashboard_system_health.php';
require_once __DIR__ . '/includes/dashboard_form_pipeline.php';
$mynakSeoForceRefresh = !empty($_GET['refresh_seo']);
$mynakSeoMetrics = function_exists('mynak_dashboard_seo_metrics_fallback_shell')
    ? mynak_dashboard_seo_metrics_fallback_shell()
    : [];
$mynakRecs = [];
$mynakDashboardLoadError = null;
$mynakSysHealth = function_exists('mynak_dashboard_system_health_fallback_shell')
    ? mynak_dashboard_system_health_fallback_shell()
    : [];
try {
    $mynakSysHealth = mynak_dashboard_system_health_collect();
} catch (Throwable $e) {
    if ($mynakDashboardLoadError === null) {
        $mynakDashboardLoadError = 'Sistem sağlığı: ' . $e->getMessage();
    } else {
        $mynakDashboardLoadError .= ' | Sistem sağlığı: ' . $e->getMessage();
    }
    if (function_exists('mynak_dashboard_system_health_fallback_shell')) {
        $mynakSysHealth = mynak_dashboard_system_health_fallback_shell();
    }
    error_log('[mynak] admin/dashboard.php sistem sağlığı: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
$mynakFormPipeline = [
    'total' => 0, 'today' => 0, 'd7' => 0, 'open' => 0, 'won' => 0, 'lost' => 0, 'spam' => 0,
    'conv_rate' => 0.0, 'by_status' => [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
    'recent7' => [], 'error' => null,
];
$mynakFormPipelineMeta = [];
try {
    $mynakFormPipeline = mynak_dashboard_form_pipeline_collect($conn);
    $mynakFormPipelineMeta = mynak_dashboard_form_pipeline_status_meta();
} catch (Throwable $e) {
    if ($mynakDashboardLoadError === null) {
        $mynakDashboardLoadError = 'Form pipeline: ' . $e->getMessage();
    } else {
        $mynakDashboardLoadError .= ' | Form pipeline: ' . $e->getMessage();
    }
    error_log('[mynak] admin/dashboard.php form pipeline: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (function_exists('mynak_dashboard_form_pipeline_status_meta')) {
        $mynakFormPipelineMeta = mynak_dashboard_form_pipeline_status_meta();
    }
}
try {
    $mynakSeoMetrics = mynak_dashboard_seo_metrics_get($conn, $mynakSeoForceRefresh, 900);
} catch (Throwable $e) {
    $mynakDashboardLoadError = 'SEO metrik: ' . $e->getMessage();
    if (function_exists('mynak_dashboard_seo_metrics_fallback_shell')) {
        $mynakSeoMetrics = mynak_dashboard_seo_metrics_fallback_shell();
    }
    error_log('[mynak] admin/dashboard.php SEO metrik: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
try {
    if (is_file(__DIR__ . '/includes/mynak_admin_recommendations.php')) {
        require_once __DIR__ . '/includes/mynak_admin_recommendations.php';
    }
    if (function_exists('mynak_admin_recommendations_collect')) {
        $mynakRecs = mynak_admin_recommendations_collect($conn, $mynakSeoMetrics, $mynakFormPipeline, $mynakSysHealth);
    }
} catch (Throwable $e) {
    if ($mynakDashboardLoadError === null) {
        $mynakDashboardLoadError = 'Panel önerileri: ' . $e->getMessage();
    } else {
        $mynakDashboardLoadError .= ' | Öneriler: ' . $e->getMessage();
    }
    error_log('[mynak] admin/dashboard.php öneriler: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}

$mynakSeoPct = static function (array $bucket): array {
    $total = (int) ($bucket['total'] ?? 0);
    if ($total < 1) {
        return ['title_ok_pct' => 0, 'meta_ok_pct' => 0];
    }
    return [
        'title_ok_pct' => (int) round(((int) ($bucket['title']['ok'] ?? 0)) / $total * 100),
        'meta_ok_pct' => (int) round(((int) ($bucket['meta']['ok'] ?? 0)) / $total * 100),
    ];
};
?>

<?php if (!empty($mynakDashboardLoadError)): ?>
<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
    <strong>Panel verisi kısmen yüklenemedi.</strong>
    <span class="small d-block mt-1"><?php echo htmlspecialchars($mynakDashboardLoadError); ?></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
</div>
<?php endif; ?>

<?php
$mynakDbCleanLib = dirname(__DIR__) . '/includes/mynak_db_clean_lib.php';
$mynakDbCleanLibOk = is_file($mynakDbCleanLib);
?>
<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <strong>DB entity temizliği (tek seferlik migration)</strong>
    <?php if (!$mynakDbCleanLibOk): ?>
        <span class="d-block small mt-1 text-danger">Eksik dosya: <code>includes/mynak_db_clean_lib.php</code> — FTP ile yukleyin.</span>
    <?php else: ?>
        <span class="d-block small mt-1 mb-2">Önce dry-run raporu alın; onay sonrası apply çalıştırın.</span>
        <a href="dashboard.php?db_entity_clean=preview" class="btn btn-sm btn-primary me-2">Dry-run başlat</a>
        <a href="dashboard.php?db_entity_clean=apply" class="btn btn-sm btn-outline-danger">Apply (DB yedeği sonrası)</a>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
</div>

<?php if (hasPermission('dashboard_view')): ?>
<!-- Panel önerileri: DB + dosya; GSC/PSI otomatik değil -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow border-start border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bx bx-list-check"></i> Yapılacaklar &amp; yönlendirme
                </h6>
                <a href="gsc_low_ctr_report.php" class="small text-decoration-none text-muted" title="Search Console dışa aktar → CSV">GSC CSV analizi</a>
            </div>
            <div class="card-body">
                <?php if (empty($mynakRecs)): ?>
                    <p class="text-success mb-0 small"><i class="bx bx-check-circle"></i> Bu otomatik taramada işaretli madde yok. GSC, PageSpeed ve hızlı smoke kontrol dış hizmet/CLI’de.</p>
                <?php else: ?>
                    <p class="text-muted small mb-3">Aşağıdakiler sadece siteden ölçülenlerdir; <strong>Search Console’da indeks/404</strong> için GSC’yi açmanız gerekir.</p>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($mynakRecs as $r): ?>
                            <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-0 px-0">
                                <div>
                                    <span class="badge bg-<?php
                                    echo $r['level'] === 'danger' ? 'danger' : ($r['level'] === 'warning' ? 'warning text-dark' : 'info text-dark');
                                    ?> me-1"><?php echo $r['level'] === 'danger' ? 'Kritik' : ($r['level'] === 'warning' ? 'Uyarı' : 'Bilgi'); ?></span>
                                    <strong class="d-block d-md-inline"><?php echo htmlspecialchars($r['title']); ?></strong>
                                    <span class="small text-muted d-block mt-1"><?php echo htmlspecialchars($r['detail']); ?></span>
                                </div>
                                <?php if (!empty($r['action_href']) && !empty($r['action_label'])): ?>
                                <a href="<?php echo htmlspecialchars($r['action_href']); ?>" class="btn btn-sm btn-outline-primary text-nowrap"><?php echo htmlspecialchars($r['action_label']); ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SEO Skor Paneli -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class='bx bx-line-chart'></i> SEO Skor Paneli
                </h6>
                <div class="small text-muted">
                    <?php if (($mynakSeoMetrics['_source'] ?? '') === 'cache'): ?>
                        Cache <?php echo (int) floor(((int) ($mynakSeoMetrics['_age_seconds'] ?? 0)) / 60); ?> dk önce
                    <?php else: ?>
                        Az önce güncellendi
                    <?php endif; ?>
                    &nbsp;·&nbsp;
                    <a href="?refresh_seo=1" class="text-decoration-none"><i class='bx bx-refresh'></i> Yenile</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $mynakSeoGroups = [
                        ['key' => 'services', 'label' => 'Hizmetler', 'edit' => 'services.php', 'color' => 'primary', 'icon' => 'bx-cog'],
                        ['key' => 'pages', 'label' => 'Sayfalar', 'edit' => 'pages.php', 'color' => 'info', 'icon' => 'bx-file'],
                        ['key' => 'blog_posts', 'label' => 'Blog', 'edit' => 'blog_posts.php', 'color' => 'success', 'icon' => 'bxs-edit-alt'],
                    ];
                    foreach ($mynakSeoGroups as $g):
                        $b = $mynakSeoMetrics[$g['key']] ?? [];
                        $pct = $mynakSeoPct($b);
                        $total = (int) ($b['total'] ?? 0);
                        $titleProblems = (int) ($b['title']['short'] ?? 0) + (int) ($b['title']['long'] ?? 0) + (int) ($b['title']['missing'] ?? 0);
                        $metaProblems = (int) ($b['meta']['short'] ?? 0) + (int) ($b['meta']['long'] ?? 0) + (int) ($b['meta']['missing'] ?? 0);
                        $scoreAvg = $b['score_avg'] ?? null;
                        $fresh7 = (int) ($mynakSeoMetrics['freshness'][$g['key']]['7d'] ?? 0);
                    ?>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0"><i class='bx <?php echo htmlspecialchars($g['icon']); ?>'></i> <?php echo htmlspecialchars($g['label']); ?></h6>
                                <span class="badge bg-<?php echo htmlspecialchars($g['color']); ?>"><?php echo $total; ?></span>
                            </div>
                            <div class="small mb-1">
                                <span class="text-muted">Title ideal:</span>
                                <strong class="<?php echo $pct['title_ok_pct'] >= 85 ? 'text-success' : ($pct['title_ok_pct'] >= 60 ? 'text-warning' : 'text-danger'); ?>"><?php echo $pct['title_ok_pct']; ?>%</strong>
                                <?php if ($titleProblems > 0): ?>
                                    <span class="text-muted">· <?php echo $titleProblems; ?> sorun</span>
                                <?php endif; ?>
                            </div>
                            <div class="small mb-1">
                                <span class="text-muted">Meta ideal:</span>
                                <strong class="<?php echo $pct['meta_ok_pct'] >= 85 ? 'text-success' : ($pct['meta_ok_pct'] >= 60 ? 'text-warning' : 'text-danger'); ?>"><?php echo $pct['meta_ok_pct']; ?>%</strong>
                                <?php if ($metaProblems > 0): ?>
                                    <span class="text-muted">· <?php echo $metaProblems; ?> sorun</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($scoreAvg !== null): ?>
                            <div class="small mb-1"><span class="text-muted">Ort. seo_score:</span> <strong><?php echo htmlspecialchars((string) $scoreAvg); ?></strong></div>
                            <?php endif; ?>
                            <div class="small text-muted">Son 7 gün güncellenen: <strong class="text-dark"><?php echo $fresh7; ?></strong></div>
                            <div class="mt-2">
                                <a href="<?php echo htmlspecialchars($g['edit']); ?>" class="btn btn-sm btn-outline-<?php echo htmlspecialchars($g['color']); ?>">Listele</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <?php
                    $lc = $mynakSeoMetrics['local_cluster'] ?? ['covered' => 0, 'weak' => 0, 'missing' => 0, 'total' => 0];
                    $lcTotal = max(1, (int) $lc['total']);
                    $lcCoveredPct = (int) round(((int) $lc['covered']) / $lcTotal * 100);
                    $ap = $mynakSeoMetrics['anchor_pool'] ?? ['total_anchors' => 0, 'unique_targets' => 0, 'active' => 0];
                    $sm = $mynakSeoMetrics['sitemap'] ?? [];
                    ?>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class='bx bx-map'></i> İlçe Kapsama</h6>
                            <div class="small mb-1">
                                <span class="text-success">Kapsanan:</span> <strong><?php echo (int) $lc['covered']; ?></strong> ·
                                <span class="text-warning">Zayıf:</span> <strong><?php echo (int) $lc['weak']; ?></strong> ·
                                <span class="text-danger">Eksik:</span> <strong><?php echo (int) $lc['missing']; ?></strong>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $lcCoveredPct; ?>%"></div>
                            </div>
                            <div class="small text-muted mt-1"><?php echo $lcCoveredPct; ?>% kapsama (toplam <?php echo (int) $lc['total']; ?>)</div>
                            <div class="mt-2">
                                <a href="local_cluster_coverage.php" class="btn btn-sm btn-outline-secondary">Matrisi Aç</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class='bx bx-purchase-tag'></i> Anchor Havuzu</h6>
                            <div class="small mb-1">Toplam anchor: <strong><?php echo (int) $ap['total_anchors']; ?></strong></div>
                            <div class="small mb-1">Hedef slug: <strong><?php echo (int) $ap['unique_targets']; ?></strong></div>
                            <div class="small mb-1">Aktif: <strong class="<?php echo $ap['active'] > 0 ? 'text-success' : 'text-muted'; ?>"><?php echo (int) $ap['active']; ?></strong></div>
                            <div class="mt-2">
                                <a href="internal_link_anchors.php" class="btn btn-sm btn-outline-secondary">Yönet</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class='bx bx-sitemap'></i> Sitemap Durumu</h6>
                            <?php foreach (['index' => 'sitemap-index', 'main' => 'sitemap', 'image' => 'image-sitemap', 'video' => 'video-sitemap'] as $k => $label):
                                $info = $sm[$k] ?? null; ?>
                                <div class="small mb-1">
                                    <?php echo htmlspecialchars($label); ?>.xml:
                                    <?php if (is_array($info)): ?>
                                        <strong class="text-success"><?php echo date('d.m.Y H:i', (int) $info['mtime']); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">dinamik (file yok)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="small text-muted mt-2">Dinamik sitemap'ler index.php üzerinden üretilir.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Takip Panosu (Madde 3 — 6-asamali pipeline ozeti) -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class='bx bx-list-ul'></i> Form Takip Panosu
                </h6>
                <div class="small text-muted">
                    Toplam: <strong><?php echo $mynakFormPipeline['total']; ?></strong>
                    · Bugün: <strong><?php echo $mynakFormPipeline['today']; ?></strong>
                    · 7 gün: <?php echo $mynakFormPipeline['d7']; ?>
                    · Açık: <strong class="text-warning"><?php echo $mynakFormPipeline['open']; ?></strong>
                    · Dönüşüm: <strong class="text-success"><?php echo $mynakFormPipeline['conv_rate']; ?>%</strong>
                    &nbsp;·&nbsp;
                    <a href="form_submissions.php" class="text-decoration-none"><i class='bx bx-link-external'></i> Tümü</a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($mynakFormPipeline['error']): ?>
                    <div class="alert alert-warning mb-0 small">Pipeline okunamadı: <?php echo htmlspecialchars($mynakFormPipeline['error']); ?></div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($mynakFormPipelineMeta as $stKey => $stMeta): ?>
                            <div class="col-6 col-md-2">
                                <a href="form_submissions.php?status=<?php echo $stKey; ?>" class="text-decoration-none text-reset">
                                    <div class="border rounded p-2 h-100" style="border-left: 4px solid <?php echo $stMeta['color']; ?> !important;">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bx <?php echo $stMeta['icon']; ?> me-1" style="color: <?php echo $stMeta['color']; ?>;"></i>
                                            <small class="text-muted"><?php echo $stMeta['label']; ?></small>
                                        </div>
                                        <h5 class="mb-0"><?php echo $mynakFormPipeline['by_status'][$stKey]; ?></h5>
                                        <?php if ($mynakFormPipeline['total'] > 0): ?>
                                            <small class="text-muted">%<?php echo round(($mynakFormPipeline['by_status'][$stKey] / $mynakFormPipeline['total']) * 100, 1); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Son 7 gun mini bar grafigi (saf CSS, kutuphane yok) -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <small class="text-muted fw-bold">Son 7 gün — günlük gelen form</small>
                            <small class="text-muted">en fazla:
                                <?php $maxC = max(array_column($mynakFormPipeline['recent7'], 'count') ?: [0]); echo $maxC; ?>
                            </small>
                        </div>
                        <div class="d-flex align-items-end gap-1" style="height: 60px;">
                            <?php
                            $maxC = max(array_column($mynakFormPipeline['recent7'], 'count') ?: [0]);
                            $maxC = $maxC > 0 ? $maxC : 1;
                            foreach ($mynakFormPipeline['recent7'] as $day):
                                $h = (int) round(($day['count'] / $maxC) * 100);
                                $isToday = ($day['date'] === date('Y-m-d'));
                            ?>
                                <div class="flex-fill text-center" title="<?php echo $day['date']; ?>: <?php echo $day['count']; ?> form">
                                    <div style="height: 50px; display: flex; flex-direction: column; justify-content: flex-end;">
                                        <div style="background: <?php echo $isToday ? '#0d6efd' : '#cbd5e1'; ?>; height: <?php echo max($h, 4); ?>%; border-radius: 3px 3px 0 0;"></div>
                                    </div>
                                    <small class="text-muted" style="font-size: 10px;">
                                        <?php echo date('d.m', strtotime($day['date'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sistem Sağlığı Paneli -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class='bx bx-server'></i> Sistem Sağlığı
                </h6>
                <div class="small text-muted">
                    PHP <?php echo htmlspecialchars($mynakSysHealth['php']['version']); ?> · <?php echo htmlspecialchars($mynakSysHealth['php']['sapi']); ?>
                    · memory_limit <?php echo htmlspecialchars($mynakSysHealth['php']['memory_limit']); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php $op = $mynakSysHealth['opcache']; ?>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class='bx bx-chip'></i> OPcache</h6>
                            <?php if ($op['enabled'] && is_array($op['status'])): ?>
                                <div class="small mb-1">
                                    Durum:
                                    <strong class="text-success">Aktif</strong>
                                </div>
                                <?php if ($op['hit_rate'] !== null): ?>
                                    <div class="small mb-1">
                                        Hit-rate:
                                        <strong class="<?php echo $op['hit_rate'] >= 95 ? 'text-success' : ($op['hit_rate'] >= 80 ? 'text-warning' : 'text-danger'); ?>">
                                            <?php echo htmlspecialchars((string) $op['hit_rate']); ?>%
                                        </strong>
                                    </div>
                                <?php endif; ?>
                                <?php if ($op['num_cached'] !== null): ?>
                                    <div class="small mb-1">Cache'teki script: <strong><?php echo (int) $op['num_cached']; ?></strong></div>
                                <?php endif; ?>
                                <?php if ($op['memory_used_pct'] !== null): ?>
                                    <div class="small mb-1">
                                        Bellek kullanımı:
                                        <strong class="<?php echo $op['memory_used_pct'] <= 70 ? 'text-success' : ($op['memory_used_pct'] <= 90 ? 'text-warning' : 'text-danger'); ?>">
                                            <?php echo htmlspecialchars((string) $op['memory_used_pct']); ?>%
                                        </strong>
                                    </div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar bg-<?php echo $op['memory_used_pct'] <= 70 ? 'success' : ($op['memory_used_pct'] <= 90 ? 'warning' : 'danger'); ?>"
                                             role="progressbar"
                                             style="width: <?php echo htmlspecialchars((string) $op['memory_used_pct']); ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="small mb-1">Durum: <strong class="text-danger">Kapalı</strong></div>
                                <div class="small text-muted"><?php echo htmlspecialchars((string) ($op['reason'] ?? 'Nedeni bilinmiyor')); ?></div>
                                <div class="small text-muted mt-2">Canlıda açılmalı: <code>opcache.enable=1</code> (php.ini), öneri <code>memory_consumption=256</code>, <code>max_accelerated_files=20000</code>, <code>validate_timestamps=1</code>, <code>revalidate_freq=60</code>.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $ch = $mynakSysHealth['cache']; ?>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class='bx bx-folder'></i> Uygulama Cache</h6>
                            <?php if ($ch['exists']): ?>
                                <div class="small mb-1">Dosya: <strong><?php echo (int) $ch['file_count']; ?></strong></div>
                                <div class="small mb-1">Boyut: <strong><?php echo htmlspecialchars(mynak_format_bytes((int) $ch['total_bytes'])); ?></strong></div>
                                <?php if ($ch['oldest_seconds'] !== null): ?>
                                    <div class="small mb-1">
                                        En eski giriş:
                                        <strong class="<?php echo $ch['oldest_seconds'] <= 86400 ? 'text-success' : 'text-warning'; ?>">
                                            <?php echo htmlspecialchars(mynak_format_duration((int) $ch['oldest_seconds'])); ?> önce
                                        </strong>
                                    </div>
                                <?php endif; ?>
                                <div class="small text-muted mt-2"><?php echo htmlspecialchars(basename($ch['dir'])); ?>/ klasörü — deploy sonrası temizlenmesi önerilir.</div>
                            <?php else: ?>
                                <div class="small text-muted">cache/ klasörü yok (henüz oluşmamış olabilir).</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $dk = $mynakSysHealth['disk']; ?>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class='bx bx-hdd'></i> Disk</h6>
                            <?php if ($dk['total_bytes'] !== null && $dk['free_bytes'] !== null): ?>
                                <div class="small mb-1">Toplam: <strong><?php echo htmlspecialchars(mynak_format_bytes((int) $dk['total_bytes'])); ?></strong></div>
                                <div class="small mb-1">Boş: <strong><?php echo htmlspecialchars(mynak_format_bytes((int) $dk['free_bytes'])); ?></strong></div>
                                <?php if ($dk['used_pct'] !== null): ?>
                                    <div class="small mb-1">
                                        Kullanım:
                                        <strong class="<?php echo $dk['used_pct'] <= 75 ? 'text-success' : ($dk['used_pct'] <= 90 ? 'text-warning' : 'text-danger'); ?>">
                                            %<?php echo (int) $dk['used_pct']; ?>
                                        </strong>
                                    </div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar bg-<?php echo $dk['used_pct'] <= 75 ? 'success' : ($dk['used_pct'] <= 90 ? 'warning' : 'danger'); ?>"
                                             role="progressbar"
                                             style="width: <?php echo (int) $dk['used_pct']; ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="small text-muted">Disk bilgisi okunamadı (bazı hostinglerde disable edilmiş olabilir).</div>
                            <?php endif; ?>
                            <?php if ($mynakSysHealth['phpstan']['exists']): ?>
                                <hr class="my-2">
                                <div class="small mb-1">
                                    <i class='bx bx-shield-quarter'></i> PHPStan baseline:
                                    <strong><?php echo (int) ($mynakSysHealth['phpstan']['baseline_count'] ?? 0); ?></strong> kayıt
                                </div>
                                <?php if ($mynakSysHealth['phpstan']['last_run_mtime'] !== null): ?>
                                    <div class="small text-muted">
                                        Son güncelleme: <?php echo date('d.m.Y', (int) $mynakSysHealth['phpstan']['last_run_mtime']); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (!$op['enabled']): ?>
                    <div class="alert alert-warning small mt-3 mb-0">
                        <strong>İpucu:</strong> OPcache canlıda açık olmalı. Canlıda dashboard'a girip bu kartı kontrol edin — hit-rate %95+ olmalı. Eğer kapalıysa hosting panelinden
                        <code>opcache.enable=1</code> yapmak yeter (restart gerektirir).
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hızlı Erişim Menüsü -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Hızlı Erişim</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <?php if (hasPermission('blog_add')): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="blog_posts.php?action=add" class="text-decoration-none">
                            <div class="quick-link">
                                <i class='bx bxs-edit-alt'></i>
                                <span>Yeni Blog Yazısı</span>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('pages_add')): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="pages.php?action=add" class="text-decoration-none">
                            <div class="quick-link">
                                <i class='bx bxs-file-plus'></i>
                                <span>Yeni Sayfa</span>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('services_add')): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="services.php?action=add" class="text-decoration-none">
                            <div class="quick-link">
                                <i class='bx bxs-cog'></i>
                                <span>Yeni Hizmet</span>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('admin_add')): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="admin_users.php?action=add" class="text-decoration-none">
                            <div class="quick-link">
                                <i class='bx bxs-user-plus'></i>
                                <span>Yeni Kullanıcı</span>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- İstatistik Kartları -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Toplam Kullanıcı</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['users']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class='bx bxs-user-detail fa-2x text-gray-300'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Toplam Sayfa</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pages']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class='bx bxs-file fa-2x text-gray-300'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Blog Yazıları</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['blog_posts']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class='bx bxs-news fa-2x text-gray-300'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Form Başvuruları</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['form_submissions']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class='bx bxs-envelope fa-2x text-gray-300'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Son Aktiviteler ve Form Başvuruları -->
<div class="row">
    <!-- Son Aktiviteler -->
    <div class="col-xl-6 col-lg-6">
<div class="card shadow mb-4">
    <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Son Aktiviteler</h6>
            </div>
            <div class="card-body">
                <div class="activity-feed">
                    <?php if (count($recent_activities) > 0): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="feed-item">
                                <div class="feed-icon 
                                    <?php 
                                    $iconClass = 'bg-secondary';
                                    $iconName = 'bxs-file';
                                    
                                    if ($activity['type'] == 'Kullanıcı') {
                                        $iconClass = 'bg-primary';
                                        $iconName = 'bxs-user';
                                    } elseif ($activity['type'] == 'Blog') {
                                        $iconClass = 'bg-info';
                                        $iconName = 'bxs-news';
                                    } elseif ($activity['type'] == 'Form') {
                                        $iconClass = 'bg-warning';
                                        $iconName = 'bxs-envelope';
                                    }
                                    echo $iconClass;
                                    ?>">
                                    <i class="bx <?php echo $iconName; ?>"></i>
                                </div>
                                <div class="feed-content">
                                    <span class="feed-text">
                                        <strong><?php echo htmlspecialchars($activity['type']); ?></strong>: 
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </span>
                                    <span class="feed-date"><?php echo date('d.m.Y H:i', strtotime($activity['date'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">Henüz aktivite bulunmuyor.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Form Başvuruları -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Son Form Başvuruları</h6>
                <?php if (hasPermission('form_view')): ?>
                <a href="form_submissions.php" class="btn btn-sm btn-primary">Tümünü Gör</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($recent_forms) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Form Tipi</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_forms as $form): ?>
                                    <?php
                                    // Status badge class
                                    $status = isset($form['status']) ? (int)$form['status'] : 0;
                                    $status_badge_class = 'bg-secondary';
                                    $status_text = 'Bekliyor';
                                    
                                    if ($status == 1) {
                                        $status_badge_class = 'bg-success';
                                        $status_text = 'İşlendi';
                                    } elseif ($status == 2) {
                                        $status_badge_class = 'bg-danger';
                                        $status_text = 'Reddedildi';
                                    } elseif ($status == 3) {
                                        $status_badge_class = 'bg-info';
                                        $status_text = 'İncelendi';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($form['popup_id'])): ?>
                                                <span class="badge bg-info me-1">Popup</span>
                                                <?php echo htmlspecialchars($form['popup_title'] ?? 'Form'); ?>
                                            <?php else: ?>
                                                <span class="badge bg-primary me-1">İletişim</span>
                                                İletişim Formu
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($form['submission_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_badge_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                            <?php if (!empty($form['notes'])): ?>
                                                <i class="bx bx-info-circle" title="<?php echo htmlspecialchars($form['notes']); ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (hasPermission('form_view')): ?>
                                            <a href="form_submissions.php?action=view&id=<?php echo $form['id']; ?>" class="btn btn-sm btn-info">
                                                <i class='bx bxs-show'></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (hasPermission('form_delete')): ?>
                                            <a href="form_submissions.php?action=delete&id=<?php echo $form['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu başvuruyu silmek istediğinize emin misiniz?');">
                                                <i class='bx bxs-trash'></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">Henüz form başvurusu bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Son Eklenen Kullanıcılar ve Blog Yazıları -->
<div class="row">
    <!-- Son Eklenen Kullanıcılar -->
    <div class="col-xl-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Son Eklenen Kullanıcılar</h6>
                <?php if (hasPermission('admin_view')): ?>
                <a href="users.php" class="btn btn-sm btn-primary">Tümünü Gör</a>
                <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Kullanıcı Adı</th>
                        <th>E-posta</th>
                        <th>Kayıt Tarihi</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                            <?php if (count($recent_users) > 0): ?>
                                <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] ? 'success' : 'danger'; ?>">
                                        <?php echo $user['status'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Henüz kullanıcı bulunmuyor.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>
    
    <!-- Son Blog Yazıları -->
    <div class="col-xl-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Son Blog Yazıları</h6>
                <?php if (hasPermission('blog_view')): ?>
                <a href="blog_posts.php" class="btn btn-sm btn-primary">Tümünü Gör</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($recent_posts) > 0): ?>
                    <div class="row">
                        <?php foreach ($recent_posts as $post): ?>
                            <div class="col-md-12 mb-3">
                                <div class="blog-card-simple d-flex">
                                    <div class="blog-image">
                                        <?php if (!empty($post['kapak_foto'])): ?>
                                            <img src="../uploads/blog/<?php echo htmlspecialchars($post['kapak_foto']); ?>" alt="<?php echo htmlspecialchars($post['baslik']); ?>" class="blog-thumbnail">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class='bx bxs-image'></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="blog-content">
                                        <h5 class="card-title"><?php echo htmlspecialchars($post['baslik']); ?></h5>
                                        <div class="blog-footer">
                                            <span class="text-muted small"><?php echo date('d.m.Y', strtotime($post['created_at'])); ?></span>
                                            <?php if (hasPermission('blog_edit')): ?>
                                            <a href="blog_edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary ms-auto">
                                                <i class='bx bxs-edit'></i> Düzenle
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">Henüz blog yazısı bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ziyaretçi İstatistikleri -->
<div class="row">
    <!-- Ziyaretçi Grafiği -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Son 7 Gün Ziyaretçi İstatistikleri</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="visitorChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Referans Kaynakları -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Ziyaretçi Kaynakları</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($referrer_stats)): ?>
                    <div class="referrer-list">
                        <?php foreach ($referrer_stats as $source => $count): ?>
                            <div class="referrer-item">
                                <div class="referrer-info">
                                    <div class="referrer-icon
                                    <?php 
                                    $iconClass = 'bg-secondary';
                                    $iconName = 'globe';
                                    
                                    switch (true) {
                                        case strpos($source, 'Google Bot') !== false:
                                            $iconClass = 'bg-danger';
                                            $iconName = 'robot';
                                            break;
                                        case strpos($source, 'Yandex Bot') !== false:
                                            $iconClass = 'bg-warning';
                                            $iconName = 'robot';
                                            break;
                                        case strpos($source, 'Bing Bot') !== false:
                                            $iconClass = 'bg-primary';
                                            $iconName = 'robot';
                                            break;
                                        case strpos($source, 'Baidu Bot') !== false:
                                            $iconClass = 'bg-info';
                                            $iconName = 'robot';
                                            break;
                                        case strpos($source, 'Google Organik') !== false:
                                            $iconClass = 'bg-danger';
                                            $iconName = 'search';
                                            break;
                                        case strpos($source, 'Yandex Organik') !== false:
                                            $iconClass = 'bg-warning';
                                            $iconName = 'search';
                                            break;
                                        case strpos($source, 'Bing Organik') !== false:
                                            $iconClass = 'bg-primary';
                                            $iconName = 'search';
                                            break;
                                        case strpos($source, 'Google') !== false:
                                            $iconClass = 'bg-danger';
                                            $iconName = 'google';
                                            break;
                                        case strpos($source, 'Yandex') !== false:
                                            $iconClass = 'bg-warning';
                                            $iconName = 'search';
                                            break;
                                        case strpos($source, 'Bing') !== false:
                                            $iconClass = 'bg-primary';
                                            $iconName = 'search';
                                            break;
                                        case strpos($source, 'Yahoo') !== false:
                                            $iconClass = 'bg-purple';
                                            $iconName = 'search';
                                            break;
                                        case strpos($source, 'Facebook') !== false:
                                            $iconClass = 'bg-primary';
                                            $iconName = 'facebook';
                                            break;
                                        case strpos($source, 'Instagram') !== false:
                                            $iconClass = 'bg-info';
                                            $iconName = 'instagram';
                                            break;
                                        case strpos($source, 'Twitter') !== false:
                                            $iconClass = 'bg-info';
                                            $iconName = 'twitter';
                                            break;
                                        case strpos($source, 'Youtube') !== false:
                                            $iconClass = 'bg-danger';
                                            $iconName = 'youtube';
                                            break;
                                        case strpos($source, 'LinkedIn') !== false:
                                            $iconClass = 'bg-primary';
                                            $iconName = 'linkedin';
                                            break;
                                        case strpos($source, 'Pinterest') !== false:
                                            $iconClass = 'bg-danger';
                                            $iconName = 'pinterest';
                                            break;
                                        case strpos($source, 'TikTok') !== false:
                                            $iconClass = 'bg-dark';
                                            $iconName = 'music';
                                            break;
                                        case strpos($source, 'Doğrudan Giriş') !== false:
                                            $iconClass = 'bg-success';
                                            $iconName = 'link';
                                            break;
                                    }
                                    ?>">
                                        <i class="fas fa-<?php echo $iconName; ?>"></i>
                                    </div>
                                    <div class="referrer-name"><?php echo htmlspecialchars($source); ?></div>
                                </div>
                                <div class="referrer-count">
                                    <span class="badge bg-light text-dark"><?php echo $count; ?> ziyaretçi</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="no-data-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <p>Henüz ziyaretçi kaynağı verisi bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Özel CSS Stilleri -->
<style>
.activity-feed {
    padding: 0;
    list-style: none;
}
.feed-item {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid #f3f3f3;
}
.feed-item:last-child {
    border-bottom: none;
}
.feed-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
.feed-content {
    flex: 1;
}
.feed-text {
    display: block;
    font-size: 14px;
}
.feed-date {
    display: block;
    font-size: 12px;
    color: #6c757d;
}
.quick-link {
    padding: 20px;
    border-radius: 8px;
    background-color: #f8f9fc;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.quick-link i {
    font-size: 32px;
    margin-bottom: 10px;
    color: #4e73df;
}
.quick-link:hover {
    background-color: #4e73df;
    color: white;
}
.quick-link:hover i {
    color: white;
}
.blog-card-simple {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    overflow: hidden;
    transition: all 0.3s;
}
.blog-card-simple:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
}
.blog-image {
    width: 100px;
    min-width: 100px;
    height: 80px;
    position: relative;
    overflow: hidden;
    background-color: #f8f9fc;
}
.blog-content {
    flex: 1;
    padding: 10px 15px;
    display: flex;
    flex-direction: column;
}
.blog-content h5 {
    margin-bottom: 0.5rem;
    font-size: 16px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.blog-footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.no-image {
    background-color: #f8f9fc;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100px;
}
.no-image i {
    font-size: 32px;
    color: #d1d3e2;
}
.blog-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
}

.ms-auto {
    margin-left: auto;
}

.referrer-list {
    padding: 0;
}
.referrer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #f3f3f3;
}
.referrer-item:last-child {
    border-bottom: none;
}
.referrer-info {
    display: flex;
    align-items: center;
}
.referrer-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
.referrer-name {
    font-weight: 500;
}
.referrer-count {
    padding: 5px;
}
.no-data-icon {
    font-size: 48px;
    color: #d1d3e2;
    margin-bottom: 15px;
}
</style>

<!-- Ziyaretçi Grafiği için JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ziyaretçi grafiği
    const visitorCtx = document.getElementById('visitorChart');
    if (visitorCtx) {
        const visitorData = <?php echo json_encode(array_values($visitor_stats ?? [])); ?>;
        const visitorLabels = <?php echo json_encode(array_keys($visitor_stats ?? [])); ?>;
        
        new Chart(visitorCtx, {
            type: 'line',
            data: {
                labels: visitorLabels,
                datasets: [{
                    label: 'Tekil Ziyaretçi',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: '#4e73df',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df',
                    data: visitorData,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 