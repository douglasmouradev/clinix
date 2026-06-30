<?php

declare(strict_types=1);

use App\Core\PortalRateLimiter;
use PHPUnit\Framework\TestCase;

final class PortalRateLimiterTest extends TestCase
{
    protected function tearDown(): void
    {
        $dir = dirname(__DIR__) . '/storage/cache/portal-rate';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    public function testLocksAfterMaxAttempts(): void
    {
        $slug = 'clinica-teste';
        $cpf = '12345678901';

        PortalRateLimiter::clear($slug, $cpf);
        $this->assertFalse(PortalRateLimiter::isLocked($slug, $cpf));

        for ($i = 0; $i < 5; $i++) {
            PortalRateLimiter::registerFailure($slug, $cpf);
        }

        $this->assertTrue(PortalRateLimiter::isLocked($slug, $cpf));
        PortalRateLimiter::clear($slug, $cpf);
        $this->assertFalse(PortalRateLimiter::isLocked($slug, $cpf));
    }
}
