<?php

class shopProductsetsPluginBackendAutocompleteController extends waController
{

    protected $limit = 100;
    private $product_model;

    public function execute()
    {
        $data = array();
        $q = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);

        if ($q) {
            $this->product_model = new shopProductModel();
            $data = $this->productAutocomplete($q);
            $data = $this->formatData($data);
        }
        echo json_encode($data);
    }

    public function productAutocomplete($q)
    {

        $q = $this->product_model->escape($q, 'like');

        $products = $this->getProducts(array("text" => $q));
        $count = count($products);

        if ($count < $this->limit) {
            $skus = $this->getSkus(array('text' => $q, 'limit' => ($this->limit - $count)));
            if ($skus) {
                $products = $products ? array_merge($products, $skus) : $skus;
            }
        }

        if (!$products) {
            $products = $this->getProducts(array("text" => $q, 'like' => 'middle'));
        }

        if ($products && waRequest::get('with_skus')) {

            $p_ids = array_map(function ($product) {
                return $product['id'];
            }, $products);
            $products = $this->getSkus(array('product_id' => $p_ids));
        }

        return $products;
    }

    private function formatData($data)
    {
        $products = array();
        if ($data) {
            $image_ids = array();
            $with_skus = waRequest::get('with_skus');
            foreach ($data as $d) {
                $products[$d['sku_id']] = $d;
                $products[$d['sku_id']] = array_merge($products[$d['sku_id']], array(
                    'price' => shop_currency_html($d['primary_price']),
                    'compare_price' => (isset($d['compare_price']) && $d['compare_price'] > 0) ? shop_currency_html($d['compare_price'], $d['currency']) : (isset($d['primary_compare_price']) && $d['primary_compare_price'] > 0 ? shop_currency_html($d['primary_compare_price']) : ''),
                    'name' => waString::escapeAll($d['name']),
                    'stocks' => shopHelper::getStockCountIcon($d['count']),
                    'stocks_with_text' => shopHelper::getStockCountIcon($d['count'], null, true)
                ));
                if (!empty($d['sku_name']) || !empty($d['sku']) || $with_skus) {
                    $products[$d['sku_id']]['sku_name'] .= ($d['sku_name'] ? waString::escapeAll($d['sku_name']) : ($d['sku'] ? waString::escapeAll($d['sku']) : (!$d['sku_name'] && !$d['sku'] ? _wp('sku ID') . ': #' . $d['sku_id'] : '')));
                }
                $products[$d['sku_id']]['label'] = $products[$d['sku_id']]['name'] . (!empty($products[$d['sku_id']]['sku_name']) ? ' (' . $products[$d['sku_id']]['sku_name'] . ')' : '');
                $image_ids[$d['image_id']] = $d['image_id'];
            }
            $images = (new shopProductImagesModel())->getByField('id', $image_ids, 'id');
            foreach ($products as &$p) {
                if (isset($images[$p['image_id']])) {
                    $p['image'] = shopImage::getUrl($images[$p['image_id']], '96x96');
                }
            }
        }

        return $products;
    }

    private function getSkus($filter)
    {
        $product_skus_model = new shopProductSkusModel();
        $sku_fields = 's.id as sku_id, s.product_id as id, s.name as sku_name, s.sku, p.name as name, s.compare_price, s.primary_price, p.currency, s.count, s.image_id';

        $sql = "SELECT {$sku_fields} FROM {$product_skus_model->getTableName()} s "
            . "LEFT JOIN {$this->product_model->getTableName()} p ON p.id = s.product_id "
            . "WHERE 1 ";
        if (isset($filter['text'])) {
            $sql .= "AND s.sku LIKE '" . $filter['text'] . "%' ";
        }
        if (!empty($filter['product_id'])) {
            $sql .= "AND s.product_id ";
            if (is_array($filter['product_id'])) {
                $sql .= "IN ('" . implode("','", $product_skus_model->escape($filter['product_id'], 'int')) . "') ";
            } else {
                $sql .= "= '" . (int) $filter['product_id'] . "' ";
            }
        }
        if (isset($filter['limit'])) {
            $sql .= "LIMIT " . $filter['limit'];
        }
        return $product_skus_model->query($sql)->fetchAll();
    }

    private function getProducts($filter)
    {
        $fields = 'id, sku_id, name, compare_price as primary_compare_price, price as primary_price, sku_count, count, image_id';
        $where = "name LIKE ";
        $like = "'" . $filter['text'] . "%'";
        if (!empty($filter['like']) && $filter['like'] == 'middle') {
            $like = "'%" . $filter['text'] . "%'";
        }
        $where .= $like;
        return $this->product_model->select($fields)->where($where)->limit($this->limit)->fetchAll();
    }
}
