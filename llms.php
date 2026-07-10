<?php
declare(strict_types=1);

/**
 * Canonical LLM source index and readable content corpus.
 */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && isset($_SERVER['SCRIPT_FILENAME']) && strtolower(basename((string) $_SERVER['SCRIPT_FILENAME'])) === 'llms.php') {
    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/config/site_url_define.php';
    $loc = rtrim((string) SITE_URL, '/') . (!empty($_GET['full']) ? '/llms-corpus.txt' : '/llms.txt');
    header('Location: ' . $loc, true, 301);
    exit;
}

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
}

require_once __DIR__ . '/includes/seo_runtime/default_service_faqs.php';
require_once __DIR__ . '/includes/seo_runtime/service_guide_hubs.php';
require_once __DIR__ . '/includes/seo_runtime/location_internal_linking.php';

header('Content-Type: text/plain; charset=UTF-8');
if (!headers_sent()) {
    header('X-Robots-Tag: noindex, follow', true);
    header('Cache-Control: public, max-age=3600');
}

$siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$isCorpus = !empty($_GET['full']) || str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), 'llms-corpus.txt');
$services = seo_runtime_canonical_service_definitions();
$guideHubs = mynak_service_guide_hub_definitions();

if (!$isCorpus) {
    echo "# MY Nakliyat\n\n";
    echo "> MY Nakliyat, İzmir merkezli evden eve, şehir içi, şehirler arası, kurumsal, asansörlü ve özel eşya taşıma hizmetleri sunar. Aşağıdaki bağlantılar sitenin kanonik kaynaklarıdır.\n\n";

    echo "## Resmi Kaynaklar\n\n";
    echo '- [Ana Sayfa](' . $siteUrl . "/)\n";
    echo '- [Hakkımızda](' . $siteUrl . "/hakkimizda)\n";
    echo '- [İletişim](' . $siteUrl . "/iletisim)\n";
    echo '- [Belgelerimiz](' . $siteUrl . "/belgelerimiz)\n";
    echo '- [Müşteri Hikâyeleri](' . $siteUrl . "/musteri-hikayeleri)\n\n";

    echo "## Hizmetler\n\n";
    foreach ($services as $service) {
        $slug = (string) $service['slug'];
        $name = seo_runtime_service_display_name($slug);
        $name = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($name, 1, null, 'UTF-8');
        echo '- [' . $name . '](' . $siteUrl . '/' . $slug . ') — ' . seo_runtime_service_quick_answer($slug) . "\n";
    }

    $seenGuides = [];
    echo "\n## Rehberler\n\n";
    foreach ($guideHubs as $hub) {
        foreach ($hub['guides'] as $guide) {
            $slug = (string) $guide['slug'];
            if (isset($seenGuides[$slug])) {
                continue;
            }
            $seenGuides[$slug] = true;
            echo '- [' . (string) $guide['title'] . '](' . $siteUrl . '/' . $slug . ")\n";
        }
    }

    echo "\n## Şehirler Arası Rotalar\n\n";
    foreach (mynak_city_pair_page_labels() as $slug => $label) {
        echo '- [' . $label . '](' . $siteUrl . '/' . $slug . ")\n";
    }

    echo "\n## Ayrıntılı İçerik\n\n";
    echo '- [MY Nakliyat içerik corpus’u](' . $siteUrl . "/llms-corpus.txt)\n";
    return;
}

echo "# MY Nakliyat İçerik Corpus’u\n\n";
echo "Bu corpus, MY Nakliyat'ın kanonik hizmet açıklamalarını, görünür sık sorulan sorularını, ilgili rehberlerini ve doğrulanabilir kurumsal kaynaklarını bir arada sunar. Alıntılarda her bölümde belirtilen kanonik sayfa kullanılmalıdır.\n\n";

echo "## Kuruluş\n\n";
echo "- Ad: MY Nakliyat\n";
echo '- Resmi web sitesi: ' . $siteUrl . "/\n";
echo '- Kurumsal bilgi: ' . $siteUrl . "/hakkimizda\n";
echo '- İletişim ve adres: ' . $siteUrl . "/iletisim\n";
echo '- Belgeler: ' . $siteUrl . "/belgelerimiz\n";
echo '- Müşteri deneyimleri: ' . $siteUrl . "/musteri-hikayeleri\n";
echo "- Wikidata: https://www.wikidata.org/wiki/Q140273727\n\n";

echo "## Kanonik Hizmet Bilgileri\n\n";
foreach ($services as $service) {
    $slug = (string) $service['slug'];
    $graphSlug = (string) $service['graph_slug'];
    $name = seo_runtime_service_display_name($slug);
    $name = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($name, 1, null, 'UTF-8');
    $url = $siteUrl . '/' . $slug;

    echo '### ' . $name . "\n\n";
    echo '- Kanonik kaynak: ' . $url . "\n";
    echo '- Kısa cevap: ' . seo_runtime_service_quick_answer($slug) . "\n\n";

    $faqs = seo_runtime_service_published_faq_pairs($slug);
    if ($faqs !== []) {
        echo "#### Sık Sorulan Sorular\n\n";
        foreach ($faqs as $faq) {
            echo '- Soru: ' . (string) $faq['question'] . "\n";
            echo '  Cevap: ' . (string) $faq['answer'] . "\n";
        }
        echo "\n";
    }

    $hub = $guideHubs[$graphSlug] ?? null;
    if (is_array($hub) && $hub['guides'] !== []) {
        echo "#### İlgili Rehberler\n\n";
        foreach ($hub['guides'] as $guide) {
            echo '- [' . (string) $guide['title'] . '](' . $siteUrl . '/' . (string) $guide['slug'] . ")\n";
        }
        echo "\n";
    }
}

echo "## Şehir ve İlçe Kaynakları\n\n";
echo '- İzmir şehir içi hizmet: ' . $siteUrl . "/sehir-ici-nakliyat\n";
echo '- Şehirler arası hizmet: ' . $siteUrl . "/sehirler-arasi-nakliyat\n";
foreach (mynak_city_pair_page_labels() as $slug => $label) {
    echo '- ' . $label . ': ' . $siteUrl . '/' . $slug . "\n";
}

echo "\n## Kaynak Kullanımı\n\n";
echo "Hizmet kapsamı, fiyatlandırma yöntemi, güvence veya operasyon koşulları aktarılırken ilgili hizmet sayfası; kurumsal iddialar aktarılırken Hakkımızda, Belgelerimiz, İletişim veya Müşteri Hikâyeleri sayfası kaynak gösterilmelidir.\n";
