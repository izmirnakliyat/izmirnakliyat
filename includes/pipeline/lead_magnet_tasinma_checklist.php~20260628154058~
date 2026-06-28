<?php
declare(strict_types=1);

/**
 * B2: Ücretsiz taşınma kontrol listesi — içerik (HTML) + dışa aktarma belgesi.
 * PDF üretimi için harici kütüphane yok; tarayıcı Yazdır → "PDF'ye kaydet" önerilir.
 */

const MYNAK_LEAD_CHECKLIST_SESSION = 'mynak_lead_checklist_ok';
const MYNAK_LEAD_CHECKLIST_SESSION_TTL = 86400; // 24 saat aynı oturumda tekrar indir

function mynak_lead_checklist_set_session(): void
{
    $_SESSION[MYNAK_LEAD_CHECKLIST_SESSION] = time() + MYNAK_LEAD_CHECKLIST_SESSION_TTL;
}

function mynak_lead_checklist_is_allowed(): bool
{
    if (empty($_SESSION[MYNAK_LEAD_CHECKLIST_SESSION]) || !is_numeric($_SESSION[MYNAK_LEAD_CHECKLIST_SESSION])) {
        return false;
    }
    return (int) $_SESSION[MYNAK_LEAD_CHECKLIST_SESSION] > time();
}

/**
 * Sadece gövde (iç sarmalayıcı) — sitede ve indirilebilir sürümde ortak.
 */
function mynak_lead_magnet_href(string $p): string
{
    if (function_exists('mynak_public_path')) {
        return mynak_public_path($p);
    }

    return '/' . ltrim($p, '/');
}

function mynak_lead_magnet_checklist_body_html(): string
{
    $t = mynak_lead_magnet_href('teklif-alin');
    $i = mynak_lead_magnet_href('iletisim');

    $esc = static function (string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };

    $sections = [
        [
            'h' => '4–5 hafta kala: plan ve evrak',
            'items' => [
                'Taşınma (veya sözleşme bitiş) tarihini netleştirin; iş/ okul/ kreş kayıtlarını buna göre planlayın.',
                'Kira/ aidat, kapıcı, depozito, abonelik fesih sürelerini (elektrik, su, doğalgaz, internet, TV) not edin.',
                'Eşya listesi taslak: satılacak, bağışlanacak, depoya gidecek ve taşınacak ayrımı.',
                'Nakliyat firmasından keşif + yazılı teklif; kapsam (ambalaj, sigorta, kat, dış asansör) aynı sütunda olsun.',
            ],
        ],
        [
            'h' => '2 hafta kala: adres ve hatlar',
            'items' => [
                'Yeni adres: posta, kargo, e-ticaret hesaplarını güncelleyin.',
                'BANKA, SGK, vergi, araç, hayvan/ kişi kartı gibi kurumlarda ikamet/ adres değişikliği prosedürlerine bakın.',
                'Site yönetimi / OSB: taşıma günü, asansör rezervasyonu, taşıma saati sınırları.',
            ],
        ],
        [
            'h' => '1 hafta kala: paketleme ve eşyalar',
            'items' => [
                'Hassas: cam, ayna, TV, piyano, antika; foto ve ayrı etiket.',
                '“İlk açılacak kutu”da mutfak/ banyo temel, yatak takımı, şarj aletleri.',
                'Buzdolabı: erken boşaltma; taşıma günü sabitleme ve taşıyıcı talimatına uyum.',
            ],
        ],
        [
            'h' => 'Taşıma günü',
            'items' => [
                'Cüzdan, kıymetli eşya, önemli evrak, ilaç: yanınızda ayrı çanta.',
                'Sayım: oda oda, mobilya ve koli adedi; teslim formunda imza.',
                'Eski adreste elektrik/ su sayaç notu (mümkünse foto); yeni adreste aynı şekilde kontrol.',
            ],
        ],
        [
            'h' => 'Yeni adreste (ilk 48 saat)',
            'items' => [
                'Kutu açma öncelik: mutfak/ yatak, çocuk odası, çalışma alanı.',
                'Elektrik panosu, su vanaları, kombi, güvenlik: hızlı kontrol listesi.',
                'Komşu tanışma, site güvenik, otopark/ kart — site kurallarını alın.',
            ],
        ],
    ];

    $out = '<div class="mynak-checklist-prose">'
        . '<p class="lead">Bu liste MY Nakliyat tarafından taşınma hazırlığı için hazırlanmıştır. Özel eşya veya kurumsal taşımalarda ekip, ek sözleşme ve sigorta maddelerini ayrıca teyit edin.</p>';
    $out .= '<p>Profesyonel teklif: <a href="' . $esc($t) . '">' . $esc('Teklif alın') . '</a> · <a href="' . $esc($i) . '">İletişim</a></p><hr class="my-4">';

    foreach ($sections as $sec) {
        $out .= '<h2 class="h4 mt-4">' . $esc($sec['h']) . '</h2><ul class="mynak-checklist-ul">';
        foreach ($sec['items'] as $it) {
            $out .= '<li>' . $esc($it) . '</li>';
        }
        $out .= '</ul>';
    }
    $out .= '</div>';

    return $out;
}

/**
 * E-posta eki yerine: tek başına açılan tam HTML (UTF-8) dosyası.
 */
function mynak_lead_magnet_checklist_full_document_for_download(): string
{
    $path = '/tasinma-kontrol-listesi';
    if (function_exists('mynak_abs_url_from_public_path') && function_exists('mynak_public_path')) {
        $path = rtrim(mynak_abs_url_from_public_path(mynak_public_path('tasinma-kontrol-listesi')), '/');
    } elseif (defined('SITE_URL') && is_string(SITE_URL) && SITE_URL !== '') {
        $path = rtrim((string) SITE_URL, '/') . '/tasinma-kontrol-listesi';
    }
    $body = mynak_lead_magnet_checklist_body_html();
    $title = 'MY Nakliyat — Taşınma Kontrol Listesi';
    $style = 'body{font-family:Segoe UI,system-ui,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem;line-height:1.6;color:#222;}h1{font-size:1.35rem;}h2{font-size:1.1rem;margin-top:1.5rem;}.mynak-checklist-ul{padding-left:1.1rem;}.small{color:#666;font-size:0.9rem;border-top:1px solid #eee;padding-top:1rem;margin-top:2rem;}@media print{body{margin:0.5cm;}}';
    $meta = 'Bu belge ' . $path . ' adresinden e-posta kaydı sonrası indirilebilir. MY Nakliyat — İzmir.';

    return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
        . '<style>' . $style . '</style></head><body>'
        . '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p class="small">' . htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') . '</p>'
        . $body
        . '<p class="small">© ' . date('Y') . ' MY Nakliyat. Taşınma hizmeti ve özel sözleşmeler için ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</body></html>';
}
