<?php
/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
return array(
    'name' => 'Автоопределение и выбор города',
    'description' => 'с использованием сервиса DaData',
    'vendor' => '962376',
    'version' => '1.15',
    'img' => 'img/plugin_icon.png',
    'frontend' => true,
    'custom_settings' => true,
    'handlers' => array(
        'address_autocomplete' => 'addressAutocomplete',

        'frontend_head' => 'frontendHead',
        'frontend_header' => 'frontendHeader',
        'frontend_checkout' => 'frontendCheckout',

        'backend_orders' => 'backendOrders',
        'backend_order_edit' => 'backendOrderEdit',

        'backend_customers' => 'backendCustomers',
        'backend_customer' => 'backendCustomer',

        'checkout_render_region' => 'checkoutRenderRegion'
    ),
);
