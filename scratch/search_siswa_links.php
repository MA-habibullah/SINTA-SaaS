<?php
$dir = realpath(__DIR__ . '/../');
echo "Searching in: " . $dir . "\n";
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $filePath = $file->getPathname();
    
    // skip scratch, database/migrations, .git
    if (strpos($filePath, 'scratch') !== false || strpos($filePath, 'database') !== false || strpos($filePath, '.git') !== false) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        continue;
    }
    if (strpos($content, '/siswa') !== false || strpos($content, 'siswa/edit') !== false || strpos($content, 'siswa/tambah') !== false) {
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (strpos($line, '/siswa') !== false || strpos($line, 'siswa/edit') !== false || strpos($line, 'siswa/tambah') !== false) {
                printf("File: %s:%d | %s\n", basename($filePath), $i + 1, trim($line));
            }
        }
    }
}
