<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * config/http_output_handler.php birim testleri.
 */
class HttpOutputHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/config/http_output_handler.php';
    }

    public function testBufferReturnedUnchangedWhenNoLeak(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.mynakliyat.com.tr';
        $buffer = '<html><body>Normal icerik</body></html>';
        $result = mynak_http_output_buffer_handler($buffer, PHP_OUTPUT_HANDLER_FINAL);
        $this->assertSame($buffer, $result);
    }

    public function testBufferReturnedUnchangedWhenEmpty(): void
    {
        $result = mynak_http_output_buffer_handler('', PHP_OUTPUT_HANDLER_FINAL);
        $this->assertSame('', $result);
    }

    public function testBufferReturnedUnchangedWhenNoHostSet(): void
    {
        $_SERVER['HTTP_HOST'] = '';
        $buffer = '<a href="http://localhost/C:/xampp/htdocs/mynakliyat/blog">test</a>';
        $result = mynak_http_output_buffer_handler($buffer, PHP_OUTPUT_HANDLER_FINAL);
        $this->assertSame($buffer, $result);
    }

    public function testBufferReturnedForNonFinalPhase(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.mynakliyat.com.tr';
        $buffer = '<html>xampp sizmasi</html>';
        $result = mynak_http_output_buffer_handler($buffer, 0);
        $this->assertSame($buffer, $result);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST']);
    }
}
