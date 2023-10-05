<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    'name' => /*_wp*/("Badges"),
    'description' => /*_wp*/("Automatic creation of badges for products"),
    'img' => 'img/autobadge.png',
    'vendor' => '969712',
    'version' => '1.7.7',
    'shop_settings' => true,
    'frontend' => true,
    'handlers' => array(
        'frontend_head' => 'frontendHead',
        'frontend_products' => 'frontendProducts',
        'routing' => 'routing',
    )
);
