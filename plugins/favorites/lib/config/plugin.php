<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
return array(
    'name' => /*_wp*/('Favorites'),
    'description' => /*_wp*/('Allows users to add products to favorites.'),
    'version'=>'1.4',
    'vendor' => 809114,
    'img'=>'img/favorites.png',
    'shop_settings' => true,
    'frontend'    => true,
    'icons'=>array(
        16 => 'img/favorites.png',
    ),
    'handlers' => array(
        'frontend_product' => 'frontendProduct',
        'frontend_my' => 'frontendMy',
        'frontend_my_nav' => 'frontendMyNav',
        'frontend_head' => 'frontendHead'
    ),
);
