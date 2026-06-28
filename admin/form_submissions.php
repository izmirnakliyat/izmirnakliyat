<?php
$page_title = 'Form Başvuruları';
require_once 'includes/header.php';
require_once '../config/db.php';

// form_submissions tablosunda form_id alanını kontrol et ve ekle
try {
    $check_column = $conn->query("SHOW COLUMNS FROM form_submissions LIKE 'form_id'");
    if ($check_column && $check_column->num_rows == 0) {
        $conn->query("ALTER TABLE form_submissions ADD COLUMN form_id INT(11) NULL AFTER id");
    }
} catch (Exception $e) {
    // Hata durumunda ses çıkarma, sayfa çalışmaya devam etsin
}

// forms tablosunu kontrol et, yoksa oluştur
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'forms'");
    if ($check_table && $check_table->num_rows == 0) {
        $create_forms_table = "CREATE TABLE IF NOT EXISTS `forms` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `title` varchar(255) NOT NULL,
            `description` text,
            `fields` longtext NOT NULL,
            `settings` text,
            `email_notifications` text,
            `success_message` text,
            `redirect_url` varchar(255),
            `status` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($create_forms_table);
    }
} catch (Exception $e) {
    // Hata durumunda ses çıkarma
}

// 2026-04-25: 6-asamali pipeline statusleri (Madde 3).
// Geriye donuk uyumlu: 0=Yeni, 1=Arandi, 2=Teklif Verildi, 3=Kazanildi, 4=Kaybedildi, 5=Spam/Gecersiz.
// (Eski 2=Reddedildi anlami otomatik 5'e migrate edildi — bkz. scripts/migrate_form_pipeline_status.php)
$mynak_pipeline_statuses = [
    0 => ['label' => 'Yeni',          'badge' => 'bg-secondary',  'icon' => 'bx-time',          'color' => '#6c757d'],
    1 => ['label' => 'Arandı',        'badge' => 'bg-warning',    'icon' => 'bx-phone-call',    'color' => '#ffc107'],
    2 => ['label' => 'Teklif Verildi','badge' => 'bg-info',       'icon' => 'bx-file',          'color' => '#0dcaf0'],
    3 => ['label' => 'Kazanıldı',     'badge' => 'bg-success',    'icon' => 'bx-check-circle',  'color' => '#198754'],
    4 => ['label' => 'Kaybedildi',    'badge' => 'bg-danger',     'icon' => 'bx-x-circle',      'color' => '#dc3545'],
    5 => ['label' => 'Spam/Geçersiz', 'badge' => 'bg-dark',       'icon' => 'bx-block',         'color' => '#212529'],
];

// Sayfalama için parametreler
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // Sayfa başına kayıt sayısı
$offset = ($page - 1) * $limit;

// Silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM form_submissions WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Başvuru başarıyla silindi.";
    } else {
        $error = "Başvuru silinirken bir hata oluştu: " . $conn->error;
    }
}

// Hizli status degistirme (yeni — modal acmadan inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_status') {
    $id = intval($_POST['id']);
    $newStatus = intval($_POST['status']);
    if (!isset($mynak_pipeline_statuses[$newStatus])) {
        $error = "Gecersiz status degeri.";
    } else {
        // Mevcut notes'u al + otomatik damga ekle
        $cur = $conn->prepare("SELECT notes FROM form_submissions WHERE id = ?");
        $cur->bind_param("i", $id);
        $cur->execute();
        $existing = (string) ($cur->get_result()->fetch_assoc()['notes'] ?? '');
        $cur->close();
        $stamp = '[' . date('d.m.Y H:i') . '] Durum: ' . $mynak_pipeline_statuses[$newStatus]['label'];
        $newNotes = trim(($existing !== '' ? $existing . "\n" : '') . $stamp);
        $stmt = $conn->prepare("UPDATE form_submissions SET status = ?, notes = ? WHERE id = ?");
        $stmt->bind_param("isi", $newStatus, $newNotes, $id);
        if ($stmt->execute()) {
            $success = "Durum guncellendi: " . $mynak_pipeline_statuses[$newStatus]['label'];
        } else {
            $error = "Hata: " . $conn->error;
        }
    }
}

