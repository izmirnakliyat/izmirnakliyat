<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

// AJAX ile popup içeriğini getir
header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Popup ID gerekli.']);
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM popups WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Popup bulunamadı.']);
    exit;
}

$popup = $result->fetch_assoc();

// Check if content is JSON (new format)
$rendered_content = '';
$content_data = json_decode($popup['content'], true);

if (json_last_error() === JSON_ERROR_NONE && isset($content_data['type']) && $content_data['type'] === 'form') {
    // This is a form popup, render it
    $form_elements = $content_data['elements'] ?? [];
    $settings = $content_data['settings'] ?? [];
    
    $intro_text = $settings['intro_text'] ?? 'Lütfen aşağıdaki formu doldurarak bizimle iletişime geçin.';
    $submit_text = $settings['submit_text'] ?? 'Gönder';
    
    // Start building the form
    $rendered_content = '<div class="popup-form-preview">';
    $rendered_content .= '<p>' . htmlspecialchars($intro_text) . '</p>';
    $rendered_content .= '<form class="form-preview">';
    
    // Add form elements
    foreach ($form_elements as $element) {
        $type = $element['type'] ?? 'text';
        $label = $element['label'] ?? '';
        $name = $element['name'] ?? '';
        $placeholder = $element['placeholder'] ?? '';
        $required = isset($element['required']) && $element['required'] ? 'required' : '';
        $options = $element['options'] ?? [];
        
        $rendered_content .= '<div class="mb-3">';
        
        // Add label if it exists
        if (!empty($label)) {
            $rendered_content .= '<label class="form-label">' . htmlspecialchars($label) . '</label>';
        }
        
        // Render different input types
        switch ($type) {
            case 'textarea':
                $rendered_content .= '<textarea class="form-control" name="' . htmlspecialchars($name) . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . ' rows="3"></textarea>';
                break;
                
            case 'select':
                $rendered_content .= '<select class="form-control" name="' . htmlspecialchars($name) . '" ' . $required . '>';
                $rendered_content .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
                foreach ($options as $option) {
                    $rendered_content .= '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                }
                $rendered_content .= '</select>';
                break;
                
            case 'checkbox':
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $rendered_content .= '<div class="form-check">';
                        $rendered_content .= '<input class="form-check-input" type="checkbox" name="' . htmlspecialchars($name) . '[]" value="' . htmlspecialchars($option) . '">';
                        $rendered_content .= '<label class="form-check-label">' . htmlspecialchars($option) . '</label>';
                        $rendered_content .= '</div>';
                    }
                } else {
                    $rendered_content .= '<div class="form-check">';
                    $rendered_content .= '<input class="form-check-input" type="checkbox" name="' . htmlspecialchars($name) . '" ' . $required . '>';
                    $rendered_content .= '<label class="form-check-label">' . htmlspecialchars($label) . '</label>';
                    $rendered_content .= '</div>';
                }
                break;
                
            case 'radio':
                foreach ($options as $option) {
                    $rendered_content .= '<div class="form-check">';
                    $rendered_content .= '<input class="form-check-input" type="radio" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($option) . '" ' . $required . '>';
                    $rendered_content .= '<label class="form-check-label">' . htmlspecialchars($option) . '</label>';
                    $rendered_content .= '</div>';
                }
                break;
                
            case 'file':
                $rendered_content .= '<input type="file" class="form-control" name="' . htmlspecialchars($name) . '" ' . $required . '>';
                break;
                
            case 'agreement':
                $agreementText = isset($element['agreementText']) ? $element['agreementText'] : '';
                $rendered_content .= '<div class="agreement-container" style="background-color: #f9f9f9; padding: 12px; border-radius: 5px; border: 1px solid #eee;">';
                $rendered_content .= '<div class="form-check" style="display: flex; align-items: flex-start;">';
                $rendered_content .= '<input class="form-check-input" type="checkbox" id="preview_' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" ' . $required . ' style="margin-right: 10px; margin-top: 4px;">';
                $rendered_content .= '<label class="form-check-label" for="preview_' . htmlspecialchars($name) . '" style="font-size: 14px; color: #555;">' . $agreementText . '</label>';
                $rendered_content .= '</div>';
                $rendered_content .= '</div>';
                break;
                
            default: // text, email, tel, number, date, etc.
                $rendered_content .= '<input type="' . $type . '" class="form-control" name="' . htmlspecialchars($name) . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . '>';
                break;
        }
        
        $rendered_content .= '</div>';
    }
    
    // Submit button
    $rendered_content .= '<div class="form-actions" style="text-align: center; margin-top: 20px;">';
    $rendered_content .= '<button type="button" class="btn btn-primary" style="background-color: #007bff; color: white; border: none; padding: 12px 30px; font-size: 15px; font-weight: 500; border-radius: 5px; cursor: pointer; width: 100%;">' . htmlspecialchars($submit_text) . '</button>';
    $rendered_content .= '</div>';
    
    // Success message (only for preview)
    $rendered_content .= '<div class="preview-note" style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #ddd; font-size: 12px; color: #666;">';
    $rendered_content .= '<p style="margin: 0;"><strong>Not:</strong> Form gönderimi önizleme modunda çalışmaz.</p>';
    $rendered_content .= '</div>';
    
    $rendered_content .= '</form>';
    $rendered_content .= '</div>';
} else {
    // Old format or raw HTML, just use as is
    $rendered_content = $popup['content'];
}

echo json_encode([
    'success' => true, 
    'id' => $popup['id'],
    'title' => $popup['title'],
    'content' => $rendered_content,
    'status' => $popup['status']
]); 