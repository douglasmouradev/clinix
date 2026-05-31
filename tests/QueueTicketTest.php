<?php

declare(strict_types=1);

use App\Models\Queue;
use PHPUnit\Framework\TestCase;

final class QueueTicketTest extends TestCase
{
    public function testTicketPrefixForRoom(): void
    {
        $this->assertSame('P', Queue::ticketPrefixForRoom('Prioritário'));
        $this->assertSame('A', Queue::ticketPrefixForRoom('Agendado'));
        $this->assertSame('B', Queue::ticketPrefixForRoom('Sem agendamento'));
        $this->assertNull(Queue::ticketPrefixForRoom('Triagem'));
        $this->assertNull(Queue::ticketPrefixForRoom(null));
    }

    public function testQueuePriorityForRoom(): void
    {
        $this->assertSame(0, Queue::queuePriorityForRoom('Prioritário'));
        $this->assertSame(1, Queue::queuePriorityForRoom('Agendado'));
        $this->assertSame(2, Queue::queuePriorityForRoom('Sem agendamento'));
        $this->assertSame(3, Queue::queuePriorityForRoom('Triagem'));
    }

    public function testFormatTicketNumber(): void
    {
        $this->assertSame('P001', Queue::formatTicketNumber(1, 'P'));
        $this->assertSame('A012', Queue::formatTicketNumber(12, 'A'));
        $this->assertSame('B100', Queue::formatTicketNumber(100, 'B'));
        $this->assertSame('007', Queue::formatTicketNumber(7, null));
    }

    public function testQueueSuggestedCallRoom(): void
    {
        $this->assertSame('Recepção', queueSuggestedCallRoom('Agendado', 'Recepção'));
        $this->assertSame('Triagem 2', queueSuggestedCallRoom('Triagem 2', 'Recepção'));
        $this->assertSame('Consultorio 1', queueSuggestedCallRoom('', 'Consultorio 1'));
    }
}
