<?php

class shopDelpayfilterPluginBackendProductAutocompleteController extends waController
{

    protected $limit = 10;

    public function execute()
    {
        $data = array();
        $q = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);
        if ($q) {
            $data = $this->productsAutocomplete($q);
            $data = $this->formatData($data);
        }
        echo json_encode($data);
    }

    private function formatData($data)
    {
        foreach ($data as &$item) {
            if (empty($item['label'])) {
                $item['label'] = htmlspecialchars($item['value']);
            }
        }

        return $data;
    }

    public function productsAutocomplete($q, $limit = null)
    {
        $limit = $limit !== null ? $limit : $this->limit;
        $product_model = new shopProductModel();
        $product_skus_model = new shopProductSkusModel();
        $q = $product_model->escape($q, 'like');
        $fields = 'id, name AS value';

        $products = $product_model->select($fields)
                ->where("name LIKE '$q%'")
                ->limit($limit)
                ->fetchAll('id');
        $count = count($products);

        if ($count < $limit) {
            $product_ids = array_keys($product_skus_model->select('id, product_id')
                            ->where("sku LIKE '$q%'")
                            ->limit($limit)
                            ->fetchAll('product_id'));
            if ($product_ids) {
                $data = $product_model->select($fields)
                        ->where('id IN (' . implode(',', $product_ids) . ')')
                        ->limit($limit - $count)
                        ->fetchAll('id');

                $new_products = $products + $data;
                $products = array();
                if ($new_products) {
                    foreach ($new_products as $np) {
                        $products[$np['id']] = $np;
                    }
                }
            }
        }

        if (!$products) {
            $products = $product_model->select($fields)
                    ->where("name LIKE '%$q%'")
                    ->limit($limit)
                    ->fetchAll('id');
        }
        unset($p);

        return array_values($products);
    }

}
