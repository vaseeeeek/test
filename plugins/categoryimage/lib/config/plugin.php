<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
return array(
    'name' => /*_wp*/('Category images'),
    'description' => /*_wp*/('Allows to upload category images'),
    'version' => '1.1',
    'vendor' => 809114,
    'img' => 'img/categoryimage.png',
    'shop_settings' => true,
    'handlers' => array(
        'category_save' => 'categorySave',
        'category_delete' => 'categoryDelete',
        'backend_products' => 'categoryTitle',
        'backend_category_dialog' => 'categoryDialog',
    ),
);
