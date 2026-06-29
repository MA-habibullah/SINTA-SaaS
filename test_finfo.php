<?php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$filePath = __DIR__ . '/storage/app/public/uploads/11111111-1111-1111-1111-111111111111/de5e7f15-9e0c-4823-9fab-401e055dd9a7/61675974a9de5f8247a09c1d5011d7e7c54b31cd.pdf'; // just test pdf
echo finfo_file($finfo, $filePath) . "\n";
