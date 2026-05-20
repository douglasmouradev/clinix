<?php

declare(strict_types=1);

use App\Core\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function testRejectsWeakPassword(): void
    {
        $this->assertNotNull(PasswordPolicy::validate('123456'));
        $this->assertNotNull(PasswordPolicy::validate('short'));
    }

    public function testAcceptsStrongPassword(): void
    {
        $this->assertNull(PasswordPolicy::validate('ChangeMe2026!'));
    }
}
