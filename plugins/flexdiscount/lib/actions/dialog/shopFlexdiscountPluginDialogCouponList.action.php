<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDialogCouponListAction extends waViewAction
{

    private $filter = array();

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException();
        }
    }

    private function setPagination($coupons_count)
    {
        // Постраничная навигация
        if ($coupons_per_page = waRequest::post("coupons_per_page", 100)) {
            shopFlexdiscountApp::get('system')['wa']->getStorage()->set('coupons_per_page', $coupons_per_page);
        }
        $session_coupons_per_page = shopFlexdiscountApp::get('system')['wa']->getStorage()->get('coupons_per_page');
        $coupons_per_page = max(1, $session_coupons_per_page);
        $this->view->assign('coupons_per_page', $coupons_per_page);
        // Текущая страница в постраничном выводе
        $current_page = waRequest::request('page', 1, waRequest::TYPE_INT);
        // Если не задана, то равна 1
        $current_page = max(1, $current_page);
        // Всего страниц
        $max_page = ceil($coupons_count / $coupons_per_page);
        // Если текущая страница больше максимальной, то присваиваем текущей максимальной значение 
        if ($current_page > $max_page && $max_page > 0) {
            $current_page = $max_page;
        }
        $this->view->assign('current_page_num', $current_page);
        $pages_num = ceil($coupons_count / $coupons_per_page);
        $this->view->assign('total_pages_num', $pages_num);
        $this->filter['limit'] = array("offset" => ($current_page - 1) * $coupons_per_page, "length" => $coupons_per_page);
    }

    public function execute()
    {
        $fl_id = waRequest::get("id", 0, waRequest::TYPE_INT);

        $fl_model = new shopFlexdiscountPluginModel();
        $coupon_model = new shopFlexdiscountCouponPluginModel();
        $coupon_discount_model = new shopFlexdiscountCouponDiscountPluginModel();

        $where = $join = "";
        $show_all = waRequest::get('show_all', 0);

        // Поиск по коду 
        if ($search = waRequest::get('search')) {
            $q = $coupon_model->escape($search, 'like');
            $where .= " AND c.code LIKE '$q%'";
            $this->view->assign("search", $search);
        }

        // Отображать все купоны
        if ($fl_id) {
            if (!$show_all) {
                $where .= " AND cd.fl_id = '" . $fl_id . "'";
            }
            $join .= " LEFT JOIN {$coupon_discount_model->getTableName()} cd ON cd.coupon_id = c.id ";
        }
        if ($show_all) {
            $this->view->assign("show_all", 1);
        }

        // Количество купонов
        $sql = "SELECT COUNT(*) FROM {$coupon_model->getTableName()} c " . $join . " WHERE 1" . $where;
        $coupons_count = $coupon_model->query($sql)->fetchField();

        // Инициализируем постраничную навигацию
        $this->setPagination($coupons_count);

        // Сортировка
        $order_by = 'code ASC';
        if ($order = waRequest::get('order', 'code')) {
            $designator = waRequest::get('designator', 'ASC');
            if (!in_array(strtolower($designator), array("asc", "desc"))) {
                $designator = "ASC";
            }
            switch ($order) {
                case 'code':
                case 'used':
                case 'limit':
                case 'comment':
                case 'create_datetime':
                case 'type':
                    $order_by = 'c.`' . $order . '` ' . $designator;
                    break;
                case 'rules':
                    $join = " LEFT JOIN {$coupon_discount_model->getTableName()} cd ON cd.coupon_id = c.id";
                    $order_by = " COUNT(cd.fl_id) " . $designator;
                    break;
                default:
                    $order = 'code';
                    $order_by = 'c.`' . $order . '` ' . $designator;
            }
            $this->view->assign("order", $order);
            $this->view->assign("designator", strtolower($designator));
        }

        $sql = "SELECT c.* ";
        if ($fl_id && $show_all) {
            // Купоны, принадлежащие текущему правилу скидок
            $sql .= ", IF(cd.fl_id = '".$fl_id."', 1, 0) is_used ";
        }
        $sql .= "FROM {$coupon_model->getTableName()} c " . $join;

        $sql .= " WHERE 1" . $where;
        $sql .= " ORDER BY " . $order_by;
        $sql .= " LIMIT " . $this->filter['limit']['offset'] . "," . $this->filter['limit']['length'];
        $coupons = $coupon_model->query($sql)->fetchAll("id");
        if ($coupons) {
            $sql = "SELECT cd.coupon_id as id, fl.id as fl_id, fl.name as fl_name "
                    . "FROM {$coupon_discount_model->getTableName()} cd "
                    . "LEFT JOIN {$fl_model->getTableName()} fl ON fl.id = cd.fl_id "
                    . "WHERE cd.coupon_id IN ('" . implode("','", array_keys($coupons)) . "')";
            foreach ($coupon_model->query($sql) as $r) {
                if (!isset($coupons[$r['id']]['rules'])) {
                    $coupons[$r['id']]['rules'] = array();
                }
                $coupons[$r['id']]['rules'][$r['fl_id']] = array("id" => $r['fl_id'], "name" => $r['fl_name']);
            }
        }

        // Столбцы
        $settings = shopFlexdiscountApp::get('settings');
        $columns = isset($settings['coupon_columns']) ? unserialize($settings['coupon_columns']) : array("used" => 1, "limit" => 1, "type" => 1, "rules" => 1);

        $this->view->assign("f_id", $fl_id);
        $this->view->assign("coupons", $coupons);
        $this->view->assign("columns", $columns);
        $this->view->assign("count", $coupons_count);
        $this->view->assign('plugin_url', shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount')->getPluginStaticUrl());
    }

}
