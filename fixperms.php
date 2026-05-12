<?php
$dirs = [
    __DIR__ . '/assets/uploads/images',
    __DIR__ . '/assets/uploads/media',
    __DIR__ . '/assets/uploads',
];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        chmod($dir, 0775);
        echo "Fixed: $dir<br>";
    } else {
        mkdir($dir, 0775, true);
        echo "Created: $dir<br>";
    }
}
echo "Done.";
