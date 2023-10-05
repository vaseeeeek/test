<?php

class shopSkoneclickPluginFrontendSkoneclickSaveController extends waJsonController{

    protected $plugin_id = "skoneclick";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $post = waRequest::post();

        if(!isset($post["check"]) || $post["check"] !== "skonclick_plugin"){
            $this->errors[] = "Ошибка проверки запроса";
            return false;
        }

        if(!isset($post["type"]) || empty($post["type"])){
            $this->errors["console"] = "Не задан тип запроса";
            return false;
        }

        if($post["type"] == "product"){
            $this->processProduct($post);
        }else if($post["type"] == "cart"){
            $this->processCart($post);
        }

        if(!empty($this->errors)){
            return false;
        }

        $view = wa()->getView();
        $definesModel = new shopSkoneclickDefinesModel();
        $defines = $definesModel->getDefines();
        $view->assign("skoneclick_defines", $defines);
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";
        $content = $view->fetch($path . '/templates/actions/frontend/Success.html');

        $this->response = array("content" => $content);

    }

    protected function processProduct($post){

        $additional = array();
        if(isset($post["additional"])){
            $additional = $post["additional"];
        }

        $cartObject = new shopSkoneclickCartProcess("product");

        if(!isset($post["cart"]) || empty($post["type"])){
            $this->errors["console"] = "Не переданы товары для обработки";
            return false;
        }

        try{
            $cartObject->clearCart();
            foreach($post["cart"] as $item){
                $cartObject->addToCart($item);
            }
        }catch(waException $e){
            $this->errors["cart"] = $e->getMessage();
            return false;
        }

        if(!shopSkoneclickValidate::validateForm($post["customer"], $errors)){
            $this->errors["validate"] = $errors;
        }

        $settings = wa("shop")->getPlugin($this->plugin_id)->getSettings();

        if($settings["personal_data_active"] && (!isset($post["additional"]["agree"]) || empty($post["additional"]["agree"]))){
            $this->errors["form"][] = $settings["personal_data_error"];
        }

        if(!empty($this->errors)){
            return false;
        }

        $checkoutObject = new shopSkoneclickCheckoutProcess("product");
        $checkoutObject->createOrder($post["customer"], $additional, $errors);

        if(!empty($errors)){
            $this->errors["form"] = $errors;
            return false;
        }

    }

    protected function processCart($post){

        $additional = array();
        if(isset($post["additional"])){
            $additional = $post["additional"];
        }

        if(!shopSkoneclickValidate::validateForm($post["customer"], $errors)){
            $this->errors["validate"] = $errors;
        }

        $settings = wa("shop")->getPlugin($this->plugin_id)->getSettings();

        if($settings["personal_data_active"] && (!isset($post["additional"]["agree"]) || empty($post["additional"]["agree"]))){
            $this->errors["form"][] = $settings["personal_data_error"];
        }

        if(!empty($this->errors)){
            return false;
        }

        $checkoutObject = new shopSkoneclickCheckoutProcess("cart");
        $checkoutObject->createOrder($post["customer"], $additional, $errors);

        if(!empty($errors)){
            $this->errors["form"] = $errors;
            return false;
        }

        $cartObject = new shopSkoneclickCartProcess("cart");
        $cartObject->clearCart();

        return true;

    }

}