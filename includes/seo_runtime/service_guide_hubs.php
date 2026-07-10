<?php
declare(strict_types=1);

/**
 * @return array<string,array{service_name:string,service_slug:string,guides:list<array{slug:string,title:string}>}>
 */
function mynak_service_guide_hub_definitions(): array
{
    return [
        'izmir-evden-eve-nakliyat' => [
            'service_name' => 'İzmir Evden Eve Nakliyat',
            'service_slug' => 'izmir-evden-eve-nakliyat',
            'guides' => [
                ['slug' => 'evden-eve-nakliyat-adim-adim-tasinma-rehberi', 'title' => 'Adım adım taşınma rehberi'],
                ['slug' => 'evden-eve-tasimacilik-paketleme-rehberi', 'title' => 'Ev eşyası paketleme rehberi'],
                ['slug' => 'ev-tasima-oncesi-kontrol-listesi', 'title' => 'Taşıma öncesi kontrol listesi'],
            ],
        ],
        'sehirler-arasi-nakliyat' => [
            'service_name' => 'Şehirler Arası Nakliyat',
            'service_slug' => 'sehirler-arasi-nakliyat',
            'guides' => [
                ['slug' => 'sehirler-arasi-nakliyat-nasil-yapilir', 'title' => 'Şehirler arası nakliyat nasıl yapılır?'],
                ['slug' => 'sehirler-arasi-nakliyat-sigorta-guvence', 'title' => 'Sigorta ve güvence rehberi'],
                ['slug' => '2026-sehirler-arasi-nakliyat-fiyatlari-guncel-rehber', 'title' => 'Şehirler arası fiyat rehberi'],
            ],
        ],
        'izmir-ofis-tasimaciligi' => [
            'service_name' => 'Ofis Taşıma',
            'service_slug' => 'kurumsal-nakliye-hizmetleri',
            'guides' => [
                ['slug' => 'ofis-tasima-oncesi-checklist-islerinizi-aksatmadan-tasinin', 'title' => 'Ofis taşıma kontrol listesi'],
                ['slug' => 'ofis-tasima-rehberi-sorunsuz-sekilde-isyeri-nasil-tasinir', 'title' => 'İş yeri taşıma rehberi'],
                ['slug' => 'izmir-ofis-tasima-maliyetleri-planlama-ve-butceleme-ipuclari', 'title' => 'Ofis taşıma bütçe rehberi'],
            ],
        ],
        'izmir-esya-depolama' => [
            'service_name' => 'İzmir Eşya Depolama',
            'service_slug' => 'esya-depolama',
            'guides' => [
                ['slug' => 'izmir-esya-depolama-nedir-nasil-yapilir', 'title' => 'Eşya depolama nasıl yapılır?'],
                ['slug' => 'esyalar-depoya-nasil-paketlenir', 'title' => 'Depolama için paketleme rehberi'],
                ['slug' => 'depolama-sozlesmesi-nasil-olmali', 'title' => 'Depolama sözleşmesi rehberi'],
            ],
        ],
        'asansorlu-nakliyat' => [
            'service_name' => 'Asansörlü Nakliyat',
            'service_slug' => 'asansorlu-nakliyat',
            'guides' => [
                ['slug' => 'asansorlu-tasimacilik-nedir-avantajlari', 'title' => 'Asansörlü taşımacılık ve avantajları'],
                ['slug' => 'asansorlu-nakliye-her-yere-kurulabilir-mi', 'title' => 'Mobil asansör kurulum koşulları'],
                ['slug' => 'izmir-asansorlu-nakliyat-hizmetinde-dikkat-edilmesi-gerekenler', 'title' => 'Asansörlü nakliyat seçim rehberi'],
            ],
        ],
        'parca-esya-tasima' => [
            'service_name' => 'Parça Eşya Taşıma',
            'service_slug' => 'parca-esya-tasima',
            'guides' => [
                ['slug' => 'izmir-parca-esya-tasima-10-altin-kural', 'title' => 'Parça eşya taşımanın 10 kuralı'],
                ['slug' => 'parca-esya-gonderiminde-sigorta-gerekli-mi', 'title' => 'Parça eşyada sigorta rehberi'],
                ['slug' => 'tek-parca-esya-nasil-tasinir', 'title' => 'Tek parça eşya nasıl taşınır?'],
            ],
        ],
        'antika-ve-piyano-tasima' => [
            'service_name' => 'Antika ve Piyano Taşıma',
            'service_slug' => 'antika-piyano-tasimaciligi',
            'guides' => [
                ['slug' => 'izmir-piyano-tasima-rehberi', 'title' => 'İzmir piyano taşıma rehberi'],
                ['slug' => 'piyano-tasima-hatalari', 'title' => 'Piyano taşırken yapılan hatalar'],
                ['slug' => 'piyano-tasima-fiyatlari', 'title' => 'Piyano taşıma fiyat rehberi'],
            ],
        ],
        'sepetli-vinc-kiralama' => [
            'service_name' => 'Sepetli Vinç Kiralama',
            'service_slug' => 'sepetli-vinc-kiralama',
            'guides' => [
                ['slug' => 'izmir-sepetli-vinc-kiralama-uygun-fiyatlarla-hizmetinizde', 'title' => 'İzmir sepetli vinç kiralama rehberi'],
                ['slug' => 'sepetli-vinc-kiralama-bornova', 'title' => 'Bornova sepetli vinç kullanım rehberi'],
                ['slug' => 'karsiyaka-sepetli-vinc-kiralama', 'title' => 'Karşıyaka sepetli vinç rehberi'],
            ],
        ],
        'mobil-asansor-kiralama' => [
            'service_name' => 'Mobil Asansör Kiralama',
            'service_slug' => 'mobil-asansor-kiralama',
            'guides' => [
                ['slug' => 'izmir-mobil-asansor-kiralama-fiyatlari', 'title' => 'Mobil asansör kiralama fiyat rehberi'],
                ['slug' => 'asansorlu-nakliye-her-yere-kurulabilir-mi', 'title' => 'Mobil asansör kurulum koşulları'],
                ['slug' => 'izmir-kiralik-asansor', 'title' => 'İzmir kiralık asansör rehberi'],
            ],
        ],
        'mobilya-montaj-kurulum' => [
            'service_name' => 'Mobilya Montaj ve Kurulum',
            'service_slug' => 'mobilya-montaj-kurulum',
            'guides' => [
                ['slug' => 'izmir-mobilya-tasima-demonte-ve-montaj-uygulamalari', 'title' => 'Mobilya demontaj ve montaj rehberi'],
                ['slug' => 'mobilya-beyaz-esya-tasimaciligi-rehberi', 'title' => 'Mobilya ve beyaz eşya taşıma rehberi'],
                ['slug' => 'izmir-evden-eve-nakliyat-hizmetleri-paketleme-montaj', 'title' => 'Paketleme ve montaj rehberi'],
            ],
        ],
        'sehir-ici-nakliyat' => [
            'service_name' => 'İzmir Şehir İçi Nakliyat',
            'service_slug' => 'sehir-ici-nakliyat',
            'guides' => [
                ['slug' => 'izmir-nakliye-konusunda-iciniz-rahat-olsun', 'title' => 'İzmir nakliye planlama rehberi'],
                ['slug' => 'izmir-esya-tasimaciligi-hizmeti-neden-tercih-edilmeli', 'title' => 'İzmir eşya taşımacılığı seçim rehberi'],
                ['slug' => 'izmir-nakliye-hizmetlerinde-musteri-memnuniyeti-cozumler', 'title' => 'Şehir içi nakliyatta hizmet kalitesi'],
            ],
        ],
    ];
}

