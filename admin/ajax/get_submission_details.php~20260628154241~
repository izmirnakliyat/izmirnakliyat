<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

// Response headers
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Form ID gereklidir.'
    ]);
    exit;
}

$id = (int)$_GET['id'];

// Get submission data from database
$stmt = $conn->prepare("SELECT fs.*, p.title as popup_title, f.fields as form_fields, f.title as form_title
                        FROM form_submissions fs 
                        LEFT JOIN popups p ON fs.popup_id = p.id 
                        LEFT JOIN forms f ON fs.form_id = f.id
                        WHERE fs.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Başvuru bulunamadı.'
    ]);
    exit;
}

$submission = $result->fetch_assoc();

// Parse form data
$form_data = json_decode($submission['form_data'], true);
$attachments = !empty($submission['attachments']) ? json_decode($submission['attachments'], true) : [];

// Form field mapping function
function getFieldLabel($fieldKey, $formFields = null) {
    // Teklif alma formu için özel mapping - sıralı field mapping
    static $fieldOrder = [
        1 => 'Ad Soyad',
        2 => 'Telefon',
        3 => 'Ev Tipi', 
        4 => 'Kat',
        5 => 'Oda Sayısı',
        6 => 'Asansör',
        7 => 'Taşınma Tarihi',
        8 => 'Nereden Nereye'
    ];
    
    static $fieldCounter = 0;
    
    // Field ID'si "Field " ile başlıyorsa timestamp ID'si var
    if (strpos($fieldKey, 'Field ') === 0) {
        $fieldCounter++;
        if (isset($fieldOrder[$fieldCounter])) {
            return $fieldOrder[$fieldCounter];
        }
        return 'Alan ' . $fieldCounter;
    }
    
    // Genel form alanları için mapping
    $fieldMappings = [
        'field_name' => 'Ad Soyad',
        'field_phone' => 'Telefon',
        'field_ev_tipi' => 'Ev Tipi',
        'field_kat' => 'Kat',
        'field_oda_sayisi' => 'Oda Sayısı',
        'field_asansor' => 'Asansör',
        'field_tasima_tarihi' => 'Taşınma Tarihi',
        'field_nereden_nereye' => 'Nereden Nereye',
        
        // Genel form alanları
        'name' => 'Ad Soyad',
        'full_name' => 'Ad Soyad',
        'fullname' => 'Ad Soyad',
        'ad_soyad' => 'Ad Soyad',
        'email' => 'E-posta',
        'e_posta' => 'E-posta',
        'eposta' => 'E-posta',
        'mail' => 'E-posta',
        'phone' => 'Telefon',
        'telefon' => 'Telefon',
        'tel' => 'Telefon',
        'mobile' => 'Telefon',
        'message' => 'Mesaj',
        'mesaj' => 'Mesaj',
        'subject' => 'Konu',
        'konu' => 'Konu',
        'address' => 'Adres',
        'adres' => 'Adres',
        'company' => 'Şirket',
        'sirket' => 'Şirket',
        'firma' => 'Şirket'
    ];
    
    // Önce mapping'de ara
    if (isset($fieldMappings[$fieldKey])) {
        return $fieldMappings[$fieldKey];
    }
    
    // Eğer form fields JSON'ı varsa, oradan label'ı al
    if ($formFields) {
        $fields = json_decode($formFields, true);
        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (isset($field['name']) && $field['name'] === $fieldKey && isset($field['label'])) {
                    return $field['label'];
                }
            }
        }
    }
    
    // Varsayılan olarak key'i düzenle
    return ucfirst(str_replace(['_', '-'], ' ', $fieldKey));
}

// Check and fix submission date
$submissionDate = null;
if (!empty($submission['submission_date']) && $submission['submission_date'] != '0000-00-00 00:00:00') {
    $submissionDate = $submission['submission_date'];
} else if (!empty($submission['created_at']) && $submission['created_at'] != '0000-00-00 00:00:00') {
    $submissionDate = $submission['created_at'];
} else {
    $submissionDate = date('Y-m-d H:i:s');
}

// Form title
$form_title = '';
if (!empty($submission['form_title'])) {
    $form_title = $submission['form_title'];
} elseif (!empty($submission['popup_id'])) {
    $form_title = $submission['popup_title'] ?? 'Bilinmeyen Popup Form';
} else {
    $form_title = 'İletişim Formu';
}

