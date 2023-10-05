<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Удаление ненужных файлов
$files = array(
    dirname(__FILE__) . '/../../../lib/actions/frontend/shopFlexdiscountPluginFrontendMy.action.php',
    dirname(__FILE__) . '/../../../templates/actions/frontend/FrontendMy.html',
);

foreach ($files as $file) {
    try {
        waFiles::delete($file, true);
    } catch (waException $e) {

    }
}
