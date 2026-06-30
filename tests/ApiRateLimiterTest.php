<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ApiRateLimiterTest extends TestCase
{
    public function testAllowsRequestsUnderLimit(): void
    {
        $hash = 'test_' . bin2hex(random_bytes(4));
        $this->assertTrue(\App\Core\ApiRateLimiter::allow($hash));
    }
}
