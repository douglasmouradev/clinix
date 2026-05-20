<?php

declare(strict_types=1);

namespace App\Core;

final class DocumentStorage
{
    private const ALLOWED_MIME = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const MAX_BYTES = 5 * 1024 * 1024;

    public static function storeUploaded(array $file, string $scope): ?array
    {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? '');
        $fileSize = (int) ($file['size'] ?? 0);
        $mimeType = (string) mime_content_type($tmpName);

        if (!in_array($mimeType, self::ALLOWED_MIME, true)) {
            return null;
        }

        if ($fileSize > self::MAX_BYTES) {
            return null;
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeName = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        $relativeDir = $scope . '/' . tenantId();
        $absoluteDir = self::basePath() . '/' . $relativeDir;

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            return null;
        }

        $targetPath = $absoluteDir . '/' . $safeName;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            return null;
        }

        return [
            'original_name' => $originalName,
            'stored_name' => $safeName,
            'file_path' => $relativeDir . '/' . $safeName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ];
    }

    public static function absolutePath(string $filePath): string
    {
        $normalized = ltrim(str_replace('\\', '/', $filePath), '/');
        if (str_starts_with($normalized, 'uploads/')) {
            return dirname(__DIR__, 2) . '/public/' . $normalized;
        }

        return self::basePath() . '/' . $normalized;
    }

    public static function delete(string $filePath): void
    {
        $absolute = self::absolutePath($filePath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    public static function basePath(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }
}
