<?php

class shopSkoneclickCheckoutProcess{

    /* Прототип shopFrontendCheckoutAction */

    protected $plugin_id = "skoneclick";

    public $cookie = 'shop_skoneclick';

    protected $type = "";

    public function __construct($type = "product"){

        if($type == "cart"){
            $this->cookie = "shop_cart";
        }

        $this->type = $type;

    }

    public function createOrder($data, $additional, &$errors = array()){

        $settings = wa("shop")->getPlugin($this->plugin_id)->getSettings();

        $code = waRequest::cookie($this->cookie);

        if($this->type == "product"){
            $cart = new shopSkoneclickCart($code);
        }else{
            $cart = new shopCart($code);
        }

        if(!wa()->getSetting('ignore_stock_count')){
            $check_count = true;
            if(wa()->getSetting('limit_main_stock') && waRequest::param('stock_id')){
                $check_count = waRequest::param('stock_id');
            }

            $cart_model = new shopCartItemsModel();
            $not_available_items = $cart_model->getNotAvailableProducts($cart->getCode(), $check_count);
            foreach($not_available_items as $row){
                if($row['sku_name']){
                    $row['name'] .= ' (' . $row['sku_name'] . ')';
                }
                if($row['available']){
                    if($row['count'] > 0){
                        $template = _w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.');
                        $errors[] = sprintf($template, $row['count'], $row['name']);
                    }else{
                        $template = _w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience. Please remove this product from your shopping cart to proceed.');
                        $errors[] = sprintf($template, $row['name']);
                    }
                }else{
                    $errors[] = sprintf(_w('Oops! %s is not available for purchase at the moment. Please remove this product from your shopping cart to proceed.'), $row['name']);
                }
            }

            if($errors){
                return false;
            }
        }

        if(wa()->getUser()->isAuth()){
            $contact = wa()->getUser();
        }else{
            $contact = new waContact();
        }

        if(isset($data) && is_array($data)){
            foreach($data as $field => $value){
                $contact->set($field, $value);
            }
            $contact->save();
        }

        $items = $cart->items(false);
        // remove id from item
        foreach($items as &$item){
            unset($item['id']);
            unset($item['parent_id']);
        }
        unset($item);

        $order = array(
            'contact' => $contact,
            'items' => $items,
            'total' => $cart->total(false),
            'params' => isset($checkout_data['params']) ? $checkout_data['params'] : array(),
        );

        $order['discount_description'] = null;
        $order['discount'] = shopDiscounts::apply($order, $order['discount_description']);

        $order['shipping'] = 0;

        $routing_url = wa()->getRouting()->getRootUrl();
        $order['params']['storefront'] = wa()->getConfig()->getDomain() . ($routing_url ? '/' . $routing_url : '');
        if(wa()->getStorage()->get('shop_order_buybutton')){
            $order['params']['sales_channel'] = 'buy_button:';
        }

        if(($ref = waRequest::cookie('referer'))){
            $order['params']['referer'] = $ref;
            $ref_parts = @parse_url($ref);
            $order['params']['referer_host'] = $ref_parts['host'];
            // try get search keywords
            if(!empty($ref_parts['query'])){
                $search_engines = array(
                    'text' => 'yandex\.|rambler\.',
                    'q' => 'bing\.com|mail\.|google\.',
                    's' => 'nigma\.ru',
                    'p' => 'yahoo\.com',
                );
                $q_var = false;
                foreach($search_engines as $q => $pattern){
                    if(preg_match('/(' . $pattern . ')/si', $ref_parts['host'])){
                        $q_var = $q;
                        break;
                    }
                }
                // default query var name
                if(!$q_var){
                    $q_var = 'q';
                }
                parse_str($ref_parts['query'], $query);
                if(!empty($query[$q_var])){
                    $order['params']['keyword'] = $query[$q_var];
                }
            }
        }

        if(($utm = waRequest::cookie('utm'))){
            $utm = json_decode($utm, true);
            if($utm && is_array($utm)){
                foreach($utm as $k => $v){
                    $order['params']['utm_' . $k] = $v;
                }
            }
        }

        if(($landing = waRequest::cookie('landing')) && ($landing = @parse_url($landing))){
            if(!empty($landing['query'])){
                @parse_str($landing['query'], $arr);
                if(!empty($arr['gclid']) && !empty($order['params']['referer_host']) && strpos($order['params']['referer_host'], 'google') !== false){
                    $order['params']['referer_host'] .= ' (cpc)';
                    $order['params']['cpc'] = 1;
                }elseif(!empty($arr['_openstat']) && !empty($order['params']['referer_host']) && strpos($order['params']['referer_host'], 'yandex') !== false){
                    $order['params']['referer_host'] .= ' (cpc)';
                    $order['params']['openstat'] = $arr['_openstat'];
                    $order['params']['cpc'] = 1;
                }
            }

            $order['params']['landing'] = $landing['path'];
        }

        // A/B tests
        $abtest_variants_model = new shopAbtestVariantsModel();
        foreach(waRequest::cookie() as $k => $v){
            if(substr($k, 0, 5) == 'waabt'){
                $variant_id = $v;
                $abtest_id = substr($k, 5);
                if(wa_is_int($abtest_id) && wa_is_int($variant_id)){
                    $row = $abtest_variants_model->getById($variant_id);
                    if($row && $row['abtest_id'] == $abtest_id){
                        $order['params']['abt' . $abtest_id] = $variant_id;
                    }
                }
            }
        }

        $order['params']['ip'] = waRequest::getIp();
        $order['params']['user_agent'] = waRequest::getUserAgent();

        foreach(array('shipping', 'billing') as $ext){
            $address = $contact->getFirst('address');
            if($address){
                foreach($address['data'] as $k => $v){
                    $order['params'][$ext . '_address.' . $k] = $v;
                }
            }
        }

        list($stock_id, $virtualstock_id) = self::determineStockIds($order);
        if($virtualstock_id){
            $order['params']['virtualstock_id'] = $virtualstock_id;
        }
        if($stock_id){
            $order['params']['stock_id'] = $stock_id;
        }

        if($settings["comment_phrase"]){
            if(isset($additional['comment']) && !empty($additional['comment'])){
                $additional['comment'] = "\n\n" . $additional['comment'];
            }else{
                $additional['comment'] = "";
            }
            $additional['comment'] = $settings["comment_phrase"] . $additional['comment'];
        }

        if(isset($additional['comment'])){
            $order['comment'] = $additional['comment'];
        }

        $workflow = new shopWorkflow();
        if($order_id = $workflow->getActionById('create')->run($order)){
            return $order_id;
        }else{
            return false;
        }

    }

