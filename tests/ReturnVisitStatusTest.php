<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ReturnVisitStatusTest extends TestCase
{
    public function testStatusLabel(): void
    {
        $this->assertSame('Pendente', returnVisitStatusLabel('pending'));
        $this->assertSame('Vencido', returnVisitStatusLabel('overdue'));
        $this->assertSame('Agendado', returnVisitStatusLabel('scheduled'));
        $this->assertSame('Concluído', returnVisitStatusLabel('completed'));
        $this->assertSame('Cancelado', returnVisitStatusLabel('cancelled'));
    }

    public function testStatusBadgeClass(): void
    {
        $this->assertStringContainsString('status-overdue', returnVisitStatusBadgeClass('overdue'));
        $this->assertStringContainsString('status-done', returnVisitStatusBadgeClass('completed'));
        $this->assertStringContainsString('status-waiting', returnVisitStatusBadgeClass('pending'));
    }
}
