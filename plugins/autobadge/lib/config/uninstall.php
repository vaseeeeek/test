<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
// Удаление каталога с картинками
try {
    waFiles::delete(wa('shop')->getDataPath('plugins/autobadge/', true, 'site'), true);
} catch (Exception $e) {
    
}