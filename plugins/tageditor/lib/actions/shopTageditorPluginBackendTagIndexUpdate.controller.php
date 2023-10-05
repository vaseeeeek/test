<?php

class shopTageditorPluginBackendTagIndexUpdateController extends waLongActionController
{
    public function execute()
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        $this->getResponse()->sendHeaders();

        $get_hash_products = (bool) waRequest::post('get-hash-products', 0, waRequest::TYPE_INT);
        $start_long_action = (bool) waRequest::post('start-long-action', 0, waRequest::TYPE_INT);

        $product_ids = waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT);
        $hash = waRequest::post('hash', '', waRequest::TYPE_STRING_TRIM);

        $hash_product_ids = waRequest::post('hash-product-ids', '', waRequest::TYPE_STRING_TRIM);

        if ($product_ids || $hash) {
            //do not auto-update if not selected in settings
            $shop_cloud_auto_update = wa('shop')->getPlugin('tageditor')->getSettings('shop_cloud_auto_update');
            if (!$shop_cloud_auto_update) {
                $this->response(array(
                    'skip' => true,
                ));
                return;
            }
        }

        if ($get_hash_products) {
            $this->response(array(
                'hash_product_ids' => $hash ? $this->getHashProducts($hash) : array(),
            ));
            return;
        }

        if (!$start_long_action && ($product_ids || $hash)) {
            //first attempt to auto-update in "Products"
            //either simply return 'long-action' = true, or update a short product list and return 'long-action' = false

            if ($product_ids) {
                $product_count = count($product_ids);
            } elseif ($hash_product_ids) {
                $products_collection = new shopProductsCollection('id/'.$hash_product_ids);
                $product_count = $products_collection->count();
                if ($product_count) {
                    $product_ids = array_keys($products_collection->getProducts('id', 0, $product_count));
                }
            }

            if (isset($product_count) && $product_count > shopTageditorPluginIndex::UPDATE_BATCH_SIZE) {
                $this->response(array(
                    'long_action' => true,
                ));
                return;
            } else {
                $index = new shopTageditorPluginIndex($product_ids);
                $index->updateProducts();

                $this->response(array(
                    'long_action' => false,
                ));
                return;
            }
        }

        //manual update in "Products → Tag editor"
        //or long auto-update
        try {
            parent::execute();
        } catch (waException $ex) {
            if ($ex->getCode() == '302') {
                $this->response(array('warning' => $ex->getMessage()));
            } else {
                $this->response(array('error' => $ex->getMessage()));
            }
        }
    }

    private function getHashProducts($hash)
    {
        $hash_products_collection = new shopProductsCollection($hash);
        $hash_product_count = $hash_products_collection->count();
        if ($hash_product_count) {
            $hash_product_ids = array_keys($hash_products_collection->getProducts('id', 0, $hash_product_count));
        }
        return ifempty($hash_product_ids, array());
    }

    protected function init()
    {
        $product_ids = waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT);
        if (!$product_ids) {
            $hash_product_ids = waRequest::post('hash-product-ids', '', waRequest::TYPE_STRING_TRIM);
            if ($hash_product_ids) {
                $products_collection = new shopProductsCollection('id/'.$hash_product_ids);
                $product_count = $products_collection->count();
                if ($product_count) {
                    $product_ids = array_keys($products_collection->getProducts('id', 0, $product_count));
                }
            }
        }

        if (!$product_ids) {
            shopTageditorPluginModels::indexProductTags()->truncate();
            shopTageditorPluginModels::indexTag()->truncate();
        }

        $locale_info = waLocale::getInfo(wa()->getLocale());

        $this->data['products_total_count'] = $product_ids ? count($product_ids) : shopTageditorPluginModels::shopProduct()->countAll();
        $this->data['offset'] = 0;
        $this->data['timestamp'] = time();
        $this->data['hint'] = _wp('Getting products data...');
        $this->data['product_ids'] = $product_ids;
        $this->data['locale_decimal_point'] = ifset($locale_info['decimal_point'], '.');
    }

    protected function step()
    {
        $chunk_size = shopTageditorPluginIndex::UPDATE_BATCH_SIZE;

        if ($this->data['product_ids']) {
            $product_ids = array_slice($this->data['product_ids'], $this->data['offset'], $chunk_size);
            $index = new shopTageditorPluginIndex($product_ids);
            $index->updateProducts();
        } else {
            $model = new waModel();
            $product_ids = $model->query(
                'SELECT id
                FROM shop_product
                ORDER BY id
                LIMIT i:offset, i:length',
                array(
                    'offset' => $this->data['offset'],
                    'length' => $chunk_size,
                )
            )->fetchAll(null, true);
            $index = new shopTageditorPluginIndex($product_ids);
            $index->updateProducts();
        }

        $this->data['offset'] += $chunk_size;
        $this->data['hint'] = _wp('Updating tag index:');

        return true;
    }

    protected function isDone()
    {
        return $this->data['offset'] >= $this->data['products_total_count'];
    }

    protected function finish($filename)
    {
        $this->info();
        if ($this->getRequest()->post('cleanup')) {
            return true;
        }
        return false;
    }

    protected function info()
    {
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time'      => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId' => $this->processId,
            'progress'  => 0.0,
            'ready'     => $this->isDone(),
            'offset'    => $this->data['offset'],
            'hint'      => $this->data['hint'],
        );

        $response['progress'] = empty($this->data['products_total_count']) ? 100 : ($this->data['offset'] / $this->data['products_total_count']) * 100;
        $response['progress'] = sprintf('%0.2f%%', $response['progress']);
        if ($this->data['locale_decimal_point'] != '.') {
            $response['progress'] = str_replace('.', $this->data['locale_decimal_point'], $response['progress']);
        }

        $this->response($response);
    }

    protected function response($response)
    {
        echo json_encode($response);
    }
}
