<?php
$page_title = 'Popup Yönetimi';
require_once 'includes/header.php';
require_once '../config/db.php';

// Popup işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = trim($_POST['title']);
                $form_data = '';
                
                // Check if form elements data exists
                if (isset($_POST['new_form_elements_data']) && !empty($_POST['new_form_elements_data'])) {
                    // Create a structured content array with all form data
                    $content_data = [
                        'type' => 'form',
                        'elements' => json_decode($_POST['new_form_elements_data'], true),
                        'settings' => [
                            'intro_text' => isset($_POST['new_form_intro']) ? $_POST['new_form_intro'] : '',
                            'submit_text' => isset($_POST['new_submit_btn_text']) ? $_POST['new_submit_btn_text'] : 'Gönder',
                            'success_message' => isset($_POST['new_success_message']) ? $_POST['new_success_message'] : 'Mesajınız başarıyla gönderildi.'
                        ]
                    ];
                    
                    // Convert to JSON for storage
                    $form_data = json_encode($content_data, JSON_UNESCAPED_UNICODE);
                }
                
                $status = isset($_POST['status']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO popups (title, content, status) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $title, $form_data, $status);
                
                if ($stmt->execute()) {
                    $success = "Popup başarıyla eklendi.";
                } else {
                    $error = "Popup eklenirken bir hata oluştu: " . $conn->error;
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = trim($_POST['title']);
                $form_data = '';
                
                // Check if form elements data exists
                if (isset($_POST['form_elements_data']) && !empty($_POST['form_elements_data'])) {
                    // Create a structured content array with all form data
                    $content_data = [
                        'type' => 'form',
                        'elements' => json_decode($_POST['form_elements_data'], true),
                        'settings' => [
                            'intro_text' => isset($_POST['form_intro']) ? $_POST['form_intro'] : '',
                            'submit_text' => isset($_POST['submit_btn_text']) ? $_POST['submit_btn_text'] : 'Gönder',
                            'success_message' => isset($_POST['success_message']) ? $_POST['success_message'] : 'Mesajınız başarıyla gönderildi.'
                        ]
                    ];
                    
                    // Convert to JSON for storage
                    $form_data = json_encode($content_data, JSON_UNESCAPED_UNICODE);
                }
                
                $status = isset($_POST['status']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE popups SET title = ?, content = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssii", $title, $form_data, $status, $id);
                
                if ($stmt->execute()) {
                    $success = "Popup başarıyla güncellendi.";
                } else {
                    $error = "Popup güncellenirken bir hata oluştu: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Önce butonlara bağlı mı kontrol et
                $check_result = $conn->query("SELECT COUNT(*) as count FROM header_buttons WHERE popup_id = $id");
                $check_data = $check_result->fetch_assoc();
                
                if ($check_data['count'] > 0) {
                    $error = "Bu popup silinemiyor çünkü bir veya daha fazla buton tarafından kullanılıyor. Önce bu butonları düzenleyin.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM popups WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $success = "Popup başarıyla silindi.";
                    } else {
                        $error = "Popup silinirken bir hata oluştu: " . $conn->error;
                    }
                }
                break;
        }
    }
}

// Popup yükleme işlemi - düzenleme için
$edit_popup = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM popups WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_popup = $result->fetch_assoc();
    }
}

