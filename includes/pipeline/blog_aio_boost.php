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
            'tanim' => 'İzmir’de eşya depolama, tadilat veya şehirler arası bekleme dönemlerinde depoya alınacak eşyanın envanter, süre ve erişim koşullarıyla planlanmasıdır. MY Nakliyat, depoya kabul ve geri getirme kapsamını talep bilgilerine göre yazılı teklifte belirtir.',
            'surec' => 'Envanter, ambalaj, depo kabul, süre, hacim ve erişim koşulları talebe göre değerlendirilir. Geri getirme, dış asansör veya montaj gerekiyorsa bunların kapsamı ve güvence seçenekleri yazılı teklifte ayrı belirtilir.',
            'fayda' => 'Depolama süresi, erişim ve geri getirme adımları yazılı planlandığında tarafların sorumlulukları daha açık izlenebilir. Hizmet bölgesi ve operasyon koşulları talebe göre teyit edilir.',
            'adimlar' => [
                ['metin' => 'Keşif veya hızlı envanter ile depoya girecek eşyaları maddeler halinde belirleme.'],
                ['metin' => 'Ambalaj, yükleme, depo kabul, liste ve gerekirse fotoğraflı kayıt.'],
                ['metin' => 'Süre, fiyat, sigorta ve geri getirme tarihini yazılı sözleşmede netleştirme.'],
                ['metin' => 'Geri getirmede yeni adreste yerleşim, montaj ve bırakma.'],
            ],
            'howto_name' => 'Eşya depolama süreci (özet)',
        ],
        386 => [
            'tanim' => 'Evden eve taşınmada zaman yönetimi, hazırlık, yükleme, ulaşım ve teslim adımlarının talep bilgilerine göre bir takvimde planlanmasıdır. MY Nakliyat, taşıma günü ve araç gereksinimini yazılı teklif aşamasında değerlendirir.',
            'surec' => 'Hazırlık listesi, keşif, yazılı sözleşme, bina/ site/ asansör randevusu, tır, yükleme, rota, teslim, sök-tak, montaj. Şehirler arası hatta depo, aktarma, ara nokta aynı sözleşmede satır. Ağır veya kırılabilir eşya ayrı zaman diliminde planlanır.',
            'fayda' => 'Kira, iş ve okul tarihleriyle birlikte planlanan bir takvim, taşıma ve montaj kapsamının önceden görülmesini sağlar. Hizmet bölgesi, araç ve ekip planı talebe göre teyit edilir.',
            'adimlar' => [
                ['metin' => 'Keşif ve yazılı teklif: eşya, bina, asansör, tır, fiyat, sigorta, takvim.'],
                ['metin' => 'Paketleme, etiket, site ve asansör hazırlığı, yükleme.'],
                ['metin' => 'Yol, varış, indirme, oda, etiket, yerleşim.'],
                ['metin' => 'Montaj, bırakma, kontrol listesi ve kapanış.'],
            ],
            'howto_name' => 'Evden eve taşınmada zaman planı (özet)',
        ],
        336 => [
            'tanim' => 'Şehirler arası nakliyat; eşya envanteri, rota, araç, yükleme ve varış koşullarının birlikte planlandığı taşıma hizmetidir. Aktarma, depolama, dış asansör veya ara nokta gerekiyorsa kapsamı yazılı teklifte ayrı değerlendirilir.',
            'surec' => 'Keşif, hacim, tır, dış asansör, sözleşme, yol, varış, indirme, oda, etiket, montaj. Ara eşya depolama, aynı envanter, tek marka, tek temsilci. Uzun mesafede, varış penceresi ve ekip, önceden netleşir.',
            'fayda' => 'Rota, teslim penceresi ve ek hizmetlerin yazılı belirtilmesi, taşıma kapsamının karşılaştırılmasını kolaylaştırır. Hizmet bölgesi, ekip ve güvence seçenekleri talep aşamasında teyit edilir.',
            'adimlar' => [
                ['metin' => 'Keşif, rota, tır, fiyat, sigorta, yazılı sözleşme.'],
                ['metin' => 'Ambalaj, yükleme, yol, varış penceresi.'],
                ['metin' => 'Varış, indirme, oda, etiket, dış asansör veya merdiven.'],
                ['metin' => 'Yerleşim, montaj, bırakma, kontrol.'],
            ],
            'howto_name' => 'Şehirler arası taşıma (özet adımlar)',
        ],
        307 => [
            'tanim' => 'MY Nakliyat; evden eve nakliyat, ofis taşıma, eşya depolama, parça eşya ve şehirler arası hizmetlere ilişkin kapsamı talep bilgilerine göre değerlendirir. Fiyat, takvim, güvence ve ek hizmetler yazılı teklif aşamasında belirtilir.',
            'surec' => 'İhtiyaç analizi, envanter, araç, ambalaj, rota, teslim ve gerekiyorsa montaj adımları değerlendirilir. Ağır veya kırılabilir eşyalar, ek hizmetler ve tarafların sorumlulukları yazılı kapsamda ayrıca belirtilir.',
            'fayda' => 'Yazılı kapsam; fiyat, takvim, ek hizmetler ve sorumlulukların teklif karşılaştırması sırasında birlikte görülmesini sağlar. Yayımlanan belgeler, müşteri deneyimleri ve iletişim bilgileri kendi kaynaklarından doğrulanabilir.',
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
            'fayda' => 'Ağırlık, erişim, çalışma alanı ve izin koşullarının önceden değerlendirilmesi operasyon planını netleştirir. Platform, ekip, süre ve ek hizmetlerin kapsamı yazılı teklifte belirtilir.',
            'adimlar' => [
                ['metin' => 'Saha keşfi: ağırlık, açı, engel, izin, platform tipi.'],
                ['metin' => 'Güvenlik, çalışma saati, fiyat, yazılı sözleşme.'],
                ['metin' => 'Operasyon, vinç, indirme, bırakma, kontrollü taşıyıcı.'],
                ['metin' => 'Kapanış, söküm, fatura, destek, memnuniyet.'],
            ],
            'howto_name' => 'Sepetli vinç ile ağır yük (özet)',
        ],
        283 => [
            'tanim' => 'Nakliye firması seçerken yazılı teklif, sözleşme, güvence kapsamı, hizmet koşulları, iletişim bilgileri ve görünür müşteri deneyimleri birlikte değerlendirilmelidir. MY Nakliyat yayımladığı hizmet kapsamlarını kanonik sayfalarda açıklar.',
            'surec' => 'Önce kısa araştırma ve karşılaştırma, ardından keşif; tır, ekip, ambalaj, yol, varış, montaj kalemleri tek teklifte. Sigorta ve taşıyıcı sorumluluğu satır satır okunur; taşınma bittikten sonra kontrol ve destek süreci sözleşmede tanımlanır.',
            'fayda' => 'Fiyat, kapsam, sorumluluk ve iletişim bilgilerinin yazılı karşılaştırılması belirsizliği azaltır. Belgeler, müşteri deneyimleri ve dış platform bilgileri kendi kaynaklarından doğrulanmalıdır.',
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
            'tanim' => 'İzmir’de evden eve taşınmadan önce envanter, bina erişimi, ambalaj, araç, asansör gereksinimi, takvim, fiyat ve güvence seçenekleri birlikte değerlendirilmelidir. MY Nakliyat bu başlıkları yazılı teklif aşamasında talebe göre planlar.',
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
