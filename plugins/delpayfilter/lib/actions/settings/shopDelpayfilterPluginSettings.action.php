<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPluginSettingsAction extends waViewAction
{

    private $filter = array();

    private function setPagination($total_count)
    {
        // Постраничная навигация
        if ($per_page = waRequest::post("delpayfilter_per_page", 100)) {
            wa()->getStorage()->set('delpayfilter_per_page', $per_page);
        }
        $session_per_page = wa()->getStorage()->get('delpayfilter_per_page');
        $per_page = max(1, $session_per_page);
        $this->view->assign('delpayfilter_per_page', $per_page);
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
        if (!$first_load) {
            $model = new shopDelpayfilterPluginModel();

            // Общее количество
            $total_count = $model->countAll();
            $this->view->assign('total_count', $total_count);

            // Инициализируем постраничную навигацию
            $this->setPagination($total_count);

            $plugins = array();
            $data_class = new shopDelpayfilterData();

            // Плагины доставки
            $plugins['shipping'] = $data_class->getShippingData();

            // Плагины оплаты
            $plugins['payment'] = $data_class->getPaymentData();

            $filters = $model->getFilters($this->filter);
            // Добавляем информацию о скрытых методах доставки и оплаты
            foreach ($filters as &$f) {
                if ($f['target']) {
                    $f['payment'] = $f['shipping'] = array();
                    $targets = shopDelpayfilterConditions::decode($f['target']);
                    foreach ($targets as $t) {
                        if (!empty($t['condition']['value']) && !empty($plugins[$t['target']][$t['condition']['value']])) {
                            $f[$t['target']][$t['condition']['value']] = shopDelpayfilterHelper::secureString($plugins[$t['target']][$t['condition']['value']]['name']);
                        }
                    }
                }
            }

            $this->view->assign('filters', $filters);
        }
        $this->view->assign('first', $first_load);
        $this->view->assign('ver', $this->unique_str($this->getDomain()));
        $this->view->assign('plugin_url', wa()->getPlugin('delpayfilter')->getPluginStaticUrl());
    }

    private function unique_str($a)
    {
        $b = 'delpayfilter';
        $c = mb_strlen($a, 'UTF-8');
        $d = strlen($b);
        for ($i = 0; $i < $c; $i++) {
            for ($j = 0; $j < $d; $j++) {
                $a[$i] = $a[$i] ^ $b[$j];
            }
        }
        return base64_encode($a);
    }

    private function getDomain()
    {
        $domain = $this->getConfig()->getDomain();
        if (strpos($domain, ":") !== false) {
            $domain = substr($domain, 0, strpos($domain, ":"));
        }
        if (strpos($domain, "/index.php") !== false) {
            $domain = substr($domain, 0, strpos($domain, "/index.php"));
        }

        return $domain;
    }

}
