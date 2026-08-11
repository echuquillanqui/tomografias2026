<?php

namespace App\Services;

use App\Models\OrderReport;
use App\Models\OrderReportAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ReportAttachmentStorage
{
    private const MAX_IMAGE_DIMENSION = 1920;
    private const WEBP_QUALITY = 75;

    public function store(OrderReport $report, UploadedFile $file): OrderReportAttachment
    {
        $mime = (string) $file->getMimeType();
        $originalSize = (int) $file->getSize();
        $directory = 'reportes/'.$report->id;

        if (str_starts_with($mime, 'image/')) {
            [$contents, $extension, $storedMime, $compressed] = $this->optimizeImage($file, $mime);
        } else {
            $raw = file_get_contents($file->getRealPath());
            if ($raw === false) {
                throw new RuntimeException('No se pudo leer el PDF adjunto.');
            }
            $gzipped = gzencode($raw, 9);
            $useGzip = $gzipped !== false && strlen($gzipped) < strlen($raw);
            $contents = $useGzip ? $gzipped : $raw;
            $extension = $useGzip ? 'pdf.gz' : 'pdf';
            $storedMime = 'application/pdf';
            $compressed = $useGzip;
        }

        $storedName = $directory.'/'.Str::uuid().'.'.$extension;
        Storage::disk('local')->put($storedName, $contents);

        try {
            return $report->attachments()->create([
                'original_name' => basename($file->getClientOriginalName()),
                'stored_name' => $storedName,
                'mime_type' => $storedMime,
                'original_size' => $originalSize,
                'stored_size' => strlen($contents),
                'compressed' => $compressed,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedName);
            throw $exception;
        }
    }

    public function replace(OrderReportAttachment $attachment, UploadedFile $file): OrderReportAttachment
    {
        $replacement = $this->store($attachment->report, $file);
        $oldStoredName = $attachment->stored_name;

        try {
            $attachment->update($replacement->only([
                'original_name', 'stored_name', 'mime_type', 'original_size',
                'stored_size', 'compressed',
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($replacement->stored_name);
            $replacement->delete();
            throw $exception;
        }

        $replacement->delete();
        Storage::disk('local')->delete($oldStoredName);

        return $attachment->refresh();
    }

    /** @return array{string, string, string, bool} */
    protected function optimizeImage(UploadedFile $file, string $mime): array
    {
        $sourceData = file_get_contents($file->getRealPath());

        if ($sourceData === false) {
            throw new RuntimeException('La imagen adjunta no pudo ser procesada.');
        }

        // GD is not enabled in every PHP installation (notably some XAMPP
        // setups). Keep uploads working there and optimize when it is present.
        if (! $this->canOptimizeImages()) {
            return [$sourceData, $this->extensionForMime($mime), $mime, false];
        }

        $source = imagecreatefromstring($sourceData);

        if ($source === false) {
            throw new RuntimeException('La imagen adjunta no pudo ser procesada.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_IMAGE_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($target, null, self::WEBP_QUALITY);
            $extension = 'webp';
            $mime = 'image/webp';
        } else {
            imagejpeg($target, null, self::WEBP_QUALITY);
            $extension = 'jpg';
            $mime = 'image/jpeg';
        }
        $contents = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        if ($contents === '') {
            throw new RuntimeException('La imagen adjunta no pudo ser optimizada.');
        }

        return [$contents, $extension, $mime, true];
    }

    protected function canOptimizeImages(): bool
    {
        return function_exists('imagecreatefromstring')
            && (function_exists('imagewebp') || function_exists('imagejpeg'));
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'img',
        };
    }
}
