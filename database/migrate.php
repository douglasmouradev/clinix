<?php

declare(strict_types=1);

require __DIR__ . '/../app/Config/config.php';

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(190) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)');

$executed = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$executedMap = array_fill_keys($executed, true);

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

foreach ($files as $filePath) {
    $filename = basename($filePath);
    if (isset($executedMap[$filename])) {
        continue;
    }

    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException('Não foi possivel ler migration: ' . $filename);
    }

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (:filename)');
        $stmt->execute(['filename' => $filename]);
        echo '[OK] ' . $filename . PHP_EOL;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo '[ERRO] ' . $filename . ': ' . $exception->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo 'Migrations finalizadas.' . PHP_EOL;

