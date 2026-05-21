<?php

declare(strict_types=1);

/**
 * Após importar database/schema.sql, marca migrations legadas como aplicadas
 * (o schema já contém tenant_id e estrutura base).
 * Deixa pendentes apenas migrations posteriores ao schema (ex.: 20260520+).
 */

require __DIR__ . '/../app/Config/config.php';

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(190) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)');

$legacyPatterns = [
    __DIR__ . '/migrations/20260505*.sql',
];

$stmt = $pdo->prepare('INSERT IGNORE INTO migrations (filename) VALUES (:filename)');
$count = 0;

foreach ($legacyPatterns as $pattern) {
    foreach (glob($pattern) ?: [] as $filePath) {
        $stmt->execute(['filename' => basename($filePath)]);
        $count += (int) $stmt->rowCount();
    }
}

echo "Migrations legadas registradas: {$count}" . PHP_EOL;
