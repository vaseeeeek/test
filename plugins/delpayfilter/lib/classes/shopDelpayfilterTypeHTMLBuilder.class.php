<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterTypeHTMLBuilder extends shopDelpayfilterConditions
{

    /**
     * Generate HTML of all conditions
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
                $html .= '<div class="condition-text"><a href="#/delete/target/" class="js-action" title="' . _wp('delete') . '"><i class="icon16 delete"></i></a></div>';
                $html .= "</div>"; // .target-row
            }
            $html .= '<a href="#/add/target/" class="s-add-target js-action"><i class="icon16 add"></i> ' . _wp('Add target') . '</a>';
        }

        return $html;
    }

    /**
     * Generate HTML of condition
     * 
     * @param array $condition
     * @return string
     */
    private static function buildConditionHTML($condition, $type = 'condition')
    {
        static $instance = null;
        if (!$instance) {
            $instance = get_class();
        }
        $html = "";
        if (!empty(self::$types[$condition['type']])) {
            if ($condition['type'] == 'shipping' && $type == 'target') {
                self::$types[$condition['type']][1] = _wp('equals');
            }
            foreach (self::$types[$condition['type']] as $value) {
                if (is_array($value)) {
                    if (isset($value['type'])) {
                        $method_name = 'get' . ucfirst($value['type']) . 'FieldHTML';
                        if (method_exists($instance, $method_name)) {
                            $html .= self::$method_name($value, $condition);
                        }
                    } elseif (isset($value['control_type'])) {
                        $html .= waHtmlControl::getControl($value['control_type'], $value['name'], $value['params']);
                    } else {
                        $html .= "<span class='condition-text'>" . $value . "</span>";
                    }
                } else {
                    $html .= "<span class='condition-text'>" . $value . "</span>";
                }
            }
            $html .= '<input name="type" value="' . $condition['type'] . '" type="hidden">';
        }
        return $html;
    }

    protected static function getCategoryFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . " style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['category'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getCategoriesTreeOptionsHtml(self::$types_data['category'], 0, $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getSetFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['set'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['set'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getTypeFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['type'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['type'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getProductFieldHTML($params, $condition)
    {
        $html = "";
        $product = isset(self::$types_data['product'][$condition[$params['name']]]) ? self::$types_data['product'][$condition[$params['name']]]['name'] : $params['link'];
        $html .= '<div class="condition-text">
                  <a href="#/open/conditionDialog/" class="js-action" data-id="product" data-source="?plugin=delpayfilter&module=dialog&action=getProducts" title="' . $params['link'] . '">' . $product . '</a>
                  <input name="' . $params['name'] . '" value="' . $condition[$params['name']] . '" type="hidden">
                  </div>';
        return $html;
    }

    protected static function getFeatureFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '400px') . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['feature'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            if (!isset($params['id'])) {
                $html .= shopDelpayfilterHelper::getFeaturesHtml(self::$types_data['feature'], $default);
            } else {
                $html .= shopDelpayfilterHelper::getFeaturesValuesHtml(self::$types_data['feature'], $default);
            }
        }
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
                $html .= shopDelpayfilterHelper::getServicesHtml(self::$types_data['services'], $default);
            } else {
                $html .= shopDelpayfilterHelper::getServicesVariantsHtml(self::$types_data['services'], $default);
            }
        }
        $html .= "</select>";

        return $html;
    }

    protected static function getUcatFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['ucat'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['ucat'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getOrderStatusFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['orderStatus'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['orderStatus'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getStocksFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'" . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['stocks'])) {
            $default = isset($condition[$params['name']]) ? (is_numeric($condition[$params['name']]) ? (int) $condition[$params['name']] : $condition[$params['name']]) : '';
            foreach (self::$types_data['stocks'] as $o) {
                $html .= "<option value='" . $o['id'] . "'" . ($default === $o['id'] ? " selected" : "") . (!empty($o['class']) ? ' class="' . $o['class'] . '"' : '') . ">" . shopDelpayfilterHelper::secureString($o['name']) . "</option>";
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
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['userData2'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getUserFieldHTML($params, $condition)
    {
        $html = "";
        $user = isset(self::$types_data['user'][$condition['value']]) ? self::$types_data['user'][$condition['value']]['name'] : $params['link'];
        $html .= '<div class="condition-text">
                  <a href="#/open/conditionDialog/" class="js-action" data-id="user" data-source="?plugin=delpayfilter&module=dialog&action=getUsers" title="' . $params['link'] . '">' . $user . '</a>
                  <input name="' . $params['name'] . '" value="' . $condition['value'] . '" type="hidden">
                  </div>';
        return $html;
    }

    protected static function getShippingFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['shipping'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['shipping'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getCountryFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['country'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['country'], $default);
        }
        $html .= "</select>";
        return $html;
    }

    protected static function getDynamicFieldHTML($params, $condition)
    {
        // Если перед нами массив значений
        $is_values = isset($params['dynamic_data_id']);
        $html = "<select name='{$params['name']}'" . (!empty($params['placeholder']) ? " data-placeholder='{$params['placeholder']}'" : "") . (!empty($params['width']) ? ' style="width:' . $params['width'] . '"' : '400px') . (!empty($params['class']) ? ' class="' . $params['class'] . '"' : '') . (!empty($params['data-value-url']) ? ' data-value-url="' . $params['data-value-url'] . '"' : '') . ">";
        $html .= "<option value=''></option>";
        $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
        if (!empty(self::$types_data[$params['id']]['fields']) && !$is_values) {
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data[$params['id']]['fields'], $default);
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
        $html .= shopDelpayfilterHelper::getDynamicValuesHtml($values['values'], $values['id'], $default);
        $html .= "</select>";
        return $html;
    }

    protected static function getPaymentFieldHTML($params, $condition)
    {
        $html = "<select name='{$params['name']}'  data-placeholder='{$params['placeholder']}' style='width: 400px'>";
        $html .= "<option value=''></option>";
        if (!empty(self::$types_data['payment'])) {
            $default = isset($condition[$params['name']]) ? $condition[$params['name']] : '';
            $html .= shopDelpayfilterHelper::getSelectOptionsHtml(self::$types_data['payment'], $default);
        }
        $html .= "</select>";
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
                $html .= shopDelpayfilterHelper::getSelectOptionsHtml($domains, $default);
            } else {
                if (!$routes) {
                    foreach (self::$types_data['storefront'] as $domain) {
                        $routes[$domain['id']] = shopDelpayfilterHelper::getRoutes($domain['name']);
                    }
                }
                $html .= shopDelpayfilterHelper::getStorefrontRoutesHtml($routes, $default);
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
