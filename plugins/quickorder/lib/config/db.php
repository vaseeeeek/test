<?php
return array(
    'shop_quickorder_cart_items' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'code' => array('varchar', 32),
        'contact_id' => array('int', 11),
        'product_id' => array('int', 11, 'null' => 0),
        'sku_id' => array('int', 11, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'quantity' => array('int', 11, 'null' => 0, 'default' => '1'),
        'type' => array('enum', "'product','service'", 'null' => 0, 'default' => 'product'),
        'service_id' => array('int', 11),
        'service_variant_id' => array('int', 11),
        'parent_id' => array('int', 11),
        ':keys' => array(
            'PRIMARY' => 'id',
            'code' => 'code',
        ),
    ),
    'shop_quickorder_settings' => array(
        'storefront' => array('varchar', 50, 'null' => 0, 'default' => 'all'),
        'field' => array('varchar', 50, 'null' => 0),
        'ext' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('storefront', 'field', 'ext'),
            'field' => 'storefront',
        ),
    ),
);
