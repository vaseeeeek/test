<?php

class shopSkoneclickPluginFrontendSkoneclickRequestController extends waJsonController{

    protected $plugin_id = "skoneclick";

    public function execute(){

        $post = waRequest::post();

        if(!isset($post["check"]) || $post["check"] !== "skonclick_plugin"){
            $this->errors[] = "Ошибка проверки запроса";
            return false;
        }

        if(!isset($post["type"]) || empty($post["type"])){
            $this->errors[] = "Не задан тип запроса";
            return false;
        }

        if($post["type"] == "product"){
            return $this->processProduct($post);
        }else if($post["type"] == "cart"){
            return $this->processCart($post);
        }

        if(!empty($this->errors)){
            return false;
        }else{
            return true;
        }

    }

    protected function processProduct($post){

        $plugin_id = $this->plugin_id;

        if(!isset($post["cart"]) && isset($post["product_id"])){
            $post["cart"][0] = $post;
        }

        if(!isset($post["cart"]) || empty($post["type"])){
            $this->errors[] = "Не переданы товары для обработки";
            return false;
        }

        $cartObject = new shopSkoneclickCartProcess("product");

        if(isset($post["coupon_code"])){
            $data = wa()->getStorage()->get('shop/checkout', array());
            $data["coupon_code"] = $post["coupon_code"];
            wa()->getStorage()->set('shop/checkout', $data);
        }

        try{
            $cartObject->clearCart();
            foreach($post["cart"] as $item){
                $cartObject->addToCart($item);
            }
        }catch(waException $e){
            $this->errors[] = $e->getMessage();
            return false;
        }

        $productData = $cartObject->getCartData();

        if(isset($post["reload"]) && !empty($post["reload"])){
            $this->response = $productData;
            return true;
        }

        $definesModel = new shopSkoneclickDefinesModel();
        $definesData = $definesModel->getDefines();

        $fieldsData = shopSkoneclickData::getDataContactByForm();

        $formCustomer = shopSkoneclickContactsForm::loadConfig(
            $fieldsData,
            array(
                'namespace' => 'customer',
            )
        );

        $settings = wa("shop")->getPlugin($this->plugin_id)->getSettings();

        $view = wa()->getView();
        $view->assign("skoneclick_type", "product");
        $view->assign("skoneclick_defines", $definesData);
        $view->assign("skoneclick_settings", $settings);
        $view->assign("skoneclick_products", $productData);
        $view->assign("skoneclick_fields", $fieldsData);
        $view->assign("skoneclick_form", $formCustomer);
        $view->assign("shopSkOneclickPathJS", wa("shop")->getPlugin($plugin_id)->getPluginStaticUrl() . "js/");

        $path = wa()->getAppPath(null, "shop") . "/plugins/" . $plugin_id;
        $content = $view->fetch($path . '/templates/actions/frontend/Form.html');

        $this->response = array("content" => $content);

        return true;
    }

    protected function  processCart($post){

        $plugin_id = $this->plugin_id;

        $cartObject = new shopSkoneclickCartProcess("cart");

        if(isset($post["coupon_code"])){
            $data = wa()->getStorage()->get('shop/checkout', array());
            $data["coupon_code"] = $post["coupon_code"];
            wa()->getStorage()->set('shop/checkout', $data);
        }

        if(isset($post["cart"]) && !empty($post["cart"])){
            try{
                foreach($post["cart"] as $item){
                    $cartObject->addToCart($item);
                }
            }catch(waException $e){
                $this->errors[] = $e->getMessage();
                return false;
            }
        }

        $productData = $cartObject->getCartData();

        if(isset($post["reload"]) && !empty($post["reload"])){
            $this->response = $productData;
            return true;
        }

        $definesModel = new shopSkoneclickDefinesModel();
        $definesData = $definesModel->getDefines();

        $fieldsData = shopSkoneclickData::getDataContactByForm();

        $formCustomer = shopSkoneclickContactsForm::loadConfig(
            $fieldsData,
            array(
                'namespace' => 'customer',
            )
        );

        $settings = wa("shop")->getPlugin($this->plugin_id)->getSettings();

        $view = wa()->getView();
        $view->assign("skoneclick_type", "cart");
        $view->assign("skoneclick_defines", $definesData);
        $view->assign("skoneclick_settings", $settings);
        $view->assign("skoneclick_products", $productData);
        $view->assign("skoneclick_fields", $fieldsData);
        $view->assign("skoneclick_form", $formCustomer);
        $view->assign("shopSkOneclickPathJS", wa("shop")->getPlugin($plugin_id)->getPluginStaticUrl() . "js/");

        $path = wa()->getAppPath(null, "shop") . "/plugins/" . $plugin_id;
        $content = $view->fetch($path . '/templates/actions/frontend/Form.html');

        $this->response = array("content" => $content);

        return true;

    }

    protected function getProductFormRequest($params){

        $data = array();
        if(!isset($params["quantity"]) || !(int)$params["quantity"]){
            $params["quantity"] = 1;
        }

        if(!isset($params["sku_id"]) || !(int)$params["sku_id"]){
            $product = new shopProduct($params["product_id"]);
            if(isset($product->data["sku_id"])){
                $data = $product->data;
            }
        }

        if(!isset($data["sku_id"])){
            throw new Exception("Не найден идентификтор артикула");
        }

        $sku_model = new shopProductSkusModel();
        $data["sku_data"] = $sku_model->getById($data['sku_id']);
        $data["params"] = $params;

        return $data;

    }

}