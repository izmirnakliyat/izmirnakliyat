<?php
/**
 * MY Nakliyat Local SEO Content Engine — modüler, kısa prompt üretimi.
 */
declare(strict_types=1);

const MYNAK_CE_BATCH_SETTING = 'blog_ce_batch_version';

/** Editör onay kuyruğu (QC PASS). */
const MYNAK_CE_DURUM_EDITOR_REVIEW = 1;

/** QC FAIL — yayın dışı, düzeltme gerekli. */
const MYNAK_CE_DURUM_NEEDS_REVISION = 2;

/** Yayında (kaynak; işlem sonrası düşürülür). */
const MYNAK_CE_DURUM_PUBLISHED = 3;

function mynak_ce_topic_hay(array $post): string
{
    return mb_strtolower(
        trim((string) ($post['slug'] ?? '') . ' ' . (string) ($post['baslik'] ?? '') . ' ' . (string) ($post['etiketler'] ?? '')),
        'UTF-8'
    );
}

/** Admin ayarından batch_version (re-run’da farklı varyant). */
function mynak_ce_batch_version(?mysqli $conn = null): string
{
    if ($conn instanceof mysqli && function_exists('br_setting_get')) {
        $v = trim(br_setting_get($conn, MYNAK_CE_BATCH_SETTING, '1'));

        return $v !== '' ? $v : '1';
    }

    return '1';
}

/**
 * seed = crc32(post_id|slug|baslik|batch_version)
 */
function mynak_ce_seed(array $post, ?string $batchVersion = null): int
{
    $bv = $batchVersion ?? (string) ($post['_batch_version'] ?? '1');
    $raw = (int) ($post['id'] ?? 0) . '|'
        . mb_strtolower(trim((string) ($post['slug'] ?? '')), 'UTF-8') . '|'
        . mb_strtolower(trim((string) ($post['baslik'] ?? '')), 'UTF-8') . '|'
        . $bv;
    $h = crc32($raw);
    if ($h < 0) {
        $h = $h & 0xffffffff;
    }

    return (int) $h;
}

function mynak_ce_pick(int $seed, int $n): int
{
    if ($n <= 0) {
        return 0;
    }

    return (int) (($seed & 0x7fffffff) % $n);
}

function mynak_ce_system_prompt(): string
{
    return 'Türkiye nakliyat SEO editörüsün. MY Nakliyat: premium, yerel, güven; saha hissi; şablon yok. '
        . 'Çıktı: (1) YENİ_BAŞLIK: … niyet korunmuş, doğal. (2) boş satır (3) HTML: h2,h3,p,ul,li,strong,a — h1 yok.';
}

/** @return list<string> */
function mynak_ce_banned_phrases(): array
{
    return [
        'Günümüzde', 'Bu noktada', 'Sonuç olarak', 'Profesyonel hizmet anlayışı', 'Müşteri memnuniyeti',
        'Kesintisiz hizmet', 'Kapsamlı çözümler', 'Sorunsuz deneyim', 'Kullanıcı deneyimi', 'Öne çıkan',
        'Alanında uzman ekip', 'Kaliteli hizmet anlayışı', 'Lider firma', 'Kurumsal çözümler',
        'En doğru yöntem', 'En iyi firma',
    ];
}

/**
 * @return array{key: string, line: string}
 */
function mynak_ce_service_intent(array $post): array
{
    $hay = mynak_ce_topic_hay($post);
    $key = 'genel';
    $line = 'güven, planlama, şeffaf süreç, operasyon kontrolü';

    if (preg_match('/sehirler.?arasi|şehirler.?arasi/', $hay)) {
        $key = 'sehirlerarasi';
        $line = 'rota, teslim süresi, yük sabitleme, sigorta, zaman planı';
    } elseif (preg_match('/asansor.*kiralama|asansör.*kiralama/', $hay)) {
        $key = 'asansor_kiralama';
        $line = 'teknik güvenlik, erişim, kat, operatör, kurulum alanı';
    } elseif (preg_match('/vinç|vinc|sepetli/', $hay)) {
        $key = 'vinc';
        $line = 'yükseklik, güvenlik, operatör, bina çevresi';
    } elseif (preg_match('/asansorlu|asansörlü/', $hay)) {
        $key = 'asansorlu';
        $line = 'kat, bina önü, kurulum, eşya güvenliği';
    } elseif (preg_match('/depo|depolama/', $hay)) {
        $key = 'depolama';
        $line = 'nem, güvenlik, envanter, erişim, depo koşulları';
    } elseif (preg_match('/parca|parça/', $hay)) {
        $key = 'parca_esya';
        $line = 'ortak rota, esneklik, az hacim, maliyet';
    } elseif (preg_match('/ofis|kurumsal/', $hay)) {
        $key = 'ofis';
        $line = 'iş sürekliliği, cihaz/dosya, hafta sonu plan';
    } elseif (preg_match('/izmir.*evden|evden.*izmir/', $hay) || (str_contains($hay, 'izmir') && str_contains($hay, 'evden'))) {
        $key = 'izmir_evden';
        $line = 'ilçe, trafik, park, bina tipi, yerel planlama';
    } elseif (str_contains($hay, 'evden')) {
        $key = 'evden_eve';
        $line = 'paketleme, eşya güvenliği, stres, zaman yönetimi';
    }

    return ['key' => $key, 'line' => $line];
}

