<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (!isset($site_settings) || !is_array($site_settings)) {
    $site_settings = mynak_site_settings_bootstrap($conn);
}
$page_title = function_exists('mynak_build_page_title')
    ? mynak_build_page_title('İletişim')
    : 'İletişim | MY Nakliyat';
$page_meta_description = 'Bize ulaşın, MY Nakliyat ile hızlı ve güvenli taşımacılık için teklif alın.';
$allow_indexing = true;
require_once __DIR__ . '/includes/header.php';

// Get contact page settings
$settings = [];
$result = $conn->query("SELECT name, value FROM settings WHERE name LIKE 'contact_%' OR name IN ('address', 'phone1', 'phone2', 'phone3', 'email', 'whatsapp', 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'website')");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['name']] = $row['value'];
    }
}
$settings = mynak_apply_contact_defaults_to_settings_array($settings);

// Default values if settings not found
$map_embed = $settings['contact_map_embed'] ?? mynak_contact_default_map_embed_html();
$contact_title = $settings['contact_title'] ?? 'Bize Ulaşın';
$contact_description = $settings['contact_description'] ?? 'Aşağıdaki iletişim bilgilerimizden bize ulaşabilir veya formu doldurarak mesaj gönderebilirsiniz.';
$form_title = $settings['contact_form_title'] ?? 'Mesaj Gönderin';
$form_description = $settings['contact_form_description'] ?? 'Formu doldurarak bize hızlıca ulaşabilirsiniz. En kısa sürede sizinle iletişime geçeceğiz.';

// Contact information
$address = $settings['address'] ?? '';
$phone1 = $settings['phone1'] ?? '';
$phone2 = $settings['phone2'] ?? '';
$phone3 = $settings['phone3'] ?? '';
$email = $settings['email'] ?? '';
$whatsapp = $settings['whatsapp'] ?? '';

// Social media links
$facebook = $settings['facebook'] ?? '';
$twitter = $settings['twitter'] ?? '';
$instagram = $settings['instagram'] ?? '';
$linkedin = $settings['linkedin'] ?? '';
$youtube = $settings['youtube'] ?? '';
$website = $settings['website'] ?? '';

