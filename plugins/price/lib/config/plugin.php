<?php

return array(
    'name' => 'Мульти Цены (Оптовые цены)',
    'description' => 'Поддержка различных типов цен для разных групп пользователей',
    'vendor' => 985310,
    'version' => '4.2.9',
    'img' => 'img/price.png',
    'shop_settings' => true,
    'frontend' => false,
    'importexport' => true,
    'handlers' => array(
        'frontend_products' => 'frontendProducts',
        'backend_product_sku_settings' => 'backendProductSkuSettings',
        'product_custom_fields' => 'productCustomFields',
        'product_save' => 'productSave',
        'backend_order_edit' => 'backendOrderEdit',
        'frontend_category' => 'frontendCategory',
        'signup' => 'signup',
    ),
);
//EOF
