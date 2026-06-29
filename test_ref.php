<?php
function testUpload(array &$oldPathsToDelete = []) {
    $oldPathsToDelete[] = '/tmp/test.txt';
}
$oldPaths = [];
testUpload($oldPaths);
var_dump($oldPaths);
