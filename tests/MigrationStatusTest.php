<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MigrationStatusTest extends TestCase
{
    public function testPendingReturnsArray(): void
    {
        $pending = \App\Core\MigrationStatus::pending();
        $this->assertIsArray($pending);
    }
}
