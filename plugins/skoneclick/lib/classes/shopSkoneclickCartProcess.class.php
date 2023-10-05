<?php

class shopSkoneclickCartProcess{

    /* Прототип shopFrontendCartAddController */

    protected $plugin_id = "skoneclick";

    public $cookie = 'shop_skoneclick';

    protected $cart;

    protected $cart_model;

    protected $is_html = true;

    protected $code;

    public function __construct($type = "product"){

        if($type !== "product"){
            $this->cookie = "shop_cart";
        }

        $code = waRequest::cookie($this->cookie);

        if(!$code){
            $code = md5(uniqid(time(), true));
            // header for IE
            wa()->getResponse()->addHeader('P3P', 'CP="NOI ADM DEV COM NAV OUR STP"');
            // set cart cookie
            wa()->getResponse()->setCookie($this->cookie, $code, time() + 30 * 86400, null, '', false, true);
        }

        $this->code = $code;
        if($type == "product"){
            $this->cart = new shopSkoneclickCart($code);
        }else{
            $this->cart = new shopCart();
        }

        $this->cart_model = new shopCartItemsModel();

    }

    public function addToCart($data){

        $code = $this->code;

        // add service
        if(isset($data['parent_id'])){
            $response = $this->addService($data);
            return $response;
        }

        // add sku
        $sku_model = new shopProductSkusModel();
        $product_model = new shopProductModel();
        if(isset($data['sku_id'])){
            $sku = $sku_model->getById($data['sku_id']);
            $product = $product_model->getById($sku['product_id']);
        }else if(isset($data['product_id'])){
            $product = $product_model->getById($data['product_id']);
            if(isset($data['sku_id'])){
                $sku = $sku_model->getById($data['sku_id']);
            }else{
                if(isset($data['features'])){
                    $product_features_model = new shopProductFeaturesModel();
                    $sku_id = $product_features_model->getSkuByFeatures($product['id'], $data['features']);
                    if($sku_id){
                        $sku = $sku_model->getById($sku_id);
                    }else{
                        $sku = null;
                    }
                }else{
                    $sku = $sku_model->getById($product['sku_id']);
                    if(!$sku['available']){
                        $sku = $sku_model->getByField(array('product_id' => $product['id'], 'available' => 1));
                    }

                    if(!$sku){
                        throw new waException(_w('This product is not available for purchase'));
                    }
                }
            }
        }

        $quantity = ifempty($data["quantity"], 1);

        if($quantity < 0){
            $quantity = 1;
        }
        if(!empty($product) && !empty($sku)){
            // check quantity
            if(!wa()->getSetting('ignore_stock_count')){

                // limit by main stock
                if(wa()->getSetting('limit_main_stock') && waRequest::param('stock_id')){
                    $stock_id = waRequest::param('stock_id');
                    $product_stocks_model = new shopProductStocksModel();
                    $sku_stock = shopHelper::fillVirtulStock($product_stocks_model->getCounts($sku['id']));
                    if(isset($sku_stock[$stock_id])){
                        $sku['count'] = $sku_stock[$stock_id];
                    }
                }

                $c = $this->cart_model->countSku($code, $sku['id']);
                if($sku['count'] !== null && $c + $quantity > $sku['count']){
                    $quantity = $sku['count'] - $c;
                    $name = $product['name'] . ($sku['name'] ? ' (' . $sku['name'] . ')' : '');
                    if(!$quantity){
                        if($sku['count'] > 0){
                            throw new waException(sprintf(_w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.'), $sku['count'], $name));
                        }else{
                            throw new waException(sprintf(_w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience.'), $name));
                        }
                    }else{
                        throw new waException(sprintf(_w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.'), $sku['count'], $name));
                    }
                }
            }

            $services = ifempty($data['services'], array());
            if($services){
                $variants = $data['service_variant'];
                $temp = array();
                $service_ids = array();
                foreach($services as $service_id){
                    if(isset($variants[$service_id])){
                        $temp[$service_id] = $variants[$service_id];
                    }else{
                        $service_ids[] = $service_id;
                    }
                }
                if($service_ids){
                    $service_model = new shopServiceModel();
                    $temp_services = $service_model->getById($service_ids);
                    foreach($temp_services as $row){
                        $temp[$row['id']] = $row['variant_id'];
                    }
                }
                $services = $temp;
            }
            $item_id = null;
            $item = $this->cart_model->getItemByProductAndServices($code, $product['id'], $sku['id'], $services);

            if($item){
                $item_id = $item['id'];
                $this->cart->setQuantity($item_id, $quantity);
            }
            if(!$item_id){
                $data = array(
                    'create_datetime' => date('Y-m-d H:i:s'),
                    'product_id' => $product['id'],
                    'sku_id' => $sku['id'],
                    'quantity' => $quantity,
                    'type' => 'product'
                );
                if($services){
                    $data_services = array();
                    foreach($services as $service_id => $variant_id){
                        $data_services[] = array(
                            'service_id' => $service_id,
                            'service_variant_id' => $variant_id,
                        );
                    }
                }else{
                    $data_services = array();
                }
                $this->cart->addItem($data, $data_services);
            }

            return true;

        }else{
            throw new waException('product not found');
        }
    }


    public function clearCart(){

        $this->cart->clear();

    }

    public function getCartData(){

        $data = wa()->getStorage()->get('shop/checkout', array());

        wa()->getStorage()->set('shop/currency', wa('shop')->getConfig()->getCurrency(false));

        $discount = $this->cart->discount($order);

        $response['coupon_code'] = "";
        if(isset($data['coupon_code'])){
            $response['coupon_code'] = $data['coupon_code'];
        }
        $response['total'] = $this->currencyFormat($this->cart->total());
        $response['discount'] = $this->currencyFormat($discount);
        $response['discount_numeric'] = $discount;
        $response['discount_coupon'] = $this->currencyFormat(ifset($order['params']['coupon_discount'], 0), true);
        $response['count'] = $this->cart->count();
        $response['items'] = $this->getCartItems();

        return $response;
    }

    protected function getCartItems(){

        $rows = $this->cart->items();
        $items = array();
        foreach($rows as $row){
            $item = array();
            foreach(array('id', 'product_id', 'name', 'quantity', 'sku_id', 'sku_code', 'sku_name') as $key){
                $item[$key] = $row[$key];
            }
            $p = $row['product'];
            $item['product_name'] = $p['name'];
            if(ifset($p, 'image_id', null)){
                $item['image_url'] = shopImage::getUrl(array(
                    'product_id' => $row['product_id'],
                    'filename' => $p['image_filename'],
                    'id' => $p['image_id'],
                    'ext' => $p['ext']
                ), "96x96");
            }else{
                $item['image_url'] = null;
            }
            $item['frontend_url'] = wa()->getRouteUrl('shop/frontend/product', array(
                'product_url' => $p['url'],
                'category_url' => ifset($p['category_url'], '')
            ));
            $item['price'] = $this->currencyFormat($row['price'], $row['currency']);
            $price = shop_currency($row['price'] * $row['quantity'], $row['currency'], null, false);
            $item['services'] = array();
            if(!empty($row['services'])){
                foreach($row['services'] as $s){
                    $item_s = array();
                    foreach(array(
                        'id',
                        'parent_id',
                        'name',
                        'quantity',
                        'service_id',
                        'service_name',
                        'service_variant_id',
                        'variant_name'
                    ) as $key){
                        if(isset($s[$key])){
                            $item_s[$key] = $s[$key];
                        }
                    }
                    $item_s['price'] = $this->currencyFormat($s['price'], $s['currency']);
                    $price += shop_currency($s['price'] * $s['quantity'], $s['currency'], null, false);
                    $item['services'][] = $item_s;
                }
            }
            $item['full_price'] = $this->currencyFormat($price, true);
            $items[] = $item;
        }
        return $items;
    }


    protected function currencyFormat($val, $currency = true){
        return $this->is_html ? shop_currency_html($val, $currency) : shop_currency($val, $currency);
    }


    protected function addService($data){

        $response = array();

        $item = $this->cart_model->getById($data['parent_id']);
        if(!$item){
            throw new waException(_w('Error'));
        }
        unset($item['id']);
        $item['parent_id'] = $data['parent_id'];
        $item['type'] = 'service';
        $item['service_id'] = $data['service_id'];
        if(isset($data['service_variant_id'])){
            $item['service_variant_id'] = $data['service_variant_id'];
        }else{
            $service_model = new shopServiceModel();
            $service = $service_model->getById($data['service_id']);
            $item['service_variant_id'] = $service['variant_id'];
        }

        if($row = $this->cart_model->getByField(array(
            'parent_id' => $data['parent_id'],
            'service_variant_id' => $item['service_variant_id']
        ))
        ){
            $id = $row['id'];
        }else{
            $id = $this->cart->addItem($item);
        }
        $total = $this->cart->total();
        $discount = $this->cart->discount($order);
        if(!empty($order['params']['affiliate_bonus'])){
            $discount -= shop_currency(shopAffiliate::convertBonus($order['params']['affiliate_bonus']), $this->getConfig()->getCurrency(true), null, false);
        }

        $response['id'] = $id;
        $response['total'] = $this->currencyFormat($total);
        $response['count'] = $this->cart->count();
        $response['discount'] = $this->currencyFormat($discount);
        $response['discount_numeric'] = $discount;
        $response['discount_coupon'] = $this->currencyFormat(ifset($order['params']['coupon_discount'], 0), true);

        $item_total = $this->cart->getItemTotal($data['parent_id']);
        $response['item_total'] = $this->currencyFormat($item_total);

        if(shopAffiliate::isEnabled()){
            $add_affiliate_bonus = shopAffiliate::calculateBonus(array(
                'total' => $total,
                'discount' => $discount,
                'items' => $this->cart->items(false)
            ));
            $response['add_affiliate_bonus'] = sprintf(_w("This order will add <strong>+%s bonuses</strong> to  your account, which you will be able to spend on getting additional discounts later."), round($add_affiliate_bonus, 2));
            $affiliate_bonus = $affiliate_discount = 0;
            if($this->getUser()->isAuth()){
                $customer_model = new shopCustomerModel();
                $customer = $customer_model->getById($this->getUser()->getId());
                $affiliate_bonus = $customer ? round($customer['affiliate_bonus'], 2) : 0;
            }
            $affiliate_discount = shopFrontendCartAction::getAffiliateDiscount($affiliate_bonus, $order);
            $response['affiliate_discount'] = $this->is_html ? shop_currency_html($affiliate_discount, true) : shop_currency($affiliate_discount, true);
        }

        return $response;
    }

}