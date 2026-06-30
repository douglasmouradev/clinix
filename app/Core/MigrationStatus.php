<?php

declare(strict_types=1);

namespace App\Core;

final class MigrationStatus
{
    /** @return list<string> */
    public static function pending(): array
    {
        try {
            $pdo = Database::connection();
            $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(190) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )');

            $executed = $pdo->query('SELECT filename FROM migrations')->fetchAll(\PDO::FETCH_COLUMN);
            $executedMap = array_fill_keys($executed ?: [], true);

            $pending = [];
            $files = glob(dirname(__DIR__, 2) . '/database/migrations/*.sql') ?: [];
            sort($files);
            foreach ($files as $filePath) {
                $filename = basename($filePath);
                if (!isset($executedMap[$filename])) {
                    $pending[] = $filename;
                }
            }

            return $pending;
        } catch (\Throwable) {
            return [];
        }
    }
}
