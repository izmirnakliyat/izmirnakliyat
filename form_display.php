<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$embed = isset($_GET['embed']) ? true : false;

if ($form_id == 0) {
    die('Form ID gerekli');
}

// Form verilerini getir
$stmt = $conn->prepare("SELECT * FROM forms WHERE id = ? AND status = 1");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form = mysqli_stmt_fetch_assoc_first($stmt);
$stmt->close();

if (!$form) {
    die('Form bulunamadı');
}

$fields = json_decode($form['fields'], true) ?: [];
$settings = json_decode($form['settings'], true) ?: [];

// Form gönderimi işleme
$form_submitted = false;
$form_success = false;
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_id']) && $_POST['form_id'] == $form_id) {
    $form_submitted = true;
    $form_data = [];
    
    // CSRF koruması (basit token kontrolü)
    $postedTok = $_POST['csrf_token'] ?? '';
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || !is_string($postedTok)
        || !hash_equals($_SESSION['csrf_token'], $postedTok)
    ) {
        $form_errors[] = 'Güvenlik doğrulaması başarısız';
    } else {
        // Alanları doğrula
        foreach ($fields as $field) {
            $field_name = $field['id'];
            $field_value = $_POST[$field_name] ?? '';
            
            // Zorunlu alan kontrolü
            if ($field['required'] && empty($field_value)) {
                $form_errors[] = $field['label'] . ' alanı zorunludur';
                continue;
            }
            
            // Email doğrulaması
            if ($field['type'] === 'email' && !empty($field_value) && !filter_var($field_value, FILTER_VALIDATE_EMAIL)) {
                $form_errors[] = $field['label'] . ' geçerli bir email adresi olmalıdır';
                continue;
            }
            
            // Telefon doğrulaması (basit)
            if ($field['type'] === 'tel' && !empty($field_value) && !preg_match('/^[\d\s\+\-\(\)]+$/', $field_value)) {
                $form_errors[] = $field['label'] . ' geçerli bir telefon numarası olmalıdır';
                continue;
            }
            
            // Dosya yükleme işlemi
            if ($field['type'] === 'file' && !empty($_FILES[$field_name]['name'])) {
                $upload_dir = 'uploads/form_attachments/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
                
                if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                    $form_errors[] = $field['label'] . ' için geçersiz dosya türü';
                    continue;
                }
                
                if ($_FILES[$field_name]['size'] > 5 * 1024 * 1024) { // 5MB limit
                    $form_errors[] = $field['label'] . ' dosyası çok büyük (max 5MB)';
                    continue;
                }
                
                $filename = time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES[$field_name]['tmp_name'], $upload_path)) {
                    $field_value = $filename;
                } else {
                    $form_errors[] = $field['label'] . ' dosyası yüklenirken hata oluştu';
                    continue;
                }
            }
            
            $form_data[$field_name] = $field_value;
        }
        
        // Hata yoksa veritabanına kaydet
        if (empty($form_errors)) {
            // form_submissions tablosunda form_id alanını ekle
            $check_column = $conn->query("SHOW COLUMNS FROM form_submissions LIKE 'form_id'");
            if ($check_column->num_rows == 0) {
                $conn->query("ALTER TABLE form_submissions ADD COLUMN form_id INT(11) NULL AFTER id");
            }
            
            $submission_data = json_encode($form_data, JSON_UNESCAPED_UNICODE);
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            $stmt = $conn->prepare("INSERT INTO form_submissions (form_id, form_data, ip_address, user_agent, submission_date, status) VALUES (?, ?, ?, ?, NOW(), 0)");
            $stmt->bind_param("isss", $form_id, $submission_data, $ip_address, $user_agent);
            
            if ($stmt->execute()) {
                $form_success = true;
                
                // Email bildirimi gönder
                if (!empty($form['email_notifications'])) {
                    $email_body = "Yeni form başvurusu alındı:\n\n";
                    $email_body .= "Form: " . $form['title'] . "\n";
                    $email_body .= "Tarih: " . date('d.m.Y H:i') . "\n\n";
                    
                    foreach ($form_data as $field_id => $value) {
                        $field = array_find($fields, function($f) use ($field_id) {
                            return $f['id'] === $field_id;
                        });
                        if ($field) {
                            $email_body .= $field['label'] . ": " . $value . "\n";
                        }
                    }
                    
                    $headers = "From: " . (ADMIN_EMAIL ?? 'noreply@' . $_SERVER['HTTP_HOST']) . "\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    mail($form['email_notifications'], 'Yeni Form Başvurusu - ' . $form['title'], $email_body, $headers);
                }
                
                // Yönlendirme varsa
                if (!empty($form['redirect_url']) && !$embed) {
                    header('Location: ' . $form['redirect_url']);
                    exit;
                }
            } else {
                $form_errors[] = 'Form gönderilirken bir hata oluştu';
            }
        }
    }
}

