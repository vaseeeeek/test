<?php

class shopListfeaturesPluginFeatures
{
    private static $value_fields = array('value', 'code', 'unit', 'begin', 'end', 'url');
    private static $type_value_fields = array(
        'boolean'   => null,
        'color'     => array('value', 'code', 'url'),
        'dimension' => array('value', 'unit', 'url'),
        'divider'   => null,
        'double'    => array('value', 'url'),
        'range'     => array('begin', 'end', 'unit'),
        'text'      => array('value'),
        'varchar'   => array('value', 'url'),
    );
    private static $extra_features = array('skus', 'tags', 'categories', 'pages');
    private static $products;
    private static $set_id;
    private static $features_clause;
    private static $product_list;

    public static function display($product, $products, $set_id = 1)
    {
        static $features;
        static $set_configs;
        if (is_array($products) && $products) {
            if (self::getProductId($product) == self::getProductId(reset($products))) {
                self::$set_id = $set_id;
                if (!isset($set_configs[$set_id])) {
                    $set_configs[$set_id] = shopListfeaturesPluginHelper::getSettlementConfig(null, $set_id);
                }
                self::setFeaturesClause();
                self::$product_list = self::getProductListHash($products);

                if (ifempty(self::$features_clause[$set_id])
                || self::displayExtraFeatures(ifempty($set_configs[$set_id]['features']))) {
                    if (!isset(self::$products[self::$product_list])) {
                        foreach ($products as $p) {
                            if ($product_id = self::getProductId($p)) {
                                self::$products[self::$product_list][] = $product_id;
                            }
                        }
                    }

                    if (self::$products[self::$product_list]) {
                        waLocale::loadByDomain(array('shop', 'listfeatures'));
                        waSystem::pushActivePlugin('listfeatures', 'shop');
                        $features[self::$product_list][$set_id] = self::getFeatures($set_configs[$set_id]['features'], $set_id);
                        waSystem::popActivePlugin();
                    }
                }
            }

            if (ifempty($features[self::$product_list][$set_id])) {
                if (($product_id = self::getProductId($product))
                && isset($features[self::$product_list][$set_id][$product_id])) {
                    //to avoid overriding design theme's variables
                    static $original_vars;
                    if (!$original_vars) {
                        $original_vars = array();
                        foreach (array('set', 'features', 'product') as $original_var_key) {
                            $original_vars[$original_var_key] = shopListfeaturesPluginHelper::getView()->getVars($original_var_key);
                        }
                    }

                    shopListfeaturesPluginHelper::getView()->assign(array(
                        'set'      => ifset($set_configs[$set_id]['options']),
                        'features' => $features[self::$product_list][$set_id][$product_id],
                        'product'  => $product,
                    ));
                    $template = shopListfeaturesPluginHelper::getTemplate(ifset($set_configs[$set_id]['options']['template']));
                    $html = shopListfeaturesPluginHelper::getView()->fetch('string:'.$template);

                    //to avoid overriding design theme's variables
                    foreach ($original_vars as $key => $original_value) {
                        if (!is_null($original_value)) {
                            shopListfeaturesPluginHelper::getView()->assign($key, $original_value);
                        } else {
                            shopListfeaturesPluginHelper::getView()->clearAssign($key);
                        }
                    }

                    return $html;
                }
            }
        }
    }

    private static function getProductListHash($products)
    {
        $ids = array();
        foreach ($products as $p) {
            $ids[] = self::getProductId($p);
        }
        sort($ids);
        return md5(serialize($ids));
    }

