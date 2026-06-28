<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_plain();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=form_submissions_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
if ($output === false) {
    http_response_code(500);
    echo 'Çıktı açılamadı';
    exit;
}

fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    'ID',
    'Tarih',
    'Form Tipi',
    'Form Adı',
    'Durum',
    'IP Adresi',
    'Ad Soyad',
    'E-posta',
    'Telefon',
    'Mesaj',
    'Diğer Veriler',
    'Notlar',
]);

// Filtreleme seçenekleri
$status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1; // -1 = tümü
$popup_filter = isset($_GET['popup_id']) ? intval($_GET['popup_id']) : 0; // 0 = tümü
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// WHERE koşulları oluştur
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter >= 0) {
    $where_conditions[] = 'status = ?';
    $params[] = $status_filter;
    $types .= 'i';
}

if ($popup_filter > 0) {
    $where_conditions[] = 'popup_id = ?';
    $params[] = $popup_filter;
    $types .= 'i';
} elseif ($popup_filter == -1) {
    $where_conditions[] = 'popup_id IS NULL';
}

// Tarih filtresi için koşul değiştirildi - multiple date fields check
if (!empty($date_from)) {
    $date_from_str = $date_from . ' 00:00:00';
    $where_conditions[] = '(submission_date >= ? OR created_at >= ?)';
    $params[] = $date_from_str;
    $params[] = $date_from_str;
    $types .= 'ss';
}

if (!empty($date_to)) {
    $date_to_str = $date_to . ' 23:59:59';
    $where_conditions[] = '(submission_date <= ? OR created_at <= ?)';
    $params[] = $date_to_str;
    $params[] = $date_to_str;
    $types .= 'ss';
}

if (!empty($search)) {
    $where_conditions[] = 'form_data LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}

// WHERE koşullarını SQL sorgusuna ekle
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Form başvurularını çek
$sql = "SELECT fs.*, p.title as popup_title 
        FROM form_submissions fs 
        LEFT JOIN popups p ON fs.popup_id = p.id 
        $where_clause 
        ORDER BY 
            CASE 
                WHEN fs.created_at IS NOT NULL AND fs.created_at != '0000-00-00 00:00:00' THEN fs.created_at
                WHEN fs.submission_date IS NOT NULL AND fs.submission_date != '0000-00-00 00:00:00' THEN fs.submission_date
                ELSE '1970-01-01 00:00:00'
            END DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    fclose($output);
    http_response_code(500);
    echo 'Sorgu hazırlanamadı';
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Durum metinleri
$status_text = [
    0 => 'Bekliyor',
    1 => 'İşlendi',
    2 => 'Reddedildi',
];

// Her başvuru için satır oluştur
while ($row = $result->fetch_assoc()) {
    $form_data = json_decode($row['form_data'], true);

    // Form türü
    $form_type = '';
    $form_name = '';

    if (!empty($row['popup_id'])) {
        $form_type = 'Popup';
        $form_name = $row['popup_title'] ?? 'Bilinmeyen Form';
    } else {
        $form_type = 'İletişim';
        $form_name = 'İletişim Formu';
    }

    // Tarih kontrolü ve düzeltmesi
    $submissionDate = null;
    if (!empty($row['submission_date']) && $row['submission_date'] != '0000-00-00 00:00:00') {
        $submissionDate = $row['submission_date'];
    } elseif (!empty($row['created_at']) && $row['created_at'] != '0000-00-00 00:00:00') {
        $submissionDate = $row['created_at'];
    } else {
        $submissionDate = date('Y-m-d H:i:s');
    }

    // Temel alanları çıkar
    $name = '';
    $email = '';
    $phone = '';
    $message = '';
    $other_data = [];

    if (!empty($form_data)) {
        foreach ($form_data as $key => $value) {
            $key_lower = strtolower((string) $key);

            if (in_array($key_lower, ['name', 'full_name', 'fullname', 'ad_soyad'], true) && empty($name)) {
                $name = (string) $value;
            } elseif (in_array($key_lower, ['email', 'e_posta', 'eposta', 'mail'], true) && empty($email)) {
                $email = (string) $value;
            } elseif (in_array($key_lower, ['phone', 'telefon', 'tel', 'mobile'], true) && empty($phone)) {
                $phone = (string) $value;
            } elseif (in_array($key_lower, ['message', 'mesaj', 'content', 'icerik'], true) && empty($message)) {
                $message = (string) $value;
            } else {
                // Diğer veriler
                $other_data[] = $key . ': ' . $value;
            }
        }
    }

    // Diğer verileri birleştir
    $other_data_str = implode(' | ', $other_data);

    // CSV satırı
    fputcsv($output, [
        $row['id'],
        date('d.m.Y H:i', strtotime((string) $submissionDate)),
        $form_type,
        $form_name,
        $status_text[$row['status']] ?? 'Bilinmeyen',
        $row['ip_address'] ?? 'Bilinmiyor',
        $name,
        $email,
        $phone,
        $message,
        $other_data_str,
        $row['notes'] ?? '',
    ]);
}

$stmt->close();
fclose($output);
exit;
