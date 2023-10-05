<?php
$delete_files = array(
    'css/sakeskuDefaultFrontend.css',
    '.integrate.log'
);

$root_path = wa()->getAppPath('plugins/salesku');
$path = $root_path . '/';

foreach ($delete_files as $file) {
    $filepath = $path.$file;
    if (file_exists($filepath)) {
        try {
            waFiles::delete($filepath);
        } catch (waException $e) {
            waLog::log('Не удалось удалить файл: '.$filepath, 'shop/salesku.log');
        }
    }
}