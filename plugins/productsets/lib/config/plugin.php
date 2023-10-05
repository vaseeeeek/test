<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    'name' =>  /*_wp*/('Product sets'),
    'description' =>  /*_wp*/('Creation and sale of product sets with discount'),
    'img' => 'img/productsets.png',
    'vendor' => '969712',
    'version' => '2.4',
    'frontend' => true,
    'shop_settings' => true,
    'handlers' => array(
        'backend_products' => 'backendProducts',
        'backend_orders' => 'backendOrders',
        'cart_delete' => 'cartDelete',
        'category_delete' => 'categoryDelete',
        'frontend_category' => 'frontendCategory',
        'frontend_head' => 'frontendHead',
        'frontend_product' => 'frontendProduct',
        'order_action.create' => 'orderActionCreate',
        'order_calculate_discount' => 'orderCalculateDiscount',
        'product_delete' => 'productDelete',
        'product_sku_delete' => 'productSkuDelete',
        'set_delete' => 'setDelete',
    ),
);
