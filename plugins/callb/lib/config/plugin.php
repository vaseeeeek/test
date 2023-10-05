<?php

/*
 * @author Max Severin <makc.severin@gmail.com>
 */
return array(
    'name' => /*_wp*/('Callback'),
    'version' => '1.4.0',
    'img' => 'img/callb.png',
    'vendor' => 1020720,
    'shop_settings' => true,
    'custom_settings' => true,
    'frontend' => true,
    'handlers' => array(
        'backend_menu'  => 'backendMenu',
        'frontend_head' => 'frontendHeader',
    ),
);