/**
 * @return array{tier: string, min: int, max: int}
 */
function mynak_ce_topic_depth(array $post, int $seed): array
{
    $hay = mynak_ce_topic_hay($post);

    if (preg_match('/rehber|nasil|nedir|ipucu|fiyat|tavsiye|tasimacilik-tavsiye/', $hay)) {
        return ['tier' => 'standart', 'min' => 1400, 'max' => 1900];
    }
    if (preg_match('/izmir-evden-eve|izmir.*evden.eve|evden-eve.*izmir/', $hay)
        || preg_match('/sehirler.?arasi|şehirler.?arasi/', $hay)) {
        return ['tier' => 'yüksek-değer', 'min' => 1600, 'max' => 2200];
    }
    if (preg_match('/izmir-evden-eve-nakliyat|evden-eve-nakliyat-hizmet/', $hay) || str_contains($hay, 'pillar')) {
        return ['tier' => 'pillar', 'min' => 1700, 'max' => 2400];
    }
    if (preg_match('/kiralama|vinç|vinc|sepetli|parca-esya|parça/', $hay)) {
        return ['tier' => 'dar-konu', 'min' => 1200, 'max' => 1500];
    }
    $bands = [
        ['tier' => 'dar-konu', 'min' => 1200, 'max' => 1500],
        ['tier' => 'standart', 'min' => 1400, 'max' => 1800],
        ['tier' => 'standart+', 'min' => 1550, 'max' => 1950],
    ];
    $b = $bands[mynak_ce_pick($seed, count($bands))];

    return ['tier' => $b['tier'], 'min' => $b['min'], 'max' => $b['max']];
}

/** @return array{key: string, instruction: string} */
function mynak_ce_intro_type(int $seed): array
{
    $types = [
        ['key' => 'direct_answer', 'instruction' => 'Giriş: 40–70 kelime net cevap; sonra derinleş.'],
        ['key' => 'user_question', 'instruction' => 'Giriş: tek somut kullanıcı sorusu.'],
        ['key' => 'mini_scenario', 'instruction' => 'Giriş: 2 cümle taşınma senaryosu.'],
        ['key' => 'field_observation', 'instruction' => 'Giriş: kısa saha gözlemi (park/site/asansör).'],
        ['key' => 'myth_bust', 'instruction' => 'Giriş: yanlış bilinen + düzeltme.'],
        ['key' => 'checklist', 'instruction' => 'Giriş: 5–7 maddelik mini checklist.'],
        ['key' => 'price_worry', 'instruction' => 'Giriş: fiyat/planlama kaygısı; kesin rakam yok.'],
        ['key' => 'planning_stress', 'instruction' => 'Giriş: zaman baskısı; sakin ton.'],
    ];
    $t = $types[mynak_ce_pick($seed, count($types))];
    $starters = ['İlk cümle fiil ile.', 'İlk cümle soru ile.', 'İlk cümle yer/zaman ile.', 'İlk cümle ölçü/kat ile.'];
    $t['instruction'] .= ' ' . $starters[mynak_ce_pick($seed >> 4, 4)];

    return $t;
}

