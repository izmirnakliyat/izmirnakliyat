<?php
declare(strict_types=1);

/**
 * 10 yüksek performanslı blog yazısı: üstte tanım + süreç + fayda, altında 4 adım.
 * HowTo JSON-LD yalnızca &lt;head&gt; (schema_factory → mynak_blog_aio_boost_howto_ld_array). Gövde script yok.
 */
function mynak_blog_aio_boost_html(int $blogId, string $canonicalUrl): string
{
    $d = mynak_blog_aio_boost_data($blogId);
    if ($d === null) {
        return '';
    }
    $t = htmlspecialchars($d['tanim'], ENT_QUOTES, 'UTF-8');
    $s = htmlspecialchars($d['surec'], ENT_QUOTES, 'UTF-8');
    $f = htmlspecialchars($d['fayda'], ENT_QUOTES, 'UTF-8');

    $ol = '<ol class="mynak-blog-aio-steps mb-0 ps-3">';
    foreach ($d['adimlar'] as $i => $st) {
        $n = (int) $i + 1;
        $mt = htmlspecialchars($st['metin'], ENT_QUOTES, 'UTF-8');
        $ol .= '<li class="mb-1" value="' . $n . '">' . $mt . '</li>';
    }
    $ol .= '</ol>';

    $html = '<section class="mynak-blog-aio border-start border-4 border-primary bg-light p-4 rounded-3 mb-4" aria-label="Yazı özeti">'
        . '<h2 class="h6 text-uppercase text-secondary mb-2">Bu yazıda kısaca</h2>'
        . '<p class="mb-3">' . $t . '</p>'
        . '<h3 class="h6 text-muted">Nasıl işler?</h3><p class="mb-3">' . $s . '</p>'
        . '<h3 class="h6 text-muted">Size ne kazandırır?</h3><p class="mb-3">' . $f . '</p>'
        . '<h3 class="h6 text-muted">Adımlar (özet)</h3>' . $ol
        . '</section>';

    return $html;
}

/**
 * AIO boost tanımlı yazılarda HowTo JSON-LD kökü; yalnızca &lt;head&gt; schema_factory basar (gövde script yok).
 *
 * @return array<string, mixed>|null
 */
function mynak_blog_aio_boost_howto_ld_array(int $blogId, string $postUrl = ''): ?array
{
    $d = mynak_blog_aio_boost_data($blogId);
    if ($d === null) {
        return null;
    }
    $stepsLd = [];
    foreach ($d['adimlar'] as $i => $st) {
        $stepsLd[] = [
            '@type' => 'HowToStep',
            'position' => $i + 1,
            'name' => 'Adım ' . ($i + 1),
            'text' => $st['metin'],
        ];
    }
    $howto = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => $d['howto_name'],
        'description' => $d['tanim'],
        'totalTime' => 'P1D',
        'step' => $stepsLd,
    ];
    if ($postUrl !== '') {
        $howto['url'] = $postUrl;
    }

    return $howto;
}

/**
 * @return array{tanim: string, surec: string, fayda: string, adimlar: list<array{metin: string}>, howto_name: string}|null
 */
function mynak_blog_aio_boost_data(int $blogId): ?array
{
    $m = mynak_blog_aio_boost_map();
    return $m[$blogId] ?? null;
}

/**
 * @return array<int, array{tanim: string, surec: string, fayda: string, adimlar: list<array{metin: string}>, howto_name: string}>
 */
