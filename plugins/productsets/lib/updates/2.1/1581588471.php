<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Удаление ненужных файлов
$files = array(
    dirname(__FILE__) . '/../../../lib/config/settings.php',
);
foreach ($files as $file) {
    try {
        waFiles::delete($file, true);
    } catch (waException $e) {

    }
}