/** @return list<string> */
function mynak_ce_micro_detail_pool(): array
{
    return [
        'park ve araç yanaşma', 'site yönetimi izni', 'dar merdiven dönüşü', 'yüksek kat residence asansörü',
        'yaz sezonu yoğunluğu', 'hafta sonu taşınma talebi', 'şehirlerarası rota penceresi', 'yük sabitleme',
        'depo nem kontrolü', 'koli etiketleme', 'mobilya söküm sırası', 'kırılacak eşya ayrımı',
        'Alsancak park dar sokak', 'Karşıyaka eski apartman merdiveni', 'Bayraklı residence asansör rezervasyonu',
        'Bornova/Buca bina girişi', 'Gaziemir site içi mesafe', 'Çeşme yaz planlaması', 'Konak trafik kısıtı',
        'asansör kurulum alanı', 'vinç rüzgar/çevre güvenliği', 'ofis kablo etiketleme', 'parça eşya ortak rota',
        'sigorta eşya listesi', 'keşif hacim revizyonu', 'oda planı koli eşleşmesi', 'beyaz eşya sabitleme',
        'antika köşe koruma', 'öğrenci evi dar kapı', 'çeyiz hacim patlaması', 'mesai dışı bina güvenlik',
        'Menemen erken çıkış trafiği', 'yokuşlu sokak araç konumu', 'Urla yaz dalgalanması',
        'Buca okul çıkışı trafiği', 'Balçova asansör sırası', 'Menderes yeni site yolu',
        'hasar tutanağı fotoğraf', 'sözleşme hasar maddeleri', 'mini asansör kapasite sınırı',
        'yağmurda yükleme süresi', 'mobilya kurulum ertesi gün', 'teslim oda dağılımı',
        'şehirlerarası erken yükleme', 'Foça mesafe planı', 'Torbalı çıkış hattı',
        'tek yön saat kısıtı', 'otopark çizgisi asansör', 'vinç operatör tecrübesi',
        'kırılgan eşya ayrı istifleme', 'yük asansörü kapasite ölçüsü', 'randevu esnetme zorluğu',
    ];
}

/**
 * @return array{ids: list<int>, count: int, hint_line: string}
 */
function mynak_ce_pick_micro_details(int $seed, int $count = 3): array
{
    $pool = mynak_ce_micro_detail_pool();
    $n = count($pool);
    $count = max(2, min(4, $count));
    $offset = mynak_ce_pick($seed >> 7, $n);
    $ids = [];
    $hints = [];
    for ($i = 0; $i < $count; $i++) {
        $idx = ($offset + $i * 7) % $n;
        $ids[] = $idx;
        $hints[] = $pool[$idx];
    }

    return [
        'ids' => $ids,
        'count' => $count,
        'hint_line' => implode('; ', $hints),
    ];
}

function mynak_ce_needs_local_block(array $post): bool
{
    return (bool) preg_match('/izmir|buca|bornova|karsiyaka|konak|cigli|bayrakli|aliaga|menemen/', mynak_ce_topic_hay($post));
}

/** @return array{place: string, note: string}|null */
function mynak_ce_local_memory(array $post, int $seed): ?array
{
    if (!mynak_ce_needs_local_block($post)) {
        return null;
    }
    $mem = [
        ['place' => 'Alsancak', 'note' => 'park/dar sokak'],
        ['place' => 'Karşıyaka', 'note' => 'eski apartman merdiveni'],
        ['place' => 'Bayraklı/Mavişehir', 'note' => 'yüksek kat residence'],
        ['place' => 'Bornova/Buca', 'note' => 'bina girişi, aile taşınması'],
        ['place' => 'Gaziemir', 'note' => 'site mesafesi, şehirlerarası bağlantı'],
        ['place' => 'Çeşme', 'note' => 'yaz yoğunluğu'],
        ['place' => 'Konak', 'note' => 'merkez trafik'],
    ];

    return $mem[mynak_ce_pick($seed >> 2, count($mem))];
}

function mynak_ce_faq_count(int $seed): int
{
    $counts = [0, 0, 3, 4, 5, 6, 7, 3, 4, 6, 5, 7, 4];

    return $counts[mynak_ce_pick($seed, count($counts))];
}

function mynak_ce_h2_count(int $seed, array $post): int
{
    $map = [4, 5, 6, 7, 8, 5, 6, 4, 7, 5, 6, 8];
    $h2 = $map[mynak_ce_pick($seed, count($map))];
    $depth = mynak_ce_topic_depth($post, $seed);
    if ($depth['min'] >= 1700) {
        $h2 = min(9, $h2 + 1);
    }
    if ($depth['max'] <= 1500) {
        $h2 = max(4, $h2 - 1);
    }

    return $h2;
}

function mynak_ce_flow_type(int $seed): string
{
    $flows = ['A-linear', 'B-faq-early', 'C-field-first', 'D-risk-mid', 'E-deep-process', 'F-checklist-mid', 'G-price-late', 'H-local-mid'];

    return $flows[mynak_ce_pick($seed, count($flows))];
}

