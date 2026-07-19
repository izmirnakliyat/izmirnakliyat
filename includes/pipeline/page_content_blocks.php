<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/mynak_resource_link_fixer.php';

/**
 * Gömülü formlar ve [blok:...] / [form id=] kısa kodları — DB erişimi bu pipeline dosyasında toplanır.
 * Bağımlılıklar: functions.php (html_etiketlerini_duzelt, demote_inline_h1_to_h2, mynak_normalize_html_href_attributes).
 */
function mynak_render_embedded_form(mysqli $conn, $form_id, $custom_settings = []) {
    // Form verilerini getir
    $stmt = $conn->prepare("SELECT * FROM forms WHERE id = ? AND status = 1");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $form = $result->fetch_assoc();
    
    if (!$form) {
        return '<div class="alert alert-warning">Form bulunamadı (ID: ' . intval($form_id) . ')</div>';
    }
    
    $fields = json_decode($form['fields'], true) ?: [];
    $settings = json_decode($form['settings'], true) ?: [];
    
    // Özel ayarları birleştir
    $settings = array_merge($settings, $custom_settings);
    
    // Ayarları al
    $step_by_step = $settings['embed_step_by_step'] ?? false;
    $embed_style = $settings['embed_style'] ?? 'default';
    $embed_width = $settings['embed_width'] ?? 'medium';
    $show_title = $settings['embed_show_title'] ?? true;
    $show_description = $settings['embed_show_description'] ?? true;
    $button_text = $settings['embed_button_text'] ?? 'Gönder';
    $button_style = $settings['embed_button_style'] ?? 'primary';
    
    // Genişlik CSS
    $width_css = [
        'full' => 'width: 100%;',
        'large' => 'max-width: 800px;',
        'medium' => 'max-width: 600px;',
        'small' => 'max-width: 400px;'
    ];
    $width = $width_css[$embed_width] ?? 'max-width: 600px;';
    
    // Stil CSS
    $style_css = [
        'default' => '',
        'card' => 'background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 30px;',
        'bordered' => 'border: 2px solid #e0e0e0; border-radius: 8px; padding: 25px;',
        'minimal' => 'padding: 20px 0;'
    ];
    $style = $style_css[$embed_style] ?? '';
    
    // Benzersiz form ID oluştur
    $unique_id = 'embedded_form_' . $form_id . '_' . uniqid();
    $total_steps = count($fields);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    mynak_ensure_csrf_token();
    
    // HTML oluştur
    $html = '';
    
    // CSS stilleri (bir kez yükle)
    static $css_loaded = false;
    if (!$css_loaded) {
        $html .= '<style>
        .embedded-form-container {
            margin: 20px auto;
        }
        .embedded-form-container .form-group {
            margin-bottom: 20px;
        }
        .embedded-form-container .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #333;
        }
        .embedded-form-container .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }
        .embedded-form-container .form-control:focus {
            outline: none;
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
        }
        .embedded-form-container .form-check {
            margin-bottom: 10px;
        }
        .embedded-form-container .form-check-input {
            margin-right: 8px;
        }
        .embedded-form-container .btn-submit,
        .embedded-form-container .btn-nav {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .embedded-form-container .btn-submit:hover,
        .embedded-form-container .btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .embedded-form-container .btn-primary { background: #4e73df; color: #fff; }
        .embedded-form-container .btn-success { background: #1cc88a; color: #fff; }
        .embedded-form-container .btn-danger { background: #e74a3b; color: #fff; }
        .embedded-form-container .btn-warning { background: #f6c23e; color: #333; }
        .embedded-form-container .btn-dark { background: #333; color: #fff; }
        .embedded-form-container .btn-secondary { background: #6c757d; color: #fff; }
        .embedded-form-container .btn-outline { background: transparent; border: 2px solid #4e73df; color: #4e73df; }
        .embedded-form-container .form-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }
        .embedded-form-container .form-description {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .embedded-form-container .required-mark {
            color: #e74a3b;
        }
        .embedded-form-container .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .embedded-form-container .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        /* Step by Step Styles */
        .embedded-form-container .step-progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .embedded-form-container .step-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border-radius: 4px;
            transition: width 0.4s ease;
        }
        .embedded-form-container .step-info {
            text-align: center;
            margin-bottom: 25px;
            color: #666;
            font-size: 14px;
        }
        .embedded-form-container .step-info strong {
            color: #4e73df;
        }
        .embedded-form-container .form-step {
            display: none;
            animation: stepFadeIn 0.4s ease;
        }
        .embedded-form-container .form-step.active {
            display: block;
        }
        @keyframes stepFadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .embedded-form-container .step-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            gap: 15px;
        }
        .embedded-form-container .step-question-label {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .embedded-form-container .field-error {
            color: #e74a3b;
            font-size: 13px;
            margin-top: 5px;
        }
        .embedded-form-container .form-control.is-invalid {
            border-color: #e74a3b;
        }
        </style>';
        $css_loaded = true;
    }
    
    $html .= '<div class="embedded-form-container" id="' . $unique_id . '" style="' . $width . ' ' . $style . '">';
    
    // Başlık
    if ($show_title && !empty($form['title'])) {
        $html .= '<h3 class="form-title">' . htmlspecialchars($form['title']) . '</h3>';
    }
    
    // Açıklama
    if ($show_description && !empty($form['description'])) {
        $html .= '<p class="form-description">' . nl2br(htmlspecialchars($form['description'])) . '</p>';
    }
    
    // Step by Step Progress Bar
    if ($step_by_step && $total_steps > 1) {
        $html .= '<div class="step-progress"><div class="step-progress-bar" id="' . $unique_id . '_progress"></div></div>';
        $html .= '<div class="step-info"><strong>Adım <span id="' . $unique_id . '_current">1</span></strong> / ' . $total_steps . '</div>';
    }
    
    // Form
    $html .= '<form class="embedded-form" method="POST" enctype="multipart/form-data" data-form-id="' . $form_id . '" data-step-mode="' . ($step_by_step ? '1' : '0') . '">';
    $html .= '<input type="hidden" name="embedded_form_id" value="' . $form_id . '">';
    $html .= mynak_csrf_hidden_input();
    
    $html .= '<div class="form-steps-container">';
    
    // Form alanları
    foreach ($fields as $index => $field) {
        $field_name = 'field_' . $index;
        $required = $field['required'] ? 'required' : '';
        $required_mark = $field['required'] ? ' <span class="required-mark">*</span>' : '';
        
        // Step wrapper (adım adım modda)
        if ($step_by_step && $total_steps > 1) {
            $active_class = ($index === 0) ? ' active' : '';
            $html .= '<div class="form-step' . $active_class . '" data-step="' . $index . '">';
        }
        
        $html .= '<div class="form-group">';
        
        // Step modunda büyük etiket
        if ($step_by_step && $total_steps > 1) {
            $html .= '<label class="step-question-label">' . htmlspecialchars($field['label']) . $required_mark . '</label>';
        }
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'tel':
            case 'date':
            case 'number':
                if (!$step_by_step || $total_steps <= 1) {
                    $html .= '<label class="form-label">' . htmlspecialchars($field['label']) . $required_mark . '</label>';
                }
                $html .= '<input type="' . $field['type'] . '" class="form-control" name="' . $field_name . '" ';
                $html .= 'placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '" ' . $required . '>';
                break;
                
            case 'textarea':
                if (!$step_by_step || $total_steps <= 1) {
                    $html .= '<label class="form-label">' . htmlspecialchars($field['label']) . $required_mark . '</label>';
                }
                $html .= '<textarea class="form-control" name="' . $field_name . '" rows="4" ';
                $html .= 'placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '" ' . $required . '></textarea>';
                break;
                
            case 'select':
                if (!$step_by_step || $total_steps <= 1) {
                    $html .= '<label class="form-label">' . htmlspecialchars($field['label']) . $required_mark . '</label>';
                }
                $html .= '<select class="form-control" name="' . $field_name . '" ' . $required . '>';
                $html .= '<option value="">Seçiniz...</option>';
                foreach ($field['options'] as $option) {
                    $html .= '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'radio':
                if (!$step_by_step || $total_steps <= 1) {
                    $html .= '<label class="form-label">' . htmlspecialchars($field['label']) . $required_mark . '</label>';
                }
                foreach ($field['options'] as $i => $option) {
                    $html .= '<div class="form-check">';
                    $html .= '<input type="radio" class="form-check-input" name="' . $field_name . '" ';
                    $html .= 'value="' . htmlspecialchars($option) . '" id="' . $unique_id . '_' . $field_name . '_' . $i . '" ' . $required . '>';
                    $html .= '<label class="form-check-label" for="' . $unique_id . '_' . $field_name . '_' . $i . '">' . htmlspecialchars($option) . '</label>';
                    $html .= '</div>';
                }
                break;
                
            case 'checkbox':
                $html .= '<div class="form-check">';
                $html .= '<input type="checkbox" class="form-check-input" name="' . $field_name . '" ';
                $html .= 'value="1" id="' . $unique_id . '_' . $field_name . '" ' . $required . '>';
                $html .= '<label class="form-check-label" for="' . $unique_id . '_' . $field_name . '">';
                $html .= htmlspecialchars($field['placeholder'] ?: $field['label']) . $required_mark . '</label>';
                $html .= '</div>';
                break;
                
            case 'file':
                if (!$step_by_step || $total_steps <= 1) {
                    $html .= '<label class="form-label">' . htmlspecialchars($field['label']) . $required_mark . '</label>';
                }
                $html .= '<input type="file" class="form-control" name="' . $field_name . '" ' . $required . '>';
                $html .= '<small class="text-muted">İzin verilen: JPG, PNG, GIF, PDF, DOC, DOCX (Max: 5MB)</small>';
                break;
        }
        
        $html .= '</div>';
        
        // Step wrapper kapat
        if ($step_by_step && $total_steps > 1) {
            $html .= '</div>';
        }
    }
    
    $html .= '</div>'; // form-steps-container
    
    // Navigasyon butonları
    if ($step_by_step && $total_steps > 1) {
        $html .= '<div class="step-navigation">';
        $html .= '<button type="button" class="btn-nav btn-outline" id="' . $unique_id . '_prev" style="display: none;"><i class="fa fa-arrow-left" style="margin-right: 8px;"></i> Önceki</button>';
        $html .= '<div></div>'; // Spacer
        $html .= '<button type="button" class="btn-nav btn-' . $button_style . '" id="' . $unique_id . '_next">İleri <i class="fa fa-arrow-right" style="margin-left: 8px;"></i></button>';
        $html .= '<button type="submit" class="btn-submit btn-' . $button_style . '" id="' . $unique_id . '_submit" style="display: none;"><i class="fa fa-paper-plane" style="margin-right: 8px;"></i>' . htmlspecialchars($button_text) . '</button>';
        $html .= '</div>';
    } else {
        // Normal mod - tek gönder butonu
        $html .= '<div class="form-group">';
        $html .= '<button type="submit" class="btn-submit btn-' . $button_style . '">';
        $html .= '<i class="fa fa-paper-plane" style="margin-right: 8px;"></i>' . htmlspecialchars($button_text);
        $html .= '</button>';
        $html .= '</div>';
    }
    
    $html .= '</form>';
    
    // Başarı mesajı container
    $html .= '<div class="success-message" style="display: none;">';
    $html .= htmlspecialchars($form['success_message'] ?: 'Formunuz başarıyla gönderildi.');
    $html .= '</div>';
    
    $html .= '</div>';
    
    // JavaScript
    $html .= '<script>
    (function() {
        var container = document.getElementById("' . $unique_id . '");
        var form = container.querySelector(".embedded-form");
        var successMsg = container.querySelector(".success-message");
        var stepMode = form.dataset.stepMode === "1";
        var totalSteps = ' . $total_steps . ';
        var currentStep = 0;
        
        // Step by Step Mode
        if (stepMode && totalSteps > 1) {
            var steps = container.querySelectorAll(".form-step");
            var prevBtn = document.getElementById("' . $unique_id . '_prev");
            var nextBtn = document.getElementById("' . $unique_id . '_next");
            var submitBtn = document.getElementById("' . $unique_id . '_submit");
            var progressBar = document.getElementById("' . $unique_id . '_progress");
            var currentSpan = document.getElementById("' . $unique_id . '_current");
            
            function updateProgress() {
                var progress = ((currentStep + 1) / totalSteps) * 100;
                progressBar.style.width = progress + "%";
                currentSpan.textContent = currentStep + 1;
            }
            
            function showStep(stepIndex) {
                steps.forEach(function(step, index) {
                    step.classList.toggle("active", index === stepIndex);
                });
                
                prevBtn.style.display = stepIndex > 0 ? "inline-flex" : "none";
                
                if (stepIndex === totalSteps - 1) {
                    nextBtn.style.display = "none";
                    submitBtn.style.display = "inline-flex";
                } else {
                    nextBtn.style.display = "inline-flex";
                    submitBtn.style.display = "none";
                }
                
                updateProgress();
            }
            
            function validateCurrentStep() {
                var currentStepEl = steps[currentStep];
                var inputs = currentStepEl.querySelectorAll("input, select, textarea");
                var isValid = true;
                
                inputs.forEach(function(input) {
                    // Clear previous errors
                    input.classList.remove("is-invalid");
                    var existingError = input.parentNode.querySelector(".field-error");
                    if (existingError) existingError.remove();
                    
                    // Check required
                    if (input.hasAttribute("required")) {
                        var value = input.value.trim();
                        if (input.type === "checkbox" && !input.checked) {
                            showFieldError(input, "Bu alan zorunludur");
                            isValid = false;
                        } else if (input.type === "radio") {
                            var radioName = input.name;
                            var radioGroup = currentStepEl.querySelectorAll("input[name=\'" + radioName + "\']");
                            var radioChecked = Array.from(radioGroup).some(function(r) { return r.checked; });
                            if (!radioChecked) {
                                showFieldError(radioGroup[0], "Lütfen bir seçenek seçin");
                                isValid = false;
                            }
                        } else if (!value) {
                            showFieldError(input, "Bu alan zorunludur");
                            isValid = false;
                        }
                    }
                    
                    // Email validation
                    if (input.type === "email" && input.value) {
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(input.value)) {
                            showFieldError(input, "Geçerli bir e-posta adresi giriniz");
                            isValid = false;
                        }
                    }
                });
                
                return isValid;
            }
            
            function showFieldError(input, message) {
                input.classList.add("is-invalid");
                var errorDiv = document.createElement("div");
                errorDiv.className = "field-error";
                errorDiv.textContent = message;
                input.parentNode.appendChild(errorDiv);
            }
            
            nextBtn.addEventListener("click", function() {
                if (validateCurrentStep()) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
            
            prevBtn.addEventListener("click", function() {
                if (currentStep > 0) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
            
            // Initialize
            showStep(0);
        }
        
        // Form Submit Handler
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            
            // Step modunda son adımı doğrula
            if (stepMode && totalSteps > 1) {
                if (!validateCurrentStep()) {
                    return;
                }
            }
            
            var formData = new FormData(form);
            var submitBtn = form.querySelector(".btn-submit");
            var originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = "<i class=\"fa fa-spinner fa-spin\" style=\"margin-right: 8px;\"></i>Gönderiliyor...";
            
            fetch((typeof window.MYNAK_BASE === "string" ? window.MYNAK_BASE : "") + "/ajax/process_form.php", {
                method: "POST",
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    form.style.display = "none";
                    if (stepMode) {
                        container.querySelector(".step-progress").style.display = "none";
                        container.querySelector(".step-info").style.display = "none";
                    }
                    successMsg.style.display = "block";
                    ' . (!empty($form['redirect_url']) ? 'setTimeout(function() { window.location.href = "' . htmlspecialchars($form['redirect_url']) . '"; }, 2000);' : '') . '
                } else {
                    alert("Hata: " + (data.message || "Form gönderilemedi"));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(function(error) {
                console.error("Error:", error);
                alert("Bir hata oluştu. Lütfen tekrar deneyin.");
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
        
        function validateCurrentStep() {
            if (!stepMode || totalSteps <= 1) return true;
            
            var steps = container.querySelectorAll(".form-step");
            var currentStepEl = steps[currentStep];
            var inputs = currentStepEl.querySelectorAll("input, select, textarea");
            var isValid = true;
            
            inputs.forEach(function(input) {
                input.classList.remove("is-invalid");
                var existingError = input.parentNode.querySelector(".field-error");
                if (existingError) existingError.remove();
                
                if (input.hasAttribute("required")) {
                    var value = input.value.trim();
                    if (input.type === "checkbox" && !input.checked) {
                        showFieldError(input, "Bu alan zorunludur");
                        isValid = false;
                    } else if (input.type === "radio") {
                        var radioName = input.name;
                        var radioGroup = currentStepEl.querySelectorAll("input[name=\'" + radioName + "\']");
                        var radioChecked = Array.from(radioGroup).some(function(r) { return r.checked; });
                        if (!radioChecked) {
                            showFieldError(radioGroup[0], "Lütfen bir seçenek seçin");
                            isValid = false;
                        }
                    } else if (!value) {
                        showFieldError(input, "Bu alan zorunludur");
                        isValid = false;
                    }
                }
                
                if (input.type === "email" && input.value) {
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value)) {
                        showFieldError(input, "Geçerli bir e-posta adresi giriniz");
                        isValid = false;
                    }
                }
            });
            
            return isValid;
        }
        
        function showFieldError(input, message) {
            input.classList.add("is-invalid");
            var errorDiv = document.createElement("div");
            errorDiv.className = "field-error";
            errorDiv.textContent = message;
            input.parentNode.appendChild(errorDiv);
        }
    })();
    </script>';
    
    return $html;
}

/**
 * Sayfa/blog gövdesi kısa kodları — tüm DB erişimi burada (pipeline).
 */
function mynak_blok_isle(mysqli $conn, string $content): string {
    $content = html_etiketlerini_duzelt($content);
    
    // Form Kısa Kodu [form id=X]
    $content = preg_replace_callback('/\[form\s+id=(\d+)\]/i', function($matches) use ($conn) {
        $form_id = intval($matches[1]);
        return mynak_render_embedded_form($conn, $form_id);
    }, $content);
    // Galeri Slider Blok
    $content = preg_replace_callback('/\[blok:galeri_slider id=(\d+)\]/i', function($matches) use ($conn) {
        $id = $matches[1];
        $stmt = $conn->prepare("SELECT * FROM gallery_slider_blocks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $resimler = json_decode($row['resimler'], true);
            $kacli = (int)($row['kacli'] ?? 3);
            $slider_id = 'lightgallery-' . $id;
            $html = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/css/lightgallery-bundle.min.css">';
            // Responsive grid için stil
            $html .= '<style>
                #' . $slider_id . ' { display: grid; grid-template-columns: repeat(' . $kacli . ', 1fr); gap: 15px; }
                @media (max-width: 992px) { #' . $slider_id . ' { grid-template-columns: repeat(2, 1fr) !important; } }
                @media (max-width: 576px) { #' . $slider_id . ' { grid-template-columns: 1fr !important; } }
            </style>';
            $html .= '<div class="gallery-block-container" style="margin:30px 0;">';
            $html .= '<div id="' . $slider_id . '" class="gallery-grid">';
            foreach($resimler as $img) {
                $img_path = '/uploads/gallery/' . basename($img);
                $imgAlt = htmlspecialchars(mynak_public_image_alt(basename((string) $img), 'İzmir nakliyat galeri fotoğrafı — MY Nakliyat', $img_path), ENT_QUOTES, 'UTF-8');
                $html .= '<a class="gallery-item" href="' . $img_path . '" data-lg-size="1600-1600">';
                $html .= '<img src="' . $img_path . '" alt="' . $imgAlt . '" style="width:100%;height:200px;object-fit:cover;border-radius:8px;cursor:pointer;">';
                $html .= '</a>';
            }
            $html .= '</div></div>';
            $html .= '<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/lightgallery.min.js"></script>';
            $html .= '<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/zoom/lg-zoom.min.js"></script>';
            $html .= '<script src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.1/plugins/thumbnail/lg-thumbnail.min.js"></script>';
            $html .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var galleryElement = document.getElementById("' . $slider_id . '");
                    if (galleryElement) {
                        lightGallery(galleryElement, {
                            plugins: [lgZoom, lgThumbnail],
                            speed: 500,
                            download: false,
                            counter: true,
                            mousewheel: true,
                            loop: true,
                            mobileSettings: {
                                controls: true,
                                showCloseIcon: true,
                                download: false
                            }
                        });
                    }
                });
            </script>';
            return $html;
        }
        return '[Galeri Slider Bulunamadı]';
    }, $content);
    
    // HTML Blok
    $content = preg_replace_callback('/\\[blok:html id=(\\d+)\\]/i', function($matches) use ($conn) {
        $id = $matches[1];
        $stmt = $conn->prepare("SELECT * FROM html_blocks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return '<div class="html-block" style="height: ' . $row['yukseklik'] . 'px;">' . $row['html_icerik'] . '</div>';
        }
        return '[HTML Blok Bulunamadı]';
    }, $content);
    
    // Blog Blok
    $content = preg_replace_callback('/\\[blok:blog id=(\\d+)\\]/i', function($matches) {
        $id = $matches[1];
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM blog_blocks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $yazi_sayisi = $row['yazi_sayisi'];
            $gosterim_tipi = $row['gosterim_tipi'];
            $kategori_id = $row['kategori_id'];
            $slider_id = 'blog_slider_' . uniqid();
            $sql = "SELECT p.*, c.ad as kategori_adi FROM blog_posts p LEFT JOIN blog_categories c ON p.kategori_id = c.id WHERE p.durum=3";
            if ($gosterim_tipi === 'kategori' && $kategori_id > 0) {
                $sql .= " AND p.kategori_id = " . intval($kategori_id);
            }
            $sql .= " ORDER BY p.created_at DESC LIMIT " . $yazi_sayisi;
            $yazilar = $conn->query($sql);
            $yazi_sayisi_gercek = $yazilar->num_rows;
            $html = '<div class="blog-slider-section" style="margin:40px 0;">';
            $html .= '<div class="blog-slider-container" style="position:relative;overflow:hidden;">';
            $html .= '<div class="blog-slider-row" id="' . $slider_id . '" style="display:flex;gap:24px;transition:transform 0.5s cubic-bezier(.77,0,.18,1);">';
            while($yazi = $yazilar->fetch_assoc()) {
                $img = $yazi['kapak_foto'];
                if (!$img) {
                    $img = '/uploads/blog/default.jpg';
                } elseif (preg_match('#^https?://#i', $img)) {
                    // harici veya tam URL
                } elseif (strpos($img, 'media/') === 0) {
                    $img = '/uploads/' . $img;
                } elseif (strpos($img, 'uploads/') === 0) {
                    $img = '/' . ltrim($img, '/');
                } else {
                    $img = '/uploads/blog/' . ltrim($img, '/');
                }
                if ($img !== '' && !preg_match('#^https?://#i', $img) && isset($img[0]) && $img[0] === '/' && function_exists('mynak_public_path')) {
                    $img = mynak_public_path(trim($img, '/'));
                }
                $devam_href = function_exists('mynak_public_path')
                    ? htmlspecialchars(mynak_public_path($yazi['slug']), ENT_QUOTES, 'UTF-8')
                    : '/' . htmlspecialchars($yazi['slug']);
                $html .= '<div class="blog-slider-item" style="background:#f8f9fa;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.04);flex:0 0 calc(100%/' . $yazi_sayisi . ' - 16px);max-width:calc(100%/' . $yazi_sayisi . ' - 16px);text-align:left;padding:18px 10px 10px;transition:box-shadow 0.2s;display:flex;flex-direction:column;min-width:220px;">';
                $html .= '<img src="' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '" alt="' . mynak_esc_html(mynak_public_image_alt((string) $yazi['baslik'], 'MY Nakliyat blog yazısı')) . '" style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:10px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">';
                $html .= '<div class="blog-category" style="font-size:13px;color:#0056b3;font-weight:600;margin-bottom:4px;">' . mynak_esc_html((string) ($yazi['kategori_adi'] ?? 'Genel')) . '</div>';
                $html .= '<h3 style="font-size:18px;font-weight:700;margin:0 0 8px 0;">' . mynak_esc_html((string) $yazi['baslik']) . '</h3>';
                $excerptPlain = mynak_decode_html_entities(strip_tags((string) ($yazi['icerik'] ?? '')));
                $html .= '<p style="font-size:14px;color:#444;margin-bottom:10px;">' . mynak_esc_html(mb_substr($excerptPlain, 0, 100)) . '...</p>';
                $yaziBaslik = (string) ($yazi['baslik'] ?? '');
                $readAnchor = mynak_decode_html_entities($yaziBaslik);
                if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($readAnchor, 'UTF-8') > 52) {
                    $readAnchor = mb_substr($readAnchor, 0, 52, 'UTF-8') . '…';
                } elseif (strlen($readAnchor) > 52) {
                    $readAnchor = substr($readAnchor, 0, 52) . '…';
                }
                $readAnchor .= ' — yazının tamamını okuyun';
                $html .= '<a href="' . $devam_href . '" class="read-more-link" style="color:#0056b3;font-weight:600;text-decoration:none;font-size:14px;margin-top:auto;">' . mynak_esc_html($readAnchor) . '</a>';
                $html .= '</div>';
            }
            $html .= '</div>';
            if($yazi_sayisi_gercek > $yazi_sayisi) {
                $html .= '<div class="blog-slider-nav mt-3" style="display:flex;justify-content:center;gap:16px;margin-top:18px;">';
                $html .= '<button type="button" class="blogPrevBtn" data-target="' . $slider_id . '" style="background:#0056b3;color:#fff;border:none;border-radius:50%;width:40px;height:40px;font-size:20px;display:flex;align-items:center;justify-content:center;transition:background 0.2s;"><i class="fas fa-chevron-left"></i></button>';
                $html .= '<button type="button" class="blogNextBtn" data-target="' . $slider_id . '" style="background:#0056b3;color:#fff;border:none;border-radius:50%;width:40px;height:40px;font-size:20px;display:flex;align-items:center;justify-content:center;transition:background 0.2s;"><i class="fas fa-chevron-right"></i></button>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<script>(function(){
                var row = document.getElementById("' . $slider_id . '");
                var items = row.querySelectorAll(".blog-slider-item");
                var prev = document.querySelector(".blogPrevBtn[data-target=\"' . $slider_id . '\"]");
                var next = document.querySelector(".blogNextBtn[data-target=\"' . $slider_id . '\"]");
                var kacli = ' . $yazi_sayisi . ';
                var current = 0;
                function updateSlider() {
                    var itemWidth = items[0].offsetWidth + 24;
                    row.style.transform = "translateX(-" + (current * itemWidth) + "px)";
                }
                prev.onclick = function() {
                    current = (current - 1 + items.length) % items.length;
                    if(current > items.length - kacli) current = items.length - kacli;
                    if(current < 0) current = 0;
                    updateSlider();
                };
                next.onclick = function() {
                    current = (current + 1) % items.length;
                    if(current > items.length - kacli) current = 0;
                    updateSlider();
                };
                window.addEventListener("resize", updateSlider);
                updateSlider();
            })();</script>';
            return $html;
        }
        return '[Blog Blok Bulunamadı]';
    }, $content);
    
    // İletişim Blok
    $content = preg_replace_callback('/\\[blok:iletisim id=(\\d+)\\]/i', function($matches) use ($conn) {
        $id = $matches[1];
        $stmt = $conn->prepare("SELECT * FROM contact_blocks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $alanlar = explode(',', $row['alanlar']);
            $form_baslik = htmlspecialchars($row['form_baslik']);
            $form_aciklama = htmlspecialchars($row['form_aciklama']);
            $gonder_metni = htmlspecialchars($row['gonder_metni']);
            $blok_html = '<div class="contact-block-preview" style="background:#fff;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.07);padding:40px 20px 30px;margin:0 auto;max-width:600px;">';
            if ($form_aciklama) $blok_html .= '<div class="contact-block-desc" style="font-size:15px;color:#666;margin-bottom:25px;text-align:center;">' . $form_aciklama . '</div>';
            $blok_html .= '<form class="contact-form-preview" method="post" action="iletisim-formu.php">';
            $blok_html .= '<input type="hidden" name="blok_id" value="' . $id . '">';
            if (in_array('ad', $alanlar)) {
                $blok_html .= '<div class="form-group"><label>Ad Soyad</label><input type="text" name="ad" class="form-control" required></div>';
            }
            if (in_array('email', $alanlar)) {
                $blok_html .= '<div class="form-group"><label>E-posta</label><input type="email" name="email" class="form-control" required></div>';
            }
            if (in_array('telefon', $alanlar)) {
                $blok_html .= '<div class="form-group"><label>Telefon</label><input type="tel" name="telefon" class="form-control"></div>';
            }
            if (in_array('konu', $alanlar)) {
                $blok_html .= '<div class="form-group"><label>Konu</label><input type="text" name="konu" class="form-control" required></div>';
            }
            if (in_array('mesaj', $alanlar)) {
                $blok_html .= '<div class="form-group"><label>Mesaj</label><textarea name="mesaj" class="form-control" rows="5" required></textarea></div>';
            }
            $blok_html .= '<button type="submit" class="submit-btn" style="padding:12px 30px;font-size:16px;font-weight:600;background:#0056b3;color:#fff;border:none;border-radius:8px;transition:all 0.3s;cursor:pointer;display:inline-flex;align-items:center;"><i class="far fa-paper-plane" style="margin-right:8px;"></i>' . $gonder_metni . '</button>';
            $blok_html .= '</form></div>';
            static $contact_css_included = false;
            if (!$contact_css_included) {
                $blok_html = '<style>
                .contact-block-preview {background:#fff;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.07);padding:40px 20px 30px;margin:0 auto;max-width:600px;width:100%;}
                .contact-block-preview .form-group {margin-bottom:18px;}
                .contact-block-preview label {font-weight:600;margin-bottom:6px;display:block;}
                .contact-block-preview input[type="text"],
                .contact-block-preview input[type="email"],
                .contact-block-preview input[type="tel"],
                .contact-block-preview textarea {
                    width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:6px;font-size:15px;box-sizing:border-box;transition:border .2s;}
                .contact-block-preview input:focus,
                .contact-block-preview textarea:focus {outline:none;border-color:#0056b3;}
                .contact-block-preview .submit-btn {padding:12px 30px;font-size:16px;font-weight:600;background:#0056b3;color:#fff;border:none;border-radius:8px;transition:all 0.3s;cursor:pointer;display:inline-flex;align-items:center;}
                .contact-block-preview .submit-btn:hover {background:#003d82;}
                @media (max-width:600px) {.contact-block-preview {padding:20px 5px 15px;}}
                </style>' . $blok_html;
                $contact_css_included = true;
            }
            return $blok_html;
        }
        return '[İletişim Formu Bulunamadı]';
    }, $content);

    $content = mynak_normalize_html_href_attributes($content);

    // Kaynak linkleri düzelt: <a href="resim.jpg">metin</a> → <img> (Semrush: "resource formatted as page link")
    $content = mynak_convert_resource_links_to_media($content);

    // Şablonda tek <h1> (banner / yazı başlığı) kalsın; içerik ve [blok:...] çıktısındaki <h1> → <h2>
    $content = demote_inline_h1_to_h2($content);

    if (!function_exists('mynak_video_normalize_watch_html')) {
        require_once dirname(__DIR__) . '/mynak_video_watch_page.php';
    }
    if (function_exists('mynak_video_normalize_watch_html')) {
        $content = mynak_video_normalize_watch_html($content);
    }

    if (function_exists('mynak_normalize_html_img_alts')) {
        $content = mynak_normalize_html_img_alts($content);
    }

    if (function_exists('mynak_normalize_html_img_loading')) {
        $content = mynak_normalize_html_img_loading($content);
    }

    return $content;
}

