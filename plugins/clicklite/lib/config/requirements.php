<?php

/**
 * Обязательные натсройки для работы плагина
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

return array(
    'app.shop' => array(
        'strict' => true,
        'version' => '>=6.0',
    ),
    'php' => array(
        'strict' => true,
        'version' => '>=5.2',
    ),
    'phpini.max_exection_time'=>array(
        'name'=>'Максимальное время исполнения PHP-скриптов',
        'description'=>'',
        'strict'=>false,
        'value'=>'>60',
    ),
    'app.installer' => array(
        'strict' => true,
        'version' => '>=1.7.6',
    )
);
