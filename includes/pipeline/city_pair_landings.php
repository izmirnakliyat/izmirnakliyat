<?php
declare(strict_types=1);

/**
 * İzmir çıkışlı 5 yüksek talepli şehir çifti SEO landing (kod-SSOT; `pages` tablosu gerekmez).
 * Slug: izmir-istanbul, izmir-ankara, izmir-bursa, izmir-antalya, izmir-mugla
 */

function mynak_cp_href(string $path): string
{
    if (function_exists('mynak_public_path')) {
        return htmlspecialchars(mynak_public_path($path), ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars('/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
}

/**
 * Sitemap + çakışma önleme (pages tablosu aynı slug taşıyorsa duplikasyon olmasın).
 *
 * @return list<string>
 */
function mynak_city_pair_landing_slugs(): array
{
    return array_keys(mynak_city_pair_landing_map());
}

/**
 * `sayfa.php` + veritabanı `pages` satırı ile uyumlu dizi.
 *
 * @return array{
 *   id: int,
 *   title: string,
 *   content: string,
 *   slug: string,
 *   created_at: string,
 *   updated_at: string,
 *   meta_description: string,
 *   meta_keywords: string,
 *   status: int,
 *   type: string
 * }|null
 */
function mynak_city_pair_landing_data(string $slug): ?array
{
    $slug = mb_strtolower(trim($slug), 'UTF-8');
    $map = mynak_city_pair_landing_map();
    if (!isset($map[$slug])) {
        return null;
    }
    $row = $map[$slug];

    $teklif = mynak_cp_href('teklif-alin');
    $ilet = mynak_cp_href('iletisim');
    $sehirler = mynak_cp_href('sehirlerarasi-nakliyat');
    $izmirEve = mynak_cp_href('izmir-evden-eve-nakliyat');
    $blog = mynak_cp_href('blog');

    $html = (string) $row['html'];
    $html = str_replace(
        ['%TEKLIF%', '%ILETISIM%', '%SEHIRLARARASI%', '%IZMIR_EVE%', '%BLOG%'],
        [$teklif, $ilet, $sehirler, $izmirEve, $blog],
        $html
    );

    $now = '2026-04-26 12:00:00';
    return [
        'id' => 0,
        'title' => (string) $row['title'],
        'content' => $html,
        'slug' => $slug,
        'created_at' => $now,
        'updated_at' => $now,
        'meta_description' => (string) $row['meta_description'],
        'meta_keywords' => (string) $row['meta_keywords'],
        'status' => 1,
        'type' => 'page',
    ];
}

/**
 * @return array<string, array{title: string, meta_description: string, meta_keywords: string, html: string}>
 */
function mynak_city_pair_landing_map(): array
{
    return [
        'izmir-istanbul' => [
            'title' => 'İzmir — İstanbul Evden Eve ve Şehirler Arası Nakliyat',
            'meta_description' => 'İzmir’den İstanbul’a ev, ofis ve parça eşya taşımasında rota, ambalaj, erişim ve güvence seçeneklerini yazılı teklifle planlayın.',
            'meta_keywords' => 'izmir istanbul nakliyat, izmir istanbul evden eve, izmirden istanbula tasima, sehirler arasi nakliyat izmir',
            'html' => <<<'HTML'
<p>İzmir’den <strong>İstanbul</strong> hattı; uzun mesafe, yoğun trafik ve farklı daire/ site erişim senaryoları nedeniyle keşif + yazılı sözleşme olmadan fiyat söylemek yerine, envanter ve güzergâh planına dayanır. MY Nakliyat; ev eşyası, ofis, eşya depolama entegrasyonu ve parça yükte aynı marka sorumluluğuyla ilerler.</p>

<h2>Operasyon nasıl ilerler?</h2>
<p>Keşif veya hızlı envanter sonrası ambalaj standardı, kat ve asansör / dış asansör ihtiyacı, sigorta satırı ve varış tarafta teslim penceresi netleşir. Tam veya parça tır, termin ve boşaltma günü önceden yazılır. İstanbul’da bölgeye göre trafik ve site kuralları farklılaştığı için, varış adresindeki sınırlar (asansör saati, park, tahditli sokak) sözleşmeye işlenir.</p>

<h2>Size ne kazandırır?</h2>
<ul>
<li>Keşif sonrası <strong>tek fiyat</strong> — kapsamı açık, gizli satır baskısı yok</li>
<li>Sigortalı sevk + montaj/ sök-tak ayrı maddelerde</li>
<li>İzmir’de muhattap, operasyon hattı ve varış ekiplerinde aynı koordinasyon</li>
</ul>

<p>Ücretsiz ön değerlendirme için <a href="%TEKLIF%">teklif formu</a> veya <a href="%ILETISIM%">İletişim</a> üzerinden hafta içi/ cumartesi hattı. <a href="%SEHIRLARARASI%">Şehirler arası nakliyat</a> ve <a href="%IZMIR_EVE%">İzmir evden eve</a> sayfalarında kapsam detaylarını inceleyebilirsiniz; <a href="%BLOG%">blog</a> bölümünde hazırlık listeleri bulunur.</p>
HTML
        ],
        'izmir-ankara' => [
            'title' => 'İzmir — Ankara Şehirler Arası ve Evden Eve Nakliyat',
            'meta_description' => 'İzmir’den Ankara’ya ev ve ofis eşyası taşımasında envanter, ambalaj, rota, takvim ve güvence seçeneklerini yazılı teklifle planlayın.',
            'meta_keywords' => 'izmir ankara nakliyat, izmirden ankara evden eve, ankara tasima, sehirler arasi ankara izmir',
            'html' => <<<'HTML'
<p>İzmir’den <strong>Ankara</strong> gidişi; büro arşivleri, kurumsal mobilya ve konut eşyasında farklı ambalaj ve yükleme disiplinleri gerektirebilir. Başkente planlı gidiş, teslimat günü ve bina/ OSB erişim koşulları sözleşmeye bağlanınca sürprizler azalır. MY Nakliyat, bu hatta hem konut hem kurumsal referansla çalışır.</p>

<h2>Süreç ve planlama</h2>
<p>Keşifte eşya listesi, hassas/ yük ekipman, dosya ve arşiv kolileri, montaj ihtiyacı ve varıştaki boşluk/ depo bekleme ayrı satırlar halinde toplanır. Gerekirse eşyalar kısa süreli <strong>depo</strong>’da tutulup, Ankara uygunken teslim edilir. Taşıyıcı ekip, vinç/ asansör ve yük sigortası teklif metninde okunur.</p>

<h2>Neden ayrı sayfa?</h2>
<p>İzmir — Ankara hattı yoğun talep gördüğü için arama niyetine uygun, net başlık ve kapsam sunmak istedik. <a href="%SEHIRLARARASI%">Şehirler arası hizmet</a> açıklamamızla birlikte; eşya veya ofis ağırlıklı senaryo için <a href="%IZMIR_EVE%">evden eve</a> ve <a href="%TEKLIF%">ücretsiz teklif</a> üzerinden keşif randevusu almanız yeterlidir. Sorularınız için <a href="%ILETISIM%">iletişim</a> formu ve merkez hattı.</p>
HTML
        ],
        'izmir-bursa' => [
            'title' => 'İzmir — Bursa Parça Eşya ve Evden Eve Nakliyat',
            'meta_description' => 'Kısa mesafede hızlı termin: İzmir’den Bursa’ya ev, ofis ve parça eşya. Yazılı teklif, ambalaj, sigorta. MY Nakliyat İzmir merkez.',
            'meta_keywords' => 'izmir bursa nakliyat, izmir bursa evden eve, bursa tasima, parca esya izmir bursa',
            'html' => <<<'HTML'
<p>İzmir ve <strong>Bursa</strong> arası mesafe kısa olsa da; eşik yük, hafta sonu/ bayram yoğunluğu ve farklı daire tiplerinde sürücü, paketleme ve boşaltma aynı disiplinle yapılmazsa süre büyür. MY Nakliyat; aynı gün veya ertesi gün termin, parça eşya veya komple ev için net kapasite planı sunar.</p>

<h2>Ne sunuyoruz?</h2>
<ul>
<li>Parça yük: az kolili sevk veya büyük mobilya (balça ambalaj + köşe koruma)</li>
<li>Ofis: iş istasyonu, dosya, IT ekipmanı ayrı etiket</li>
<li>Ev: keşif + ambalaj + montaj, isteğe depo bekleme</li>
</ul>

<p>Önce fiyat almak isterseniz <a href="%TEKLIF%">teklif</a> sayfası; rota hakkında genel bilgi için <a href="%BLOG%">blog</a> yazılarımız. Ardından <a href="%ILETISIM%">telefon / WhatsApp</a> hattıyla gün onayı. Tüm <a href="%SEHIRLARARASI%">şehirler arası</a> sözleşme koşullarımız Bursa güzergâhı için de geçerlidir.</p>
HTML
        ],
        'izmir-antalya' => [
            'title' => 'İzmir — Antalya Evden Eve ve Sezonluk Taşıma',
            'meta_description' => 'İzmir’den Antalya’ya konut, yazlık eşyası, ofis: uzun rota, sigorta, ambalaj. Sezon öncesi/ sonrası termin. MY Nakliyat.',
            'meta_keywords' => 'izmir antalya nakliyat, izmir antalya evden eve, antalya tasima, yazlik esya tasima',
            'html' => <<<'HTML'
<p>İzmir’den <strong>Antalya</strong> hattı; sezonluk taşınma, ikinci konut, turizm bölgesi adresleri ve sitede erişim kısıtı gibi faktörlerle öne çıkar. Denizyolu/ kara güzergâh ve termin haftaları, net yazılmadığında bırakma günü kayabilir. MY Nakliyat, keşifte kapı-kapı süreyi, varıştaki bırakma senaryosunu (zemin, bodrum, asansörsü yükse kat) açık fiyatlarla bağlar.</p>

<h2>Kapsam</h2>
<p>Beyaz eşya, mobilya, kutu/ koli, özel eşya ve ofis; hepsi için ayrı ambalaj sınıfı. Uzun mesafede yük emniyeti ve sigorta; taşıyıcı, yükleme ve indirmede aynı ekip sorumluluğu. Gerekirse kısa depolama + sonra Antalya’ya gidiş, tek sözleşme altında.</p>

<p><a href="%TEKLIF%">Teklif alın</a>, <a href="%SEHIRLARARASI%">şehirler arası hizmet</a> açıklamasını okuyun, ardından <a href="%IZMIR_EVE%">İzmir evden eve</a> ile entegre fiyat/ süre değerlendirmesi yapalım. <a href="%ILETISIM%">İletişim</a> üzerinden sezonluk veya acil gidişlerde ek müsaitlik sorabilirsiniz.</p>
HTML
        ],
        'izmir-mugla' => [
            'title' => 'İzmir — Muğla (Bodrum, Fethiye, Marmaris) Eşya Taşıma',
            'meta_description' => 'İzmir’den Muğla’ya bölge ve tatil beldeleri: eşya, site erişimi, yükse kat, dış asansör. Yazılı teklif, sigorta, MY Nakliyat.',
            'meta_keywords' => 'izmir mugla nakliyat, izmir bodrum tasima, fethiye evden eve izmir, mugla sehirler arasi',
            'html' => <<<'HTML'
<p><strong>Muğla</strong> bölgesi; dar sokak, eğim, site içi dolasım ve yükse kat dairelerde dış asansör ihtiyacı açısından “standart şehirler arası” çözümlerden farklılaşabilir. İzmir’den Bodrum, Fethiye, Marmaris veya merkez ilçelere eşya taşırken keşifte rota, araç boyutu ve bırakma noktası (marina çevresi, site, villa) ayrıntılı ele alınmalıdır.</p>

<h2>MY Nakliyat yaklaşımı</h2>
<p>Envanter + foto veya saha keşif; yükse kat veya asansörsü senaryo için net ek satırlar. Koli, mobilya, beyaz eşya ve fırın/ buzdolabı gibi hassas cihazlar ayrı ambalaj. Sigorta, taşıyıcı ve montaj/ demontaj; teklif PDF’inde okunur. Yaz/ erken dönem termininde kapasite planı önemlidir — erken sözleşme önerilir.</p>

<p>Önce <a href="%TEKLIF%">form</a> veya <a href="%ILETISIM%">iletişim</a> ile gün/ adres bilgisi verin. Genel hizmet çerçevesi <a href="%SEHIRLARARASI%">şehirler arası</a> sayfasındadır; İzmir içi ayrıntı için <a href="%IZMIR_EVE%">evden eve</a>, hazırlık için <a href="%BLOG%">blog</a> rehberlerine bakabilirsiniz.</p>
HTML
        ],
    ];
}
