<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * includes/functions.php yardimci fonksiyonlari birim testleri.
 *
 * DB bagimliligi olmayan saf fonksiyonlar test edilir.
 */
class FunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('MYNAK_BOOTSTRAP_LOADED')) {
            define('MYNAK_BOOTSTRAP_LOADED', true);
        }
        require_once PROJECT_ROOT . '/config/env_functions.php';
        if (!function_exists('mynak_http_host_is_local')) {
            require_once PROJECT_ROOT . '/config/environment.php';
        }
        if (!defined('SITE_NAME')) {
            define('SITE_NAME', 'MY Nakliyat');
        }
        if (!defined('MYNAK_BRAND_NAME')) {
            define('MYNAK_BRAND_NAME', 'MY Nakliyat');
        }
        if (!defined('MYNAK_CONTACT_PHONE_DISPLAY')) {
            define('MYNAK_CONTACT_PHONE_DISPLAY', '+90 850 203 12 52');
        }
        if (!defined('MYNAK_CONTACT_WHATSAPP_DISPLAY')) {
            define('MYNAK_CONTACT_WHATSAPP_DISPLAY', '+90 850 203 12 52');
        }
        if (!defined('MYNAK_CONTACT_ADDRESS_DISPLAY')) {
            define('MYNAK_CONTACT_ADDRESS_DISPLAY', 'Seyhan, 653/2. Sk. :10 K:3, Buca/İzmir');
        }
        if (!defined('MYNAK_CONTACT_GOOGLE_MAPS_URL')) {
            define('MYNAK_CONTACT_GOOGLE_MAPS_URL', 'https://maps.app.goo.gl/KhjpeauhbhoXaupZ8');
        }
        if (!defined('MYNAK_CONTACT_MAP_EMBED_SRC')) {
            define('MYNAK_CONTACT_MAP_EMBED_SRC', 'https://www.google.com/maps?q=test&output=embed');
        }
        if (!defined('SITE_URL')) {
            define('SITE_URL', 'https://www.mynakliyat.com.tr');
        }
        if (!function_exists('seo_runtime_pipeline_request_normalize')) {
            function seo_runtime_pipeline_request_normalize(): void {}
        }
        require_once PROJECT_ROOT . '/includes/blog_post_status.php';
        require_once PROJECT_ROOT . '/includes/mynak_seo_length_helpers.php';
        if (!function_exists('mynak_decode_html_entities')) {
            require_once PROJECT_ROOT . '/includes/functions.php';
        }
    }

    // --- mynak_decode_html_entities ---

    public function testDecodeHtmlEntitiesBasic(): void
    {
        $this->assertSame('Test & value', mynak_decode_html_entities('Test &amp; value'));
    }

    public function testDecodeHtmlEntitiesLegacyTurkish(): void
    {
        $this->assertSame('ç', mynak_decode_html_entities('&c_cedil;'));
        $this->assertSame('Ç', mynak_decode_html_entities('&C_cedil;'));
        $this->assertSame('ş', mynak_decode_html_entities('&s_cedil;'));
        $this->assertSame('Ş', mynak_decode_html_entities('&S_cedil;'));
        $this->assertSame('ğ', mynak_decode_html_entities('&g_breve;'));
        $this->assertSame('Ğ', mynak_decode_html_entities('&G_breve;'));
        $this->assertSame('ü', mynak_decode_html_entities('&u_uml;'));
        $this->assertSame('Ü', mynak_decode_html_entities('&U_uml;'));
        $this->assertSame('ö', mynak_decode_html_entities('&o_uml;'));
        $this->assertSame('Ö', mynak_decode_html_entities('&O_uml;'));
        $this->assertSame('ı', mynak_decode_html_entities('&i_dotless;'));
        $this->assertSame('İ', mynak_decode_html_entities('&I_dot;'));
    }

    public function testDecodeHtmlEntitiesEmpty(): void
    {
        $this->assertSame('', mynak_decode_html_entities(''));
    }

    public function testDecodeHtmlEntitiesDoubleEncoded(): void
    {
        $this->assertSame('&', mynak_decode_html_entities('&amp;amp;'));
    }

    // --- mynak_normalize_db_text ---

    public function testNormalizeDbText(): void
    {
        $this->assertSame('Türkçe', mynak_normalize_db_text('T&u_uml;rk&c_cedil;e'));
    }

    // --- mynak_esc_html ---

    public function testEscHtml(): void
    {
        $result = mynak_esc_html('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testEscHtmlDecodesFirst(): void
    {
        $result = mynak_esc_html('&amp;');
        $this->assertSame('&amp;', $result);
    }

    // --- guvenli_cikti ---

    public function testGuvenliCikti(): void
    {
        $this->assertSame('&lt;b&gt;test&lt;/b&gt;', guvenli_cikti('<b>test</b>'));
    }

    // --- mynak_section_heading_inner_html ---

    public function testSectionHeadingInnerHtml(): void
    {
        $this->assertSame('Baslik', mynak_section_heading_inner_html('<h2>Baslik</h2>'));
        $this->assertSame('Baslik', mynak_section_heading_inner_html('<h1 class="foo">Baslik</h1>'));
    }

    public function testSectionHeadingInnerHtmlNoHeading(): void
    {
        $this->assertSame('Duz metin', mynak_section_heading_inner_html('Duz metin'));
    }

    public function testSectionHeadingInnerHtmlEmpty(): void
    {
        $this->assertSame('', mynak_section_heading_inner_html(''));
    }

    // --- mynak_is_meaningless_image_label ---

    public function testIsMeaninglessImageLabelEmpty(): void
    {
        $this->assertTrue(mynak_is_meaningless_image_label(''));
        $this->assertTrue(mynak_is_meaningless_image_label('   '));
    }

    public function testIsMeaninglessImageLabelGalleryImage(): void
    {
        $this->assertTrue(mynak_is_meaningless_image_label('gallery image'));
        $this->assertTrue(mynak_is_meaningless_image_label('Gallery Image'));
    }

    public function testIsMeaninglessImageLabelSponsor(): void
    {
        $this->assertTrue(mynak_is_meaningless_image_label('Sponsor 1'));
        $this->assertTrue(mynak_is_meaningless_image_label('sponsor 99'));
    }

    public function testIsMeaninglessImageLabelFilename(): void
    {
        $this->assertTrue(mynak_is_meaningless_image_label('photo.jpg'));
        $this->assertTrue(mynak_is_meaningless_image_label('IMG_1234'));
        $this->assertTrue(mynak_is_meaningless_image_label('DSC00123'));
    }

    public function testIsMeaninglessImageLabelHashOnly(): void
    {
        $this->assertTrue(mynak_is_meaningless_image_label('68641b52e38bc'));
    }

    public function testIsMeaninglessImageLabelNumericOnly(): void
    {
        $this->assertTrue(mynak_is_meaningless_image_label('1234567'));
        $this->assertTrue(mynak_is_meaningless_image_label('12-34-56'));
    }

    public function testIsMeaninglessImageLabelMeaningful(): void
    {
        $this->assertFalse(mynak_is_meaningless_image_label('İzmir evden eve nakliyat'));
        $this->assertFalse(mynak_is_meaningless_image_label('MY Nakliyat kamyon'));
    }

    // --- mynak_alt_label_from_src_path ---

    public function testAltLabelFromSrcPathNormal(): void
    {
        $result = mynak_alt_label_from_src_path('/uploads/izmir-nakliyat.jpg');
        $this->assertSame('izmir nakliyat', $result);
    }

    public function testAltLabelFromSrcPathHash(): void
    {
        $result = mynak_alt_label_from_src_path('/uploads/68641b52e38bc.jpg');
        $this->assertSame('', $result);
    }

    public function testAltLabelFromSrcPathNull(): void
    {
        $this->assertSame('', mynak_alt_label_from_src_path(null));
    }

    public function testAltLabelFromSrcPathEmpty(): void
    {
        $this->assertSame('', mynak_alt_label_from_src_path(''));
    }

    // --- mynak_public_image_alt ---

    public function testPublicImageAltReturnsCandidate(): void
    {
        $this->assertSame('Bornova nakliyat', mynak_public_image_alt('Bornova nakliyat'));
    }

    public function testPublicImageAltFallsBackToPageTitle(): void
    {
        $this->assertSame('Sayfa Basligi', mynak_public_image_alt('', '', null, 'Sayfa Basligi'));
    }

    public function testPublicImageAltFallsBackToFallback(): void
    {
        $this->assertSame('Fallback metin', mynak_public_image_alt('', 'Fallback metin'));
    }

    public function testPublicImageAltFallsBackToDefault(): void
    {
        $this->assertSame('MY Nakliyat', mynak_public_image_alt(''));
    }

    public function testPublicImageAltRejectsMeaningless(): void
    {
        $result = mynak_public_image_alt('gallery image', 'fallback');
        $this->assertSame('fallback', $result);
    }

    // --- mynak_is_decorative_image_src ---

    public function testIsDecorativeImageSrcTruckSvg(): void
    {
        $this->assertTrue(mynak_is_decorative_image_src('/assets/img/truck.svg'));
    }

    public function testIsDecorativeImageSrcPatternSvg(): void
    {
        $this->assertTrue(mynak_is_decorative_image_src('/img/shape/circle.png'));
    }

    public function testIsDecorativeImageSrcClass(): void
    {
        $this->assertTrue(mynak_is_decorative_image_src('/img/foo.png', 'sh-truck'));
        $this->assertTrue(mynak_is_decorative_image_src('/img/foo.png', 'decorative'));
    }

    public function testIsDecorativeImageSrcNonDecorative(): void
    {
        $this->assertFalse(mynak_is_decorative_image_src('/uploads/nakliyat-foto.jpg'));
    }

    // --- mynak_decorative_img_attrs ---

    public function testDecorativeImgAttrs(): void
    {
        $result = mynak_decorative_img_attrs();
        $this->assertStringContainsString('alt=""', $result);
        $this->assertStringContainsString('role="presentation"', $result);
        $this->assertStringContainsString('aria-hidden="true"', $result);
    }

    // --- mynak_sh_truck_img_attrs ---

    public function testShTruckImgAttrs(): void
    {
        $result = mynak_sh_truck_img_attrs();
        $this->assertStringContainsString('width="36"', $result);
        $this->assertStringContainsString('height="36"', $result);
    }

    // --- mynak_slide_variant_path ---

    public function testSlideVariantPathMob(): void
    {
        $result = mynak_slide_variant_path('slides', 'hero', 'mob');
        $this->assertSame('slides/hero-mob.webp', $result);
    }

    public function testSlideVariantPathAvif(): void
    {
        $result = mynak_slide_variant_path('slides/', 'hero', 'avif');
        $this->assertSame('slides/hero.avif', $result);
    }

    // --- mynak_slide_mime_from_filename ---

    public function testSlideMimeJpg(): void
    {
        $this->assertSame('image/jpeg', mynak_slide_mime_from_filename('photo.jpg'));
    }

    public function testSlideMimeJpeg(): void
    {
        $this->assertSame('image/jpeg', mynak_slide_mime_from_filename('photo.jpeg'));
    }

    public function testSlideMimePng(): void
    {
        $this->assertSame('image/png', mynak_slide_mime_from_filename('icon.png'));
    }

    public function testSlideMimeWebp(): void
    {
        $this->assertSame('image/webp', mynak_slide_mime_from_filename('hero.webp'));
    }

    public function testSlideMimeAvif(): void
    {
        $this->assertSame('image/avif', mynak_slide_mime_from_filename('img.avif'));
    }

    public function testSlideMimeGif(): void
    {
        $this->assertSame('image/gif', mynak_slide_mime_from_filename('anim.gif'));
    }

    public function testSlideMimeUnknown(): void
    {
        $this->assertSame('image/jpeg', mynak_slide_mime_from_filename('file.bmp'));
    }

    // --- mynak_contact_settings_defaults ---

    public function testContactSettingsDefaultsReturnsExpectedKeys(): void
    {
        $defaults = mynak_contact_settings_defaults();
        $this->assertArrayHasKey('phone1', $defaults);
        $this->assertArrayHasKey('whatsapp', $defaults);
        $this->assertArrayHasKey('address', $defaults);
        $this->assertArrayHasKey('google_maps_url', $defaults);
        $this->assertArrayHasKey('contact_map_embed', $defaults);
    }

    // --- mynak_apply_contact_defaults_to_settings_array ---

    public function testApplyContactDefaultsFillsEmpty(): void
    {
        $settings = ['phone1' => '', 'whatsapp' => ''];
        $result = mynak_apply_contact_defaults_to_settings_array($settings);
        $this->assertNotEmpty($result['phone1']);
        $this->assertNotEmpty($result['whatsapp']);
    }

    public function testApplyContactDefaultsDoesNotOverride(): void
    {
        $settings = ['phone1' => '+90 555 111 22 33'];
        $result = mynak_apply_contact_defaults_to_settings_array($settings);
        $this->assertSame('+90 555 111 22 33', $result['phone1']);
    }

    // --- mynak_contact_default_map_embed_html ---

    public function testContactDefaultMapEmbedHtml(): void
    {
        $result = mynak_contact_default_map_embed_html();
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('allowfullscreen', $result);
    }
}
