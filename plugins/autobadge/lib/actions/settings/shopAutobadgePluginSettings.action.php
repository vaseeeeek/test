<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginSettingsAction extends waViewAction
{

    private $filter = array();

    private function setPagination($total_count)
    {
        // Постраничная навигация
        if ($per_page = waRequest::post("autobadge_per_page", 100)) {
            wa('shop')->getStorage()->set('autobadge_per_page', $per_page);
        }
        $session_per_page = wa('shop')->getStorage()->get('autobadge_per_page');
        $per_page = max(1, $session_per_page);
        $this->view->assign('autobadge_per_page', $per_page);
        // Текущая страница в постраничном выводе
        $current_page = waRequest::request('page', 1, waRequest::TYPE_INT);
        // Если не задана, то равна 1
        $current_page = max(1, $current_page);
        // Всего страниц
        $max_page = ceil($total_count / $per_page);
        // Если текущая страница больше максимальной, то присваиваем текущей максимальной значение 
        if ($current_page > $max_page && $max_page > 0) {
            $current_page = $max_page;
        }
        $this->view->assign('current_page_num', $current_page);
        $pages_num = ceil($total_count / $per_page);
        $this->view->assign('total_pages_num', $pages_num);
        $this->filter['limit'] = array("offset" => ($current_page - 1) * $per_page, "length" => $per_page);
    }

    public function execute()
    {
        $first_load = waRequest::get("first", 1, waRequest::TYPE_INT);
        
        // Вывод напоминающего окна с необходимостью произвести настройку шаблона
        $app_settings = new waAppSettingsModel();
        $first_time_open = !$app_settings->get('shop.autobadge', 'first_load');
        if ($first_time_open) {
            $app_settings->set('shop.autobadge', 'first_load', 1);
        }
        
        if (!$first_load) {
            $model = new shopAutobadgePluginModel();

            // Общее количество
            $total_count = $model->countAll();
            $this->view->assign('total_count', $total_count);

            // Инициализируем постраничную навигацию
            $this->setPagination($total_count);

            $filters = $model->getFilters($this->filter);
            $this->view->assign('filters', $filters);
        }
        $this->view->assign('first', $first_load);
        $this->view->assign('alert_popup', $first_time_open);
        $this->view->assign('js_locale_strings', (new shopAutobadgeHelper())->getJsLocaleStrings());
        $this->view->assign('plugin_url', wa('shop')->getPlugin('autobadge')->getPluginStaticUrl());
        
    }

}