    protected static function determineStockIds($order){
        $stock_rules_model = new shopStockRulesModel();
        $rules = $stock_rules_model->getRules();
        $stocks = shopHelper::getStocks();

        /**
         * @event frontend_checkout_stock_rules
         *
         * Hook allows to implement custom rules to automatically select stock for new orders.
         *
         * $params['rules'] is a list of rules from `shop_stock_rules` table.
         * Plugins are expected to modify items in $params['rules'] by creating 'fulfilled' key (boolean)
         * for rule types plugin is responsible for.
         *
         * See also `backend_settings_stocks` event for how to set up settings form for such rules.
         *
         * @param array $params
         * @param array [array] $params['order'] order data
         * @param array [array] $params['rules'] list of rules to modify.
         * @param array [array] $params['stocks'] same as shopHelper::getStocks()
         * @return null
         */
        $event_params = array(
            'order' => $order,
            'stocks' => $stocks,
            'rules' => &$rules,
        );
        self::processBuiltInRules($event_params);
        wa('shop')->event('frontend_checkout_stock_rules', $event_params);

        $groups = $stock_rules_model->prepareRuleGroups($rules);
        foreach($groups as $g){
            if(($g['stock_id'] && empty($stocks[$g['stock_id']])) || ($g['virtualstock_id'] && empty($stocks['v' . $g['virtualstock_id']]))){
                continue;
            }

            $all_fulfilled = true;
            foreach($g['conditions'] as $rule){
                if(!ifset($rule['fulfilled'], false)){
                    $all_fulfilled = false;
                    break;
                }
            }
            if($all_fulfilled){
                return array($g['stock_id'], $g['virtualstock_id']);
            }
        }

        // No rule matched the order. Use stock specified in routing params.
        $virtualstock_id = null;
        $stock_id = waRequest::param('stock_id', null, 'string');
        if(empty($stocks[$stock_id])){
            $stock_id = null;
        }elseif(isset($stocks[$stock_id]['substocks'])){
            $virtualstock_id = $stocks[$stock_id]['id'];
            $stock_id = null;
        }
        return array($stock_id, $virtualstock_id);
    }

    protected static function processBuiltInRules(&$params){
        $shipping_type_id = null;
        if(!empty($params['order']['params']['shipping_id'])){
            $shipping_type_id = $params['order']['params']['shipping_id'];
        }
        $shipping_country = $shipping_region = null;
        if(!empty($params['order']['params']['shipping_address.country'])){
            $shipping_country = (string)$params['order']['params']['shipping_address.country'];
            if(!empty($params['order']['params']['shipping_address.region'])){
                $shipping_region = $shipping_country . ':' . $params['order']['params']['shipping_address.region'];
            }
        }

        foreach($params['rules'] as &$rule){
            if($rule['rule_type'] == 'by_shipping'){
                $rule['fulfilled'] = $shipping_type_id && $shipping_type_id == $rule['rule_data'];
            }elseif($rule['rule_type'] == 'by_region'){
                $rule['fulfilled'] = false;
                foreach(explode(',', $rule['rule_data']) as $candidate){
                    if($candidate === $shipping_country || $candidate === $shipping_region){
                        $rule['fulfilled'] = true;
                        break;
                    }
                }
            }
        }
        unset($rule);
    }

}