function mynak_ce_cta_style(int $seed): string
{
    $ctas = [
        'sakin planlama + doğal teklif linki',
        'soru ile kapanış + teklif',
        '3 madde ne-yapmalı + teklif',
        'yazılı teklif vurgusu (en iyi yok)',
        'tek kısa cümle + link',
        'kriter özeti + yumuşak teklif',
        'yoğun sezon hatırlatma + keşif',
        'sadece teklif formu',
        'acele seçim riski + teklif',
        '2 cümle özet + teklif',
        'karar erteleme + teklif',
        'checklist kapanışı + teklif',
    ];

    return $ctas[mynak_ce_pick($seed >> 5, count($ctas))];
}

function mynak_ce_syntax_style(int $seed): array
{
    $styles = [
        ['key' => 'warm', 'text' => 'sıcak, kısa cümle'],
        ['key' => 'technical', 'text' => 'teknik, süreç/izin'],
        ['key' => 'checklist', 'text' => 'checklist ağırlıklı'],
        ['key' => 'explainer', 'text' => 'açıklayıcı sade'],
        ['key' => 'scenario', 'text' => '1 mini senaryo'],
        ['key' => 'concise', 'text' => 'kısa net'],
    ];
    $s = $styles[mynak_ce_pick($seed >> 3, count($styles))];
    $temps = [0.64, 0.68, 0.72, 0.66, 0.74, 0.70];

    return ['key' => $s['key'], 'text' => $s['text'], 'temperature' => $temps[mynak_ce_pick($seed >> 3, count($temps))]];
}

function mynak_ce_snippet_hint(int $seed): string
{
    $forms = [
        'girişte 40–70 kelime cevap kutusu',
        'ilk H2 altında kısa tanım',
        'giriş + 3 maddelik mini liste',
        'ilk 2 H3 kısa soru-cevap',
    ];

    return $forms[mynak_ce_pick($seed >> 6, count($forms))];
}

function mynak_ce_link_entropy_hint(int $seed): string
{
    $hints = [
        'blog → hub → teklif',
        'tanım → teklif → blog → hub',
        'yerel → hub → belgeler → teklif',
        'hub → hizmet → blog → teklif',
        'blog → hub → teklif (tekrar yok)',
        'hub → teklif → blog',
    ];

    return $hints[mynak_ce_pick($seed, count($hints))];
}

/**
 * @return array<string, mixed>
 */
function mynak_ce_build_pack(array $post, ?mysqli $conn = null): array
{
    $batchVersion = mynak_ce_batch_version($conn);
    $seed = mynak_ce_seed($post, $batchVersion);
    $depth = mynak_ce_topic_depth($post, $seed);
    $intro = mynak_ce_intro_type($seed);
    $syntax = mynak_ce_syntax_style($seed);
    $faq = mynak_ce_faq_count($seed);
    $h2 = mynak_ce_h2_count($seed, $post);
    $flow = mynak_ce_flow_type($seed);
    $intent = mynak_ce_service_intent($post);
    $micro = mynak_ce_pick_micro_details($seed, mynak_ce_pick($seed >> 7, 3) + 2);
    $local = mynak_ce_local_memory($post, $seed);

    return [
        'seed' => $seed,
        'batch_version' => $batchVersion,
        'intro_type' => $intro['key'],
        'intro_instruction' => $intro['instruction'],
        'flow_type' => $flow,
        'service_intent' => $intent['key'],
        'service_intent_line' => $intent['line'],
        'word_min' => $depth['min'],
        'word_max' => $depth['max'],
        'depth_tier' => $depth['tier'],
        'faq_count' => $faq,
        'h2_count' => $h2,
        'cta_style' => mynak_ce_cta_style($seed),
        'syntax_key' => $syntax['key'],
        'syntax_line' => $syntax['text'],
        'temperature' => $syntax['temperature'],
        'snippet_hint' => mynak_ce_snippet_hint($seed),
        'link_entropy' => mynak_ce_link_entropy_hint($seed),
        'micro_detail_ids' => $micro['ids'],
        'micro_count' => $micro['count'],
        'micro_hint_line' => $micro['hint_line'],
        'local_memory' => $local,
        'use_local' => $local !== null,
    ];
}

/** Modüler blok: core quality (kısa). */
function mynak_ce_block_core_quality(): string
{
    $banned = implode(', ', array_slice(mynak_ce_banned_phrases(), 0, 10)) . '…';

    return 'CORE: Premium MY Nakliyat — planlı operasyon, saha bilgisi, şeffaf süreç; "en iyi/lider" yok. '
        . 'Yasak: ' . $banned . '. '
        . 'Gerçekçilik: yoğun sezon, park, asansör her binada uygun değil — korkutma yok.';
}

function mynak_ce_block_service_intent(array $pack): string
{
    return 'SERVICE: ' . ($pack['service_intent_line'] ?? 'güven, planlama');
}

