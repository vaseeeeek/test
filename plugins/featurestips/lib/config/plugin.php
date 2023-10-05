<?php

return array(
    'name' => _w('Features tips'),
    'description' => _w('Tool tips for products features'),
    'img' => 'img/logo.png',
    'vendor' => 991800,
    'version' => '1.2',
    'handlers' => array(
        'frontend_head' => 'frontendHead',
        'frontend_product' => 'frontendProduct',
    ),
    'shop_settings' => true,
    'frontend' 		=> true,
);