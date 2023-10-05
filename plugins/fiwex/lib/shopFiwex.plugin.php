<?php
class shopFiwexPlugin extends shopPlugin
{
    public function frontendCategory()
    {
        $app_settings = new waAppSettingsModel();
        $enable = $app_settings->get(wa()->getApp('shop').'.fiwex','enable');
        if ((int)$enable == 1) {
            $shop_path_for_fiwex = wa()->getRouteUrl('shop/frontend');
            $view = wa()->getView();
            $view->assign('shop_path_for_fiwex_dump', $shop_path_for_fiwex.'fiwex_dump/');
            $view->assign('shop_path_for_fiwex_expflag', $shop_path_for_fiwex.'fiwex_expflag/');
   
            //Закидываем стиль для пояснений
            $style = '<style type="text/css" rel="stylesheet">';
            $query_style = $app_settings->get(wa()->getApp('shop').'.fiwex','style');

            if ($query_style) {
                $style.=$query_style;
            } else {
                $path = wa()->getAppPath('plugins/fiwex/CSS/','shop');
	            $query_style = file_get_contents($path.'style.css');
	            $url = wa()->getAppStaticUrl('shop', true);
	            $query_style = str_replace('{$path}', $url, $query_style);
	            $style.=$query_style;
            }
            $style.='</style>';
            $view->assign('style', $style);

            $content = $view->fetch($this->path.'/templates/fiwexFrontendCategory.html');
            return $content;
        }
    }

    public function frontendProduct()
    {
        $app_settings = new waAppSettingsModel();
        $enable = $app_settings->get(wa()->getApp('shop') . '.fiwex', 'enable');
        if ((int)$enable == 1) {
            $shop_path_for_fiwex = wa()->getRouteUrl('shop/frontend');
            $view = wa()->getView();
            $view->assign('shop_path_for_fiwex_dump', $shop_path_for_fiwex . 'fiwex_dump/');
            $view->assign('shop_path_for_fiwex_expflag', $shop_path_for_fiwex . 'fiwex_expflag/');

            //Закидываем стиль для пояснений
            $style = '<style type="text/css" rel="stylesheet">';
            $query_style = $app_settings->get(wa()->getApp('shop') . '.fiwex', 'style');

            if ($query_style) {
                $style .= $query_style;
            } else {
                $path = wa()->getAppPath('plugins/fiwex/CSS/', 'shop');
                $query_style = file_get_contents($path . 'style.css');
                $url = wa()->getAppStaticUrl('shop', true);
                $query_style = str_replace('{$path}', $url, $query_style);
                $style .= $query_style;
            }
            $style .= '</style>';
            $view->assign('style', $style);

            $content = $view->fetch($this->path.'/templates/fiwexFrontendProduct.html');

            return array('block' => $content);
        }
    }

    public function frontendFooter() {
        $content = '';

        if (strpos(waRequest::server('REQUEST_URI'), '/compare') !== false) {
            $app_settings = new waAppSettingsModel();
            $enable = $app_settings->get(wa()->getApp('shop') . '.fiwex', 'enable');

            if ((int)$enable == 1) {
                $shop_path_for_fiwex = wa()->getRouteUrl('shop/frontend');
                $view = wa()->getView();
                $view->assign('shop_path_for_fiwex_dump', $shop_path_for_fiwex . 'fiwex_dump/');
                $view->assign('shop_path_for_fiwex_expflag', $shop_path_for_fiwex . 'fiwex_expflag/');

                //Закидываем стиль для пояснений
                $style = '<style type="text/css" rel="stylesheet">';
                $query_style = $app_settings->get(wa()->getApp('shop') . '.fiwex', 'style');

                if ($query_style) {
                    $style .= $query_style;
                } else {
                    $path = wa()->getAppPath('plugins/fiwex/CSS/', 'shop');
                    $query_style = file_get_contents($path . 'style.css');
                    $url = wa()->getAppStaticUrl('shop', true);
                    $query_style = str_replace('{$path}', $url, $query_style);
                    $style .= $query_style;
                }
                $style .= '</style>';
                $view->assign('style', $style);

                $content = $view->fetch($this->path.'/templates/fiwexFrontendCompare.html');
            }
        }

        return $content;
    }
}