// Form submission handler
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_form') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email_address = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // 1. Honeypot kontrolü
    $honeypot_triggered = false;
    if (!empty($_POST['fax_number'])) {
        $honeypot_triggered = true;
    }
    
    // Validate form data
    $errors = [];
    
    // 2. Rate Limiting (1 saatte maks 5 form)
    if (!isset($_SESSION['contact_submit_times'])) {
        $_SESSION['contact_submit_times'] = [];
    }
    $one_hour_ago = time() - 3600;
    $_SESSION['contact_submit_times'] = array_filter($_SESSION['contact_submit_times'], function($time) use ($one_hour_ago) {
        return $time > $one_hour_ago;
    });
    if (count($_SESSION['contact_submit_times']) >= 5 && !$honeypot_triggered) {
        $errors[] = 'Çok fazla mesaj gönderdiniz. Lütfen daha sonra tekrar deneyin.';
    }

    if (empty($name)) {
        $errors[] = 'Ad Soyad alanı zorunludur.';
    }
    
    if (empty($email_address) || !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girmelisiniz.';
    }
    
    if (empty($message)) {
        $errors[] = 'Mesaj alanı zorunludur.';
    }
    
    if (empty($errors)) {
        if ($honeypot_triggered) {
            // Bot tespit edildi, başarılı gibi gösterip çık
            $success_message = 'Mesajınız başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.';
            $name = $email_address = $phone_number = $message = '';
        } else {
            $_SESSION['contact_submit_times'][] = time();
            
            // Prepare form data for storing
            $form_data = json_encode([
                'name' => $name,
                'email' => $email_address,
                'phone' => $phone_number,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE);
            
            // Set timezone to Turkey
            date_default_timezone_set('Europe/Istanbul');
            $current_date = date('Y-m-d H:i:s');
            
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO form_submissions (popup_id, form_data, ip_address, submission_date) VALUES (NULL, ?, ?, ?)");
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param("sss", $form_data, $ip_address, $current_date);

            try {
                $ok = $stmt->execute();
            } catch (Throwable $e) {
                error_log('iletisim form_submissions: ' . $e->getMessage());
                $ok = false;
            }

            if (!empty($ok)) {
                $success_message = 'Mesajınız başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.';
                $name = $email_address = $phone_number = $message = '';
            } else {
                if (isset($stmt) && $stmt->errno === 1062) {
                    error_log('iletisim: form_submissions PRIMARY duplicate (genelde id AUTO_INCREMENT veya id=0) — sql/patches/form_submissions_autoincrement.sql');
                } elseif (isset($stmt) && $stmt->error) {
                    error_log('iletisim form insert: ' . $stmt->error);
                }
                $error_message = 'Mesajınız gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!-- Sayfa başlığı — tek H1 (Semrush / SEO) -->
<section class="page-header" style="background-color: #f8f9fa; background-image: none; padding: 48px 0 32px; height: auto;">
    <div class="container">
        <h1>İletişim</h1>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Ana Sayfa</a> / <span>İletişim</span>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="map-container">
        <?php echo $map_embed; ?>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="contact-container">
        <!-- Left Column - Contact Info -->
        <div class="contact-info">
            <div class="contact-info-box">
                <h2 class="section-title"><?php echo htmlspecialchars($contact_title); ?></h2>
                <p class="section-description"><?php echo htmlspecialchars($contact_description); ?></p>
                
                <div class="contact-details">
                    <?php if (!empty($address)): ?>
                    <div class="contact-item">
                        <div class="icon-box">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="content">
                            <h5>Adres</h5>
                            <p><?php echo nl2br(htmlspecialchars($address)); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($phone1) || !empty($phone2) || !empty($phone3)): ?>
                    <div class="contact-item">
                        <div class="icon-box">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="content">
                            <h5>Telefon</h5>
                            <?php if (!empty($phone1)): ?>
                            <p class="phone-item"><a href="<?php echo htmlspecialchars(footer_tel_uri($phone1), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($phone1); ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($phone2)): ?>
                            <p class="phone-item"><a href="<?php echo htmlspecialchars(footer_tel_uri($phone2), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($phone2); ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($phone3)): ?>
                            <p class="phone-item"><a href="<?php echo htmlspecialchars(footer_tel_uri($phone3), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($phone3); ?></a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($email)): ?>
                    <div class="contact-item">
                        <div class="icon-box">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="content">
                            <h5>E-posta</h5>
                            <p><a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($whatsapp)): ?>
                    <div class="contact-item">
                        <div class="icon-box whatsapp-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="content">
                            <h5>WhatsApp</h5>
                            <p><a href="<?php echo htmlspecialchars(footer_whatsapp_wa_uri($whatsapp), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars(footer_whatsapp_display_label($whatsapp)); ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($facebook) || !empty($twitter) || !empty($instagram) || !empty($linkedin) || !empty($youtube) || !empty($website)): ?>
                <div class="social-links">
                    <h5>Sosyal Medya Hesaplarımız</h5>
                    <div class="social-icons">
                        <?php if (!empty($facebook)): ?>
                        <a href="https://facebook.com/<?php echo htmlspecialchars($facebook); ?>" target="_blank" class="social-icon facebook" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($twitter)): ?>
                        <a href="https://twitter.com/<?php echo htmlspecialchars($twitter); ?>" target="_blank" class="social-icon twitter" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($instagram)): ?>
                        <a href="https://instagram.com/<?php echo htmlspecialchars($instagram); ?>" target="_blank" class="social-icon instagram" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($linkedin)): ?>
                        <a href="https://linkedin.com/company/<?php echo htmlspecialchars($linkedin); ?>" target="_blank" class="social-icon linkedin" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($youtube)): ?>
                        <a href="https://youtube.com/<?php echo htmlspecialchars($youtube); ?>" target="_blank" class="social-icon youtube" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($website)): ?>
                        <a href="<?php echo htmlspecialchars($website); ?>" target="_blank" class="social-icon website" title="Website">
                            <i class="fas fa-globe"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column - Contact Form -->
        <div class="contact-form-wrapper">
            <div class="contact-form-box">
                <h2 class="section-title"><?php echo htmlspecialchars($form_title); ?></h2>
                <p class="section-description"><?php echo htmlspecialchars($form_description); ?></p>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="contact-form">
                    <input type="hidden" name="action" value="contact_form">
                    
                    <div style="display:none;" aria-hidden="true">
                        <label for="fax_number">Fax Number</label>
                        <input type="text" id="fax_number" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="name">Ad Soyad <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        <div class="form-group half">
                            <label for="email">E-posta <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email_address ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone_number ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Mesajınız <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="submit-btn">
                            <i class="far fa-paper-plane"></i> Mesaj Gönder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
/* Contact Page Styles */
.map-section {
    height: 450px;
    width: 100%;
    overflow: hidden;
    margin-top: 0;
}

.map-container {
    width: 100%;
    height: 100%;
}

.map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

