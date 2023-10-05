<?php

class shopSkoneclickCart extends shopCart{

    public function __construct($code = ''){
        $this->model = new shopCartItemsModel();
        $this->code = waRequest::cookie("shop_skoneclick", $code);
        if(!$this->code && wa()->getUser()->isAuth()){
            $code = $this->model->getLastCode(wa()->getUser()->getId());
            if($code){
                $this->code = $code;
                // set cookie
                wa()->getResponse()->setCookie("shop_skoneclick", $code, time() + 30 * 86400, null, '', false, true);
                $this->clearSessionData();
            }
        }
    }

    protected function getSessionData($key, $default = null){
        $data = wa()->getStorage()->get('shop/skonclick');
        return isset($data[$key]) ? $data[$key] : $default;
    }

    protected function setSessionData($key, $value){
        $data = wa()->getStorage()->get('shop/skonclick', array());
        $data[$key] = $value;
        wa()->getStorage()->set('shop/skonclick', $data);
    }

    public function clearSessionData(){
        wa()->getStorage()->remove('shop/skonclick');
    }

}