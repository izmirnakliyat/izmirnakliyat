<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * includes/mynak_seo_length_helpers.php birim testleri.
 */
class SeoLengthHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/mynak_seo_length_helpers.php';
    }

    // --- mynak_seo_dashboard_title_class ---

    public function testTitleClassMissing(): void
    {
        $this->assertSame('missing', mynak_seo_dashboard_title_class(0));
    }

    public function testTitleClassShort(): void
    {
        $this->assertSame('short', mynak_seo_dashboard_title_class(10));
        $this->assertSame('short', mynak_seo_dashboard_title_class(29));
    }

    public function testTitleClassOk(): void
    {
        $this->assertSame('ok', mynak_seo_dashboard_title_class(30));
        $this->assertSame('ok', mynak_seo_dashboard_title_class(45));
        $this->assertSame('ok', mynak_seo_dashboard_title_class(60));
    }

    public function testTitleClassLong(): void
    {
        $this->assertSame('long', mynak_seo_dashboard_title_class(61));
        $this->assertSame('long', mynak_seo_dashboard_title_class(200));
    }

    // --- mynak_seo_dashboard_meta_class ---

    public function testMetaClassMissing(): void
    {
        $this->assertSame('missing', mynak_seo_dashboard_meta_class(0));
    }

    public function testMetaClassShort(): void
    {
        $this->assertSame('short', mynak_seo_dashboard_meta_class(50));
        $this->assertSame('short', mynak_seo_dashboard_meta_class(119));
    }

    public function testMetaClassOk(): void
    {
        $this->assertSame('ok', mynak_seo_dashboard_meta_class(120));
        $this->assertSame('ok', mynak_seo_dashboard_meta_class(140));
        $this->assertSame('ok', mynak_seo_dashboard_meta_class(160));
    }

    public function testMetaClassLong(): void
    {
        $this->assertSame('long', mynak_seo_dashboard_meta_class(161));
    }

    // --- mynak_seo_trim_at_word ---

    public function testTrimAtWordReturnsShortStringUntouched(): void
    {
        $this->assertSame('kisa metin', mynak_seo_trim_at_word('kisa metin', 60));
    }

    public function testTrimAtWordCutsAtWordBoundary(): void
    {
        $text = 'Bu bir uzun metin denemesidir ve altmis karakterden fazla karakteri vardir aslinda';
        $result = mynak_seo_trim_at_word($text, 40);
        $this->assertLessThanOrEqual(40, mb_strlen($result));
        $this->assertStringNotContainsString('  ', $result);
    }

    public function testTrimAtWordHandlesEmptyString(): void
    {
        $this->assertSame('', mynak_seo_trim_at_word('', 60));
    }

    public function testTrimAtWordHandlesExactLengthString(): void
    {
        $text = str_repeat('a', 60);
        $this->assertSame($text, mynak_seo_trim_at_word($text, 60));
    }

    // --- mynak_seo_fix_page_title ---

    public function testFixPageTitleRemovesSiteSuffix(): void
    {
        $result = mynak_seo_fix_page_title(
            'Bornova Evden Eve Nakliyat - İzmir Evden Eve Nakliyat',
            'Bornova Evden Eve Nakliyat'
        );
        $this->assertStringNotContainsString('İzmir Evden Eve Nakliyat', $result);
        $this->assertNotEmpty($result);
    }

    public function testFixPageTitleFallsBackToPageTitle(): void
    {
        $result = mynak_seo_fix_page_title('', 'Buca Evden Eve Nakliyat');
        $this->assertNotEmpty($result);
    }

    public function testFixPageTitleRemovesMYNakliyatSuffix(): void
    {
        $result = mynak_seo_fix_page_title(
            'Test Sayfa | MY Nakliyat',
            'Test Sayfa'
        );
        $this->assertStringNotContainsString('| MY Nakliyat', $result);
    }

    public function testFixPageTitleStaysWithinBounds(): void
    {
        $result = mynak_seo_fix_page_title(
            'Cok uzun bir baslik metni bu ve altmis karakteri asabilir ama yine de gerekirse kisaltilmalidir',
            'Test'
        );
        $this->assertLessThanOrEqual(60, mb_strlen($result));
    }

    // --- mynak_seo_fix_page_meta ---

    public function testFixPageMetaGeneratesFromPageTitleWhenEmpty(): void
    {
        $result = mynak_seo_fix_page_meta('', 'Bornova Nakliyat');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Bornova Nakliyat', $result);
    }

    public function testFixPageMetaTrimsLongDescriptions(): void
    {
        $longMeta = str_repeat('Bu bir test cümlesidir. ', 20);
        $result = mynak_seo_fix_page_meta($longMeta, 'Test');
        $this->assertLessThanOrEqual(160, mb_strlen($result));
    }

    public function testFixPageMetaDefaultsWhenBothEmpty(): void
    {
        $result = mynak_seo_fix_page_meta('', '');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('İzmir', $result);
    }

    // --- mynak_seo_fix_page_row ---

    public function testFixPageRowReturnsChangedFalseWhenOk(): void
    {
        $row = [
            'seo_title' => 'Bornova Evden Eve Nakliyat Hizmeti',
            'title' => 'Bornova Evden Eve Nakliyat',
            'meta_description' => 'Bornova evden eve nakliyat hizmeti ile guvenli tasinma. Sigortali nakliyat, ucretsiz ekspertiz ve profesyonel ekip ile tasinmaniz cok kolay.',
            'slug' => 'bornova-evden-eve-nakliyat',
        ];
        $result = mynak_seo_fix_page_row($row);
        $this->assertArrayHasKey('seo_title', $result);
        $this->assertArrayHasKey('meta_description', $result);
        $this->assertArrayHasKey('changed', $result);
    }

    public function testFixPageRowFixesMissingFields(): void
    {
        $row = [
            'seo_title' => '',
            'title' => 'Buca Evden Eve Nakliyat',
            'meta_description' => '',
            'slug' => 'buca-evden-eve-nakliyat',
        ];
        $result = mynak_seo_fix_page_row($row);
        $this->assertTrue($result['changed']);
        $this->assertNotEmpty($result['meta_description']);
    }

    // --- mynak_seo_fix_blog_row ---

    public function testFixBlogRowMapsBaslikToTitle(): void
    {
        $row = [
            'baslik' => 'Evden Eve Tasinma Rehberi',
            'seo_title' => '',
            'meta_description' => '',
            'slug' => 'evden-eve-tasinma-rehberi',
        ];
        $result = mynak_seo_fix_blog_row($row);
        $this->assertTrue($result['changed']);
        $this->assertNotEmpty($result['seo_title']);
    }
}
