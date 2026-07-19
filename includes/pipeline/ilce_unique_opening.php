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
        'aliaga-evden-eve-nakliyat' => 'Aliağa, İzmir’in kuzeyinde denize kıyısı olan bir ilçedir; ama kimliğini büyük ölçüde limanından, rafinerisinden ve ağır sanayi tesislerinden alır. İlçede yaşayanların önemli bir bölümü bu tesislerde çalışır ve gün çoğu zaman vardiyalara göre şekillenir.

Sanayi bölgelerine giden yollarda ağır araç trafiği yoğundur. Konut alanları ise merkezde ve yeni sitelerde toplanır.

Bir taşınmada hem bu trafiği hem de vardiya saatlerini hesaba katmak, planı gerçeğe daha yakın hâle getirir.

Aliağa’da bir taşınmayı, ilçenin sanayi temposu ve yol yoğunluğuyla birlikte ele alıyoruz.',

        'balcova-evden-eve-nakliyat' => 'Balçova, İzmir merkezine çok yakın, sınırları dar ama günlük yaşamı hareketli bir ilçedir. Termal kaplıcaları, teleferiği ve üniversitesiyle tanınır; konut dokusu ağırlıkla sitelerden oluşur.

Site yaşamının yaygın olması, taşınmada asansör sırasını ve site giriş kurallarını öne çıkarır.

Hangi sitede ve kaçıncı katta oturulduğu önceden bilinirse, uygun ekip ve saat buna göre ayarlanabilir.

Balçova’da evden eve taşımayı, site düzenine ve merkeze yakın konumuna göre planlıyoruz.',

        'bayındır-evden-eve-nakliyat-hizmetleri' => 'Bayındır, İzmir’in doğusunda, çiçek ve fidan yetiştiriciliğiyle tanınan bir tarım ilçesidir. İlçe merkezi küçük ve sakindir; çevresi ise seralara, bahçelere ve köylere yayılır.

Yerleşimin dağınık olması ve merkeze mesafe, taşınmada yol süresini belirleyen başlıca konudur.

Çıkış ve varış adreslerinin köyde mi yoksa merkezde mi olduğunu önceden konuşmak, günün planını netleştirir.

Bayındır’daki taşınmalarda ilçenin tarımsal ve dağınık yerleşim yapısını göz önünde tutuyoruz.',

        'bayrakli-evden-eve-nakliyat' => 'Bayraklı, son yıllarda yüksek katlı iş kuleleri ve rezidanslarla öne çıkan, aynı zamanda eski mahalleleri de bulunan bir ilçedir.

Yeni yapılarda asansör ve otopark düzeni belirleyiciyken, eski mahallelerde sokak genişliği ve kat sayısı öne çıkar.

Yeni bir rezidansta mı yoksa eski bir binada mı oturulduğu, hazırlanacak ekipmanı doğrudan etkiler.

Bayraklı’da evden eve taşımayı, binanın türüne ve bulunduğu bölgeye göre planlıyoruz.',

        'bergama-evden-eve-nakliyat' => 'Bergama, antik Pergamon’un mirasını taşıyan, tarih ve turizmle iç içe köklü bir ilçedir. İlçenin kendine ait hareketli bir merkezi vardır; çevresinde ise tarım ve kırsal mahalleler yer alır.

Tarihi merkezdeki eski ve dar sokaklar ile ilçenin İzmir’e uzaklığı, taşınmada araç erişimini ve yol süresini öne çıkarır.

Aracın adrese ne kadar yaklaşabildiğini ve mesafeyi baştan bilmek, planı netleştirir.

Bergama’da bir taşınmayı, tarihi merkezin erişim koşulları ve ilçe mesafesiyle birlikte değerlendiriyoruz.',

        'beydag-evden-eve-nakliyat' => 'Beydağ, İzmir’in doğu ucunda, dağlık ve büyük ölçüde kırsal bir ilçedir. Nüfus azdır; yerleşim, küçük ilçe merkezi ile dağ köyleri arasında dağılır.

Şehir merkezine uzaklık ve köyleri bağlayan yolların yapısı, bir taşınmada en çok yol süresini ve güzergâhı belirler.

