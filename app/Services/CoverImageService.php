<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CoverImageService
{
    /** Output size: A4 at 150 dpi, so a cover fills a printed page without upscaling. */
    public const WIDTH = 1240;

    public const HEIGHT = 1754;

    private const FOLDER = 'covers';

    private const QUALITY = 85;

    /** Width over height — the ratio the crop frame in the browser must match. */
    public static function aspectRatio(): float
    {
        return self::WIDTH / self::HEIGHT;
    }

    /**
     * Crop an uploaded image to the given rectangle, re-encode it at cover size
     * and store it, returning the stored path. Re-encoding is what strips EXIF
     * and any other metadata the original carried.
     *
     * @param  array{x: float, y: float, width: float, height: float}  $crop  fractions of the source image
     */
    public function store(UploadedFile $file, array $crop): string
    {
        $source = $this->decode($file);

        try {
            $binary = $this->render($source, $crop);
        } finally {
            imagedestroy($source);
        }

        $path = self::FOLDER.'/cover_'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * Decode the upload into a GD image, upright and ready to crop.
     */
    private function decode(UploadedFile $file): GdImage
    {
        $path = $file->getRealPath();
        $type = @getimagesize($path)[2] ?? null;

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $image) {
            throw ValidationException::withMessages([
                'cover' => [__('messages.cover_unreadable')],
            ]);
        }

        return $type === IMAGETYPE_JPEG ? $this->orient($image, $path) : $image;
    }

    /**
     * Browsers render JPEGs with EXIF orientation already applied, so the crop
     * arrives in upright coordinates — GD has to be rotated to match.
     */
    private function orient(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $rotated = match (@exif_read_data($path)['Orientation'] ?? 1) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if (! $rotated) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /**
     * @param  array{x: float, y: float, width: float, height: float}  $crop
     */
    private function render(GdImage $source, array $crop): string
    {
        [$x, $y, $width, $height] = $this->rectangle($source, $crop);

        $target = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        // Flatten any transparency onto white; JPEG has no alpha channel.
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, $x, $y, self::WIDTH, self::HEIGHT, $width, $height);

        ob_start();
        imagejpeg($target, null, self::QUALITY);
        $binary = ob_get_clean();

        imagedestroy($target);

        return $binary;
    }

    /**
     * Translate the fractional crop into pixels, kept inside the source bounds
     * so a malformed or rounded-up rectangle can never overrun the image.
     *
     * @param  array{x: float, y: float, width: float, height: float}  $crop
     * @return array{int, int, int, int}
     */
    private function rectangle(GdImage $source, array $crop): array
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $x = min((int) round($crop['x'] * $sourceWidth), $sourceWidth - 1);
        $y = min((int) round($crop['y'] * $sourceHeight), $sourceHeight - 1);

        return [
            $x,
            $y,
            max(1, min((int) round($crop['width'] * $sourceWidth), $sourceWidth - $x)),
            max(1, min((int) round($crop['height'] * $sourceHeight), $sourceHeight - $y)),
        ];
    }
}
