<?php
$page_title = function_exists('mynak_build_page_title')
    ? mynak_build_page_title('Ücretsiz Nakliyat Teklifi')
    : 'Ücretsiz Nakliyat Teklifi | MY Nakliyat';
$page_meta_description = 'İzmir evden eve ve şehirlerarası nakliyat için ücretsiz teklif alın. Sigortalı taşıma, net fiyat ve hızlı dönüş. Formu doldurun.';
$allow_indexing = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/db.php';

// Form gönderimi işleme
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quote_form') {
    // Form verilerini al
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $ev_tipi = trim($_POST['ev_tipi'] ?? '');
    $kat = trim($_POST['kat'] ?? '');
    $oda_sayisi = trim($_POST['oda_sayisi'] ?? '');
    $asansor = trim($_POST['asansor'] ?? '');
    $tasima_tarihi = trim($_POST['tasima_tarihi'] ?? '');
    $nereden_nereye = trim($_POST['nereden_nereye'] ?? '');
    
    // 1. Honeypot kontrolü
    $honeypot_triggered = false;
    if (!empty($_POST['fax_number'])) {
        $honeypot_triggered = true;
    }
    
    // Doğrulama
    $errors = [];
    
    // 2. Rate Limiting (1 saatte maks 5 form)
    if (!isset($_SESSION['quote_submit_times'])) {
        $_SESSION['quote_submit_times'] = [];
    }
    $one_hour_ago = time() - 3600;
    $_SESSION['quote_submit_times'] = array_filter($_SESSION['quote_submit_times'], function($time) use ($one_hour_ago) {
        return $time > $one_hour_ago;
    });
    if (count($_SESSION['quote_submit_times']) >= 5 && !$honeypot_triggered) {
        $errors[] = 'Çok fazla teklif talebi gönderdiniz. Lütfen daha sonra tekrar deneyin.';
    }

    if (empty($name)) {
        $errors[] = 'Ad Soyad alanı zorunludur.';
    }
    
    if (empty($phone)) {
        $errors[] = 'Telefon alanı zorunludur.';
    }
    
    if (empty($nereden_nereye)) {
        $errors[] = 'Nereden Nereye alanı zorunludur.';
    }
    
    if (empty($errors)) {
        if ($honeypot_triggered) {
            // Bot tespit edildi, başarılı gibi gösterip çık
            $success_message = 'Teklif talebiniz başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.';
            $name = $phone = $ev_tipi = $kat = $oda_sayisi = $asansor = $tasima_tarihi = $nereden_nereye = '';
        } else {
            $_SESSION['quote_submit_times'][] = time();
            
            // Form verilerini JSON olarak hazırla
            $form_data = json_encode([
                'name' => $name,
                'phone' => $phone,
                'ev_tipi' => $ev_tipi,
                'kat' => $kat,
                'oda_sayisi' => $oda_sayisi,
                'asansor' => $asansor,
                'tasima_tarihi' => $tasima_tarihi,
                'nereden_nereye' => $nereden_nereye
            ], JSON_UNESCAPED_UNICODE);
            
            // Veritabanına kaydet
            date_default_timezone_set('Europe/Istanbul');
            $current_date = date('Y-m-d H:i:s');
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // form_submissions tablosuna kaydet
            $stmt = $conn->prepare("INSERT INTO form_submissions (form_title, form_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?)");
            $form_title = 'Teklif Alma Formu';
            $stmt->bind_param("sssss", $form_title, $form_data, $ip_address, $user_agent, $current_date);
            
            if ($stmt->execute()) {
                $success_message = 'Teklif talebiniz başarıyla gönderildi. En kısa sürede sizinle iletişime geçeceğiz.';
                // Form verilerini temizle
                $name = $phone = $ev_tipi = $kat = $oda_sayisi = $asansor = $tasima_tarihi = $nereden_nereye = '';
            } else {
                $error_message = 'Talebiniz gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
            }
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<style>
.quote-form-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.quote-form-container {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 40px;
}

.quote-form-title {
    text-align: center;
    color: #333;
    margin-bottom: 10px;
    font-size: 2.2rem;
    font-weight: bold;
}

.quote-form-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 40px;
    font-size: 1.1rem;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-col {
    flex: 1;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 8px center;
    background-repeat: no-repeat;
    background-size: 16px;
}

.btn-submit {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 15px 40px;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 20px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
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

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .quote-form-container {
        margin: 20px;
        padding: 30px 20px;
    }
    
    .quote-form-title {
        font-size: 1.8rem;
    }
}
</style>

<section class="quote-form-section">
    <div class="container">
        <div class="quote-form-container">
            <h1 class="quote-form-title">Ücretsiz Teklif Alın</h1>
            <p class="quote-form-subtitle">Evden eve nakliyat hizmetimiz için detaylı bilgi verin, size en uygun teklifi hazırlayalım.</p>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="quote_form">
                
                <div style="display:none;" aria-hidden="true">
                    <label for="fax_number">Fax Number</label>
                    <input type="text" id="fax_number" name="fax_number" tabindex="-1" autocomplete="off">
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="name" class="form-label">Ad Soyad *</label>
                            <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="phone" class="form-label">Telefon *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" required value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="ev_tipi" class="form-label">Ev Tipi</label>
                            <select id="ev_tipi" name="ev_tipi" class="form-control form-select">
                                <option value="">Seçiniz</option>
                                <option value="1+0" <?php echo ($ev_tipi ?? '') === '1+0' ? 'selected' : ''; ?>>1+0</option>
                                <option value="1+1" <?php echo ($ev_tipi ?? '') === '1+1' ? 'selected' : ''; ?>>1+1</option>
                                <option value="2+1" <?php echo ($ev_tipi ?? '') === '2+1' ? 'selected' : ''; ?>>2+1</option>
                                <option value="3+1" <?php echo ($ev_tipi ?? '') === '3+1' ? 'selected' : ''; ?>>3+1</option>
                                <option value="4+1" <?php echo ($ev_tipi ?? '') === '4+1' ? 'selected' : ''; ?>>4+1</option>
                                <option value="5+1" <?php echo ($ev_tipi ?? '') === '5+1' ? 'selected' : ''; ?>>5+1</option>
                                <option value="Villa" <?php echo ($ev_tipi ?? '') === 'Villa' ? 'selected' : ''; ?>>Villa</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="kat" class="form-label">Kat</label>
                            <select id="kat" name="kat" class="form-control form-select">
                                <option value="">Seçiniz</option>
                                <option value="Zemin" <?php echo ($kat ?? '') === 'Zemin' ? 'selected' : ''; ?>>Zemin</option>
                                <option value="1. Kat" <?php echo ($kat ?? '') === '1. Kat' ? 'selected' : ''; ?>>1. Kat</option>
                                <option value="2. Kat" <?php echo ($kat ?? '') === '2. Kat' ? 'selected' : ''; ?>>2. Kat</option>
                                <option value="3. Kat" <?php echo ($kat ?? '') === '3. Kat' ? 'selected' : ''; ?>>3. Kat</option>
                                <option value="4. Kat" <?php echo ($kat ?? '') === '4. Kat' ? 'selected' : ''; ?>>4. Kat</option>
                                <option value="5+ Kat" <?php echo ($kat ?? '') === '5+ Kat' ? 'selected' : ''; ?>>5+ Kat</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="oda_sayisi" class="form-label">Oda Sayısı</label>
                            <select id="oda_sayisi" name="oda_sayisi" class="form-control form-select">
                                <option value="">Seçiniz</option>
                                <option value="1" <?php echo ($oda_sayisi ?? '') === '1' ? 'selected' : ''; ?>>1</option>
                                <option value="2" <?php echo ($oda_sayisi ?? '') === '2' ? 'selected' : ''; ?>>2</option>
                                <option value="3" <?php echo ($oda_sayisi ?? '') === '3' ? 'selected' : ''; ?>>3</option>
                                <option value="4" <?php echo ($oda_sayisi ?? '') === '4' ? 'selected' : ''; ?>>4</option>
                                <option value="5+" <?php echo ($oda_sayisi ?? '') === '5+' ? 'selected' : ''; ?>>5+</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="asansor" class="form-label">Asansör</label>
                            <select id="asansor" name="asansor" class="form-control form-select">
                                <option value="">Seçiniz</option>
                                <option value="Var" <?php echo ($asansor ?? '') === 'Var' ? 'selected' : ''; ?>>Var</option>
                                <option value="Yok" <?php echo ($asansor ?? '') === 'Yok' ? 'selected' : ''; ?>>Yok</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tasima_tarihi" class="form-label">Taşınma Tarihi</label>
                    <input type="date" id="tasima_tarihi" name="tasima_tarihi" class="form-control" value="<?php echo htmlspecialchars($tasima_tarihi ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="nereden_nereye" class="form-label">Nereden Nereye *</label>
                    <textarea id="nereden_nereye" name="nereden_nereye" class="form-control" rows="3" required placeholder="Örn: İzmir/Bornova'dan İzmir/Konak'a"><?php echo htmlspecialchars($nereden_nereye ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Ücretsiz Teklif Al</button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?> 