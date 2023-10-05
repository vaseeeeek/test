<?php
return array(
    'shop_flexdiscount' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 200, 'null' => 0, 'default' => ''),
        'code' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'sort' => array('int', 10, 'null' => 0, 'default' => '0'),
        'status' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'description' => array('text'),
        'conditions' => array('text'),
        'target' => array('text'),
        'deny' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'frontend_sort' => array('int', 11, 'null' => 0, 'default' => '-1'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_flexdiscount_affiliate' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'order_id' => array('int', 11, 'null' => 0),
        'affiliate' => array('int', 11, 'null' => 0),
        'status' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => array('contact_id', 'order_id'),
        ),
    ),
    'shop_flexdiscount_coupon' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'type' => array('varchar', 9, 'null' => 0, 'default' => 'coupon'),
        'prefix' => array('varchar', 30, 'null' => 0, 'default' => ''),
        'code' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'start' => array('datetime'),
        'end' => array('datetime'),
        'comment' => array('text', 'null' => 0),
        'symbols' => array('varchar', 100, 'null' => 0, 'default' => ''),
        'length' => array('tinyint', 2, 'null' => 0, 'default' => '0'),
        'limit' => array('int', 11, 'null' => 0, 'default' => '-1'),
        'user_limit' => array('int', 11, 'null' => 0, 'default' => '0'),
        'used' => array('int', 11, 'null' => 0, 'default' => '0'),
        'name' => array('varchar', 30, 'null' => 0, 'default' => ''),
        'lifetime' => array('varchar', 20, 'null' => 0, 'default' => ''),
        'create_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'code' => 'code',
        ),
    ),
    'shop_flexdiscount_coupon_discount' => array(
        'coupon_id' => array('int', 11, 'null' => 0),
        'fl_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('coupon_id', 'fl_id'),
            'fl_id' => 'fl_id',
        ),
    ),
    'shop_flexdiscount_coupon_order' => array(
        'coupon_id' => array('int', 11, 'null' => 0),
        'order_id' => array('int', 11, 'null' => 0),
        'discount' => array('float', "14,2", 'null' => 0),
        'affiliate' => array('int', 11, 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        'code' => array('varchar', 50, 'default' => ''),
        'reduced' => array('tinyint', 1, 'default' => '1'),
        ':keys' => array(
            'coupon' => 'coupon_id',
            'code' => 'code',
        ),
    ),
    'shop_flexdiscount_group' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 50, 'default' => ''),
        'combine' => array('varchar', 3, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_flexdiscount_group_discount' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'fl_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'fl_id'),
        ),
    ),
    'shop_flexdiscount_order_params' => array(
        'order_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 30, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('order_id', 'name'),
            'name' => 'name',
        ),
    ),
    'shop_flexdiscount_params' => array(
        'fl_id' => array('int', 11, 'null' => 0),
        'field' => array('varchar', 50, 'null' => 0),
        'ext' => array('varchar', 50, 'null' => 0, 'default' => ''),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('fl_id', 'field', 'ext'),
        ),
    ),
    'shop_flexdiscount_settings' => array(
        'field' => array('varchar', 30, 'null' => 0),
        'ext' => array('varchar', 30, 'null' => 0, 'default' => ''),
        'value' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('field', 'ext'),
        ),
    ),
);
