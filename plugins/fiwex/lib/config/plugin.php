<?php
return array(
    'name' => 'Подсказки к характеристикам фильтра',
    'img' => 'img/fiwex.png',
    'descriptions' => 'Подсказки к характеристикам и значениям характеристик в фильтре',
    'vendor' => 873332,
    'author' => 'wm-site',
    'version' => '1.4.0',
    'shop_settings' => true,
    'handlers' => array(
        'frontend_category' => 'frontendCategory',
        'frontend_product' => 'frontendProduct',
        'frontend_footer' => 'frontendFooter'
    ),
    'frontend'=>true,
);