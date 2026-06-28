<?php
/**
 * Madde 4 — Admin 2FA (TOTP) Yonetim Sayfasi
 * - Henuz aktif degilse: setup wizard (secret + QR + dogrulama + backup codes)
 * - Aktif ise: durum ozeti + devre disi birakma + backup codes yenileme
 *
 * Hicbir 3rd-party kutuphane kullanmaz. Login akisina dokunmaz; sadece DB sutunlarini doldurur.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/require_admin_web.php';
require_once __DIR__ . '/includes/totp_helper.php';

$page_title = '2FA Guvenlik';
$adminId = (int) ($_SESSION['admin_id'] ?? 0);
if ($adminId <= 0) {
    header('Location: login.php');
    exit;
}

$siteName = defined('SITE_NAME') ? SITE_NAME : 'Mynakliyat';
$flashSuccess = '';
$flashError = '';

/* --------------------------------------------------------------------- *
 * Mevcut admin verisini cek
 * --------------------------------------------------------------------- */
$adminRow = null;
$stmt = $conn->prepare("SELECT id, username, email, totp_secret, totp_enabled, totp_verified_at, totp_backup_codes FROM admin_users WHERE id = ?");
$stmt->bind_param('i', $adminId);
$stmt->execute();
$res = $stmt->get_result();
if ($res) {
    $adminRow = $res->fetch_assoc();
}
$stmt->close();

if (!$adminRow) {
    $flashError = 'Admin kullanicisi bulunamadi.';
}

$isEnabled = !empty($adminRow['totp_enabled']);

/* --------------------------------------------------------------------- *
 * POST aksiyonlari
 * --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminRow) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'start_setup' && !$isEnabled) {
        $secret = mynak_totp_generate_secret();
        $_SESSION['totp_setup_secret'] = $secret;
        header('Location: security_2fa.php');
        exit;
    }

    if ($action === 'verify_setup' && !$isEnabled) {
        $secret = (string) ($_SESSION['totp_setup_secret'] ?? '');
        $code   = (string) ($_POST['code'] ?? '');
        if ($secret === '') {
            $flashError = 'Secret bulunamadi. Lutfen kurulumu tekrar baslatin.';
        } elseif (!mynak_totp_verify($secret, $code)) {
            $flashError = 'Kod gecersiz. Authenticator uygulamanizdaki guncel 6 haneli kodu girin.';
            $_SESSION['totp_setup_step'] = 'verify';
        } else {
            $backupCodes = mynak_totp_generate_backup_codes(8);
            $hashed = mynak_totp_hash_backup_codes($backupCodes);
            $bcJson = json_encode($hashed, JSON_UNESCAPED_SLASHES);

            $u = $conn->prepare("UPDATE admin_users SET totp_secret = ?, totp_enabled = 1, totp_verified_at = NOW(), totp_backup_codes = ? WHERE id = ?");
            $u->bind_param('ssi', $secret, $bcJson, $adminId);
            if ($u->execute()) {
                unset($_SESSION['totp_setup_secret'], $_SESSION['totp_setup_step']);
                $_SESSION['totp_show_backup_once'] = $backupCodes;
                $flashSuccess = '2FA basariyla etkinlestirildi. Kurtarma kodlarini guvenli bir yere kaydedin.';
                $u->close();
                header('Location: security_2fa.php');
                exit;
            } else {
                $flashError = 'Veritabani guncellemesi basarisiz: ' . $conn->error;
            }
            $u->close();
        }
    }

    if ($action === 'cancel_setup' && !$isEnabled) {
        unset($_SESSION['totp_setup_secret'], $_SESSION['totp_setup_step']);
        header('Location: security_2fa.php');
        exit;
    }

    if ($action === 'disable_2fa' && $isEnabled) {
        $password = (string) ($_POST['password'] ?? '');
        $code     = (string) ($_POST['code'] ?? '');

        $passOk = false;
        $p = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
        $p->bind_param('i', $adminId);
        $p->execute();
        $pr = $p->get_result();
        if ($pr && ($prow = $pr->fetch_assoc())) {
            $passOk = password_verify($password, (string) $prow['password']);
        }
        $p->close();

        $codeOk = mynak_totp_verify((string) $adminRow['totp_secret'], $code);

        if (!$passOk) {
            $flashError = 'Sifre hatali.';
        } elseif (!$codeOk) {
            $flashError = '2FA kodu hatali.';
        } else {
            $u = $conn->prepare("UPDATE admin_users SET totp_secret = NULL, totp_enabled = 0, totp_verified_at = NULL, totp_backup_codes = NULL WHERE id = ?");
            $u->bind_param('i', $adminId);
            if ($u->execute()) {
                $flashSuccess = '2FA devre disi birakildi.';
                $u->close();
                header('Location: security_2fa.php');
                exit;
            } else {
                $flashError = 'Veritabani guncellemesi basarisiz: ' . $conn->error;
            }
            $u->close();
        }
    }

    if ($action === 'regen_backup_codes' && $isEnabled) {
        $code = (string) ($_POST['code'] ?? '');
        if (!mynak_totp_verify((string) $adminRow['totp_secret'], $code)) {
            $flashError = '2FA kodu hatali.';
        } else {
            $newCodes = mynak_totp_generate_backup_codes(8);
            $hashed = mynak_totp_hash_backup_codes($newCodes);
            $bcJson = json_encode($hashed, JSON_UNESCAPED_SLASHES);
            $u = $conn->prepare("UPDATE admin_users SET totp_backup_codes = ? WHERE id = ?");
            $u->bind_param('si', $bcJson, $adminId);
            if ($u->execute()) {
                $_SESSION['totp_show_backup_once'] = $newCodes;
                $flashSuccess = 'Yeni kurtarma kodlari uretildi. Lutfen kaydedin.';
                $u->close();
                header('Location: security_2fa.php');
                exit;
            } else {
                $flashError = 'Veritabani guncellemesi basarisiz: ' . $conn->error;
            }
            $u->close();
        }
    }
}

/* --------------------------------------------------------------------- *
 * Verileri yenile (POST sonrasi redirect olmadiysa)
 * --------------------------------------------------------------------- */
