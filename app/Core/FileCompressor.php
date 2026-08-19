<?php

namespace App\Core;

/**
 * FileCompressor — Kompresi file secara otomatis saat upload.
 * 
 * Mendukung:
 *   - Gambar (JPEG, PNG, WebP, GIF) → dikonversi ke WebP dengan resize
 *   - PDF → validasi magic bytes + optimizer ringan berbasis zlib
 */
class FileCompressor
{
    // =========================================================================
    //  IMAGE: Compress & Convert to WebP
    // =========================================================================

    /**
     * Kompresi gambar dan simpan sebagai WebP (Otomatis kompresi berulang jika > 500 KB).
     *
     * @param string $tmpPath      Path file temporary ($_FILES['x']['tmp_name'])
     * @param string $destDir      Direktori tujuan (harus ada dan writable)
     * @param int    $maxWidth     Lebar maksimal piksel. Gambar lebih besar akan di-resize.
     * @param int    $quality      Kualitas awal WebP 1-100 (default 80)
     * @param int    $maxSizeBytes Target ukuran maksimal file dalam bytes (default 500 KB = 512,000 bytes)
     * @return array ['filename'=>string, 'size_before'=>int, 'size_after'=>int, 'saved'=>int]
     * @throws \RuntimeException jika gambar tidak bisa diproses
     */
    public static function compressImage(
        string $tmpPath,
        string $destDir,
        int $maxWidth = 1200,
        int $quality = 80,
        int $maxSizeBytes = 500 * 1024
    ): array {
        // Deteksi mime type dari konten file (bukan ekstensi — lebih aman)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmpPath);

        switch ($mime) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($tmpPath);
                break;
            case 'image/png':
                $src = imagecreatefrompng($tmpPath);
                if ($src) {
                    imagepalettetotruecolor($src);
                    imagealphablending($src, true);
                    imagesavealpha($src, true);
                }
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($tmpPath);
                break;
            case 'image/gif':
                $src = imagecreatefromgif($tmpPath);
                break;
            default:
                throw new \RuntimeException("Tipe gambar tidak didukung: {$mime}");
        }

        if (!$src) {
            throw new \RuntimeException('Gagal memuat gambar. File mungkin rusak.');
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Hitung dimensi awal jika perlu resize
        if ($origW > $maxWidth) {
            $currW = $maxWidth;
            $currH = (int) round($origH * ($maxWidth / $origW));
        } else {
            $currW = $origW;
            $currH = $origH;
        }

        // Generate nama file unik berekstensi .webp
        $filename = bin2hex(random_bytes(20)) . '.webp';
        $destPath = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // Loop kompresi cerdas: jika ukuran > 500 KB, turunkan dimensi & kualitas secara iteratif
        $currQuality = $quality;
        $attempt = 0;
        $maxAttempts = 5;

        do {
            $attempt++;
            $canvas = imagecreatetruecolor($currW, $currH);

            // Background putih untuk transparansi
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);

            // Resampling berkualitas tinggi
            imagecopyresampled($canvas, $src, 0, 0, 0, 0, $currW, $currH, $origW, $origH);

            if (!imagewebp($canvas, $destPath, $currQuality)) {
                imagedestroy($canvas);
                imagedestroy($src);
                throw new \RuntimeException('Gagal menyimpan gambar WebP ke disk.');
            }
            imagedestroy($canvas);

            clearstatcache(true, $destPath);
            $sizeAfter = filesize($destPath);

            // Jika ukuran sudah <= target 500 KB, selesai
            if ($sizeAfter <= $maxSizeBytes) {
                break;
            }

            // Jika masih > 500 KB, kurangi resolusi 15% dan kualitas 10 poin
            $currW = (int) round($currW * 0.85);
            $currH = (int) round($currH * 0.85);
            $currQuality = max(30, $currQuality - 10);

        } while ($attempt < $maxAttempts && ($currW > 300 || $currQuality > 30));