Adresin merkezde mi yoksa yukarı köylerden birinde mi olduğu, günün nasıl planlanacağını doğrudan değiştirir.

Beydağ’da bir taşınmayı, ilçenin dağlık yapısı ve mesafesiyle birlikte ele alıyoruz.',

        'bornova-evden-eve-nakliyat' => 'Bornova, İzmir’in doğusunda yer alan büyük ve kalabalık bir ilçedir. Üniversite çevresindeki öğrenci nüfusu, eski mahalleler ve yeni yükselen siteler bir arada bulunur.

Bazı sokaklar dar ve tek yönlüdür; yeni site bölgelerinde ise giriş ve asansör düzeni farklıdır.

Adresin hangi tür bir yerleşimde olduğunu önceden bilmek, taşınma saatini ve ekibi buna göre ayarlamaya yardımcı olur.

Bornova’da evden eve taşımayı, mahallenin yapısına ve adresin durumuna göre planlıyoruz.',

        'buca-evden-eve-nakliyat' => 'Buca, İzmir’in en kalabalık ilçelerinden biridir; üniversite kampüsleri ve öğrenci nüfusu yıl boyunca hareketli bir yerleşim oluşturur.

Apartmanların sık olduğu mahallelerde sokaklar dardır; bazı adreslerde taşıma aracı binanın önüne kadar yaklaşamaz.

Bu yüzden aracın nereye kadar girebildiğini ve kat durumunu önceden bilmek, taşınma gününü daha rahat planlamaya yardımcı olur.

Buca’da evden eve taşımayı, mahallenin yoğunluğuna ve adresin erişimine göre planlıyoruz.',

        'cesme-evden-eve-nakliyat' => 'Çeşme, yılın büyük bölümünde sakin; yaz aylarında ise nüfusu ve trafiği belirgin biçimde artan bir ilçedir. Konutların önemli kısmı yazlık ve site tipindedir.

Bu mevsimsel fark taşınmayı doğrudan etkiler: yaz döneminde yollar yoğunlaşır, site giriş saatleri ve otopark sınırlı olur.

Taşınmanın hangi mevsimde ve günün hangi saatinde yapılacağını önceden belirlemek, bu yoğunluğu yönetmeye yardımcı olur.

Çeşme’de evden eve taşımayı, sezon durumuna ve site erişim koşullarına göre planlıyoruz.',

        'cigli-evden-eve-nakliyat' => 'Çiğli, İzmir’in kuzeyinde; büyük konut siteleri, sanayi bölgeleri ve açık alanların bir arada bulunduğu geniş bir ilçedir.

Site bölgelerinde giriş saatleri ve asansör, sanayiye yakın yollarda ise araç trafiği taşınmayı etkiler.

Yükleme noktasının bir site içinde mi yoksa daha açık bir alanda mı olduğu, günün planını değiştirir.

Çiğli’de evden eve taşımayı, ilçenin bu karma yapısına göre planlıyoruz.',

        'dikili-evden-eve-nakliyat' => 'Dikili, sahil kesiminde yazlık hareketliliğin, iç kesimlerde ise tarımın öne çıktığı bir ilçedir. Kıyıdaki yerleşim yaz aylarında dolar, kış aylarında seyrelir.

Yaz döneminde sahil yolundaki yoğunluk ve ilçenin İzmir’e mesafesi, taşınmada zamanlamayı önemli kılar.

Adresin sahilde mi yoksa iç mahallelerde mi olduğunu bilmek, güzergâhı ve saati netleştirir.

Dikili’deki taşınmalarda mevsim yoğunluğunu ve ilçenin konumunu göz önünde tutuyoruz.',

        'evden-eve-nakliyat' => 'İzmir’de her ilçenin zorluğu ayrıdır: Konak trafiği, Bayraklı yüksek kat, Urla mesafesi, Bornova yoğunluğu ayrı ayrı yönetilmeli.

MY Nakliyat bölgesel planlama yürütür: ilçe başına rota, ekip ve zamanlama; yüksek katlarda asansörlü taşımacılık, yoğunlukta saat penceresi.

Hizmetler:

