<?php

// From shopRepairActions
$target_path = wa()->getDataPath('products/', true, 'shop');
$target = $target_path . 'thumb.php';
$php_file = '<?php
$file = dirname(__FILE__)."/../../../../"."/wa-apps/shop/lib/config/data/thumb.php";

if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
';
waFiles::write($target, $php_file);

foreach (range(0, 4) as $x) {
    foreach ([0, 1] as $y) {
        $path = wa()->getDataPath("products/$y$x/webp/", true, 'shop');
        waFiles::delete($path);
    }
}

waFiles::delete(wa()->getDataPath('plugins/cwebp', true, 'shop'));