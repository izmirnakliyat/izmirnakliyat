<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * config/environment.php birim testleri.
 */
class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        require_once PROJECT_ROOT . '/config/env_functions.php';
        require_once PROJECT_ROOT . '/config/environment.php';
    }

    // --- mynak_http_host_is_local ---

    public function testIsLocalReturnsTrueForLocalhost(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertTrue(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsTrueForLocalhostWithPort(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $this->assertTrue(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsTrueFor127001(): void
    {
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $this->assertTrue(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsTrueForDotLocal(): void
    {
        $_SERVER['HTTP_HOST'] = 'myapp.local';
        $this->assertTrue(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsTrueForDotTest(): void
    {
        $_SERVER['HTTP_HOST'] = 'myapp.test';
        $this->assertTrue(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsTrueForPrivateIp(): void
    {
        $_SERVER['HTTP_HOST'] = '192.168.1.100';
        $this->assertTrue(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsFalseForProductionDomain(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.mynakliyat.com.tr';
        $this->assertFalse(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsFalseForEmptyHost(): void
    {
        $_SERVER['HTTP_HOST'] = '';
        $this->assertFalse(mynak_http_host_is_local());
    }

    public function testIsLocalReturnsFalseForPublicIp(): void
    {
        $_SERVER['HTTP_HOST'] = '8.8.8.8';
        $this->assertFalse(mynak_http_host_is_local());
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST']);
    }
}
