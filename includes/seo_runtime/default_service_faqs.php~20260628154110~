<?php
declare(strict_types=1);

/**
 * Hizmet sayfaları için varsayılan SSS (FAQPage) içeriği.
 *
 * Hizmet sayfasının HTML gövdesinde "H2/H3 soru — paragraf cevap" yapısı
 * yoksa bu fallback devreye girer; her hizmet için 4-6 yapay zeka
 * dostu Q&A üretilir.
 *
 * Idempotent yükleme.
 */

if (defined('MYNAK_SEO_DEFAULT_SERVICE_FAQS_LOADED')) {
    return;
}
define('MYNAK_SEO_DEFAULT_SERVICE_FAQS_LOADED', true);

/**
 * Slug → Q/A map.
 *
 * @return array<string, list<array{q:string,a:string}>>
 */
function seo_runtime_default_service_faq_map(): array
{
    return [
        'fiyat' => [
            [
                'q' => 'İzmir evden eve nakliyat fiyatı nasıl hesaplanır?',
                'a' => 'Eşya hacmi (m³), kat sayısı, asansör veya asansörlü vinç ihtiyacı, taşıma mesafesi, paketleme kapsamı ve sigorta tercihi birlikte değerlendirilir. Keşif sonrası yazılı teklif sunulur.',
            ],
            [
                'q' => '1+1 daire taşıma İzmir\'de ne kadar?',
                'a' => '2026 için İzmir içi 1+1 taşımalarda tipik aralık 8.000 – 14.000 TL bandındadır. Eşya yoğunluğu, kat ve paketleme ile değişir; kesin fiyat keşif sonrası netleşir.',
            ],
            [
                'q' => 'Keşif ücretsiz mi?',
                'a' => 'Evet. Yerinde veya görüntülü keşif ücretsizdir; teklifi kabul etmek zorunda değilsiniz.',
            ],
            [
                'q' => 'Asansörlü taşıma ek ücreti ne kadar?',
                'a' => 'Genelde 2.000 – 6.000 TL aralığında eklenir; bina yüksekliği ve vinç süresine bağlıdır.',
            ],
            [
                'q' => 'Şehirler arası taşıma fiyatı ne kadar?',
                'a' => 'İzmir çıkışlı şehirler arası taşımalarda km ve m³ birlikte hesaplanır. İstanbul/Ankara gibi güzergâhlarda 18.000 TL’den başlayan bandlar görülür.',
            ],
        ],
        'izmir-evden-eve-nakliyat-hizmeti' => [
            [
                'q' => 'İzmir evden eve nakliyat fiyatı nasıl belirlenir?',
                'a' => 'Fiyatlandırma; eşya hacmi (m³), kat ve asansör durumu, taşıma mesafesi, paketleme kapsamı ve sigorta seçeneklerine göre yapılır. Ücretsiz ekspertiz sonrası yazılı, net bir teklif sunulur; sonradan ek ücret çıkarılmaz.',
            ],
            [
                'q' => 'Evden eve nakliyatta ekspertiz nedir, ücretli mi?',
                'a' => 'Ekspertiz, eşyalarınızın yerinde incelenerek hacim, paketleme ve taşıma planının çıkarıldığı ön değerlendirmedir. My Nakliyat\'ta ekspertiz tamamen ücretsizdir ve hiçbir bağlayıcılığı yoktur.',
            ],
            [
                'q' => 'Paketleme ve ambalajlama hizmete dahil mi?',
                'a' => 'Paketleme hizmeti talep edilirse pakete dahildir. Mobilyalar streç ve havalı naylon, kırılacaklar köpük dolgulu kutular, beyaz eşyalar çift kat ambalajla profesyonel olarak korunur.',
            ],
            [
                'q' => 'Taşıma sırasında eşyalar sigortalı mı?',
                'a' => 'Tüm taşıma süreçlerinde sözleşmeli hizmet sunulur ve talebe göre eşya sigortası eklenir. Sigorta kapsamı, koşulları ve limiti yazılı teklif aşamasında müşteriyle paylaşılır.',
            ],
            [
                'q' => 'Evden eve taşıma kaç saatte tamamlanır?',
                'a' => 'Standart bir 3+1 dairenin taşıması — paketleme dahil — ortalama 6 ile 10 saat sürer. Asansör kullanımı, mesafe ve eşya yoğunluğu süreyi etkiler; planlama ekspertiz sırasında netleşir.',
            ],
            [
                'q' => 'İzmir\'in hangi ilçelerine evden eve nakliyat hizmeti veriyorsunuz?',
                'a' => 'Bornova, Buca, Karşıyaka, Konak, Gaziemir, Çiğli, Bayraklı, Karabağlar, Balçova, Narlıdere, Güzelbahçe, Menderes, Torbalı, Urla, Çeşme ve diğer tüm İzmir metropol ilçelerinde hizmet veriyoruz.',
            ],
        ],
        'sehirlerarasi-nakliyat' => [
            [
                'q' => 'Şehirler arası nakliyat fiyatı nasıl hesaplanır?',
                'a' => 'Şehirler arası fiyat; eşya hacmi, alış–varış mesafesi, yakıt maliyeti, kat/asansör durumu ve ek hizmetlere (paketleme, sigorta, depolama) göre belirlenir. Ücretsiz ekspertiz sonrası yazılı teklif verilir.',
            ],
            [
                'q' => 'İzmir-İstanbul, İzmir-Ankara taşıma kaç günde teslim edilir?',
                'a' => 'Şehirler arası taşımalarda eşyalarınız ortalama 1-2 iş günü içinde teslim edilir. Tek seferlik özel araç tahsis edilerek başka eşyayla karıştırılmadan, kapıdan kapıya hizmet verilir.',
            ],
            [
                'q' => 'Şehirler arası taşımada eşyam başka müşteri eşyalarıyla karışır mı?',
                'a' => 'Hayır. My Nakliyat\'ta her müşteri için özel araç tahsisi yapılır; eşyalarınız asla başka eşyalarla karıştırılmaz, transfer veya aktarma yapılmaz.',
            ],
            [
                'q' => 'Hangi şehirlere taşıma hizmeti veriyorsunuz?',
                'a' => 'Türkiye\'nin 81 iline şehirler arası nakliyat hizmeti veriyoruz. Sık güzergahlarımız: İzmir-İstanbul, İzmir-Ankara, İzmir-Bursa, İzmir-Antalya, İzmir-Muğla, İzmir-Aydın ve İzmir-Manisa.',
            ],
            [
                'q' => 'Şehirler arası taşımada sigorta nasıl çalışır?',
                'a' => 'Şehirler arası taşımalarda sözleşmeli hizmet sunulur ve talebe göre eşya değer sigortası eklenir. Kapsam, muafiyet ve limit yazılı teklif aşamasında müşteriyle netleştirilir.',
            ],
        ],
        'kurumsal-nakliye-ofis-tasima' => [
            [
                'q' => 'Ofis taşıma süreci nasıl planlanır?',
                'a' => 'Ofis taşımacılığında ön ekspertiz, demontaj-paketleme planı, iş günü içinde minimum aksaklıkla taşıma, yeni adreste montaj ve yerleştirme adımları izlenir. Hafta sonu ve gece taşıması da yapılabilir.',
            ],
            [
                'q' => 'Ofis eşyalarının demontaj ve montajı hizmete dahil mi?',
                'a' => 'Evet. Çalışma masaları, dolaplar, bölme paneller, toplantı masaları profesyonel ekiplerimizce demonte edilir, taşınır ve yeni adreste eksiksiz monte edilir.',
            ],
            [
                'q' => 'Bilgisayar, sunucu ve elektronik ekipman güvenli taşınır mı?',
                'a' => 'IT ekipmanları için antistatik balon ambalaj ve özel taşıma kutuları kullanılır. Sunucu kabinleri için titreşim emici sandık ve özel sabitleme sistemleri uygulanır.',
            ],
            [
                'q' => 'Hafta sonu veya mesai dışı ofis taşıması yapıyor musunuz?',
                'a' => 'Evet. İş akışınızı kesintiye uğratmamak için Cuma akşamı – Pazar günleri içinde veya mesai sonrası gece taşımacılığı planlanabilir.',
            ],
            [
                'q' => 'Kurumsal müşteriler için fatura ve sözleşme süreci nasıl işler?',
                'a' => 'Tüm kurumsal hizmetlerde yazılı sözleşme imzalanır, e-fatura kesilir. KOBİ ve kurumsal müşterilere özel anlaşmalı tarifeler ve aylık fatura seçenekleri sunulur.',
            ],
        ],
        'kurumsal-nakliye-hizmetleri' => [
            [
                'q' => 'Kurumsal nakliye hizmetleriniz hangi sektörleri kapsar?',
                'a' => 'Fabrika, depo, mağaza zinciri, banka, sigorta şubesi, hastane, otel, restoran, e-ticaret deposu, AVM içi kiracı taşıması gibi tüm kurumsal segmentlere hizmet veriyoruz.',
            ],
            [
                'q' => 'Fabrika ve depo taşıması için özel ekipman sağlıyor musunuz?',
                'a' => 'Forklift, lift, transpalet, sepetli vinç, mobil asansör, ağır yük asansörü ve özel araçlar kullanıyoruz. Hat sökme-kurma için makine montaj ekiplerimiz mevcuttur.',
            ],
            [
                'q' => 'Kurumsal projeler için anlaşmalı tarife mümkün mü?',
                'a' => 'Evet. Çok şubeli işletmeler, zincir mağazalar ve büyük hacimli taşımalar için yıllık çerçeve sözleşmesi ve anlaşmalı tarifeler düzenliyoruz.',
            ],
            [
                'q' => 'Kurumsal taşımada sigorta ve sorumluluk kapsamı nedir?',
                'a' => 'Yazılı sözleşmede sigorta limiti, muafiyet ve hasarsız taşıma taahhüdü yer alır. Eşya değer sigortası ve nakliye sorumluluk sigortası birlikte uygulanabilir.',
            ],
        ],
        'parca-esya-tasima' => [
            [
                'q' => 'Parça eşya taşıma nedir, kimler için uygundur?',
                'a' => 'Tek bir oda, az sayıda mobilya veya birkaç koliden oluşan küçük hacimli taşımalardır. Öğrenci, bekar taşımaları ile depodan eve mobilya/beyaz eşya teslimleri için idealdir.',
            ],
            [
                'q' => 'Parça eşya taşımada fiyat nasıl belirlenir?',
                'a' => 'Hacim (m³), mesafe, kat durumu ve paketleme talebine göre hesaplanır. Az hacimli taşımalarda paylaşımlı araç seçeneğiyle ekonomik fiyat sunulabilir.',
            ],
            [
                'q' => 'Sadece beyaz eşya veya tek bir mobilyayı taşır mısınız?',
                'a' => 'Evet. Tek bir buzdolabı, çamaşır makinesi, koltuk takımı, yatak veya piyano gibi tekil eşya taşımaları için aynı gün veya planlı hizmet verilir.',
            ],
            [
                'q' => 'Parça eşya taşımada paketleme dahil mi?',
                'a' => 'Talep edildiğinde profesyonel paketleme paket içine eklenebilir. Mobilyalar streç naylon, beyaz eşyalar çift kat ambalaj, kırılacaklar köpük dolgulu kutu ile korunur.',
            ],
            [
                'q' => 'Şehirler arası parça eşya taşıma yapıyor musunuz?',
                'a' => 'Evet. Türkiye genelinde şehirler arası parça eşya taşıma seçeneği mevcuttur. Az hacimli taşımalarda paylaşımlı araç ile maliyet düşürülür.',
            ],
        ],
        'asansorlu-nakliyat' => [
            [
                'q' => 'Asansörlü nakliyat nedir ve ne zaman gerekir?',
                'a' => 'Eşyaları pencere veya balkon üzerinden dış cephe asansörüyle taşıma yöntemidir. Yüksek katlı binalar, dar merdiven veya küçük yük asansörlerinde, büyük hacimli mobilyalarda tercih edilir.',
            ],
            [
                'q' => 'Mobil asansör kaç kata kadar çıkar?',
                'a' => 'Mobil asansörlerimiz 30. kata kadar çıkabilir. Yükseklik, eşya ağırlığı ve cephe yapısına göre uygun model seçilir.',
            ],
            [
                'q' => 'Asansörlü taşıma normal taşımaya göre daha pahalı mı?',
                'a' => 'Genelde aynı tutar veya çok az farklıdır. Çünkü dar merdiven kullanımı ekstra işçi, zaman ve hasar riski getirir; asansör bu maliyeti dengeler ve eşyayı korur.',
            ],
            [
                'q' => 'Asansörlü taşıma ne kadar sürer?',
                'a' => 'Eşya hacmine bağlı olarak ortalama 1-2 saat içinde yükleme veya boşaltma tamamlanır. Geleneksel merdiven taşımasına göre %60-70 daha hızlıdır.',
            ],
            [
                'q' => 'Asansörlü taşıma için ruhsat/izin gerekir mi?',
                'a' => 'Bazı yoğun trafikli sokaklar için belediye geçici işgal izni gerekebilir; tüm izin süreçlerini ekibimiz takip eder ve müşteriye ek iş yükü bırakmaz.',
            ],
        ],
        'sepetli-vinc-kiralama' => [
            [
                'q' => 'Sepetli vinç hangi işlerde kullanılır?',
                'a' => 'Yüksek noktalardaki tabela, klima, çatı, ağaç budama, dış cephe montaj, ışıklandırma, kamera kurulumu ve büyük eşya taşıma gibi her türlü yüksek erişim ihtiyacında kullanılır.',
            ],
            [
                'q' => 'Sepetli vinç kaç metreye kadar erişir?',
                'a' => 'Modeline göre 18-42 metre yüksekliğe kadar erişim sağlanır. İş yerinde keşif yapılarak uygun kapasite ve uzanım belirlenir.',
            ],
            [
                'q' => 'Operatör hizmete dahil mi?',
                'a' => 'Evet. Tüm vinç kiralamalarında deneyimli, sertifikalı operatör hizmete dahildir. Operatör güvenlik prosedürlerini uygular ve iş güvenliği denetimi yapar.',
            ],
            [
                'q' => 'Saatlik ve günlük kiralama mümkün mü?',
                'a' => 'Hem saatlik hem günlük kiralama seçeneği vardır. Uzun süreli projeler için indirimli haftalık/aylık tarife uygulanır.',
            ],
        ],
        'mobil-asansor-kiralama' => [
            [
                'q' => 'Mobil asansör kiralama nedir?',
                'a' => 'Dış cepheye yerleştirilen, motor güçle çalışan ve eşyaları pencere/balkondan kat seviyesine taşıyan asansör sistemidir. Taşıma ve montaj işlerinde kullanılır.',
            ],
            [
                'q' => 'Mobil asansör kiralama ücreti nasıl belirlenir?',
                'a' => 'Kat yüksekliği, kullanım süresi (saat/gün) ve operatör hizmetine göre fiyatlandırılır. Net fiyat için ekspertiz veya telefonla ön bilgi alınır.',
            ],
            [
                'q' => 'Mobil asansörü kendim mi kuruyorum?',
                'a' => 'Hayır. Tüm kurulum, kullanım ve toplama sertifikalı operatörümüz tarafından yapılır. İş güvenliği ekipmanları (baret, halat, eldiven) sağlanır.',
            ],
            [
                'q' => 'Yağmurlu veya rüzgarlı havada mobil asansör çalışır mı?',
                'a' => 'Hafif yağmurda hizmet verilir; ancak güçlü rüzgarda (saatte 50 km üzeri) ve fırtınalı havalarda iş güvenliği nedeniyle operasyon durdurulur.',
            ],
        ],
        'esya-depolama' => [
            [
                'q' => 'Eşya depolama hizmeti nedir, depolar nerede?',
                'a' => 'Profesyonel kapalı depolarımızda eşyalarınızı kısa veya uzun süreli güvenle saklarız. Depolarımız İzmir\'de, klimatize, neme karşı korumalı ve 7/24 güvenlik kameralı tesislerdir.',
            ],
            [
                'q' => 'Eşya depolama fiyatı nasıl hesaplanır?',
                'a' => 'Saklanacak eşyanın hacmine (m³), saklama süresine ve özel koruma talebine (klimatize, neme/böceğe karşı ekstra koruma) göre fiyatlandırılır. Aylık tarifeler de mevcuttur.',
            ],
            [
                'q' => 'Eşyalarımı istediğim zaman görebilir veya alabilir miyim?',
                'a' => 'Evet. Önceden randevulu olarak depo ziyareti yapılır; isterseniz tüm eşyalar, isterseniz seçtiğiniz birkaç parça teslim edilir.',
            ],
            [
                'q' => 'Depolama süresince eşyalarım sigortalı mı?',
                'a' => 'Tüm depolama hizmetlerinde temel depo sigortası mevcuttur. Değerli eşyalar için ek değer sigortası seçeneği sunulur.',
            ],
            [
                'q' => 'Minimum depolama süresi nedir?',
                'a' => 'Minimum süre 1 aydır. Bekar taşıma, ev yenileme veya geçici taşınma için kısa süreli haftalık paketler de planlanabilir.',
            ],
        ],
        'antika-piyano-tasimaciligi' => [
            [
                'q' => 'Piyano taşıması neden uzmanlık gerektirir?',
                'a' => 'Piyano hem ağır hem dengesizdir; iç mekanizması titreşime karşı hassastır. Yanlış taşıma akort bozulmasına, gövde çatlamasına veya mekanizma arızasına yol açar.',
            ],
            [
                'q' => 'Piyano taşıma için hangi ekipmanları kullanıyorsunuz?',
                'a' => 'Özel piyano arabası, koruyucu battaniye, sabitleme kayışları, ahşap rampa ve gerektiğinde sepetli vinç veya mobil asansör kullanılır. Kuyruklu piyanolarda özel sandık imal edilebilir.',
            ],
            [
                'q' => 'Piyano taşıma sonrası akort gerekir mi?',
                'a' => 'Taşıma sonrası 2-4 hafta içinde akort önerilir. Talep edildiğinde anlaşmalı akortçumuza yönlendirme yapılır.',
            ],
            [
                'q' => 'Antika ve değerli mobilya taşımacılığı yapıyor musunuz?',
                'a' => 'Evet. Antika konsol, ayna, tablo, biblo gibi değerli eşyalar için özel ahşap sandık imalatı, çift kat ambalaj ve değer sigortası seçeneği sunulur.',
            ],
        ],
        'mobilya-montaj-kurulum' => [
            [
                'q' => 'Mobilya montaj hizmeti hangi ürünleri kapsar?',
                'a' => 'IKEA, modüler dolap, yatak başlığı, gardırop, çalışma masası, oyuncak takımı, mutfak dolapları ve tüm hazır mobilya markalarının kurulumunu yapıyoruz.',
            ],
            [
                'q' => 'Montaj fiyatı nasıl belirlenir?',
                'a' => 'Ürün sayısı, parça karmaşıklığı ve montaj süresi dikkate alınarak hesaplanır. Tekil parça montajı için sabit tarifeler, çok parçalı projeler için keşif fiyatı uygulanır.',
            ],
            [
                'q' => 'Eski mobilyanın demontajı ve yenisinin montajı tek seferde olur mu?',
                'a' => 'Evet. Aynı ziyarette eski mobilya demonte edilip yeni mobilya monte edilebilir; ek transfer maliyeti çıkmaz.',
            ],
            [
                'q' => 'Mutfak dolabı kurulumu ve duvara sabitleme yapıyor musunuz?',
                'a' => 'Evet. Üst dolapların duvara sabitlenmesi, ankrajlama, ayar ve seviyeleme dahildir. Tezgah delme ve sifon bağlantı gibi tesisat işleri de yapılabilir.',
            ],
        ],
        'sehir-ici-nakliyat' => [
            [
                'q' => 'İzmir şehir içi nakliyat fiyatı ne kadar?',
                'a' => 'Şehir içi taşıma; eşya hacmi, kat/asansör durumu ve ilçeler arası mesafeye göre hesaplanır. Aynı ilçe içi taşımalar daha ekonomiktir. Net teklif için ücretsiz ekspertiz yapılır.',
            ],
            [
                'q' => 'İzmir\'in hangi ilçelerine şehir içi taşıma yapıyorsunuz?',
                'a' => 'Bornova, Buca, Karşıyaka, Konak, Gaziemir, Çiğli, Bayraklı, Karabağlar, Balçova, Narlıdere, Güzelbahçe, Menderes, Torbalı, Urla, Çeşme ve Foça dahil tüm İzmir ilçelerinde hizmet veriyoruz.',
            ],
            [
                'q' => 'Aynı gün taşıma mümkün mü?',
                'a' => 'Müsaitlik durumuna bağlı olarak aynı gün ekspertiz ve taşıma planlanabilir. Acil taşımalar için telefonla ön rezervasyon önerilir.',
            ],
            [
                'q' => 'Şehir içi parça eşya taşımalarında minimum ücret var mı?',
                'a' => 'Evet, hizmetin lojistik maliyetini karşılayan minimum bir başlangıç ücreti vardır. Bekar/parça taşımalar için ekonomik paketler sunulur.',
            ],
        ],
    ];
}

