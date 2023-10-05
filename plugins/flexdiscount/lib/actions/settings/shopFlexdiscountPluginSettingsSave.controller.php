<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginSettingsSaveController extends waJsonController
{

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_settings")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        $settings = waRequest::post('settings');
        if ($settings) {
            try {
                $isset_conditions = array(
                    'flexdiscount_affiliate_bonus',
                    'flexdiscount_user_discounts',
                    'flexdiscount_product_discounts',
                    'flexdiscount_avail_discounts',
                    'flexdiscount_my_discounts',
                    'flexdiscount_deny_discounts',
                    'enable_price_output',
                );
                foreach ($isset_conditions as $ic) {
                    if (!isset($settings[$ic])) {
                        $settings[$ic] = array();
                    }
                }

                $empty_conditions = array(
                    array('key' => 'frontend_prices', 'value' => 0),
                    array('key' => 'currency_rounding', 'value' => 0),
                    array('key' => 'default_affiliate_bonus', 'value' => 0),
                    array('key' => 'enable_frontend_cart_hook', 'value' => 0),
                    array('key' => 'update_infoblocks', 'value' => 0),
                    array('key' => 'cache_conditions', 'value' => 0),
                    array('key' => 'flexdiscount_affiliate_bonus', 'value' => 'value'),
                    array('key' => 'flexdiscount_user_discounts', 'value' => 'value'),
                    array('key' => 'flexdiscount_product_discounts', 'value' => 'value'),
                    array('key' => 'flexdiscount_avail_discounts', 'value' => 'value'),
                    array('key' => 'flexdiscount_my_discounts', 'value' => 'value'),
                    array('key' => 'flexdiscount_my_discounts', 'value' => 'show_nav'),
                    array('key' => 'flexdiscount_my_discounts', 'value' => 'show_nav_pos'),
                    array('key' => 'flexdiscount_my_discounts', 'value' => 'show_only_active'),
                    array('key' => 'flexdiscount_deny_discounts', 'value' => 'value'),
                    array('key' => 'enable_price_output', 'value' => 'value'),
                    array('key' => 'enable_price_output', 'value' => 'not_hide'),
                );
                foreach ($empty_conditions as $v) {
                    if ($v['value'] === 0) {
                        $settings[$v['key']] = !empty($settings[$v['key']]) ? 1 : 0;
                    } else {
                        $settings[$v['key']][$v['value']] = !empty($settings[$v['key']][$v['value']]) ? 1 : 0;
                    }
                }

                $serialize_conditions = array(
                    array('key' => 'flexdiscount_avail_discounts', 'value' => 'filter_by'),
                    array('key' => 'flexdiscount_avail_discounts', 'value' => 'ignore_deny'),
                );
                foreach ($serialize_conditions as $v) {
                    $settings[$v['key']][$v['value']] = !empty($settings[$v['key']][$v['value']]) ? serialize($settings[$v['key']][$v['value']]) : '';
                }
                
                $settings['ignore_plugins'] =  serialize(!empty($settings['ignore_plugins']) ? $settings['ignore_plugins'] : array());
                $settings['skip_shop_cart_plugins'] =  serialize(!empty($settings['skip_shop_cart_plugins']) ? $settings['skip_shop_cart_plugins'] : array());

                (new shopFlexdiscountSettingsPluginModel())->save($settings);
            } catch (waDbException $e) {
                $this->errors = 1;
            }
        }
    }

}
