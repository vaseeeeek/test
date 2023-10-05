<?php
/** @var shopCwebpPlugin $this */

$files = [
    'lib/actions/shopCwebpPluginBackendRun.controller.php',
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-fbsd',
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-linux-0.6.1',
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-linux-1.0.2-shared',
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-linux-1.0.2-static',
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-sol',
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp-mac12',
    'lib/vendors/rosell-dk/webp-convert/src/Convert/Converters/Binaries/cwebp.exe',
    'js/',
];

foreach ($files as $file) {
    try {
        waFiles::delete($this->path . '/' . $file);
    } catch (Exception $e) {
    }
}