function mynak_ce_block_flow(array $pack): string
{
    $faq = (int) ($pack['faq_count'] ?? 0);
    $faqLine = $faq === 0 ? 'FAQ:0' : "FAQ:{$faq}";
    $h2 = (int) ($pack['h2_count'] ?? 6);

    return 'FLOW ' . ($pack['flow_type'] ?? 'A-linear')
        . " | {$h2} H2 | {$faqLine}"
        . ' | ' . ($pack['intro_instruction'] ?? '')
        . ' | CTA:' . ($pack['cta_style'] ?? '');
}

function mynak_ce_block_local(array $pack): string
{
    $local = $pack['local_memory'] ?? null;
    if (!is_array($local)) {
        return '';
    }

    return 'LOCAL: ' . $local['place'] . ' — ' . $local['note'] . ' (en fazla 1–2 cümle; ilçe spam yok)';
}

function mynak_ce_block_micro(array $pack): string
{
    $n = (int) ($pack['micro_count'] ?? 0);
    if ($n < 1) {
        return '';
    }
    $hints = (string) ($pack['micro_hint_line'] ?? '');

    return 'MICRO (' . $n . " tema — sadece ilham)\n"
        . 'İpuçları: ' . $hints . "\n"
        . 'KURAL: Seçilen mikro detayları birebir listeleme. En fazla 2–4 tanesini doğal cümle içinde erit. '
        . 'Havuzdaki ifadeleri kopyalama; bağlama göre yeniden anlat.';
}

function mynak_ce_block_internal_links(string $linkBlock, array $pack): string
{
    if ($linkBlock === '') {
        return 'LINKS: bu koşuda iç link yok; <a> kullanma.';
    }
    $entropy = (string) ($pack['link_entropy'] ?? '');

    return "LINKS\n{$linkBlock}" . ($entropy !== '' ? "\nSıra: {$entropy}" : '');
}

function mynak_ce_block_output(array $pack, string $htmlRule): string
{
    $wMin = (int) ($pack['word_min'] ?? 1400);
    $wMax = (int) ($pack['word_max'] ?? 1800);
    $faq = (int) ($pack['faq_count'] ?? 0);

    return 'OUTPUT: Kelime ' . $wMin . '–' . $wMax . ' (' . ($pack['depth_tier'] ?? '') . '). '
        . $htmlRule . '. İlk satır YENİ_BAŞLIK (niyet korunur). h1 yok; slug spam yok. FAQ hedefi: ' . $faq . '.';
}

/**
 * Başlık clickbait / spam heuristiği.
 *
 * @return array{ok: bool, reasons: list<string>}
 */
function mynak_ce_title_spam_check(?string $title, string $oldTitle): array
{
    if ($title === null || trim($title) === '') {
        return ['ok' => true, 'reasons' => []];
    }
    $t = trim($title);
    $reasons = [];
    $spamPhrases = ['en iyi', 'lider', 'mutlaka', 'kaçırma', 'şok', '#1', 'tek tercih', 'garanti'];
    foreach ($spamPhrases as $p) {
        if (mb_stripos($t, $p) !== false) {
            $reasons[] = 'clickbait_ifade:' . $p;
        }
    }
    if (preg_match('/!{2,}/u', $t) || preg_match('/\?{2,}/u', $t)) {
        $reasons[] = 'abartili_noktalama';
    }
    if (mb_strlen($t) > 95) {
        $reasons[] = 'baslik_cok_uzun';
    }
    $words = preg_split('/[\s\-–—]+/u', mb_strtolower($t, 'UTF-8')) ?: [];
    $freq = [];
    foreach ($words as $w) {
        if (mb_strlen($w) < 4) {
            continue;
        }
        $freq[$w] = ($freq[$w] ?? 0) + 1;
        if ($freq[$w] >= 3) {
            $reasons[] = 'kelime_tekrari';
            break;
        }
    }
    $upper = preg_match_all('/[A-ZÇĞİÖŞÜ]/u', $t);
    $len = max(1, mb_strlen($t));
    if ($upper > 8 && ($upper / $len) > 0.35) {
        $reasons[] = 'asiri_buyuk_harf';
    }

    return ['ok' => $reasons === [], 'reasons' => $reasons];
}

/**
 * Slug bütünlüğü — DB slug değişmez; içerikte farklı slug sinyali.
 *
 * @return array{ok: bool, reasons: list<string>}
 */
