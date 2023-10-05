<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginFrontendFlexdiscountAction extends shopFrontendAction
{

    public function execute()
    {
        $settings = shopFlexdiscountApp::get('settings');

        // Заголовок страницы
        $title = (!empty($settings['flexdiscount_my_discounts']['page_name']) ? waString::escapeAll($settings['flexdiscount_my_discounts']['page_name']) : _wp('Your discounts'));
        $this->view->assign('title', $title);

        waRequest::setParam('plugin', 'flexdiscount');

        $html = '';
        // Список доступных скидок
        if (!empty($settings['my_discounts']) && !empty($settings['flexdiscount_my_discounts']['value'])) {
            $view = shopFlexdiscountApp::get('system')['wa']->getView();

            if (!empty($settings['flexdiscount_my_discounts']['show_only_active'])) {
                $workflow = shopFlexdiscountPluginHelper::getProductDiscounts(shopFlexdiscountData::getAbstractProduct(ifempty($settings, 'abstract_product', true)), null, 0, false);
                $discounts = $workflow['items'];
            } else {
                $discounts = shopFlexdiscountPluginHelper::getAvailableDiscounts(null, null, 0, array(), false);
            }

            $view->assign(array(
                'fl_discounts' => $discounts,
                'view_type' => !empty($settings['flexdiscount_my_discounts']['type']) ? $settings['flexdiscount_my_discounts']['type'] : ''
            ));
            $html .= $view->fetch('string:' . $settings['my_discounts']);
            $view->clearAssign(array('fl_discounts', 'view_type'));
        }

        $this->view->assign('plugin_content', $html);
        $this->view->assign('show_nav', !empty($settings['flexdiscount_my_discounts']['show_nav']));
        $this->view->assign('show_nav_above', !empty($settings['flexdiscount_my_discounts']['show_nav_pos']));

        // Выводим страницу со скидками через файл текущей темы my.flexdiscount.html
        $view = shopFlexdiscountApp::get('system')['wa']->getView();
        $theme = new waTheme(waRequest::getTheme());
        $theme_path = $theme->getPath();
        $f = 'my.flexdiscount.html';
        $template_path = $theme_path . '/' . $f;
        if (file_exists($template_path)) {
            $view->setThemeTemplate($theme, $f);
            $this->setTemplate($template_path);
        }

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new shopFrontendLayout());
            $this->getResponse()->setTitle($title);
            $this->view->assign('breadcrumbs', $this->getBreadcrumbs($title));
            $this->layout->assign('nofollow', true);
        }
    }

    private function getBreadcrumbs($title)
    {
        return array(
            array(
                'name' => $title,
                'url' => shopFlexdiscountApp::get('system')['wa']->getRouteUrl('/frontend/my') . 'flexdiscount/',
            ),
        );
    }

}
