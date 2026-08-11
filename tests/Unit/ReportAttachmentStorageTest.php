<?php

namespace Tests\Unit;

use App\Services\ReportAttachmentStorage;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class ReportAttachmentStorageTest extends TestCase
{
    public function test_it_preserves_an_image_when_gd_is_not_available(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $file = UploadedFile::fake()->createWithContent('tomografia.png', $png);

        $storage = new class extends ReportAttachmentStorage
        {
            /** @return array{string, string, string, bool} */
            public function prepare(UploadedFile $file, string $mime): array
            {
                return $this->optimizeImage($file, $mime);
            }

            protected function canOptimizeImages(): bool
            {
                return false;
            }
        };

        [$contents, $extension, $mime, $compressed] = $storage->prepare($file, 'image/png');

        $this->assertSame($png, $contents);
        $this->assertSame('png', $extension);
        $this->assertSame('image/png', $mime);
        $this->assertFalse($compressed);
    }
}