function mynak_ce_slug_integrity_check(string $originalSlug, string $html, ?string $newTitle): array
{
    $reasons = [];
    $slug = mb_strtolower(trim($originalSlug), 'UTF-8');
    if ($slug === '') {
        return ['ok' => false, 'reasons' => ['slug_bos']];
    }
    if ($newTitle !== null && preg_match('/[a-z0-9]+(?:-[a-z0-9]+){2,}/i', $newTitle)) {
        $reasons[] = 'baslikta_slug_benzeri';
    }
    $plain = strip_tags($html);
    if (preg_match_all('#/([a-z0-9]+(?:-[a-z0-9]+)+)#i', $plain, $m)) {
        foreach ($m[1] as $found) {
            $f = mb_strtolower($found, 'UTF-8');
            if ($f !== $slug && str_contains($f, 'nakliyat') && !str_contains($slug, $f) && !str_contains($f, $slug)) {
                $reasons[] = 'icerikte_farkli_slug:' . $f;
                break;
            }
        }
    }

    return ['ok' => $reasons === [], 'reasons' => $reasons];
}

/**
 * Yayın güvenliği QC — kritik fail → needs_revision (durum=2).
 *
 * @param array<string, mixed> $pack
 * @param list<array{path?: string, url?: string}> $linkCatalog
 * @return array<string, mixed>
 */
