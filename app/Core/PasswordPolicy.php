<?php

declare(strict_types=1);

namespace App\Core;

final class PasswordPolicy
{
    private const MIN_LENGTH = 10;

    /** @var list<string> */
    private const BLOCKED = [
        '123456', '12345678', '123456789', '1234567890', 'password', 'senha',
        'admin123', 'qwerty', 'clinix', 'clinix123',
    ];

    public static function validate(string $password): ?string
    {
        if (strlen($password) < self::MIN_LENGTH) {
            return 'A senha deve ter no mínimo ' . self::MIN_LENGTH . ' caracteres.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Inclua ao menos uma letra maiúscula.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'Inclua ao menos uma letra minúscula.';
        }

        if (!preg_match('/\d/', $password)) {
            return 'Inclua ao menos um número.';
        }

        $lower = strtolower($password);
        foreach (self::BLOCKED as $blocked) {
            if ($lower === $blocked || str_contains($lower, $blocked)) {
                return 'Senha muito comum ou fraca. Escolha outra.';
            }
        }

        return null;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
