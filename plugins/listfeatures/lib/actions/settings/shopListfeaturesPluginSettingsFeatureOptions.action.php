<?php

class shopListfeaturesPluginSettingsFeatureOptionsAction extends waViewAction
{
    public function execute()
    {
        $control_params = array(
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper'     => '<div class="name">%s</div><div class="value">%s %s</div>',
        );

        $controls = array();
        $control_settings = $this->getControlsSettings();
        foreach ($control_settings as $name => $row) {
            $row = array_merge($row, $control_params);
            $controls[] = waHtmlControl::getControl($row['control_type'], "options[$name]", $row);
        }

        $this->view->assign('controls', $controls);
    }

    private function getControlsSettings()
    {
        $settlement_hash = waRequest::request('settlement');
        $set_id = waRequest::request('set_id');
        $feature_id = waRequest::request('feature_id');

        $config = shopListfeaturesPluginHelper::getSettlementConfig($settlement_hash, $set_id, 'features');
        $config = ifset($config[$feature_id]);

        $settings = array();

        //feature_name
        $settings['name'] = array(
            'control_type' => waHtmlControl::INPUT,
            'title'        => _wp('Feature name'),
            'value'        => ifset($config['name']),
            'placeholder'  => waRequest::post('feature_name'),
            'description'  => _wp('Enter custom name for this feature to be displayed in product lists. HTML is allowed.'),
        );

        //class name
        $settings['class_name'] = array(
            'control_type' => waHtmlControl::INPUT,
            'title'        => _wp('Feature class name'),
            'value'        => ifset($config['class_name']),
            'description'  => _wp('CSS class name to be applied to this feature\'s row'),
        );

        //color features
        if (shopListfeaturesPluginHelper::isFeatureType($feature_id, 'color')) {
            //show color names
            $settings['color_display_mode'] = array(
                'control_type' => waHtmlControl::SELECT,
                'title'        => _wp('Display mode'),
                'value'        => ifset($config['color_display_mode']),
                'description'  => _wp('Select how color values must be displayed.'),
                'options'      => array(
                    'html'  => _wp('Color markers and names'),
                    'icon'  => _wp('Only color markers'),
                    'value' => _wp('Only color names'),
                ),
            );
        }

        //link features
        if (shopListfeaturesPluginHelper::isFeatureType($feature_id, 'link')) {
            //show values as links
            $settings['link_values'] = array(
                'control_type' => waHtmlControl::CHECKBOX,
                'title'        => _wp('Show values as links'),
                'value'        => 1,
                'checked'      => (bool) ifset($config['link_values'], false),
                'description'  => _wp('Show each value as link to appropriate product listing page.'),
            );

            if (shopListfeaturesPluginHelper::isFeatureType($feature_id, 'filter')) {
                $listfeatures_feature_model = new shopListfeaturesPluginFeatureModel();
                $feature_meta_data = $listfeatures_feature_model->getByField(array(
                    'settlement' => $settlement_hash,
                    'set_id'     => $set_id,
                    'feature_id' => $feature_id,
                ));

                //META keywords
                $settings['meta_keywords'] = array(
                    'control_type' => waHtmlControl::TEXTAREA,
                    'title'        => _wp('META keywords for product listing pages'),
                    'value'        => ifset($feature_meta_data['meta_keywords']),
                    'placeholder'  => _wp('E.g.:')
                        ."\n"
                        .'{$wa->shop->settings(\'name\')}, {$feature.name}, {$feature.value}',
                    'description'  => _wp('Enter META keywords for product listings displayed via links to this feature\'s values.')
                        .'<br>'
                        ._wp('Smarty syntax is supported. E.g.:')
                        .'<br>'
                        ._wp('<code>{$wa-&gt;shop-&gt;settings(\'name\')}</code> &mdash; your store name from general settings')
                        .'<br>'
                        .'<strong>'._wp('Additional supported variables').'</strong>'
                        .'<br>'
                        ._wp('<code>{$feature.name}</code> &mdash; feature name')
                        .'<br>'
                        ._wp('<code>{$feature.value}</code> &mdash; feature value')
                        .'<br>'
                        ._wp('These variables are also available for “META description” field below.'),
                );

                //META description
                $settings['meta_description'] = array(
                    'control_type' => waHtmlControl::TEXTAREA,
                    'title'        => _wp('META description for product listing pages'),
                    'value'        => ifset($feature_meta_data['meta_description']),
                    'placeholder'  => _wp('E.g.:')
                        ."\n"
                        ._wp('Products with value “{$feature.value}” of feature “{$feature.name}”'),
                );

                //select product order
                $settings['product_order'] = array(
                    'control_type' => waHtmlControl::SELECT,
                    'title'        => _wp('Sort products'),
                    'value'        => ifempty($config['product_order'], 'p.name ASC'),
                    'description'  => _wp('Select the order of products displayed via links to this feature\'s values.'),
                    'options'      => array(
                        'p.name ASC'                 => _wp('By name'),
                        'p.price ASC'                => _wp('Least expensive'),
                        'p.price DESC'               => _wp('Most expensive'),
                        'p.rating DESC'              => _wp('Highest rated'),
                        'p.rating ASC'               => _wp('Lowest rated'),
                        'p.total_sales DESC'         => _wp('Best sellers'),
                        'p.total_sales ASC'          => _wp('Worst sellers'),
                        'IF(p.count > 0, 1, 0) DESC' => _wp('In stock'),
                        'p.create_datetime DESC'     => _wp('Date added'),
                    ),
                );
            }
        }

        //checkboxes features
        if (shopListfeaturesPluginHelper::isFeatureType($feature_id, 'multiple')) {
            //values delimiter
            $settings['delimiter'] = array(
                'control_type' => waHtmlControl::INPUT,
                'title'        => _wp('Value delimiter'),
                'value'        => ifset($config['delimiter']),
                'placeholder'  => shopListfeaturesPluginHelper::isFeatureType($feature_id, 'color') ? '<br>' : ', ',
                'description'  => _wp('What must be displayed between features values. HTML is accepted.'),
            );

            //values limit
            $settings['values_limit'] = array(
                'control_type' => waHtmlControl::INPUT,
                'title'        => _wp('Values limit'),
                'value'        => ifset($config['values_limit']),
                'description'  => _wp('Maximum number of values to be displayed'),
            );

            //hide remaining values
            $settings['hide_remaining'] = array(
                'control_type' => waHtmlControl::CHECKBOX,
                'title'        => _wp('Hide remaining values'),
                'value'        => 1,
                'checked'      => (bool) ifset($config['hide_remaining']),
                'description'  => _wp(
                    'Enable to hide remaining values with a CSS class. Users can view hidden values using the “Show all” link, for which you can specify custom'
                        .' text below.<br> Or keep this option disabled to simply skip remaining values.'
                ),
            );

            //show all link
            $settings['remaining_indicator'] = array(
                'control_type' => waHtmlControl::INPUT,
                'title'        => _wp('"Show all" link'),
                'value'        => ifset($config['remaining_indicator']),
                'placeholder'  => shopListfeaturesPluginHelper::isFeatureType($feature_id, 'color') ? '<br>...' : '...',
                'description'  => _wp(
                    'Enter custom HTML code to be displayed instead of remaining values; e.g., <b>&amp;darr;</b> or <b>&lt;br&gt;show all</b>. A click on this'
                        .' item will show all remaining values. Default link text is “...”.'
                ),
            );
        }

        return $settings;
    }
}
