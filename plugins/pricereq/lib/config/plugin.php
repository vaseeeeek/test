<?php

/*
 * @author Max Severin <makc.severin@gmail.com>
 */
return array(
    'name' => /*_wp*/('Price request'),
    'version' => '1.1.0',
    'img' => 'img/pricereq.png',
    'vendor' => 1020720,
    'shop_settings' => true,
    'custom_settings' => true,
    'frontend' => true,
    'handlers' => array(
        'backend_menu'  => 'backendMenu',
        'frontend_head' => 'frontendHead',
    ),
);