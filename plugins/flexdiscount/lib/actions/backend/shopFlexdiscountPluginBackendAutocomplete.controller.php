<?php

class shopFlexdiscountPluginBackendAutocompleteController extends waController
{

    protected $limit = 10;

    public function execute()
    {
        $data = array();
        $q = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);
        $type = waRequest::get('type', 'product');

        if ($q) {
            if ($type == 'contact') {
                $data = $this->contactAutocomplete($q);
            } else {
                $data = $this->productAutocomplete($q);
            }
            $data = $this->formatData($data, $type);
        }
        echo json_encode($data);
    }

    private function contactAutocomplete($q)
    {
        $collection = new waContactsCollection('search/name*=' . $q);
        $contacts = $collection->getContacts('*');
        if (!$contacts) {
            $collection = new waContactsCollection('search/email*=' . $q);
            $contacts = $collection->getContacts('*');
        }
        return $contacts;
    }

    public function productAutocomplete($q)
    {
        $product_model = new shopProductModel();
        $product_skus_model = new shopProductSkusModel();
        $q = $product_model->escape($q, 'like');
        $fields = 'id, name';

        $products = $product_model->select($fields)
                ->where("name LIKE '$q%'")
                ->limit($this->limit)
                ->fetchAll('id');
        $count = count($products);

        if ($count < $this->limit) {
            $sql = "SELECT s.id, s.product_id, s.name as sku_name, s.sku, p.name as name FROM {$product_skus_model->getTableName()} s "
                    . "LEFT JOIN {$product_model->getTableName()} p ON p.id = s.product_id "
                    . "WHERE s.sku LIKE '$q%' LIMIT " . ($this->limit - $count);
            $skus = $product_skus_model->query($sql)->fetchAll();

            if ($skus) {
                foreach ($skus as $s) {
                    $products[] = array(
                        'label' => waString::escapeAll($s['name']) . ' <span class="hint">' . ($s['sku_name'] ? waString::escapeAll($s['sku_name']) . ' ' : '') . ($s['sku'] ? ' (' . waString::escapeAll($s['sku']) . ')' : '') . (!$s['sku_name'] && !$s['sku'] ? _wp('sku ID') . ': #' . $s['id'] : '') . '</span>',
                        'name' => waString::escapeAll($s['name']),
                        'sku_name' => ($s['sku_name'] ? waString::escapeAll($s['sku_name']) : ($s['sku'] ? waString::escapeAll($s['sku']) : (!$s['sku_name'] && !$s['sku'] ? _wp('sku ID') . ': #' . $s['id'] : ''))),
                        'sku_id' => $s['id'],
                        'id' => $s['product_id']
                    );
                }
                return array_values($products);
            }
        }

        if (!$products) {
            $products = $product_model->select($fields)
                    ->where("name LIKE '%$q%'")
                    ->limit($this->limit)
                    ->fetchAll('id');
        }

        foreach ($products as &$p) {
            $p['name'] = $p['label'] = waString::escapeAll($p['name']);
        }

        if (waRequest::get('with_skus')) {
            $p_ids = array_keys($products);
            $product_skus = $product_skus_model->getByField('product_id', $p_ids, true);
            $product_and_skus = array();
            foreach ($product_skus as $s) {
                $product_and_skus[] = array(
                    'label' => $products[$s['product_id']]['name'] . ' <span class="hint">' . ($s['name'] ? waString::escapeAll($s['name']) . ' ' : '') . ($s['sku'] ? ' (' . waString::escapeAll($s['sku']) . ')' : '') . (!$s['name'] && !$s['sku'] ? _wp('sku ID') . ': #' . $s['id'] : '') . '</span>',
                    'name' => $products[$s['product_id']]['name'],
                    'sku_name' => ($s['name'] ? waString::escapeAll($s['name']) : ($s['sku'] ? waString::escapeAll($s['sku']) : (!$s['name'] && !$s['sku'] ? _wp('sku ID') . ': #' . $s['id'] : ''))),
                    'sku_id' => $s['id'],
                    'id' => $s['product_id']
                );
            }
            $products = $product_and_skus;
        }

        return array_values($products);
    }

    private function formatData($data, $type)
    {
        if ($type == 'product') {
            return $data;
        }
        $formatted = array();
        foreach ($data as &$item) {
            $name = trim($item['name']) ? htmlspecialchars($item['name']) : '&lt;' . _wp("No name") . '&gt;';
            $formatted[] = array(
                'id' => $item['id'],
                'label' => $name,
                'name' => $name
            );
        }

        return $formatted;
    }

}