Evden eve nakliyat
Parça eşya taşıma
Ofis taşımacılığı
Şehirler arası nakliyat
Eşya depolama
Asansörlü taşımacılık',

        'foca-evden-eve-nakliyat' => 'Foça, eski ve dar sokaklı yerleşimiyle bilinen bir sahil ilçesidir; bu doku, taşınmada aracın her kapıya kadar yaklaşamamasına yol açabilir. Yaz aylarında ilçenin hareketi belirgin biçimde artar.

Dar sokaklarda taşıma çoğu zaman aracın durabildiği en yakın noktadan yürütülür; bu da ek taşıma mesafesi anlamına gelir.

Adresin sokak genişliğini ve aracın nereye kadar girebileceğini önceden bilmek, günün planını gerçekçi kılar.

Foça’da bir taşınmayı, dar sokak erişimi ve sezon yoğunluğuyla birlikte değerlendiriyoruz.',

        'gaziemir-evden-eve-nakliyat' => 'Gaziemir, İzmir’in güneyinde, havalimanına ve şehir merkezine yakın bir ilçedir. Sanayi tesisleri ile konut alanları çoğu yerde iç içe geçmiştir.

Bu karışık doku, hem yük araçlarının hem de günlük trafiğin aynı yollarda yoğunlaşmasına yol açar.

Taşınma saatini bu trafiğe göre seçmek, gün içinde beklenmedik gecikmeleri azaltmaya yardımcı olur.

Gaziemir’de evden eve taşımayı, sanayi ile konutun iç içe olduğu bu yapıya göre planlıyoruz.',

        'guzelbahce-evden-eve-nakliyat' => 'Güzelbahçe’de konutların önemli bölümü bahçeli ev ve villadır; siteler de yaygındır. Nüfus görece düşük, yerleşim ferahtır.

Bahçeli ev ve villalarda eşya hacmi çoğu zaman yüksektir; bu da taşımada daha fazla planlama gerektirir.

Adresin bir villa mı yoksa site içinde bir daire mi olduğunu bilmek, ekip ve araç seçimini belirler.

Güzelbahçe’de evden eve taşımayı, konut tipine ve eşya hacmine göre planlıyoruz.',

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

        'karabaglar-evden-eve-nakliyat' => 'Karabağlar’da sokaklar dar, apartmanlar sıktır; İzmir’in en kalabalık ilçelerinden biridir. Aynı gün, aynı çevrede birden fazla taşınma yaşanabilir.

Böyle bir yoğunlukta en kritik konu, aracın binaya yakın durabileceği bir yer bulmaktır.

Taşınma saatini trafiğin ve park durumunun daha uygun olduğu bir zamana denk getirmek işi kolaylaştırır.

Karabağlar’da evden eve taşımayı, yoğun apartman dokusuna ve park koşullarına göre planlıyoruz.',

        'karsiyaka-evden-eve-nakliyat' => 'Karşıyaka, İzmir’in sahilinde yer alan; hem yoğun çarşı bölgeleri hem de siteleşmiş konut alanları olan bir ilçedir.

Sitelerde asansörün uygunluğu ve giriş saatleri, çarşıya yakın kalabalık sokaklarda ise araç erişimi taşınmayı etkiler.

Adresin bir site içinde mi yoksa cadde üstü bir binada mı olduğunu önceden bilmek, uygun ekip ve saati belirlemeyi kolaylaştırır.

Karşıyaka’da evden eve taşımayı, binanızın türüne ve çevresinin yoğunluğuna göre planlıyoruz.',

        'konak-evden-eve-nakliyat' => 'Konak, İzmir’in tarihi ve ticari merkezidir; Kemeraltı çevresindeki dar, çoğu zaman tek yönlü sokaklar ilçenin dokusunu belirler.

Gün içinde çarşı ve sahil trafiği yoğunlaşır, bazı adreslere araçla yaklaşmak sınırlı kalır.

Bu yüzden yükleme için uygun saati ve aracın durabileceği noktayı önceden belirlemek önem taşır.

Konak’ta evden eve taşımayı, merkezin yoğunluğuna ve sokakların erişim durumuna göre planlıyoruz.',

        'menderes-evden-eve-nakliyat' => 'Menderes çok geniş bir alana yayılır; havalimanı çevresinden kıyıdaki Özdere ve Gümüldür’e kadar farklı yerleşimleri içine alır.

