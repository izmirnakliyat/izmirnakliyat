<?php
/**
 * Yönetim paneli: ölçülebilir “yapılabilecek” maddeler (iç tespit).
 * Dış hizmetler (GSC kapsamı, PageSpeed skoru) buraya otomatik gelmez; GSC Düşük CTR’da CSV kullanın.
 */
declare(strict_types=1);

if (!function_exists('mynak_admin_recommendations_collect')) {
    /**
     * @param array<string, mixed>|null $mynakSeoMetrics  mynak_dashboard_seo_metrics_get çıktısı
     * @param array<string, mixed>|null $mynakFormPipeline
     * @param array<string, mixed>|null $mynakSysHealth
     * @return list<array{level:string,title:string,detail:string,action_href:?string,action_label:?string}>
     */
    function mynak_admin_recommendations_collect(
        mysqli $conn,
        ?array $mynakSeoMetrics = null,
        ?array $mynakFormPipeline = null,
        ?array $mynakSysHealth = null
    ): array {
        $items = [];

        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : realpath(__DIR__ . '/../..');
        if (!is_string($root) || $root === '') {
            $root = dirname(__DIR__, 2);
        }
        $root = rtrim($root, DIRECTORY_SEPARATOR);

        $site = [];
        $st = $conn->query('SELECT name, value FROM settings');
        if ($st) {
            while ($row = $st->fetch_assoc()) {
                $site[(string) $row['name']] = (string) $row['value'];
            }
            $st->free();
        }
        if (!function_exists('mynak_apply_contact_defaults_to_settings_array')) {
            $fn = dirname(__DIR__, 2) . '/includes/functions.php';
            if (is_readable($fn)) {
                require_once $fn;
            }
        }
        if (function_exists('mynak_apply_contact_defaults_to_settings_array')) {
            $site = mynak_apply_contact_defaults_to_settings_array($site);
        }

        $t = static function (string $s): string {
            $s = trim($s);
            return preg_replace('/\s+/u', ' ', $s) ?? $s;
        };

        if ($t($site['phone1'] ?? '') === '') {
            $items[] = [
                'level' => 'warning',
                'title' => 'Telefon (NAP) ayarı eksik',
                'detail' => 'Şema ve üst bölüm CTA’ları telefonu kullanır. Site ayarlarından doldurun.',
                'action_href' => 'settings.php',
                'action_label' => 'Site ayarları',
            ];
        }
        if ($t($site['email'] ?? '') === '') {
            $items[] = [
                'level' => 'warning',
                'title' => 'E-posta alanı boş',
                'detail' => 'İletişim ve şema için e-posta önerilir.',
                'action_href' => 'settings.php',
                'action_label' => 'Site ayarları',
            ];
        }
        if ($t($site['short_description'] ?? '') === '' || mb_strlen($t($site['short_description'] ?? '')) < 40) {
            $items[] = [
                'level' => 'info',
                'title' => 'Kısa firma açıklaması kısa veya boş',
                'detail' => 'Marka/şema açıklaması için 1–2 cümle doldurun (ideal 120–200 karakter civarı).',
                'action_href' => 'settings.php',
                'action_label' => 'Site ayarları',
            ];
        }
        if (!empty($site['clarity_enabled']) && (string) $site['clarity_enabled'] === '1') {
            $cid = $t($site['clarity_project_id'] ?? '');
            if ($cid === '' || !preg_match('/^[a-z0-9]{3,32}$/i', $cid)) {
                $items[] = [
                    'level' => 'warning',
                    'title' => 'Clarity açık ama proje kimliği geçersiz',
                    'detail' => 'Aç/kapa veya geçerli Clarity proje ID girin (3–32 alfanümerik).',
                    'action_href' => 'settings.php',
                    'action_label' => 'Site ayarları',
                ];
            }
        }

        if (is_array($mynakSeoMetrics) && $mynakSeoMetrics !== []) {
            $groups = [
                'services' => ['label' => 'Hizmetler', 'list' => 'services.php'],
                'pages' => ['label' => 'Sayfalar', 'list' => 'pages.php'],
                'blog_posts' => ['label' => 'Blog', 'list' => 'blog_posts.php'],
            ];
            foreach ($groups as $key => $g) {
                $b = $mynakSeoMetrics[$key] ?? null;
                if (!is_array($b) || (int) ($b['total'] ?? 0) < 1) {
                    continue;
                }
                $total = (int) $b['total'];
                $tOk = (int) ($b['title']['ok'] ?? 0);
                $mOk = (int) ($b['meta']['ok'] ?? 0);
                $tPct = (int) round($tOk / $total * 100);
                $mPct = (int) round($mOk / $total * 100);
                if ($tPct < 70 || $mPct < 70) {
                    $items[] = [
                        'level' => $tPct < 50 || $mPct < 50 ? 'warning' : 'info',
                        'title' => $g['label'] . ' SEO alanları iyileştirilebilir',
                        'detail' => "Title ideal: %{$tPct}, meta ideal: %{$mPct} (hedef: ≥%70). Eksik/kısa/uzun kayıtları listeden düzeltin.",
                        'action_href' => $g['list'],
                        'action_label' => 'Listele & düzenle',
                    ];
                }
            }
            $lc = $mynakSeoMetrics['local_cluster'] ?? null;
            if (is_array($lc) && (int) ($lc['missing'] ?? 0) > 0) {
                $items[] = [
                    'level' => 'info',
                    'title' => 'İlçe × hizmet kopyasında boşluk var',
                    'detail' => 'Eksik: ' . (int) $lc['missing'] . ' (açıklama sayfaları veya hizmet eşleşmesi).',
                    'action_href' => 'local_cluster_coverage.php',
                    'action_label' => 'İlçe kapsama',
                ];
            }
            $sm = $mynakSeoMetrics['sitemap'] ?? null;
            if (is_array($sm) && (empty($sm['main']) || empty($sm['index']))) {
                $items[] = [
                    'level' => 'warning',
                    'title' => 'Sitemap dosyası bulunamadı veya eski',
                    'detail' => 'sitemap.xml / sitemap-index.xml kökte okunamıyor. Üretin ve canlıya yükleyin.',
                    'action_href' => '../generate_full_sitemap.php',
                    'action_label' => 'Sitemap oluştur (site kökü)',
                ];
            }
            $ap = $mynakSeoMetrics['anchor_pool'] ?? null;
            if (is_array($ap) && (int) ($ap['active'] ?? 0) < 3 && (int) ($ap['total_anchors'] ?? 0) < 3) {
                $items[] = [
                    'level' => 'info',
                    'title' => 'İç link havuzu sınırlı',
                    'detail' => 'İç link önerileri eklerseniz otorite dağıtımı güçlenir.',
                    'action_href' => 'internal_link_anchors.php',
                    'action_label' => 'İç link çapaları',
                ];
            }
        }

        if (is_array($mynakFormPipeline) && (int) ($mynakFormPipeline['open'] ?? 0) > 0) {
            $open = (int) $mynakFormPipeline['open'];
            $items[] = [
                'level' => $open > 8 ? 'warning' : 'info',
                'title' => 'Açık form başvuruları: ' . $open,
                'detail' => 'Yeni / arandı / teklif aşamalarındaki kayıtları arşivleme veya güncelleyin.',
                'action_href' => 'form_submissions.php',
                'action_label' => 'Form başvuruları',
            ];
        }

        $mynakSapiName = (string) php_sapi_name();
        if (is_array($mynakSysHealth) && !($mynakSysHealth['opcache']['enabled'] ?? false) && (PHP_SAPI === 'fpm-fcgi' || PHP_SAPI === 'fpm' || strpos($mynakSapiName, 'fpm') !== false || strpos($mynakSapiName, 'apache2handler') !== false)) {
            $items[] = [
                'level' => 'info',
                'title' => 'OPcache bu ortamda kapalı görünüyor',
                'detail' => 'Web sunucusunda açılması (barındırma php.ini) sunucu yanıt süresine yardımcı olur. Panelden değişmez; hosting üzerinde kontrol edin.',
                'action_href' => null,
                'action_label' => null,
            ];
        }

        if (!is_file($root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . '.htaccess')) {
            $items[] = [
                'level' => 'info',
                'title' => 'assets/.htaccess yok (statik önbellek)',
                'detail' => 'Performans için proje `assets` klasörüne .htaccess ekleyin; deploy/ZIP’e dahil edin.',
                'action_href' => null,
                'action_label' => null,
            ];
        }
        $gscFiles = 0;
        foreach (['google7a25dbafffa6d398.html', 'google9e3ba42c6da7ec49.html'] as $gsc) {
            if (is_file($root . DIRECTORY_SEPARATOR . $gsc)) {
                $gscFiles++;
            }
        }
        if ($gscFiles === 0) {
            $items[] = [
                'level' => 'info',
                'title' => 'GSC HTML doğrulama dosyası yok (yerel proje)',
                'detail' => 'Search Console “HTML dosyası” yönteminde, dosya site köküne (public_html) yüklenmeli.',
                'action_href' => 'seo_management.php',
                'action_label' => 'SEO yönetimi (genel)',
            ];
        }

        $levelOrder = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($items, static function (array $a, array $b) use ($levelOrder): int {
            $la = $levelOrder[$a['level'] ?? 'info'] ?? 2;
            $lb = $levelOrder[$b['level'] ?? 'info'] ?? 2;
            if ($la !== $lb) {
                return $la <=> $lb;
            }
            return strcmp($a['title'] ?? '', $b['title'] ?? '');
        });

        return $items;
    }
}
