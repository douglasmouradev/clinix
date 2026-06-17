<?php

declare(strict_types=1);

use App\Core\DocumentStorage;
use PHPUnit\Framework\TestCase;

final class DocumentStoragePathTest extends TestCase
{
    public function testRejectsPathTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DocumentStorage::absolutePath('uploads/../../.env');
    }

    public function testResolvesUploadPath(): void
    {
        $path = DocumentStorage::absolutePath('uploads/demo/file.pdf');
        $this->assertStringEndsWith('public/uploads/demo/file.pdf', str_replace('\\', '/', $path));
    }
}