function mynak_ce_quality_report(
    array $post,
    string $html,
    array $pack,
    array $linkCatalog,
    ?string $newTitle,
    string $modelUsed = ''
): array {
    $slug = (string) ($post['slug'] ?? '');
    $oldTitle = (string) ($post['baslik'] ?? '');
    $wc = function_exists('br_word_count_html') ? br_word_count_html($html) : 0;
    $wMin = (int) ($pack['word_min'] ?? 0);
    $wMax = (int) ($pack['word_max'] ?? 0);
    $wordOkSoft = $wc >= (int) ($wMin * 0.8) && $wc <= (int) ($wMax * 1.2);
    $wordOkCritical = $wc >= (int) ($wMin * 0.75) && $wc <= (int) ($wMax * 1.25);

    $hasH1 = (bool) preg_match('/<h1\b/i', $html);
    preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $m);
    $hrefs = $m[1] ?? [];
    $allowed = [];
    foreach ($linkCatalog as $item) {
        $p = strtolower(rtrim((string) ($item['path'] ?? $item['url'] ?? ''), '/'));
        if ($p !== '') {
            $allowed[$p] = true;
        }
    }
    $offWhitelist = [];
    foreach ($hrefs as $h) {
        $path = strtolower(rtrim((string) (parse_url($h, PHP_URL_PATH) ?: $h), '/'));
        $ok = isset($allowed[$path]);
        if (!$ok) {
            foreach (array_keys($allowed) as $ap) {
                if ($ap !== '' && (str_contains($path, $ap) || str_contains($ap, $path))) {
                    $ok = true;
                    break;
                }
            }
        }
        if (!$ok) {
            $offWhitelist[] = $h;
        }
    }
    $linkCount = count($hrefs);
    $catalogNonEmpty = $linkCatalog !== [];
    $linkOk = !$catalogNonEmpty
        || ($linkCount >= 1 && $linkCount <= 8 && $offWhitelist === []);

    $bannedHits = [];
    $plain = strip_tags($html);
    foreach (mynak_ce_banned_phrases() as $phrase) {
        if (mb_stripos($plain, $phrase) !== false) {
            $bannedHits[] = $phrase;
        }
    }

    $faqExpected = (int) ($pack['faq_count'] ?? 0);
    preg_match_all('/<h3\b/i', $html, $h3m);
    $h3Count = count($h3m[0] ?? []);
    $faqOk = $faqExpected === 0 ? $h3Count <= 2 : $h3Count >= max(1, $faqExpected - 1) && $h3Count <= $faqExpected + 2;

    $salesy = [];
    foreach (['hemen ara', 'kaçırma', 'en iyi firma', 'lider firma', 'tek tercih', '%100 garanti'] as $p) {
        if (mb_stripos($plain, $p) !== false) {
            $salesy[] = $p;
        }
    }

    $microListy = (bool) preg_match('/(?:^|\n)\s*[-•]\s*(?:park|site yönetim|dar merdiven|yüksek kat|şehirlerarası rota)/mu', $plain)
        || (bool) preg_match('/(?:^|\n)\s*\d+\.\s*(?:park|site|merdiven|asansör)/mu', $plain);

    $titleCheck = mynak_ce_title_spam_check($newTitle ?? $oldTitle, $oldTitle);
    $slugCheck = mynak_ce_slug_integrity_check($slug, $html, $newTitle);

    $criticalFail = [];
    if ($hasH1) {
        $criticalFail[] = 'h1';
    }
    if (!$slugCheck['ok']) {
        $criticalFail[] = 'slug';
    }
    if ($offWhitelist !== []) {
        $criticalFail[] = 'off_whitelist_link';
    }
    if (!$wordOkCritical) {
        $criticalFail[] = 'word_range';
    }
    if ($bannedHits !== []) {
        $criticalFail[] = 'banned';
    }
    if ($catalogNonEmpty && ($linkCount < 1 || $linkCount > 8 || $offWhitelist !== [])) {
        $criticalFail[] = 'internal_links';
    }
    if (!$titleCheck['ok']) {
        $criticalFail[] = 'title_spam';
    }
    if ($microListy) {
        $criticalFail[] = 'micro_listy';
    }

    $kwReasons = [];
    $repReasons = [];
    if (function_exists('mynak_ce_qc_keyword_stuffing_check')) {
        $kw = mynak_ce_qc_keyword_stuffing_check($html, $post);
        if (!$kw['ok']) {
            $criticalFail[] = 'keyword_stuffing';
            $kwReasons = $kw['reasons'];
        }
    }
    if (function_exists('mynak_ce_qc_paragraph_repetition_check')) {
        $rep = mynak_ce_qc_paragraph_repetition_check($html);
        if (!$rep['ok']) {
            $criticalFail[] = 'paragraph_repetition';
            $repReasons = $rep['reasons'];
        }
    }

    $warnKeys = [];
    if (!$faqOk) {
        $warnKeys[] = 'faq';
    }
    if ($salesy !== []) {
        $criticalFail[] = 'spam_cta';
    }
    if (!$wordOkSoft && $wordOkCritical) {
        $warnKeys[] = 'word_range_soft';
    }

    $pass = $criticalFail === [];
    if (!function_exists('mynak_ce_qc_gate_status')) {
        require_once __DIR__ . '/mynak_ce_quality_model.php';
    }
    $qcStatus = mynak_ce_qc_gate_status(['pass' => $pass, 'warning_keys' => $warnKeys]);
    $queue = $pass ? 'editor_review' : (defined('MYNAK_CE_QUEUE_DRAFT_INVALID') ? MYNAK_CE_QUEUE_DRAFT_INVALID : 'needs_revision');
    $targetDurum = $pass ? MYNAK_CE_DURUM_EDITOR_REVIEW : MYNAK_CE_DURUM_NEEDS_REVISION;

    $checks = [
        'title_changed' => $newTitle !== null && $newTitle !== $oldTitle,
        'slug_preserved' => $slugCheck['ok'],
        'slug_issues' => $slugCheck['reasons'],
        'no_h1' => !$hasH1,
        'word_range_ok' => $wordOkCritical,
        'word_range_soft_ok' => $wordOkSoft,
        'word_count' => $wc,
        'word_target' => [$wMin, $wMax],
        'internal_link_count' => $linkCount,
        'internal_links_ok' => $linkOk,
        'off_whitelist_links' => $offWhitelist,
        'micro_details_natural' => !$microListy,
        'banned_phrase_hits' => $bannedHits,
        'title_spam_ok' => $titleCheck['ok'],
        'title_spam_reasons' => $titleCheck['reasons'],
        'faq_count_ok' => $faqOk,
        'h3_count' => $h3Count,
        'faq_expected' => $faqExpected,
        'cta_salesy_hits' => $salesy,
        'keyword_stuffing_reasons' => $kwReasons,
        'paragraph_repetition_reasons' => $repReasons,
    ];

    return [
        'post_id' => (int) ($post['id'] ?? 0),
        'slug' => $slug,
        'model' => $modelUsed,
        'pass' => $pass,
        'qc_pass' => $pass,
        'qc_status' => $qcStatus,
        'queue' => $queue,
        'target_durum' => $targetDurum,
        'critical_fail_keys' => $criticalFail,
        'warning_keys' => $warnKeys,
        'fail_keys' => array_values(array_unique(array_merge($criticalFail, $warnKeys))),
        'checks' => $checks,
        'editor_note' => mynak_ce_format_qc_editor_note($pass, $queue, $criticalFail, $warnKeys, $checks, $pack),
    ];
}

/**
 * @param array<string, mixed> $checks
 * @param array<string, mixed> $pack
 */