    private static function setFeaturesClause()
    {
        static $features;
        if (!isset($features[self::$set_id])) {
            $features[self::$set_id] = array_filter(
                array_keys(shopListfeaturesPluginHelper::getSettlementConfig(null, self::$set_id, 'features')),
                create_function('$item', 'return is_numeric($item);')
            );
        }

        if ($features[self::$set_id]) {
            $feature_ids = implode(',', $features[self::$set_id]);
            self::$features_clause[self::$set_id] = ' AND (
                f.parent_id IS NULL AND f.id IN ('.$feature_ids.')
                OR f.parent_id IS NOT NULL AND f.parent_id IN ('.$feature_ids.')
            )';
        } else {
            self::$features_clause[self::$set_id] = null;
        }
    }

    private static function getProductId($product)
    {
        return isset($product['product_id']) ? (int) $product['product_id'] : (isset($product['id']) ? (int) $product['id'] : null);
    }

    private static function displayExtraFeatures($features_config)
    {
        foreach (self::$extra_features as $extra) {
            if (isset($features_config[$extra])) {
                return true;
            }
        }
        return false;
    }

    private static function getValueUrl($feature_id, $url)
    {
        static $route;
        if (empty($route)) {
            $route = wa()->getRouting()->getRoute();
        }

        static $urls;
        if (!isset($urls[$feature_id])) {
            switch ($feature_id) {
                case 'tags':
                    $urls[$feature_id] = wa()->getRouteUrl('shop/frontend/tag', array(
                        'tag' => '%TAG_URL%'
                    ));
                    break;
                case 'categories':
                    $urls[$feature_id] = wa()->getRouteUrl('shop/frontend/category', array(
                        'category_url' => '%CATEGORY_URL%'
                    ));
                    break;
                case 'pages':
                    $urls[$feature_id] = wa()->getRouteUrl('shop/frontend/productPage', array(
                        'product_url'  => '%PRODUCT_URL%',
                        'category_url' => '%CATEGORY_URL%',
                        'page_url'     => '%PAGE_URL%',
                    ));
                    break;
                default:
                    $urls[$feature_id] = wa()->getRouteUrl('shop/frontend', false).'listfeatures/%SET_ID%/%FEATURE_ID%/%VALUE_ID%/';
            }
        }

        switch ($feature_id) {
            case 'tags':
                return str_replace('%TAG_URL%', urlencode($url), $urls[$feature_id]);
            case 'categories':
                $category_urls = explode('|', $url);
                return str_replace('%CATEGORY_URL%', isset($route['url_type']) && $route['url_type'] == 1 ? $category_urls[0] : $category_urls[1], $urls[$feature_id]);
            case 'pages':
                return str_replace(array('%PRODUCT_URL%', '%CATEGORY_URL%', '%PAGE_URL%'), explode('|', $url), $urls[$feature_id]);
            default:
                return str_replace(array('%SET_ID%', '%FEATURE_ID%', '%VALUE_ID%'), explode('|', $url), $urls[$feature_id]);
        }
    }

    private static function loc($key)
    {
        static $locale;
        if (!isset($locale[$key])) {
            $locale[$key] = _wp($key);
        }
        return $locale[$key];
    }

    private static function locUnit($feature)
    {
        /**
         * @var $feature['f_type'] 'type' from 'shop_feature' table
         * @var $feature['unit']   'unit' from 'shop_feature_values_***' tables
         */
        $f_type_parts = explode('.', $feature['f_type']);
        $type = end($f_type_parts);
        $unit = $feature['unit'];

        static $units;
        if (!isset($units[$type][$unit])) {
            $units[$type][$unit] = shopDimension::getUnit($type, $unit);
        }
        return ifset($units[$type][$unit]['title']);
    }

    private static function getFeatures($features_config, $set_id)
    {
        $model = shopListfeaturesPluginHelper::getModel();

        if (self::$features_clause[self::$set_id]) {
            $sql = '
                SELECT
                    pf.product_id,
                    pf.feature_id,
                    pf.feature_value_id,
                    f.type as feature_type
                FROM shop_product_features pf
                JOIN shop_feature f
                    ON f.id = pf.feature_id
                JOIN shop_type_features tf
                    ON (f.parent_id IS NULL AND tf.feature_id = f.id)
                        OR (f.parent_id IS NOT NULL AND tf.feature_id = f.parent_id)
                JOIN shop_product p
                    ON p.id = pf.product_id
                WHERE
                    f.type <> "divider"
                    AND pf.product_id IN (i:product_ids)
                    AND p.type_id = tf.type_id

                UNION

                SELECT
                    p.id as product_id,
                    f.id as feature_id,
                    NULL as feature_value_id,
                    f.type as feature_type
                FROM shop_product p
                JOIN shop_type_features tf
                    ON tf.type_id = p.type_id
                JOIN shop_type t
                    ON t.id = p.type_id
                JOIN shop_feature f
                    ON f.id = tf.feature_id
                WHERE f.type = "divider"
                    AND p.id IN (i:product_ids)';

            $data = $model->query($sql, array(
                'product_ids' => self::$products[self::$product_list]
            ))->fetchAll();

            if (!$data && !self::displayExtraFeatures($features_config)) {
                return null;
            }

            //group feature value ids and associated data by feature type
            foreach ($data as &$item) {
                foreach (explode('.', $item['feature_type']) as $part) {
                    $item['feature_type'] = null;
                    if (in_array($part, array_keys(self::$type_value_fields))) {
                        $item['feature_type'] = $part;
                        break;
                    }
                }
            }
            unset($item);

            $processed = array();
            foreach ($data as $item) {
                if ($item['feature_type']) {
                    $processed[$item['feature_type']][$item['product_id']][] = array(
                        'feature_id' => $item['feature_id'],
                        'value_id'   => $item['feature_value_id'],
                    );
                }
            }

            //group product ids, feature ids, and value ids by feature type
            $raw = $processed;
            $processed = array();
            foreach ($raw as $type => $values) {
                foreach ($values as $product_id => $features) {
                    if (!isset($processed[$type]['product_ids'])) {
                        $processed[$type]['product_ids'] = array();
                    }
                    if (!in_array($product_id, $processed[$type]['product_ids'])) {
                        $processed[$type]['product_ids'][] = $product_id;
                    }
                    foreach ($features as $feature) {
                        if (!isset($processed[$type]['feature_ids'])) {
                            $processed[$type]['feature_ids'] = array();
                        }
                        if (!in_array($feature['feature_id'], $processed[$type]['feature_ids'])) {
                            $processed[$type]['feature_ids'][] = $feature['feature_id'];
                        }
                        if (!isset($processed[$type]['value_ids'])) {
                            $processed[$type]['value_ids'] = array();
                        }
                        if (!in_array($feature['value_id'], $processed[$type]['value_ids'])) {
                            $processed[$type]['value_ids'][] = $feature['value_id'];
                        }
                    }
                }
            }
        } else {
            //empty array to add extra features to
            $processed = array();
        }

        //add extra features if selected in settings
        foreach (self::$extra_features as $extra) {
            if (isset($features_config[$extra])) {
                $processed[$extra] = array(
                    'product_ids' => self::$products[self::$product_list],
                );
            }
        }

        //get feature values and associated data from database
        $set_options = shopListfeaturesPluginHelper::getSettlementConfig(shopListfeaturesPluginHelper::getSettlementHash(), self::$set_id, 'options');
        $data_sort_config = ifset($set_options['data_sort'], array());

        $skus_conditions = array(
            'available' => !((int) ifset($set_options['show_disabled_skus_data'])),
            'count'     => (int) ifset($set_options['hide_outofstock_skus_data']),
        );
        $check_skus = array_sum($skus_conditions) > 0;
        $skus_select_dummy = $check_skus ? ' , NULL as sku_id, NULL as sku_count, NULL as sku_available' : '';

        $type_sqls = array();
        foreach ($processed as $type => $ids) {
            if ($type == 'skus') {
            // skus
                $type_sql = '
                SELECT
                    p.id as product_id,
                    "skus" as feature_id,
                    NULL as feature_name,
                    "%skus%" as feature_code,
                    NULL as type_id,
                    "extra" as feature_type,
                    NULL as f_type,
                    NULL as value_id,
                    '.ifset($data_sort_config[$type], 0).' as data_sort,
                    0 as feature_sort';

                foreach (self::$value_fields as $field) {
                    $type_sql .= ', ';
                    if ($field == 'value') {
                        $type_sql .= 'ps.sku as value';
                    } else {
                        $type_sql .= 'NULL as '.$field;
                    }
                }

                $type_sql .= ', ps.sku as value_sort';
                $type_sql .= $skus_select_dummy;

                $type_sql .= '
                    FROM shop_product_skus ps
                    JOIN shop_product p
                        ON p.id = ps.product_id
                    WHERE p.id IN ('.implode(',', $ids['product_ids']).')
                        AND LENGTH(ps.sku) > 0'
                            .($skus_conditions['available'] ? ' AND ps.available = 1' : '')
                            .($skus_conditions['count'] ? ' AND (ps.count IS NULL OR ps.count > 0)' : '');
            } elseif ($type == 'tags') {
            // tags
                $type_sql = '
                SELECT
                    p.id as product_id,
                    "tags" as feature_id,
                    NULL as feature_name,
                    "%tags%" as feature_code,
                    NULL as type_id,
                    "extra" as feature_type,
                    NULL as f_type,
                    NULL as value_id,
                    '.ifset($data_sort_config[$type], 0).' as data_sort,
                    0 as feature_sort';

                foreach (self::$value_fields as $field) {
                    $type_sql .= ', ';
                    if ($field == 'value') {
                        $type_sql .= 't.name as value';
                    } elseif ($field == 'url') {
                        $type_sql .= 't.name as url';
                    } else {
                        $type_sql .= 'NULL as '.$field;
                    }
                }

                $type_sql .= ', t.name as value_sort';
                $type_sql .= $skus_select_dummy;

                $type_sql .= '
                    FROM shop_product_tags pt
                    JOIN shop_tag t
                        ON t.id = pt.tag_id
                    JOIN shop_product p
                        ON p.id = pt.product_id
                    WHERE p.id IN ('.implode(',', $ids['product_ids']).')';
            } elseif ($type == 'categories') {
            // categories
                $type_sql = '
                SELECT
                    p.id as product_id,
                    "categories" as feature_id,
                    NULL as feature_name,
                    "%categories%" as feature_code,
                    NULL as type_id,
                    "extra" as feature_type,
                    NULL as f_type,
                    NULL as value_id,
                    '.ifset($data_sort_config[$type], 0).' as data_sort,
                    0 as feature_sort';

                foreach (self::$value_fields as $field) {
                    $type_sql .= ', ';
                    if ($field == 'value') {
                        $type_sql .= 'c.name as value';
                    } elseif ($field == 'url') {
                        $type_sql .= 'CONCAT_WS("|",  c.url, c.full_url) as url';
                    } else {
                        $type_sql .= 'NULL as '.$field;
                    }
                }

                $type_sql .= ', c.name as value_sort';
                $type_sql .= $skus_select_dummy;

                $type_sql .= '
                    FROM shop_category_products cp
                    JOIN shop_category c
                        ON c.id = cp.category_id
                    JOIN shop_product p
                        ON p.id = cp.product_id
                    WHERE p.id IN ('.implode(',', $ids['product_ids']).')';
            } elseif ($type == 'pages') {
            // pages
                $type_sql = '
                SELECT
                    p.id as product_id,
                    "pages" as feature_id,
                    NULL as feature_name,
                    "%pages%" as feature_code,
                    NULL as type_id,
                    "extra" as feature_type,
                    NULL as f_type,
                    NULL as value_id,
                    '.ifset($data_sort_config[$type], 0).' as data_sort,
                    0 as feature_sort';

                foreach (self::$value_fields as $field) {
                    $type_sql .= ', ';
                    if ($field == 'value') {
                        $type_sql .= 'pp.name as value';
                    } elseif ($field == 'url') {
                        $type_sql .= 'CONCAT_WS("|", p.url, IFNULL(c.url, ""), pp.url) as url';
                    } else {
                        $type_sql .= 'NULL as '.$field;
                    }
                }

                $type_sql .= ', pp.sort as value_sort';
                $type_sql .= $skus_select_dummy;

                $type_sql .= '
                    FROM shop_product_pages pp
                    JOIN shop_product p
                        ON p.id = pp.product_id
                    LEFT JOIN shop_category c
                        ON c.id = p.category_id
                    WHERE p.id IN ('.implode(',', $ids['product_ids']).')';
            } elseif ($type == 'divider') {
            // divider features
                $type_sql = '
                SELECT
                    p.id as product_id,
                    tf.feature_id,
                    f.name as feature_name,
                    f.code as feature_code,
                    tf.type_id,
                    "divider" as feature_type,
                    NULL as f_type,
                    NULL as value_id,
                    '.ifset($data_sort_config['features'], 0).' as data_sort,
                    tf.sort as feature_sort';

                foreach (self::$value_fields as $field) {
                    $type_sql .= ', NULL as '.$field;
                }

                $type_sql .= ', NULL as value_sort';
                $type_sql .= $skus_select_dummy;

                $type_sql .= '
                    FROM shop_product p
                    JOIN shop_type_features tf
                        ON tf.type_id = p.type_id
                    JOIN shop_type t
                        ON t.id = p.type_id
                    JOIN shop_feature f
                        ON f.id = tf.feature_id
                    WHERE
                        p.id IN('.implode(',', $ids['product_ids']).')
                        AND f.id IN('.implode(',', $ids['feature_ids']).')
                        AND p.type_id = tf.type_id';

                $type_sql .= self::$features_clause[self::$set_id];
            } elseif ($type == 'boolean') {
            // boolean features
                $type_sql = '
                SELECT
                    pf.product_id,
                    pf.feature_id,
                    f.name as feature_name,
                    f.code as feature_code,
                    tf.type_id,
                    "boolean" as feature_type,
                    NULL as f_type,
                    NULL as value_id,
                    '.ifset($data_sort_config['features'], 0).' as data_sort,
                    tf.sort as feature_sort';

                foreach (self::$value_fields as $field) {
                    $type_sql .= ', ';
                    if ($field == 'value') {
                        $type_sql .= 'pf.feature_value_id as value';
                    } else {
                        $type_sql .= 'NULL as '.$field;
                    }
                }

                $type_sql .= ', NULL as value_sort';
                $type_sql .= $skus_select_dummy;

                $type_sql .= '
                    FROM shop_product_features pf
                    JOIN shop_feature f
                        ON f.id = pf.feature_id
                    JOIN shop_type_features tf
                        ON tf.feature_id = f.id
                    JOIN shop_product p
                        ON p.id = pf.product_id
                    WHERE
                        pf.product_id IN('.implode(',', $ids['product_ids']).')
                        AND pf.feature_id IN('.implode(',', $ids['feature_ids']).')
                        AND p.type_id = tf.type_id';

                $type_sql .= self::$features_clause[self::$set_id];
            } else {
            // other features
                if ($check_skus) {
                    $other_features_config = array(
                        //fixed SKU products
                        array(
                            'select' => $skus_select_dummy,
                            'join' => '',
                            'where' => ' AND p.sku_type = 0',
                        ),
                        //virtual SKU products, virtual SKUs features
                        array(
                            'select' => ', ps.id as sku_id, ps.count as sku_count, ps.available as sku_available',
                            'join' => '
                                JOIN shop_product_skus ps
                                    ON ps.product_id = pf.product_id
                                        AND pf.sku_id = ps.id',
                            'where' => ' AND p.sku_type = 1'
                                .($skus_conditions['available'] ? ' AND ps.available = 1' : '')
                                .($skus_conditions['count'] ? ' AND (ps.count IS NULL OR ps.count > 0)' : ''),
                        ),
                        //virtual SKU products, product features
                        array(
                            'select' => $skus_select_dummy,
                            'join' => '
                                JOIN shop_product_skus ps
                                    ON ps.product_id = pf.product_id
                                        AND pf.sku_id IS NULL',
                            'where' => ' AND p.sku_type = 1',
                        ),
                    );
                } else {
                    $other_features_config = array(
                        array(
                            'select' => $skus_select_dummy,
                            'join' => '',
                            'where' => '',
                        ),
                    );
                }

                $other_features_sqls = array();
                foreach ($other_features_config as $other_features_config_entry) {
                    $other_features_sql = '
                        SELECT
                            pf.product_id,
                            pf.feature_id,
                            f.name as feature_name,
                            f.code as feature_code,
                            tf.type_id,
                            "'.$type.'" as feature_type,
                            f.type as f_type,
                            v.id as value_id,
                            '.ifset($data_sort_config['features'], 0).' as data_sort,
                            tf.sort as feature_sort';

                    foreach (self::$value_fields as $field) {
                        $other_features_sql .= ', ';
                        if (in_array($field, self::$type_value_fields[$type])) {
                            if ($field == 'url') {
                                $other_features_sql .= "CONCAT_WS('|', $set_id, f.id, v.id) as url";
                            } elseif ($field == 'code') {
                                $other_features_sql .= "CAST(v.$field AS UNSIGNED) as code";
                            } else {
                                $other_features_sql .= 'v.'.$field;
                            }
                        } else {
                            $other_features_sql .= 'NULL as '.$field;
                        }
                    }

                    $other_features_sql .= ', v.sort as value_sort';
                    $other_features_sql .= $other_features_config_entry['select'];

                    $other_features_sql .= '
                        FROM shop_product_features pf
                        JOIN shop_feature_values_'.$type.' v
                            ON v.feature_id = pf.feature_id AND v.id = pf.feature_value_id
                        JOIN shop_feature f
                            ON f.id = pf.feature_id
                        JOIN shop_type_features tf
                            ON (f.parent_id IS NULL AND tf.feature_id = f.id
                                OR f.parent_id IS NOT NULL AND tf.feature_id = f.parent_id)
                        JOIN shop_product p
                            ON p.id = pf.product_id';

                    $other_features_sql .= $other_features_config_entry['join'];

                    $other_features_sql .= '
                        WHERE
                            pf.product_id IN('.implode(',', $ids['product_ids']).')
                            AND pf.feature_id IN('.implode(',', $ids['feature_ids']).')
                            AND v.id IN('.implode(',', $ids['value_ids']).')
                            AND p.type_id = tf.type_id'
                            .$other_features_config_entry['where'];

                    $other_features_sql .= self::$features_clause[self::$set_id];

                    $other_features_sqls[] = $other_features_sql;
                }

                $type_sql = implode(' UNION ', $other_features_sqls);
            }

            $type_sqls[] = $type_sql;
        }

        $common_sql = implode(' UNION ', $type_sqls).' ORDER BY feature_type, feature_sort, feature_id, value_sort';

        $processed = $model->query($common_sql)->fetchAll();

        //group feature values by feature type
        $raw = $processed;
        $processed = array();
        foreach ($raw as $value) {
            if (!isset($processed[$value['feature_type']])) {
                $processed[$value['feature_type']] = array();
            }
            $processed[$value['feature_type']][] = $value;
        }

        /**
         * Integration with "Tag editor" plugin: Apply custom tag URLs.
         */
        if (ifset($features_config['tags']['link_values']) && isset($processed['extra'])) {
            self::tageditorPluginUpdateTagUrls($processed);
        }

        //group feature values and associated data by product id
        $raw = $processed;
        $processed = array();
        foreach ($raw as $type => $values) {
            if (!isset($processed[$type])) {
                $processed[$type] = array();
            }
            foreach ($values as $value) {
                $processed[$type][$value['product_id']][] = $value;
            }
        }

        //prepare data for passing to template
        $raw = $processed;
        $processed = array();

        foreach ($raw as $type => $products) {
            foreach ($products as $product_id => $values) {
                foreach ($values as $value) {
                    $multi = preg_match('/(.+)\.\d+$/', $value['feature_code'], $m); //value * value [* value] ?
                    $code = $multi ? $m[1] : $value['feature_code'];
                    if (!isset($processed[$product_id][$code])) {    //create feature array with 1st value

                        //avoiding duplicate values
                        if ($value['value_id'] !== null
                        && isset($processed[$product_id][$code]['all_values'])
                        && in_array($value['value_id'], $processed[$product_id][$code]['all_values'])) {
                            continue;
                        }

                        //name
                        if (ifset($features_config[$value['feature_id']]['name'])) {
                            $feature_name = $features_config[$value['feature_id']]['name'];
                        } else {
                            switch ($value['feature_id']) {
                                case 'skus':
                                    $feature_name = self::loc('SKU');
                                    break;
                                case 'tags':
                                    $feature_name = self::loc('Tag');
                                    break;
                                case 'categories':
                                    $feature_name = self::loc('Category');
                                    break;
                                case 'pages':
                                    $feature_name = self::loc('Pages');
                                    break;
                                default:
                                    $feature_name = $multi ? preg_replace('/\.\d+$/', '', $value['feature_name']) : $value['feature_name'];
                            }
                        }

                        //value
                        if ($type == 'range') {
                            $value['value'] = $value['begin'];
                        }
                        $v = self::formatValue($value, $type, $features_config);
                        if ($value['unit'] && $type != 'range' && !$multi) {
                            $v .= '&nbsp;'.self::locUnit($value);
                        }
                        if (ifset($features_config[$value['feature_id']]['link_values']) || $value['feature_id'] == 'pages') {
                            $v = '<a href="'.self::getValueUrl($value['feature_id'], $value['url']).'">'.$v.'</a>';
                        }

                        //delimiter
                        if ($multi) {
                            $delimiter = ' &times; ';
                        } else {
                            switch ($type) {
                                case 'color':
                                    $delimiter = '<br>';
                                    break;
                                case 'range':
                                    $delimiter = ' &mdash; ';
                                    break;
                                default:
                                    $delimiter = ', ';
                            }
                        }

                        $processed[$product_id][$code] = array(
                            'id'           => $value['feature_id'],
                            'code'         => $value['feature_code'],
                            'name'         => $feature_name,
                            'data_sort'    => $value['data_sort'],
                            'feature_sort' => $value['feature_sort'],
                            'type'         => $multi ? 'multi' : $type,
                            'f_type'       => $value['f_type'],
                            'values'       => array($v),
                            'all_values'   => array(),
                            'unit'         => $value['unit'],
                            'delimiter'    => $delimiter,
                            'options'      => ifset($features_config[$value['feature_id']]),
                        );
                        if ($value['value_id'] !== null) {
                            $processed[$product_id][$code]['all_values'][] = $value['value_id'];
                        }

                        if ($type == 'range') {
                            $value['value'] = $value['end'];
                            $processed[$product_id][$code]['values'][] = self::formatValue($value, $type, $features_config);
                        }
                    } else {    //add other values
                        if (isset($features_config[$value['feature_id']]['values_limit'])
                        && $features_config[$value['feature_id']]['values_limit'] > 0
                        && count($processed[$product_id][$code]['values']) == $features_config[$value['feature_id']]['values_limit']) {
                            $values_key = 'remaining_values';
                        } else {
                            $values_key = 'values';
                        }

                        //avoiding duplicate values
                        if ($value['value_id'] !== null
                        && isset($processed[$product_id][$code]['all_values'])
                        && in_array($value['value_id'], $processed[$product_id][$code]['all_values'])) {
                            continue;
                        }

                        $v = self::formatValue($value, $type, $features_config);

                        if ($value['unit']
                        && !in_array($processed[$product_id][$code]['type'], array('range', 'multi'))) {
                            $v .= '&nbsp;'.self::locUnit($value);
                        }

                        if (ifset($features_config[$value['feature_id']]['link_values']) || $value['feature_id'] == 'pages') {
                            $v = '<a href="'.self::getValueUrl($value['feature_id'], $value['url']).'">'.$v.'</a>';
                        }

                        $processed[$product_id][$code][$values_key][] = $v;
                        //to control duplicate values
                        if ($value['value_id'] !== null) {
                            $processed[$product_id][$code]['all_values'][] = $value['value_id'];
                        }

                        if (!ifset($features_config[$value['feature_id']]['name'])) {
                            switch ($value['feature_id']) {
                                case 'skus':
                                    $processed[$product_id][$code]['name'] = self::loc('SKUs');
                                    break;
                                case 'tags':
                                    $processed[$product_id][$code]['name'] = self::loc('Tags');
                                    break;
                                case 'categories':
                                    $processed[$product_id][$code]['name'] = self::loc('Categories');
                                    break;
                                case 'pages':
                                    $processed[$product_id][$code]['name'] = self::loc('Pages');
                                    break;
                            }
                        }
                    }
                }
            }
        }

        //sort features for each product as set up in product's type settings
        static $sort_function;
        if (empty($sort_function)) {
            $sort_function = create_function(
                '$a, $b',
                'if ($a["data_sort"] != $b["data_sort"]) {
                    return $a["data_sort"] < $b["data_sort"] ? -1 : 1;
                } else {
                    if ($a["feature_sort"] != $b["feature_sort"]) {
                        return $a["feature_sort"] < $b["feature_sort"] ? -1 : 1;
                    } else {
                        return $a["id"] < $b["id"] ? -1 : 1;
                    }
                }'
            );
        }
        foreach ($processed as &$features) {
            usort($features, $sort_function);
        }
        unset($features);

        //prepare data to simplify template
        foreach ($processed as &$features) {
            foreach ($features as &$feature) {
                //class names
                $classes = array();
                if ($feature['type'] == 'divider') {
                    $classes[] = 'divider';
                }
                if (ifset($feature['options']['class_name'])) {
                    $classes[] = $feature['options']['class_name'];
                }
                $feature['class_names'] = implode(' ', $classes);

                //values
                if ($feature['type'] == 'divider') {
                    $feature['values'] = '';
                } else {
                    if (in_array($feature['type'], array('range', 'multi'))) {
                        $feature['values'] = implode($feature['delimiter'], $feature['values']);
                        if ($feature['unit']) {
                            $feature['values'] .= '&nbsp;'.self::locUnit($feature);
                        }
                    } else {
                        $values_delimiter = ifset($feature['options']['delimiter'], $feature['delimiter']);
                        $feature['values'] = implode($values_delimiter, $feature['values']);
                        if (ifset($feature['options']['hide_remaining']) && ifset($feature['remaining_values'])) {
                            $feature['values'] .= '<span class="remaining hidden">'.$values_delimiter.implode($values_delimiter, $feature['remaining_values']).'</span>';
                            $feature['values'] .= '<span class="listfeatures-show-all">';
                            if ($feature['type'] == 'color'
                            && !isset($feature['options']['delimiter'])
                            && !isset($feature['options']['remaining_indicator'])) {
                                $feature['values'] .= '<br>';
                            }
                            $feature['values'] .= ifset($feature['options']['remaining_indicator'], '...');
                            $feature['values'] .= '</span>';
                        }
                    }
                    $feature['values'] = '<div class="listfeatures-values">'.$feature['values'].'</div>';
                }

                //remove auxiliary properties
                foreach (array_keys($feature) as $property) {
                    if (!in_array($property, array('name', 'code', 'type', 'values', 'class_names'))) {
                        unset($feature[$property]);
                    }
                }
            }
        }
        unset($feature, $features);

        return $processed;
    }

    private static function formatValue($value, $type, $set_config)
    {
        static $locale_decimal_point;

        if (empty($locale_decimal_point)) {
            $locale_info = waLocale::getInfo(wa()->getLocale());
            $locale_decimal_point = ifset($locale_info['decimal_point'], '.');
        }

        if ($type == 'color') {
            $color = new shopColorValue(array(
                'code'  => $value['code'],
                'value' => $value['value']
            ));
            $display_mode = ifset($set_config[$value['feature_id']]['color_display_mode'], 'html');
            return in_array($display_mode, array('html', 'icon')) ? str_replace('<i ', '<i title="'.$color->value.'" ', $color->$display_mode) : $color->$display_mode;
        } elseif ($type == 'boolean') {
            return $value['value'] ? _w('Yes') : _w('No');
        } elseif (is_numeric($value['value'])
        && in_array($type, array('dimension', 'double', 'range'))
        && $locale_decimal_point != '.') {
            return $value['value'] = str_replace('.', $locale_decimal_point, $value['value']);
        } else {
            return $value['value'];
        }
    }

    private static function tageditorPluginUpdateTagUrls(&$values)
    {
        $tageditor = wa('shop')->getConfig()->getPluginInfo('tageditor');
        if (isset($tageditor['version']) && version_compare($tageditor['version'], '1.6.2', '>=')) {
            if (method_exists('shopTageditorPlugin', 'tags')) {
                $tags = array();
                foreach ($values['extra'] as $v) {
                    if ($v['feature_code'] == '%tags%' && !isset($tags[$v['value']])) {
                        $tags[$v['value']] = $v['url'];
                    }
                }
                if ($tags) {
                    $tags = shopTageditorPlugin::tags($tags);
                    foreach ($values['extra'] as &$v) {
                        if (isset($tags[$v['value']]['uri_name'])) {
                            $v['url'] = $tags[$v['value']]['uri_name'];
                        }
                    }
                    unset($v);
                }
            }
        }
    }
}
