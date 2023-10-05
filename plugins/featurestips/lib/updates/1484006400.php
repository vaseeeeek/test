<?php

try {
    // old files
    $file = wa('shop')->getAppPath('plugins/featurestips/css/index.html');
    waFiles::delete($file);
    $file = wa('shop')->getAppPath('plugins/featurestips/img/index.html');
    waFiles::delete($file);
    $file = wa('shop')->getAppPath('plugins/featurestips/img/instruction/index.html');
    waFiles::delete($file);
    $file = wa('shop')->getAppPath('plugins/featurestips/img/instruction/en/index.html');
    waFiles::delete($file);
    $file = wa('shop')->getAppPath('plugins/featurestips/img/instruction/ru/index.html');
    waFiles::delete($file);
    $file = wa('shop')->getAppPath('plugins/featurestips/js/index.html');
    waFiles::delete($file);
}
catch (Exception $e)
{
    waLog::log('shop/plugins/featurestips: unable to delete old files "index.html".');
}