// Build HTML
$html = '<div class="submission-details">';
$html .= '<div class="card mb-3">';
$html .= '<div class="card-header">';
$html .= '<h5 class="mb-0">Form Bilgileri</h5>';
$html .= '</div>';
$html .= '<div class="card-body">';
$html .= '<table class="table table-bordered">';
$html .= '<tr><th style="width: 200px">Form Tipi</th><td>';

if (!empty($submission['form_title'])) {
    $html .= '<span class="badge bg-success me-1">Özel Form</span> ' . htmlspecialchars($form_title);
} elseif (!empty($submission['popup_id'])) {
    $html .= '<span class="badge bg-info me-1">Popup</span> ' . htmlspecialchars($form_title);
} else {
    $html .= '<span class="badge bg-primary me-1">İletişim</span> ' . htmlspecialchars($form_title);
}

$html .= '</td></tr>';
$html .= '<tr><th>Tarih</th><td>' . date('d.m.Y H:i', strtotime($submissionDate)) . '</td></tr>';
$html .= '<tr><th>IP Adresi</th><td>' . htmlspecialchars($submission['ip_address'] ?? 'Bilinmiyor') . '</td></tr>';
$html .= '<tr><th>Durum</th><td>';

// Status badge
if ($submission['status'] == 0) {
    $html .= '<span class="badge bg-secondary">Bekliyor</span>';
} elseif ($submission['status'] == 1) {
    $html .= '<span class="badge bg-success">İşlendi</span>';
} else {
    $html .= '<span class="badge bg-danger">Reddedildi</span>';
}

if (!empty($submission['notes'])) {
    $html .= '<div class="mt-2"><strong>Notlar:</strong> ' . htmlspecialchars($submission['notes']) . '</div>';
}

$html .= '</td></tr>';
$html .= '</table>';
$html .= '</div>';
$html .= '</div>';

// Form data
$html .= '<div class="card">';
$html .= '<div class="card-header">';
$html .= '<h5 class="mb-0">Form Verileri</h5>';
$html .= '</div>';
$html .= '<div class="card-body">';
$html .= '<table class="table table-bordered">';

if (!empty($form_data)) {
    // Teklif alma formu için alan isimleri (sıralı)
    $fieldLabels = [
        'Ad Soyad',
        'Telefon', 
        'Ev Tipi',
        'Kat',
        'Oda Sayısı',
        'Asansör',
        'Taşınma Tarihi',
        'Nereden Nereye'
    ];
    
    $fieldIndex = 0;
    foreach ($form_data as $key => $value) {
        // Her türlü Field formatını yakala
        if (stripos($key, 'field') !== false && preg_match('/\d/', $key)) {
            // Field içeren ve sayı içeren tüm key'ler için sıralı mapping
            if (isset($fieldLabels[$fieldIndex])) {
                $fieldLabel = $fieldLabels[$fieldIndex];
            } else {
                $fieldLabel = 'Alan ' . ($fieldIndex + 1);
            }
            $fieldIndex++;
        } else {
            // Standart alan isimleri için mapping
            $fieldMappings = [
                'name' => 'Ad Soyad',
                'full_name' => 'Ad Soyad',
                'phone' => 'Telefon',
                'email' => 'E-posta',
                'message' => 'Mesaj'
            ];
            
            $fieldLabel = isset($fieldMappings[strtolower($key)]) 
                ? $fieldMappings[strtolower($key)] 
                : ucfirst(str_replace(['_', '-'], ' ', $key));
        }
        
        $html .= '<tr>';
        $html .= '<th style="width: 200px">' . htmlspecialchars($fieldLabel) . '</th>';
        $html .= '<td>' . htmlspecialchars($value) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="2" class="text-center">Form verisi bulunamadı.</td></tr>';
}

$html .= '</table>';

// Attachments
if (!empty($attachments)) {
    $html .= '<div class="mt-4">';
    $html .= '<h6>Ekler</h6>';
    $html .= '<ul class="list-group">';
    
    foreach ($attachments as $field_name => $file_path) {
        $file_name = basename($file_path);
        $file_url = '../../' . $file_path;
        $fieldLabel = getFieldLabel($field_name, $submission['form_fields']);
        
        $html .= '<li class="list-group-item">';
        $html .= '<strong>' . htmlspecialchars($fieldLabel) . ':</strong> ';
        $html .= '<a href="' . htmlspecialchars($file_url) . '" target="_blank" class="ms-2">';
        $html .= '<i class="bx bx-download"></i> ' . htmlspecialchars($file_name);
        $html .= '</a>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
}

$html .= '</div>';
$html .= '</div>';
$html .= '</div>';

// Return response
echo json_encode([
    'success' => true,
    'html' => $html
]);
?> 