if ($adminRow) {
    $stmt = $conn->prepare("SELECT id, username, email, totp_secret, totp_enabled, totp_verified_at, totp_backup_codes FROM admin_users WHERE id = ?");
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) { $adminRow = $res->fetch_assoc(); }
    $stmt->close();
    $isEnabled = !empty($adminRow['totp_enabled']);
}

$setupSecret = (string) ($_SESSION['totp_setup_secret'] ?? '');
$showBackupOnce = $_SESSION['totp_show_backup_once'] ?? null;
unset($_SESSION['totp_show_backup_once']);

$backupRemaining = 0;
if ($isEnabled && !empty($adminRow['totp_backup_codes'])) {
    $stored = mynak_totp_decode_backup_codes_json((string) $adminRow['totp_backup_codes']);
    $backupRemaining = mynak_totp_count_unused_backup_codes($stored);
}

$accountLabel = (string) ($adminRow['email'] ?? $adminRow['username'] ?? 'admin');

include 'includes/header.php';
?>

<style>
.mynak-2fa-card { border-radius: 14px; box-shadow: 0 6px 18px rgba(0,0,0,.06); }
.mynak-2fa-secret { font-family: 'Courier New', monospace; font-size: 1.05rem; letter-spacing: 2px; word-break: break-all; }
.mynak-2fa-qr { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; display: inline-block; }
.mynak-2fa-qr img { display: block; max-width: 220px; height: auto; }
.mynak-2fa-step { display: flex; align-items: center; gap: .5rem; padding: .35rem .75rem; border-radius: 999px; background: #eff6ff; color: #1e40af; font-size: .8rem; font-weight: 600; }
.mynak-2fa-step.done { background: #dcfce7; color: #166534; }
.mynak-2fa-backup { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 1rem; }
.mynak-2fa-backup code { font-size: 1.05rem; padding: .35rem .65rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; display: inline-block; margin: .15rem; }
.mynak-2fa-status-on  { background: #dcfce7; color: #166534; padding: .35rem .85rem; border-radius: 999px; font-weight: 600; }
.mynak-2fa-status-off { background: #fee2e2; color: #991b1b; padding: .35rem .85rem; border-radius: 999px; font-weight: 600; }
.mynak-2fa-code-input { font-size: 1.5rem; letter-spacing: 8px; text-align: center; font-family: 'Courier New', monospace; }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1"><i class="bx bx-shield-quarter me-2"></i>2FA Guvenlik</h2>
            <p class="text-muted mb-0">Hesabin uzerine ek bir dogrulama katmani ekleyin. Google Authenticator, Authy, 1Password vb. uygulamalarla uyumludur.</p>
        </div>
        <span class="<?php echo $isEnabled ? 'mynak-2fa-status-on' : 'mynak-2fa-status-off'; ?>">
            <i class="bx <?php echo $isEnabled ? 'bx-check-circle' : 'bx-x-circle'; ?>"></i>
            <?php echo $isEnabled ? '2FA Aktif' : '2FA Pasif'; ?>
        </span>
    </div>

    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($flashError); ?></div>
    <?php endif; ?>

    <?php if (is_array($showBackupOnce) && !empty($showBackupOnce)): ?>
        <div class="card mynak-2fa-card mb-3 border-warning">
            <div class="card-body">
                <h5 class="mb-2 text-warning"><i class="bx bx-key me-1"></i>Kurtarma Kodlari (Sadece SIMDI gosteriliyor)</h5>
                <p class="mb-2 text-muted">Authenticator cihazina erisemediginizde her birini bir kez kullanabilirsiniz. Bu sayfadan ayrildiktan sonra tekrar gosterilemez.</p>
                <div class="mynak-2fa-backup mb-2">
                    <?php foreach ($showBackupOnce as $bc): ?>
                        <code><?php echo htmlspecialchars($bc); ?></code>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-outline-secondary btn-sm" onclick="mynak2faCopyBackup(this)" data-codes="<?php echo htmlspecialchars(implode(' ', $showBackupOnce)); ?>">
                    <i class="bx bx-copy"></i> Tumunu Kopyala
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isEnabled): ?>
        <!-- Aktif Durum: Disable + Yeni Backup Codes -->
        <div class="row g-3">
            <div class="col-md-7">
                <div class="card mynak-2fa-card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bx bx-shield me-1 text-success"></i>2FA Aktif</h5>
                        <ul class="list-unstyled mb-3">
                            <li><strong>Hesap:</strong> <?php echo htmlspecialchars($accountLabel); ?></li>
                            <li><strong>Etkinlestirme:</strong> <?php echo htmlspecialchars((string) ($adminRow['totp_verified_at'] ?? '-')); ?></li>
                            <li><strong>Kalan kurtarma kodu:</strong> <span class="badge bg-<?php echo $backupRemaining > 2 ? 'success' : 'danger'; ?>"><?php echo $backupRemaining; ?> / 8</span></li>
                        </ul>
                        <p class="text-muted small mb-0">Bir sonraki giriste kullanici adi + sifrenin yaninda 6 haneli kod istenecek.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card mynak-2fa-card mb-3">
                    <div class="card-body">
                        <h6 class="mb-2"><i class="bx bx-refresh"></i> Kurtarma Kodlarini Yenile</h6>
                        <form method="POST">
                            <input type="hidden" name="action" value="regen_backup_codes">
                            <div class="mb-2">
                                <label class="form-label small">Authenticator Kodu</label>
                                <input type="text" name="code" class="form-control mynak-2fa-code-input" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required>
                            </div>
                            <button class="btn btn-warning btn-sm w-100" type="submit"><i class="bx bx-sync"></i> Yenile</button>
                            <p class="text-muted small mb-0 mt-2">Eski kodlar gecersiz olur.</p>
                        </form>
                    </div>
                </div>
                <div class="card mynak-2fa-card border-danger">
                    <div class="card-body">
                        <h6 class="mb-2 text-danger"><i class="bx bx-shield-x"></i> 2FA'yi Devre Disi Birak</h6>
                        <form method="POST" onsubmit="return confirm('2FA devre disi birakilsin mi?');">
                            <input type="hidden" name="action" value="disable_2fa">
                            <div class="mb-2">
                                <label class="form-label small">Sifre</label>
                                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Authenticator Kodu</label>
                                <input type="text" name="code" class="form-control mynak-2fa-code-input" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required>
                            </div>
                            <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bx bx-power-off"></i> Devre Disi Birak</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Setup Wizard -->
        <?php if ($setupSecret === ''): ?>
            <div class="card mynak-2fa-card">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bx bx-rocket me-1"></i>2FA Kurulumu</h5>
                    <ol class="mb-3">
                        <li>Kurulumu baslatin — sizin icin yeni bir secret uretilir.</li>
                        <li>Authenticator uygulamaniza QR kodunu okutun (veya manuel olarak girin).</li>
                        <li>Uretilen 6 haneli kodu girerek dogrulayin.</li>
                        <li>Tek-kullanimlik kurtarma kodlarinizi kaydedin.</li>
                    </ol>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="start_setup">
                        <button class="btn btn-primary"><i class="bx bx-shield-quarter"></i> Kuruluma Basla</button>
                    </form>
                </div>
            </div>
        <?php else:
            $otpauth = mynak_totp_otpauth_uri($siteName . ' Admin', $accountLabel, $setupSecret);
            $qrUrl   = mynak_totp_qr_image_url($otpauth, 220);
        ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card mynak-2fa-card h-100">
                        <div class="card-body">
                            <span class="mynak-2fa-step done"><i class="bx bx-check"></i> 1. Secret Uretildi</span>
                            <h6 class="mt-3 mb-2">QR Kodunu Okutun</h6>
                            <div class="mynak-2fa-qr mb-3">
                                <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="2FA QR" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none'; document.getElementById('qrFallback').style.display='block';">
                            </div>
                            <div id="qrFallback" style="display:none" class="alert alert-info small">QR yuklenemedi — asagidaki secret'i authenticator uygulamaniza manuel olarak girin.</div>

                            <h6 class="mt-2 mb-1">Manuel Giris</h6>
                            <p class="text-muted small mb-2">Hesap: <code><?php echo htmlspecialchars($accountLabel); ?></code></p>
                            <p class="text-muted small mb-2">Issuer: <code><?php echo htmlspecialchars($siteName); ?> Admin</code></p>
                            <p class="text-muted small mb-1">Secret (Base32):</p>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="mynak-2fa-secret flex-grow-1"><?php echo htmlspecialchars(chunk_split($setupSecret, 4, ' ')); ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="mynak2faCopyText(this, '<?php echo htmlspecialchars($setupSecret); ?>')"><i class="bx bx-copy"></i></button>
                            </div>
                            <p class="text-muted small mb-0">Algoritma: SHA1 · Periyot: 30s · Hane: 6</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mynak-2fa-card h-100">
                        <div class="card-body">
                            <span class="mynak-2fa-step"><i class="bx bx-shield"></i> 2. Kodu Dogrula</span>
                            <p class="mt-3 text-muted small">Authenticator uygulamanizda gosterilen 6 haneli kodu girin.</p>
                            <form method="POST" autocomplete="off">
                                <input type="hidden" name="action" value="verify_setup">
                                <div class="mb-3">
                                    <input type="text" name="code" class="form-control mynak-2fa-code-input" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus placeholder="000000">
                                </div>
                                <button class="btn btn-success w-100"><i class="bx bx-check-shield"></i> Dogrula ve Etkinlestir</button>
                            </form>
                            <hr>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="cancel_setup">
                                <button class="btn btn-link text-muted btn-sm p-0">Kurulumu iptal et ve secret'i sifirla</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function mynak2faCopyText(btn, txt) {
    navigator.clipboard.writeText(txt).then(function () {
        var prev = btn.innerHTML;
        btn.innerHTML = '<i class="bx bx-check"></i>';
        setTimeout(function () { btn.innerHTML = prev; }, 1500);
    });
}
function mynak2faCopyBackup(btn) {
    var codes = btn.getAttribute('data-codes') || '';
    navigator.clipboard.writeText(codes.replace(/ /g, '\n')).then(function () {
        var prev = btn.innerHTML;
        btn.innerHTML = '<i class="bx bx-check"></i> Kopyalandi';
        setTimeout(function () { btn.innerHTML = prev; }, 1500);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
