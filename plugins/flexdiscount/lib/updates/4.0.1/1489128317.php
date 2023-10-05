<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Удаление ненужных файлов

$files = array(
    dirname(__FILE__) . '/../../../templates/actions/coupons/',
    dirname(__FILE__) . '/../../../js/jquery.mask.min.js',
);

foreach ($files as $file) {
    try {
        waFiles::delete($file, true);
    } catch (waException $e) {
        
    }
}