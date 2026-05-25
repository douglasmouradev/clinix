<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QueueStatusLabelTest extends TestCase
{
    public function testQueueStatusLabels(): void
    {
        $this->assertSame('Aguardando', queueStatusLabel('waiting'));
        $this->assertSame('Chamado', queueStatusLabel('called'));
        $this->assertSame('Finalizado', queueStatusLabel('done'));
        $this->assertSame('outro', queueStatusLabel('outro'));
    }

    public function testPanelDisplayName(): void
    {
        $this->assertSame('Paciente', panelDisplayName('Maria Silva', true));
        $this->assertSame('Maria Silva', panelDisplayName('Maria Silva', false));
        $this->assertSame('', panelDisplayName('', true));
    }
}
