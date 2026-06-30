<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BuildPatientAddressTest extends TestCase
{
    public function testBuildsFormattedAddress(): void
    {
        $result = buildPatientAddressFromRequest([
            'cep' => '28970-000',
            'address_street' => 'Rua dos Franciscanos',
            'address_number' => '36',
            'address_complement' => 'Casa',
            'address_neighborhood' => 'Dom Avelar',
            'address_city' => 'Rio das Ostras',
            'address_state' => 'rj',
        ]);

        $this->assertSame('28970000', $result['cep']);
        $this->assertStringContainsString('Rua dos Franciscanos', (string) $result['address']);
        $this->assertStringContainsString('Rio das Ostras/RJ', (string) $result['address']);
    }
}
