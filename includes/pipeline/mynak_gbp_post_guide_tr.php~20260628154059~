<?php
declare(strict_types=1);

/**
 * B4: Google İşletme Profili (GBP) gönderi stratejisi — sabit rehber metni (TR).
 * Hem public sayfa hem admin yardım sayfası aynı içeriği kullanır.
 */
function mynak_gbp_post_guide_tr_html(): string
{
    $teklif = function_exists('mynak_public_path') ? mynak_public_path('teklif-alin') : '/teklif-alin';
    $galeri = function_exists('mynak_public_path') ? mynak_public_path('galeri') : '/galeri';
    $blog = function_exists('mynak_public_path') ? mynak_public_path('blog') : '/blog';

    $t = htmlspecialchars($teklif, ENT_QUOTES, 'UTF-8');
    $g = htmlspecialchars($galeri, ENT_QUOTES, 'UTF-8');
    $b = htmlspecialchars($blog, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<article class="mynak-gbp-rehber">
    <h2>Google İşletme Profili: gönderi planı ve otomasyona hazırlık</h2>
    <p>GBP (Google Haritalar / “Google benim işletmem”) yalnızca telefon değil; <strong>duyurular</strong> ile taze sinyal verir. Aşağıdaki plan; haftalık manuel yayımla veya ekip randevu takvimine koy. Tam otomasyon için harici (Resmo, GMB API lisans, üçüncü taraf) araçlar gerekir; bu rehber <strong>ne ve ne zaman</strong> paylaşılacağını standartlaştırır.</p>

    <h3>1. Hedef: hafta içi 1–2 gönderi</h3>
    <ul>
        <li><strong>Hafta ortası (Salı–Çarşamba):</strong> hizmet / referans ağırlıklı: evden eve, şehirler arası, eşya depolama, parça eşya.</li>
        <li><strong>Hafta sonu (Cumartesi):</strong> fırsat veya bölge — İzmir ilçe, yeni rota, saha kısa not.</li>
    </ul>

    <h3>2. Biçim ve içerik</h3>
    <ul>
        <li>İlk 2 cümlede: kimin için + net hizmet + İzmir veya 81 il vurgusu.</li>
        <li>1 satır <strong>CTA</strong>: “Hemen Ara / WhatsApp / Teklif” (panelde dönüştüğünüz numara / bağlantı).</li>
        <li><strong>1 dikey / kare</strong> saha veya ekip foto; mümkünse MY Nakliyat aracı / logosu veya telifsiz ekip çekimi.</li>
        <li>Etiket (buton) olarak <strong>“Teklif al”</strong> veya <strong>“Ara”</strong> (işletmeye uygun olandan birini) seçin; çift tık çağrısını aşırı kullanmayın.</li>
    </ul>

    <h3>3. Konu fikir şablonları (kopyala – özelleştir)</h3>
    <ol>
        <li>“<strong>Keşif + yazılı teklif</strong> aynı gün — Buca, Karşıyaka, Bornova evden eve; şehirler arası tır planı.”</li>
        <li>“<strong>Parça eşya</strong> ve koli — depo günü, montaj, sigorta maddeleri açık.”</li>
        <li>“<strong>Ofis &amp; arşiv</strong> — hafta sonu taşımada ekstra sütun.”</li>
        <li>“<strong>İzmir → İstanbul / Ankara</strong> hattı — dönüş boşluk, termin net.”</li>
    </ol>

    <h3>4. Foto: ana sayfa galerisiyle uyum (web)</h3>
    <p>Ana sayfadaki galeri alanı, yayımlı karelerin bir kısmını gösterir. GBP’de paylaşacağınız saha karelerini mümkünse <strong>galeride aynı anda öne çıkmamış</strong> karelerden veya aynı rota, farklı açıdan seçin; Harita ve sitede tekrar hissi azalır.</p>

    <h3>5. Raporlama (Clarity + GA4)</h3>
    <p>Clarity ısı haritaları, GA4 dönüşüm etkinlikleri ve GBP “Ara / rota / web site” tıkları birlikte okunur. Ayarlardan Clarity açık ise sayfa tıkları ile GBP “Web sitesi” tıkları çapraz kontrol edilebilir.</p>

    <h3>6. Hızlı çekirdek (otomasyon)</h3>
    <ul>
        <li>Takvime: her Pazartesi 15 dk. — haftanın metni + görsel; Salı 10:00’da yayın.</li>
        <li>Aynı metni 30 gün sonra tazeleyin (tekrar tespitini azaltmak için 2. paragrafı değiştirin).</li>
    </ul>

    <p class="mynak-gbp-cta">İletişim ve teklif: <a href="{$t}">Teklif alın</a> · <a href="{$g}">Galeri</a> · <a href="{$b}">Blog</a></p>
</article>
HTML;
}
