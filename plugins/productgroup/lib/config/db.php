<?php
return array(
    'shop_productgroup_group' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 64, 'null' => 0),
        'markup_template_id' => array('varchar', 64, 'null' => 0, 'default' => ''),
        'is_shown' => array('tinyint', 3, 'unsigned' => 1, 'null' => 0, 'default' => '1'),
        'related_feature_id' => array('int', 10, 'unsigned' => 1),
        'sort' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_productgroup_group_settings' => array(
        'group_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'scope' => array('varchar', 10, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'scope', 'name'),
        ),
    ),
    'shop_productgroup_product_group' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'group_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'group_id' => 'group_id',
        ),
    ),
    'shop_productgroup_product_group_product' => array(
        'product_group_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'product_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'label' => array('varchar', 250),
        'is_primary' => array('tinyint', 3, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        'sort' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => array('product_group_id', 'product_id'),
            'product_id' => 'product_id',
        ),
    ),
    'shop_productgroup_settings' => array(
        'storefront' => array('varchar', 255, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('storefront', 'name'),
        ),
    ),
    'shop_productgroup_storefront_theme_markup_style_settings' => array(
        'theme_id' => array('varchar', 100, 'null' => 0),
        'storefront' => array('varchar', 169, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('theme_id', 'storefront', 'name'),
        ),
    ),
);
