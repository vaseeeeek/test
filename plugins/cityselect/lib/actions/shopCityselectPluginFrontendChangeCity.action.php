<?php
/**
 * Окно смены города
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopCityselectPluginFrontendChangeCityAction extends waViewAction
{
    public function execute()
    {
        $this->view->assign('location', shopCityselectHelper::getLocation());
        $this->view->assign('current_theme', waRequest::getTheme());

        $settings = shopCityselectPlugin::loadRouteSettings();

        $this->view->assign('settings', $settings);

        //Выбор шаблона
        $template_file = '';

        //Пользователь указал свои шаблоны
        if ($settings['user_template']) {

            if (!empty($settings['template_change'])) {
                $template_file = wa()->getDataPath($settings['template_change'], false, 'shop');
            }

            //Если какая ошибка, используем системный шаблон
            if ((empty($settings['template_change'])) || (!is_readable($template_file))) {
                $template_file = wa()->getAppPath('plugins/cityselect/templates/actions/frontend/template_change.html', 'shop');
            }

        } else {
            $template_file = wa()->getAppPath('plugins/cityselect/templates/actions/frontend/template_change.html', 'shop');
        }

        $this->template = $template_file;
    }

}