// Durum güncelleme işlemi (modal — notla birlikte)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = intval($_POST['id']);
    $status = intval($_POST['status']);
    if (!isset($mynak_pipeline_statuses[$status])) { $status = 0; }
    $userNote = trim((string) ($_POST['notes'] ?? ''));
    $appendOnly = isset($_POST['append_note']) && $_POST['append_note'] === '1';

    if ($appendOnly && $userNote !== '') {
        // Mevcut notes'a damgali ekle
        $cur = $conn->prepare("SELECT notes FROM form_submissions WHERE id = ?");
        $cur->bind_param("i", $id);
        $cur->execute();
        $existing = (string) ($cur->get_result()->fetch_assoc()['notes'] ?? '');
        $cur->close();
        $stamp = '[' . date('d.m.Y H:i') . '] ' . $userNote;
        $finalNotes = trim(($existing !== '' ? $existing . "\n" : '') . $stamp);
    } else {
        // Geriye donuk: mevcut davranis (overwrite)
        $finalNotes = $userNote;
    }

    $stmt = $conn->prepare("UPDATE form_submissions SET status = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("isi", $status, $finalNotes, $id);

    if ($stmt->execute()) {
        $success = "Başvuru durumu başarıyla güncellendi.";
    } else {
        $error = "Başvuru güncellenirken bir hata oluştu: " . $conn->error;
    }
}

// Filtreleme seçenekleri
$status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1; // -1 = tümü
// Yeni: aktif (open) cluster filtresi — yeni/arandi/teklif (kapanmamis taleplere odak)
$open_only = isset($_GET['open_only']) && $_GET['open_only'] === '1';
$popup_filter = isset($_GET['popup_id']) ? intval($_GET['popup_id']) : 0; // 0 = tümü
$form_filter = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0; // 0 = tümü
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// WHERE koşulları oluştur
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter >= 0) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 'i';
} elseif ($open_only) {
    // Acik talepler: yeni/arandi/teklif verildi (spam ve kapali olanlari haric tut)
    $where_conditions[] = "status IN (0,1,2)";
}

if ($popup_filter > 0) {
    $where_conditions[] = "popup_id = ?";
    $params[] = $popup_filter;
    $types .= 'i';
} elseif ($popup_filter == -1) {
    $where_conditions[] = "popup_id IS NULL";
}

// form_id filtrelemesi için alan kontrolü
$has_form_id_temp = false;
try {
    $check_column_temp = $conn->query("SHOW COLUMNS FROM form_submissions LIKE 'form_id'");
    $has_form_id_temp = ($check_column_temp && $check_column_temp->num_rows > 0);
} catch (Exception $e) {
    $has_form_id_temp = false;
}

if ($form_filter > 0 && $has_form_id_temp) {
    $where_conditions[] = "form_id = ?";
    $params[] = $form_filter;
    $types .= 'i';
}

if (!empty($date_from)) {
    $date_from_str = $date_from . ' 00:00:00';
    $where_conditions[] = "(created_at >= ? OR submission_date >= ?)";
    $params[] = $date_from_str;
    $params[] = $date_from_str;
    $types .= 'ss';
}

if (!empty($date_to)) {
    $date_to_str = $date_to . ' 23:59:59';
    $where_conditions[] = "(created_at <= ? OR submission_date <= ?)";
    $params[] = $date_to_str;
    $params[] = $date_to_str;
    $types .= 'ss';
}

