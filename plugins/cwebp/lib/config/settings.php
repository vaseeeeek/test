<?php

return array(
    'enabled' => array(
        'title' => _wp('Plugin enabled'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0,
    ),

    'converters' => array(
        'title' => _wp('Convertion method'),
        'control_type' => waHtmlControl::GROUPBOX,
        'options_callback' => array('shopCwebpPluginConvert', 'getAvailableConverters'),
    ),

    'type' => array(
        'title' => _wp('Type'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            array(
                'value' => 'ondemand',
                'title' => _wp('On demand'),
                'description' => _wp('Allow on demand conversion, recommended way.'),
            ),
            array(
                'value' => 'cron',
                'title' => _wp('Use cron'),
                'description' => _wp('Use cron task to convert images in background. Add following command: ') . '<b>/usr/bin/php -q ' . wa()->getConfig()->getPath('root').'/cli.php shop cwebpPluginRun</b>.',
            ),
            array(
                'value' => 'rewrite',
                'title' => 'rewrite',
                'description' => _wp('Be sure to make all the settings according to the instructions on the plugin page. Will not work in the cloud. Add command: ') . '<b>/usr/bin/php -q ' . wa()->getConfig()->getPath('root').'/cli.php shop cwebpPluginConvert</b>.',
                'disabled' => wa()->appExists('hosting'),
            ),
        ),
        'value' => 'ondemand',
    ),

    'products_only' => array(
        'title' => _wp('Products only'),
        'description' => _wp('Convert product images only'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0,
    ),

    'no_png' => array(
        'title' => _wp('Skip PNG'),
        'description' => _wp('Skip images in PNG format in case of quality issues.'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0,
    ),

    'log' => array(
        'title' => _wp('Enable logging'),
        'description' => _wp('Enable conversions log file in cwebp.log'),
        'control_type' => waHtmlControl::CHECKBOX,
        'value' => 0,
    ),

    'quality' => array(
        'title' => _wp('Quality'),
        'description' => _wp('Compression quality from 1 to 99. 80 recommended'),
        'control_type' => waHtmlControl::INPUT,
        'class' => 'short',
        'value' => '80',
    ),

    'custom' => array(
        'title' => _wp('Tasks'),
        'description' => '',
        'control_type' => waHtmlControl::CUSTOM . ' ' . 'shopCwebpPlugin::getSettingsButtons',
    ),
);