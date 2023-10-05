<?php
/**
 * @package Syrattach
 * @author Serge Rodovnichenko <sergerod@gmail.com>
 * @version 1.0.0
 * @copyright (c) 2014, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */
return array(
    'frontend_product_hook' => array(
        'title'         => _wp('Hook'),
        'description'   => _wp('Hook name to display filelist. Each hook placement depends of theme design. <a href="http://www.webasyst.com/developers/docs/plugins/hooks/shop/frontend_product/" target="_blank">More info about hooks.</a>'),
        'value'         => '0',
        'control_type'  => waHtmlControl::SELECT,
        'options'       => array(
            array(
                'value'     => '0',
                'title'     => _wp('No. Disable hooks'),
            ),
            array(
                'value'     => 'block',
                'title'     => 'frontend_product.block'
            ),
            array(
                'value'     => 'block_aux',
                'title'     => 'frontend_product.block_aux'
            ),
        )
    ),
    'template' => array(
        'title'             => _wp('Template'),
        'description'       => _wp("Template to display at the hook position. HTML+Smarty"),
        'control_type'      => waHtmlControl::CUSTOM . ' shopSyrattachPlugin::templateControl'
    )
);