<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    'name' => _wp("WhatsApp share button"),
    'description' => _wp("Plugin lets the customers share the product in WhatsApp"),
    'img' => 'img/whatsapp.png',
    'vendor' => '969712',
    'version' => '1.2',
    'shop_settings' => true,
    'handlers' => array(
        'frontend_product' => 'frontendProduct',
    ),
);