function mynak_service_guide_graph_slug(string $slug): string
{
    $aliases = [
        'izmir-evden-eve-nakliyat-hizmeti' => 'izmir-evden-eve-nakliyat',
        'sehirlerarasi-nakliyat' => 'sehirler-arasi-nakliyat',
        'kurumsal-nakliye-ofis-tasima' => 'izmir-ofis-tasimaciligi',
        'kurumsal-nakliye-hizmetleri' => 'izmir-ofis-tasimaciligi',
        'esya-depolama' => 'izmir-esya-depolama',
        'antika-piyano-tasimaciligi' => 'antika-ve-piyano-tasima',
    ];

    return $aliases[trim($slug, '/')] ?? trim($slug, '/');
}

function mynak_service_guide_hub_html(string $serviceSlug): string
{
    $definition = mynak_service_guide_hub_definitions()[mynak_service_guide_graph_slug($serviceSlug)] ?? null;
    if (!is_array($definition)) {
        return '';
    }
    $html = '<aside class="mynak-guide-hub card border-0 shadow-sm mt-4" aria-labelledby="mynak-guide-hub-title">'
        . '<div class="card-body p-4"><h2 id="mynak-guide-hub-title" class="h5 mb-2">'
        . htmlspecialchars((string) $definition['service_name'], ENT_QUOTES, 'UTF-8')
        . ' Rehberleri</h2><p class="small text-muted mb-3">Hizmet kapsamını; hazırlık, paketleme, güvence ve maliyet başlıklarıyla tamamlayan uzman içerikleri.</p>'
        . '<ul class="list-unstyled row g-2 mb-0">';
    foreach ($definition['guides'] as $guide) {
        $href = function_exists('mynak_public_path')
            ? mynak_public_path((string) $guide['slug'])
            : '/' . ltrim((string) $guide['slug'], '/');
        $html .= '<li class="col-md-6"><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars((string) $guide['title'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }

    return $html . '</ul></div></aside>';
}

/**
 * @return array<string,array{name:string,guides:list<array{slug:string,title:string}>}>
 */
function mynak_topic_hub_definitions(): array
{
    return [
        'tasinma-rehberi' => [
            'name' => 'Taşınma Rehberi',
            'guides' => [
                ['slug' => 'evden-eve-nakliyat-adim-adim-tasinma-rehberi', 'title' => 'Adım adım taşınma rehberi'],
                ['slug' => 'ev-tasima-oncesi-kontrol-listesi', 'title' => 'Ev taşıma öncesi kontrol listesi'],
                ['slug' => 'ev-tasinmadan-once-yapilmasi-gereken-10-altin-hazirlik', 'title' => 'Taşınmadan önce 10 hazırlık'],
                ['slug' => 'tasinirken-dikkat-edilmesi-gerekenler-izmir-evden-eve-nakliyat', 'title' => 'Taşınırken dikkat edilmesi gerekenler'],
            ],
        ],
        'nakliyat-rehberi' => [
            'name' => 'Nakliyat Rehberi',
            'guides' => [
                ['slug' => 'izmir-evden-eve-nakliyat-rehberi', 'title' => 'İzmir evden eve nakliyat rehberi'],
                ['slug' => 'profesyonel-tasima-firmalari-nasil-secilir', 'title' => 'Taşıma firması seçme rehberi'],
                ['slug' => 'evden-eve-nakliyatta-sigorta-ve-guvenlik-onlemleri', 'title' => 'Nakliyatta sigorta ve güvenlik'],
                ['slug' => 'nakliye-sigortasi-nedir', 'title' => 'Nakliye sigortası nedir?'],
            ],
        ],
        'paketleme-rehberi' => [
            'name' => 'Paketleme Rehberi',
            'guides' => [
                ['slug' => 'evden-eve-tasimacilik-paketleme-rehberi', 'title' => 'Ev eşyası paketleme rehberi'],
                ['slug' => 'evden-eve-nakliyatta-paketleme-ve-ambalajlama-ipuclari', 'title' => 'Paketleme ve ambalajlama ipuçları'],
                ['slug' => 'paketleme-hatalari-esyalarinizi-korumak-10-tavsiye', 'title' => 'Paketleme hataları ve 10 tavsiye'],
                ['slug' => 'esya-tasirken-zarar-gormemesi-icin-paketleme-nasil-olmali', 'title' => 'Eşyalar zarar görmeden nasıl paketlenir?'],
            ],
        ],
        'nakliyat-fiyat-rehberi' => [
            'name' => 'Nakliyat Fiyat Rehberi',
            'guides' => [
                ['slug' => 'evden-eve-nakliyat-maliyetleri-fiyat-rehberi', 'title' => 'Evden eve nakliyat maliyet rehberi'],
                ['slug' => 'izmir-evden-eve-nakliyat-fiyatlari-2026', 'title' => 'İzmir nakliyat fiyatları rehberi'],
                ['slug' => 'nakliyat-fiyatlarini-etkileyen-faktorler', 'title' => 'Nakliyat fiyatını etkileyen faktörler'],
                ['slug' => 'parca-esya-tasima-fiyatlari-2026-ucretler-nasil-belirlenir', 'title' => 'Parça eşya fiyatlandırma rehberi'],
            ],
        ],
    ];
}

function mynak_blog_topic_hub_html(array $blog): string
{
    $currentSlug = trim((string) ($blog['slug'] ?? ''), '/');
    if ($currentSlug === '') {
        return '';
    }
    foreach (mynak_topic_hub_definitions() as $hubKey => $hub) {
        $slugs = array_column($hub['guides'], 'slug');
        if (!in_array($currentSlug, $slugs, true)) {
            continue;
        }
        $id = 'mynak-topic-hub-' . $hubKey;
        $html = '<aside class="mynak-guide-hub card border-0 shadow-sm mt-4" aria-labelledby="' . $id . '">'
            . '<div class="card-body p-4"><h2 id="' . $id . '" class="h5 mb-2">'
            . htmlspecialchars((string) $hub['name'], ENT_QUOTES, 'UTF-8')
            . '</h2><p class="small text-muted mb-3">Bu konuyu hazırlık, güvenlik ve maliyet yönleriyle tamamlayan ilgili içerikler.</p>'
            . '<ul class="list-unstyled row g-2 mb-0">';
        foreach ($hub['guides'] as $guide) {
            if ((string) $guide['slug'] === $currentSlug) {
                continue;
            }
            $href = function_exists('mynak_public_path')
                ? mynak_public_path((string) $guide['slug'])
                : '/' . ltrim((string) $guide['slug'], '/');
            $html .= '<li class="col-md-6"><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars((string) $guide['title'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }

        return $html . '</ul></div></aside>';
    }

    return '';
}

/** @return array{graph_slug:string,service_name:string,service_slug:string}|null */
function mynak_blog_service_context(array $blog): ?array
{
    $haystack = mb_strtolower(
        (string) ($blog['slug'] ?? '') . ' ' . (string) ($blog['baslik'] ?? '') . ' ' . (string) ($blog['etiketler'] ?? ''),
        'UTF-8'
    );
    $rules = [
        'asansör' => 'asansorlu-nakliyat',
        'asansor' => 'asansorlu-nakliyat',
        'şehirler arası' => 'sehirler-arasi-nakliyat',
        'sehirler-arasi' => 'sehirler-arasi-nakliyat',
        'sehirlerarasi' => 'sehirler-arasi-nakliyat',
        'ofis' => 'izmir-ofis-tasimaciligi',
        'kurumsal' => 'izmir-ofis-tasimaciligi',
        'depolama' => 'izmir-esya-depolama',
        'parça eşya' => 'parca-esya-tasima',
        'parca-esya' => 'parca-esya-tasima',
        'piyano' => 'antika-ve-piyano-tasima',
        'antika' => 'antika-ve-piyano-tasima',
    ];
    $definitions = mynak_service_guide_hub_definitions();
    foreach ($rules as $term => $graphSlug) {
        if (str_contains($haystack, $term) && isset($definitions[$graphSlug])) {
            return [
                'graph_slug' => $graphSlug,
                'service_name' => (string) $definitions[$graphSlug]['service_name'],
                'service_slug' => (string) $definitions[$graphSlug]['service_slug'],
            ];
        }
    }
    if (!str_contains($haystack, 'nakliyat') && !str_contains($haystack, 'taşıma') && !str_contains($haystack, 'tasima')) {
        return null;
    }
    $definition = $definitions['izmir-evden-eve-nakliyat'];

    return [
        'graph_slug' => 'izmir-evden-eve-nakliyat',
        'service_name' => (string) $definition['service_name'],
        'service_slug' => (string) $definition['service_slug'],
    ];
}

function mynak_blog_related_service_html(array $blog): string
{
    $context = mynak_blog_service_context($blog);
    if ($context === null) {
        return '';
    }
    $href = function_exists('mynak_public_path')
        ? mynak_public_path($context['service_slug'])
        : '/' . $context['service_slug'];

    return '<aside class="mynak-related-service alert alert-light border mt-4" aria-label="Bu rehberle ilgili hizmet">'
        . '<strong>Bu rehberin ilgili hizmeti:</strong> <a href="'
        . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($context['service_name'], ENT_QUOTES, 'UTF-8')
        . ' kapsamı ve teklif süreci</a></aside>'
        . mynak_blog_topic_hub_html($blog);
}

/** @return list<array<string,string>> */
function mynak_service_guide_article_refs(string $canonicalOrigin, string $serviceSlug): array
{
    $definition = mynak_service_guide_hub_definitions()[mynak_service_guide_graph_slug($serviceSlug)] ?? null;
    if (!is_array($definition)) {
        return [];
    }
    $origin = rtrim($canonicalOrigin, '/');
    $refs = [];
    foreach ($definition['guides'] as $guide) {
        $refs[] = ['@id' => $origin . '/' . rawurlencode((string) $guide['slug']) . '#article'];
    }

    return $refs;
}
