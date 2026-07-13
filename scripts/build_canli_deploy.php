<?php
/**
 * Canli deploy paketi olusturucu.
 * - deploy/canli/CANLI_MIGRATION.sql  (ALTER + 10 UPDATE + pages arsivi, idempotent)
 * - deploy/canli/files/               (degisen PHP dosyalari, orijinal klasor yapisiyla)
 * - deploy/canli/TALIMAT.md           (FTP + cPanel phpMyAdmin adim adim)
 *
 * Kullanim:
 *   php scripts\build_canli_deploy.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { exit("CLI only.\n"); }

$root = dirname(__DIR__);
require_once $root . '/config/db.php';

/** @var mysqli $conn */
$conn->set_charset('utf8mb4');

$out = $root . '/deploy/canli';
if (!is_dir($out)) { mkdir($out, 0775, true); }
if (!is_dir($out . '/files/includes')) { mkdir($out . '/files/includes', 0775, true); }
if (!is_dir($out . '/files/includes/seo_runtime')) { mkdir($out . '/files/includes/seo_runtime', 0775, true); }
if (!is_dir($out . '/files/admin')) { mkdir($out . '/files/admin', 0775, true); }
if (!is_dir($out . '/files/admin/includes')) { mkdir($out . '/files/admin/includes', 0775, true); }
if (!is_dir($out . '/files/scripts')) { mkdir($out . '/files/scripts', 0775, true); }
if (!is_dir($out . '/files/includes/pipeline')) { mkdir($out . '/files/includes/pipeline', 0775, true); }
if (!is_dir($out . '/files/css')) { mkdir($out . '/files/css', 0775, true); }
if (!is_dir($out . '/files/js')) { mkdir($out . '/files/js', 0775, true); }

/* ===============================================================
   1) CANLI_MIGRATION.sql
   =============================================================== */

$sqlLines = [];
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "-- MY Nakliyat — Canli Veritabani Migration (services.icerik)";
$sqlLines[] = "-- Amac: 10 hizmet sayfasinda detay icin uzun HTML icerigi saglamak.";
$sqlLines[] = "-- Ana sayfa kartlari (services.aciklama) KORUNUR.";
$sqlLines[] = "-- Yeniden calistirilabilir (idempotent). Once yedek alin.";
$sqlLines[] = "-- Olusturuldu: " . date('Y-m-d H:i:s');
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "";
$sqlLines[] = "-- ADIM 0: (opsiyonel) services tablosunun yedegini al";
$sqlLines[] = "-- CREATE TABLE IF NOT EXISTS services_backup_" . date('Ymd') . " LIKE services;";
$sqlLines[] = "-- INSERT INTO services_backup_" . date('Ymd') . " SELECT * FROM services;";
$sqlLines[] = "";
$sqlLines[] = "-- ADIM 1: services tablosuna 'icerik' MEDIUMTEXT kolonu ekle (yoksa)";
$sqlLines[] = "-- IF NOT EXISTS MariaDB 10.0.2+ / MySQL 8.0.29+ gerekir. Eski surumde hata alirsaniz";
$sqlLines[] = "-- asagidaki satiri yorum yapin ve manuel olarak ekleyip yine calistirin.";
$sqlLines[] = "ALTER TABLE services ADD COLUMN IF NOT EXISTS icerik MEDIUMTEXT NULL AFTER aciklama;";
$sqlLines[] = "";
$sqlLines[] = "-- ADIM 2: 10 hizmet icin pages.content -> services.icerik verisi";

