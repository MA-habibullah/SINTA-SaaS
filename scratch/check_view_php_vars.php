<?php
$content = file_get_contents(__DIR__ . '/../views/tambah_siswa.php');
preg_match_all('/<\?php(.*?)\?>/s', $content, $matches);
echo "=== PHP Blocks ===\n";
foreach ($matches[1] as $idx => $block) {
    echo "Block $idx:\n" . trim($block) . "\n-------------------\n";
}

preg_match_all('/<\?=(.*?)\?>/s', $content, $matches_short);
echo "\n=== PHP Short Output Blocks ===\n";
foreach ($matches_short[1] as $idx => $block) {
    echo "Short Block $idx: " . trim($block) . "\n";
}
