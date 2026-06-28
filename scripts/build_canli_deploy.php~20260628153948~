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
$sqlLines[] = "  puan DECIMAL(2,1) NULL DEFAULT 5.0,";
$sqlLines[] = "  kalkis_il VARCHAR(60) NULL,";
$sqlLines[] = "  varis_il VARCHAR(60) NULL,";
$sqlLines[] = "  ev_tipi VARCHAR(40) NULL,";
$sqlLines[] = "  tasima_tarihi DATE NULL,";
$sqlLines[] = "  fiyat_araligi VARCHAR(60) NULL,";
$sqlLines[] = "  gorsel VARCHAR(255) NULL,";
$sqlLines[] = "  meta_title VARCHAR(180) NULL,";
$sqlLines[] = "  meta_description VARCHAR(255) NULL,";
$sqlLines[] = "  status TINYINT(1) NOT NULL DEFAULT 1,";
$sqlLines[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$sqlLines[] = "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,";
$sqlLines[] = "  UNIQUE KEY uniq_slug (slug),";
$sqlLines[] = "  KEY idx_status_created (status, created_at)";
$sqlLines[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$sqlLines[] = "";
$sqlLines[] = "-- Ornek 3 kayit (canlida da gostermek isterseniz):";
$sqlLines[] = "-- (Atlamak icin asagidaki INSERT'leri yorum yapin)";

// 3 sample kayıt için INSERT'leri yerelden çek ve aynen aktar
$resCs = $conn->query("SELECT slug, baslik, ozet, icerik, musteri_ad, musteri_yorumu, puan, kalkis_il, varis_il, ev_tipi, tasima_tarihi, fiyat_araligi, meta_title, meta_description, status FROM case_studies ORDER BY id LIMIT 3");
if ($resCs) {
    while ($cs = $resCs->fetch_assoc()) {
        $vals = [];
        foreach ($cs as $k => $v) {
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (is_numeric($v) && in_array($k, ['puan', 'status'], true)) {
                $vals[] = (string) $v;
            } else {
                $vals[] = "'" . $conn->real_escape_string((string) $v) . "'";
            }
        }
        $sqlLines[] = "INSERT IGNORE INTO case_studies (slug, baslik, ozet, icerik, musteri_ad, musteri_yorumu, puan, kalkis_il, varis_il, ev_tipi, tasima_tarihi, fiyat_araligi, meta_title, meta_description, status) VALUES (" . implode(', ', $vals) . ");";
    }
}

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
    'includes/front_controller_slug.php'             => 'files/includes/front_controller_slug.php', // route entegrasyon
    'includes/sitemap_build.php'                     => 'files/includes/sitemap_build.php', // sitemap entegrasyon

    // --- 2026-04-25 FAZ 3: AI / E-E-A-T zenginlestirme (Guvenilir Marka Odullu + GBP 5.0/270) ---
    // jsonld_encode_and_schema.php zaten yukarida ekli (FAZ 1) — FAZ 3 degisiklikleri ayni dosyada.
    // sayfa.php: Hakkimizda trust badges + AboutPage JSON-LD (slug='hakkimizda' icin)
    'sayfa.php'                                      => 'files/sayfa.php',
    // index.php: Slider altina ince trust strip (5,0 Google + 270+ Yorum + Guvenilir Marka Odullu + Sigortali + 81 il)
    'index.php'                                      => 'files/index.php',
    // llms-full-tr.txt: AI alinti ozeti + en iyi firma karsilastirma sablonlari
    'llms-full-tr.txt'                               => 'files/llms-full-tr.txt',
    // GBP rating settings seed scripti (canli icin manuel calistirilir; Places API yoksa fallback)
    'scripts/seed_gbp_settings.php'                  => 'files/scripts/seed_gbp_settings.php',

    // --- 2026-04-25 FAZ 3.1: Marka yazim tutarliligi + gercek odul listesi ---
    // "My Nakliyat" -> "MY Nakliyat" idempotent normalize scripti (settings + about + pages + blog + services)
    'scripts/normalize_brand_capitalization.php'     => 'files/scripts/normalize_brand_capitalization.php',

    // --- 2026-04-25 FAZ 3.2: Kanonik firma aciklamasi (lojistik -> 5 birincil hizmet) ---
    // settings.short_description, settings.site_description, settings.global_meta_description
    // tek kanonik cumleye senkronlanir (idempotent, dry-run + --apply destekler)
    'scripts/update_brand_canonical_description.php' => 'files/scripts/update_brand_canonical_description.php',
    // A1: Ekip seed + team_members.id AUTO_INCREMENT duzeltmesi (canlida bir kez)
    'scripts/fix_team_members_autoinc.php'           => 'files/scripts/fix_team_members_autoinc.php',
    'scripts/seed_team_members.php'                  => 'files/scripts/seed_team_members.php',
    // A2: Musteri Hikayeleri 6 kayit (migrate 3 + seed_case_studies_extra 3)
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
    'admin/includes/sidebar.php'                      => 'files/admin/includes/sidebar.php',
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
# Canliya Deploy Talimati

**Tarih:** %DATE%
**Hedef:** https://www.mynakliyat.com.tr
**Etki:** 10 hizmet sayfasinin detay icerigi uzun HTML ile dolar. Ana sayfa kartlari degismez.

---

## ICERIKLER

Bu klasorde:

### Veritabani
- `CANLI_MIGRATION.sql` — services.icerik migration (ALTER + 10 UPDATE + pages arsiv + services_backup drop)

### Degisen PHP dosyalari (yuklenecek)
- `files/includes/front_controller_slug.php` — detay render onceligi (icerik > aciklama)
- `files/includes/breadcrumb_jsonld.php` — TR karakter eslestirme 40+ yeni kelime (breadcrumb schema)
- `files/includes/seo_runtime/faq_extractor.php` — h1 baslıklari da FAQ schema icin yakala (1 soru -> ~40 soru)
- `files/includes/seo_runtime/jsonld_encode_and_schema.php` — Service schema zenginlestirme (brand, serviceOutput, aggregateRating, hoursAvailable, termsOfService)
- `files/admin/dashboard.php` — Sistem Sagligi widget dahil + SEO Skor paneli
- `files/admin/service_edit.php` — "Detay Sayfa Icerigi" alani (icerik) + SEO score body ayrimi
- `files/admin/blog_health_report.php` — YENI salt-okuma rapor (blog icerik saglık skoru)
- `files/admin/gsc_low_ctr_report.php` — GSC raporu services.icerik okur
- `files/admin/local_cluster_coverage.php` — local cluster raporu services.icerik okur
- `files/admin/includes/dashboard_seo_metrics.php` — dashboard icerik uzunlugu metrikleri
- `files/admin/includes/dashboard_system_health.php` — YENI sistem sagligi modulu (OPcache, cache klasoru, disk, PHPStan)
- `files/admin/includes/mynak_admin_recommendations.php` + `files/admin/dashboard.php` — “Yapilacaklar & yonlendirme” karti (ic tespit, admin linkleri)
- `files/admin/includes/sidebar.php` — "Blog Sagligi" linki eklendi
- `files/scripts/canonical_slug_consolidate.php` — YENI CLI, duplicate slug consolidation (idempotent)
- `files/scripts/migrate_service_content_from_pages.php` — ESKI migration scripti, deprecated guard eklendi
- `files/.htaccess` — 4 yeni 301 redirect (duplicate slug) + teklif-al context-aware
- `files/includes/whatsapp_float_button.php` — YENI WhatsApp floating button (yesil, sayfa-tipi farkli preset mesaj, GA4 dataLayer)
- `files/includes/phone_float_button.php` — YENI Telefon (Ara) floating button (mavi, WA FAB'in ustunde, settings.phone1, GA4 dataLayer)
- `files/includes/footer.php` — her iki FAB widget'ini include eder; `</body>` oncesi GA4 (gtag)
- `files/admin/form_submissions.php` — YENI 6-asamali pipeline (Yeni/Arandi/Teklif/Kazanildi/Kaybedildi/Spam) + KPI kartlari + hizli dropdown + otomatik damgali notlar + tel:/wa.me eylem butonlari
- `files/admin/includes/dashboard_form_pipeline.php` — YENI dashboard pipeline collector (salt-okuma) + son 7 gun sparkline datasi
- `files/admin/includes/totp_helper.php` — YENI saf PHP TOTP/HOTP/Base32 helper (RFC 6238)
- `files/admin/security_2fa.php` — YENI 2FA setup wizard (QR + manuel anahtar + dogrulama + backup kodlar)
- `files/admin/login_2fa.php` — YENI 2FA challenge ekrani (login sonrasi)
- `files/admin/login.php` — 2FA aktifse challenge'a yonlendirir
- `files/admin/includes/require_admin_web.php` — login_2fa.php public liste
- `files/musteri-hikayeleri.php` — YENI public listing + detay sayfasi (Madde 5)
- `files/admin/case_studies.php` — YENI admin liste (Musteri Hikayeleri CRUD)
- `files/admin/case_study_edit.php` — YENI admin yeni/duzenle (TinyMCE + slug autogen)
- `files/includes/sitemap_build.php` — sitemap'e /musteri-hikayeleri + detay URL'leri eklenir

### FAZ 3 — AI / E-E-A-T zenginlestirme (2026-04-25)
- `files/sayfa.php` — Hakkimizda sayfasinda 6'li "Trust Badges" kart + AboutPage JSON-LD (mainEntity = MovingCompany)
- `files/index.php` — Slider altina ince "Trust Strip" bandi (Guvenilir Marka Odullu + 5,0 Google + 270+ Yorum + Sigortali + 81 il)
- `files/includes/seo_runtime/jsonld_encode_and_schema.php` — MovingCompany schema'da: brand, slogan, award, foundingDate, knowsAbout, makesOffer, sameAs zenginlestirme + AggregateRating fallback (cache + settings)
- `files/llms-full-tr.txt` — Yeni "BÖLÜM 0.A — AI'YA HIZLI ALINTI ÖZETİ" + Bölüm 1'e marka detaylari (270+ yorum, 5,0/5) + 3 yeni AI cevap sablonu (en iyi firma, profesyonel, ilceler)
- `files/scripts/seed_gbp_settings.php` — YENI CLI: settings tablosuna `google_place_rating=5.0` + `google_total_reviews=270` yazar + cache/gbp_data.json olusturur (Places API yoksa fallback)

### FAZ 3.1 — Marka yazim tutarliligi + gercek odul listesi (2026-04-25b)
- `files/includes/seo_runtime/jsonld_encode_and_schema.php` — schema `award` listesi 6 GERCEK odul (ISO 9001, Sehirler Arasi, Kurumsal, Guvenilir Marka, Altin Marka, Lider Tasimacilik)
- `files/sayfa.php` — Hakkimizda trust badges 9 karta cikti (ISO 9001, Sehirler Arasi Nakliye Odulu, Altin Marka 2016 dahil)
- `files/llms-full-tr.txt` — Bolum 0.A alinti paragrafi 6 odulle netlestirildi + "MY Nakliyat" resmi yazim kurali eklendi
- `files/includes/footer.php` — default short_description "My Nakliyat" -> "MY Nakliyat"
- `files/includes/pipeline/public_layout_context.php` — title_suffix "My Nakliyat" -> "MY Nakliyat"
- `files/css/main.css`, `files/js/translator.js`, `files/js/games.js` — yorum satirlari "MY Nakliyat"
- `files/llms-full-tr.php` — fallback embed marka yazimi
- `files/admin/seo_management.php`, `files/admin/add_seo_fields.php`, `files/admin/welcome_popup.php`, `files/admin/rich_snippets.php`, `files/admin/create_rich_snippets_table.php`, `files/admin/settings.php`, `files/admin/kids_management.php` — admin form default value'lar / placeholder'lar
- `files/scripts/normalize_brand_capitalization.php` — YENI CLI: DB'deki "My Nakliyat" -> "MY Nakliyat" idempotent normalize

### FAZ 3.2 — Kanonik firma aciklamasi (2026-04-25c)
Sektor tanimi "lojistik" yerine 5 birincil hizmetle anildi: **evden eve nakliyat, ofis tasima, esya depolama, parca esya tasima, sehirler arasi nakliyat**. Tek kanonik cumle hem schema description, hem meta description, hem footer, hem llms.txt'te birebir kullanilir.

> "MY Nakliyat ® Evden eve nakliyat, Ofis tasima, Esya Depolama, Parca esya tasima & Sehirler arasi nakliyati saglayan Guvenilir Marka odullu Izmir nakliyat firmasidir."

- `files/includes/seo_runtime/jsonld_encode_and_schema.php` — MovingCompany `description` fallback yeni kanonik cumle (settings bos kalirsa devreye girer)
- `files/includes/footer.php` — default short_description fallback yeni kanonik cumle (eski "Profesyonel nakliyat ve lojistik hizmetleri" silindi)
- `files/sayfa.php` — Hakkimizda AboutPage JSON-LD `description` yeni kanonik cumle + ISO 9001 + 270 yorum
- `files/llms-full-tr.txt` — Bolum 0.A AI alinti yeniden yazildi; Bolum 1'e "Resmi firma aciklamasi (kanonik)" + "Sektor tanimi" eklendi (lojistik tanimi reddedildi)
- `files/llms-full-tr.php` — fallback embed sektor satiri kanonik cumleye guncellendi
- `files/blog.php` — `$page_meta_description` "Nakliyat, tasimacilik ve lojistik" -> 5 birincil hizmet adiyla yazildi
- `files/admin/create_rich_snippets_table.php` — Organization snippet ornek description yeni kanonik cumle
- `files/scripts/update_brand_canonical_description.php` — YENI CLI: settings.short_description / site_description / global_meta_description'i kanonik cumleyle senkronlar (idempotent, dry-run + --apply)

### FAZ 3.3 — A1: Ekip sayfasi (E-E-A-T) (2026-04-26)
- `files/sayfa.php` — `slug=ekibimiz` iken `team_members` tablosundan 6'li ekip karti + `Person` JSON-LD (`worksFor` -> `#mynak-moving-company`); profil yoksa bas harf avatar
- `files/includes/functions.php` — `mynak_initials_from_name()` yardimci
- `files/scripts/fix_team_members_autoinc.php` — bazi XAMPP kurulumlarinda `id` AUTO_INCREMENT yok; bir kez `ALTER` + (yerelde) tablo temizlik
- `files/scripts/seed_team_members.php` — 6 ornek ekip uyesi (idempotent, `ad` bazli)
- **Canlida sira:** once `php scripts/fix_team_members_autoinc.php` (mysqlden id sutunu zaten AI ise sadece seed), sonra `php scripts/seed_team_members.php --apply`, sonra Admin > Ekip'ten gercek isimler/fotolar

### FAZ 3.4 — A2: Musteri hikayeleri 6 kayit
- `files/scripts/seed_case_studies_extra.php` — 3 EK hikaye (Karsiyaka→Ankara, Gaziemir→Avcilar, Bornova eşya depolama). `migrate_case_studies.php` 3 hikayeyi zaten eklettiyse toplam 6 olur (slug bazli idempotent)
- **Canlida:** `php scripts/migrate_case_studies.php` (tablo yoksa) sonra `php scripts/seed_case_studies_extra.php --apply`

### FAZ 3.6 — A5: Ilce / evden-eve cluster tekil acilis
- `files/includes/pipeline/ilce_unique_opening.php` + `files/sayfa.php` (mb_strtolower slug) — 24 adet farkli acilis paragraf, `mynak-local-lede` aside; hizmet / MY Nakliyat sektor tanimiyle uyumlu

### FAZ 3.5 — A4: Mobil alt CTA (4 dugme)
- `files/includes/mobile_sticky_cta_bar.php` + `files/includes/footer.php` — max-width 768px: sabit alt bant (Ara, WhatsApp, Teklif, Yol tarifi / Google Maps); ayni genislikte sag-alt FAB'lar gizlenir, `body` alt padding

### FAZ 3.7 — A3: 10 yuksek-trafik blog (AI ozet + HowTo JSON-LD)
- `files/blog-detay.php` — kanonik slug URL ile `mynak_blog_aio_boost_html()` ciktisi, ana icerikten once
- `files/includes/pipeline/blog_aio_boost.php` — 10 post ID (seo_score yuksek) map: ust bolum (tanim, surec, fayda, 4 adim) + altta HowTo `step`

### FAZ 3.11 — B4: GBP rehber (gönderi planı)
- `files/includes/pipeline/mynak_gbp_post_guide_tr.php` + `rehber-google-isletme-gonderileri.php` + `admin/gbp_gonderi_rehberi.php` — ayni rehber metni (GMB/GBP gonderi plani; tam otomasyon notu)
- `files/admin/includes/sidebar.php` + `sitemap` + `front_controller` + `sayfa` 301 rehber

### B5: VideoObject + 3 YouTube vitrin karti
- `files/includes/mynak_home_videos.php` + `files/includes/seo_runtime/jsonld_encode_and_schema.php` + `files/includes/seo_runtime/pipeline_page_type.php` — `ItemList` + `VideoObject` (yalniz gecerli YouTube ID varken)
- `files/includes/controllers/HomePageController.php` + `files/index.php` — 3 slot (iskelet + lazy embed); `files/admin/settings.php` — `mynak_home_youtube_ids` JSON

### B6: Sosyal kanit — Google 5,0 + 270+ + son 10 yorum
- `files/includes/controllers/HomePageController.php` — `google_reviews` son 10 satir; `g_total` + `g_rating` (settings) alt metinde; `gr_marquee_sec` kayan bant
- `files/index.php` — yatay `gr-marquee` (kayan bant); bolum `id="musteri-yorumlari"`; uzun aciklama metni kaldirildi

### C4: Veritabani yedegi (mysqldump) + rotasyon + uptime notu
- `files/scripts/backup_database.php` — CLI: `.env` (DB_*) veya XAMPP varsayilani; `storage/db_backups/*.sql` (30+ gun oncekileri siler, `--days=` ile degisir)
- `files/storage/db_backups/.htaccess` — yedek SQL HTTP ile okunmasin
- `files/assets/.htaccess` — css/js/gorsel/font icin 7 gun tarayici onbellegi (AllowOverride All + mod_expires/mod_headers gerekir)
- `files/scripts/C4_uptime_rehber_kisa.txt` — UptimeRobot / harici izleme kisasi
- Aylik/haftalik: `php scripts/backup_database.php` (Windows gorev zamanlayicisi veya sunucu cron)

### C5: Performans rehberi (CDN, WebP, Critical CSS, olcum)
- `files/scripts/C5_performans_rehber_kisa.txt` — Cloudflare önbellek notu, görsel, kritik CSS, Lighthouse
- Uygulama: DNS/CDN ayrı panelde; kodda sadece gerektikçe (ör. picture/WebP) müdahale

### C6: Semantik / sema (genişleme yol notu)
- `files/scripts/C6_seo_sema_rehber_kisa.txt` — HowTo, Person, Speakable, Price; `schema_factory` tek kanal
- Icerik ve denetimle uyumlu, parçalı ilerleme

### FAZ 3.10 — B3: Microsoft Clarity (opsiyonel, settings)
- `files/includes/mynak_clarity.php` — `mynak_microsoft_clarity_head_markup($site_settings)`; `clarity_enabled=1` + `clarity_project_id` (3–32 alfanümerik)
- `files/includes/header.php` — preconnect+cdn (cdnjs, fonts, jsdelivr); Clarity; GA/gtag yok (footer.php)
- `files/admin/settings.php` — Admin Site Ayarlari: ac/kapa + proje kimligi

### FAZ 3.9 — B2: Tasinma kontrol listesi (lead magnet)
- `files/includes/pipeline/lead_magnet_tasinma_checklist.php` — kontrol icerik + HTML export
- `files/tasinma-kontrol-listesi.php` — e-posta + KVKK onayi → `form_submissions`, oturum acilir
- `files/tasinma-kontrol-listesi-icerik.php` — noindex, HTML indir / yazdir (PDF: tarayici "PDF'ye kaydet")
- `files/includes/front_controller.php` + `files/includes/sitemap_build.php` + `files/includes/pipeline/public_footer_context.php` — rota, sitemap, footer hizli link

### FAZ 3.8 — B1: 5 sehir cifti landing (DB siz)
- `files/includes/pipeline/city_pair_landings.php` — `izmir-istanbul`, `izmir-ankara`, `izmir-bursa`, `izmir-antalya`, `izmir-mugla` (icerik + meta + ic linkler `mynak_public_path`)
- `files/includes/front_controller_slug.php` — `services` sorgusundan once `mynak_city_pair_landing_data()` → `sayfa.php`
- `files/sayfa.php` — `?slug=` ile dogrudan `sayfa.php` acilinca 301 → kanonik kok slug
- `files/includes/sitemap_build.php` — 5 URL `urlset` + `pages` duplikasyonu `skipSlugs`

### Dokumanlar
- `TALIMAT.md` — bu dosya

---

## Etkilenen 10 slug

`%SLUGS%`

---

## ADIM 1 — Veritabani yedegi (ZORUNLU)

cPanel > phpMyAdmin > **mynakliyat** veritabani > `services` ve `pages` tablolarini secin > **Disa Aktar** (Export) > **Quick** > **SQL** > Go.

Olusan `.sql` dosyasini guvenli bir yerde saklayin (rollback icin).

---

## ADIM 2 — Dosyalari FTP / cPanel File Manager ile yukleyin

Asagidaki dosyalari, uzerine yazarak yukleyin (yol yapisi korunarak):

| Yerel (bu paket) | Sunucu |
|---|---|
| `files/includes/front_controller_slug.php`                  | `/public_html/includes/front_controller_slug.php` |
| `files/includes/breadcrumb_jsonld.php`                      | `/public_html/includes/breadcrumb_jsonld.php` |
| `files/includes/seo_runtime/faq_extractor.php`              | `/public_html/includes/seo_runtime/faq_extractor.php` |
| `files/includes/seo_runtime/jsonld_encode_and_schema.php`   | `/public_html/includes/seo_runtime/jsonld_encode_and_schema.php` |
| `files/admin/dashboard.php`                                 | `/public_html/admin/dashboard.php` |
| `files/admin/service_edit.php`                              | `/public_html/admin/service_edit.php` |
| `files/admin/blog_health_report.php`                        | `/public_html/admin/blog_health_report.php` (YENI) |
| `files/admin/gsc_low_ctr_report.php`                        | `/public_html/admin/gsc_low_ctr_report.php` |
| `files/admin/local_cluster_coverage.php`                    | `/public_html/admin/local_cluster_coverage.php` |
| `files/admin/includes/dashboard_seo_metrics.php`            | `/public_html/admin/includes/dashboard_seo_metrics.php` |
| `files/admin/includes/dashboard_system_health.php`          | `/public_html/admin/includes/dashboard_system_health.php` (YENI) |
| `files/admin/includes/sidebar.php`                          | `/public_html/admin/includes/sidebar.php` |
| `files/scripts/canonical_slug_consolidate.php`              | `/public_html/scripts/canonical_slug_consolidate.php` (YENI) |
| `files/scripts/migrate_service_content_from_pages.php`      | `/public_html/scripts/migrate_service_content_from_pages.php` |
| `files/.htaccess`                                           | `/public_html/.htaccess` |
| `files/assets/.htaccess`                                    | `/public_html/assets/.htaccess` (statik onbellek) |
| `files/includes/whatsapp_float_button.php`                  | `/public_html/includes/whatsapp_float_button.php` (YENI) |
| `files/includes/phone_float_button.php`                     | `/public_html/includes/phone_float_button.php` (YENI) |
| `files/includes/footer.php`                                 | `/public_html/includes/footer.php` |
| `files/admin/form_submissions.php`                          | `/public_html/admin/form_submissions.php` (Madde 3) |
| `files/admin/includes/dashboard_form_pipeline.php`          | `/public_html/admin/includes/dashboard_form_pipeline.php` (YENI - Madde 3) |
| `files/admin/includes/mynak_admin_recommendations.php`      | `/public_html/admin/includes/mynak_admin_recommendations.php` (Dashboard oneri karti) |
| `files/admin/includes/totp_helper.php`                      | `/public_html/admin/includes/totp_helper.php` (YENI - Madde 4) |
| `files/admin/security_2fa.php`                              | `/public_html/admin/security_2fa.php` (YENI - Madde 4) |
| `files/admin/login_2fa.php`                                 | `/public_html/admin/login_2fa.php` (YENI - Madde 4) |
| `files/admin/login.php`                                     | `/public_html/admin/login.php` (Madde 4) |
| `files/admin/includes/require_admin_web.php`                | `/public_html/admin/includes/require_admin_web.php` (Madde 4) |
| `files/musteri-hikayeleri.php`                              | `/public_html/musteri-hikayeleri.php` (YENI - Madde 5) |
| `files/admin/case_studies.php`                              | `/public_html/admin/case_studies.php` (YENI - Madde 5) |
| `files/admin/case_study_edit.php`                           | `/public_html/admin/case_study_edit.php` (YENI - Madde 5) |
| `files/includes/sitemap_build.php`                          | `/public_html/includes/sitemap_build.php` (Madde 5) |
| `files/sayfa.php`                                           | `/public_html/sayfa.php` (FAZ 3 — Hakkimizda trust badges + AboutPage JSON-LD) |
| `files/index.php`                                           | `/public_html/index.php` (FAZ 3 trust strip + B5 video + B6 yorumlar) |
| `files/llms-full-tr.txt`                                    | `/public_html/llms-full-tr.txt` (FAZ 3 — AI alinti ozeti) |
| `files/scripts/seed_gbp_settings.php`                       | `/public_html/scripts/seed_gbp_settings.php` (YENI - FAZ 3) |
| `files/scripts/normalize_brand_capitalization.php`          | `/public_html/scripts/normalize_brand_capitalization.php` (YENI - FAZ 3.1) |
| `files/includes/pipeline/public_layout_context.php`         | `/public_html/includes/pipeline/public_layout_context.php` (FAZ 3.1) |
| `files/blog-detay.php`                                     | `/public_html/blog-detay.php` (A3 — blog AI ozet + HowTo) |
| `files/includes/pipeline/blog_aio_boost.php`                | `/public_html/includes/pipeline/blog_aio_boost.php` (A3) |
| `files/includes/pipeline/city_pair_landings.php`            | `/public_html/includes/pipeline/city_pair_landings.php` (B1) |
| `files/tasinma-kontrol-listesi.php`                          | `/public_html/tasinma-kontrol-listesi.php` (B2) |
| `files/tasinma-kontrol-listesi-icerik.php`                    | `/public_html/tasinma-kontrol-listesi-icerik.php` (B2) |
| `files/includes/pipeline/lead_magnet_tasinma_checklist.php` | `/public_html/includes/pipeline/lead_magnet_tasinma_checklist.php` (B2) |
| `files/includes/front_controller.php`                        | `/public_html/includes/front_controller.php` (B2 rotalari) |
| `files/includes/pipeline/public_footer_context.php`          | `/public_html/includes/pipeline/public_footer_context.php` (B2 footer link) |
| `files/css/main.css`                                        | `/public_html/css/main.css` (FAZ 3.1) |
| `files/js/translator.js`                                    | `/public_html/js/translator.js` (FAZ 3.1) |
| `files/js/games.js`                                         | `/public_html/js/games.js` (FAZ 3.1) |
| `files/llms-full-tr.php`                                    | `/public_html/llms-full-tr.php` (FAZ 3.1) |
| `files/admin/seo_management.php`                            | `/public_html/admin/seo_management.php` (FAZ 3.1) |
| `files/admin/add_seo_fields.php`                            | `/public_html/admin/add_seo_fields.php` (FAZ 3.1) |
| `files/admin/welcome_popup.php`                             | `/public_html/admin/welcome_popup.php` (FAZ 3.1) |
| `files/admin/rich_snippets.php`                             | `/public_html/admin/rich_snippets.php` (FAZ 3.1) |
| `files/admin/create_rich_snippets_table.php`                | `/public_html/admin/create_rich_snippets_table.php` (FAZ 3.1) |
| `files/admin/settings.php`                                  | `/public_html/admin/settings.php` (FAZ 3.1 + B3 + B5 videolar) |
| `files/includes/mynak_clarity.php`                          | `/public_html/includes/mynak_clarity.php` (B3) |
| `files/includes/header.php`                                 | `/public_html/includes/header.php` (B3) |
| `files/rehber-google-isletme-gonderileri.php`               | `/public_html/rehber-google-isletme-gonderileri.php` (B4) |
| `files/includes/mynak_home_videos.php`                       | `/public_html/includes/mynak_home_videos.php` (B5) |
| `files/includes/seo_runtime/jsonld_encode_and_schema.php`   | `/public_html/includes/seo_runtime/jsonld_encode_and_schema.php` (B5) |
| `files/includes/seo_runtime/pipeline_page_type.php`           | `/public_html/includes/seo_runtime/pipeline_page_type.php` (B5) |
| `files/includes/pipeline/mynak_gbp_post_guide_tr.php`      | `/public_html/includes/pipeline/mynak_gbp_post_guide_tr.php` (B4) |
| `files/includes/controllers/HomePageController.php`         | `/public_html/includes/controllers/HomePageController.php` (B4) |
| `files/admin/gbp_gonderi_rehberi.php`                        | `/public_html/admin/gbp_gonderi_rehberi.php` (B4) |
| `files/admin/includes/sidebar.php`                          | `/public_html/admin/includes/sidebar.php` (B4) |
| `files/admin/kids_management.php`                           | `/public_html/admin/kids_management.php` (FAZ 3.1) |

> Not: Sunucunuzda "public_html" degil "www" veya "httpdocs" kullaniliyor olabilir — hosting panelinize gore hedef klasor adini degistirin.
>
> **DIKKAT (.htaccess):** Mevcut canli .htaccess'te bu projede olmayan ek kurallariniz varsa once mevcut dosyayi indirin (`/public_html/.htaccess.YEDEK` olarak kopyalayin), karsilastirin. Paketteki .htaccess asagidaki yeni kurallari icerir:
> - 4 adet 301 redirect (duplicate slug): `sehirler-arasi-nakliyat`, `antika-ve-piyano-tasima`, `izmir-evden-eve-nakliyat-hizmeti`, `kurumsal-nakliye-ofis-tasima`
> - `teklif-al` redirect'i context-aware (localhost / canli)

---

## ADIM 3 — SQL'i calistir (phpMyAdmin)

1. cPanel > phpMyAdmin
2. Soldan `mynakliyat` veritabanini sec
3. Ust menuden **SQL** sekmesi
4. `CANLI_MIGRATION.sql` dosyasini **Aciniz** -> tum icerigi kopyalayin -> SQL kutusuna yapistirin
5. **Git** (Go) butonu

Beklenen sonuc:
- 1 satir `ALTER TABLE` mesaji (veya "kolon zaten var" hatasi — ignore edin)
- 10 satir `UPDATE services` (etkilenen 10 kayit)
- 1 satir `UPDATE pages` (10 kayit arsivlendi)
- Dogrulama sorgusu 10 slug listeler, `icerik_len` hepsinde >500
- 4 satir `ALTER TABLE admin_users ADD COLUMN ...` (totp_secret, totp_enabled, totp_verified_at, totp_backup_codes)
- 1 satir `CREATE TABLE case_studies` (Madde 5)
- 3 satir `INSERT IGNORE INTO case_studies` (sample veriler — istemiyorsaniz yorum yapin)

**Eger `ALTER TABLE ... IF NOT EXISTS` hatasi alirsaniz** (eski MySQL/MariaDB):
- Once yalniz bu komutu calistirin: `ALTER TABLE services ADD COLUMN icerik MEDIUMTEXT NULL AFTER aciklama;`
- Sonra `CANLI_MIGRATION.sql`'daki ADIM 1 satirini yorum yapip (ilk basina `--`), tum dosyayi yeniden calistirin.

---

## ADIM 3.5 — Duplicate Slug Consolidation (Madde 6-7, CLI)

Bu adim 4 duplicate URL'i kapatir + 61+ iç linki canonical'a guncelller. Idempotent (tekrar calistirilirsa degisen sey "already inactive, skip" olur).

### Ana hedef
| Eski slug | Yeni canonical |
|---|---|
| `/sehirler-arasi-nakliyat`          | `/sehirlerarasi-nakliyat` |
| `/antika-ve-piyano-tasima`          | `/antika-piyano-tasimaciligi` |
| `/izmir-evden-eve-nakliyat-hizmeti` | `/izmir-evden-eve-nakliyat` |
| `/kurumsal-nakliye-ofis-tasima`     | `/kurumsal-nakliye-hizmetleri` |

### CLI (cPanel Terminal / SSH)
```bash
cd /home/XXXXXX/public_html
php scripts/canonical_slug_consolidate.php --dry-run    # once raporla
php scripts/canonical_slug_consolidate.php --apply      # uygula
```

Beklenen cikti:
- 2 pages + 2 services kaydi arşivlenir (status=0); icerik DB'de yedek kalir
- 49+ blog yazisinda ic link canonical slug'a guncellenir
- Rollback SQL: `logs/canonical_slug_rollback_YYYYMMDD_HHMMSS.sql` otomatik uretilir

### Alternatif: cPanel yoksa / CLI acilmiyor ise

phpMyAdmin uzerinden **manuel**:
```sql
UPDATE pages SET status = 0 WHERE slug IN ('sehirler-arasi-nakliyat','antika-ve-piyano-tasima') AND status = 1;
UPDATE services SET status = 0 WHERE slug IN ('izmir-evden-eve-nakliyat-hizmeti','kurumsal-nakliye-ofis-tasima') AND status = 1;
```
Ic link guncellemesi manuel olmaz, CLI ile yapmak zorunludur; ancak `.htaccess` 301'ler yuklendigi anda kullanici trafigi zaten canonical'a gider. Ic linkler "eventually consistent" — SEO icin en az 1-2 hafta icinde CLI ile duzeltilmesi onerilir.

---

## ADIM 4 — Dogrulama

Tarayiciniz (Chrome/Edge) gizli sekmede acin (cache atlatmak icin):

| URL | Beklenen |
|---|---|
| https://www.mynakliyat.com.tr/ | Ana sayfa kartlari AYNI, kisa ozetler |
| https://www.mynakliyat.com.tr/parca-esya-tasima | Uzun detay icerik (~90 KB HTML) |
| https://www.mynakliyat.com.tr/asansorlu-nakliyat | Uzun detay icerik |
| https://www.mynakliyat.com.tr/esya-depolama | Uzun detay icerik |
| https://www.mynakliyat.com.tr/sehir-ici-nakliyat | Uzun detay icerik |

Tum 10 slug icin testi tekrarlayin.

### Duplicate slug 301 testi (terminal)
```bash
curl -sI https://www.mynakliyat.com.tr/sehirler-arasi-nakliyat          | grep -i "^location\|^HTTP"
curl -sI https://www.mynakliyat.com.tr/antika-ve-piyano-tasima          | grep -i "^location\|^HTTP"
curl -sI https://www.mynakliyat.com.tr/izmir-evden-eve-nakliyat-hizmeti | grep -i "^location\|^HTTP"
curl -sI https://www.mynakliyat.com.tr/kurumsal-nakliye-ofis-tasima     | grep -i "^location\|^HTTP"
```
Her dordu `HTTP/1.1 301` + `Location: https://www.mynakliyat.com.tr/<canonical>` dondurmeli.

**Admin panel:**
- `/admin/service_edit.php?id=X` -> "Detay Sayfa Icerigi" buyuk textarea gorunmeli, icinde HTML icerik olmali.
- `/admin/dashboard.php` -> SEO Skor Paneli + Form Takip Panosu + Sistem Sagligi (OPcache / Cache / Disk) kartlari gorulmeli.
- `/admin/blog_health_report.php` -> Aktif blog yazilari saglık skoru tablosu acilmali (salt-okuma).
- `/admin/form_submissions.php` -> 6-asamali pipeline (Yeni/Arandi/Teklif/Kazanildi/Kaybedildi/Spam) + KPI kartlari + hizli durum dropdown gorulmeli.
- `/admin/security_2fa.php` -> 2FA setup wizard (Madde 4). Asama 1: "Kuruluma Basla" -> QR + manuel anahtar -> 6 haneli dogrulama -> backup kodlar. Bir admin icin aktif edip test edin.
- `/admin/case_studies.php` -> Musteri Hikayeleri listesi (Madde 5). 3 sample kayit gorulmeli. "Yeni Hikaye" calismali.

**Public site (Madde 5):**
- `https://www.mynakliyat.com.tr/musteri-hikayeleri` -> listing sayfasi (3 kart + ItemList JSON-LD)
- `https://www.mynakliyat.com.tr/musteri-hikayeleri/<slug>` -> detay sayfasi (Review JSON-LD + musteri yorumu blogu)
- `https://www.mynakliyat.com.tr/sitemap.xml` -> /musteri-hikayeleri ve detay URL'leri eklenmis olmali.

### FAZ 3 — AI / E-E-A-T zenginlestirme dogrulamasi
Anasayfa: Slider'in hemen altinda **sari "Trust Strip" bandi** gorulmeli:
- "Guvenilir Marka Odullu Nakliye Firmasi" + "5,0 Google · 270+ Yorum" + "Sigortali Tasima + Yazili Sozlesme" + "Izmir + 81 Il Hizmet Agi"

Hakkimizda (`/hakkimizda`): Sayfa metninin altinda **"Neden MY Nakliyat?" 6'li trust kart** gorulmeli + sag ust kosede "Google'da 5,0 ★ — 270+ yorum" rozet pill.

Schema dogrulama (terminal):
```bash
curl -s https://www.mynakliyat.com.tr/ | grep -oE 'aggregateRating[^,}]+' | head -1
# Beklenen: "aggregateRating":{"@type":"AggregateRating","ratingValue":"5.0","reviewCount":270,...}

curl -s https://www.mynakliyat.com.tr/ | grep -oE '"award":\[[^]]+\]'
# Beklenen: ["Guvenilir Marka Odulu","Izmir Musteri Memnuniyeti Birinciligi"]

curl -s https://www.mynakliyat.com.tr/ | grep -oE 'mynak-trust-strip-inner' | wc -l
# Beklenen: 1 (en az)

curl -s https://www.mynakliyat.com.tr/hakkimizda | grep -oE '"@type":"AboutPage"' | wc -l
# Beklenen: 1
```

Google Rich Results Test: https://search.google.com/test/rich-results?url=https%3A%2F%2Fwww.mynakliyat.com.tr%2F
- "MovingCompany" -> aggregateRating + award + brand + foundingDate alanlari gozukmeli.

### FAZ 3.1 — Marka yazimi + gercek odul listesi dogrulamasi
```bash
# Hicbir sayfada "My Nakliyat" yanlis yazimi kalmamali (yalniz "MY Nakliyat" gormeli)
curl -s https://www.mynakliyat.com.tr/ | grep -c '\bMy Nakliyat\b'
# Beklenen: 0

curl -s https://www.mynakliyat.com.tr/hakkimizda | grep -c '\bMy Nakliyat\b'
# Beklenen: 0

# Schema'da 6 GERCEK odul olmali (ISO 9001 dahil)
curl -s https://www.mynakliyat.com.tr/ | grep -oE '"award":\[[^]]+\]'
# Beklenen: 6 odul, "ISO 9001" + "Sehirler Arasi" + "Kurumsal" + "Guvenilir Marka" + "Altin Marka" + "Lider Tasimacilik" iceren liste
```

Hakkimizda sayfasinda 9 trust badge gorulmeli (eski 6 + 3 yeni: ISO 9001, Sehirler Arasi Nakliye, Altin Marka).

**2FA test (DIKKAT — tek admin hesapsa):**
- Tek admin hesabi varsa, Authy / Google Authenticator yedeginiz hazir olmadan 2FA aktif etmeyin.
- Aktif ettikten sonra **mutlaka backup kodlari kaydedin** (yeniden gosterilmez).
- Telefon kaybolursa: SQL ile `UPDATE admin_users SET totp_enabled=0, totp_secret=NULL, totp_backup_codes=NULL WHERE username='X';` calistirilarak 2FA devre disi birakilabilir.

---

## ADIM 4.4 — FAZ 3.1: Marka yazim tutarliligi (ZORUNLU — bir kez)

Tum sitedeki "My Nakliyat" yazimi resmi yazima ("MY Nakliyat") cevrilir.
Schema'daki `award` listesi de Hakkimizda > "Kalite Belgeleri ve Odüller" ile birebir senkrondur:

- 2024 — ISO 9001 Belgeli İlk Nakliye Firması
- 2023 — En İyi Şehirler Arası Nakliyat Firması Ödülü
- 2022 — En Çok Tercih Edilen Kurumsal Nakliyat Firması
- 2018, 2020, 2022 — Güvenilir Marka Ödülleri
- 2016 — Türkiye Altın Marka Ödülü
- 2014 — Yılın Lider Taşımacılık Markası

DB'deki settings/sayfa/blog/service metinlerinde "My Nakliyat" gecen yerleri tek seferde duzelt:

```bash
cd /home/XXXXXX/public_html
php scripts/normalize_brand_capitalization.php           # dry-run (preview)
php scripts/normalize_brand_capitalization.php --apply   # uygula
```

Beklenen cikti (ornek):
- `settings.value -> 3 satir etkilenir`  (site_title, copyright_text, vs.)
- `pages.content -> 12 satir etkilenir`
- `blog_posts.icerik -> 25 satir etkilenir`
- `services.icerik -> 8 satir etkilenir`
- `Toplam guncellenen satir: 50` (rakam degisken)

> Idempotent: REPLACE ile sadece tam "My Nakliyat" -> "MY Nakliyat" yapar; "MY Nakliyat" zaten dogruysa dokunmaz. Tekrar tekrar calistirilabilir.

---

## ADIM 4.4.B — FAZ 3.2: Kanonik firma aciklamasi (ZORUNLU — ilk deploy'da bir kez)

Settings tablosundaki firma aciklamalarini tek kanonik cumleye senkronlar. "Lojistik" tanimini kaldirir, "Esya Depolama"yi ekler ve "Guvenilir Marka odullu" ifadesini standartlastirir.

```bash
cd /home/XXXXXX/public_html
php scripts/update_brand_canonical_description.php           # dry-run (preview)
php scripts/update_brand_canonical_description.php --apply   # uygula
```

Etkilenen settings anahtarlari:
- `short_description` — schema description ve footer fallback'ta kullanilir
- `site_description` — global meta description fallback
- `global_meta_description` — `<meta name="description">` tag'i

Beklenen cikti:
- `short_description -> 1 satir etkilenir`
- `site_description -> INSERT (yoksa olusturulur)`
- `global_meta_description -> 1 satir etkilenir`
- `cache/public_layout_settings_menus.json silindi (yeniden uretilecek)`

> Idempotent: zaten kanonik cumleyle eslesirse "Zaten ayni, atlandi" yazar; tekrar calistirilabilir.

Dogrulama:
```bash
curl -s https://www.mynakliyat.com.tr/ | grep -o "Esya Depolama, Parca esya tasima.\{0,40\}Guvenilir Marka odullu" | head -1
# beklenti: en az 1 satir match (schema + meta + footer'da gecer)
```

---

## ADIM 4.5 — FAZ 3: GBP rating settings seed (ZORUNLU — ilk deploy'da bir kez)

Eger Places API entegrasyonunuz YOKSA, AggregateRating schema'nin canliya basabilmesi icin
settings tablosuna GBP rating degerlerini bir kez yazmaniz gerekir. Iki yontem:

**Yontem A — CLI (onerilen, idempotent):**
```bash
cd /home/XXXXXX/public_html
php scripts/seed_gbp_settings.php                  # 5.0 + 270 default
php scripts/seed_gbp_settings.php --rating=5.0 --reviews=287   # ozel deger
```

Beklenen cikti:
- `google_place_rating: (yok) -> 5.0`
- `google_total_reviews: (yok) -> 270`
- `cache/gbp_data.json yazildi.`

**Yontem B — phpMyAdmin (CLI yoksa):**
```sql
INSERT INTO settings (name, value, description) VALUES
  ('google_place_rating', '5.0', 'GBP yildiz ortalamasi'),
  ('google_total_reviews', '270', 'GBP toplam yorum sayisi')
ON DUPLICATE KEY UPDATE value = VALUES(value);
```

> **NOT:** Eger Places API key'iniz `settings` tablosunda tanimliysa (`google_places_api_key` + `google_place_id`), sistem zaten 24 saatte bir Google'dan otomatik tazeler. Bu adim sadece "API yoksa fallback" senaryosu icindir. API varsa adim atlanabilir; gerçek değer otomatik gelir.

---

## ADIM 5 — Cache temizligi (varsa)

Eger sunucunuzda Cloudflare / LiteSpeed / Redis / opcache varsa:
- **OPcache reset**: cPanel > PHP Selector > OPcache Reset (yoksa, servisi yeniden baslatin)
- **Cloudflare**: Cache > Purge Everything (hizli duzeltme)
- **LiteSpeed Cache**: Toolbox > Purge All

Bu siteye ozel ic cache dosyalarini da sifirlayin:
- `/public_html/cache/public_layout_settings_menus.json` (dosyayi silerseniz yeniden uretilir)

---

## ROLLBACK (bir sey ters giderse)

### Veritabani rollback

phpMyAdmin > SQL:

```sql
-- 10 slug icin icerik'i temizle, pages'i geri ac
UPDATE services SET icerik = NULL WHERE slug IN (%SLUGS_QUOTED%);
UPDATE pages SET status = 1 WHERE slug IN (%SLUGS_QUOTED%) AND status = 0;
```

Veya ADIM 1'de aldiginiz yedek .sql dosyasini import edin.

### Dosya rollback

Eski `front_controller_slug.php` ve `service_edit.php` dosyalarini geri yukleyin (ADIM 1 yedeginden).

---

## Ozet — Ne degisecek, ne degismeyecek

| Degisen | Degismeyen |
|---|---|
| Detay hizmet sayfalari zengin icerik basar | Ana sayfa kart metinleri aynen kalir |
| 10 pages kaydi arsive duser (status=0) | Menu yapisi, CSS, tasarim hic degismez |
| Admin hizmet duzenleme ekraninda iki textarea olur | Blog, iletisim, diger sayfalar etkilenmez |
| `services.icerik` yeni kolon dolu | `services.aciklama` aynen kalir |

---

## Sorularda yardim

Herhangi bir adimda hata alirsaniz hata metnini bana yapistirin, tam dogru komutu verecegim.
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