// array_find helper function
if (!function_exists('array_find')) {
    function array_find($array, $callback) {
        foreach ($array as $item) {
            if ($callback($item)) {
                return $item;
            }
        }
        return null;
    }
}

// Embed modunda sadece form HTML'i döndür
if ($embed): ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .form-container { max-width: 100%; }
    </style>
</head>
<body>
    <div class="form-container p-3">
<?php endif; ?>

<?php if ($form_success): ?>
    <div class="alert alert-success">
        <?php echo nl2br(htmlspecialchars($form['success_message'] ?: 'Formunuz başarıyla gönderildi.')); ?>
    </div>
<?php else: ?>
    <?php if (!empty($form_errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($form_errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-builder-form">
        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        
        <?php if (!$embed): ?>
            <h2 class="mb-3"><?php echo htmlspecialchars($form['title']); ?></h2>
            
            <?php if (!empty($form['description'])): ?>
                <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($form['description'])); ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php foreach ($fields as $field): ?>
            <div class="mb-3">
                <label class="form-label">
                    <?php echo htmlspecialchars($field['label']); ?>
                    <?php if ($field['required']): ?>
                        <span class="text-danger">*</span>
                    <?php endif; ?>
                </label>
                
                <?php
                $field_value = $form_submitted ? ($_POST[$field['id']] ?? '') : '';
                
                switch ($field['type']):
                    case 'text':
                    case 'email':
                    case 'tel':
                    case 'date':
                    case 'number': ?>
                        <input type="<?php echo $field['type']; ?>" 
                               class="form-control" 
                               name="<?php echo $field['id']; ?>"
                               value="<?php echo htmlspecialchars($field_value); ?>"
                               placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                               <?php echo $field['required'] ? 'required' : ''; ?>>
                    <?php break;
                    
                    case 'textarea': ?>
                        <textarea class="form-control" 
                                  name="<?php echo $field['id']; ?>"
                                  placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                  rows="3"
                                  <?php echo $field['required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($field_value); ?></textarea>
                    <?php break;
                    
                    case 'select': ?>
                        <select class="form-control" name="<?php echo $field['id']; ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($field['options'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" 
                                        <?php echo $field_value === $option ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php break;
                    
                    case 'radio': ?>
                        <?php foreach ($field['options'] as $i => $option): ?>
                            <div class="form-check">
                                <input type="radio" 
                                       class="form-check-input" 
                                       name="<?php echo $field['id']; ?>" 
                                       value="<?php echo htmlspecialchars($option); ?>"
                                       id="<?php echo $field['id'] . '_' . $i; ?>"
                                       <?php echo $field_value === $option ? 'checked' : ''; ?>
                                       <?php echo $field['required'] ? 'required' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $field['id'] . '_' . $i; ?>">
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php break;
                    
                    case 'checkbox': ?>
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="<?php echo $field['id']; ?>"
                                   value="1"
                                   id="<?php echo $field['id']; ?>"
                                   <?php echo $field_value ? 'checked' : ''; ?>
                                   <?php echo $field['required'] ? 'required' : ''; ?>>
                            <label class="form-check-label" for="<?php echo $field['id']; ?>">
                                <?php echo htmlspecialchars($field['placeholder'] ?: $field['label']); ?>
                            </label>
                        </div>
                    <?php break;
                    
                    case 'file': ?>
                        <input type="file" 
                               class="form-control" 
                               name="<?php echo $field['id']; ?>"
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                               <?php echo $field['required'] ? 'required' : ''; ?>>
                        <small class="text-muted">İzin verilen: JPG, PNG, GIF, PDF, DOC, DOCX, TXT (Max: 5MB)</small>
                    <?php break;
                endswitch; ?>
            </div>
        <?php endforeach; ?>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
                Gönder
            </button>
        </div>
    </form>
<?php endif; ?>

<?php if ($embed): ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?> 