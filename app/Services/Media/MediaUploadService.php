<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Media;
use App\Support\Str;
use RuntimeException;

/**
 * Validated upload handling: extension allowlist + real MIME sniffing
 * (fileinfo, not the client-supplied Content-Type) + size cap + the file
 * is always renamed on store (prompt.md section 11).
 */
final class MediaUploadService
{
    private ImageProcessor $imageProcessor;

    public function __construct(private array $securityConfig)
    {
        $this->imageProcessor = new ImageProcessor();
    }

    /**
     * @param array<string,mixed> $file One entry from $_FILES (as returned by Request::file())
     * @throws RuntimeException on validation failure
     */
    public function store(array $file, string $type, ?int $uploadedBy = null): Media
    {
        $this->assertUploadOk($file);
        $this->assertSize((int) $file['size'], $type);

        $extension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $this->assertExtensionAllowed($extension, $type);

        $mimeType = $this->detectRealMimeType((string) $file['tmp_name']);
        $this->assertMimeAllowed($mimeType, $type);

        $subdir = date('Y/m');
        $targetDir = public_path('uploads/' . $subdir);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $filename = Str::random(32) . '.' . $extension;
        $destination = $targetDir . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            // move_uploaded_file() fails outside a real HTTP upload (e.g. tests) - fall back to copy.
            if (!@copy((string) $file['tmp_name'], $destination)) {
                throw new RuntimeException("Impossible d'enregistrer le fichier telecharge.");
            }
        }

        [$width, $height] = $this->dimensions($destination, $mimeType);
        $sizeBytes = (int) $file['size'];

        $optimized = str_starts_with($mimeType, 'image/')
            ? $this->imageProcessor->optimize($destination, $mimeType)
            : null;
        if ($optimized !== null) {
            $filename = basename($optimized['path']);
            $mimeType = $optimized['mime_type'];
            $width = $optimized['width'];
            $height = $optimized['height'];
            $sizeBytes = (int) (filesize($optimized['path']) ?: $sizeBytes);
        }

        return Media::create([
            'disk_path' => 'uploads/' . $subdir . '/' . $filename,
            'url' => '/uploads/' . $subdir . '/' . $filename,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'width' => $width,
            'height' => $height,
            'type' => $type,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    private function assertUploadOk(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Echec du televersement (code {$error}).");
        }
    }

    private function assertSize(int $sizeBytes, string $type): void
    {
        $key = $type === 'realization_video' ? 'video_max_size_bytes' : 'max_size_bytes';
        $max = (int) ($this->securityConfig['uploads'][$key] ?? 8 * 1024 * 1024);
        if ($sizeBytes > $max) {
            throw new RuntimeException('Le fichier depasse la taille maximale autorisee.');
        }
    }

    private function assertExtensionAllowed(string $extension, string $type): void
    {
        $key = $type === 'realization_video' ? 'allowed_video_extensions' : 'allowed_extensions';
        $allowed = (array) ($this->securityConfig['uploads'][$key] ?? []);
        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException("Extension de fichier non autorisee : .{$extension}");
        }
    }

    private function assertMimeAllowed(string $mimeType, string $type): void
    {
        $key = $type === 'realization_video' ? 'allowed_video_mime_types' : 'allowed_mime_types';
        $allowed = (array) ($this->securityConfig['uploads'][$key] ?? []);
        if (!in_array($mimeType, $allowed, true)) {
            throw new RuntimeException("Type de fichier non autorise : {$mimeType}");
        }
    }

    private function detectRealMimeType(string $tmpPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? finfo_file($finfo, $tmpPath) : false;
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        return $mime !== false ? $mime : 'application/octet-stream';
    }

    /** @return array{0:?int,1:?int} */
    private function dimensions(string $path, string $mimeType): array
    {
        if (!str_starts_with($mimeType, 'image/') || $mimeType === 'image/svg+xml') {
            return [null, null];
        }

        $info = @getimagesize($path);

        return $info === false ? [null, null] : [$info[0], $info[1]];
    }
}
