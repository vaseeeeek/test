<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    'name' => /*_wp*/('1-Click Ordering'),
    'description' => /*_wp*/('1-Click product and cart ordering'),
    'img' => 'img/quickorder.png',
    'vendor' => '969712',
    'version' => '2.11.11',
    'shop_settings' => true,
    'frontend' => true,
    'handlers' => array(
        'frontend_product' => 'frontendProduct',
        'frontend_head' => 'frontendHead',
        'frontend_cart' => 'frontendCart',
        'frontend_order_cart_vars' => 'frontendOrderCartVars',
        'backend_reports' => 'backendReports',
        'backend_order' => 'backendOrder',
    ),
);