<?php

return array(
    'php.curl' => array(
        'name' => 'cURL',
        'description' => 'Обмен данными со сторонними серверами',
        'strict' => true,
    ),
    'php.vips' => array(
        'name' => 'vips',
        'description' => 'Расширение для работы с изображениями vips',
        'strict' => false,
    ),
    'php.gd' => array(
        'name' => 'gd',
        'description' => 'Расширение для работы с изображениями GD',
        'strict' => false,
    ),
    'php.imagick' => array(
        'name' => 'imagick',
        'description' => 'Расширение для работы с изображениями Imagick',
        'strict' => false,
    ),
    'php' => array(
        'strict' => true,
        'version' => '>=5.6',
    ),
);