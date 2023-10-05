<?php
return array(
    'name'     => 'Выбор характеристик и опций в списках',
    'version'  => '2.2.6',
    'critical'    => '2.2.4',
    'img'=> 'img/salesku.png',
    'vendor'   => '990614',
    'author' => 'Genasyst',
    'shop_settings' => true,
    'frontend' => true,
    'custom_settings' => true,
    'handlers' =>  array(
        'frontend_head' => 'frontendHead',
        'frontend_products' => 'frontendProducts',
    ),
);