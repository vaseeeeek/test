<?php

/**
 * Конфиг плагина
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */
 
return array(
    'name' => 'Покупка в один клик (lite)',
    'description' => 'Покупка товара в один клик',
    'version'=>'1.3.0',
    'vendor' => '989788',
    'img' => 'img/icon.png',
    'shop_settings' => true,
    'frontend' => true,
    'custom_settings' => true,
    'icons' => array(
        16 => 'img/icon.png',
    ),
    'handlers' => array(
        'backend_menu' => 'backendMenu',
        'frontend_head' => 'frontendHead',
        'frontend_product' => 'frontendProduct',
        'frontend_cart' => 'frontendCart',
        'frontend_footer' => 'frontendFooter',
        'rights.config' => 'rightsConfig',
    ),
);