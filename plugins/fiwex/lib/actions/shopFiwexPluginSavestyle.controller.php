<?php
class shopFiwexPluginSavestyleController extends waJsonController
{
    function execute()
    {
        $content = waRequest::post('content');
        $clear = waRequest::post('clear', 0, waRequest::TYPE_INT);
        $app_settings = new waAppSettingsModel();

        if (empty($clear)) {
            $this->response['state'] = $app_settings->set(wa()->getApp('shop').'.fiwex','style',$content);
        } else if(!empty($clear)) {
            $app_settings->del('shop.fiwex', 'style');
            $path = wa()->getAppPath('plugins/fiwex/CSS/','shop');
            $url = wa()->getAppStaticUrl('shop', true);
            $style = file_get_contents($path.'style.css');
            $style = str_replace('{$path}', $url, $style);

            $this->response['style'] = $style;
        }
  
    }
}