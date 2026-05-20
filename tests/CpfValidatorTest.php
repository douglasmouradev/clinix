<?php

declare(strict_types=1);

use App\Core\CpfValidator;
use PHPUnit\Framework\TestCase;

final class CpfValidatorTest extends TestCase
{
    public function testValidCpf(): void
    {
        $this->assertTrue(CpfValidator::isValid('52998224725'));
    }

    public function testInvalidCpf(): void
    {
        $this->assertFalse(CpfValidator::isValid('11111111111'));
        $this->assertFalse(CpfValidator::isValid('123'));
    }

    public function testFormat(): void
    {
        $this->assertSame('529.982.247-25', CpfValidator::format('52998224725'));
    }
}
