<?php

return array(
    'name' => 'Форма заказа',
    'description' => 'Оформление заказа на одной странице',
    'vendor'=>'972539',
    'version'=>'99.0.0',
    'img'=>'img/brands.png',
    //'shop_settings' => true,
    'frontend'    => true,
    'icons'=>array(
        16 => 'img/brands.png',
    ),
    'handlers' => array(
        'frontend_checkout' => 'frontendCheckout',
        'frontend_cart' => 'frontendCart'
    ),
);
//EOF
