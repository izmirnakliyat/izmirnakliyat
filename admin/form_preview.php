<?php
require_once __DIR__ . '/includes/require_admin_web.php';

$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($form_id == 0) {
    die('Form ID gerekli');
}

// Form verilerini getir
$stmt = $conn->prepare("SELECT * FROM forms WHERE id = ? AND status = 1");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();

if (!$form) {
    die('Form bulunamadı');
}

$fields = json_decode($form['fields'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title']); ?> - Önizleme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .form-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .preview-badge { position: fixed; top: 10px; right: 10px; z-index: 1000; }
    </style>
</head>
<body>
    <div class="preview-badge">
        <span class="badge bg-warning text-dark">Önizleme Modu</span>
    </div>

    <div class="form-container">
        <h2 class="mb-3"><?php echo htmlspecialchars($form['title']); ?></h2>
        
        <?php if (!empty($form['description'])): ?>
            <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($form['description'])); ?></p>
        <?php endif; ?>

        <form id="previewForm">
            <?php foreach ($fields as $field): ?>
                <div class="mb-3">
                    <label class="form-label">
                        <?php echo htmlspecialchars($field['label']); ?>
                        <?php if ($field['required']): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php switch ($field['type']): 
                        case 'text':
                        case 'email':
                        case 'tel':
                        case 'date':
                        case 'number': ?>
                            <input type="<?php echo $field['type']; ?>" 
                                   class="form-control" 
                                   name="<?php echo $field['id']; ?>"
                                   placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                   <?php echo $field['required'] ? 'required' : ''; ?>>
                        <?php break;
                        
                        case 'textarea': ?>
                            <textarea class="form-control" 
                                      name="<?php echo $field['id']; ?>"
                                      placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                      rows="3"
                                      <?php echo $field['required'] ? 'required' : ''; ?>></textarea>
                        <?php break;
                        
                        case 'select': ?>
                            <select class="form-control" name="<?php echo $field['id']; ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                                <option value="">Seçiniz...</option>
                                <?php foreach ($field['options'] as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>">
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
                                       id="<?php echo $field['id']; ?>"
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
                                   <?php echo $field['required'] ? 'required' : ''; ?>>
                        <?php break;
                    endswitch; ?>
                </div>
            <?php endforeach; ?>

            <div class="d-grid">
                <button type="button" class="btn btn-primary btn-lg" onclick="showPreviewAlert()">
                    Gönder (Önizleme)
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showPreviewAlert() {
            alert('Bu önizleme modudur. Form gerçekten gönderilmez.\n\nForm canlı sitede normal şekilde çalışacaktır.');
        }
    </script>
</body>
</html> 