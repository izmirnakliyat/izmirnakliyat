<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * includes/mynak_meta_description.php birim testleri.
 * Runtime meta açıklama clamp/varsayılan üretiminin davranışını kilitler.
 */
class MetaDescriptionTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/includes/mynak_meta_description.php';
    }

    // --- mynak_meta_description_clamp ---

    public function testClampReturnsEmptyForEmpty(): void
    {
        $this->assertSame('', mynak_meta_description_clamp(''));
        $this->assertSame('', mynak_meta_description_clamp('   '));
    }

    public function testClampLeavesShortTextUntouched(): void
    {
        $text = 'Kısa bir meta açıklaması.';
        $this->assertSame($text, mynak_meta_description_clamp($text, 160));
    }

    public function testClampCollapsesWhitespace(): void
    {
        $this->assertSame('a b c', mynak_meta_description_clamp("a   b\n\tc", 160));
    }

    public function testClampTrimsLongTextWithinBound(): void
    {
        $text = str_repeat('kelime ', 60); // ~420 karakter
        $result = mynak_meta_description_clamp($text, 160);
        // İçerik 160'ı aşmaz; sona tek '…' eklenebilir.
        $this->assertLessThanOrEqual(161, mb_strlen($result));
        $this->assertStringEndsWith('…', $result);
        $this->assertStringNotContainsString('  ', $result);
    }

    public function testClampCutsAtWordBoundary(): void
    {
        $text = 'Bu cümle epeyce uzun ve belirli bir noktada kelime sınırında kesilmesi gerekiyor çünkü limit aşıldı burada devam ediyor uzun uzun metin daha da uzuyor';
        $result = mynak_meta_description_clamp($text, 80);
        $this->assertLessThanOrEqual(81, mb_strlen($result));
        // Kelime ortasında değil, boşlukta kesilmeli (ellipsis öncesi tam kelime).
        $core = rtrim($result, '…');
        $this->assertFalse(str_ends_with($core, ' '), 'Ellipsis öncesi sonda boşluk kalmamalı');
    }

    // --- mynak_default_meta_description_for_page ---

    public function testDefaultMetaContainsBrand(): void
    {
        $result = mynak_default_meta_description_for_page('Bornova Evden Eve Nakliyat', 'bornova-evden-eve-nakliyat');
        $this->assertStringContainsString('MY Nakliyat', $result);
        $this->assertLessThanOrEqual(161, mb_strlen($result));
    }

    public function testDefaultMetaContainsTitle(): void
    {
        $result = mynak_default_meta_description_for_page('Buca Nakliyat', 'buca');
        $this->assertStringContainsString('Buca Nakliyat', $result);
    }

    public function testDefaultMetaRehberVariant(): void
    {
        $result = mynak_default_meta_description_for_page('Taşınma Rehberi', 'nakliyat-rehberi');
        $this->assertStringContainsString('rehberi', mb_strtolower($result));
        $this->assertStringContainsString('MY Nakliyat', $result);
    }

    public function testDefaultMetaFallsBackWhenTitleEmpty(): void
    {
        $result = mynak_default_meta_description_for_page('', '');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('MY Nakliyat', $result);
    }

    public function testDefaultMetaStaysWithinBound(): void
    {
        $longTitle = str_repeat('Uzun Başlık ', 20);
        $result = mynak_default_meta_description_for_page($longTitle, 'uzun-baslik');
        $this->assertLessThanOrEqual(161, mb_strlen($result));
    }
}