$res = $conn->query("
    SELECT s.slug, p.content
    FROM services s
    JOIN pages p ON p.slug = s.slug
    WHERE s.status = 1
      AND (p.status IN (0,1))
      AND s.icerik IS NOT NULL
      AND CHAR_LENGTH(s.icerik) > 500
    ORDER BY s.slug
");
$migratedSlugs = [];
while ($row = $res->fetch_assoc()) {
    $slug = (string) $row['slug'];
    $content = (string) $row['content'];
    // content yerine yerelde services.icerik'te ne varsa onu kullan (guncel/nihai)
    $stmt = $conn->prepare("SELECT icerik FROM services WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $localIcerik = (string) ($stmt->get_result()->fetch_assoc()['icerik'] ?? '');
    $stmt->close();
    if (mb_strlen($localIcerik) < 500) { continue; }

    $escSlug = $conn->real_escape_string($slug);
    $escContent = $conn->real_escape_string($localIcerik);
    $sqlLines[] = "UPDATE services SET icerik = '" . $escContent . "', updated_at = NOW() WHERE slug = '" . $escSlug . "' AND status = 1;";
    $migratedSlugs[] = $slug;
}

if ($migratedSlugs === []) {
    // Yerelde hic migrate kaydi yoksa SQL'i bos ureterek yaniltmayalim
    echo "UYARI: Yerelde services.icerik dolu kayit bulunamadi.\n";
    exit(2);
}

$sqlLines[] = "";
$sqlLines[] = "-- ADIM 3: Eski pages kayitlarini arsivle (status=0) — kart/layout duplikasyonunu onler";
$inList = "'" . implode("','", array_map(fn($s) => addslashes($s), $migratedSlugs)) . "'";
$sqlLines[] = "UPDATE pages SET status = 0 WHERE slug IN ({$inList}) AND status = 1;";
$sqlLines[] = "";
$sqlLines[] = "-- ADIM 3.5: Ic temizlik — eski migration yedek tablosu (varsa kaldir)";
$sqlLines[] = "-- Bu tablo 2026-04 ilk migration denemesinde olusmustu; artik ihtiyac yok.";
$sqlLines[] = "-- Canlida muhtemelen zaten yok; IF EXISTS hata vermeden atlar.";
$sqlLines[] = "DROP TABLE IF EXISTS services_backup;";
$sqlLines[] = "";
$sqlLines[] = "-- ADIM 4: Dogrulama sorgusu (islem sonrasi calistirin)";
$sqlLines[] = "SELECT slug, CHAR_LENGTH(aciklama) AS aciklama_len, CHAR_LENGTH(icerik) AS icerik_len";
$sqlLines[] = "FROM services WHERE slug IN ({$inList}) ORDER BY icerik_len DESC;";
$sqlLines[] = "";
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "-- ADIM 5 (Madde 3): form_submissions pipeline status migration";
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "-- Eski semantik: 0=Bekliyor, 1=Islendi, 2=Reddedildi (3 deger)";
$sqlLines[] = "-- Yeni semantik: 0=Yeni, 1=Arandi, 2=Teklif Verildi, 3=Kazanildi, 4=Kaybedildi, 5=Spam";
$sqlLines[] = "-- Eski 'Reddedildi' (2) anlami yeni semantikte 'Spam' (5)'e karsilik gelir.";
$sqlLines[] = "-- Idempotent: tekrar calistirilirsa etkilenen satir 0 olur.";
$sqlLines[] = "UPDATE form_submissions SET status = 5 WHERE status = 2 AND id IN (";
$sqlLines[] = "  -- Sadece eski kayitlari (yeni semantikten once eklenenleri) hedefle:";
$sqlLines[] = "  -- 2026-04-25 oncesinde eklenen ve status=2 olanlar (eski 'Reddedildi' anlami).";
$sqlLines[] = "  SELECT id FROM (";
$sqlLines[] = "    SELECT id FROM form_submissions WHERE status = 2";
$sqlLines[] = "      AND COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date) < '2026-04-25 00:00:00'";
$sqlLines[] = "  ) AS x";
$sqlLines[] = ");";
$sqlLines[] = "";
$sqlLines[] = "-- (Opsiyonel) Pipeline sonrasi dogrulama sorgusu";
$sqlLines[] = "SELECT status, COUNT(*) cnt FROM form_submissions GROUP BY status ORDER BY status;";
$sqlLines[] = "";
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "-- ADIM 6 (Madde 4): Admin 2FA (TOTP) sutunlari (idempotent)";
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "-- Saf PHP TOTP (RFC 6238). Google Authenticator / Authy / 1Password uyumlu.";
$sqlLines[] = "-- Kurulum: Admin panel -> sol menude 'Guvenlik (2FA)' -> Kuruluma Basla.";
$sqlLines[] = "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(64) NULL DEFAULT NULL;";
$sqlLines[] = "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) NOT NULL DEFAULT 0;";
$sqlLines[] = "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS totp_verified_at DATETIME NULL DEFAULT NULL;";
$sqlLines[] = "ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS totp_backup_codes MEDIUMTEXT NULL DEFAULT NULL;";
$sqlLines[] = "";
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "-- ADIM 7 (Madde 5): case_studies tablosu (Musteri Hikayeleri)";
$sqlLines[] = "-- =====================================================================";
$sqlLines[] = "-- Public route: /musteri-hikayeleri (listing) + /musteri-hikayeleri/<slug> (detay)";
$sqlLines[] = "-- Schema: BreadcrumbList (otomatik) + Review JSON-LD";
$sqlLines[] = "-- Idempotent (CREATE TABLE IF NOT EXISTS).";
$sqlLines[] = "CREATE TABLE IF NOT EXISTS case_studies (";
$sqlLines[] = "  id INT AUTO_INCREMENT PRIMARY KEY,";
$sqlLines[] = "  slug VARCHAR(190) NOT NULL,";
$sqlLines[] = "  baslik VARCHAR(200) NOT NULL,";
$sqlLines[] = "  ozet VARCHAR(300) NULL,";
$sqlLines[] = "  icerik MEDIUMTEXT NULL,";
$sqlLines[] = "  musteri_ad VARCHAR(120) NULL,";
$sqlLines[] = "  musteri_yorumu TEXT NULL,";
$sqlLines[] = "  puan DECIMAL(2,1) NULL DEFAULT NULL,";
$sqlLines[] = "  kalkis_il VARCHAR(60) NULL,";
$sqlLines[] = "  varis_il VARCHAR(60) NULL,";
$sqlLines[] = "  ev_tipi VARCHAR(40) NULL,";
$sqlLines[] = "  tasima_tarihi DATE NULL,";
$sqlLines[] = "  fiyat_araligi VARCHAR(60) NULL,";
$sqlLines[] = "  gorsel VARCHAR(255) NULL,";
$sqlLines[] = "  meta_title VARCHAR(180) NULL,";
$sqlLines[] = "  meta_description VARCHAR(255) NULL,";
$sqlLines[] = "  status TINYINT(1) NOT NULL DEFAULT 0,";
$sqlLines[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$sqlLines[] = "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
$sqlLines[] = "  UNIQUE KEY uniq_slug (slug),";
$sqlLines[] = "  KEY idx_status_created (status, created_at)";
$sqlLines[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$sqlLines[] = "";
$sqlLines[] = "-- Müşteri hikâyesi verisi otomatik taşınmaz.";
$sqlLines[] = "-- Yalnızca gerçek ve doğrulanabilir kayıtlar admin panelinden taslak olarak eklenmelidir.";

$sqlLines[] = "";
$sqlLines[] = "-- (Opsiyonel) Dogrulama";
$sqlLines[] = "SELECT id, slug, baslik, status FROM case_studies ORDER BY id;";
$sqlLines[] = "";
$sqlLines[] = "-- SON.";

file_put_contents($out . '/CANLI_MIGRATION.sql', implode("\n", $sqlLines));
echo "✓ CANLI_MIGRATION.sql yazildi (" . count($migratedSlugs) . " slug)\n";

/* ===============================================================
   2) Dosyalar (degisen 2 PHP dosyasi)
   =============================================================== */

$files = [
    // --- Core (zorunlu): detay render onceligi + admin icerik alani ---
    'includes/front_controller_slug.php'             => 'files/includes/front_controller_slug.php',
    'admin/service_edit.php'                         => 'files/admin/service_edit.php',

    // --- 2026-04-24 celiski fix'leri (admin raporlarinin services.icerik'i dogru okumasi) ---
    'admin/includes/dashboard_seo_metrics.php'       => 'files/admin/includes/dashboard_seo_metrics.php',
    'admin/gsc_low_ctr_report.php'                   => 'files/admin/gsc_low_ctr_report.php',
    'admin/local_cluster_coverage.php'               => 'files/admin/local_cluster_coverage.php',

    // --- Otonom 5 madde (2026-04-24 FAZ 1) ---
    // Sistem sagligi widget'i (dashboard)
    'admin/dashboard.php'                            => 'files/admin/dashboard.php',
    'admin/includes/mynak_admin_recommendations.php'   => 'files/admin/includes/mynak_admin_recommendations.php',
    'admin/includes/dashboard_system_health.php'     => 'files/admin/includes/dashboard_system_health.php',
    // Blog saglik raporu
    'admin/blog_health_report.php'                   => 'files/admin/blog_health_report.php',
    'admin/includes/sidebar.php'                     => 'files/admin/includes/sidebar.php',
    // Breadcrumb TR karakter eslestirme genisletme
    'includes/breadcrumb_jsonld.php'                 => 'files/includes/breadcrumb_jsonld.php',
    // FAQ extractor h1 destegi + Service schema zenginlestirme
    'includes/seo_runtime/faq_extractor.php'         => 'files/includes/seo_runtime/faq_extractor.php',
    'includes/seo_runtime/jsonld_encode_and_schema.php' => 'files/includes/seo_runtime/jsonld_encode_and_schema.php',

    // --- Madde 6-7: Duplicate slug consolidation scripti (CLI, canlida --apply ile calistirilir) ---
    'scripts/canonical_slug_consolidate.php'         => 'files/scripts/canonical_slug_consolidate.php',
    // Eski migration scriptine deprecated guard (yanlislikla calistirilmayi onler)
    'scripts/migrate_service_content_from_pages.php' => 'files/scripts/migrate_service_content_from_pages.php',

    // --- .htaccess: 4 yeni duplicate slug 301 redirect + teklif-al context-aware ---
    '.htaccess'                                      => 'files/.htaccess',

    // --- 2026-04-25 FAZ 2: CRO/UX otonom geliştirmeler ---
    // Madde 1a: WhatsApp Float Button (yeşil, sağ alt, sayfa-tipine göre preset mesaj)
    'includes/whatsapp_float_button.php'             => 'files/includes/whatsapp_float_button.php',
    // Madde 1b: Telefon (Ara) Float Button (mavi, WhatsApp FAB'ın üstünde, settings.phone1)
    'includes/phone_float_button.php'                => 'files/includes/phone_float_button.php',
    'includes/mobile_sticky_cta_bar.php'             => 'files/includes/mobile_sticky_cta_bar.php',
    // Footer her iki FAB'ı include eder
    'includes/footer.php'                            => 'files/includes/footer.php',

    // --- Madde 3: Form Takip Panosu (6-asamali pipeline) ---
    // Admin form listeleme sayfasi: KPI kartlari, hizli dropdown, otomatik damgali notlar, 6 status
    'admin/form_submissions.php'                     => 'files/admin/form_submissions.php',
    // Dashboard form pipeline widget (yeniden kullanilabilir collector)
    'admin/includes/dashboard_form_pipeline.php'     => 'files/admin/includes/dashboard_form_pipeline.php',
    // Dashboard sayfasi (Form Pipeline panosunu render eder)
    // NOT: 'admin/dashboard.php' yukarida zaten ekli, tekrar etme.

    // --- Madde 4: Admin 2FA (TOTP, saf PHP, harici lib yok) ---
    'admin/includes/totp_helper.php'                 => 'files/admin/includes/totp_helper.php',
    'admin/security_2fa.php'                         => 'files/admin/security_2fa.php',
    'admin/login_2fa.php'                            => 'files/admin/login_2fa.php',
    'admin/login.php'                                => 'files/admin/login.php',
    'admin/includes/require_admin_web.php'           => 'files/admin/includes/require_admin_web.php',
    // NOT: 'admin/includes/sidebar.php' yukarida zaten ekli (Guvenlik linki + Musteri Hikayeleri linki ayni dosyada)

    // --- Madde 5: Musteri Hikayeleri (case_studies) ---
    'musteri-hikayeleri.php'                         => 'files/musteri-hikayeleri.php',
    'admin/case_studies.php'                         => 'files/admin/case_studies.php',
    'admin/case_study_edit.php'                      => 'files/admin/case_study_edit.php',
    'includes/sitemap_build.php'                     => 'files/includes/sitemap_build.php', // sitemap entegrasyon

    // --- 2026-04-25 FAZ 3: AI / E-E-A-T ve doğrulanabilir güven sinyalleri ---
    // jsonld_encode_and_schema.php zaten yukarida ekli (FAZ 1) — FAZ 3 degisiklikleri ayni dosyada.
    // sayfa.php: Hakkimizda trust badges + AboutPage JSON-LD (slug='hakkimizda' icin)
    'sayfa.php'                                      => 'files/sayfa.php',
    // index.php: Slider altında kanıt sayfalarına bağlanan güven şeridi
    'index.php'                                      => 'files/index.php',
    // llms-full-tr.txt: Kanonik AI kaynak ve doğrulama politikası
    'llms-full-tr.txt'                               => 'files/llms-full-tr.txt',
    // GBP verisini yalnızca Places Details API üzerinden senkronize eden script
    'scripts/seed_gbp_settings.php'                  => 'files/scripts/seed_gbp_settings.php',

    // --- 2026-04-25 FAZ 3.1: Marka yazım tutarlılığı ve kanıt temizliği ---
    // "My Nakliyat" -> "MY Nakliyat" idempotent normalize scripti (settings + about + pages + blog + services)
    'scripts/normalize_brand_capitalization.php'     => 'files/scripts/normalize_brand_capitalization.php',

    // --- 2026-04-25 FAZ 3.2: Kanonik firma aciklamasi (lojistik -> 5 birincil hizmet) ---
    // settings.short_description, settings.site_description, settings.global_meta_description
    // tek kanonik cumleye senkronlanir (idempotent, dry-run + --apply destekler)
    'scripts/update_brand_canonical_description.php' => 'files/scripts/update_brand_canonical_description.php',
    // A1: Ekip seed + team_members.id AUTO_INCREMENT duzeltmesi (canlida bir kez)
    'scripts/fix_team_members_autoinc.php'           => 'files/scripts/fix_team_members_autoinc.php',
    'scripts/seed_team_members.php'                  => 'files/scripts/seed_team_members.php',
    // A2: Eski örnek müşteri hikâyelerini pasife alma
    'scripts/seed_case_studies_extra.php'            => 'files/scripts/seed_case_studies_extra.php',
    'includes/functions.php'                         => 'files/includes/functions.php',
    // Blog sayfa basligi/metasinda "lojistik" yerine 5 birincil hizmet
    'blog.php'                                       => 'files/blog.php',
    // A3: 10 yuksek-trafik blog — AI ozet (ust) + HowTo JSON-LD
    'blog-detay.php'                                 => 'files/blog-detay.php',
    'includes/pipeline/blog_aio_boost.php'            => 'files/includes/pipeline/blog_aio_boost.php',
    // B1: 5 sehir cifti landing (izmir-istanbul, ankara, bursa, antalya, mugla)
    'includes/pipeline/city_pair_landings.php'       => 'files/includes/pipeline/city_pair_landings.php',
    // B2: Taşınma kontrol listesi + e-posta lead
    'includes/pipeline/lead_magnet_tasinma_checklist.php' => 'files/includes/pipeline/lead_magnet_tasinma_checklist.php',
    'includes/front_controller.php'                 => 'files/includes/front_controller.php',
    'includes/pipeline/public_footer_context.php'  => 'files/includes/pipeline/public_footer_context.php',
    'tasinma-kontrol-listesi.php'                    => 'files/tasinma-kontrol-listesi.php',
    'tasinma-kontrol-listesi-icerik.php'             => 'files/tasinma-kontrol-listesi-icerik.php',
    // B3: Microsoft Clarity
    'includes/mynak_clarity.php'                     => 'files/includes/mynak_clarity.php',
    'includes/header.php'                            => 'files/includes/header.php',
    'includes/mynak_home_videos.php'                 => 'files/includes/mynak_home_videos.php',
    // C4: mysqldump + 30 gun rotasyon + storage/db_backups (HTTP engelli)
    'scripts/backup_database.php'                      => 'files/scripts/backup_database.php',
    'scripts/smoke_check.php'                          => 'files/scripts/smoke_check.php',
    'scripts/C4_uptime_rehber_kisa.txt'               => 'files/scripts/C4_uptime_rehber_kisa.txt',
    'scripts/C5_performans_rehber_kisa.txt'           => 'files/scripts/C5_performans_rehber_kisa.txt',
    'scripts/C6_seo_sema_rehber_kisa.txt'            => 'files/scripts/C6_seo_sema_rehber_kisa.txt',
    'storage/db_backups/.htaccess'                     => 'files/storage/db_backups/.htaccess',
    // /assets: Cache-Control + Expires (PageSpeed, tekrar ziyaret)
    'assets/.htaccess'                                 => 'files/assets/.htaccess',
    'storage/db_backups/.gitkeep'                     => 'files/storage/db_backups/.gitkeep',
    'includes/pipeline/mynak_gbp_post_guide_tr.php'  => 'files/includes/pipeline/mynak_gbp_post_guide_tr.php',
    'rehber-google-isletme-gonderileri.php'            => 'files/rehber-google-isletme-gonderileri.php',
    'includes/controllers/HomePageController.php'    => 'files/includes/controllers/HomePageController.php',
    'admin/gbp_gonderi_rehberi.php'                  => 'files/admin/gbp_gonderi_rehberi.php',
    // create_rich_snippets_table.php icindeki ornek snippet description guncellendi
    // (admin/create_rich_snippets_table.php zaten yukarida ekli)
    // Footer default "My Nakliyat" -> "MY Nakliyat"
    // (footer.php zaten yukarida ekli; FAZ 3.1 degisiklikleri ayni dosyada)
    'includes/pipeline/public_layout_context.php'    => 'files/includes/pipeline/public_layout_context.php',
    'includes/pipeline/ilce_unique_opening.php'        => 'files/includes/pipeline/ilce_unique_opening.php',
    // CSS / JS yorum satirlarinda marka yazimi (gorunmez ama bilgi notu)
    'css/main.css'                                   => 'files/css/main.css',
    'js/translator.js'                               => 'files/js/translator.js',
    'js/games.js'                                    => 'files/js/games.js',
    // llms-full-tr.php fallback embed icindeki marka yazimi
    'llms-full-tr.php'                               => 'files/llms-full-tr.php',
    // Admin form default value'lar (yeni kurulum / placeholder)
    'admin/seo_management.php'                       => 'files/admin/seo_management.php',
    'admin/add_seo_fields.php'                       => 'files/admin/add_seo_fields.php',
    'admin/welcome_popup.php'                        => 'files/admin/welcome_popup.php',
    'admin/rich_snippets.php'                        => 'files/admin/rich_snippets.php',
    'admin/create_rich_snippets_table.php'           => 'files/admin/create_rich_snippets_table.php',
    'admin/settings.php'                             => 'files/admin/settings.php',
    'admin/kids_management.php'                      => 'files/admin/kids_management.php',
];
foreach ($files as $srcRel => $dstRel) {
    $src = $root . '/' . $srcRel;
    $dst = $out . '/' . $dstRel;
    if (!file_exists($src)) { echo "EKSIK: $srcRel\n"; continue; }
    copy($src, $dst);
    echo "✓ Kopyalandi: $srcRel -> deploy/canli/$dstRel\n";
}

/* ===============================================================
   3) TALIMAT.md
   =============================================================== */

$rawSlugList = implode(', ', $migratedSlugs);
$talimat = <<<MD
# Canlıya Deploy Talimatı

**Tarih:** %DATE%
**Hedef:** https://www.mynakliyat.com.tr

## Güvenli uygulama sırası

1. Canlı veritabanı ile değiştirilecek dosyaların yedeğini alın.
2. `CANLI_MIGRATION.sql` dosyasını inceleyin; yalnızca onaylanan migration bölümlerini uygulayın.
3. `files/` altındaki dosyaları aynı dizin yapısıyla yükleyin.
4. Müşteri hikâyesi, ekip profili, yazar, yorum, puan, ödül veya sertifika verisini otomatik üretmeyin.
5. Google puan/yorum verisini yalnızca Places Details API senkronizasyonuyla güncelleyin.
6. PHPUnit, PHP lint, JSON-LD, canonical, sitemap, robots, API ve Markdown kontrollerini çalıştırın.
7. Ana sayfa, hizmet, blog, şehir, yazar ve müşteri hikâyesi rotalarında 200/301/404 davranışını doğrulayın.
8. Sorun halinde yedekten geri dönün.

## Migration kapsamı

İşlenen hizmet slugları: $rawSlugList

Müşteri hikâyeleri ve ekip üyeleri varsayılan olarak taslak/pasif kalır. Yalnızca görünür ve doğrulanabilir bilgiler admin panelinden yayınlanmalıdır.
MD;

$talimat = str_replace(
    ['%DATE%', '%SLUGS%', '%SLUGS_QUOTED%'],
    [date('Y-m-d H:i'), $rawSlugList, "'" . implode("','", $migratedSlugs) . "'"],
    $talimat
);
file_put_contents($out . '/TALIMAT.md', $talimat);
echo "✓ TALIMAT.md yazildi\n";

/* ===============================================================
   4) Ozet
   =============================================================== */

echo "\n" . str_repeat('=', 70) . "\n";
echo "DEPLOY PAKETI HAZIR: {$out}\n";
echo str_repeat('=', 70) . "\n";
echo "Icindekiler:\n";
echo "  - CANLI_MIGRATION.sql (" . number_format(filesize($out . '/CANLI_MIGRATION.sql')) . " byte)\n";
echo "  - TALIMAT.md\n";
foreach ($files as $srcRel => $dstRel) {
    $p = $out . '/' . $dstRel;
    if (is_file($p)) {
        echo "  - {$dstRel} (" . number_format(filesize($p)) . " byte)\n";
    }
}
echo "Toplam migrate edilen slug: " . count($migratedSlugs) . "\n";
