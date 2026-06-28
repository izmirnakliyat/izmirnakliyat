<?php
declare(strict_types=1);

$teklifUrl = mynak_public_path('teklif-alin');
$evdenEveUrl = mynak_public_path('izmir-evden-eve-nakliyat');
$sehirlerArasiUrl = mynak_public_path('sehirler-arasi-nakliyat');
$asansorUrl = mynak_public_path('asansorlu-nakliyat');

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$izmirRows = [
    ['tip' => '1+1 daire', 'aralik' => '8.000 – 14.000 TL', 'sure' => '4 – 6 saat'],
    ['tip' => '2+1 daire', 'aralik' => '12.000 – 20.000 TL', 'sure' => '5 – 8 saat'],
    ['tip' => '3+1 daire', 'aralik' => '18.000 – 28.000 TL', 'sure' => '6 – 10 saat'],
    ['tip' => '4+1 / villa', 'aralik' => '25.000 – 45.000 TL', 'sure' => '8 – 14 saat'],
];

$extras = [
    ['label' => 'Asansörlü taşıma', 'note' => 'Kat yüksekliği ve erişime göre +2.000 – 6.000 TL'],
    ['label' => 'Profesyonel paketleme', 'note' => 'Eşya hacmine göre +1.500 – 5.000 TL'],
    ['label' => 'Parça eşya / tek parça', 'note' => 'Genelde 2.500 – 6.000 TL (mesafe + parça sayısı)'],
    ['label' => 'Şehirler arası (İzmir çıkışlı)', 'note' => 'Km + m³ bazlı; İstanbul/Ankara için 18.000 – 35.000+ TL bandı'],
];
?>
<section id="mynak-pricing-landing" aria-labelledby="price-hero-title">
    <div class="lm-wrap">
        <header class="lm-hero">
            <span class="lm-badge">2026 güncel rehber</span>
            <h1 id="price-hero-title">İzmir Nakliyat Fiyatları — Şeffaf Aralıklar</h1>
            <p class="lm-hero-lead">Evden eve taşıma ücreti eşya hacmi, kat, mesafe ve ek hizmetlere göre değişir. Aşağıdaki tablo <strong>İzmir içi</strong> taşımalar için tipik aralıkları gösterir; kesin fiyat yalnızca ücretsiz keşif sonrası yazılı teklif ile netleşir.</p>
            <div class="lm-trust-row" role="list">
                <span role="listitem">Sigortalı taşıma</span>
                <span role="listitem">Ücretsiz keşif</span>
                <span role="listitem">Yazılı teklif</span>
            </div>
        </header>

        <div class="price-grid">
            <div class="price-card price-card-main">
                <h2>İzmir içi evden eve nakliyat (2026)</h2>
                <p class="price-note">Paketleme dahil değildir; keşifte netleştirilir. Sezon (yaz) ve hafta sonu talebi fiyatı etkileyebilir.</p>
                <div class="table-responsive">
                    <table class="price-table">
                        <thead>
                        <tr>
                            <th scope="col">Konut tipi</th>
                            <th scope="col">Tipik fiyat aralığı</th>
                            <th scope="col">Süre (tahmini)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($izmirRows as $row): ?>
                            <tr>
                                <td><?php echo $esc($row['tip']); ?></td>
                                <td><strong><?php echo $esc($row['aralik']); ?></strong></td>
                                <td><?php echo $esc($row['sure']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="price-disclaimer">* Rakamlar bilgilendirme amaçlıdır; eşya yoğunluğu, asansör, otopark ve mesafe aralığı değiştirebilir.</p>
            </div>

            <aside class="price-card price-card-side">
                <h2>Fiyatı ne artırır?</h2>
                <ul class="price-factor-list">
                    <?php foreach ($extras as $ex): ?>
                        <li>
                            <strong><?php echo $esc($ex['label']); ?></strong>
                            <span><?php echo $esc($ex['note']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="lm-btn lm-btn-primary price-cta" href="<?php echo $esc($teklifUrl); ?>">Ücretsiz teklif alın</a>
                <p class="price-micro">2 dakikada form · Aynı gün dönüş</p>
            </aside>
        </div>

        <div class="price-links">
            <h2>İlgili hizmetler</h2>
            <ul>
                <li><a href="<?php echo $esc($evdenEveUrl); ?>">İzmir evden eve nakliyat</a></li>
                <li><a href="<?php echo $esc($sehirlerArasiUrl); ?>">Şehirler arası nakliyat</a></li>
                <li><a href="<?php echo $esc($asansorUrl); ?>">Asansörlü nakliyat</a></li>
                <li><a href="<?php echo $esc(mynak_public_path('blog')); ?>">Nakliyat rehberleri (blog)</a></li>
            </ul>
        </div>

        <div class="price-faq lm-card">
            <h2>Sık sorulan sorular</h2>
            <h3>İzmir evden eve nakliyat fiyatı nasıl hesaplanır?</h3>
            <p>Eşya hacmi (m³), kat sayısı, asansör veya asansörlü vinç ihtiyacı, taşıma mesafesi, paketleme kapsamı ve sigorta tercihi birlikte değerlendirilir. Keşif sonrası yazılı teklif sunulur.</p>
            <h3>Keşif ücretsiz mi, taahhüt var mı?</h3>
            <p>Evet — yerinde veya görüntülü keşif ücretsizdir; teklifi kabul etmek zorunda değilsiniz.</p>
            <h3>En ucuz nakliyat firması nasıl seçilir?</h3>
            <p>Yalnızca en düşük fiyata bakmak risklidir. Sigorta, sözleşme, ekipman ve Google yorumları birlikte değerlendirilmeli; yazılı teklifte gizli kalem olmamalıdır.</p>
            <h3>Şehirler arası taşıma fiyatı ne kadar?</h3>
            <p>İzmir’den İstanbul veya Ankara gibi güzergâhlarda 18.000 TL’den başlayan bandlar görülür; eşya hacmi ve km arttıkça üst sınır yükselir. Detay için şehirler arası sayfamıza bakın.</p>
            <h3>Asansörlü taşıma ek ücreti ne kadar?</h3>
            <p>Genelde 2.000 – 6.000 TL aralığında eklenir; bina yüksekliği ve vinç süresine bağlıdır.</p>
        </div>

        <div class="price-bottom-cta">
            <p>Kesin fiyat için keşif şart — tablodaki aralıklar planlama içindir.</p>
            <a class="lm-btn lm-btn-primary" href="<?php echo $esc($teklifUrl); ?>">Hemen teklif alın</a>
        </div>
    </div>
</section>
