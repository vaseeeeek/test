<?php

class shopSkoneclickPluginBackendFieldAddController extends waJsonController{

    protected $plugin_id = "skoneclick";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $control_id = waRequest::post("control_id");

        if(!$control_id){
            $this->errors[] = "Ошибка при передаче типа поля";
            return false;
        }


        $checkout_controls = shopSkoneclickData::getCheckoutControls();
        if(!isset($checkout_controls[$control_id])){
            $this->errors[] = "Данного поля не обнаружено";
            return false;
        }

        $control = $checkout_controls[$control_id];
        $control = array(
            "control_id" => $control_id,
            "title" => $control["localized_names"],
            "class" => $control["class"],
            "is_mask" => $control["is_mask"],
            "mask" => $control["is_mask"] ? "+7(###)###-##-##" : "",
            "require" => $control["class"] == "waContactPhoneField" ? 1 : 0
        );

        $view = wa()->getView();
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

        $view->assign("control", $control);

        $content = $view->fetch($path . '/templates/actions/settings/SettingsField.html');

        $this->response = array("content" => $content);

        return true;

    }

}