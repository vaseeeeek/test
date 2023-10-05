<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
return array(
    'name' => _wp("Delivery and payment filter"),
    'description' => _wp("Filters delivery and payment methods by any criteria"),
    'img' => 'img/delpayfilter.png',
    'vendor' => '969712',
    'version' => '1.7.3',
    'shop_settings' => true,
    'handlers' => array(
        'order_calculate_discount' => 'orderCalculateDiscount',
        'checkout_after_shipping' => 'checkoutAfterShipping',
        'checkout_render_shipping' => 'checkoutRenderShipping',
        'checkout_render_payment' => 'checkoutRenderPayment',
        'frontend_order_cart_vars' => 'frontendOrderCartVars',
    ),
);