// Popupları getir
$popups = [];
$result = $conn->query("SELECT * FROM popups ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $popups[] = $row;
    }
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Site Yönetimi /</span> Popup Yönetimi
    </h4>

    <div class="row">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($edit_popup): ?>
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Popup Düzenle</h5>
                        <a href="popups.php" class="btn btn-secondary btn-sm">İptal</a>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $edit_popup['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_popup['title']); ?>" required>
                            </div>
                            
                            <?php
                            // Parse popup content JSON if exists
                            $form_elements = [];
                            $form_intro = 'Lütfen aşağıdaki formu doldurarak bizimle iletişime geçin.';
                            $submit_btn_text = 'Gönder';
                            $success_message = 'Mesajınız başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.';
                            
                            if (!empty($edit_popup['content'])) {
                                $content_data = json_decode($edit_popup['content'], true);
                                if (json_last_error() === JSON_ERROR_NONE && isset($content_data['elements'])) {
                                    $form_elements = $content_data['elements'];
                                    
                                    if (isset($content_data['settings'])) {
                                        $form_intro = $content_data['settings']['intro_text'] ?? $form_intro;
                                        $submit_btn_text = $content_data['settings']['submit_text'] ?? $submit_btn_text;
                                        $success_message = $content_data['settings']['success_message'] ?? $success_message;
                                    }
                                }
                            }
                            ?>
                            
                            <div class="mb-3">
                                <label class="form-label">İçerik</label>
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Form Oluşturucu</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-builder">
                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6>Form Elemanları</h6>
                                                    <button type="button" class="btn btn-primary btn-sm" id="addFormElement">
                                                        <i class="bx bx-plus"></i> Yeni Eleman Ekle
                                                    </button>
                                                </div>
                                                
                                                <div id="formElements" class="mb-3">
                                                    <!-- Form elemanları burada dinamik olarak oluşturulacak -->
                                                </div>
                                                
                                                <div class="mt-4">
                                                    <h6>Form Ayarları</h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="form_intro" class="form-label">Form Giriş Metni</label>
                                                                <textarea class="form-control" id="form_intro" name="form_intro" rows="3"><?php echo htmlspecialchars($form_intro); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="submit_btn_text" class="form-label">Gönder Butonu Metni</label>
                                                                <input type="text" class="form-control" id="submit_btn_text" name="submit_btn_text" value="<?php echo htmlspecialchars($submit_btn_text); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="success_message" class="form-label">Başarı Mesajı</label>
                                                                <textarea class="form-control" id="success_message" name="success_message" rows="2"><?php echo htmlspecialchars($success_message); ?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="form_elements_data" id="form_elements_data" value='<?php echo htmlspecialchars(json_encode($form_elements, JSON_UNESCAPED_UNICODE)); ?>'>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="popupStatus" name="status" <?php echo $edit_popup['status'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="popupStatus">Aktif</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Popuplar</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPopupModal">
                            <i class='bx bx-plus'></i> Yeni Popup
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 60px">ID</th>
                                        <th>Başlık</th>
                                        <th style="width: 150px">Oluşturulma Tarihi</th>
                                        <th style="width: 80px">Durum</th>
                                        <th style="width: 150px">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($popups) > 0): ?>
                                        <?php foreach ($popups as $popup): ?>
                                            <tr>
                                                <td><?php echo $popup['id']; ?></td>
                                                <td><?php echo htmlspecialchars($popup['title']); ?></td>
                                                <td><?php 
                                                    if (!empty($popup['date_created']) && $popup['date_created'] != '0000-00-00 00:00:00') {
                                                        echo date('d.m.Y H:i', strtotime($popup['date_created']));
                                                    } else {
                                                        echo date('d.m.Y H:i');
                                                    }
                                                ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $popup['status'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $popup['status'] ? 'Aktif' : 'Pasif'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?php echo $popup['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick="previewPopup(<?php echo $popup['id']; ?>)">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $popup['id']; ?>, '<?php echo htmlspecialchars($popup['title']); ?>')">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Henüz popup eklenmemiş.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Popup Silme Form - Gizli -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<!-- Popup Ekleme Modal -->
<div class="modal fade" id="addPopupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Popup Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">İçerik</label>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Form Oluşturucu</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-builder">
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6>Form Elemanları</h6>
                                            <button type="button" class="btn btn-primary btn-sm" id="addNewFormElement">
                                                <i class="bx bx-plus"></i> Yeni Eleman Ekle
                                            </button>
                                        </div>
                                        
                                        <div id="newFormElements" class="mb-3">
                                            <!-- Form elemanları burada dinamik olarak oluşturulacak -->
                                        </div>
                                        
                                        <div class="mt-4">
                                            <h6>Form Ayarları</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="new_form_intro" class="form-label">Form Giriş Metni</label>
                                                        <textarea class="form-control" id="new_form_intro" name="new_form_intro" rows="3">Lütfen aşağıdaki formu doldurarak bizimle iletişime geçin.</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="new_submit_btn_text" class="form-label">Gönder Butonu Metni</label>
                                                        <input type="text" class="form-control" id="new_submit_btn_text" name="new_submit_btn_text" value="Gönder">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="new_success_message" class="form-label">Başarı Mesajı</label>
                                                        <textarea class="form-control" id="new_success_message" name="new_success_message" rows="2">Mesajınız başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="new_form_elements_data" id="new_form_elements_data">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="newPopupStatus" name="status" checked>
                        <label class="form-check-label" for="newPopupStatus">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Popup Önizleme Modal -->
<div class="modal fade" id="previewPopupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Popup Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewPopupContent">
                <!-- İçerik JavaScript ile doldurulacak -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form element template
    const formElementTemplate = `
        <div class="form-element card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="element-title">Form Elemanı</span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary move-up-btn">
                        <i class="bx bx-up-arrow-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary move-down-btn">
                        <i class="bx bx-down-arrow-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-element-btn">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Eleman Tipi</label>
                        <select class="form-control element-type">
                            <option value="text">Metin Kutusu</option>
                            <option value="textarea">Çok Satırlı Metin</option>
                            <option value="email">E-posta</option>
                            <option value="tel">Telefon</option>
                            <option value="number">Sayı</option>
                            <option value="select">Açılır Menü</option>
                            <option value="checkbox">Onay Kutusu</option>
                            <option value="radio">Radyo Buton</option>
                            <option value="date">Tarih</option>
                            <option value="file">Dosya Yükleme</option>
                            <option value="agreement">Sözleşme Onay Kutusu</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Etiket</label>
                        <input type="text" class="form-control element-label" placeholder="Örn: Adınız">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Alan Adı</label>
                        <input type="text" class="form-control element-name" placeholder="Örn: user_name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Placeholder</label>
                        <input type="text" class="form-control element-placeholder" placeholder="Örn: Lütfen adınızı girin">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input element-required">
                            <label class="form-check-label">Zorunlu Alan</label>
                        </div>
                    </div>
                    <div class="col-md-6 options-container" style="display: none;">
                        <label class="form-label">Seçenekler (her satıra bir seçenek)</label>
                        <textarea class="form-control element-options" rows="3" placeholder="Seçenek 1&#10;Seçenek 2&#10;Seçenek 3"></textarea>
                    </div>
                </div>
                <div class="col-md-12 agreement-container" style="display: none; margin-top: 10px;">
                    <label class="form-label">Sözleşme Metni</label>
                    <textarea class="form-control element-agreement-text" rows="3" placeholder="Örneğin: Kişisel verilerimin işlenmesine izin veriyorum."></textarea>
                    <div class="form-text mt-1">Bu metin, sözleşme onay kutusunun yanında görünecektir. Dilerseniz HTML link ekleyebilirsiniz.</div>
                </div>
            </div>
        </div>
    `;

    // Add form element to existing form
    const addFormElementBtn = document.getElementById('addFormElement');
    const formElementsContainer = document.getElementById('formElements');
    const formElementsDataInput = document.getElementById('form_elements_data');
    
    if (addFormElementBtn && formElementsContainer) {
        addFormElementBtn.addEventListener('click', function() {
            addFormElement(formElementsContainer);
            updateFormElementsData(formElementsContainer, formElementsDataInput);
        });
    }

    // Add form element to new form
    const addNewFormElementBtn = document.getElementById('addNewFormElement');
    const newFormElementsContainer = document.getElementById('newFormElements');
    const newFormElementsDataInput = document.getElementById('new_form_elements_data');
    
    if (addNewFormElementBtn && newFormElementsContainer) {
        addNewFormElementBtn.addEventListener('click', function() {
            addFormElement(newFormElementsContainer);
            updateFormElementsData(newFormElementsContainer, newFormElementsDataInput);
        });
    }

    // Function to add a form element
    function addFormElement(container) {
        const element = document.createElement('div');
        element.innerHTML = formElementTemplate;
        container.appendChild(element.firstElementChild);
        
        // Setup event listeners for the new element
        setupElementEventListeners(container.lastElementChild, container);
    }

    // Setup event listeners for form elements
    function setupElementEventListeners(element, container) {
        // Element type change
        const typeSelect = element.querySelector('.element-type');
        typeSelect.addEventListener('change', function() {
            const optionsContainer = element.querySelector('.options-container');
            const agreementContainer = element.querySelector('.agreement-container');
            
            // Tüm özel konteynerları varsayılan olarak gizle
            optionsContainer.style.display = 'none';
            if (agreementContainer) agreementContainer.style.display = 'none';
            
            if (this.value === 'select' || this.value === 'checkbox' || this.value === 'radio') {
                optionsContainer.style.display = 'block';
            } else if (this.value === 'agreement') {
                agreementContainer.style.display = 'block';
            }
            
            updateElementTitle(element);
            updateFormElementsData(container, container.id === 'formElements' ? formElementsDataInput : newFormElementsDataInput);
        });

        // Element label change
        const labelInput = element.querySelector('.element-label');
        labelInput.addEventListener('input', function() {
            updateElementTitle(element);
            updateFormElementsData(container, container.id === 'formElements' ? formElementsDataInput : newFormElementsDataInput);
        });

        // Element name, placeholder, required changes
        const nameInput = element.querySelector('.element-name');
        const placeholderInput = element.querySelector('.element-placeholder');
        const requiredCheckbox = element.querySelector('.element-required');
        const optionsTextarea = element.querySelector('.element-options');
        
        [nameInput, placeholderInput, requiredCheckbox, optionsTextarea].forEach(input => {
            if (input) {
                input.addEventListener('change', function() {
                    updateFormElementsData(container, container.id === 'formElements' ? formElementsDataInput : newFormElementsDataInput);
                });
            }
        });

        // Delete button
        const deleteBtn = element.querySelector('.delete-element-btn');
        deleteBtn.addEventListener('click', function() {
            element.remove();
            updateFormElementsData(container, container.id === 'formElements' ? formElementsDataInput : newFormElementsDataInput);
        });

        // Move up button
        const moveUpBtn = element.querySelector('.move-up-btn');
        moveUpBtn.addEventListener('click', function() {
            if (element.previousElementSibling) {
                container.insertBefore(element, element.previousElementSibling);
                updateFormElementsData(container, container.id === 'formElements' ? formElementsDataInput : newFormElementsDataInput);
            }
        });

        // Move down button
        const moveDownBtn = element.querySelector('.move-down-btn');
        moveDownBtn.addEventListener('click', function() {
            if (element.nextElementSibling) {
                container.insertBefore(element.nextElementSibling, element);
                updateFormElementsData(container, container.id === 'formElements' ? formElementsDataInput : newFormElementsDataInput);
            }
        });

        // Update initial element title
        updateElementTitle(element);
    }

    // Update element title based on type and label
    function updateElementTitle(element) {
        const typeSelect = element.querySelector('.element-type');
        const labelInput = element.querySelector('.element-label');
        const titleSpan = element.querySelector('.element-title');
        
        let typeText = '';
        switch(typeSelect.value) {
            case 'text': typeText = 'Metin Kutusu'; break;
            case 'textarea': typeText = 'Çok Satırlı Metin'; break;
            case 'email': typeText = 'E-posta'; break;
            case 'tel': typeText = 'Telefon'; break;
            case 'number': typeText = 'Sayı'; break;
            case 'select': typeText = 'Açılır Menü'; break;
            case 'checkbox': typeText = 'Onay Kutusu'; break;
            case 'radio': typeText = 'Radyo Buton'; break;
            case 'date': typeText = 'Tarih'; break;
            case 'file': typeText = 'Dosya Yükleme'; break;
            default: typeText = 'Form Elemanı';
        }
        
        titleSpan.textContent = labelInput.value ? `${typeText}: ${labelInput.value}` : typeText;
    }

    // Update form elements data
    function updateFormElementsData(container, dataInput) {
        const elements = container.querySelectorAll('.form-element');
        const data = [];
        
        elements.forEach(element => {
            const type = element.querySelector('.element-type').value;
            const label = element.querySelector('.element-label').value;
            const name = element.querySelector('.element-name').value;
            const placeholder = element.querySelector('.element-placeholder').value;
            const required = element.querySelector('.element-required').checked;
            const options = element.querySelector('.element-options').value;
            const agreementText = element.querySelector('.element-agreement-text') ? element.querySelector('.element-agreement-text').value : '';
            
            data.push({
                type,
                label,
                name,
                placeholder,
                required,
                options: type === 'select' || type === 'checkbox' || type === 'radio' ? options.split('\n').filter(opt => opt.trim() !== '') : [],
                agreementText: type === 'agreement' ? agreementText : ''
            });
        });
        
        dataInput.value = JSON.stringify(data);
    }

    // Add initial form elements if editing an existing popup
    if (formElementsContainer && formElementsDataInput && formElementsDataInput.value) {
        try {
            const data = JSON.parse(formElementsDataInput.value);
            data.forEach(elementData => {
                addFormElement(formElementsContainer);
                const element = formElementsContainer.lastElementChild;
                
                element.querySelector('.element-type').value = elementData.type;
                element.querySelector('.element-label').value = elementData.label;
                element.querySelector('.element-name').value = elementData.name;
                element.querySelector('.element-placeholder').value = elementData.placeholder;
                element.querySelector('.element-required').checked = elementData.required;
                
                if (elementData.options && elementData.options.length > 0) {
                    element.querySelector('.element-options').value = elementData.options.join('\n');
                    if (elementData.type === 'select' || elementData.type === 'checkbox' || elementData.type === 'radio') {
                        element.querySelector('.options-container').style.display = 'block';
                    }
                }
                
                // Sözleşme metni varsa, ilgili alana ekleyelim ve görünür yapalım
                if (elementData.type === 'agreement' && elementData.agreementText) {
                    const agreementContainer = element.querySelector('.agreement-container');
                    const agreementText = element.querySelector('.element-agreement-text');
                    if (agreementContainer && agreementText) {
                        agreementText.value = elementData.agreementText;
                        agreementContainer.style.display = 'block';
                    }
                }
                
                updateElementTitle(element);
            });
        } catch (e) {
            console.error('Error parsing form elements data:', e);
        }
    }

    // Add default form elements for new popup
    if (newFormElementsContainer && !newFormElementsContainer.children.length) {
        // Add a name field
        addFormElement(newFormElementsContainer);
        const nameElement = newFormElementsContainer.lastElementChild;
        nameElement.querySelector('.element-type').value = 'text';
        nameElement.querySelector('.element-label').value = 'Ad Soyad';
        nameElement.querySelector('.element-name').value = 'name';
        nameElement.querySelector('.element-placeholder').value = 'Adınızı ve soyadınızı girin';
        nameElement.querySelector('.element-required').checked = true;
        updateElementTitle(nameElement);
        
        // Add an email field
        addFormElement(newFormElementsContainer);
        const emailElement = newFormElementsContainer.lastElementChild;
        emailElement.querySelector('.element-type').value = 'email';
        emailElement.querySelector('.element-label').value = 'E-posta';
        emailElement.querySelector('.element-name').value = 'email';
        emailElement.querySelector('.element-placeholder').value = 'E-posta adresinizi girin';
        emailElement.querySelector('.element-required').checked = true;
        updateElementTitle(emailElement);
        
        // Update form data
        updateFormElementsData(newFormElementsContainer, newFormElementsDataInput);
    }

    // Form submission handling
    const editForm = document.querySelector('form[method="POST"]');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            // Check if we need to update form data before submission
            if (formElementsContainer && formElementsDataInput) {
                updateFormElementsData(formElementsContainer, formElementsDataInput);
            }
            if (newFormElementsContainer && newFormElementsDataInput) {
                updateFormElementsData(newFormElementsContainer, newFormElementsDataInput);
            }
        });
    }
});

// Silme onayı
function confirmDelete(id, title) {
    if (confirm('"' + title + '" popupını silmek istediğinizden emin misiniz?')) {
        var form = document.getElementById('deleteForm');
        document.getElementById('delete_id').value = id;
        form.submit();
    }
}

// Popup önizleme
function previewPopup(id) {
    // AJAX ile popup içeriğini al
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_popup.php?id=' + id, true);
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                var response = JSON.parse(this.responseText);
                if (response.success) {
                    document.getElementById('previewPopupContent').innerHTML = response.content;
                    var modal = new bootstrap.Modal(document.getElementById('previewPopupModal'));
                    modal.show();
                } else {
                    alert('Popup içeriği alınamadı: ' + response.message);
                }
            } catch (e) {
                alert('Popup içeriği alınamadı.');
            }
        } else {
            alert('Popup içeriği alınamadı.');
        }
    };
    xhr.send();
}
</script>

<?php require_once 'includes/footer.php'; ?>