if (!empty($search)) {
    $where_conditions[] = "form_data LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}

// WHERE koşullarını SQL sorgusuna ekle
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// form_id içeren WHERE koşullarını sayım için kontrol et
$count_where_clause = $where_clause;
$count_params = $params;
$count_types = $types;

// Eğer form_id alanı yoksa, form_id içeren koşulları kaldır
if (!$has_form_id_temp && $form_filter > 0) {
    // form_id koşulunu where_conditions'dan çıkar
    $filtered_conditions = [];
    $filtered_params = [];
    $filtered_types = '';
    
    for ($i = 0; $i < count($where_conditions); $i++) {
        if (strpos($where_conditions[$i], 'form_id') === false) {
            $filtered_conditions[] = $where_conditions[$i];
        }
    }
    
    // Parametreleri de yeniden oluştur
    $param_index = 0;
    foreach ($where_conditions as $condition) {
        if (strpos($condition, 'form_id') === false) {
            if (strpos($condition, '?') !== false) {
                $filtered_params[] = $params[$param_index];
                $filtered_types .= $types[$param_index];
            }
        }
        if (strpos($condition, '?') !== false) {
            $param_index++;
        }
    }
    
    $count_where_clause = !empty($filtered_conditions) ? "WHERE " . implode(" AND ", $filtered_conditions) : "";
    $count_params = $filtered_params;
    $count_types = $filtered_types;
}

// Toplam kayıt sayısını al
$count_sql = "SELECT COUNT(*) as total FROM form_submissions $count_where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// form_id alanının varlığını kontrol et
$has_form_id = false;
try {
    $check_column = $conn->query("SHOW COLUMNS FROM form_submissions LIKE 'form_id'");
    $has_form_id = ($check_column && $check_column->num_rows > 0);
} catch (Exception $e) {
    $has_form_id = false;
}

// Form başvurularını çek
if ($has_form_id) {
    $sql = "SELECT fs.*, p.title as popup_title, f.title as form_title, f.name as form_name
            FROM form_submissions fs 
            LEFT JOIN popups p ON fs.popup_id = p.id 
            LEFT JOIN forms f ON fs.form_id = f.id
            $where_clause 
            ORDER BY 
                CASE 
                    WHEN fs.created_at IS NOT NULL AND fs.created_at != '0000-00-00 00:00:00' THEN fs.created_at
                    WHEN fs.submission_date IS NOT NULL AND fs.submission_date != '0000-00-00 00:00:00' THEN fs.submission_date
                    ELSE '1970-01-01 00:00:00'
                END DESC
            LIMIT ?, ?";
} else {
    $sql = "SELECT fs.*, p.title as popup_title, NULL as form_title, NULL as form_name
            FROM form_submissions fs 
            LEFT JOIN popups p ON fs.popup_id = p.id 
            $where_clause 
            ORDER BY 
                CASE 
                    WHEN fs.created_at IS NOT NULL AND fs.created_at != '0000-00-00 00:00:00' THEN fs.created_at
                    WHEN fs.submission_date IS NOT NULL AND fs.submission_date != '0000-00-00 00:00:00' THEN fs.submission_date
                    ELSE '1970-01-01 00:00:00'
                END DESC
            LIMIT ?, ?";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // Parametrelere offset ve limit ekle
    $params[] = $offset;
    $params[] = $limit;
    $types .= 'ii';
    
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
$submissions = [];

while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}

// Popup listesini al (filtre için)
$popups_result = $conn->query("SELECT id, title FROM popups ORDER BY title");
$popups = [];

if ($popups_result) {
    while ($row = $popups_result->fetch_assoc()) {
        $popups[] = $row;
    }
}

// Form listesini al (filtre için)
$forms = [];
try {
    $forms_result = $conn->query("SELECT id, name, title FROM forms ORDER BY title");
    if ($forms_result) {
        while ($row = $forms_result->fetch_assoc()) {
            $forms[] = $row;
        }
    }
} catch (Exception $e) {
    // forms tablosu yoksa boş array kullan
    $forms = [];
}

// 2026-04-25: Pipeline KPI metriklerini hesapla.
$mynak_pipeline_counts = array_fill_keys(array_keys($mynak_pipeline_statuses), 0);
$mynak_kpi_total_all   = 0;
$mynak_kpi_today       = 0;
$mynak_kpi_7days       = 0;
$mynak_kpi_30days      = 0;
$mynak_kpi_open        = 0;       // 0+1+2
$mynak_kpi_won         = 0;       // 3
$mynak_kpi_lost        = 0;       // 4
$kpiRes = $conn->query("
    SELECT
      status,
      COUNT(*) AS c,
      SUM(CASE WHEN DATE(COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date)) = CURDATE() THEN 1 ELSE 0 END) AS today_c,
      SUM(CASE WHEN COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date) >= (NOW() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS d7_c,
      SUM(CASE WHEN COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date) >= (NOW() - INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS d30_c
    FROM form_submissions
    GROUP BY status
");
if ($kpiRes) {
    while ($r = $kpiRes->fetch_assoc()) {
        $st = (int) $r['status'];
        $c = (int) $r['c'];
        if (isset($mynak_pipeline_counts[$st])) {
            $mynak_pipeline_counts[$st] = $c;
        } else {
            // Bilinmeyen status (geriye uyum) — Yeni'ye topla
            $mynak_pipeline_counts[0] += $c;
        }
        $mynak_kpi_total_all += $c;
        $mynak_kpi_today  += (int) $r['today_c'];
        $mynak_kpi_7days  += (int) $r['d7_c'];
        $mynak_kpi_30days += (int) $r['d30_c'];
    }
}
$mynak_kpi_open = $mynak_pipeline_counts[0] + $mynak_pipeline_counts[1] + $mynak_pipeline_counts[2];
$mynak_kpi_won  = $mynak_pipeline_counts[3];
$mynak_kpi_lost = $mynak_pipeline_counts[4];
$mynak_kpi_conv_rate = ($mynak_kpi_won + $mynak_kpi_lost) > 0
    ? round(($mynak_kpi_won / ($mynak_kpi_won + $mynak_kpi_lost)) * 100, 1)
    : 0.0;
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Form Yönetimi /</span> Form Takip Panosu
    </h4>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- 2026-04-25 Madde 3: Pipeline KPI kartlari -->
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3 col-xl">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge bg-label-secondary me-2"><i class="bx bx-list-ul"></i></span>
                        <small class="text-muted">Toplam</small>
                    </div>
                    <h4 class="mb-0"><?php echo $mynak_kpi_total_all; ?></h4>
                    <small class="text-muted">Bugun: <strong><?php echo $mynak_kpi_today; ?></strong> · 7g: <?php echo $mynak_kpi_7days; ?> · 30g: <?php echo $mynak_kpi_30days; ?></small>
                </div>
            </div>
        </div>
        <?php foreach ($mynak_pipeline_statuses as $stKey => $stMeta): ?>
            <div class="col-6 col-md-3 col-xl">
                <a href="?status=<?php echo $stKey; ?>" class="text-decoration-none text-reset">
                    <div class="card h-100" style="border-left: 4px solid <?php echo $stMeta['color']; ?>;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge <?php echo $stMeta['badge']; ?> me-2"><i class="bx <?php echo $stMeta['icon']; ?>"></i></span>
                                <small class="text-muted"><?php echo $stMeta['label']; ?></small>
                            </div>
                            <h4 class="mb-0"><?php echo $mynak_pipeline_counts[$stKey]; ?></h4>
                            <small class="text-muted">
                                <?php if ($mynak_kpi_total_all > 0): ?>
                                    %<?php echo round(($mynak_pipeline_counts[$stKey] / $mynak_kpi_total_all) * 100, 1); ?> oran
                                <?php else: ?>
                                    &nbsp;
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Hizli erisim cipleri -->
    <div class="mb-3">
        <span class="text-muted me-2">Hizli filtre:</span>
        <a href="form_submissions.php" class="btn btn-sm <?php echo (!$open_only && $status_filter < 0) ? 'btn-primary' : 'btn-outline-primary'; ?>">Tumu</a>
        <a href="?open_only=1" class="btn btn-sm <?php echo $open_only ? 'btn-primary' : 'btn-outline-primary'; ?>">Acik talepler (<?php echo $mynak_kpi_open; ?>)</a>
        <a href="?status=0" class="btn btn-sm <?php echo $status_filter === 0 ? 'btn-secondary' : 'btn-outline-secondary'; ?>">Yeni (<?php echo $mynak_pipeline_counts[0]; ?>)</a>
        <a href="?status=3" class="btn btn-sm <?php echo $status_filter === 3 ? 'btn-success' : 'btn-outline-success'; ?>">Kazanildi (<?php echo $mynak_kpi_won; ?>)</a>
        <span class="text-muted ms-3">Donusum:
            <strong class="text-success"><?php echo $mynak_kpi_conv_rate; ?>%</strong>
            <small class="text-muted">(kazanildi / kazanildi+kaybedildi)</small>
        </span>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detayli Filtreleme</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row">
                        <div class="col-md-2 mb-3">
                            <label for="status" class="form-label">Durum</label>
                            <select name="status" id="status" class="form-select">
                                <option value="-1"<?php echo $status_filter == -1 ? ' selected' : ''; ?>>Tümü</option>
                                <?php foreach ($mynak_pipeline_statuses as $stKey => $stMeta): ?>
                                    <option value="<?php echo $stKey; ?>"<?php echo $status_filter === $stKey ? ' selected' : ''; ?>>
                                        <?php echo $stMeta['label']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="popup_id" class="form-label">Popup Formları</label>
                            <select name="popup_id" id="popup_id" class="form-select">
                                <option value="0"<?php echo $popup_filter === 0 ? ' selected' : ''; ?>>Tümü</option>
                                <option value="-1"<?php echo $popup_filter === -1 ? ' selected' : ''; ?>>İletişim Formu</option>
                                <?php foreach ($popups as $popup): ?>
                                    <option value="<?php echo $popup['id']; ?>"<?php echo $popup_filter === $popup['id'] ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars($popup['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="form_id" class="form-label">Özel Formlar</label>
                            <select name="form_id" id="form_id" class="form-select">
                                <option value="0"<?php echo $form_filter === 0 ? ' selected' : ''; ?>>Tümü</option>
                                <?php foreach ($forms as $form): ?>
                                    <option value="<?php echo $form['id']; ?>"<?php echo $form_filter === $form['id'] ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars($form['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="date_from" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="date_to" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="search" class="form-label">Arama</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" placeholder="Ad, e-posta, telefon..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary" type="submit">Ara</button>
                            </div>
                        </div>
                    </form>
                    <?php if ($status_filter >= 0 || $popup_filter > 0 || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                        <div class="mt-2">
                            <a href="form_submissions.php" class="btn btn-outline-secondary btn-sm">Filtreleri Temizle</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Başvuru Listesi (<?php echo $total_records; ?>)</h5>
                    <div>
                        <?php if ($total_records > 0): ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="exportData()">
                            <i class="bx bx-export"></i> Dışa Aktar
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($submissions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">#</th>
                                        <th style="width: 180px">Tarih</th>
                                        <th style="width: 180px">Form Tipi</th>
                                        <th>Başvuru Bilgileri</th>
                                        <th style="width: 100px">Durum</th>
                                        <th style="width: 130px">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <?php
                                        $form_data = json_decode($submission['form_data'], true);
                                        if (!is_array($form_data)) {
                                            $form_data = [];
                                        }
                                        $attachments = !empty($submission['attachments']) ? json_decode($submission['attachments'], true) : [];
                                        
                                        // Extract name and email from form data for display
                                        $name = '';
                                        $email = '';
                                        $phone = '';
                                        
                                        foreach ($form_data as $key => $value) {
                                            if (in_array($key, ['name', 'full_name', 'fullname', 'ad_soyad']) && empty($name)) {
                                                $name = $value;
                                            } elseif (in_array($key, ['email', 'e_posta', 'eposta', 'mail']) && empty($email)) {
                                                $email = $value;
                                            } elseif (in_array($key, ['phone', 'telefon', 'tel', 'mobile']) && empty($phone)) {
                                                $phone = $value;
                                            }
                                        }
                                        
                                        // 2026-04-25 Madde 3: 6-asamali pipeline badge'leri (geriye uyumlu)
                                        $st = (int) $submission['status'];
                                        $stMeta = $mynak_pipeline_statuses[$st] ?? $mynak_pipeline_statuses[0];
                                        $status_badge_class = $stMeta['badge'];
                                        $status_text = $stMeta['label'];
                                        $status_icon = $stMeta['icon'];
                                        ?>
                                        <tr>
                                            <td><?php echo $submission['id']; ?></td>
                                            <td><?php 
                                                // Tarih kontrolü ve düzeltmesi
                                                $displayDate = null;
                                                
                                                if (!empty($submission['created_at']) && $submission['created_at'] != '0000-00-00 00:00:00') {
                                                    $displayDate = $submission['created_at'];
                                                } else if (!empty($submission['submission_date']) && $submission['submission_date'] != '0000-00-00 00:00:00') {
                                                    $displayDate = $submission['submission_date'];
                                                } else if (!empty($submission['date']) && $submission['date'] != '0000-00-00 00:00:00') {
                                                    $displayDate = $submission['date'];
                                                } else {
                                                    $displayDate = date('Y-m-d H:i:s');
                                                }
                                                
                                                echo date('d.m.Y H:i', strtotime($displayDate));
                                            ?></td>
                                            <td>
                                                <?php if (!empty($submission['form_title'])): ?>
                                                    <span class="badge bg-success me-1">Özel Form</span>
                                                    <?php echo htmlspecialchars($submission['form_title']); ?>
                                                <?php elseif (!empty($submission['popup_id'])): ?>
                                                    <span class="badge bg-info me-1">Popup</span>
                                                    <?php echo htmlspecialchars($submission['popup_title'] ?? 'Bilinmeyen Form'); ?>
                                                <?php else: ?>
                                                    <span class="badge bg-primary me-1">İletişim</span>
                                                    İletişim Formu
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($name)): ?>
                                                    <div><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($name); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($email)): ?>
                                                    <div><strong>E-posta:</strong> <?php echo htmlspecialchars($email); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($phone)): ?>
                                                    <div><strong>Telefon:</strong> <?php echo htmlspecialchars($phone); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($attachments)): ?>
                                                    <div class="mt-1">
                                                        <strong>Ekler:</strong> 
                                                        <?php foreach ($attachments as $field => $path): ?>
                                                            <a href="<?php echo htmlspecialchars('../' . $path); ?>" target="_blank" class="badge bg-info text-white">
                                                                <i class="bx bx-file"></i> Dosya
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Hizli status degistirme dropdown (modal acmadan) -->
                                                <div class="dropdown">
                                                    <button class="btn btn-sm <?php echo $status_badge_class; ?> dropdown-toggle text-white"
                                                            type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                            title="Durumu degistir">
                                                        <i class="bx <?php echo $status_icon; ?>"></i>
                                                        <?php echo $status_text; ?>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php foreach ($mynak_pipeline_statuses as $sKey => $sMeta): ?>
                                                            <?php if ($sKey === $st) continue; ?>
                                                            <li>
                                                                <form method="POST" class="m-0">
                                                                    <input type="hidden" name="action" value="quick_status">
                                                                    <input type="hidden" name="id" value="<?php echo $submission['id']; ?>">
                                                                    <input type="hidden" name="status" value="<?php echo $sKey; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="bx <?php echo $sMeta['icon']; ?>" style="color: <?php echo $sMeta['color']; ?>;"></i>
                                                                        <?php echo $sMeta['label']; ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                                <?php if (!empty($submission['notes'])): ?>
                                                    <div class="mt-1">
                                                        <i class="bx bx-note text-muted" data-bs-toggle="tooltip" data-bs-html="true"
                                                           title="<?php echo htmlspecialchars(nl2br($submission['notes']), ENT_QUOTES); ?>"></i>
                                                        <small class="text-muted"><?php echo mb_substr(strip_tags($submission['notes']), 0, 40) . (mb_strlen($submission['notes']) > 40 ? '…' : ''); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info" title="Detay"
                                                            onclick="viewDetails(<?php echo $submission['id']; ?>, '<?php echo addslashes(htmlspecialchars($submission['popup_title'] ?? 'Bilinmeyen Form')); ?>')">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-primary" title="Not + Durum (modal)"
                                                            onclick="updateStatus(<?php echo $submission['id']; ?>, <?php echo $submission['status']; ?>, '<?php echo addslashes(htmlspecialchars($submission['notes'] ?? '')); ?>')">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <?php if (!empty($phone)): ?>
                                                        <a class="btn btn-success" href="tel:<?php echo htmlspecialchars(preg_replace('/\D+/', '', $phone)); ?>" title="Ara">
                                                            <i class="bx bx-phone"></i>
                                                        </a>
                                                        <a class="btn btn-success" target="_blank" rel="noopener"
                                                           href="https://wa.me/<?php
                                                                $waPhone = preg_replace('/\D+/', '', $phone);
                                                                if (strpos($waPhone, '90') !== 0 && strlen($waPhone) === 10) { $waPhone = '90' . $waPhone; }
                                                                elseif (strpos($waPhone, '0') === 0) { $waPhone = '90' . substr($waPhone, 1); }
                                                                echo htmlspecialchars($waPhone);
                                                           ?>" title="WhatsApp">
                                                            <i class="bx bxl-whatsapp"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-danger" title="Sil"
                                                            onclick="confirmDelete(<?php echo $submission['id']; ?>, '<?php echo addslashes(htmlspecialchars($name)); ?>')">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-3">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&popup_id=<?php echo $popup_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                                Önceki
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Önceki</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($start_page + 4, $total_pages);
                                    if ($end_page - $start_page < 4 && $start_page > 1) {
                                        $start_page = max(1, $end_page - 4);
                                    }
                                    ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&popup_id=<?php echo $popup_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&popup_id=<?php echo $popup_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                                Sonraki
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Sonraki</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <p>Henüz form başvurusu bulunmuyor.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Silme için gizli form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<!-- Durum güncelleme için modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="status_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Başvuru Durumunu Güncelle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status_select" class="form-label">Durum</label>
                        <select class="form-select" id="status_select" name="status">
                            <?php foreach ($mynak_pipeline_statuses as $stKey => $stMeta): ?>
                                <option value="<?php echo $stKey; ?>"><?php echo $stMeta['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notlar</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Mevcut notlar..."></textarea>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="append_note" name="append_note" value="1" checked>
                            <label class="form-check-label" for="append_note">
                                <small>Notu uzerine yazma — alta ekle (otomatik tarih damgali). <br>
                                <span class="text-muted">Isaretliyse: yazdiginiz metin <code>[GG.AA.YYYY HH:DD]</code> ile altina eklenir.</span></small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Başvuru Detayları Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="details_title">Başvuru Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="details_content">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
// Başvuru silme işlemi
function confirmDelete(id, name) {
    if (confirm(`"${name}" adlı başvuruyu silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Durum güncelleme modalı
function updateStatus(id, currentStatus, notes) {
    document.getElementById('status_id').value = id;
    document.getElementById('status_select').value = currentStatus;
    var notesField = document.getElementById('notes');
    var appendCheckbox = document.getElementById('append_note');
    // Append modu varsayilan: textarea bos basla, mevcut notu placeholder'a yansit
    if (appendCheckbox && appendCheckbox.checked) {
        notesField.value = '';
        notesField.placeholder = notes ? ('Mevcut not:\n' + notes + '\n\n--- Yeni notu yazin ---') : 'Yeni not yazin...';
    } else {
        notesField.value = notes;
        notesField.placeholder = '';
    }
    if (appendCheckbox) {
        appendCheckbox.onchange = function () {
            if (this.checked) {
                notesField.value = '';
                notesField.placeholder = notes ? ('Mevcut not:\n' + notes + '\n\n--- Yeni notu yazin ---') : 'Yeni not yazin...';
            } else {
                notesField.value = notes;
                notesField.placeholder = '';
            }
        };
    }
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

// Başvuru detaylarını görüntüleme
function viewDetails(id, title) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    
    // İletişim Formu için başlık düzeltmesi
    if (title === 'Bilinmeyen Form') {
        title = 'İletişim Formu';
    }
    
    document.getElementById('details_title').textContent = `Başvuru Detayları: ${title}`;
    document.getElementById('details_content').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // AJAX ile başvuru detaylarını al
    fetch(`ajax/get_submission_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('details_content').innerHTML = data.html;
            } else {
                document.getElementById('details_content').innerHTML = `
                    <div class="alert alert-danger">
                        ${data.message || 'Başvuru detayları alınırken bir hata oluştu.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('details_content').innerHTML = `
                <div class="alert alert-danger">
                    Bir hata oluştu: ${error.message}
                </div>
            `;
        });
}

// Verileri dışa aktarma
function exportData() {
    // Mevcut filtreleri al
    const status = document.getElementById('status').value;
    const popupId = document.getElementById('popup_id').value;
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    const search = document.getElementById('search').value;
    
    // URL oluştur
    let url = 'ajax/export_submissions.php?';
    
    if (status >= 0) url += `status=${status}&`;
    if (popupId > 0) url += `popup_id=${popupId}&`;
    if (dateFrom) url += `date_from=${dateFrom}&`;
    if (dateTo) url += `date_to=${dateTo}&`;
    if (search) url += `search=${encodeURIComponent(search)}&`;
    
    // Dışa aktarma sayfasını yeni sekmede aç
    window.open(url, '_blank');
}

// Tooltips için
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>