Bu yüzden taşınmada ilk belirleyici şey, adresin ilçenin hangi bölgesinde olduğudur; merkez, köy ve kıyı arasındaki mesafe epeyce değişir.

Çıkış ve varış noktalarını baştan netleştirmek, yol süresini ve güzergâhı öngörülebilir kılar.

Menderes’te bir taşınmayı, adresin bulunduğu bölgeye ve mesafeye göre ele alıyoruz.',

        'menemen-evden-eve-nakliyat' => 'Menemen, İzmir’in kuzeyinde ovaya yayılan bir ilçedir. Yapılaşma çoğunlukla az katlı; son yıllarda ilçe merkezine yeni siteler de eklendi.

Şehir merkezine mesafe ve çevredeki sanayi yollarının araç yoğunluğu, taşınma planını etkileyen başlıca etkenlerdir.

Bu nedenle çıkış saatini şehir içindeki bir taşınmadan farklı düşünmek, günün akışını daha öngörülebilir kılar.

Menemen’de evden eve taşımayı bu mesafe ve trafik koşullarına göre sizinle birlikte planlıyoruz.',

        'narlidere-evden-eve-nakliyat' => 'Narlıdere denince akla önce deniz kıyısı ve sahil boyunca yükselen siteler gelir; yukarı mahallelere çıkıldıkça sokaklar yokuşlu ve eğimli hâle gelir.

Bu iki farklı yerleşim biçimi taşınmayı doğrudan etkiler: sitelerde asansör uygunluğu ve giriş saatleri, yamaç mahallelerde ise aracın eve yaklaşabildiği mesafe önem taşır.

Adresin özelliklerini önceden konuşmak, hangi ekip ve ekipmanın uygun olacağını netleştirir.

Narlıdere’de evden eve taşımayı, binanızın ve sokağınızın bu koşullarına göre planlıyoruz.',

        'karaburun-evden-eve-nakliyat' => 'Karaburun, İzmir’in en batısına uzanan ince ve uzun bir yarımadadır. Yerleşim, kıyı boyunca dağılmış köyler ve küçük merkezlerden oluşur; arazi genellikle engebelidir.

Şehir merkezine olan uzaklık ve kıvrımlı sahil yolu, bir taşınmada mesafeyi ve zamanlamayı öne çıkarır.

Bu koşullar, yol güzergâhının ve varış saatinin işin başında konuşulmasını gerektirir.

Karaburun’a ya da yarımada içindeki bir adrese taşınmada yolu ve mesafeyi önceden birlikte konuşmayı tercih ediyoruz.',

        'kemalpasa-evden-eve-nakliyat' => 'Doğuda, Nif Dağı’nın eteğinde kurulu Kemalpaşa, tarım alanları ile organize sanayi bölgelerini bir arada barındırır. İlçede hem kırsal mahalleler hem de sanayiye yakın konut bölgeleri vardır.

Sanayi yollarındaki ağır araç trafiği ve ilçenin geniş yayılımı, taşınma gününün planında etkilidir.

Çıkış ve varış noktalarının konumu, güzergâhın baştan belirlenmesinde belirleyici olur.

Kemalpaşa’daki taşınmalarda ilçenin bu tarım ve sanayi dokusunu hesaba katarak ilerliyoruz.',

        'kinik-evden-eve-nakliyat' => 'İzmir’in kuzey ucundaki Kınık, büyük ölçüde kırsal ve tarıma dayalı bir ilçedir. Yerleşim, ilçe merkezi ile çevredeki köyler arasında dağılmıştır.

Şehir merkezine uzaklık ve köyler arası mesafeler, taşınmada yol süresini belirleyen temel unsurdur.

Bu yüzden güzergâh ve zamanlama, işin başında netleştirilir.

Kınık’ta evden eve taşımanın planını bu kırsal yapıya ve mesafeye göre çıkarıyoruz.',

        'kiraz-evden-eve-nakliyat' => 'Kiraz, ilin doğu sınırına yakın, dağlık bir bölgede yer alır. İlçe merkezi ile dağ köyleri arasında yükseklik ve yol farkları belirgindir.

Engebeli arazi ve şehir merkezine uzaklık, bir taşınmada güzergâh seçimini öne çıkarır.

