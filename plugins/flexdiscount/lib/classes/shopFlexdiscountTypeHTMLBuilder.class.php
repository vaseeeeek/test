<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountTypeHTMLBuilder extends shopFlexdiscountConditions
{

    /**
     * Generate HTML of all discount conditions
     *
     * @param array $conditions
     * @param int $level
     * @return string
     */
    public static function buildConditionHTMLTree($conditions, $level = 0)
    {
        $html = "<div class='condition-block" . ($level % 2 == 1 ? ' even' : '') . (!empty($conditions['conditions']) && count($conditions['conditions']) > 1 ? " show-cond-op" : "") . (isset($conditions['group_op']) && $conditions['group_op'] == 'or' ? " s-or" : "") . "'>";
        if (isset($conditions['group_op']) && !empty($conditions['conditions']) && count($conditions['conditions']) > 1) {
            $html .= '<div class="cond-op margin-block">
                        <select name="cond-op" style="width: 354px;">
                            <option class="op-and" value="and"' . ($conditions['group_op'] == 'and' ? ' selected' : '') . '>' . _wp("All conditions return true") . '</option>
                            <option class="op-or" value="or"' . ($conditions['group_op'] == 'or' ? ' selected' : '') . '>' . _wp("Any of condition returns true") . '</option>
                        </select>
                    </div>';
        }
        if ($level > 0) {
            $html .= '<a class="js-action" href="#/delete/conditionBlock/" style="position: absolute; top: -5px; right: -5px;" title="delete"><i class="icon16 delete"></i></a>';
        }
        $html .= "<div class='conditions" . (!empty($conditions['conditions']) && count($conditions['conditions']) > 1 ? ' tree' : '') . "'>";
        if (!empty($conditions['conditions'])) {
            foreach ($conditions['conditions'] as $condition) {
                $html .= "<div class='condition'>";
                // Выбираем, что строить: группу или условие
                if (isset($condition['group_op'])) {
                    $html .= self::buildConditionHTMLTree($condition, $level + 1);
                } else {
                    $html .= self::buildConditionHTML($condition);
                    $html .= '<span class="condition-text s-delete"><a href="#/delete/condition/" class="js-action block half-padded" title="' . _wp('Delete') . '"><i class="icon16 delete"></i></a></span>';
                }
                $html .= "</div>";
            }
        }
        $html .= "</div>"; // .conditions
        $html .= '<a class="js-action" href="#/show/condition/" title="' . _wp('Add condition') . '"><i class="icon16 add"></i> ' . _wp('Add condition') . '</a>';
        $html .= "</div>"; // .condition-block

        return $html;
    }

    /**
     * Generate HTML of all targets
     *
     * @param array $targets
     * @return string
     */
    public static function buildTargetHTML($targets)
    {
        $html = "";

        if ($targets) {
            foreach ($targets as $k => $target) {
                $html .= '<div class="target-row margin-block bottom">';
                $html .= '<div class="condition-text">
                          <select name="data[target]" class="target-chosen' . (isset($target['condition']) ? " hide-after-init" : "") . '" style="width: 400px;">';
                foreach (self::$targets as $t_id => $t) {
                    $html .= '<option value="' . $t_id . '"' . ($target['target'] == $t_id ? ' selected' : '') . '>' . $t . '</option>';
                }
                $html .= "</select>";
                $html .= '<div class="target-block' . (isset($target['condition']) ? " s-target-" . $target['condition']['type'] : " hidden") . '" style="' . (!isset($target['condition']) ? 'display: none;' : 'display: inline-block;') . '">';
                if (isset($target['condition'])) {
                    $html .= "<div class='condition'>";
                    $html .= self::buildConditionHTML($target['condition'], 'target');
                    $html .= "</div>";
                }
                $html .= "</div>"; // .target-block
                $html .= '<a href="#/edit/target/" class="js-action inline-link"' . (!isset($target['condition']) ? " style='display: none'" : "") . '><i class="icon16 edit"></i> <b>' . _wp('edit') . '</b></a>';
                $html .= "</div>"; // .condition-text
                $html .= '<div class="condition-text s-details"' . ($target['target'] !== 'shipping' ? " style='display: inline-block'" : " style='display: none'") . '>
                                <select style="display: none;" data-placeholder="' . _wp('choose details if necessary') . '" class="details-select hidden">
                                    <option value=""></option>';
                foreach (self::$details as $d_id => $d) {
                    $html .= '<option value="' . $d_id . '"' . (!empty($target['details']['field']) && $target['details']['field'] == $d_id ? " selected" : "") . '>' . $d . '</option>';
                }
                $html .= "</select>";
                $html .= '<div class="details-block inline-block ' . (!empty($target['details']['field']) && !empty(self::$details[$target['details']['field']]) ? "s-detail-" . $target['details']['field'] : "hidden") . '" style="' . (!empty($target['details']['field']) && !empty(self::$details[$target['details']['field']]) ? "display: inline-block" : "display: none") . '">';
                $html .= self::buildTargetDetailsHTML($target);
                $html .= "</div>"; // .details-block
                $html .= '<a href="#/edit/discountDetails/" class="js-action inline-link" style="vertical-align: middle;"><i class="icon16 edit"></i> <b>' . _wp('clarify') . '</b></a>';
                $html .= "</div>"; // .condition-text
                if ($k > 0) {
                    $html .= '<div class="condition-text"><a href="#/delete/target/" class="js-action" title="' . _wp('delete') . '"><i class="icon16 delete"></i></a></div>';
                }
                $html .= "</div>"; // .target-row
            }
            $html .= '<a href="#/add/target/" class="s-add-target js-action"><i class="icon16 add"></i> ' . _wp('Add target') . '</a>';
        }

        return $html;
    }

    /**
     * Generate HTML of target details
     *
     * @param array $target
     * @return string
     */
    private static function buildTargetDetailsHTML($target)
    {
        $html = "";
        if (!empty($target['details']['field']) && !empty(self::$details[$target['details']['field']])) {
            $field = $target['details']['field'];
            $value = $field !== 'multiple' ? waString::escapeAll($target['details']['value']) : $target['details']['value'];
            $price_select_html = " " . _wp('among') . " <select class='details-price-select ignore-chosen inherit'>
                <option value='price'" . (ifempty($target['details'], 'price_type', 'price') == 'price' ? ' selected' : '') . ">" . _wp('product prices') . "</option>
                <option value='compare_price'" . (ifempty($target['details'], 'price_type', 'price') == 'compare_price' ? ' selected' : '') . ">" . _wp('compare prices') . "</option>
            </select>";
            $html .= _wp(" to") . " ";
            if ($field == 'every') {
                $html .= _wp("every") . " <input name='details' type='text' value='" . $value . "' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('th similar product');
            } elseif ($field == 'cheapest') {
                $html .= "<input name='details' value='" . $value . "' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('cheapest products');
                $html .= $price_select_html;
            } elseif ($field == 'ncheapest' || $field == 'ncheapest2') {
                $html .= _wp("every") . " <input name='details' value='" . $value . "' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('th cheapest products') . ' ' . ($field == 'ncheapest' ? _wp('(in favor of the seller)') : _wp('(in favor of the buyer)'));
                $html .= $price_select_html;
            } else if ($field == 'expensive') {
                $html .= "<input name='details' type='text' value='" . $value . "' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('the most expensive products');
                $html .= $price_select_html;
            } else if ($field == 'nexpensive' || $field == 'nexpensive2') {
                $html .= _wp("every") . "<input name='details' type='text' value='" . $value . "' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('th the most expensive products') . ' ' . ($field == 'nexpensive' ? _wp('(in favor of the seller)') : _wp('(in favor of the buyer)'));
                $html .= $price_select_html;
            } else if ($field == 'subsiquent') {
                $html .= _wp("all subsequent similar products following by") . " <input name='details' value='" . $value . "' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('th product');
            } else if ($field == 'nsubsiquent') {
                $html .= "<input name='details' value='" . $value . "' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('th product and all subsequent products following by n-product (descending prices)');
                $html .= $price_select_html;
            } else if ($field == 'multiple') {
                $html .= " <input name='details' value='" . waString::escapeAll($value[0]) . "' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp('products from') . " <input name='details2' value='" . waString::escapeAll($value[1]) . "' type='text' onkeypress='$.flexdiscount.isValid(event, /[0-9]/);' class='width50px'> " . _wp("similar");
            }
            $html .= " <div class='condition-text'><a href='javascript:void(0)' onclick='$(this).closest(\".target-row\").find(\".details-block\").addClass(\"hidden\").hide()'><i class='icon16 no'></i></a></div> ";
        }
        return $html;
    }

    /**
     * Generate HTML of condition
     *
     * @param array $condition
     * @param string $scope
     * @return string
     */
    private static function buildConditionHTML($condition, $scope = 'condition')
    {
        static $instance = null;
        if (!$instance) {
            $instance = get_class();
        }
        $html = "";
        if (!empty(self::$types[$condition['type']])) {
            foreach (self::$types[$condition['type']] as $value) {
                if (is_array($value)) {
                    if (isset($value['type'])) {
                        $method_name = 'get' . ucfirst($value['type']) . 'FieldHTML';
                        if (method_exists($instance, $method_name)) {
                            if (!empty($value['scope']) && $value['scope'] !== $scope) {
                                continue;
                            }
                            $html .= self::$method_name($value, $condition);
                        }
                    } elseif (isset($value['control_type'])) {
                        $html .= waHtmlControl::getControl($value['control_type'], $value['name'], $value['params']);
                    } else {
                        $html .= self::addFieldText($value);
                    }
                } else {
                    $html .= self::addFieldText($value);
                }
            }
            $html .= '<input name="type" value="' . $condition['type'] . '" type="hidden">';
        }
        return $html;
    }

    private static function addFieldText($text)
    {
        return "<span class='condition-text'>" . $text . "</span>";
    }

    protected static function getCategoryFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . " style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['category'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopFlexdiscountHelper::getCategoriesTreeOptionsHtml(self::$types_data['category'], 0, $default);
        }
        $html .= "</select>";
        return $html;
    }

    private static function buildSelectHTML($params, $condition, $type)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data[$type])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopFlexdiscountHelper::getSelectOptionsHtml(self::$types_data[$type], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getSetFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'set');
    }

    protected static function getTypeFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'type');
    }

    protected static function getTagsFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'tags');
    }

    protected static function getUcatFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'ucat');
    }

    protected static function getShippingFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'shipping');
    }

    protected static function getPaymentFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'payment');
    }

    protected static function getCountryFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'country');
    }

    protected static function getParamsFieldHTML($params, $condition)
    {
        return self::buildSelectHTML($params, $condition, 'params');
    }

    protected static function getDynamicFieldHTML($params, $condition)
    {
        // Если перед нами массив значений
        $is_values = isset($params['dynamic_data_id']);
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '400px') . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . (!empty($params['data-value-url']) ? ' data-value-url="' . $params['data-value-url'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
        if (!empty(self::$types_data[$params['id']]['fields']) && !$is_values) {
            $html .= shopFlexdiscountHelper::getSelectOptionsHtml(self::$types_data[$params['id']]['fields'], $default);
        }
        $html .= "</select>";
        // Обрабатываем значения, если они существуют
        if ($is_values) {
            $html .= self::getDynamicValuesFieldHTML(array('id' => $condition['field'], 'values' => !empty(self::$types_data[$params['dynamic_data_id']]['values'][$condition['field']]) ? self::$types_data[$params['dynamic_data_id']]['values'][$condition['field']] : array()), $params, $default);
            $html .= '<div class="condition-text">';
            $html .= '<a href="#/reset/selection/" class="js-action s-reset-button" title="' . _wp('reset selection') . '"><i class="icon16 no"></i></a>';
            $html .= '</div>';
        }

        return $html;
    }

    protected static function getDynamicValuesFieldHTML($values, $params, $default)
    {
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '400px') . ' class="' . (!empty($params['class']) ? $params['class'] . ' ' : '') . 'dynamic-value dynamic-value-' . $values['id'] . '"' . ">";
        $html .= "<option value=''></option>";
        $html .= shopFlexdiscountHelper::getDynamicValuesHtml($values['values'], $values['id'], $default);
        $html .= "</select>";
        return $html;
    }

    protected static function getProductFieldHTML($params, $condition)
    {
        $html = "";
        $product_type = (!empty($condition['product_type']) ? $condition['product_type'] : 'product');
        $product = isset(self::$types_data['product'][$product_type][$condition[$params['name']]]) ? self::$types_data['product'][$product_type][$condition[$params['name']]]['name'] : $params['link'];
        if ($product_type == 'sku' && isset(self::$types_data['product'][$product_type][$condition[$params['name']]])) {
            $pdata = self::$types_data['product'][$product_type][$condition[$params['name']]];
            $product = waString::escapeAll($product) . ' (' . ($pdata['sku_name'] ? waString::escapeAll($pdata['sku_name']) : ($pdata['sku'] ? waString::escapeAll($pdata['sku']) : (!$pdata['sku_name'] && !$pdata['sku'] ? _wp('sku ID') . ': #' . $pdata['id'] : ''))) . ')';
        }

        $html .= '<div class="condition-text' . (!empty($params['can_reset']) ? ' has-reset' : '') . '">
                  <a href="#/open/conditionDialog/" class="js-action" data-id="product" data-source="?plugin=flexdiscount&module=dialog&action=getProducts" title="' . $params['link'] . '">' . $product . '</a>';
        $html .= '<a href="#/reset/dialogSelection/"' . (empty($params['can_reset']) || !$condition[$params['name']] ? ' style="display: none"' : '') . ' data-reset="' . $params['link'] . '" class="js-action s-reset-button" title="' . _wp('reset product') . '"><i class="icon16 no"></i></a>';
        $html .= '<input class="s-value-field" name="' . $params['name'] . '" value="' . $condition[$params['name']] . '" type="hidden">
                  <input class="s-type-field" name="product_type" value="' . $product_type . '" type="hidden">
                  </div>';
        return $html;
    }

    protected static function getFeatureFieldHTML($params, $condition)
    {
        // Если перед нами массив значений характеристик
        $is_values = isset($params['id']);
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '400px') . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
        if (!empty(self::$types_data['feature']['features']) && !$is_values) {
            $html .= shopFlexdiscountHelper::getFeaturesHtml(self::$types_data['feature']['features'], $default);
        }
        $html .= "</select>";

        // Обрабатываем значения характеристик, если они существуют
        if ($is_values && !empty(self::$types_data['feature']['values'][$condition['field']]['selectable'])) {
            $html .= self::getFeatureValuesFieldHTML(self::$types_data['feature']['values'][$condition['field']], $params, $default);
        }

        return $html;
    }

    protected static function getFeatureValuesFieldHTML($values, $params, $default)
    {
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '400px') . ' class="feature-value feature-value-' . $values['id'] . '"' . ">";
        $html .= "<option value=''></option>";
        $html .= shopFlexdiscountHelper::getFeaturesValuesHtml($values['values'], $values['id'], $default);
        $html .= "</select>";
        return $html;
    }

    protected static function getServicesFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '400px') . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['services'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            if ($params['name'] == 'field') {
                $html .= shopFlexdiscountHelper::getServicesHtml(self::$types_data['services'], $default);
            } else {
                $html .= shopFlexdiscountHelper::getServicesVariantsHtml(self::$types_data['services'], $default);
            }
        }
        $html .= "</select>";

        if ($params['name'] !== 'field') {
            $html .= '<div class="condition-text">';
            $html .= '<a href="#/reset/selection/" class="js-action s-reset-button" title="' . _wp('reset selection') . '"><i class="icon16 no"></i></a>';
            $html .= '</div>';
        }

        return $html;
    }

    protected static function getStocksFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'" . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['stocks'])) {
            $default = isset($condition[$params['name']]) ? (is_numeric($condition[$params['name']]) ? (int) $condition[$params['name']] : $condition[$params['name']]) : '';
            foreach (self::$types_data['stocks'] as $o) {
                $html .= "<option value='" . $o['id'] . "'" . ($default === $o['id'] ? " selected" : "") . (!empty($o['class']) ? ' class="' . $o['class'] . '"' : '') . ">" . waString::escapeAll($o['name']) . "</option>";
            }
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getUserDataFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['userData2'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopFlexdiscountHelper::getSelectOptionsHtml(self::$types_data['userData2'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getCustomerSourceFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['customerSource'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopFlexdiscountHelper::getSelectOptionsHtml(self::$types_data['customerSource'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getUserFieldHTML($params, $condition)
    {
        $html = "";
        $user = isset(self::$types_data['user'][$condition['value']]) ? self::$types_data['user'][$condition['value']]['name'] : $params['link'];
        $html .= '<div class="condition-text">
                  <a href="#/open/conditionDialog/" class="js-action" data-id="user" data-source="?plugin=flexdiscount&module=dialog&action=getUsers" title="' . $params['link'] . '">' . $user . '</a>
                  <input class="s-value-field" name="' . $params['name'] . '" value="' . $condition['value'] . '" type="hidden">
                  </div>';
        return $html;
    }

    protected static function getStorefrontFieldHTML($params, $condition)
    {
        static $domains = array();
        static $routes = array();
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '') . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['storefront'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            if (!isset($params['id'])) {
                if (!$domains) {
                    foreach (self::$types_data['storefront'] as $k => $dom) {
                        $domains[$k] = $dom;
                        $domains[$k]['name'] = $dom['title'] ? $dom['title'] : $dom['name'];
                    }
                }
                $html .= shopFlexdiscountHelper::getSelectOptionsHtml($domains, $default);
            } else {
                if (!$routes) {
                    $helper = new shopFlexdiscountHelper();
                    foreach (self::$types_data['storefront'] as $domain) {
                        $routes[$domain['id']] = $helper->getRoutes($domain['name']);
                    }
                }
                $html .= shopFlexdiscountHelper::getStorefrontRoutesHtml($routes, $default);
            }
        }
        $html .= "</select>";

        if (isset($params['id'])) {
            $html .= '<div class="condition-text">';
            $html .= '<a href="#/reset/selection/" class="js-action s-reset-button" title="' . _wp('reset selection') . '"><i class="icon16 no"></i></a>';
            $html .= '</div>';
        }

        return $html;
    }

    protected static function getPeriodFieldHTML($params, $condition)
    {
        $options = array(
            'period' => _wp('period'), 'ndays' => _wp('last n-days'), 'pweek' => _wp('previous week'),
            'pmonth' => _wp('previous month'), 'pquarter' => _wp('previous quarter'), 'p6m' => _wp('previous half a year'),
            'p9m' => _wp('previous 9 months'), 'p12m' => _wp('previous year'), 'today' => _wp('today'), 'cweek' => _wp('current week'),
            'cmonth' => _wp('current month'), 'cquarter' => _wp('current quarter'), 'c6m' => _wp('current 6 months'),
            'c9m' => _wp('current 9 months'), 'c12m' => _wp('current year'), '' => _wp('all time')
        );

        $html = "<div class='inline-block condition-text'><select name='period_type' style='width: 400px' class='inherit period-select' data-placeholder='" . _wp("all time") . "'><option value=''></option>";
        foreach ($options as $k => $o) {
            $html .= "<option value='" . $k . "'" . ($condition['period_type'] == $k ? " selected" : "") . ">" . $o . "</option>";
        }
        $html .= "</select></div>";

        if ($condition['period_type'] == 'period' || $condition['period_type'] == 'ndays') {
            $html .= "<div class='period-block condition-text'><div class='s-" . $condition['period_type'] . "'>";
            if ($condition['period_type'] == 'period') {
                $html .= self::addFieldText(_wp('period from')) .
                    self::getInputFieldHTML(array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field1', 'params' => array('style' => 'width: 120px; min-width: 120px;', 'class' => 'init-datepicker', 'value' => $condition['field1'])), $condition) .
                    self::addFieldText(_wp('to')) .
                    self::getInputFieldHTML(array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext1', 'params' => array('style' => 'width: 120px; min-width: 120px;', 'class' => 'init-datepicker', 'value' => $condition['ext1'])), $condition);
            } elseif ($condition['period_type'] == 'ndays') {
                $html .= self::addFieldText(_wp('the last')) .
                    self::getInputFieldHTML(array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field1', 'params' => array('style' => 'width: 70px; min-width: 70px;', 'value' => $condition['field1'])), $condition) .
                    self::addFieldText(_wp('days'));
            }
            $html .= "</div></div>";
        }
        return $html;
    }

    protected static function getTimeFieldHTML($params, $condition)
    {
        $html = '<div class="inline-block align-center condition-text">'
            . '<input name="hour" type="text" value="' . (!empty($condition['hour']) ? $condition['hour'] : "") . '" style="min-width: 35px;width:35px" maxlength="2"><br>' . _wp('HH')
            . '</div>';
        $html .= '<div class="inline-block align-center condition-text">'
            . '<input name="minute" type="text" value="' . (!empty($condition['minute']) ? $condition['minute'] : "") . '" style="min-width: 35px;width:35px" maxlength="2"><br>' . _wp('MM')
            . '</div>';
        return $html;
    }

    protected static function getTooltipFieldHTML($params, $condition)
    {
        $html = '<div class="condition-text">';
        $html .= '<div class="hover-tooltip">
                    <span>?</span>
                    <div class="hover-content">
                        <div>
                            ' . waString::escapeAll($params['value']) . '
                        </div>
                    </div>
                </div>';
        $html .= '</div>';
        return $html;
    }

    protected static function getOrderStatusFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['orderStatus'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopFlexdiscountHelper::getSelectOptionsHtml(self::$types_data['orderStatus'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getSelectFieldHTML($params, $condition)
    {
        if (!empty($condition[$params['name']])) {
            $params['params']['value'] = $condition[$params['name']];
        }
        return waHtmlControl::getControl($params['control_type'], $params['name'], $params['params']);
    }

    protected static function getInputFieldHTML($params, $condition)
    {
        $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
        if (!empty($params['params']['class']) && !$default && !empty($params['hidden'])) {
            $params['params']['class'] .= ' hidden';
        }
        if ($default !== '') {
            $params['params']['value'] = $default;
        }
        return waHtmlControl::getControl($params['control_type'], $params['name'], $params['params']);
    }

}
