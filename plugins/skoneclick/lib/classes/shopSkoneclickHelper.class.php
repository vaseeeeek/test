<?php


class shopSkoneclickHelper{

    const plugin_id = "skoneclick";

    public static function isActive(){

        $active = wa("shop")->getPlugin(self::plugin_id)->getSettings("active");

        if($active){
            return true;
        }else{
            return false;
        }

    }

    public static function getScript(){

        $settings = wa("shop")->getPlugin(self::plugin_id)->getSettings();

        $definesModel = new shopSkoneclickDefinesModel();
        $definesData = $definesModel->getDefines();

        $paramsInit = array(
            "urlSave" => wa()->getRouteUrl("shop/frontend/skoneclickSave/"),
            "urlRequest" => wa()->getRouteUrl("shop/frontend/skoneclickRequest/"),
            "yandexId" => $definesData["yandex_number"],
            "yandexOpen" => $definesData["yandex_open"],
            "yandexSend" => $definesData["yandex_send"],
            "yandexError" => $definesData["yandex_error"],
            "googleOpenCategory" => $definesData["goggle_open_category"],
            "googleOpenAction" => $definesData["goggle_open_action"],
            "googleSendCategory" => $definesData["goggle_send_category"],
            "googleSendAction" => $definesData["goggle_send_action"],
            "googleErrorCategory" => $definesData["goggle_error_category"],
            "googleErrorAction" => $definesData["goggle_error_action"],
        );

        $view = wa()->getView();

        $view->assign("skoneclick_init", $paramsInit);
        $view->assign("skoneclick_defines", $settings);
        $view->assign("shopSkOneclickPathJS", wa("shop")->getPlugin(self::plugin_id)->getPluginStaticUrl() . "js/");

        $path = wa()->getAppPath(null, "shop") . "/plugins/" . self::plugin_id;
        $content = $view->fetch($path . '/templates/actions/frontend/Script.html');
        return $content;
    }

}