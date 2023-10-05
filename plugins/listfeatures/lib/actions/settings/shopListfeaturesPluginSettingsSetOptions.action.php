<?php

class shopListfeaturesPluginSettingsSetOptionsAction extends waViewAction
{
    public function execute()
    {
        $settlement_hash = waRequest::post('settlement');
        $set_id = waRequest::post('set_id');
        $set_config = shopListfeaturesPluginHelper::getSettlementConfig($settlement_hash, $set_id);
        $hash_settlements = shopListfeaturesPluginHelper::getHashSettlements();

        $set_options = array();

        //comments box
        $set_options[] = array(
            'id'           => 'description',
            'title'        => _wp('Set description'),
            'control_type' => waHtmlControl::TEXTAREA,
            'params'       => array(
                'value' => ifset($set_config['options']['description']),
                'description'  => _wp('Enter arbitrary description to remember where this set is used. It will not be shown to customers.'),
            )
        );

        //displayed data
        $displayed_data = array_intersect(
            array('skus', 'categories', 'tags', 'pages'),
            $features = array_keys(ifset($set_config['features'], array()))
        );
        if (array_filter($features, create_function('$id', 'return is_numeric($id);'))) {
            $displayed_data[] = 'features';
        }
        $params = array(
            'value'   => array_fill_keys($displayed_data, 1),
            'options' => array()
        );
        foreach (array(
                array(
                    'value' => 'features',
                    'title' => _wp('Features'),
                ),
                array(
                    'value' => 'skus',
                    'title' => _wp('SKUs'),
                ),
                array(
                    'value' => 'tags',
                    'title' => _wp('Tags'),
                ),
                array(
                    'value' => 'categories',
                    'title' => _wp('Categories'),
                ),
                array(
                    'value' => 'pages',
                    'title' => _wp('Product pages'),
                ),
            ) as $option) {
            $option['sort'] = ifset(
                $set_config['options']['data_sort'][$option['value']],
                count($params['options']) - 1
            );
            $params['options'][] = $option;
        }
        usort(
            $params['options'],
            create_function(
                '$a, $b',
                'if ($a["sort"] != $b["sort"]) {
                    return $a["sort"] < $b["sort"] ? -1 : 1;
                }'
            )
        );
        $set_options[] = array(
            'id'           => 'features',
            'class_name'   => 'data',
            'title'        => _wp('Displayed data'),
            'control_type' => waHtmlControl::GROUPBOX,
            'params'       => $params,
        );

        //templates
        $templates = shopListfeaturesPluginHelper::getTemplates();
        $params = array(
            'value' => ifset($set_config['options']['template'], 'default'),
            'description' => _wp('Choose default or create your own HTML template to display product features'),
            'options' => array(
                array(
                    'value' => 'default',
                    'title' => _wp('Default template'),
                ),
            )
        );
        foreach ($templates as $id => $template) {
            $params['options'][] = array(
                'value' => $id,
                'title' => sprintf(_wp('Template %s'), $id),
            );
        }
        $params['options'][] = array(
            'value' => '',
            'title' => _wp('Add new...'),
        );
        $set_options[] = array(
            'id'           => 'template',
            'title'        => _wp('Template'),
            'control_type' => waHtmlControl::SELECT,
            'params'       => $params,
        );

        //class name
        $set_options[] = array(
            'id'         => 'class_name',
            'title'      => _wp('Class name'),
            'control_type' => waHtmlControl::INPUT,
            'params'       => array(
                'value' => ifset($set_config['options']['class_name']),
                'description' => _wp('CSS class applied to entire features block for this set'),
            ),
        );

        //show features for disabled SKUs
        $set_options[] = array(
            'id'           => 'show_disabled_skus_data',
            'title'        => _wp('Show properties of disabled SKUs'),
            'control_type' => waHtmlControl::CHECKBOX,
            'params'       => array(
                'value'    => 1,
                'checked'  => (bool) (int) ifset($set_config['options']['show_disabled_skus_data']),
                'description' => sprintf(
                    _wp('Applicable only to products which were set up using option "<b>%s</b>" rather than "%s".'),
                    _w('Selectable parameters'),
                    _w('Purchase options')
                )
                .'<br>'._wp('By default, properties of disabled SKUs are <b>not displayed</b> in product lists.')
                .'<br>'
                .'<br>'
                .sprintf(
                    _wp('If you do not sell products using option "%s" or if you want to reduce the load on your database server, then <strong>enable</strong> this setting'),
                    _w('Selectable parameters')
                )
                .' '
                .sprintf(
                    _wp('and <strong>disable</strong> setting "%s" below.'),
                    _wp('Hide properties of SKUs that are out of stock')
                ),
            ),
        );

        //hide features for SKUs out of stock
        $set_options[] = array(
            'id'           => 'hide_outofstock_skus_data',
            'title'        => _wp('Hide properties of SKUs that are out of stock'),
            'control_type' => waHtmlControl::CHECKBOX,
            'params'       => array(
                'value'    => 1,
                'checked'  => (bool) (int) ifset($set_config['options']['hide_outofstock_skus_data']),
                'description' => sprintf(
                    _wp('Applicable only to products which were set up using option "<b>%s</b>" rather than "%s".'),
                    _w('Selectable parameters'),
                    _w('Purchase options')
                )
                .'<br>'._wp('By default, properties of SKUs that are out of stock are <b>displayed</b> in product lists.')
                .'<br>'
                .'<br>'
                .sprintf(
                    _wp('If you do not sell products using option "%s" or if you want to reduce the load on your database server, then <strong>disable</strong> this setting'),
                    _w('Selectable parameters')
                )
                .' '
                .sprintf(
                    _wp('and <strong>enable</strong> setting "%s" above.'),
                    _wp('Show properties of disabled SKUs')
                ),
            ),
        );

        //features
        $routes = shopListfeaturesPluginHelper::getSettlements();
        $settlement = ifset($hash_settlements[$settlement_hash]);
        $product_types = ifset($routes[$settlement]) === 0 ? null : $routes[$settlement];
        $where = $product_types ? 'WHERE tf.type_id IN (i:type_ids)' : '';
        $sql = "
            SELECT f.*
            FROM shop_feature f
            JOIN shop_type_features tf ON tf.feature_id = f.id
            $where
            GROUP BY f.id
            ORDER BY f.name, f.code
        ";
        $model = new waModel();
        $features = $model->query($sql, array('type_ids' => $product_types))->fetchAll();
        $params = array(
            'value' => ifset($set_config['features']) ? array_fill_keys(array_keys($set_config['features']), 1) : null,
        );
        foreach ($features as $feature) {
            $params['options'][] = array(
                'value'       => $feature['id'],
                'title'       => $feature['name'],
                'description' => $feature['code'],
            );
        }
        $set_options[] = array(
            'id'           => 'features',
            'class_name'   => 'native',
            'title'        => _wp('Displayed product features'),
            'control_type' => waHtmlControl::GROUPBOX,
            'params'       => $params,
        );

        $control_params = array(
            'description_wrapper' => '<br><span class="hint">%s</span>',
        );
        foreach ($set_options as &$set_option) {
            $set_option['html'] = waHtmlControl::getControl($set_option['control_type'], $set_option['id'], ifset($set_option['params'], array()) + $control_params);
        }
        unset($set_option);

        $this->view->assign('set_options', $set_options);
    }
}
