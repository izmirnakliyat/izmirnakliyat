<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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

// Popup ayarları kontrol et
if (!isset($settings['popup_enabled']) || !$settings['popup_enabled']) {
    die('Bu form popup olarak yapılandırılmamış');
}

$step_by_step = isset($settings['step_by_step']) && $settings['step_by_step'];
$popup_width = $settings['popup_width'] ?? 'medium';
$popup_title = $form['title'];

// Width değerlerini CSS sınıflarına dönüştür
$width_classes = [
    'small' => 'modal-sm',
    'medium' => '',
    'large' => 'modal-lg',
    'full' => 'modal-fullscreen'
];
$modal_size = $width_classes[$popup_width] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($popup_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    
    <style>
        .popup-form-modal .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .popup-form-modal .modal-header {
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .popup-form-modal .modal-body {
            padding: 30px;
        }
        
        .popup-form-modal .btn-close {
            filter: invert(1);
        }
        
        .form-step {
            display: none;
            animation: fadeInUp 0.5s ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        .step-progress {
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .step-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
            border-radius: 3px;
        }
        
        .step-info {
            text-align: center;
            margin-bottom: 25px;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control, .form-select {
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-nav {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 500;
            min-width: 120px;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .field-error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- Popup Form Modal -->
<div class="modal fade popup-form-modal" id="popupFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog <?php echo $modal_size; ?> modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo htmlspecialchars($popup_title); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($form['description']): ?>
                    <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($form['description'])); ?></p>
                <?php endif; ?>
                
                <form id="popupForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                    <input type="hidden" name="popup_form" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <?php if ($step_by_step && count($fields) > 1): ?>
                        <!-- Step Progress -->
                        <div class="step-progress">
                            <div class="step-progress-bar" id="progressBar"></div>
                        </div>
                        <div class="step-info">
                            <span id="stepInfo">1. adım / <?php echo count($fields); ?> adım</span>
                        </div>
                    <?php endif; ?>
                    
                    <div id="formContent">
                        <?php if ($step_by_step && count($fields) > 1): ?>
                            <!-- Step by step form -->
                            <?php foreach ($fields as $index => $field): ?>
                                <div class="form-step <?php echo $index === 0 ? 'active' : ''; ?>" data-step="<?php echo $index; ?>">
                                    <?php echo renderPopupFormField($field, $index); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Regular form -->
                            <?php foreach ($fields as $index => $field): ?>
                                <div class="form-group">
                                    <?php echo renderPopupFormField($field, $index); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4" id="formNavigation">
                        <?php if ($step_by_step && count($fields) > 1): ?>
                            <button type="button" class="btn btn-outline-secondary btn-nav" id="prevBtn" style="display: none;">
                                <i class="bx bx-chevron-left"></i> Önceki
                            </button>
                            <button type="button" class="btn btn-primary-custom btn-nav" id="nextBtn">
                                İleri <i class="bx bx-chevron-right"></i>
                            </button>
                            <button type="submit" class="btn btn-success btn-nav" id="submitBtn" style="display: none;">
                                <i class="bx bx-check"></i> Gönder
                            </button>
                        <?php else: ?>
                            <div></div>
                            <button type="submit" class="btn btn-primary-custom btn-nav">
                                <i class="bx bx-send"></i> Gönder
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <div id="successMessage" style="display: none;">
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="bx bx-check-circle"></i>
                        </div>
                        <h4>Teşekkürler!</h4>
                        <p><?php echo htmlspecialchars($form['success_message'] ?? 'Formunuz başarıyla gönderildi.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
class PopupFormManager {
    constructor() {
        this.currentStep = 0;
        this.totalSteps = <?php echo count($fields); ?>;
        this.stepByStep = <?php echo $step_by_step ? 'true' : 'false'; ?>;
        this.formData = new FormData();
        
        this.init();
    }
    
    init() {
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('popupFormModal'));
        modal.show();
        
        if (this.stepByStep) {
            this.setupStepNavigation();
            this.updateProgress();
        }
        
        this.setupFormSubmission();
    }
    
    setupStepNavigation() {
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        nextBtn.addEventListener('click', () => this.nextStep());
        prevBtn.addEventListener('click', () => this.prevStep());
    }
    
    setupFormSubmission() {
        document.getElementById('popupForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });
    }
    
    nextStep() {
        if (!this.validateCurrentStep()) {
            return;
        }
        
        this.saveCurrentStepData();
        
        if (this.currentStep < this.totalSteps - 1) {
            this.currentStep++;
            this.showStep(this.currentStep);
            this.updateProgress();
            this.updateNavigation();
        }
    }
    
    prevStep() {
        if (this.currentStep > 0) {
            this.currentStep--;
            this.showStep(this.currentStep);
            this.updateProgress();
            this.updateNavigation();
        }
    }
    
    showStep(stepIndex) {
        document.querySelectorAll('.form-step').forEach((step, index) => {
            step.classList.toggle('active', index === stepIndex);
        });
    }
    
    updateProgress() {
        const progress = ((this.currentStep + 1) / this.totalSteps) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.getElementById('stepInfo').textContent = `${this.currentStep + 1}. adım / ${this.totalSteps} adım`;
    }
    
    updateNavigation() {
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        prevBtn.style.display = this.currentStep > 0 ? 'block' : 'none';
        
        if (this.currentStep === this.totalSteps - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
    }
    
    validateCurrentStep() {
        const currentStepElement = document.querySelector(`.form-step[data-step="${this.currentStep}"]`);
        const inputs = currentStepElement.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        inputs.forEach(input => {
            this.clearFieldError(input);
            
            if (input.hasAttribute('required') && !input.value.trim()) {
                this.showFieldError(input, 'Bu alan zorunludur');
                isValid = false;
            }
            
            if (input.type === 'email' && input.value && !this.isValidEmail(input.value)) {
                this.showFieldError(input, 'Geçerli bir e-posta adresi giriniz');
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    saveCurrentStepData() {
        const currentStepElement = document.querySelector(`.form-step[data-step="${this.currentStep}"]`);
        const inputs = currentStepElement.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (input.type === 'file') {
                if (input.files.length > 0) {
                    this.formData.set(input.name, input.files[0]);
                }
            } else if (input.type === 'checkbox') {
                this.formData.set(input.name, input.checked ? '1' : '0');
            } else if (input.type === 'radio') {
                if (input.checked) {
                    this.formData.set(input.name, input.value);
                }
            } else {
                this.formData.set(input.name, input.value);
            }
        });
    }
    
    submitForm() {
        if (this.stepByStep) {
            if (!this.validateCurrentStep()) {
                return;
            }
            this.saveCurrentStepData();
        } else {
            // Regular form validation
            const form = document.getElementById('popupForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Collect all form data
            const formData = new FormData(form);
            this.formData = formData;
        }
        
        // Add form metadata
        this.formData.set('form_id', '<?php echo $form_id; ?>');
        this.formData.set('popup_form', '1');
        this.formData.set('csrf_token', <?php echo json_encode($_SESSION['csrf_token'] ?? '', JSON_UNESCAPED_UNICODE); ?>);
        
        // Submit form
        fetch('ajax/process_form.php', {
            method: 'POST',
            body: this.formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccess();
            } else {
                alert('Hata: ' + (data.message || 'Form gönderilemedi'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
    
    showSuccess() {
        document.getElementById('formContent').style.display = 'none';
        document.getElementById('formNavigation').style.display = 'none';
        document.getElementById('successMessage').style.display = 'block';
        
        // Notify parent window
        if (window.parent && window.parent !== window) {
            window.parent.postMessage('popup_form_success', '*');
        }
        
        // Auto close after 3 seconds
        setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('popupFormModal'));
            if (modal) {
                modal.hide();
            }
            
            // Also notify parent to close
            if (window.parent && window.parent !== window) {
                window.parent.postMessage('popup_form_close', '*');
            }
        }, 3000);
    }
    
    showFieldError(input, message) {
        this.clearFieldError(input);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        input.classList.add('is-invalid');
        input.parentNode.appendChild(errorDiv);
    }
    
    clearFieldError(input) {
        input.classList.remove('is-invalid');
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

// Initialize popup form when page loads
document.addEventListener('DOMContentLoaded', function() {
    new PopupFormManager();
});
</script>

</body>
</html>

<?php
function renderPopupFormField($field, $index) {
    $required = $field['required'] ? 'required' : '';
    $requiredMark = $field['required'] ? ' <span class="text-danger">*</span>' : '';
    
    $html = '<div class="form-group">';
    $html .= '<label class="form-label fw-bold">' . htmlspecialchars($field['label']) . $requiredMark . '</label>';
    
    switch ($field['type']) {
        case 'text':
        case 'email':
        case 'tel':
        case 'date':
        case 'number':
            $html .= '<input type="' . $field['type'] . '" class="form-control" name="field_' . $index . '" ';
            $html .= 'placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '" ' . $required . '>';
            break;
            
        case 'textarea':
            $html .= '<textarea class="form-control" name="field_' . $index . '" rows="4" ';
            $html .= 'placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '" ' . $required . '></textarea>';
            break;
            
        case 'select':
            $html .= '<select class="form-select" name="field_' . $index . '" ' . $required . '>';
            $html .= '<option value="">Seçiniz...</option>';
            foreach ($field['options'] as $option) {
                $html .= '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
            }
            $html .= '</select>';
            break;
            
        case 'radio':
            foreach ($field['options'] as $i => $option) {
                $html .= '<div class="form-check mb-2">';
                $html .= '<input type="radio" class="form-check-input" name="field_' . $index . '" ';
                $html .= 'value="' . htmlspecialchars($option) . '" id="field_' . $index . '_' . $i . '" ' . $required . '>';
                $html .= '<label class="form-check-label" for="field_' . $index . '_' . $i . '">' . htmlspecialchars($option) . '</label>';
                $html .= '</div>';
            }
            break;
            
        case 'checkbox':
            $html .= '<div class="form-check">';
            $html .= '<input type="checkbox" class="form-check-input" name="field_' . $index . '" ';
            $html .= 'value="1" id="field_' . $index . '" ' . $required . '>';
            $html .= '<label class="form-check-label" for="field_' . $index . '">';
            $html .= htmlspecialchars($field['placeholder'] ?: $field['label']) . '</label>';
            $html .= '</div>';
            break;
            
        case 'file':
            $html .= '<input type="file" class="form-control" name="field_' . $index . '" ' . $required . '>';
            break;
    }
    
    $html .= '</div>';
    return $html;
}
?>
