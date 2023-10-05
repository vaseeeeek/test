<?php

class shopTageditorPluginBackendTagOptionsAction extends waViewAction
{
    public function execute()
    {
        $tag = waRequest::post('tag', array(), waRequest::TYPE_ARRAY);

        $default_values = shopTageditorPluginHelper::getDefaultValues();
        $tag_data = shopTageditorPluginModels::tag()->getByName($tag['name']);

        $_tag_data = ifempty($tag_data, $default_values);
        $field_aliases = shopTageditorPluginHelper::getFieldAliases($_tag_data);
        unset($_tag_data);

        $controls_config = array(
            'url' => array(
                'control_type' => waHtmlControl::INPUT,
                'params' => array(
                    'title' => _wp('Custom URL'),
                    'description' => _wp(
                        'To apply custom URLs to tags in your storefront, make changes to your design theme as described on '
                        .'<a href="http://www.webasyst.com/store/plugin/shop/tageditor/">plugin page in Webasyst Store</a>.'
                    ),
                    'custom_control_wrapper' => '<div class="field block"><div class="name">%s</div><div class="value">'
                        .'%s <a href="" class="inline-link small suggest-url"><b><i>'._wp('suggest URL').'</i></b></a>%s</div></div>',
                ),
            ),
            'title' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _wp('Tag page title (H1)'),
                ),
                'default_values' => true,
            ),
            'description' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _wp('SEO text'),
                ),
                'wysiwyg' => true,
                'default_values' => true,
            ),
            'description_extra' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _wp('Extra SEO text'),
                    'description' => _wp(
                        'To show extra SEO text on a tag page, add <strong><tt>{shopTageditorPlugin::seoTextExtra()}</tt></strong> to <em>search.html</em> file in'
                        .' your design editor.'
                    ),
                ),
                'wysiwyg' => true,
                'default_values' => true,
            ),
            'meta_title' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _w('TITLE'),
                ),
                'default_values' => true,
            ),
            'meta_keywords' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _w('META keywords'),
                ),
                'default_values' => true,
            ),
            'meta_description' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _w('META description'),
                ),
                'default_values' => true,
            ),
            'og_title' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _w('OpenGraph title'),
                ),
                'use_field_alias' => array(
                    'title',
                    'meta_title',
                ),
                'default_values' => true,
            ),
            'og_description' => array(
                'control_type' => waHtmlControl::TEXTAREA,
                'params' => array(
                    'title' => _w('OpenGraph description'),
                ),
                'use_field_alias' => array(
                    'meta_description',
                ),
                'default_values' => true,
            ),
            'sort_products' => array(
                'control_type' => waHtmlControl::SELECT,
                'params' => array(
                    'title' => _wp('Products sort order'),
                    'options' => array(
                        '0'                    => _wp('-- select --'),
                        'name asc'             => _wp('By name'),
                        'price desc'           => _wp('Most expensive'),
                        'price asc'            => _wp('Least expensive'),
                        'rating desc'          => _wp('Highest rated'),
                        'rating asc'           => _wp('Lowest rated'),
                        'total_sales desc'     => _wp('Best sellers'),
                        'total_sales asc'      => _wp('Worst sellers'),
                        'count desc'           => _wp('In stock'),
                        'create_datetime desc' => _wp('Date added'),
                        'stock_worth desc'     => _wp('Stock net worth'),
                    ),
                ),
            ),
        );

        $control_params = array(
            'namespace'           => 'data',
            'control_wrapper'     => '<div>%s</div>%s',
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
        );

        $can_save_default_values = wa()->getUser()->getRights('shop', 'tageditor_save_default_values');

        //add auxiliary controls to main controls
        $controls = array();

        foreach ($controls_config as $name => $control) {
            if (array_key_exists($name, $field_aliases)) {
                $control['params']['style'] = 'display: none;';
                $control['params']['value'] = '';
            }

            $control_wrapper = $control_params['control_wrapper'];

            //add field alias controls
            if (!empty($control['use_field_alias'])) {
                $use_field_alias_options = array(
                    '' => '',
                );
                foreach ($control['use_field_alias'] as $field_name) {
                    $use_field_alias_options[$field_name] = $controls_config[$field_name]['params']['title'];
                }

                $use_field_alias_classes = array(
                    'tageditor-user-field-alias-list'
                );
                if (!array_key_exists($name, $field_aliases)) {
                    $use_field_alias_classes[] = 'hidden';
                }

                $control_wrapper .= waHtmlControl::getControl(waHtmlControl::CHECKBOX, '', array(
                    'label' => _wp('use the value of another field'),
                    'value' => 1,
                    'checked' => array_key_exists($name, $field_aliases),
                    'class' => 'tageditor-user-other-field-show',
                    'control_wrapper' => '<div class="small black">%s%s'.waHtmlControl::getControl(waHtmlControl::SELECT, "use_field_alias[$name]", array(
                        'options' => $use_field_alias_options,
                        'class' => implode(' ', $use_field_alias_classes),
                        'value' => ifempty($field_aliases[$name]),
                    )).'</div>',
                ));
            }

            if (!empty($control['default_values'])) {
                $control['params']['value'] = ifset($tag_data[$name], ifset($default_values[$name]));
                if (shopTageditorPluginHelper::fieldIsAlias($control['params']['value'])) {
                    $control['params']['value'] = '';
                }

                if (!empty($control['wysiwyg'])) {
                    if ($can_save_default_values) {
                        $default_values_checkbox = waHtmlControl::getControl(waHtmlControl::CHECKBOX, "default_values[$name]", array(
                            'label' => _wp('default value'),
                            'control_wrapper' => '<div class="small black">%s%s</div>',
                        ));
                    }
                    $default_values_checkbox = ifempty($default_values_checkbox);
                    $this->view->assign(compact('default_values_checkbox'));
                    $wysiwyg_control_wrapper_template = '<br><br>
<div class="wa-editor-core-wrapper s-editor-core-wrapper">
    <ul class="wa-editor-wysiwyg-html-toggle s-wysiwyg-html-toggle">
        <li class="selected"><a class="wysiwyg" href="#">[s`WYSIWYG`]</a></li>
        <li><a class="html" href="#">HTML</a></li>
    </ul>
    <div>%s</div>
</div>
<div>{$default_values_checkbox}%s</div>';
                    $control_wrapper = $this->view->fetch('string:'.$wysiwyg_control_wrapper_template);
                    $this->view->clearAllAssign();
                } else {
                    $control_wrapper .= waHtmlControl::getControl(waHtmlControl::CHECKBOX, "default_values[$name]", array(
                        'label' => _wp('default value'),
                        'control_wrapper' => '<div class="small black">%s%s</div>'
                    ));
                }
            } elseif ($name == 'url') {
                $control['params']['value'] = ifempty($tag_data['url'], $tag['name']);
            } else {
                $control['params']['value'] = ifset($tag_data[$name]);
            }

            $control['params']['control_wrapper'] = '<div class="field block"><div class="name">%s</div><div class="value">'.$control_wrapper.'</div></div>';
            $controls[$name] = waHtmlControl::getControl($control['control_type'], $name, array_merge($control_params, $control['params']));
        }

        $controls = implode('', $controls);
        $this->view->assign(compact('tag', 'controls'));
    }
}
