<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

try {
    $update = 0;
    $config_path = wa()->getDataPath('plugins/quickorder/config.php', false, 'shop', false);
    if (file_exists($config_path)) {
        $update = 1;
        $config = include $config_path;
        waFiles::delete($config_path, true);
    }
    $fields_path = wa()->getDataPath('plugins/quickorder/fields.php', false, 'shop', false);
    if (file_exists($fields_path)) {
        $update = 1;
        $fields = include $fields_path;
        waFiles::delete($fields_path, true);
    }
    if ($update) {
        $settings = array(
            'status' => 0,
            'terms' => !empty($config['terms']) ? 1 : 0,
            'terms_text' => !empty($config['terms_text']) ? (!empty($config['terms_name']) ? str_replace('%terms%', $config['terms_name'], $config['terms_text']) : $config['terms_text']) : _wp("I am agree to the terms of service"),
            'terms_error' => !empty($config['terms_error']) ? $config['terms_error'] : _wp('You must read and agree to the Terms of service to place an order.'),
            'shared_display_settings' => 0,
            'product' => array(
                'use_hook' => !empty($config['enable_frontend_product_hook']) ? 1 : 0,
                'button_name' => !empty($config['button_name']) ? $config['button_name'] : _wp('Buy now with 1-Click'),
                'button_display' => 'table',
                'button_css' => '',
                'form_title' => !empty($config['button_name']) ? $config['button_name'] : _wp('Buy now with 1-Click'),
                'form_after_title' => '',
                'user_comment' => !empty($config['enable_user_comment']) ? 1 : 0,
                'coupon_field' => !empty($config['show_coupon']) ? 1 : 0,
                'discount_info' => 1,
                'total_price' => 1,
                'captcha' => 0,
                'form_before_submit' => !empty($config['form_text']) ? $config['form_text'] : '',
                'submit_button' => _wp('Send'),
                'successfull_message' => !empty($config['order_text']) ? str_replace('$order_id', '{$order.id}', $config['order_text']) : shopQuickorderPluginHelper::getDefaultSuccessMsg(),
                'product_image' => 1,
                'image_size_w' => '96',
                'image_size_h' => '96',
                'product_quantity' => !empty($config['hide_quantity']) ? 1 : 0,
                'product_services' => 0,
                'product_skus' => 0,
                'product_compareatprice' => 0,
                'product_quantity_mult' => 1,
                'ruble_sign' => 'rub',
                'ya_counter' => '',
                'yaecom' => '',
                'yaecom_goal_id' => '',
                'yaecom_container' => '',
                'ya_fopen' => '',
                'ya_submit' => '',
                'ya_submit_error' => '',
                'ga_counter' => '',
                'ga_category_fopen' => '',
                'ga_action_fopen' => '',
                'ga_category_submit' => '',
                'ga_action_submit' => '',
                'ga_category_submit_error' => '',
                'ga_action_submit_error' => '',
                'use_important' => '',
                'fields_layout' => 1,
            ),
            'cart' => array(),
            'shared_analytics_settings' => 1,
            'shared_appearance_settings' => 1,
            'css' => '',
            'minimal' => array(
                'price' => !empty($config['minimal']['price']) ? $config['minimal']['price'] : '',
                'product_sum' => '',
                'total_quantity' => '',
                'product_quantity' => '',
            ),
            'use_delpayfilter' => 0,
            'use_flexdiscount_ad' => 0,
            'collapse_link' => _wp('show more'),
            'flexdiscount_avt' => 0,
            'flexdiscount_prices' => 0,
            'flexdiscount_prices_com' => 0,
            'fields' => array(),
            'shipping' => array(),
            'payment' => array(),
            'appearance' => array(),
        );
        if (empty($config['popup_quickorder'])) {
            $settings['product']['hide_button'] = 1;
        }
        $settings['cart'] = $settings['product'];
        if (!empty($config['cart_button_name'])) {
            $settings['cart']['button_name'] = $config['cart_button_name'];
        }
        $settings['cart']['use_hook'] = !empty($config['enable_frontend_product_hook']) ? 1 : 0;

        if (!empty($fields)) {
            $new_fields = array();
            foreach ($fields as $field) {
                if (!empty($field['field_type']) && !empty($field['field_value'])) {
                    $new_fields[] = array(
                        0 => array(
                            'name' => 'name',
                            'value' => !empty($field['field_name']) ? $field['field_name'] : ''
                        ),
                        1 => array(
                            'name' => 'placeholder',
                            'value' => ''
                        ),
                        2 => array(
                            'name' => 'type',
                            'value' => ($field['field_type'] == 'address' ? 'address::' : '') . ($field['field_value'] == 'name' ? 'firstname' : $field['field_value'])
                        ),
                        3 => array(
                            'name' => 'css_class',
                            'value' => !empty($field['css_class']) ? $field['css_class'] : ''
                        ),
                        4 => array(
                            'name' => 'required',
                            'value' => !empty($field['required']) && $field['required'] == '1' ? 1 : 0
                        ),
                    );
                }
            }
            if ($new_fields) {
                $settings['fields']['cart'] = $settings['fields']['product'] = json_encode($new_fields);
            }
        }

        $storefront = 'all';

        // Статус плагина
        (new waAppSettingsModel())->set('shop.quickorder', 'status', 1);

        // Сохраняем настройки
        $model = new shopQuickorderPluginSettingsModel();
        $model->set($storefront, $settings);
    }
} catch (Exception $e) {

}

// Удаление ненужных файлов
$files = array(
    dirname(__FILE__) . '/../../../css/quickorderFrontendOriginal.css',
    dirname(__FILE__) . '/../../../img/overlay.png',
    dirname(__FILE__) . '/../../../img/quickorder25.png',
    dirname(__FILE__) . '/../../../js/quickorder.js',
    dirname(__FILE__) . '/../../../lib/actions/shopQuickorderPluginFrontendSendQuickorder.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/shopQuickorderPluginSettingsSave.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/shopServicesetsPluginSettings.action.php',
    dirname(__FILE__) . '/../../../lib/actions/shopQuickorderPluginFrontendGetCartQuickorder.controller.php',
    dirname(__FILE__) . '/../../../lib/actions/shopQuickorderPluginBackendRestoreCss.controller.php',
    dirname(__FILE__) . '/../../../lib/config/config.php',
    dirname(__FILE__) . '/../../../lib/config/fields.php',
);

foreach ($files as $file) {
    try {
        waFiles::delete($file, true);
    } catch (Exception $e) {

    }
}