        imagedestroy($src);

        $sizeBefore = filesize($tmpPath);
        clearstatcache(true, $destPath);
        $finalSize = filesize($destPath);

        return [
            'filename'    => $filename,
            'path'        => $destPath,
            'size_before' => $sizeBefore,
            'size_after'  => $finalSize,
            'saved'       => max(0, $sizeBefore - $finalSize),
        ];
    }

    // =========================================================================
    //  PDF: Validate + Lightweight Optimizer
    // =========================================================================

    /**
     * Proses dan simpan file PDF dengan validasi ketat + optimasi ringan.
     *
     * @param string $tmpPath      Path file temporary
     * @param string $destDir      Direktori tujuan
     * @param int    $maxSizeBytes Batas ukuran maksimal file asli yang diizinkan (default 10MB)
     * @return array ['filename'=>string, 'size_before'=>int, 'size_after'=>int, 'saved'=>int]
     * @throws \RuntimeException jika validasi gagal
     */
    public static function processPdf(
        string $tmpPath,
        string $destDir,
        int $maxSizeBytes = 10 * 1024 * 1024
    ): array {
        // 1. Validasi magic bytes — '%PDF'
        $handle = fopen($tmpPath, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Gagal membuka file PDF.');
        }
        $header = fread($handle, 5);
        fclose($handle);

        if (strncmp($header, '%PDF-', 5) !== 0) {
            throw new \RuntimeException('File bukan PDF yang valid. Harap unggah file PDF asli.');
        }

        // 2. Cek ukuran file asli
        $sizeBefore = filesize($tmpPath);
        if ($sizeBefore > $maxSizeBytes) {
            $maxMB    = round($maxSizeBytes / (1024 * 1024), 1);
            $actualMB = round($sizeBefore / (1024 * 1024), 1);
            throw new \RuntimeException(
                "Ukuran PDF ({$actualMB}MB) melebihi batas maksimal server ({$maxMB}MB)."
            );
        }

        // 3. Coba optimasi stream objects yang belum terkompresi
        $content      = file_get_contents($tmpPath);
        $optimized    = self::optimizePdfStreams($content);
        $useOptimized = strlen($optimized) < strlen($content);
        $finalContent = $useOptimized ? $optimized : $content;

        // 4. Simpan ke dest
        $filename = bin2hex(random_bytes(20)) . '.pdf';
        $destPath = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($destPath, $finalContent) === false) {
            throw new \RuntimeException('Gagal menyimpan file PDF ke disk.');
        }

        clearstatcache(true, $destPath);
        $sizeAfter = filesize($destPath);

        return [
            'filename'    => $filename,
            'path'        => $destPath,
            'size_before' => $sizeBefore,
            'size_after'  => $sizeAfter,
            'saved'       => max(0, $sizeBefore - $sizeAfter),
        ];
    }

    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    /**
     * Coba kompresi stream objects PDF yang belum menggunakan FlateDecode (zlib).
     * Hanya efektif untuk PDF dari Word/LibreOffice, bukan scan.
     */
    private static function optimizePdfStreams(string $content): string
    {
        $result = preg_replace_callback(
            '/<<([^>]*)>>\s*\nstream\r?\n(.*?)\nendstream/s',
            function (array $m) {
                $dict    = $m[1];
                $rawData = $m[2];

                // Skip jika sudah punya /Filter (sudah dikompres)
                if (stripos($dict, '/Filter') !== false) {
                    return $m[0];
                }

                $compressed = gzcompress($rawData, 6);
                if ($compressed === false || strlen($compressed) >= strlen($rawData)) {
                    return $m[0];
                }

                $newDict = $dict . "\n/Filter /FlateDecode\n/Length " . strlen($compressed);
                return "<<{$newDict}>>\nstream\n{$compressed}\nendstream";
            },
            $content
        );

        return $result ?? $content;
    }
}