/**
 * Verilen slug için (veya alias varyantları için) default FAQ pair'lerini döner.
 * `seo_runtime_faq_page_ld()` için `question/answer` formatına da köprü kurar
 * (mevcut çağrı `q/a` bekliyor; uyumluluk için her ikisi de döner).
 *
 * @return list<array{q:string,a:string,question:string,answer:string}>
 */
function seo_runtime_default_faq_pairs_for_service_slug(string $slug): array
{
    $slug = trim($slug, "/ \t");
    if ($slug === '') {
        return [];
    }
    $map = seo_runtime_default_service_faq_map();

    // 1) Tam slug eşleşmesi
    if (isset($map[$slug])) {
        return seo_runtime_default_faq_normalize($map[$slug]);
    }

    // 2) Bilinen alias eşleştirmeleri (eski → yeni)
    static $aliases = [
        'izmir-evden-eve-nakliyat' => 'izmir-evden-eve-nakliyat-hizmeti',
        'evden-eve-nakliyat' => 'izmir-evden-eve-nakliyat-hizmeti',
        'ofis-tasima' => 'kurumsal-nakliye-ofis-tasima',
        'ofis-tasimaciligi' => 'kurumsal-nakliye-ofis-tasima',
        'sehirler-arasi-nakliyat' => 'sehirlerarasi-nakliyat',
        'sehirler-arasi' => 'sehirlerarasi-nakliyat',
        'piyano-tasima' => 'antika-piyano-tasimaciligi',
        'piyano-tasimaciligi' => 'antika-piyano-tasimaciligi',
        'antika-tasima' => 'antika-piyano-tasimaciligi',
        'mobilya-montaj' => 'mobilya-montaj-kurulum',
        'esya-deposu' => 'esya-depolama',
        'depolama' => 'esya-depolama',
        'asansorlu-tasima' => 'asansorlu-nakliyat',
        'vinc-kiralama' => 'sepetli-vinc-kiralama',
        'mobil-asansor' => 'mobil-asansor-kiralama',
    ];
    if (isset($aliases[$slug], $map[$aliases[$slug]])) {
        return seo_runtime_default_faq_normalize($map[$aliases[$slug]]);
    }

    // 3) Fuzzy contains: slug'da geçen anahtar kelimelere göre en yakın hizmet.
    foreach ($map as $key => $pairs) {
        if (strpos($slug, $key) === 0 || strpos($key, $slug) === 0) {
            return seo_runtime_default_faq_normalize($pairs);
        }
    }

    return [];
}

/**
 * Q/A formatını hem q/a hem question/answer olacak şekilde normalize eder.
 *
 * @param list<array{q:string,a:string}> $pairs
 * @return list<array{q:string,a:string,question:string,answer:string}>
 */
function seo_runtime_default_faq_normalize(array $pairs): array
{
    $out = [];
    foreach ($pairs as $p) {
        $q = trim((string) ($p['q'] ?? $p['question'] ?? ''));
        $a = trim((string) ($p['a'] ?? $p['answer'] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $out[] = [
            'q' => $q,
            'a' => $a,
            'question' => $q,
            'answer' => $a,
        ];
    }
    return $out;
}
