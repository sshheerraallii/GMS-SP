<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class DocumentNormalizer
{
    public const MAX_SIZE_KB = 2048;

    /**
     * @param UploadedFile $file   The uploaded file
     * @param string       $path   Path under storage/app (e.g. "public/guards/123")
     * @param string       $field  Input name for clean validation errors (e.g. "evisa_ss")
     */
    public static function process(UploadedFile $file, string $path, string $field = 'document'): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return self::processImage($file, $path, $field);
        }

        return self::storeRaw($file, $path, $field);
    }

    protected static function processImage(UploadedFile $file, string $path, string $field): string
    {
        self::ensureDirExists($path);

        $manager = new ImageManager(new Driver());

        try {
            $image = $manager->read($file->getPathname());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                $field => 'Invalid or corrupted image file.',
            ]);
        }

        // Always normalize images to JPG for predictable compression
        $filename = Str::uuid() . '.jpg';
        $fullPath = storage_path("app/{$path}/{$filename}");

        // Resize safely
        $image->scaleDown(width: 1600);

        $quality = 85;

        do {
            $image->toJpeg($quality)->save($fullPath);
            $quality -= 5;
        } while (filesize($fullPath) > self::maxBytes() && $quality >= 50);

        if (filesize($fullPath) > self::maxBytes()) {
            @unlink($fullPath);

            throw ValidationException::withMessages([
                $field => 'Image could not be reduced below 2MB.',
            ]);
        }

        return "{$path}/{$filename}";
    }

    protected static function storeRaw(UploadedFile $file, string $path, string $field): string
    {
        self::ensureDirExists($path);

        // Keep same extension for non-images
        $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());

        // If you pass "public/..." then disk must be default/local. But your app uses public disk.
        // We'll store via Storage to be consistent:
        // Convert "public/guards/123" => disk("public") path "guards/123"
        if (str_starts_with($path, 'public/')) {
            $diskPath = substr($path, 7); // remove "public/"
            $stored   = Storage::disk('public')->putFileAs($diskPath, $file, $filename);
            $fullPath = Storage::disk('public')->path($stored);
            $return   = 'public/' . $stored;
        } else {
            // fallback for other paths
            $stored   = $file->storeAs($path, $filename);
            $fullPath = storage_path('app/' . $stored);
            $return   = $stored;
        }

        if (filesize($fullPath) > self::maxBytes()) {
            @unlink($fullPath);

            throw ValidationException::withMessages([
                $field => 'Document exceeds 2MB and cannot be normalized.',
            ]);
        }

        return $return;
    }

    protected static function ensureDirExists(string $path): void
    {
        $dir = storage_path("app/{$path}");
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    protected static function maxBytes(): int
    {
        return self::MAX_SIZE_KB * 1024;
    }
}