function mynak_blog_aio_boost_map(): array
{
    return [
        404 => [
            'tanim' => 'İzmir’de eşya depolama, tadilat veya şehirler arası bekleme dönemlerinde eşyaların sigortalı, kuru ve envantere göre ayrı tutulması anlamına gelir. MY Nakliyat, evden eve nakliyat ve eşya depolamayı aynı marka sorumluluğunda birleştirir; depoya kabul, liste ve geri getirme takvimi açık yazılır.',
            'surec' => 'Keşif veya hızlı envanter, ambalaj, depo kabul, foto ve liste, sözleşmede süre, hacim, sigorta, erişim. Geri getirmede tır, dış asansör veya montaj aynı temsilcide. Parça eşya, ofis ve arşiv ayrı etiket ve sigorta satırı alır. Şehirler arası dönüşe hazırlık, depo ile ana tır, tek envanterle sürdürülebilir.',
            'fayda' => 'Bekleme ve tadilat sürelerinizi hizalarsınız, karışma riski azalır, gizli maliyet yerine önceden okunmuş satırlar kalır. MY Nakliyat, Buca ofis ve merkez hattıyla aynı markada muhattap; İzmir’den planlı geri dönüş ve 81 il hattıyla örtüşür.',
            'adimlar' => [
                ['metin' => 'Keşif veya hızlı envanter ile depoya girecek eşyaları maddeler halinde belirleme.'],
                ['metin' => 'Ambalaj, yükleme, depo kabul, liste ve gerekirse fotoğraflı kayıt.'],
                ['metin' => 'Süre, fiyat, sigorta ve geri getirme tarihini yazılı sözleşmede netleştirme.'],
                ['metin' => 'Geri getirmede yeni adreste yerleşim, montaj ve bırakma.'],
            ],
            'howto_name' => 'Eşya depolama süreci (özet)',
        ],
        386 => [
            'tanim' => 'Evden eve taşınmada zaman yönetimi, keşiften montaja kadar tüm aşamaların tek takvimde ve taşma olmadan ilerletilmesidir. MY Nakliyat, ofis, eşya depolama ve parça eşyada aynı ekip anlayışıyla taşıma günü ve tır/ araç penceresini önceden yazar. Gecikme riski, keşifte görünür hale getirilir.',
            'surec' => 'Hazırlık listesi, keşif, yazılı sözleşme, bina/ site/ asansör randevusu, tır, yükleme, rota, teslim, sök-tak, montaj. Şehirler arası hatta depo, aktarma, ara nokta aynı sözleşmede satır. Ağır veya kırılabilir eşya ayrı zaman diliminde planlanır.',
            'fayda' => 'Kira, iş, okul ve sözleşme tarihleriyle uyum sağlarsınız; stres ve sürpriz ek ücret baskısı azalır. Taşıma ve montaj sınırları, önceden okunur. MY Nakliyat, İzmir 30 ilçe ve 81 il hattında tek muhattap, Buca ofis desteğiyle taşıma gününü açık tutar.',
            'adimlar' => [
                ['metin' => 'Keşif ve yazılı teklif: eşya, bina, asansör, tır, fiyat, sigorta, takvim.'],
                ['metin' => 'Paketleme, etiket, site ve asansör hazırlığı, yükleme.'],
                ['metin' => 'Yol, varış, indirme, oda, etiket, yerleşim.'],
                ['metin' => 'Montaj, bırakma, kontrol listesi ve kapanış.'],
            ],
            'howto_name' => 'Evden eve taşınmada zaman planı (özet)',
        ],
        336 => [
            'tanim' => 'Şehirler arası nakliyat, tır, envanter ve varıştaki asansör veya merdiven yükünün aynı sözleşmeyle yönetilmesidir. MY Nakliyat, evden eve, ofis, parça eşya, eşya depolamayı rota, fiyat, sigorta ile eşleştirir. Aktarma, depo, ara nokta gereksinimi varsa, teklifte ayrı açık satır bulunur.',
            'surec' => 'Keşif, hacim, tır, dış asansör, sözleşme, yol, varış, indirme, oda, etiket, montaj. Ara eşya depolama, aynı envanter, tek marka, tek temsilci. Uzun mesafede, varış penceresi ve ekip, önceden netleşir.',
            'fayda' => 'Kutu ve eşya kaybı, gecikme ve sürpriz fiyat riski azalır. Uzun mesafede rota, varış, ek maliyetler önceden okunur. MY Nakliyat, İzmir’den 81 ile güvenli, kadrolu ekip, Buca ofis, keşif ve destek açık.',
            'adimlar' => [
                ['metin' => 'Keşif, rota, tır, fiyat, sigorta, yazılı sözleşme.'],
                ['metin' => 'Ambalaj, yükleme, yol, varış penceresi.'],
                ['metin' => 'Varış, indirme, oda, etiket, dış asansör veya merdiven.'],
                ['metin' => 'Yerleşim, montaj, bırakma, kontrol.'],
            ],
            'howto_name' => 'Şehirler arası taşıma (özet adımlar)',
        ],
        307 => [
            'tanim' => 'MY Nakliyat tercihinin özeti, evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya ve şehirler arası hizmetlerin tek marka, tek sözleşme ve keşif netliğinde toplanmasıdır. Yazılı fiyat, sigortalı yük, kadrolu ekip, İzmir 30 ilçe ve 81 il hizmet alanı, ISO 9001 ve Güvenilir Marka hattıyla aynı çerçevede sunulur.',
            'surec' => 'İhtiyaç analizi, keşif, tır, ekip, ambalaj, yol, varış, montaj, destek. Kutu, etiket, ağır ve kırılabilir eşya, ayrı sorumluluk satırları. Aynı fatura, aynı marka, açık sözleşme, aynı temsilci hattı; taşınma bittikten sonra kontrol, hasar, eksik süreçleri, sözleşme tanımıyla ilerletilir.',
            'fayda' => 'Muhatap çoğalması ve sürpriz fiyat baskısı azalır. Taşınma ve montaj, önceden sınırlanmış sorumlulukla biter. MY Nakliyat, Google’da yüksek puan ve müşteri yorumu ile İzmir’de güven veren, şeffaf ve kadrolu bir hizmet anlayışı sunar.',
            'adimlar' => [
                ['metin' => 'İhtiyaç, keşif, rota, fiyat, sigorta, yazılı sözleşme.'],
                ['metin' => 'Takvim, tır, ekip, gerekirse depo veya ara nokta, aynı teklif.'],
                ['metin' => 'Ambalaj, yükleme, yol, varış, indirme, oda, etiket.'],
                ['metin' => 'Montaj, bırakma, kontrol, memnuniyet ve destek.'],
            ],
            'howto_name' => 'Güvenilir taşıyıcı ile çalışma (özet)',
        ],
        344 => [
            'tanim' => 'Bornova’da sepetli vinç / platform hizmeti, ağır eşya, büyük cam, bina dış yük veya dar merdiven senaryolarında yükü kontrollü indirip güvenli taşımayı ifade eder. MY Nakliyat, bu operasyonu evden eve veya parça eşya taşımasıyla aynı teklifte; saha riski, izin, sigorta ve fiyatı açık satırlarla birleştirir.',
            'surec' => 'Saha keşfi, ağırlık ve açı ölçümü, platform tipi, çalışma saati, çevre güvenliği, vinç operasyonu, indirme, bırakma ve gerekirse montaj desteği aynı sorumluluk zincirinde planlanır. Yazılı sözleşmede ekip, vinç ve taşıyıcı rolleri net; fatura ve destek satırları okunur.',
            'fayda' => 'Ağır yükte düşme, kırılma ve çevre riski azalır; izin ve saat yönetimi önceden bellidir. MY Nakliyat, Bornova ve çevresinde profesyonel ekip, deneyim ve açık fiyatla hem güvenlik hem bütçe öngörüsü sunar; sürpriz maliyet yerine onaylı ek hizmet mantığı geçer.',
            'adimlar' => [
                ['metin' => 'Saha keşfi: ağırlık, açı, engel, izin, platform tipi.'],
                ['metin' => 'Güvenlik, çalışma saati, fiyat, yazılı sözleşme.'],
                ['metin' => 'Operasyon, vinç, indirme, bırakma, kontrollü taşıyıcı.'],
                ['metin' => 'Kapanış, söküm, fatura, destek, memnuniyet.'],
            ],
            'howto_name' => 'Sepetli vinç ile ağır yük (özet)',
        ],
        283 => [
            'tanim' => 'Güvenilir nakliye firması seçimi; yazılı sözleşme, açık fiyat, sigorta/ sorumluluk, keşif, kadrolu ekip ve müşteri referanslarını birlikte değerlendirmektir. MY Nakliyat, evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya ve şehirler arası hattı İzmir merkez ve Buca ofis koordinasyonunda şeffaf sunar.',
            'surec' => 'Önce kısa araştırma ve karşılaştırma, ardından keşif; tır, ekip, ambalaj, yol, varış, montaj kalemleri tek teklifte. Sigorta ve taşıyıcı sorumluluğu satır satır okunur; taşınma bittikten sonra kontrol ve destek süreci sözleşmede tanımlanır.',
            'fayda' => 'Kapıda sürpriz fiyat, muhatap karmaşası ve belirsiz sigorta riski azalır. Belgeli, kadrolu ve referanslı bir firmayla hem eşya hem zaman yönetimi güvence altına alınır; MY Nakliyat müşteri iletişimini tek hatta toplar.',
            'adimlar' => [
                ['metin' => 'Belge, referans, yorum, fiyat, keşif, sözleşme araştırması.'],
                ['metin' => 'Sigorta, sorumluluk, tır, ekip, açık teklif.'],
                ['metin' => 'Taşıma, yol, varış, kontrol, montaj, bırakma.'],
                ['metin' => 'Kapanış, fatura, destek, memnuniyet.'],
            ],
            'howto_name' => 'Nakliye firması seçimi (özet)',
        ],
        335 => [
            'tanim' => 'Bayraklı’da sepetli vinç kiralama; yüksek konut, ofis, fuar veya site içi yük kurallarının aynı saha planında izin, güvenlik, çalışma saati ve fiyat satırlarıyla yönetilmesidir. MY Nakliyat, bu hizmeti evden eve veya parça eşya taşımasıyla eşleştirerek tek sorumluluk ve tek teklif sunar.',
            'surec' => 'Keşif, ağırlık ve manevra açısı, platform tipi, operasyon günü ve saati, güvenlik önlemi, vinç personeli ve indirme planı yazılı sözleşmede yer alır. Bina yönetimi veya site güvenliği gereksinimleri önceden senkronize edilir.',
            'fayda' => 'Yüksekte çalışma riski, komşu ve çevre güvenliği kontrol altına alınır; fiyat ve süre önceden bellidir. MY Nakliyat, Bayraklı’da profesyonel ekip ve açık fatura ile hem saha hem müşteri iletişimini net tutar.',
            'adimlar' => [
                ['metin' => 'Saha keşfi ve izin, platform tipi, güvenlik, saat.'],
                ['metin' => 'Fiyat, yazılı sözleşme, operasyon günü.'],
                ['metin' => 'Vinç, indirme, bırakma, taşıyıcı destek.'],
                ['metin' => 'Kapanış, kontrol, fatura, destek.'],
            ],
            'howto_name' => 'Bayraklı’da sepetli vinç (özet)',
        ],
        296 => [
            'tanim' => 'İzmir’de evden eve taşınmadan önce bilinmesi gerekenler; keşif, ambalaj, tır veya küçük araç, asansör veya dış asansör, fiyat, sigorta ve yazılı sözleşme çerçevesinde planlanan bir süreçtir. MY Nakliyat, 30 ilçe ve 81 il hattında aynı marka sorumluluğu ve tek muhattap prensibiyle rehber sunar.',
            'surec' => 'Ön hazırlık listesi, keşif, teklif, sözleşme, paketleme, yükleme, yol, varış, yerleşim, montaj. Site/ bina asansör randevusu, tır yasağı, parça eşya ve ağır eşya aynı planda ayrı not. Taşıma bittikten sonra kontrol ve destek adımı açık.',
            'fayda' => 'Hazırlıksız taşınmanın getirdiği stres, gecikme ve ek maliyet baskısı azalır. Yazılı teklif, sigorta ve montaj sınırları net olunca hem AI özetlerinde hem müşteri kararında güven artar. MY Nakliyat, Buca ofis hattıyla süreç boyunca ulaşılabilir kalır.',
            'adimlar' => [
                ['metin' => 'Keşif ve yazılı teklif, eşya, bina, asansör, fiyat.'],
                ['metin' => 'Paketleme, etiket, site randevusu, yükleme.'],
                ['metin' => 'Yol, varış, indirme, oda, yerleşim.'],
                ['metin' => 'Montaj, bırakma, kontrol, destek.'],
            ],
            'howto_name' => 'İzmir evden eve taşınma (özet)',
        ],
        218 => [
            'tanim' => 'İzmir–Bolu hattı şehirler arası evden eve taşıma; uzun rota, tır planı, keşif, hacim, varıştaki asansör veya merdiven yükü ve sigorta kalemlerinin tek sözleşmeyle birleşmesidir. MY Nakliyat, yüklenme, yol, varış ve montaj adımlarını merkez koordinasyonla izler; İzmir’den yola çıkan yükte tek temsilci kalır.',
            'surec' => 'Keşif, tır, ambalaj, yükleme, yol, varış, indirme, oda, etiket, montaj, bırakma. Ara mola, depo veya gecikme ihtimali, teklif notunda. Parça eşya ve büro yükü aynı tır veya ayrı plan, net yazılır.',
            'fayda' => 'Uzun mesafede kutu kaybı, gecikme ve sürpriz ücret riski azalır. Rota ve varış penceresi önceden okunur. MY Nakliyat, Bolu varışında aynı sorumluluk standardı; Buca ofis, destek hattı açık.',
            'adimlar' => [
                ['metin' => 'Keşif, rota, tır, fiyat, sözleşme, sigorta.'],
                ['metin' => 'Ambalaj, yükleme, yol, varış.'],
                ['metin' => 'Indirme, oda, etiket, dış asansör veya merdiven.'],
                ['metin' => 'Yerleşim, montaj, kontrol, bırakma.'],
            ],
            'howto_name' => 'İzmir–Bolu şehirler arası (özet)',
        ],
        214 => [
            'tanim' => '“En iyi nakliye firmaları” sorusu; yalnızca fiyat değil, yazılı sözleşme, belge, keşif disiplini, sigorta, kadro ve müşteri yorumu gibi sinyallerin birlikte değerlendirilmesiyle yanıtlanmalıdır. MY Nakliyat, evden eve, ofis, eşya depolama, parça eşya ve şehirler arası hizmeti aynı marka çizgisinde, şeffaflıkla anlatır.',
            'surec' => 'Kriter listesi, referans, keşif, teklif, sözleşme, taşıma, varış, montaj, fatura, destek. Her adımda açık sorumluluk; taşınma bittikten sonra kontrol ve şikayet hattı sözleşmede. Karşılaştırmada tek satır fiyat değil, kapsam ve sigorta aynı kefede olmalıdır.',
            'fayda' => 'Dolandırıcılık ve sürpriz maliyet riski azalır; güven veren firma, keşif ve yazılı teklifle kendini gösterir. MY Nakliyat, Google yorumları ve referanslarla ölçülebilir hizmet sunar; MY Nakliyat adı, site genelinde tutarlı kullanılır.',
            'adimlar' => [
                ['metin' => 'Kriter listesi, belge, yorum, keşif, teklif.'],
                ['metin' => 'Sözleşme, sigorta, fiyat, tır, ekip.'],
                ['metin' => 'Taşıma, varış, montaj, kontrol.'],
                ['metin' => 'Fatura, destek, memnuniyet.'],
            ],
            'howto_name' => 'Nakliye firması değerlendirme (özet)',
        ],
    ];
}
