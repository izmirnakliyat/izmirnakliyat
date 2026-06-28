<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pipeline/lead_magnet_tasinma_checklist.php';

$site_settings = mynak_site_settings_bootstrap($conn);
$mynak_lead_error = '';

$redirectIcerik = static function (): void {
    $u = mynak_abs_url_from_public_path(mynak_public_path('tasinma-kontrol-listesi-icerik'));
    header('Location: ' . $u, true, 303);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['mynak_lead_action'] ?? '') === 'checklist')) {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $mynak_lead_error = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        $mynak_lead_error = 'Bot algılandı.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $ad = trim((string) ($_POST['ad'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mynak_lead_error = 'Lütfen geçerli bir e-posta adresi girin.';
        } elseif (empty($_POST['kvkk_onay'])) {
            $mynak_lead_error = 'Devam etmek için aydınlatma ve onay kutusunu işaretleyin.';
        } else {
            $payload = json_encode(
                [
                    'email' => $email,
                    'ad' => $ad,
                    'kaynak' => 'tasinma-kontrol-listesi',
                ],
                JSON_UNESCAPED_UNICODE
            );
            if ($payload === false) {
                $mynak_lead_error = 'Geçici bir hata oluştu.';
            } else {
                date_default_timezone_set('Europe/Istanbul');
                $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
                $ct = date('Y-m-d H:i:s');
                $form_title = 'Lead: Taşınma kontrol listesi (indirme)';
                $stmt = $conn->prepare('INSERT INTO form_submissions (form_title, form_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sssss', $form_title, $payload, $ip, $ua, $ct);
                    if ($stmt->execute()) {
                        $stmt->close();
                        mynak_lead_checklist_set_session();
                        $redirectIcerik();
                    }
                    $stmt->close();
                }
                $mynak_lead_error = 'Kayıt sırasında hata oluştu. Lütfen bir süre sonra tekrar deneyin.';
            }
        }
    }
}

$page_title = 'Ücretsiz taşınma kontrol listesi' . ' - ' . (!empty($site_settings['site_title']) ? (string) $site_settings['site_title'] : '');
$allow_indexing = true;
$page_meta_description = 'E-posta ile kayıt — taşınma öncesi adım adım kontrol listesini (HTML) indirin veya PDF için yazdırın. MY Nakliyat İzmir.';
$hide_title_suffix = true;

require_once __DIR__ . '/includes/header.php';
?>

<main id="content">
    <section class="page-banner" style="background: linear-gradient(135deg, #1a3a5c 0%, #2b5a8a 100%); padding: 3rem 0 2rem;">
        <div class="container text-center text-white">
            <span class="d-inline-block text-uppercase small opacity-90 mb-2">Ücretsiz indirilebilir rehber</span>
            <h1 class="h2 mb-2" style="color:#fff;">Taşınma kontrol listesi</h1>
            <p class="mb-0 opacity-90 col-lg-8 mx-auto" style="max-width: 640px;">E-postanızı bırakın; dijital listeyi anında açın veya HTML dosyası olarak kaydedin. PDF için tarayıcıda <strong>Yazdır → PDF'ye kaydet</strong> kullanabilirsiniz.</p>
        </div>
    </section>
    <section class="py-5">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4 p-md-5">
                            <?php if (!empty($mynak_lead_error)): ?>
                                <div class="alert alert-warning"><?php echo htmlspecialchars($mynak_lead_error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <h2 class="h5 mb-3">E-posta ile erişin</h2>
                            <p class="text-muted small mb-3">E-posta adresiniz; bu listenin gönderilmesi, MY Nakliyat’ın size ölçülü bilgi ve fırsat iletebilmesi (çift onay/iletisim tercihleriniz saklıdır) amacıyla kaydedilir. Aşağıdaki metni onaylamanız gerekir.</p>
                            <form method="post" action="<?php echo htmlspecialchars(mynak_public_path('tasinma-kontrol-listesi'), ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                                <input type="hidden" name="mynak_lead_action" value="checklist">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <p class="d-none" aria-hidden="true">
                                    <label>Website <input type="text" name="website" value="" autocomplete="off" tabindex="-1"></label>
                                </p>
                                <div class="mb-3">
                                    <label class="form-label" for="mynak_l_ad">Ad Soyad (isteğe bağlı)</label>
                                    <input class="form-control" type="text" name="ad" id="mynak_l_ad" maxlength="120" value="<?php echo isset($_POST['ad']) ? htmlspecialchars((string) $_POST['ad'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="mynak_l_em">E-posta <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" name="email" id="mynak_l_em" required maxlength="200" value="<?php echo isset($_POST['email']) ? htmlspecialchars((string) $_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>" autocomplete="email">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="kvkk_onay" value="1" id="mynak_kvkk" required <?php echo !empty($_POST['kvkk_onay']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="mynak_kvkk">Kişisel verilerin işlenmesine ilişkin <a href="<?php echo htmlspecialchars(mynak_public_path('gizlilik-politikasi'), ENT_QUOTES, 'UTF-8'); ?>">aydınlatma</a> metnini okudum. E-posta adresimin listeyi sunmak ve MY Nakliyat’ın bana yasal sınırlar içinde iletişim kurması için kullanılmasını kabul ediyorum. <span class="text-danger">*</span></label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Listeyi aç / indir sayfasına git</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body p-4">
                            <h3 class="h6 text-uppercase text-secondary">İçerik önizleme</h3>
                            <ul class="small mb-0">
                                <li>Hafta hafta hazırlık</li>
                                <li>Abonelik ve adres değişikliği</li>
                                <li>Taşınma günü sayımı</li>
                                <li>Yeni adreste ilk 48 saat</li>
                            </ul>
                            <p class="small text-muted mt-3 mb-0">Profesyonel taşınmada fiyat, ambalaj ve sigorta ayrıntıları tek tek yazılmalıdır. <a href="<?php echo htmlspecialchars(mynak_public_path('teklif-alin'), ENT_QUOTES, 'UTF-8'); ?>">Ücretsiz teklif alın</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
