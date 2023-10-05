<?php

$files = [
    wa()->getAppPath('plugins/cwebp/lib/vendors/whichbrowser', 'shop'),
    wa()->getAppPath('plugins/cwebp/lib/vendors/psr', 'shop')
];

foreach ($files as $file) {
    waFiles::delete($file);
}