<?php
/**
 * Dashboard widget: Form Pipeline ozeti
 * --------------------------------------------------------------
 * Salt-okunur, hizli (tek SQL). form_submissions tablosundan 6-asamali
 * pipeline metrikleri ve son 7 gun trend datasini cekerek admin
 * dashboard'unda kart olarak gosterir.
 *
 * Kullanim: admin/dashboard.php icinde
 *   require_once __DIR__ . '/includes/dashboard_form_pipeline.php';
 *   $mynakFormPipeline = mynak_dashboard_form_pipeline_collect($conn);
 *
 * Cikti formati:
 *   [
 *     'total' => int,
 *     'today' => int,
 *     'd7'    => int,
 *     'open'  => int (status 0+1+2),
 *     'won'   => int (3),
 *     'lost'  => int (4),
 *     'spam'  => int (5),
 *     'conv_rate' => float,
 *     'by_status' => [statusKey => count, ...],
 *     'recent7' => [['date' => 'YYYY-MM-DD', 'count' => int], ...]  (son 7 gun)
 *   ]
 */

declare(strict_types=1);

if (!function_exists('mynak_dashboard_form_pipeline_collect')) {

    function mynak_dashboard_form_pipeline_collect(mysqli $conn): array
    {
        $result = [
            'total' => 0,
            'today' => 0,
            'd7'    => 0,
            'open'  => 0,
            'won'   => 0,
            'lost'  => 0,
            'spam'  => 0,
            'conv_rate' => 0.0,
            'by_status' => [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'recent7' => [],
            'error' => null,
        ];

        try {
            $r = $conn->query("
                SELECT status, COUNT(*) c,
                  SUM(CASE WHEN DATE(COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date)) = CURDATE() THEN 1 ELSE 0 END) AS today_c,
                  SUM(CASE WHEN COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date) >= (NOW() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS d7_c
                FROM form_submissions
                GROUP BY status
            ");
            if ($r) {
                while ($row = $r->fetch_assoc()) {
                    $st = (int) $row['status'];
                    $c = (int) $row['c'];
                    $result['total'] += $c;
                    $result['today'] += (int) $row['today_c'];
                    $result['d7']    += (int) $row['d7_c'];
                    if (isset($result['by_status'][$st])) {
                        $result['by_status'][$st] = $c;
                    } else {
                        $result['by_status'][0] += $c; // bilinmeyen status -> Yeni
                    }
                }
            }
            $result['open'] = $result['by_status'][0] + $result['by_status'][1] + $result['by_status'][2];
            $result['won']  = $result['by_status'][3];
            $result['lost'] = $result['by_status'][4];
            $result['spam'] = $result['by_status'][5];
            if (($result['won'] + $result['lost']) > 0) {
                $result['conv_rate'] = round(($result['won'] / ($result['won'] + $result['lost'])) * 100, 1);
            }

            // Son 7 gunun gunluk dagilimi (sparkline icin)
            $r2 = $conn->query("
                SELECT DATE(COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date)) d, COUNT(*) c
                FROM form_submissions
                WHERE COALESCE(NULLIF(created_at,'0000-00-00 00:00:00'), submission_date) >= (NOW() - INTERVAL 7 DAY)
                GROUP BY d
                ORDER BY d ASC
            ");
            $byDate = [];
            if ($r2) {
                while ($row = $r2->fetch_assoc()) {
                    $byDate[(string) $row['d']] = (int) $row['c'];
                }
            }
            // 7 gunu doldur (eksik gunler 0)
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $result['recent7'][] = ['date' => $d, 'count' => $byDate[$d] ?? 0];
            }
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}

if (!function_exists('mynak_dashboard_form_pipeline_status_meta')) {
    /** form_submissions.php ile ortak status meta */
    function mynak_dashboard_form_pipeline_status_meta(): array
    {
        return [
            0 => ['label' => 'Yeni',           'badge' => 'bg-secondary', 'icon' => 'bx-time',         'color' => '#6c757d'],
            1 => ['label' => 'Arandı',         'badge' => 'bg-warning',   'icon' => 'bx-phone-call',   'color' => '#ffc107'],
            2 => ['label' => 'Teklif Verildi', 'badge' => 'bg-info',      'icon' => 'bx-file',         'color' => '#0dcaf0'],
            3 => ['label' => 'Kazanıldı',      'badge' => 'bg-success',   'icon' => 'bx-check-circle', 'color' => '#198754'],
            4 => ['label' => 'Kaybedildi',     'badge' => 'bg-danger',    'icon' => 'bx-x-circle',     'color' => '#dc3545'],
            5 => ['label' => 'Spam/Geçersiz',  'badge' => 'bg-dark',      'icon' => 'bx-block',        'color' => '#212529'],
        ];
    }
}
