<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Удаление ненужных файлов
$files = array(
    dirname(__FILE__) . '/../../../css/productsetsFrontendOriginal.css',
    dirname(__FILE__) . '/../../../img/dummy140.jpg',
    dirname(__FILE__) . '/../../../img/opacity-white.png',
    dirname(__FILE__) . '/../../../img/overlay.png',
    dirname(__FILE__) . '/../../../js/productsets.js',
    dirname(__FILE__) . '/../../../js/productsetsFrontend.js',
    dirname(__FILE__) . '/../../../js/productsetsFrontendLocaleOriginal.js',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopProductsetsPluginBackendColor.action.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopProductsetsPluginBackendColorSave.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopProductsetsPluginBackendCss.action.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopProductsetsPluginBackendHandler.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopProductsetsPluginBackendJslocale.action.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopProductsetsPluginBackendSave.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/backend/shopProductsetsPluginBackendTemplate.action.php',
    dirname(__FILE__) . '/../../../lib/classes/shopProductsetsHelper.class.php',
    dirname(__FILE__) . '/../../../lib/config/locale_config.php',
    dirname(__FILE__) . '/../../../lib/config/settings.php',
    dirname(__FILE__) . '/../../../lib/models/shopProductsetsItemsPlugin.model.php',
    dirname(__FILE__) . '/../../../lib/models/shopProductsetsLocalePlugin.model.php',
    dirname(__FILE__) . '/../../../templates/actions/backend/BackendColor.html',
    dirname(__FILE__) . '/../../../templates/actions/backend/BackendCss.html',
    dirname(__FILE__) . '/../../../templates/actions/backend/BackendJslocale.html',
    dirname(__FILE__) . '/../../../templates/actions/backend/BackendTemplate.html',
    dirname(__FILE__) . '/../../../templates/set.html',
);
foreach ($files as $file) {
    try {
        waFiles::delete($file, true);
    } catch (waException $e) {

    }
}