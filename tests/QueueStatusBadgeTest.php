<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QueueStatusBadgeTest extends TestCase
{
    public function testBadgeClasses(): void
    {
        require_once dirname(__DIR__) . '/app/Helpers.php';

        $this->assertSame('status-badge status-waiting', queueStatusBadgeClass('waiting'));
        $this->assertSame('status-badge status-called', queueStatusBadgeClass('called'));
        $this->assertSame('status-badge status-done', queueStatusBadgeClass('done'));
    }
}
