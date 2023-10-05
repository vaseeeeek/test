<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
try {
    $settings_model = new shopFlexdiscountSettingsPluginModel();
    $file = dirname(__FILE__) . '/../../config/data/flexdiscount.block.styles.css';

    if (!$settings_model->getByField('field', 'styles')) {
        $view = shopFlexdiscountApp::get('system')['wa']->getView();
        if (file_exists($file)) {
            $contents = $view->fetch('string:' . file_get_contents($file));
            $settings_model->insert(array(
                'field' => 'styles',
                'ext' => '',
                'value' => $contents
            ), 2);
        }
    }
} catch (Exception $e) {

}