Yolun durumu ve varış noktasının konumu, planlamanın önceden yapılmasını gerektirir.

Kiraz’da bir taşınmayı, bölgenin dağlık yapısı ve mesafesiyle birlikte değerlendiriyoruz.',

        'odemis-evden-eve-nakliyat' => 'Küçük Menderes Ovası’nın ortasındaki Ödemiş, kendi kent merkezi bulunan köklü ve büyük bir ilçedir. Merkezde yoğun bir yerleşim, çevrede ise geniş tarım alanları görülür.

İlçenin İzmir merkezine uzaklığı ve kendi içindeki hareketli merkez trafiği, taşınmayı iki ayrı açıdan etkiler.

Adresin merkezde mi yoksa kırsalda mı olduğuna göre güzergâh ve zamanlama değişir.

Ödemiş’te ilçe içi ve şehirler arası taşımaları bu ölçeğe göre ele alıyoruz.',

        'selcuk-evden-eve-nakliyat' => 'Selçuk, Efes’in hemen yanında, tarih ve turizmle iç içe geçmiş küçük bir ilçedir. İlçe merkezi kompakt, çevresi ise tarım alanları ve ören yerleriyle çevrilidir.

Turizm sezonundaki hareketlilik ve merkezdeki dar yollar, taşınma gününün zamanlamasını etkiler.

Sezonun ve saatin önceden konuşulması, güzergâhın buna göre ayarlanmasına imkân verir.

Selçuk’ta taşınma planını sezon ve merkezdeki yol durumuna göre yapıyoruz.',

        'tire-evden-eve-nakliyat' => 'Tire, dar sokaklı tarihi merkeziyle ve çevresindeki tarım alanlarıyla tanınan bir ilçedir. Eski mahallelerde sokaklar dar, çevre mahallelerde ise yerleşim daha dağınıktır.

Tarihi merkezdeki dar geçitler ve şehir merkezine uzaklık, bir taşınmada araç erişimini öne çıkarır.

Aracın adrese ne kadar yaklaşabildiğinin önceden bilinmesi, planı netleştirir.

Tire’de evden eve taşımada tarihi merkezin erişim koşullarını göz önünde tutuyoruz.',

        'seferihisar-evden-eve-nakliyat' => 'Seferihisar, düşük katlı yapısı ve geniş bir alana yayılan mahalleleriyle öne çıkar; kıyıdaki Sığacık çevresinde yazlık hareketi artar.

Düşük katlı evlerde asansör çoğu zaman gündeme gelmez; asıl konu, dağınık yerleşimde adresler arasındaki mesafedir.

Yaz aylarında kıyı bölgesindeki yoğunluk da taşınma saatini etkiler.

Seferihisar’daki taşınmalarda dağınık yerleşimi ve kıyının sezon yoğunluğunu göz önünde tutuyoruz.',

        'torbali-evden-eve-nakliyat' => 'Torbalı, İzmir’in güneyinde geniş bir ovaya kuruludur; büyük sanayi bölgeleri ile tarım alanlarını bir arada barındırır ve kendi hareketli bir merkezi vardır.

Sanayi yollarındaki ağır araç trafiği ve ilçenin şehir merkezine uzaklığı, taşınmada yol süresini öne çıkarır.

Çıkış ve varış noktalarını önceden konuşmak, günün nasıl ilerleyeceğini daha net gösterir.

Torbalı’da evden eve taşımayı, ilçenin sanayi ile tarım dokusuna ve mesafesine göre planlıyoruz.',

        'urla-evdeneve-nakliyat' => 'Urla’da eski taş evlerin bulunduğu dar sokaklı tarihi merkez ile yeni villa ve site bölgeleri bir arada bulunur. Kıyıdaki İskele çevresi ise yaz aylarında hareketlenir.

Bu yüzden taşınmada adresin tarihi merkezde mi, kıyıda mı yoksa yeni yerleşimlerde mi olduğu belirleyicidir; sokak genişliği ve mesafe buna göre değişir.

Tarihi merkezde araç çoğu zaman kapının önüne kadar giremez; yeni bölgelerde erişim daha rahattır.

Urla’da bir taşınmayı, adresin bulunduğu bölgeye ve sokak yapısına göre planlıyoruz.',
    ];
}
