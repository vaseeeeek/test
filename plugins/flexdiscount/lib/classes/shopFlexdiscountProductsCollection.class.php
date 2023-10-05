<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountProductsCollection extends shopProductsCollection
{

    public function getProducts($fields = "*", $offset = 0, $limit = null, $escape = true, $skip_workup = 0)
    {
        if (is_bool($limit)) {
            $escape = $limit;
            $limit = null;
        }
        if ($limit === null) {
            if ($offset) {
                $limit = $offset;
                $offset = 0;
            } else {
                $limit = 50;
            }
        }
        if ($this->is_frontend && $fields == '*') {
            $fields .= ',frontend_url';
        }
        $split_fields = array_map('trim', explode(',', $fields));
        if (in_array('frontend_url', $split_fields) && !in_array('*', $split_fields)) {
            if ($dependent_fields = array_diff(array('url', 'category_id',), $split_fields)) {
                $fields .= ',' . implode(',', $dependent_fields);
            }
        }

        $sql = $this->getSQL();

        // for dynamic set
        if ($this->hash[0] == 'set' && !empty($this->info['id']) && $this->info['type'] == shopSetModel::TYPE_DYNAMIC) {
            $this->count();
            if ($offset + $limit > $this->count) {
                $limit = $this->count - $offset;
            }
        }

        $order = $this->_getOrderBy();
        $sql = "SELECT " . ($this->joins && !$this->group_by ? 'DISTINCT ' : '') . $this->getFields($fields) . " " . $sql;
        $sql .= $this->_getGroupBy();
        if ($this->having) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }
        $sql .= $order;
        $sql .= " LIMIT " . ($offset ? $offset . ',' : '') . (int) $limit;

        $data = $this->getModel()->query($sql)->fetchAll('id');
        if (!$data) {
            return array();
        }
        if (!$skip_workup) {
            $this->workupProducts($data, $escape);
        }
        return $data;
    }

    private function workupProducts(&$products = array(), $escape = true)
    {
        if (empty($products)) {
            return;
        }

        // Round prices for products
        $config = shopFlexdiscountApp::get('system')['config'];
        $default_currency = shopFlexdiscountApp::get('system')['primary_currency'];
        $frontend_currency = null;
        if ($this->is_frontend) {
            $frontend_currency = shopFlexdiscountApp::get('system')['current_currency'];
            !empty($this->options['round_prices']) && shopRounding::roundProducts($products);
        }
        $rounding = array(
            'price', 'min_price', 'max_price', 'compare_price'
        );

        // Names of fields that must be converted to float values
        $float = array(
            'min_price',
            'max_price',
            'total_sales',
            'base_price_selectable',
            'rating',
            'price',
            'compare_price',
        );

        $fetch_params = !empty($this->options['params']) || (!empty($this->post_fields['_internal']) && in_array('params', $this->post_fields['_internal']));

        foreach ($products as &$p) {
            foreach ($float as $field) {
                if (isset($p[$field])) {
                    $p[$field] = (float) $p[$field];
                }
            }

            if (isset($p['total_sales'])) {
                $p['total_sales_html'] = '';
                if (!empty($p['total_sales'])) {
                    $p['total_sales_html'] = '<span class="nowrap">' .
                            shopFlexdiscountApp::getFunction()->shop_currency_html($p['total_sales'], $default_currency, $default_currency) .
                            '</span>';
                }
            }

            if (isset($p['rating'])) {
                $p['rating_html'] = '<span class="rate nowrap" title="' . htmlspecialchars(sprintf_wp('Average user rating: %s / 5', $p['rating'])) . '">' .
                        shopHelper::getRatingHtml($p['rating'], 10, true) .
                        '</span>';
            }

            // escape
            if ($escape) {
                if (isset($p['name'])) {
                    $p['name'] = htmlspecialchars($p['name']);
                }
                if (isset($p['url'])) {
                    $p['url'] = htmlspecialchars($p['url']);
                }
            }

            // Make sure array exists for all products
            if ($fetch_params) {
                $p['params'] = array();
            }

            if ($this->is_frontend) {
                // Striked-out price can not be lower than actual price
                if (!empty($p['compare_price']) && $p['compare_price'] <= ifset($p['price'])) {
                    $p['compare_price'] = 0;
                }

                if (empty($this->options['round_prices'])) {
                    // Add the 'frontend_*' and 'unconverted_*' keys anyway
                    foreach ($rounding as $k) {
                        if (isset($p[$k])) {
                            $p['unconverted_' . $k] = $p[$k];
                            $p['frontend_' . $k] = shopFlexdiscountApp::getFunction()->shop_currency($p[$k], $default_currency, $frontend_currency, false);
                        }
                    }
                }
            }
        }
        unset($p);

        // Fetch params
        if ($fetch_params) {
            $product_params_model = new shopProductParamsModel();
            $rows = $product_params_model->getByField('product_id', array_keys($products), true);
            foreach ($rows as $row) {
                $products[$row['product_id']]['params'][$row['name']] = $row['value'];
            }
        }

        // Get 'category_url' for each product
        if ($this->is_frontend && waRequest::param('url_type') == 2) {
            $this->updateCategoryUrls($products);
        }

        if ($this->post_fields) {

            $unprocessed = $this->post_fields;

            if (!empty($unprocessed['_internal'])) {
                $fields = array_fill_keys($unprocessed['_internal'], true);
                unset($unprocessed['_internal']);

                if (isset($fields['images']) || isset($fields['images2x'])) {
                    $fields['images'] = 1;
                    foreach ($products as &$p) {
                        $p['images'] = array();
                    }
                    unset($p);

                    $sizes = array();
                    $enabled_2x = isset($fields['images2x']) && shopFlexdiscountApp::get('system')['config']->getOption('enable_2x');
                    foreach (array('thumb', 'crop', 'big') as $size) {
                        $sizes[$size] = $config->getImageSize($size);
                        if ($enabled_2x) {
                            $sizes[$size] .= '@2x';
                        }
                    }
                    $product_images_model = new shopProductImagesModel();
                    $product_images = $product_images_model->getImages(array_keys($products), $sizes, 'product_id');
                    foreach ($product_images as $product_id => $images) {
                        $products[$product_id]['images'] = $images;
                    }
                }
                if (isset($fields['image'])) {
                    $sizes = array();
                    foreach (array('thumb', 'crop', 'big') as $size) {
                        $sizes[$size] = $config->getImageSize($size);
                    }

                    $absolute_image_url = !empty($this->options['absolute_image_url']);

                    foreach ($products as &$p) {
                        if ($p['image_id']) {
                            $tmp = array(
                                'id' => $p['image_id'],
                                'product_id' => $p['id'],
                                'filename' => $p['image_filename'],
                                'ext' => $p['ext']
                            );
                            foreach ($sizes as $size_id => $size) {
                                $p['image'][$size_id . '_url'] = shopImage::getUrl($tmp, $size, ifset($this->options['absolute'], $absolute_image_url));
                            }
                        } else {
                            foreach ($sizes as $size_id => $size) {
                                $p['image'] = null;
                            }
                        }
                    }
                    unset($p);
                }
                if (isset($fields['image_crop_small'])) {
                    $size = $config->getImageSize('crop_small');
                    foreach ($products as &$p) {
                        if ($p['image_id']) {
                            $tmp = array('id' => $p['image_id'], 'product_id' => $p['id'],
                                'filename' => $p['image_filename'], 'ext' => $p['ext']);
                            $p['image_crop_small'] = shopImage::getUrl($tmp, $size, isset($this->options['absolute']) ? $this->options['absolute'] : false);
                        }
                    }
                    unset($p);
                }
                if (isset($fields['image_count'])) {
                    if (isset($fields['images'])) {
                        foreach ($products as &$p) {
                            $p['image_count'] = count($p['images']);
                        }
                    } else {
                        $product_images_model = new shopProductImagesModel();
                        foreach ($product_images_model->countImages(array_keys($products)) as $product_id => $count) {
                            isset($products[$product_id]) && ($products[$product_id]['image_count'] = $count);
                        }
                    }
                }
                if (isset($fields['skus'])) {
                    $skus_model = new shopProductSkusModel();
                    $skus = $skus_model->getByField('product_id', array_keys($products), 'id');

                    foreach ($skus as &$sku) {
                        if (isset($sku['price'])) {
                            $sku['price_float'] = (float) $sku['price'];
                        }
                        if (isset($sku['purchase_price'])) {
                            $sku['purchase_price_float'] = (float) $sku['purchase_price'];
                        }
                        if (isset($sku['compare_price'])) {
                            $sku['compare_price_float'] = (float) $sku['compare_price'];
                        }
                        if (isset($sku['primary_price'])) {
                            $sku['primary_price_float'] = (float) $sku['primary_price'];
                        }
                    }
                    unset($sku);

                    foreach ($products as &$p) {
                        $p['skus'] = array();
                        if (isset($fields['stock_counts'])) {
                            $p['has_stock_counts'] = false;
                        }
                    }
                    unset($p);
                    $empty_stocks = array();

                    if (isset($fields['stock_counts'])) {
                        $stock_model = new shopStockModel();
                        $stocks = $stock_model->getAll('id');
                        $empty_stocks = array_fill_keys(array_keys($stocks), null);

                        $product_stocks_model = new shopProductStocksModel();
                        $rows = $product_stocks_model->getByField('product_id', array_keys($products), true);
                        foreach ($rows as $row) {
                            if (!empty($skus[$row['sku_id']])) {
                                $skus[$row['sku_id']]['stock'][$row['stock_id']] = $row['count'];
                            }
                            if (!empty($products[$row['product_id']])) {
                                $products[$row['product_id']]['has_stock_counts'] = true;
                            }
                        }
                        unset($rows, $row);
                    }

                    foreach ($skus as $s) {
                        if (empty($products[$s['product_id']])) {
                            continue;
                        }
                        if (isset($fields['stock_counts'])) {
                            if (empty($products[$s['product_id']]['has_stock_counts'])) {
                                $s['stock'] = null;
                            } else {
                                $s['stock'] = ifempty($s['stock'], array()) + $empty_stocks;
                            }
                        }
                        $products[$s['product_id']]['skus'][$s['id']] = $s;
                    }
                }
                if (isset($fields['sku'])) {
                    $sku_ids = array();
                    foreach ($products as $p) {
                        $sku_ids[] = $p['sku_id'];
                    }
                    $skus_model = new shopProductSkusModel();
                    $skus = $skus_model->getByField('id', $sku_ids, 'id');
                    foreach ($products as &$p) {
                        $p['sku'] = ifset($skus[$p['sku_id']]['sku'], '');
                    }
                    unset($p);
                }
                if (isset($fields['frontend_url'])) {
                    foreach ($products as &$p) {
                        $route_params = array('product_url' => $p['url']);
                        if (isset($p['category_url'])) {
                            $route_params['category_url'] = $p['category_url'];
                        } elseif (isset($this->info['hash']) && $this->info['hash'] == 'category' && !$this->info['type']) {
                            if (isset($this->info['subcategories']) && $this->info['id'] != $p['category_id']) {
                                if (isset($this->info['subcategories'][$p['category_id']])) {
                                    $route_params['category_url'] = $this->info['subcategories'][$p['category_id']]['full_url'];
                                }
                            } else {
                                $route_params['category_url'] = $this->info['full_url'];
                            }
                        }

                        $p['frontend_url'] = shopFlexdiscountApp::get('system')['wa']->getRouteUrl('shop/frontend/product', $route_params);
                    }
                    unset($p);
                }
                if (isset($fields['sales_30days'])) {
                    $default_currency = $config->getCurrency(true);
                    $sql = "SELECT product_id, SUM(oi.price*oi.quantity*o.rate)
                            FROM shop_order_items AS oi
                                JOIN shop_order AS o
                                    ON oi.order_id=o.id
                            WHERE oi.product_id IN (?)
                                AND oi.type='product'
                                AND o.paid_date >= ?
                            GROUP BY product_id";
                    $sales = $this->getModel()->query($sql, array(array_keys($products), date('Y-m-d', time() - 3600 * 24 * 30)))->fetchAll('product_id', true);
                    foreach ($products as &$p) {
                        $p['sales_30days'] = (float) ifset($sales[$p['id']], 0.0);
                        $p['sales_30days_html'] = empty($p['sales_30days']) ? '' : '<span class="nowrap">' .
                                shopFlexdiscountApp::getFunction()->shop_currency_html($p['sales_30days'], $default_currency, $default_currency) .
                                '</span>';
                    }
                    unset($p);
                }
                if (isset($fields['stock_worth'])) {
                    $default_currency = $config->getCurrency(true);
                    $sql = "SELECT s.product_id, SUM(s.primary_price*s.count) AS net_worth
                            FROM shop_product_skus AS s
                            WHERE s.product_id IN (?)
                            GROUP BY s.product_id";
                    $stock_worth = $this->getModel()->query($sql, array(array_keys($products)))->fetchAll('product_id', true);
                    foreach ($products as &$p) {
                        $p['stock_worth'] = (float) ifset($stock_worth[$p['id']], 0.0);
                        $p['stock_worth_html'] = empty($p['stock_worth']) ? '' : '<span class="nowrap">' .
                                shopFlexdiscountApp::getFunction()->shop_currency_html($p['stock_worth'], $default_currency, $default_currency) .
                                '</span>';
                    }
                    unset($p);
                }
            }

            // features
            if (!empty($unprocessed['_features'])) {
                $feature_ids = array_keys($unprocessed['_features']);
                unset($unprocessed['_features']);

                // product_id => feature_id => array(value => ..., value_html => ...)
                $feature_values = array();

                $feature_model = new shopFeatureModel();
                $features = $feature_model->getByField('id', $feature_ids, 'id');
                if ($features) {

                    // Get feature_value_ids for all products
                    $sql = "SELECT pf.*
                            FROM shop_product_features AS pf
                            WHERE pf.product_id IN (?)
                                AND pf.feature_id IN (?)";
                    $product_features = $this->getModel()->query($sql, array(
                        array_keys($products),
                        $feature_ids,
                    ));

                    // Prepare list of value_ids to fetch later, and places to fetch them from
                    $storages = array(); // feature type => feature_value_id => list of product_ids
                    foreach ($product_features as $row) {
                        $f = $features[$row['feature_id']];
                        $type = preg_replace('/\..*$/', '', $f['type']);
                        if ($type == shopFeatureModel::TYPE_BOOLEAN) {
                            /** @var shopFeatureValuesBooleanModel $model */
                            $model = shopFeatureModel::getValuesModel($type);
                            $values = $model->getValues('id', $row['feature_value_id']);
                            $feature_values[$row['product_id']][$row['feature_id']]['value'] = reset($values);
                        } elseif ($type == shopFeatureModel::TYPE_DIVIDER) {
                            // ignore dividers
                        } else {
                            $storages[$type][$row['feature_value_id']][$row['product_id']] = true;
                        }
                    }

                    // Fetch actual values from shop_feature_values_* tables
                    foreach ($storages as $type => $value_products) {
                        $model = shopFeatureModel::getValuesModel($type);
                        foreach ($model->getValues('id', array_keys($value_products)) as $feature_id => $values) {
                            if (isset($features[$feature_id])) {
                                $f = $features[$feature_id];
                                foreach ($values as $value_id => $value) {
                                    foreach (array_keys($value_products[$value_id]) as $product_id) {
                                        if (!empty($f['multiple'])) {
                                            $feature_values[$product_id][$feature_id]['value'][] = $value;
                                        } else {
                                            $feature_values[$product_id][$feature_id]['value'] = $value;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Prepare value_html for each feature value
                    foreach ($feature_values as &$fv) {
                        foreach ($fv as $feature_id => &$arr) {
                            if (is_array($arr['value'])) {
                                $arr['value_html'] = join(', ', $arr['value']);
                            } else {
                                $arr['value_html'] = (string) $arr['value'];
                            }
                        }
                    }
                    unset($fv, $arr);
                }

                // Finally, assign feature data to actual products
                foreach ($products as &$p) {
                    foreach ($feature_ids as $fid) {
                        $p['feature_' . $fid] = ifset($feature_values[$p['id']][$fid]['value']);
                        $p['feature_' . $fid . '_html'] = ifset($feature_values[$p['id']][$fid]['value_html'], ifempty($p['feature_' . $fid], ''));
                    }
                }
                unset($p);
            }
        }

        if ($this->is_frontend) {
            foreach ($products as $p_id => $p) {
                if (isset($p['price'])) {
                    $products[$p_id]['original_price'] = $p['price'];
                }
                if (isset($p['compare_price'])) {
                    $products[$p_id]['original_compare_price'] = $p['compare_price'];
                }
            }
        }
    }

}
