<?php

namespace Modules\Absensi\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadCompressionService
{
    /**
     * Process, resize, and compress uploaded file strictly under 500 KB
     * 
     * @param UploadedFile $file
     * @param string $folder e.g. 'absensi_bukti' or 'jurnal_kegiatan'
     * @param int $maxSizeKb Max target size in KB (default 500)
     * @return array
     */
    public static function processAndCompress(UploadedFile $file, string $folder = 'absensi_bukti', int $maxSizeKb = 500): array
    {
        $mime = strtolower($file->getMimeType() ?? '');
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();

        // 1. Handle PDF Documents
        if ($extension === 'pdf' || str_contains($mime, 'pdf')) {
            $filename = (string) Str::uuid() . '.pdf';
            $path = $file->storeAs("public/{$folder}", $filename);
            $sizeKb = round($file->getSize() / 1024, 2);

            return [
                'url'           => Storage::url($path),
                'path'          => $path,
                'filename'      => $filename,
                'original_name' => $originalName,
                'size_kb'       => $sizeKb,
                'mime_type'     => 'application/pdf',
            ];
        }

        // 2. Handle Image Resizing & Iterative Compression (< 500 KB) via GD
        $supportedImages = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (in_array($mime, $supportedImages) || in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $imageResource = null;

            switch ($extension) {
                case 'png':
                    $imageResource = @imagecreatefrompng($file->getRealPath());
                    break;
                case 'webp':
                    $imageResource = @imagecreatefromwebp($file->getRealPath());
                    break;
                default:
                    $imageResource = @imagecreatefromjpeg($file->getRealPath());
                    break;
            }

            if ($imageResource) {
                // Correct EXIF orientation if JPEG
                if (function_exists('exif_read_data') && in_array($extension, ['jpg', 'jpeg'])) {
                    try {
                        $exif = @exif_read_data($file->getRealPath());
                        if (!empty($exif['Orientation'])) {
                            switch ($exif['Orientation']) {
                                case 3:
                                    $imageResource = imagerotate($imageResource, 180, 0);
                                    break;
                                case 6:
                                    $imageResource = imagerotate($imageResource, -90, 0);
                                    break;
                                case 8:
                                    $imageResource = imagerotate($imageResource, 90, 0);
                                    break;
                            }
                        }
                    } catch (\Throwable $e) {
                        // ignore exif error
                    }
                }

                $origWidth = imagesx($imageResource);
                $origHeight = imagesy($imageResource);

                // Max dimensions 1600px width/height
                $maxDim = 1600;
                $newWidth = $origWidth;
                $newHeight = $origHeight;

                if ($origWidth > $maxDim || $origHeight > $maxDim) {
                    $ratio = min($maxDim / $origWidth, $maxDim / $origHeight);
                    $newWidth = (int) round($origWidth * $ratio);
                    $newHeight = (int) round($origHeight * $ratio);
                }

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Handle transparency for PNG / WebP
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);

                imagecopyresampled($resizedImage, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

                // Prepare Storage Directory
                $storageDir = storage_path("app/public/{$folder}");
                if (!is_dir($storageDir)) {
                    mkdir($storageDir, 0755, true);
                }

                $filename = (string) Str::uuid() . '.jpg';
                $destPath = "{$storageDir}/{$filename}";

                // Iterative Compression to guarantee < 500 KB (maxSizeKb)
                $quality = 82;
                do {
                    imagejpeg($resizedImage, $destPath, $quality);
                    clearstatcache(true, $destPath);
                    $currentSizeKb = filesize($destPath) / 1024;
                    $quality -= 8;
                } while ($currentSizeKb > $maxSizeKb && $quality >= 40);

                imagedestroy($imageResource);
                imagedestroy($resizedImage);

                $finalSizeKb = round(filesize($destPath) / 1024, 2);

                return [
                    'url'           => Storage::url("public/{$folder}/{$filename}"),
                    'path'          => "public/{$folder}/{$filename}",
                    'filename'      => $filename,
                    'original_name' => $originalName,
                    'size_kb'       => $finalSizeKb,
                    'mime_type'     => 'image/jpeg',
                ];
            }
        }

        // Fallback store directly
        $filename = (string) Str::uuid() . '.' . $extension;
        $path = $file->storeAs("public/{$folder}", $filename);

        return [
            'url'           => Storage::url($path),
            'path'          => $path,
            'filename'      => $filename,
            'original_name' => $originalName,
            'size_kb'       => round($file->getSize() / 1024, 2),
            'mime_type'     => $mime,
        ];
    }
}
