<?php

class shopClipackPluginZipCli extends waCliController
{
    public function execute()
    {
        $product_params_model = new shopProductParamsModel();
        $products = $product_params_model->query('SELECT `id` FROM `shop_product`')->fetchAll();
        foreach ($products as $product) {
            $product = new shopProduct($product['id']);
            $features = $product->getFeatures();
            $partnomer = [];
            if ($features['artikul_tekst']) {
                array_push($partnomer, $features['artikul_tekst']);
            }
            if ($features['yedinitsa_izmereniya']) {
                array_push($partnomer, $features['yedinitsa_izmereniya']);
            }

            $fields = array(
                'product_id' => $product['id'],
                'name' => 'partnomer',
            );
            $hasParams = count($product_params_model->getByField($fields));

            if (count($partnomer)) {
                $string = implode(', ', $partnomer);

                if ($hasParams) {
                    $data = array(
                        'value' => $string,
                    );
                    $product_params_model->updateByField($fields, $data);
                } else {
                    $data = array(
                        'product_id' => $product['id'],
                        'name' => 'partnomer',
                        'value' => $string,
                    );
                    $product_params_model->insert($data, 1);
                }
            } else {
                $product_params_model->deleteByField($fields);
            }
        }
    }

}
