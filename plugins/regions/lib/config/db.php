<?php
return array(
    'shop_regions_city' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'country_iso3' => array('varchar', 3),
        'region_code' => array('varchar', 255),
        'name' => array('varchar', 255),
        'storefront' => array('varchar', 255),
        'phone' => array('varchar', 255),
        'email' => array('varchar', 255),
        'schedule' => array('varchar', 255),
        'is_popular' => array('tinyint', 1),
        'is_enable' => array('tinyint', 1),
        'is_default_for_storefront' => array('tinyint', 1, 'default' => '0'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'domain_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'route' => array('varchar', 255, 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'is_default_for_storefront' => 'is_default_for_storefront',
            'sort' => 'sort',
            'domain_id' => 'domain_id',
            'is_popular' => 'is_popular',
            'is_enable' => 'is_enable',
            'country_iso3' => 'country_iso3',
            'region_code' => 'region_code',
            'storefront' => 'storefront',
            'create_datetime' => 'create_datetime',
            'update_datetime' => 'update_datetime',
        ),
    ),
    'shop_regions_city_param' => array(
        'city_id' => array('int', 11, 'null' => 0),
        'param_id' => array('int', 11, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('city_id', 'param_id'),
        ),
    ),
    'shop_regions_city_settings' => array(
        'city_id' => array('int', 11, 'null' => 0),
        'storefront_settings' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'city_id',
        ),
    ),
    'shop_regions_page_template' => array(
        'url' => array('varchar', 255, 'null' => 0),
        'content' => array('text'),
        'ignore_default' => array('enum', "'Y','N'", 'null' => 0, 'default' => 'N'),
        ':keys' => array(
            'PRIMARY' => 'url',
            'ignore_default' => 'ignore_default',
        ),
    ),
    'shop_regions_page_template_excluded_storefront' => array(
        'page_url' => array('varchar', 255, 'null' => 0),
        'storefront' => array('varchar', 255, 'null' => 0),
        'page_route_hash' => array('varchar', 40, 'null' => 0),
        ':keys' => array(
            'page_route_hash' => array('page_route_hash', 'unique' => 1),
            'storefront' => 'storefront',
            'page_url' => 'page_url',
        ),
    ),
    'shop_regions_param' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255),
        'sort' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'sort' => 'sort',
        ),
    ),
    'shop_regions_robots_option' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'domain' => array('varchar', 255, 'null' => 0),
        'is_custom' => array('tinyint', 4, 'null' => 0, 'default' => '0'),
        'robots_last_modified_time' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'domain' => 'domain',
        ),
    ),
    'shop_regions_settings' => array(
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'name',
        ),
    ),
    'shop_regions_user_environment' => array(
        'key' => array('varchar', 32, 'null' => 0, 'default' => ''),
        'cookies' => array('text', 'null' => 0),
        'time' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'key' => 'key',
            'time' => 'time',
        ),
    ),
);
