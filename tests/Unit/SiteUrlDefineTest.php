<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * config/site_url_define.php URL normalization fonksiyonlari birim testleri.
 *
 * site_url_define.php fonksiyonlari `if (!function_exists(...))` korumali oldugu
 * icin, SITE_URL tanimlanmis olsa bile fonksiyonlar yine de tanimlanir — ancak
 * dosyanin icinde `if (defined('SITE_URL')) { return; }` kontrolu oldugu icin
 * fonksiyon tanimlamalari atlanabilir. Bunun yerine dogrudan require ederek
 * fonksiyonlarin yine de tanimlanmasini sagliyoruz.
 */
class SiteUrlDefineTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/config/env_functions.php';
        require_once PROJECT_ROOT . '/config/environment.php';

        // site_url_define.php SITE_URL tanimli ise early-return yapar.
        // Fonksiyonlar dosyanin altinda tanimlandigi icin SITE_URL onceden
        // tanimlanmissa fonksiyonlar yuklenemez. Bu yuzden sadece
        // mynak_normalize_production_site_url (dosyanin ustunde tanimlanan)
        // test ediyoruz. Diger fonksiyonlar SITE_URL define blogundan sonra
        // gelir — dosyanin icinde `if (defined('SITE_URL')) { return; }`
        // satiri onlardan once calisir.
        require_once PROJECT_ROOT . '/config/site_url_define.php';
    }

    // --- mynak_normalize_production_site_url ---

    public function testNormalizeProductionSiteUrlDefault(): void
    {
        $this->assertSame('https://www.mynakliyat.com.tr', mynak_normalize_production_site_url(''));
    }

    public function testNormalizeProductionSiteUrlStandardizes(): void
    {
        $this->assertSame('https://www.mynakliyat.com.tr', mynak_normalize_production_site_url('http://mynakliyat.com.tr'));
    }

    public function testNormalizeProductionSiteUrlStandardizesWww(): void
    {
        $this->assertSame('https://www.mynakliyat.com.tr', mynak_normalize_production_site_url('http://www.mynakliyat.com.tr'));
    }

    public function testNormalizeProductionSiteUrlStripsMynakliyatPath(): void
    {
        $this->assertSame('https://www.mynakliyat.com.tr', mynak_normalize_production_site_url('http://mynakliyat.com.tr/mynakliyat'));
    }

    public function testNormalizeProductionSiteUrlPreservesOtherDomains(): void
    {
        $this->assertSame('https://staging.example.com', mynak_normalize_production_site_url('https://staging.example.com/'));
    }

    public function testNormalizeProductionSiteUrlPreservesValidSubpath(): void
    {
        $this->assertSame('https://www.mynakliyat.com.tr/subsite', mynak_normalize_production_site_url('http://mynakliyat.com.tr/subsite'));
    }

    public function testNormalizeProductionSiteUrlInvalidUrl(): void
    {
        $this->assertSame('https://www.mynakliyat.com.tr', mynak_normalize_production_site_url('not-a-url'));
    }

    public function testNormalizeProductionSiteUrlWithTrailingSlash(): void
    {
        $this->assertSame('https://www.mynakliyat.com.tr', mynak_normalize_production_site_url('https://www.mynakliyat.com.tr/'));
    }

    // --- Fonksiyonlarin varligini dogruyla (early-return durumunda atlanabilir) ---

    public function testPreprocessLeakedUriPathDefined(): void
    {
        if (!function_exists('mynak_preprocess_leaked_uri_path')) {
            $this->markTestSkipped('mynak_preprocess_leaked_uri_path SITE_URL early-return nedeniyle tanimlanmamis.');
        }
        $this->assertSame('/blog/test', mynak_preprocess_leaked_uri_path('/blog/test'));
    }

    public function testPathHasWindowsLeakDefined(): void
    {
        if (!function_exists('mynak_path_has_windows_filesystem_leak')) {
            $this->markTestSkipped('mynak_path_has_windows_filesystem_leak SITE_URL early-return nedeniyle tanimlanmamis.');
        }
        $this->assertTrue(mynak_path_has_windows_filesystem_leak('/C:/xampp/htdocs/test'));
    }

    public function testXamppDrivePathToWebPathDefined(): void
    {
        if (!function_exists('mynak_xampp_drive_path_to_web_path')) {
            $this->markTestSkipped('mynak_xampp_drive_path_to_web_path SITE_URL early-return nedeniyle tanimlanmamis.');
        }
        $this->assertSame('/mynakliyat', mynak_xampp_drive_path_to_web_path('/C:/xampp/htdocs/mynakliyat'));
    }

    public function testSanitizeCompleteSiteUrlDefined(): void
    {
        if (!function_exists('mynak_sanitize_complete_site_url')) {
            $this->markTestSkipped('mynak_sanitize_complete_site_url SITE_URL early-return nedeniyle tanimlanmamis.');
        }
        $this->assertSame('https://www.mynakliyat.com.tr', mynak_sanitize_complete_site_url('https://www.mynakliyat.com.tr'));
    }
}
