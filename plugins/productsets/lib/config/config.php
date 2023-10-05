<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

return [
    'templates' => [
        'product_skus' => [
            'name' => _wp('Skus popup template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/FrontendGetProductSkus.html',
        ],
        'bundle' => [
            'name' => _wp('Bundle template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.bundle.html',
        ],
        'bundle_item' => [
            'name' => _wp('Bundle item template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.bundle.item.html',
        ],
        'bundle_single_item' => [
            'name' => _wp('Bundle single item template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.bundle.single.item.html',
        ],
        'bundle_total_block' => [
            'name' => _wp('Bundle total block template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.bundle.total.block.html',
        ],
        'userbundle' => [
            'name' => _wp('Userbundle template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.userbundle.html',
        ],
        'userbundle_item' => [
            'name' => _wp('Userbundle item template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.userbundle.item.html',
        ],
        'userbundle_group' => [
            'name' => _wp('Userbundle group'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.userbundle.group.html',
        ],
        'sets' => [
            'name' => _wp('Sets template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/sets.html',
        ]
    ],
    'system_templates' => [
        'vars' => [
            'name' => _wp('Variables'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.vars.html',
        ],
        'item_vars' => [
            'name' => _wp('Item variables'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.item.vars.html',
        ],
    ]
];