function mynak_ce_format_qc_editor_note(
    bool $pass,
    string $queue,
    array $criticalFail,
    array $warnKeys,
    array $checks,
    array $pack
): string {
    $lines = [];
    $durumLabel = $pass ? '1 editor_review' : '2 draft_invalid/needs_revision';
    if (!function_exists('mynak_ce_qc_gate_status')) {
        require_once __DIR__ . '/mynak_ce_quality_model.php';
    }
    $gate = mynak_ce_qc_gate_status(['pass' => $pass, 'warning_keys' => $warnKeys]);
    $lines[] = '[QC_GATE] ' . $gate . ' → ' . $queue . ' (durum=' . $durumLabel . ')';
    $lines[] = 'Otomatik yayın YOK — editör onayı şart.';
    if ($criticalFail !== []) {
        $lines[] = 'Kritik hatalar: ' . implode(', ', $criticalFail);
    } else {
        $lines[] = 'Kritik hatalar: yok';
    }
    if ($warnKeys !== []) {
        $lines[] = 'Uyarılar: ' . implode(', ', $warnKeys);
    }
    $lines[] = 'Kelime: ' . (int) ($checks['word_count'] ?? 0)
        . ' / hedef ' . (int) ($pack['word_min'] ?? 0) . '–' . (int) ($pack['word_max'] ?? 0);
    $lines[] = 'İç link: ' . (int) ($checks['internal_link_count'] ?? 0);
    if (!empty($checks['off_whitelist_links'])) {
        $lines[] = 'Whitelist dışı: ' . implode(', ', (array) $checks['off_whitelist_links']);
    }
    if (!empty($checks['banned_phrase_hits'])) {
        $lines[] = 'Yasak kalıp: ' . implode(', ', (array) $checks['banned_phrase_hits']);
    }
    if (!empty($checks['title_spam_reasons'])) {
        $lines[] = 'Başlık spam: ' . implode(', ', (array) $checks['title_spam_reasons']);
    }

    return implode("\n", $lines);
}

/**
 * 10 yazılık production test planı + eksik kova raporu.
 *
 * @return array{items: list<array{id: int, bucket: string}>, gaps: array<string, array{needed: int, found: int}>, ready: bool}
 */
function mynak_ce_production_test_audit(mysqli $conn): array
{
    $buckets = [
        ['bucket' => 'ana_hizmet', 'needed' => 3, 'sql' => "durum=3 AND (slug LIKE '%evden-eve%' OR baslik LIKE '%evden eve%') AND slug NOT LIKE '%sehirler%' ORDER BY id ASC LIMIT 3"],
        ['bucket' => 'sehirlerarasi', 'needed' => 2, 'sql' => "durum=3 AND (slug LIKE '%sehirler%' OR baslik LIKE '%şehirlerarası%' OR baslik LIKE '%sehirlerarasi%') ORDER BY id ASC LIMIT 2"],
        ['bucket' => 'asansorlu_teknik', 'needed' => 2, 'sql' => "durum=3 AND (slug LIKE '%asansor%' OR slug LIKE '%vinc%' OR slug LIKE '%vinç%') ORDER BY id ASC LIMIT 2"],
        ['bucket' => 'depolama', 'needed' => 1, 'sql' => "durum=3 AND (slug LIKE '%depo%' OR baslik LIKE '%depolama%') ORDER BY id ASC LIMIT 1"],
        ['bucket' => 'parca_esya', 'needed' => 1, 'sql' => "durum=3 AND (slug LIKE '%parca%' OR baslik LIKE '%parça%') ORDER BY id ASC LIMIT 1"],
        ['bucket' => 'ofis', 'needed' => 1, 'sql' => "durum=3 AND (slug LIKE '%ofis%' OR baslik LIKE '%ofis%') ORDER BY id ASC LIMIT 1"],
    ];
    $items = [];
    $gaps = [];
    $seen = [];
    foreach ($buckets as $b) {
        $found = 0;
        $q = $conn->query('SELECT id FROM blog_posts WHERE ' . $b['sql']);
        if ($q) {
            while ($row = $q->fetch_assoc()) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0 && !isset($seen[$id])) {
                    $seen[$id] = true;
                    $items[] = ['id' => $id, 'bucket' => $b['bucket']];
                    ++$found;
                }
            }
        }
        if ($found < $b['needed']) {
            $gaps[$b['bucket']] = ['needed' => $b['needed'], 'found' => $found];
        }
    }

    return [
        'items' => $items,
        'gaps' => $gaps,
        'ready' => $gaps === [],
    ];
}

/** @return list<array{id: int, bucket: string}> */
function mynak_ce_production_test_plan(mysqli $conn): array
{
    return mynak_ce_production_test_audit($conn)['items'];
}

/** Eski API uyumluluğu. */
function mynak_ce_build_user_layers(array $post, array $pack): string
{
    if (function_exists('mynak_ce_assemble_prompt')) {
        return (string) (mynak_ce_assemble_prompt($post, [], null)['prompt'] ?? '');
    }

    return 'KONU: ' . ($post['baslik'] ?? '');
}
