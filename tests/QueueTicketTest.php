<?php

declare(strict_types=1);

use App\Models\Queue;
use PHPUnit\Framework\TestCase;

final class QueueTicketTest extends TestCase
{
    public function testTicketPrefixForRoom(): void
    {
        $this->assertSame('A', Queue::ticketPrefixForRoom('Agendado'));
        $this->assertSame('B', Queue::ticketPrefixForRoom('Sem agendamento'));
        $this->assertNull(Queue::ticketPrefixForRoom('Triagem'));
        $this->assertNull(Queue::ticketPrefixForRoom(null));
    }

    public function testQueuePriorityForRoom(): void
    {
        $this->assertSame(0, Queue::queuePriorityForRoom('Agendado'));
        $this->assertSame(1, Queue::queuePriorityForRoom('Sem agendamento'));
        $this->assertSame(2, Queue::queuePriorityForRoom('Triagem'));
    }
}
