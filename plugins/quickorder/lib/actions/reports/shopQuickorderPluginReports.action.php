<?php

class shopQuickorderPluginReportsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('quickorder');

        list($start_date, $end_date, $group_by, $request_options) = shopReportsSalesAction::getTimeframeParams();
        $sales_channel = waRequest::request('sales_channel', null, waRequest::TYPE_STRING);

        // SS6
        $storefront = waRequest::request('storefront', null, waRequest::TYPE_STRING);

        $order_by = waRequest::request('sort', 'profit', waRequest::TYPE_STRING);
        $page = waRequest::request('page', 'products', waRequest::TYPE_STRING);
        $type = waRequest::request('qsource', 'product', waRequest::TYPE_STRING);
        $limit = waRequest::request('limit', '100', waRequest::TYPE_INT);

        $request_options['limit'] = $limit;

        $model_options = array();

        // Канал продаж
        if ($sales_channel) {
            $request_options['sales_channel'] = $sales_channel;
            $model_options['sales_channel'] = $sales_channel;
        }
        // SS6
        if ($storefront) {
            $request_options['sales_channel'] = $storefront;
            $model_options['storefront'] = $storefront;
        }

        // Сортировка
        if ($order_by !== 'sales') {
            $order_by = 'profit';
        }
        if ($order_by) {
            $request_options['sort'] = $order_by;
        }

        // Страница
        if ($page !== 'products') {
            $page = 'services';
        }
        if ($page) {
            $request_options['page'] = $page;
        }

        // Тип
        if ($type !== 'cart') {
            $type = 'product';
        }
        if ($type) {
            $request_options['type'] = $type;
            $model_options['type'] = $type;
        }

        // Top products
        $max_sales = 0;
        $max_profit = 0;
        $product_total_sales = 0;
        $product_total_profit = 0;

        // Товары
        if ($page == 'products') {
            $products = $this->getTopProducts($limit, $order_by, $start_date, $end_date, $model_options)->fetchAll('id');
            foreach ($products as &$p) {
                $max_sales = max($p['sales'], $max_sales);
                $max_profit = max($p['profit'], $max_profit);
                $product_total_profit += $p['profit'];
                $product_total_sales += $p['sales'];
                $p['profit_percent'] = 0;
                $p['sales_percent'] = 0;
            }

            if ($max_sales > 0 || $max_profit > 0) {
                $val = 100 / max($max_profit, $max_sales);
                foreach ($products as &$p) {
                    $p['profit_percent'] = round($p['profit'] * $val);
                    $p['sales_percent'] = round($p['sales'] * $val);
                }
            }
            unset($p);

            $this->view->assign(array(
                'products' => $products,
                'product_total_sales' => $product_total_sales,
                'product_total_profit' => $product_total_profit,
            ));
            $this->view->assign('product_total_sales', $products);
        }

        // Услуги
        if ($page == 'services') {
            // Top services
            $services = $this->getTopServices($limit, $start_date, $end_date, $model_options)->fetchAll('id');
            $max_val = 0;
            $service_total_val = 0;
            foreach ($services as $s) {
                $max_val = max($s['total'], $max_val);
                $service_total_val += $s['total'];
            }
            foreach ($services as &$s) {
                $s['total_percent'] = round($s['total'] * 100 / ifempty($max_val, 1));
            }
            unset($s);

            $service_total_percent = 0;
            $total = 0;
            foreach ($this->getSales($start_date, $end_date, $model_options) as $row) {
                $total += $row['sales'];
            }
            $total += $service_total_val;
            if ($total) {
                $service_total_percent = round($service_total_val * 100 / $total, 1);
                unset($row);
            }

            $this->view->assign(array(
                'services' => $services,
                'service_total_percent' => $service_total_percent,
                'service_total_val' => $service_total_val,
            ));
        }

        $migrate = !method_exists('shopReportsSalesAction', 'getSalesChannels');
        $this->view->assign(array(
            'def_cur' => wa()->getConfig()->getCurrency(),
            'sales_channels' => !$migrate ? shopReportsSalesAction::getSalesChannels() : shopReportsSalesAction::getStorefronts(),
            'request_options' => $request_options,
            'migrate' => $migrate,
            'plugin_url' => $plugin->getPluginStaticUrl(),
            'version' => $plugin->getVersion(),
        ));
    }

    public function getTopProducts($limit, $order = 'sales', $start_date = null, $end_date = null, $options = array())
    {
        $model = new waModel();

        $paid_date_sql = shopOrderModel::getDateSql('o.paid_date', $start_date, $end_date);

        if ($order !== 'sales') {
            $order = 'profit';
        }
        $limit = (int) $limit;
        $limit = ifempty($limit, 10);

        $storefront_where = '';
        $storefront_join = " JOIN shop_order_params AS opst2
                                ON opst2.order_id=o.id AND opst2.name='quickorder_" . $options['type'] . "'";
        if (!empty($options['sales_channel'])) {
            $storefront_where .= " AND opst2.value='" . $model->escape($options['sales_channel']) . "' ";
        }
        // SS6
        if (!empty($options['storefront'])) {
            $storefront_where .= " AND opst2.value='storefront:" . $model->escape($options['storefront']) . "' ";
        }

        $sales_subtotal = '(oi.price*o.rate*oi.quantity)';
        $order_subtotal = '(o.total+o.discount-o.tax-o.shipping)';
        $discount = "IF({$order_subtotal} <= 0, 0, oi.price*o.rate*oi.quantity*o.discount / {$order_subtotal})";
        $purchase = '(IF(oi.purchase_price > 0, oi.purchase_price*o.rate, ps.purchase_price*pcur.rate)*oi.quantity)';

        // !!! With 15k orders this query takes ~3 seconds
        $sql = "SELECT
                    p.*,
                    IF(ps.name<>'', CONCAT(p.name, ' (', ps.name, ')'), p.name) AS `name`,
                    SUM({$sales_subtotal} - {$discount}) AS sales,
                    SUM({$sales_subtotal} - {$discount} - {$purchase}) AS profit,
                    SUM({$sales_subtotal}) AS sales_subtotal,
                    SUM({$discount}) AS discount,
                    SUM({$purchase}) AS purchase,
                    SUM(oi.quantity) AS quantity
                FROM shop_order AS o
                    JOIN shop_order_items AS oi
                        ON oi.order_id=o.id
                    JOIN shop_product AS p
                        ON oi.product_id=p.id
                    JOIN shop_product_skus AS ps
                        ON oi.sku_id=ps.id
                    JOIN shop_currency AS pcur
                        ON pcur.code=p.currency
                    {$storefront_join}
                WHERE $paid_date_sql
                    AND oi.type = 'product'
                    {$storefront_where}
                GROUP BY p.id
                ORDER BY $order DESC
                LIMIT $limit";
        return $model->query($sql);
    }

    public function getTopServices($limit, $start_date = null, $end_date = null, $options = array())
    {
        $model = new waModel();

        $paid_date_sql = array();
        if ($start_date) {
            $paid_date_sql[] = "o.paid_date >= DATE('" . $model->escape($start_date) . "')";
        }
        if ($end_date) {
            $paid_date_sql[] = "o.paid_date <= DATE('" . $model->escape($end_date) . "')";
        }
        if ($paid_date_sql) {
            $paid_date_sql = implode(' AND ', $paid_date_sql);
        } else {
            $paid_date_sql = "o.paid_date IS NOT NULL";
        }

        $limit = (int) $limit;
        $limit = ifempty($limit, 10);

        $storefront_where = '';
        $storefront_join = " JOIN shop_order_params AS opst2
                                ON opst2.order_id=o.id AND opst2.name='quickorder_" . $options['type'] . "'";
        if (!empty($options['sales_channel'])) {
            $storefront_where .= " AND opst2.value='" . $model->escape($options['sales_channel']) . "' ";
        }
        // SS6
        if (!empty($options['storefront'])) {
            $storefront_where .= " AND opst2.value='storefront:" . $model->escape($options['storefront']) . "' ";
        }

        $sql = "SELECT
                    s.*,
                    SUM(oi.price*o.rate*oi.quantity) AS total,
                    SUM(oi.quantity) AS quantity
                FROM shop_order AS o
                    JOIN shop_order_items AS oi
                        ON oi.order_id=o.id
                    JOIN shop_service AS s
                        ON oi.service_id=s.id
                    {$storefront_join}
                WHERE $paid_date_sql
                    AND oi.type = 'service'
                    {$storefront_where}
                GROUP BY s.id
                ORDER BY total DESC
                LIMIT $limit";

        return $model->query($sql);
    }

    public function getSales($start_date = null, $end_date = null, $options = array())
    {
        $model = new waModel();

        $storefront_where = '';
        $storefront_join = " JOIN shop_order_params AS opst2
                                ON opst2.order_id=o.id AND opst2.name='quickorder_" . $options['type'] . "'";
        if (!empty($options['sales_channel'])) {
            $storefront_where .= " AND opst2.value='" . $model->escape($options['sales_channel']) . "' ";
        }
        // SS6
        if (!empty($options['storefront'])) {
            $storefront_where .= " AND opst2.value='storefront:" . $model->escape($options['storefront']) . "' ";
        }

        // !!! With 15k orders this query takes ~2 seconds
        $paid_date_sql = shopOrderModel::getDateSql('o.paid_date', $start_date, $end_date);
        $sql = "SELECT
                    t.*,
                    SUM(ps.price*pcur.rate*oi.quantity) AS sales
                FROM shop_order AS o
                    JOIN shop_order_items AS oi
                        ON oi.order_id=o.id
                    JOIN shop_product AS p
                        ON oi.product_id=p.id
                    JOIN shop_product_skus AS ps
                        ON oi.sku_id=ps.id
                    JOIN shop_currency AS pcur
                        ON pcur.code=p.currency
                    JOIN shop_type AS t
                        ON t.id=p.type_id
                    {$storefront_join}
                WHERE $paid_date_sql
                    AND oi.type = 'product'
                    {$storefront_where}
                GROUP BY t.id";
        return $model->query($sql)->fetchAll('id');
    }
}
