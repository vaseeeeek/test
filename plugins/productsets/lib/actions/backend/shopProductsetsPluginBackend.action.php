<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginBackendAction extends waViewAction
{

    private $filter = array();

    private function setPagination($count)
    {
        $session_items_per_page = wa()->getStorage()->get('productsets_items_per_page');
        $items_per_page = $session_items_per_page ? (int) $session_items_per_page : 50;
        $this->view->assign('items_per_page', $items_per_page);
        // Текущая страница в постраничном выводе
        $current_page = waRequest::get('page', 1, waRequest::TYPE_INT);
        // Если не задана, то равна 1
        $current_page = max(1, $current_page);
        // Всего страниц
        $max_page = ceil($count / $items_per_page);
        // Если текущая страница больше максимальной, то присваиваем текущей максимальной значение 
        if ($current_page > $max_page && $max_page > 0) {
            $current_page = $max_page;
        }
        $this->view->assign('current_page_num', $current_page);
        $pages_num = ceil($count / $items_per_page);
        $this->view->assign('total_pages_num', $pages_num);
        $this->filter['limit'] = array("offset" => ($current_page - 1) * $items_per_page, "length" => $items_per_page);
    }

    public function execute()
    {
        $psm = new shopProductsetsPluginModel();
        // Постраничная навигация
        $count = $psm->countAll();
        $this->setPagination($count);

        $sets = $psm->getSets($this->filter);

        $this->view->assign('sets', $sets);
        $this->view->assign('count', $count);

        $this->setLayout(new shopProductsetsPluginBackendLayout());
    }

}
