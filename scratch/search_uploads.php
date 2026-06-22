<?php
$dir = realpath(__DIR__ . '/../');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $filePath = $file->getPathname();
    if (strpos($filePath, 'scratch') !== false || strpos($filePath, 'database') !== false || strpos($filePath, '.git') !== false) {
        continue;
    }
    $content = file_get_contents($filePath);
    if (strpos($content, 'move_uploaded_file') !== false || strpos($content, '$_FILES') !== false) {
        echo "Found upload logic in: " . basename($filePath) . "\n";
    }
}
