<?php

return array(
    'name'        => 'Tag editor',
    'description' => 'Edit and delete product tags',
    'img'         => 'img/tageditor16.png',
    'version'     => '1.16.3',
    'vendor'      => 817747,
    'handlers'    => array(
        'backend_products' => 'backendProducts',
        'frontend_head'    => 'frontendHead',
        'frontend_search'  => 'frontendSearch',
        'sitemap'          => 'sitemap',
        'product_save'     => 'productSave',
        'product_delete'   => 'productDelete',
        'reset'            => 'reset',
        'rights.config'    => 'rightsConfig',
        'routing'          => 'routing',
    ),
);