.contact-section {
    padding: 80px 0;
    background: #fff;
}

.contact-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
    display: flex;
    flex-wrap: wrap;
}

.contact-info {
    width: 50%;
    padding-right: 30px;
    box-sizing: border-box;
}

.contact-form-wrapper {
    width: 50%;
    padding-left: 30px;
    box-sizing: border-box;
}

.contact-info-box,
.contact-form-box {
    background: #fff;
    border-radius: 10px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.07);
    height: 100%;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 15px;
    color: #333;
    position: relative;
    padding-bottom: 15px;
}

.section-title:after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background-color: #0056b3;
}

.section-description {
    font-size: 16px;
    color: #666;
    margin-bottom: 30px;
    line-height: 1.7;
}

.contact-details {
    margin-bottom: 30px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 25px;
}

.icon-box {
    width: 50px;
    height: 50px;
    background: #f5f9ff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.icon-box i {
    font-size: 22px;
    color: #0056b3;
    transition: all 0.3s ease;
}

.icon-box.whatsapp-icon {
    background-color: #e9f9e9;
}

.icon-box.whatsapp-icon i {
    color: #25D366;
}

.contact-item:hover .icon-box {
    background: #0056b3;
    transform: translateY(-3px);
}

.contact-item:hover .icon-box i {
    color: #fff;
}

.contact-item:hover .icon-box.whatsapp-icon {
    background: #25D366;
}

.contact-item .content {
    flex: 1;
}

.contact-item .content h5 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 7px;
    color: #333;
}

.contact-item .content p {
    font-size: 16px;
    color: #666;
    line-height: 1.6;
    margin: 0;
    margin-bottom: 8px;
}

.contact-item .content p:last-child {
    margin-bottom: 0;
}

.phone-item {
    margin-bottom: 5px;
}

.contact-item .content p a {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
}

.contact-item .content p a:hover {
    color: #0056b3;
}

.social-links {
    margin-top: 40px;
}

.social-links h5 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.social-icons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.social-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #f5f9ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0056b3;
    font-size: 18px;
    transition: all 0.3s;
    text-decoration: none;
}

.social-icon:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.social-icon.facebook:hover {
    background: #3b5998;
    color: #fff;
}

.social-icon.twitter:hover {
    background: #1da1f2;
    color: #fff;
}

.social-icon.instagram:hover {
    background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
    color: #fff;
}

.social-icon.linkedin:hover {
    background: #0077b5;
    color: #fff;
}

.social-icon.youtube:hover {
    background: #ff0000;
    color: #fff;
}

/* Form Styles */
.contact-form {
    margin-top: 20px;
}

.form-row {
    display: flex;
    margin: 0 -10px;
    flex-wrap: wrap;
}

.form-group {
    margin-bottom: 20px;
    width: 100%;
    box-sizing: border-box;
    padding: 0;
}

.form-row .form-group {
    padding: 0 10px;
}

.form-group.half {
    width: 50%;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    display: block;
    font-size: 15px;
    color: #444;
}

.form-group .required {
    color: #e74a3b;
}

.form-control {
    width: 100%;
    height: 50px;
    padding: 10px 15px;
    border: 1px solid #e1e5ee;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    box-sizing: border-box;
}

textarea.form-control {
    height: auto;
    resize: vertical;
}

.form-control:focus {
    border-color: #0056b3;
    box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.15);
    outline: none;
}

.submit-btn {
    padding: 12px 30px;
    font-size: 16px;
    font-weight: 600;
    background: #0056b3;
    color: #fff;
    border: none;
    border-radius: 8px;
    transition: all 0.3s;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
}

.submit-btn:hover {
    background: #003d82;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 86, 179, 0.15);
}

.submit-btn i {
    margin-right: 8px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Responsive Styles */
@media (max-width: 991px) {
    .contact-container {
        flex-direction: column;
    }
    
    .contact-info,
    .contact-form-wrapper {
        width: 100%;
        padding: 0;
    }
    
    .contact-info {
        margin-bottom: 40px;
    }
    
    .contact-info-box,
    .contact-form-box {
        padding: 30px;
    }
    
    .contact-section {
        padding: 60px 0;
    }
}

@media (max-width: 767px) {
    .section-title {
        font-size: 24px;
    }
    
    .contact-section {
        padding: 40px 0;
    }
    
    .contact-info-box,
    .contact-form-box {
        padding: 25px;
    }
    
    .icon-box {
        width: 40px;
        height: 40px;
    }
    
    .icon-box i {
        font-size: 18px;
    }
    
    .form-group.half {
        width: 100%;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 