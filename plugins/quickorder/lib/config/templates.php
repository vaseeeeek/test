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
        'affiliate' => [
            'name' => _wp('Affiliate template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.affiliate.html',
        ],
        'contact' => [
            'name' => _wp('Contact fields template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.contact.fields.html',
        ],
        'payment' => [
            'name' => _wp('Payment methods template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.payment.methods.html',
        ],
        'shipping' => [
            'name' => _wp('Shipping methods template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.shipping.methods.html',
        ],
        'product' => [
            'name' => _wp('Product item template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.product.html',
        ],
        'product_service' => [
            'name' => _wp('Product item service template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.product.service.html',
        ],
        'sku_variants_stocks' => [
            'name' => _wp('Sku variant stocks template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/include.sku_variants.stocks.html',
        ],
        'form' => [
            'name' => _wp('Common template'),
            'path' => dirname(__FILE__) . '/../../templates/actions/frontend/quickorder.form.html',
        ]
    ],
];
