<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * config/env_functions.php birim testleri.
 */
class EnvFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/config/env_functions.php';
    }

    // --- mynak_env_str ---

    public function testEnvStrReturnsValueFromPutenv(): void
    {
        putenv('MYNAK_TEST_KEY_1=hello');
        $this->assertSame('hello', mynak_env_str('MYNAK_TEST_KEY_1'));
        putenv('MYNAK_TEST_KEY_1');
    }

    public function testEnvStrReturnsValueFromEnvSuperglobal(): void
    {
        $_ENV['MYNAK_TEST_KEY_2'] = 'world';
        $this->assertSame('world', mynak_env_str('MYNAK_TEST_KEY_2'));
        unset($_ENV['MYNAK_TEST_KEY_2']);
    }

    public function testEnvStrReturnsEmptyForMissingKey(): void
    {
        $this->assertSame('', mynak_env_str('MYNAK_TOTALLY_NONEXISTENT_KEY_XYZ'));
    }

    // --- mynak_load_dotenv ---

    public function testLoadDotenvParsesKeyValuePairs(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($tmp, "FOO_TEST_A=bar\nBAZ_TEST_A=qux\n");
        mynak_load_dotenv($tmp);
        $this->assertSame('bar', mynak_env_str('FOO_TEST_A'));
        $this->assertSame('qux', mynak_env_str('BAZ_TEST_A'));
        unlink($tmp);
        putenv('FOO_TEST_A');
        putenv('BAZ_TEST_A');
    }

    public function testLoadDotenvStripsQuotes(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($tmp, "QUOTED_D=\"double\"\nQUOTED_S='single'\n");
        mynak_load_dotenv($tmp);
        $this->assertSame('double', mynak_env_str('QUOTED_D'));
        $this->assertSame('single', mynak_env_str('QUOTED_S'));
        unlink($tmp);
        putenv('QUOTED_D');
        putenv('QUOTED_S');
    }

    public function testLoadDotenvSkipsCommentsAndBlankLines(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($tmp, "# comment\n\nVALID_K=yes\n  # another comment\n");
        mynak_load_dotenv($tmp);
        $this->assertSame('yes', mynak_env_str('VALID_K'));
        unlink($tmp);
        putenv('VALID_K');
    }

    public function testLoadDotenvSkipsLinesWithoutEquals(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($tmp, "NOEQ\nHAS_EQ=ok\n");
        mynak_load_dotenv($tmp);
        $this->assertSame('ok', mynak_env_str('HAS_EQ'));
        unlink($tmp);
        putenv('HAS_EQ');
    }

    public function testLoadDotenvHandlesBOM(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($tmp, "\xEF\xBB\xBFBOM_KEY=bomval\n");
        mynak_load_dotenv($tmp);
        $this->assertSame('bomval', mynak_env_str('BOM_KEY'));
        unlink($tmp);
        putenv('BOM_KEY');
    }

    public function testLoadDotenvIgnoresUnreadablePath(): void
    {
        // Should not throw
        mynak_load_dotenv('/nonexistent/path/.env.fake');
        $this->assertTrue(true);
    }
}
