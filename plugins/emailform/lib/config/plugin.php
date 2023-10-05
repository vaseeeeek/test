<?php

return array(
    'name'              => 'Скидка за подписку',
    'img'               => 'img/emailform.png',
    'description'       => 'Сборщик email адресов, предложи купон за подписку',
    'version'           => '1.042',
    'vendor'            => 973724,
    //'shop_settings'     => true,
    'custom_settings'   => true, //формирование пользовательского интерфейса настроек
    'frontend'          => true, //флаг, обозначающий, есть ли у приложения фронтенд
    'handlers' => array(
        'frontend_footer' => 'frontendFooter',
    ),
);