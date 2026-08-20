<?php

declare(strict_types=1);

namespace App\Services\Media;

/**
 * Resizes uploads to a sane max width and re-encodes JPEG/PNG as WebP
 * (prompt.md section 12: "images automatiquement redimensionnees",
 * "generation WebP ou AVIF"). SVG/GIF pass through untouched - GD's basic
 * GIF handling would drop animation, and SVG is already vector.
 */
final class ImageProcessor
{
    private const MAX_WIDTH = 1920;

    /**
     * @return array{path:string,mime_type:string,width:int,height:int}|null
     *         null means "left as-is, use the original file info"
     */
    public function optimize(string $sourcePath, string $mimeType): ?array
    {
        if (!in_array($mimeType, ['image/jpeg', 'image/png'], true) || !function_exists('imagewebp')) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $image = $mimeType === 'image/jpeg' ? @imagecreatefromjpeg($sourcePath) : @imagecreatefrompng($sourcePath);
        if ($image === false) {
            return null;
        }

        if ($width > self::MAX_WIDTH) {
            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) round($height * ($newWidth / $width));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }

        $webpPath = preg_replace('/\.[^.]+$/', '', $sourcePath) . '.webp';
        $ok = imagewebp($image, $webpPath, 82);
        imagedestroy($image);

        if (!$ok || !is_file($webpPath)) {
            return null;
        }

        if ($webpPath !== $sourcePath) {
            @unlink($sourcePath);
        }

        return ['path' => $webpPath, 'mime_type' => 'image/webp', 'width' => $width, 'height' => $height];
    }
}
