<?php
/**
 * Otomatik Blog — önizleme kaydı (CE Faz 1: QC gate, asla doğrudan yayın yok).
 */
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

ini_set('display_startup_errors', 1);
header('Content-Type: application/json; charset=utf-8');

include '../includes/init.php';
require_once __DIR__ . '/../includes/auto_blog_functions.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/mynak_local_seo_content_engine.php';
require_once __DIR__ . '/../../includes/mynak_ce_production.php';
require_once __DIR__ . '/../../includes/auto_blog_ce_adapter.php';

function publish_log($msg): void
{
    $logDir = realpath(__DIR__ . '/../../logs');
    if ($logDir === false) {
        @mkdir(__DIR__ . '/../../logs', 0777, true);
        $logDir = realpath(__DIR__ . '/../../logs');
    }
    $logFile = ($logDir ?: __DIR__) . '/auto_blog_publish.log';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . print_r($msg, true) . "\n", FILE_APPEND);
}

try {
    if (!isset($_POST['action']) || $_POST['action'] !== 'publish') {
        throw new Exception('Geçersiz istek');
    }

    foreach (['baslik', 'icerik', 'slug', 'kategori_id'] as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Eksik alan: $field");
        }
    }

    $baslik = (string) $_POST['baslik'];
    $icerik = icerik_donustur((string) $_POST['icerik']);
    $slug = ab_ce_unique_slug($conn, ab_ce_finalize_slug((string) $_POST['slug']));
    $kategori_id = (int) $_POST['kategori_id'];
    $etiketler = (string) ($_POST['etiketler'] ?? '');
    $kapak_foto = (string) ($_POST['kapak_foto'] ?? '');
    $setting_id = (int) ($_POST['setting_id'] ?? 0);

    $qcPass = isset($_POST['qc_pass']) && ($_POST['qc_pass'] === '1' || $_POST['qc_pass'] === 'true' || $_POST['qc_pass'] === true);
    $durum = $qcPass ? MYNAK_BLOG_STATUS_EDITOR_QUEUE : MYNAK_BLOG_STATUS_REVISION;

    publish_log(['publish_started' => compact('baslik', 'slug', 'kategori_id', 'setting_id', 'qcPass', 'durum')]);

    $post = [
        'id' => 0,
        'baslik' => $baslik,
        'slug' => $slug,
        'icerik' => $icerik,
        'etiketler' => $etiketler,
    ];
    $linkCatalog = br_internal_link_catalog($conn, $post);
    $pack = mynak_ce_build_production_pack($post, $conn);
    $qc = mynak_ce_quality_report($post, $icerik, $pack, $linkCatalog, $baslik, '');
    if (!($qc['pass'] ?? false)) {
        $durum = MYNAK_BLOG_STATUS_REVISION;
        $qcPass = false;
    }

    $pack['_qc_pass'] = $qcPass;
    $pack['_qc_queue'] = (string) ($qc['queue'] ?? '');
    $pack['_qc_status'] = (string) ($qc['qc_status'] ?? mynak_ce_qc_gate_status($qc));
    $scores = mynak_ce_compute_score_bundle($icerik, $post, $pack, $qc);
    $quality = (int) ($scores['structure_score'] ?? mynak_ce_structure_score($icerik));
    $ceMeta = mynak_ce_production_metadata_json($post, $pack, '', 0.0);
    $ceMeta = mynak_ce_merge_scores_into_meta($ceMeta, $scores, $pack, $qc);
    $ceMeta = mynak_ce_merge_fingerprint_into_meta($ceMeta, [
        'source' => 'preview_publish',
        'qc_pass_at_publish' => $qcPass,
    ]);
    $editorNotes = '[Otomatik Blog önizleme kaydı ' . date('Y-m-d H:i') . "]\n"
        . (string) ($qc['editor_note'] ?? '')
        . ($qcPass ? '' : "\n[QC_FAIL] Önizleme yayın yolu — revize gerekli.")
        . "\n[CE_META]" . $ceMeta;

    $metaDesc = mb_substr(preg_replace('/\s+/u', ' ', strip_tags($icerik)) ?? '', 0, 160);
    $authorId = auto_blog_pick_author_id($kategori_id);

    $insert = ab_ce_insert_post(
        $conn,
        $baslik,
        $icerik,
        $slug,
        $kategori_id,
        $etiketler,
        $kapak_foto,
        $durum,
        $authorId,
        $quality,
        $metaDesc,
        $editorNotes,
        $ceMeta
    );

    if (!($insert['ok'] ?? false)) {
        throw new Exception('Veritabanı hatası: ' . ($insert['error'] ?? 'kayıt başarısız'));
    }

    $blog_id = (int) ($insert['blog_id'] ?? 0);
    publish_log(['blog_saved' => $blog_id, 'durum' => $durum, 'qc_pass' => $qcPass]);

    $msg = $qcPass
        ? 'Yazı Editör Kuyruğuna alındı (QC PASS). Yayın için editör onayı şart.'
        : 'Yazı revize kuyruğuna alındı (QC FAIL). Otomatik yayın yapılmadı.';

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'blog_id' => $blog_id,
        'slug' => $slug,
        'durum' => $durum,
        'qc_pass' => $qcPass,
        'in_review' => $durum === MYNAK_BLOG_STATUS_EDITOR_QUEUE,
        'needs_revision' => $durum === MYNAK_BLOG_STATUS_REVISION,
        'review_url' => '/admin/blog_review.php?focus=' . $blog_id . ($qcPass ? '' : '&status=2'),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    publish_log(['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    publish_log(['fatal_error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Kritik hata: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
