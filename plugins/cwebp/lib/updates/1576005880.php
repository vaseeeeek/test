<?php
/** @var shopCwebpPlugin $this */

$files = [
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-103-linux-x86-64-static',
];

foreach ($files as $file) {
    try {
        waFiles::delete($this->path . '/' . $file);
    } catch (Exception $e) {
    }
}