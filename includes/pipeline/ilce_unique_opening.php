<?php
declare(strict_types=1);

/**
 * Evden eve / yerel cluster sayfaları için açılış bloğu: birden çok paragraf ve satır aralıklı maddelî listeler
 * (başlık satırı “:” ile biter, altında maddeler) mynak_ilce_unique_opening_text_to_html() ile HTML’e dönüştürülür.
 * Metin yalnızca htmlspecialchars ile çıktı verilir.
 *
 * @return string Güvenli HTML veya boş
 */
function mynak_ilce_unique_opening_text_to_html(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $blocks = preg_split('/\R\R+/u', $text);
    $blocks = array_values(
        array_filter(
            is_array($blocks) ? $blocks : [],
            static fn (string $b): bool => trim($b) !== ''
        )
    );
    $merged = [];
    $n = count($blocks);
    for ($i = 0; $i < $n; $i++) {
        $b = trim($blocks[$i]);
        $next = $i + 1 < $n ? trim($blocks[$i + 1]) : null;
        if ($next !== null && preg_match('/^[^\r\n]+:\s*$/u', $b)) {
            $nextLines = array_values(
                array_filter(
                    array_map('trim', preg_split('/\R/u', $next)),
                    static fn (string $l): bool => $l !== ''
                )
            );
            $okMerge = false;
            if (count($nextLines) >= 2) {
                $okMerge = true;
            } elseif (count($nextLines) === 1) {
                $okMerge = mb_strlen($nextLines[0]) <= 100;
            }
            if ($okMerge) {
                $merged[] = ['type' => 'list', 'title' => $b, 'items' => $nextLines];
                $i++;
                continue;
            }
        }
        if (str_contains($b, "\n") && preg_match('/^([^\r\n]+:)\R\R(.+)$/us', $b, $m)) {
            $bodyLines = array_values(
                array_filter(
                    array_map('trim', preg_split('/\R/u', (string) $m[2])),
                    static fn (string $l): bool => $l !== ''
                )
            );
            if ($bodyLines !== []) {
                $merged[] = ['type' => 'list', 'title' => trim($m[1]), 'items' => $bodyLines];
                continue;
            }
        }
        $merged[] = ['type' => 'p', 'text' => $b];
    }
    $html = [];
    foreach ($merged as $seg) {
        if ($seg['type'] === 'p') {
            $t = (string) $seg['text'];
            $t = trim(preg_replace('/\R+/u', ' ', $t));
            $t = trim(preg_replace('/\s+/u', ' ', $t));
            if ($t === '') {
                continue;
            }
            $html[] = '<p class="mb-3 mb-md-2">' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</p>';
        } else {
            $html[] = '<p class="mb-2 text-body small fw-semibold">'
                . htmlspecialchars($seg['title'], ENT_QUOTES, 'UTF-8') . '</p>';
            $html[] = '<ul class="list-unstyled mb-3 mynak-lede-bullets ps-1 small">';
            foreach ($seg['items'] as $it) {
                if ($it === '') {
                    continue;
                }
                $html[] = '<li class="mb-1 ps-1"><span class="text-primary" aria-hidden="true">·</span> '
                    . htmlspecialchars($it, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $html[] = '</ul>';
        }
    }
    return implode("\n", $html);
}

function mynak_ilce_unique_opening_html(string $slug): string
{
    $k = mb_strtolower(trim($slug), 'UTF-8');
    /** @var array<string, string> */
    $map = mynak_ilce_unique_opening_map();
    if (!isset($map[$k])) {
        return '';
    }
    $text = $map[$k];
    if ($text === '') {
        return '';
    }
    $body = mynak_ilce_unique_opening_text_to_html($text);
    if ($body === '') {
        return '';
    }
    return '<aside class="mynak-local-lede alert alert-light border-0 border-start border-4 border-primary shadow-sm mb-4" role="note">'
        . '<div class="lead fs-6">' . $body . '</div>'
        . '</aside>';
}

/**
 * @return array<string, string> slug (veritabanı ile birebir) => metin
 */
function mynak_ilce_unique_opening_map(): array
{
    return [
        'aliaga-evden-eve-nakliyat' => 'Aliağa’da evden eve nakliyat, sanayi ve liman trafiğiyle belirlenir; ağır araç hareketi şehir içi zamanlamayı riskli kılar.

Taşınma “saat değil, trafik penceresi” ile planlanır.

MY Nakliyat dinamik rota, sanayi akışına göre çıkış noktası ve ekip konumunu aynı operasyon çizgisinde toplar.

Hizmet yaklaşımı:

Evden eve nakliyat (sanayi trafiğine göre planlı)
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık (yüksek katlı yapılarda)',

        'balcova-evden-eve-nakliyat' => 'Balçova’da “erişim planlaması” gerekir; site kuralları ve asansör saatleri operasyon hızını belirler.

MY Nakliyat bina yönetimiyle ilerler; gün, önceden netleşen erişim planıyla, bekleme olmadan akar.

Hizmet yapısı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık (yüksek kat avantajı)',

        'bayındır-evden-eve-nakliyat-hizmetleri' => 'Bayındır’da yarı kırsal lojistik modeli geçerli; değişken mesafe, standart süre planını çoğu kez dışlar.

MY Nakliyat “mesafe tabanlı akış” uygular: rota, erişim noktaları ve gerekirse etaplar önceden belli.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık (uygun yapılarda)',

        'bayrakli-evden-eve-nakliyat' => 'Bayraklı’da yüksek katlı yapı, operasyonu dikey lojistiğe çevirir; asansör ve kat planı trafikten öne çıkar.

MY Nakliyat kat bazlı ilerletir: asansör pencereleri, taşıma sırası ve ekip yönlendirmesi, bina içi kaybı kısar.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık (yüksek kat standardı)',

        'bergama-evden-eve-nakliyat' => 'Bergama’da tarihi doku ve yeni mahalleler iç içe; dar sokak veya mesafe, erişimi farklı kılar.

MY Nakliyat taşımayı, yapı tipine göre lojistik plana bağlar: bina erişimi, araç konumu ve akış taslakta netleşir.

Hizmet yapısı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık (yüksek katlı yapılarda)',

        'beydag-evden-eve-nakliyat' => 'Beydağ’da kırsal yapı ve mesafeler şehir içi modelden ayrışır; süreyi yol ve ev aralığı belirler.

MY Nakliyat rota ve erişimi kilitler: güzergâh, yükleme noktası ve akış, kontrollü ilerler.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık (uygun yapılarda)',

        'bornova-evden-eve-nakliyat' => 'Bornova’da öğrenci ve sürekli hareket, yüksek tempolu lojistik ister; kısa dönem taşınmalar planı gerektirir.

MY Nakliyat hızlı keşif ve hazır operasyon modeliyle ilerler: ekip, bina erişimi ve gün, minimum bekleme için hizalanır.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık (özellikle yüksek katlı bölgelerde)',

        'buca-evden-eve-nakliyat' => 'Buca’da evden eve nakliyat, yoğun yerleşim ve öğrenci hareketi nedeniyle sabit operasyon modeliyle yönetilemez; gün içinde aynı mahallede bile trafik ve erişim değişir.

Kritik mesele araç erişimidir: birçok sokakta bina önü yok, süreç yalnızca yükleme–boşaltma değil ara lojistik akış yönetimidir.

MY Nakliyat çok katmanlı planlama yürütür: erişim noktası, bina içi rota ve ekip sahada buna göre; yüksek katlarda asansörlü taşımacılık süreci hızlandırır. Amaç, yoğun yapıda operasyonu kesintisiz ve kontrollü tutmaktır.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'cesme-evden-eve-nakliyat' => 'Çeşme’de sezonluk yoğunluk değişir; yaz trafiği, site ve erişim saatleri operasyonu yönetir.

MY Nakliyat sezon ve saat planı kurgular; villa ve büyük hacim, saatle birlikte netleşir.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'cigli-evden-eve-nakliyat' => 'Çiğli’de site, sanayi ve konut iç içe; değişken trafik, çift yönlü ve esnek plan ister.

MY Nakliyat rota ve zamanı optimize eder: bina erişimi, araç saatleri ve ekip. Yüksek katlarda asansörlü taşımacılık ile süre kısalır.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'dikili-evden-eve-nakliyat' => 'Dikili’de yaz hareketi sezonluk fark yaratır; sahil hattı yoğunluğu zamanlamayı etkiler.

MY Nakliyat sezona ve trafiğe göre saatleri ayarlar; büyük hacimde asansörlü taşımacılık operasyonu kısaltır.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'evden-eve-nakliyat' => 'İzmir’de her ilçenin zorluğu ayrıdır: Konak trafiği, Bayraklı yüksek kat, Urla mesafesi, Bornova yoğunluğu ayrı ayrı yönetilmeli.

MY Nakliyat bölgesel planlama yürütür: ilçe başına rota, ekip ve zamanlama; yüksek katlarda asansörlü taşımacılık, yoğunlukta saat penceresi.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'foca-evden-eve-nakliyat' => 'Foça’da dar sokak ve kıyı, araç her eve kadar sokmaz; yaz trafiği de değişkendir.

MY Nakliyat etaplı model ve gerekirse ara lojistik noktayla operasyonu kesintisiz tutar.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'gaziemir-evden-eve-nakliyat' => 'Gaziemir’de sanayi ve konut aynı hatta; ağır ve bireysel trafik, plan zorunlu kılar.

MY Nakliyat saat analizi ve yedek rota ile ilerler; yüksek katlarda asansörlü taşımacılık, süre ve güveni dengeler.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'guzelbahce-evden-eve-nakliyat' => 'Güzelbahçe’de villa, bahçe ve büyük hacim, standart daire taşımasına dönük değil; erişim ve iç mesafe süreyi yönetir.

MY Nakliyat alan bazlı lojistik kurgular: konum, yükleme bölümleri, hacimde asansörlü taşımacılık. Amaç, geniş alanı kontrollü operasyon fırsatına çevirmek.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'izmir-evden-eve-nakliyat' => 'İzmir’de her ilçe ayrı dinamik: Konak trafiği, Bayraklı kat yükü, Urla mesafesi, Bornova hareketi, farklı operasyon ister.

MY Nakliyat bölgesel rota, ekip ve saat penceresini ayrı açar: yüksek katlarda asansörlü taşımacılık, yoğunlukta saat planı. Amaç, bölgeye göre optimize, profesyonel taşımacılık.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'izmir-evden-eve-nakliyat-tavsiyeleri' => 'İzmir’de en kritik mesele doğru plan: trafik, bina ve erişim süreyi yönetir.

Dikkat edilmesi gerekenler:

Keşif öncesi
Bina erişim analizi
Saat bazlı plan
Eşyaya göre paketleme
Yüksek katta asansörlü taşımacılık

MY Nakliyat süreci planlanmış operasyon sayar; parça eşya, ofis taşımacılığı ve şehirler arası ile bütüncül ilerler.',

        'karabaglar-evden-eve-nakliyat' => 'Karabağlar’da apartman ve dar sokak yoğun; eş zamanlı taşınmalar, planı zorlaştırabilir.

MY Nakliyat bina bazlı ilerir: yükleme sırası, kat, araç erişimi; yüksek katlarda asansörlü taşımacılık, yoğun yapıda kontrollü ve kesintisiz akış hedefi.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'karsiyaka-evden-eve-nakliyat' => 'Karşıyaka’da site, giriş ve asansör planı, zamanı belirler; prosedür siteye göre değişir.

MY Nakliyat saat, site yönetimi ve asansörlü ekipmanı önden hizalar; hedef, zaman kaybı olmadan kontrollü, premium taşımacılık.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'konak-evden-eve-nakliyat' => 'Konak’ta trafik ve erişim kısıtı, standart modeli zorlaştırır; dar sokak ve tek yönlü akış belirleyicidir.

MY Nakliyat entegre lojistikle ilerir: rota, bina önü penceresi ve ekip, gecikmesiz, kontrollü operasyonu hedefler.

Hizmet sadece ev taşımayla sınırlı değildir:

Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama çözümleri',

        'menderes-evden-eve-nakliyat' => 'Menderes’te geniş alan ve değişken mesafe; ev aralığı süre üretir.

MY Nakliyat mesafe tabanlı planlar; araç ve etap, kontrolsüz zaman kaybı olmadan ilerletilir.

Sunulan hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'seferihisar-evden-eve-nakliyat' => 'Seferihisar’da geniş ve dağınık yerleşim; villa ve mesafe, planı açar.

MY Nakliyat alan ve erişim yönetimini üstlenir: yükleme noktası, kontrollü akış; hız değil düzen, asansörlü taşımacılık ise ihtiyaca göre hız katar.

Hizmet kapsamı:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'torbali-evden-eve-nakliyat' => 'Torbalı’da sanayi ve ağır trafik, rota ve saati yönetir; gün içi akış değişkendir.

MY Nakliyat saat analizi ve yedek rota ile ilerir; hedef, sanayi hattında kesintisiz operasyon, yüksek katlarda asansörlü taşımacılık.

Sunulan hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'urla-evdeneve-nakliyat' => 'Urla’da dağınık yerleşim ve mesafe, standart modele uymaz; adımlar arası süre oynar.

MY Nakliyat etaplı lojistik kurgular: konum, yükleme ve erişim önceden belli, mesafeye bağlı gecikmeyi azaltma hedefi; yüksek katlarda asansörlü taşımacılık.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',
    ];
}
