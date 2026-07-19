<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CacheAccessControlTest extends TestCase
{
    public function testCacheDirectoryDeniesDirectHttpAccess(): void
    {
        $rules = file_get_contents(PROJECT_ROOT . '/cache/.htaccess');

        $this->assertIsString($rules);
        $this->assertStringContainsString('Require all denied', $rules);
        $this->assertStringContainsString('Deny from all', $rules);
    }
}
