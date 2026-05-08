#!/usr/bin/env bash
set -euo pipefail

echo "==> PHP lint"
php -r '
$dirs = ["app", "public", "database"];
foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), ".php")) {
            $path = $file->getPathname();
            passthru("php -l " . escapeshellarg($path), $code);
            if ($code !== 0) {
                exit($code);
            }
        }
    }
}
'

echo "==> DB migrations"
php database/migrate.php >/dev/null

echo "Quality check concluido."

