<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 * 
 */

class shopAutobadgeConditions extends shopAutobadgeData
{

    // Старшинство типов условий
    protected static $type_precedence = array(
        'ucat' => 0, 'user' => 0, 'user_date' => 0, 'shipping' => 0, 'payment' => 0, 'all_orders' => 0, 'order_int' => 0, 'count_orders' => 0,
        'order_count_int' => 0, 'order_prod' => 0, 'order_prod_int' => 0, 'order_prod_cat' => 0, 'order_prod_cat_all' => 0, 'order_prod_cat_int' => 0,
        'order_prod_cat_all_int' => 0, 'date' => 0, 'week' => 0, 'time' => 0, 'cookie' => 0, 'session' => 0, 'get' => 0, 'post' => 0, 'server' => 0,
        'storefront' => 0, 'mobile' => 0, 'theme' => 0, 'product_page' => 0,
        'cat' => 1, 'cat_all' => 1, 'set' => 1, 'type' => 1, 'product' => 1, 'feature' => 1, 'params' => 1, 'num_total' => 2,
        'product_name' => 2, 'product_sku' => 2, 'product_sku_name' => 2, 'product_summary' => 2, 'product_mt' => 2, 'product_mk' => 2, 'product_md' => 2, 'product_description' => 2,
        'product_create' => 2, 'product_age' => 2, 'product_edit' => 2, 'product_video' => 2, 'product_image' => 2, 'product_rating' => 2, 'product_rating_count' => 2,
        'product_price' => 2, 'product_margin' => 2, 'product_margin_comp' => 2, 'product_compare_price' => 2, 'product_purchase_price' => 2, 'product_min_price' => 2,
        'product_max_price' => 2, 'product_stock' => 2, /*'product_stock_change' => 2,*/
        'product_total_sales' => 2, 'product_number_sales' => 2, 'product_services' => 2, 'product_tags' => 2, 'product_badge_type' => 2,
        'num' => 3, 'num_prod' => 3, 'num_cat' => 3, 'num_cat_all' => 3, 'num_set' => 3, 'num_type' => 3, 'num_feat' => 3, 'num_items' => 3, 'sum' => 3, 'sum_cat' => 3, 'sum_cat_all' => 3,
        'num_all_cat' => 3, 'num_all_cat_all' => 3, 'num_all_set' => 3, 'num_all_type' => 3, 'sum_feat' => 3, 'total_feat' => 3, 'prod_each_price' => 3, 'services' => 3, 'total' => 3
    );
    private static $optsEqNe;
    private static $optsAll;
    private static $optCats;
    private static $optsText;
    private static $optsNum;
    private static $optsNum2;
    private static $optsSumSku;
    private static $optsStocks;
    private static $filterCond;
    private static $all_items = array();
    protected static $types;
    protected static $user = array();

    /**
     * Decode JSON object
     *
     * @param string|array $json
     * @return array
     */
    public static function decode($json)
    {
        return is_string($json) ? json_decode($json) : $json;
    }

    /**
     * Decode JSON object to array
     *
     * @param string|array $json
     * @return array
     */
    public static function decodeToArray($json)
    {
        return is_string($json) ? shopAutobadgeHelper::object_to_array(json_decode($json)) : $json;
    }

    /**
     * Init conditions
     *
     * @param array $conditions
     * @return boolean
     */
    public static function init($conditions)
    {
        static $inited = false;
        if ($inited) {
            return true;
        }

        self::initConstants();
        self::getTypesData($conditions);

        $inited = 1;
    }

    /**
     * Assign constants
     */
    private static function initConstants()
    {
        self::$optsEqNe = array(
            'type' => 'select',
            'control_type' => waHtmlControl::SELECT,
            'name' => 'op',
            'params' => array(
                'style' => 'width: 100px',
                'options' => array(
                    array('title' => _wp("equal"), 'value' => 'eq'),
                    array('title' => _wp("not equal"), 'value' => 'neq'),
                )
            )
        );
        self::$optsAll = array(
            'type' => 'select',
            'control_type' => waHtmlControl::SELECT,
            'name' => 'op',
            'params' => array(
                'style' => 'width: 130px',
                'options' => array(
                    array('title' => _wp("equal"), 'value' => 'eq'),
                    array('title' => _wp("not equal"), 'value' => 'neq'),
                    array('title' => _wp("contains"), 'value' => 'cont'),
                    array('title' => _wp("not contains"), 'value' => 'notcont'),
                    array('title' => _wp("begins with"), 'value' => 'begins'),
                    array('title' => '>', 'value' => 'gt'),
                    array('title' => '>=', 'value' => 'gte'),
                    array('title' => '<', 'value' => 'lt'),
                    array('title' => '<=', 'value' => 'lte'),
                    array('title' => '=', 'value' => 'eq_num'),
                    array('title' => '<>', 'value' => 'neq_num'),
                )
            )
        );
        self::$optsText = array(
            'type' => 'select',
            'control_type' => waHtmlControl::SELECT,
            'name' => 'op',
            'params' => array(
                'style' => 'width: 130px',
                'options' => array(
                    array('title' => _wp("equal"), 'value' => 'eq'),
                    array('title' => _wp("not equal"), 'value' => 'neq'),
                    array('title' => _wp("contains"), 'value' => 'cont'),
                    array('title' => _wp("not contains"), 'value' => 'notcont'),
                    array('title' => _wp("begins with"), 'value' => 'begins'),
                )
            )
        );
        self::$optsNum = array(
            'type' => 'select',
            'control_type' => waHtmlControl::SELECT,
            'name' => 'op',
            'params' => array(
                'style' => 'width: 60px',
                'options' => array(
                    array('title' => '>', 'value' => 'gt'),
                    array('title' => '>=', 'value' => 'gte'),
                    array('title' => '<', 'value' => 'lt'),
                    array('title' => '<=', 'value' => 'lte'),
                    array('title' => '=', 'value' => 'eq_num'),
                    array('title' => '<>', 'value' => 'neq_num'),
                )
            )
        );
        self::$optsNum2 = array(
            'type' => 'select',
            'control_type' => waHtmlControl::SELECT,
            'name' => 'op2',
            'params' => array(
                'style' => 'width: 60px',
                'options' => array(
                    array('title' => '>', 'value' => 'gt'),
                    array('title' => '>=', 'value' => 'gte'),
                    array('title' => '<', 'value' => 'lt'),
                    array('title' => '<=', 'value' => 'lte'),
                    array('title' => '=', 'value' => 'eq_num'),
                    array('title' => '<>', 'value' => 'neq_num'),
                )
            )
        );
        self::$optCats = array(
            'type' => 'category',
            'placeholder' => _wp('Select category'),
            'id' => 'cat',
            'name' => 'value'
        );
        self::$optsSumSku = array(
            'type' => 'select',
            'control_type' => waHtmlControl::SELECT,
            'name' => 'sum_type',
            'params' => array(
                'style' => 'width: 150px',
                'class' => 's-sum-sku',
                'options' => array(
                    array('title' => _wp("each sku is separate"), 'value' => 'not_sum'),
                    array('title' => _wp("summarize skus"), 'value' => 'sum'),
                )
            )
        );
        self::$optsStocks = array(
            'type' => 'select',
            'control_type' => waHtmlControl::SELECT,
            'name' => 'stock_type',
            'params' => array(
                'style' => 'width: 150px',
                'class' => 'stock-options',
                'options' => array(
                    array('title' => _wp("virtual and real"), 'value' => 'virtreal'),
                    array('title' => _wp("virtual"), 'value' => 'virt'),
                    array('title' => _wp("real"), 'value' => 'real'),
                )
            )
        );
        self::$filterCond = '<i title="' . _wp('This condition will filter the result items') . '" class="icon16 funnel"></i>';
        $currency = waCurrency::getInfo(wa('shop')->getConfig()->getCurrency(true));
        self::$types = array(
            'cat' => array(_wp('Category'), self::$optsEqNe, self::$optCats, self::$filterCond),
            'cat_all' => array(_wp('Category and subcategories'), self::$optsEqNe, self::$optCats, self::$filterCond),
            'set' => array(_wp('Product set'), self::$optsEqNe, array('type' => 'set', 'placeholder' => _wp('Select product set'), 'id' => 'set', 'name' => 'value'), self::$filterCond),
            'type' => array(_wp('Product type'), self::$optsEqNe, array('type' => 'type', 'placeholder' => _wp('Select product type'), 'id' => 'type', 'name' => 'value'), self::$filterCond),
            'product' => array(_wp('Product'), self::$optsEqNe, array('type' => 'product', 'link' => _wp("select product"), 'name' => 'value'), self::$filterCond),
            'feature' => array(
                _wp('Product feature'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select', 'width' => '350px'),
                self::$optsAll,
                array('type' => 'feature', 'id' => 'featureValue', 'name' => 'value', 'placeholder' => _wp('Select feature value'), 'class' => 'feature-value-template', 'width' => '350px'),
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'hidden' => 1, 'params' => array('style' => 'width: 150px; min-width: 150px', 'class' => 'feature-value-input')),
                self::$filterCond
            ),
            'params' => array(
                _wp('Product params'),
                array('type' => 'params', 'name' => 'field', 'placeholder' => _wp('Select product params'), 'width' => '400px'),
                self::$optsAll,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')),
                self::$filterCond
            ),
            'product_name' => array(_wp('Product name'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_sku' => array(_wp('SKU code'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_sku_name' => array(_wp('SKU name'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_summary' => array(_wp('Product summary'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_mt' => array(_wp('META title'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_mk' => array(_wp('META keywords'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_md' => array(_wp('META description'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_description' => array(_wp('Product description'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px')), self::$filterCond),
            'product_create' => array(_wp('Product create datetime'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 120px; min-width: 120px;', 'class' => 'init-datepicker')), self::$filterCond),
            'product_age' => array(_wp('Product age'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), _wp('days'), self::$filterCond),
            'product_edit' => array(_wp('Product edit datetime'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 120px; min-width: 120px;', 'class' => 'init-datepicker')), self::$filterCond),
            'product_video' => array(_wp('Product'), self::$optsEqNe, _wp('has video'), self::$filterCond),
            'product_image' => array(_wp('Product'), self::$optsEqNe, _wp('has image'), self::$filterCond),
            'product_rating' => array(_wp('Product rating'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), self::$filterCond),
            'product_rating_count' => array(_wp('Product rating count'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), self::$filterCond),
            'product_price' => array(_wp('Product price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_compare_price' => array(_wp('Product compare price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_purchase_price' => array(_wp('Product purchase price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_margin' => array(_wp('Product price') . " - " . _wp('Product purchase price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_margin_comp' => array(_wp('Product compare price') . " - " . _wp('Product price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_min_price' => array(_wp('Product minimal price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_max_price' => array(_wp('Product maximum price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_stock' => array(
                _wp('Product stock count'),
                self::$optsSumSku,
                _wp('on '),
                array('type' => 'stocks', 'placeholder' => _wp('all stocks'), 'id' => 'stocks', 'class' => 'stocks-select', 'name' => 'field'),
                self::$optsStocks,
                self::$optsNum,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px', 'placeholder' => '∞')),
                self::$filterCond
            ),
            //'product_stock_change' => array(_wp('Changing of product stocks for'), array('type' => 'period'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), self::$filterCond),
            'product_total_sales' => array(_wp('Product total sales for'), array('type' => 'period'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'product_number_sales' => array(_wp('Product total quantity of sales for'), array('type' => 'period'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), self::$filterCond),
            'product_services' => array(
                _wp('Product has service'),
                self::$optsEqNe,
                array('type' => 'services', 'placeholder' => _wp('Select service'), 'id' => 'services', 'name' => 'field', 'class' => 'feature-select s-services', 'width' => '350px'),
                array('type' => 'services', 'id' => 'servicesVariants', 'name' => 'value', 'placeholder' => _wp('Select service variant'), 'class' => 'feature-value', 'width' => '350px'),
                self::$filterCond
            ),
            'product_tags' => array(
                _wp('Product has tag'),
                self::$optsEqNe,
                array('type' => 'tags', 'placeholder' => _wp('Select tags'), 'id' => 'tags', 'name' => 'value', 'width' => '400px'),
                self::$filterCond
            ),
            'product_badge_type' => array(_wp('Badge type'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'num_total' => array(_wp('Total quantity of all products'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'num' => array(_wp('Quantity of products'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'num_prod' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), self::$optsSumSku, self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_cat' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), self::$optsSumSku, _wp('from category'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_cat_all' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), self::$optsSumSku, _wp('from category and subcategories of'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_set' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), self::$optsSumSku, _wp('from set'), self::$optsEqNe, array('type' => 'set', 'placeholder' => _wp('Select product set'), 'id' => 'set', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_type' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), self::$optsSumSku, _wp('from type'), self::$optsEqNe, array('type' => 'type', 'placeholder' => _wp('Select product type'), 'id' => 'type', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_all_cat' => array(_wp('Quantity of all products from category'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_all_cat_all' => array(_wp('Quantity of all products from category and subcategories'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_all_set' => array(_wp('Quantity of all products from set'), self::$optsEqNe, array('type' => 'set', 'placeholder' => _wp('Select product set'), 'id' => 'set', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_all_type' => array(_wp('Quantity of all products from type'), self::$optsEqNe, array('type' => 'type', 'placeholder' => _wp('Select product type'), 'id' => 'type', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), self::$filterCond),
            'num_feat' => array(
                _wp('Quantity of products with features'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select', 'width' => '350px'),
                self::$optsAll,
                array('type' => 'feature', 'id' => 'featureValue', 'name' => 'ext', 'placeholder' => _wp('Select feature value'), 'class' => 'feature-value-template', 'width' => '350px'),
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext', 'hidden' => 1, 'params' => array('style' => 'width: 90px', 'class' => 'feature-value-input')),
                self::$optsNum2,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')),
                self::$filterCond
            ),
            'num_items' => array(_wp('Quantity of unique items'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'sum' => array(_wp('Total price of all products'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'sum_cat' => array(_wp('Total price of products'), _wp('from category'), array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'sum_cat_all' => array(_wp('Total price of products'), _wp('from category and subcategories of'), array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign'], self::$filterCond),
            'sum_feat' => array(
                _wp('Total price of all products with features'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select', 'width' => '350px'),
                self::$optsAll,
                array('type' => 'feature', 'id' => 'featureValue', 'name' => 'ext', 'placeholder' => _wp('Select feature value'), 'class' => 'feature-value-template', 'width' => '350px'),
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext', 'hidden' => 1, 'params' => array('style' => 'width: 150px; min-width: 150px', 'class' => 'feature-value-input')),
                self::$optsNum2,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')),
                $currency['sign'],
                self::$filterCond
            ),
            'total' => array(_wp('Order price without discount'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'total_feat' => array(
                _wp('Total sum of features values'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select extrem-feature-select', 'width' => '350px'),
                self::$optsNum,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'hidden' => 1, 'params' => array('style' => 'width: 150px; min-width: 150px', 'class' => 'feature-value-input'))
            ),
            'prod_each_price' => array(_wp('Price of each product'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'services' => array(
                _wp('Service'),
                self::$optsEqNe,
                array('type' => 'services', 'placeholder' => _wp('Select service'), 'id' => 'services', 'name' => 'field', 'class' => 'feature-select s-services', 'width' => '350px'),
                array('type' => 'services', 'id' => 'servicesVariants', 'name' => 'value', 'placeholder' => _wp('Select service variant'), 'class' => 'feature-value', 'width' => '350px'),
            ),
            'ucat' => array(_wp('User category'), self::$optsEqNe, array('type' => 'ucat', 'placeholder' => _wp('Select user category'), 'id' => 'ucat', 'name' => 'value')),
            'user' => array(_wp('Contact'), self::$optsEqNe, array('type' => 'user', 'link' => _wp('select contact'), 'id' => 'user', 'name' => 'value')),
            'user_date' => array(_wp('Contact create datetime'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 120px; min-width: 120px;', 'class' => 'init-datepicker'))),
            'shipping' => array(_wp('Shipping'), self::$optsEqNe, array('type' => 'shipping', 'placeholder' => _wp('Select shipping'), 'id' => 'shipping', 'name' => 'value')),
            'payment' => array(_wp('Payment'), self::$optsEqNe, array('type' => 'payment', 'placeholder' => _wp('Select payment'), 'id' => 'payment', 'name' => 'value')),
            'all_orders' => array(_wp('Total sum of all orders'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'order_int' => array(_wp('Sum of orders for'), array('type' => 'period'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'count_orders' => array(_wp('Quantity of all orders'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'order_count_int' => array(_wp('Quantity of orders for'), array('type' => 'period'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'order_prod' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("select product"), 'name' => 'value')),
            'order_prod_int' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'value', 'can_reset' => 1), _wp('for'), array('type' => 'period'), self::$filterCond),
            'order_prod_cat' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), _wp("from category"), self::$optCats, self::$filterCond),
            'order_prod_cat_all' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), _wp("from category and subcategories of"), self::$optCats, self::$filterCond),
            'order_prod_cat_int' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), _wp("from category"), self::$optCats, _wp('for'), array('type' => 'period'), self::$filterCond),
            'order_prod_cat_all_int' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field', 'can_reset' => 1), _wp("from category and subcategories of"), self::$optCats, _wp('for'), array('type' => 'period'), self::$filterCond),
            'date' => array(_wp('Date'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 120px; min-width: 120px;', 'class' => 'init-datepicker'))),
            'week' => array(_wp('Day of week'), array('type' => 'select', 'control_type' => waHtmlControl::SELECT, 'name' => 'value', 'params' => array('style' => 'width: 110px', 'options' => array(array('title' => _wp("Monday"), 'value' => 1), array('title' => _wp("Tuesday"), 'value' => 2), array('title' => _wp("Wednesday"), 'value' => 3), array('title' => _wp("Thursday"), 'value' => 4), array('title' => _wp("Friday"), 'value' => 5), array('title' => _wp("Saturday"), 'value' => 6), array('title' => _wp("Sunday"), 'value' => 7))))),
            'time' => array(_wp('Time'), self::$optsNum, array('type' => 'time')),
            'cookie' => array('$_COOKIE["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'session' => array('$_SESSION["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'get' => array('$_GET["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'post' => array('$_POST["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'server' => array('$_SERVER["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'storefront' => array(
                _wp('Storefront'),
                self::$optsEqNe,
                array('type' => 'storefront', 'placeholder' => _wp('Select domain'), 'width' => '350px', 'class' => 'storefront-domain', 'name' => 'field'),
                array('type' => 'storefront', 'placeholder' => _wp('Select route'), 'id' => 'storefrontRoutes', 'hidden' => 1, 'width' => '350px', 'class' => 'storefront-route', 'name' => 'value'),
            ),
            'mobile' => array(self::$optsEqNe, _wp('Mobile version')),
            'theme' => array(
                _wp('Theme design'),
                self::$optsEqNe,
                array('type' => 'theme', 'id' => 'theme', 'placeholder' => _wp('Select theme design'), 'width' => '350px', 'name' => 'value'),
            ),
            'product_page' => array(self::$optsEqNe, _wp('Product page')),
        );
    }

    /**
     * Filter items by conditions
     *
     * @param array $items - cart items
     * @param string $group_andor - combine type: and, or
     * @param array $conditions
     * @return array Return filtered items
     */
    protected static function filter_items($items, $group_andor, $conditions)
    {
        $instance = self::get_instance();

        $result_items = $group_andor == 'and' ? $items : array();

        // Сортируем условия по старшинству
        $conditions = self::sort_by_precedence($conditions);

        foreach ($conditions as $c) {
            // Если перед нами группа скидок, разбираем ее
            if (isset($c->group_op)) {
                $result = self::filter_items($items, $c->group_op, $c->conditions);
                if ($group_andor == 'and' && !$result) {
                    $result_items = array();
                    break;
                }
                $result_items += $result;
            } else {
                // Проверяем работоспособность оператора
                if (isset($c->op) && !self::prepare_operator($c->op)) {
                    continue;
                }
                // Выполняем фильтрацию товаров согласно функции
                $function_name = 'filter_by_' . $c->type;
                if (method_exists($instance, $function_name)) {
                    $filtered_items = self::executeFilterFunction($function_name, $group_andor == 'and' ? $result_items : $items, $c);
                    if ($group_andor == 'and') {
                        $result_items = $filtered_items;
                        if (!$result_items) {
                            break;
                        }
                    } else {
                        $result_items += $filtered_items;
                    }
                }
            }
        }

        return $result_items;
    }

    /**
     * Execute filter function. If function is not filtered, then save it results to cache.
     * Cache contains bool values. If true - return all items, false - return empty array
     *
     * @param string $function_name
     * @param array $items
     * @param array $params
     * @return mixed
     */
    private static function executeFilterFunction($function_name, $items, $params)
    {
        static $cache_results = array();
        // Функции, которые не являются фильтрующими
        $not_filtered_conditions = array(
            'filter_by_services' => 1,
            'filter_by_num_items' => 1,
            'filter_by_sum' => 1,
            'filter_by_total' => 0,
            'filter_by_total_feat' => 1,
            'filter_by_prod_each_price' => 1,
            'filter_by_ucat' => 0,
            'filter_by_user' => 0,
            'filter_by_user_date' => 0,
            'filter_by_all_orders' => 0,
            'filter_by_order_int' => 0,
            'filter_by_count_orders' => 0,
            'filter_by_order_count_int' => 0,
            'filter_by_order_prod' => 0,
            'filter_by_date' => 0,
            'filter_by_week' => 0,
            'filter_by_time' => 0,
            'filter_by_cookie' => 0,
            'filter_by_session' => 0,
            'filter_by_get' => 0,
            'filter_by_post' => 0,
            'filter_by_server' => 0,
            'filter_by_storefront' => 0
        );
        $params = (array) $params;
        if (isset($not_filtered_conditions[$function_name])) {
            $hash = $not_filtered_conditions[$function_name] ? self::getRequestHash($function_name, self::getProductIds($items), $params) : self::getRequestHash($function_name, $params);
            if (!isset($cache_results[$hash])) {
                $result = self::$function_name($items, $params);
                $cache_results[$hash] = $result ? 1 : 0;
            }
            return $cache_results[$hash] ? $items : array();
        }
        return self::$function_name($items, $params);
    }

    /**
     * Sort conditions by precedence
     *
     * @param array $conditions
     * @return array
     */
    private static function sort_by_precedence($conditions)
    {
        $sorted_conditions = array(0 => array(), 1 => array(), 2 => array(), 3 => array());
        foreach ($conditions as $c) {
            if (isset($c->group_op)) {
                $sorted_conditions[0][] = $c;
            } else {
                if (isset(self::$type_precedence[$c->type])) {
                    $sorted_conditions[self::$type_precedence[$c->type]][] = $c;
                }
            }
        }
        $result = array();
        foreach ($sorted_conditions as $sc) {
            $result = array_merge($result, $sc);
        }
        return $result;
    }

    /*     * **
     * Товар
     * * */

    private static function _filter_by_type($items, $params, $all = false, $type = 'cat')
    {
        if (!empty($params['value'])) {
            // Получаем ID товаров
            $filter_by_type = $type == 'cat' ? shopAutobadgeData::getCategoryProducts($params['value'], $all) : ($type == 'set' ? shopAutobadgeData::getSetProducts($params['value']) : $params['value']);
            foreach ($items as $k => $item) {
                $id = $type == 'type' ? (isset($item['type_id']) ? (int) $item['type_id'] : (isset($item['product']['type_id']) ? (int) $item['product']['type_id'] : 0)) : self::getProductId($item);
                if (!self::execute_operator($params['op'], $id, $filter_by_type)) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    protected static function filter_by_cat($items, $params)
    {
        return self::_filter_by_type($items, $params);
    }

    protected static function filter_by_cat_all($items, $params)
    {
        return self::_filter_by_type($items, $params, true);
    }

    protected static function filter_by_set($items, $params)
    {
        return self::_filter_by_type($items, $params, false, 'set');
    }

    protected static function filter_by_type($items, $params)
    {
        return self::_filter_by_type($items, $params, false, 'type');
    }

    protected static function filter_by_services($items, $params)
    {
        $all_services = array();
        foreach ($items as $item) {
            if (!empty($item['product_services'])) {
                foreach ($item['product_services'] as $service_id => $serv) {
                    foreach ($serv as $s) {
                        $all_services[$service_id][$s['service_variant_id']] = $s['service_variant_id'];
                    }
                }
            }
        }

        if (!empty($params['field']) && empty($params['value'])) {
            $service = $params['field'];
            $services = array_keys($all_services);
        } elseif (!empty($params['field']) && !empty($params['value'])) {
            $service = $params['value'];
            $services = !empty($all_services[$params['field']]) ? $all_services[$params['field']] : array();
        } else {
            return $items;
        }

        if (self::execute_operator($params['op'], $service, $services)) {
            return $items;
        }

        return array();
    }

    protected static function filter_by_product($items, $params)
    {
        if (!empty($params['value'])) {
            foreach ($items as $k => $item) {
                if (!self::execute_operator($params['op'], self::getProductId($item, $params['product_type']), $params['value'])) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    private static function _filter_by_feature($items, $feature_id, $operator, $value)
    {
        $result_items = $products = array();
        // Если не было передано характеристики, прерываем обработку
        if (!$feature_id) {
            return $items;
        }
        // Выбираем товары
        foreach ($items as $k => $item) {
            $items[$k]['product_id'] = self::getProductId($item);
        }

        // Получаем все характеристики и присваиваем товарам их характеристики
        $features = shopAutobadgeData::getFeatures($items);
        if ($features) {
            foreach ($items as $k => $item) {
                $product = $item['product'];
                // Если характеристика выпадающая
                if (!empty($features[$feature_id]['selectable'])) {
                    // Значение должно быть двойное: первая часть - ID характеристики, вторая - ID значения
                    if (strpos($value, "-") !== false) {
                        $parts = explode("-", $value);
                        // Проверка. Если ID характеристики в значении не совпадает с переданным ID, значит целостность нарушена.
                        if ($parts[0] !== $feature_id) {
                            continue;
                        }
                        $value_id = $parts[1];
                        // Значение характеристик товара
                        $product_feature_value = isset($product['feature_values'][$feature_id]) ? (is_array($product['feature_values'][$feature_id]) ? array_keys($product['feature_values'][$feature_id]) : $product['feature_values'][$feature_id]) : 0;
                        $item_sku_id = self::getProductId($item, 'sku');
                        if ((empty($product['selectable_features'][$item_sku_id][$feature_id]) && self::execute_operator($operator, $product_feature_value, $value_id)) || (!empty($product['selectable_features'][$item_sku_id][$feature_id]) && self::execute_operator($operator, $product['selectable_features'][$item_sku_id][$feature_id], $value_id))) {
                            $result_items[$k] = $items[$k];
                        }
                    } else {
                        continue;
                    }
                } else {
                    // Если у товара существует характеристика, и она является объектом
                    if (isset($product['features'][$feature_id]) && is_object($product['features'][$feature_id])) {
                        // Если это диапазон, то обработку операторов будем выполнять специально для диапазона значений
                        if ($product['features'][$feature_id] instanceof shopRangeValue) {
                            if (self::execute_operator($operator, array($product['features'][$feature_id]->begin_base_unit, $product['features'][$feature_id]->end_base_unit), $value, true)) {
                                $result_items[$k] = $items[$k];
                            }
                        } else {
                            $product_feature_value = '';
                            if (property_exists($product['features'][$feature_id], 'value_base_unit')) {
                                $product_feature_value = $product['features'][$feature_id]->value_base_unit;
                            } elseif (property_exists($product['features'][$feature_id], 'value')) {
                                $product_feature_value = $product['features'][$feature_id]->value;
                            }
                            // Обрабатываем другие объекты
                            if (self::execute_operator($operator, $product_feature_value, $value)) {
                                $result_items[$k] = $items[$k];
                            }
                        }
                    } else {
                        // Обработка других типов характеристик
                        $product_feature_value = isset($product['features'][$feature_id]) ? $product['features'][$feature_id] : '';
                        if (self::execute_operator($operator, $product_feature_value, $value)) {
                            $result_items[$k] = $items[$k];
                        }
                    }
                }
            }
        }
        return $result_items;
    }

    protected static function filter_by_feature($items, $params)
    {
        return self::_filter_by_feature($items, $params['field'], $params['op'], $params['value']);
    }

    protected static function filter_by_params($items, $params)
    {
        if (!empty($params['field'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                $product_params = shopAutobadgeData::getProductParams($product_id);
                if (!self::execute_operator($params['op'], $params['value'], isset($product_params[$params['field']]) ? $product_params[$params['field']] : '')) {
                    unset($items[$k]);
                }
            }
            return $items;
        }
        return array();
    }

    /*     * **
     * Корзина
     * * */

    protected static function filter_by_num($items, $params)
    {
        return self::_filter_by_num($items, $params['op'], $params['value']);
    }

    private static function _filter_by_num($items, $operator, $value)
    {
        $quantity = 0;
        if ($items) {
            foreach ($items as $item) {
                $quantity += $item['quantity'];
            }
        }
        if (!self::execute_operator($operator, $quantity, $value)) {
            return array();
        }
        return $items ? $items : array(self::getAbstractProduct());
    }

    protected static function filter_by_num_total($items, $params)
    {
        $result_items = self::_filter_by_num(self::getAllItems(null), $params['op'], $params['value']);
        return $result_items ? $items : array();
    }

    private static function filter_by_num_prod($items, $params)
    {
        $products = $product_items = array();
        // Фильтруем по товару
        if ($params['field']) {
            $items = self::filter_by_product($items, array("op" => "eq_num", "value" => $params['field'], "product_type" => $params['product_type']));
        }
        $quantity = 0;
        if ($items) {
            foreach ($items as $k => $item) {
                $id = self::getProductId($item, $params['product_type']);
                // Если необходимо просуммировать кол-во артикулов
                if ($params['sum_type'] == 'sum' && $params['product_type'] !== 'sku') {
                    $products[$id] = !isset($products[$id]) ? (int) $item['quantity'] : $products[$id] + (int) $item['quantity'];
                    $product_items[$id][] = $k;
                } else {
                    if (!self::execute_operator($params['op'], $item['quantity'], $params['value'])) {
                        unset($items[$k]);
                    }
                }
            }
            // Для просуммированных артикулов проверяем значения
            if ($products) {
                foreach ($products as $product_id => $quantity) {
                    if (!self::execute_operator($params['op'], $quantity, $params['value'])) {
                        foreach ($product_items[$product_id] as $i) {
                            unset($items[$i]);
                        }
                    }
                }
            }
        } else {
            // Если отфильтрованного товара не существует, проверяем, возможно пользователь установил кол-во равное нулю 
            if (self::execute_operator($params['op'], $quantity, $params['value'])) {
                return array(self::getAbstractProduct());
            }
        }
        return $items;
    }

    private static function _filter_by_num_type($items, $params, $all = false, $type = 'cat')
    {
        if (!empty($params['ext'])) {
            $products = $product_items = array();
            // Фильтруем по товару
            if ($params['field']) {
                $items = self::filter_by_product($items, array("op" => "eq_num", "value" => $params['field'], "product_type" => $params['product_type']));
                if (!$items) {
                    return array();
                }
            }
            $filter_by_type = $type == 'cat' ? shopAutobadgeData::getCategoryProducts($params['ext'], $all) : ($type == 'set' ? shopAutobadgeData::getSetProducts($params['ext']) : $params['ext']);
            foreach ($items as $k => $item) {
                $id = self::getProductId($item, $params['product_type']);
                // Если необходимо просуммировать кол-во артикулов
                if ($params['sum_type'] == 'sum' && $params['product_type'] !== 'sku') {
                    $products[$id] = !isset($products[$id]) ? (int) $item['quantity'] : $products[$id] + (int) $item['quantity'];
                    $product_items[$id][] = $k;
                } // Если каждый артикул рассчитывается отдельно
                else {
                    $quantity = 0;
                    if ($type == 'type') {
                        $type_id = isset($item['type_id']) ? (int) $item['type_id'] : (isset($item['product']['type_id']) ? (int) $item['product']['type_id'] : 0);
                    }
                    if (self::execute_operator($params['op'], $type == 'type' ? $type_id : $id, $filter_by_type)) {
                        $quantity += $item['quantity'];
                    }

                    if (!self::execute_operator($params['op2'], $quantity, $params['value'])) {
                        unset($items[$k]);
                    }
                }
            }
            // Для просуммированных артикулов проверяем значения
            if ($products) {
                foreach ($products as $product_id => $quantity) {
                    if ($type == 'type') {
                        $item = $items[reset($product_items[$product_id])];
                        $type_id = isset($item['type_id']) ? (int) $item['type_id'] : (isset($item['product']['type_id']) ? (int) $item['product']['type_id'] : 0);
                    }
                    if (!self::execute_operator($params['op'], $type == 'type' ? $type_id : $product_id, $filter_by_type)) {
                        $quantity = 0;
                    }
                    if (!self::execute_operator($params['op2'], $quantity, $params['value'])) {
                        foreach ($product_items[$product_id] as $i) {
                            unset($items[$i]);
                        }
                    }
                }
            }

            return $items;
        }
        return array();
    }

    private static function filter_by_num_cat($items, $params)
    {
        return self::_filter_by_num_type($items, $params);
    }

    private static function filter_by_num_cat_all($items, $params)
    {
        return self::_filter_by_num_type($items, $params, true);
    }

    private static function filter_by_num_set($items, $params)
    {
        return self::_filter_by_num_type($items, $params, false, 'set');
    }

    private static function filter_by_num_type($items, $params)
    {
        return self::_filter_by_num_type($items, $params, false, 'type');
    }

    private static function _filter_by_num_all_type($items, $params, $all = false, $type = 'cat')
    {
        if (!empty($params['field'])) {
            $result_items = self::_filter_by_type($items, array('op' => $params['op'], 'value' => $params['field']), $all, $type);
            return self::_filter_by_num($result_items, $params['op2'], $params['value']);
        }
        return array();
    }

    private static function filter_by_num_all_cat($items, $params)
    {
        return self::_filter_by_num_all_type($items, $params);
    }

    private static function filter_by_num_all_cat_all($items, $params)
    {
        return self::_filter_by_num_all_type($items, $params, true);
    }

    private static function filter_by_num_all_set($items, $params)
    {
        return self::_filter_by_num_all_type($items, $params, false, 'set');
    }

    private static function filter_by_num_all_type($items, $params)
    {
        return self::_filter_by_num_all_type($items, $params, false, 'type');
    }

    private static function filter_by_num_feat($items, $params)
    {
        if (!empty($params['field']) && !empty($params['ext'])) {
            $result_items = self::_filter_by_feature($items, $params['field'], $params['op'], $params['ext']);
            return self::_filter_by_num($result_items, $params['op2'], $params['value']);
        }
        return array();
    }

    private static function filter_by_num_items($items, $params)
    {
        if (!self::execute_operator($params['op'], count($items), $params['value'])) {
            return array();
        }
        return $items;
    }

    private static function filter_by_sum($items, $params)
    {
        return self::_filter_by_sum($items, $params['op'], $params['value']);
    }

    private static function _filter_by_sum($items, $operator, $value)
    {
        $total_price = 0;
        foreach ($items as $item) {
            $total_price += $item['primary_price'] * $item['quantity'];
        }
        if (!self::execute_operator($operator, $total_price, $value)) {
            return array();
        }

        return $items;
    }

    private static function _filter_by_sum_cat($items, $params, $all = false)
    {
        if (!empty($params['field'])) {
            $total_price = 0;
            $filter_by_type = shopAutobadgeData::getCategoryProducts($params['field'], $all);
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if (self::execute_operator('eq', $product_id, $filter_by_type)) {
                    $total_price += $item['primary_price'] * $item['quantity'];
                } else {
                    unset($items[$k]);
                }
            }
            if (self::execute_operator($params['op'], $total_price, $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_sum_cat($items, $params)
    {
        return self::_filter_by_sum_cat($items, $params);
    }

    private static function filter_by_sum_cat_all($items, $params)
    {
        return self::_filter_by_sum_cat($items, $params, true);
    }

    private static function filter_by_sum_feat($items, $params)
    {
        if (!empty($params['field']) && !empty($params['ext'])) {
            $result_items = self::_filter_by_feature($items, $params['field'], $params['op'], $params['ext']);
            if ($result_items) {
                return self::_filter_by_sum($result_items, $params['op2'], $params['value']);
            }
        }
        return array();
    }

    private static function filter_by_total($items, $params)
    {
        $order = shopAutobadgeData::getOrderInfo();
        $total = (float) shop_currency($order['total'], $order['currency'], wa('shop')->getConfig()->getCurrency(true), false);

        if (!self::execute_operator($params['op'], $total, $params['value'])) {
            return array();
        }

        return $items;
    }

    private static function filter_by_total_feat($items, $params)
    {
        if (!empty($params['field'])) {
            $feature_id = $params['field'];
            // Суммируем значение характеристики
            $feature = 0;
            // Получаем все характеристики и присваиваем товарам их характеристики
            shopAutobadgeData::getFeatures($items);
            foreach ($items as $it) {
                $product = $it['product'];
                $item_sku_id = self::getProductId($it, 'sku');
                if (isset($product['selectable_features'][$item_sku_id])) {
                    if (!isset($product['selectable_features'][$item_sku_id][$feature_id])) {
                        continue;
                    }
                    $feature_sku_value = $product['selectable_features'][$item_sku_id][$feature_id];
                    if (isset($product['features'][$feature_id][$feature_sku_value])) {
                        // Если у товара существует характеристика, и она является объектом
                        if (is_object($product['features'][$feature_id][$feature_sku_value]) && $product['features'][$feature_id][$feature_sku_value] instanceof shopDimensionValue) {
                            $feature += $product['features'][$feature_id][$feature_sku_value]->value_base_unit * $it['quantity'];
                        } // Обработка других типов характеристик
                        else {
                            $feature += shopAutobadgeHelper::floatVal($product['features'][$feature_id][$feature_sku_value]) * $it['quantity'];
                        }
                    }
                } else {
                    // Если у товара существует характеристика, и она является объектом
                    if (isset($product['features'][$feature_id])) {
                        // Обработка массива
                        if (is_array($product['features'][$feature_id])) {
                            foreach ($product['features'][$feature_id] as $pff) {
                                if (is_object($pff) && $pff instanceof shopDimensionValue) {
                                    $feature += $pff->value_base_unit * $it['quantity'];
                                }
                            }
                        } // Обработка объекта shopDimensionValue
                        elseif (is_object($product['features'][$feature_id]) && $product['features'][$feature_id] instanceof shopDimensionValue) {
                            $feature += $product['features'][$feature_id]->value_base_unit * $it['quantity'];
                        } else {
                            // Обработка других типов характеристик
                            $feature += shopAutobadgeHelper::floatVal($product['features'][$feature_id]) * $it['quantity'];
                        }
                    }
                }
            }
            if (self::execute_operator($params['op'], $feature, $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    /*     * **
     * Свойства товара
     * * */

    private static function _filter_by_product_ternary($items, $params, $name, $strip_tags = false)
    {
        foreach ($items as $k => $item) {
            if (!self::execute_operator($params['op'], isset($item['product'][$name]) ? ($strip_tags ? strip_tags($item['product'][$name]) : $item['product'][$name]) : '', $params['value'])) {
                unset($items[$k]);
            }
        }

        return $items;
    }

    private static function filter_by_product_name($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'name');
    }

    private static function filter_by_product_summary($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'summary', true);
    }

    private static function filter_by_product_mt($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'meta_title');
    }

    private static function filter_by_product_mk($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'meta_keywords');
    }

    private static function filter_by_product_md($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'meta_description');
    }

    private static function filter_by_product_description($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'description', true);
    }

    private static function filter_by_product_rating($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'rating');
    }

    private static function filter_by_product_rating_count($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'rating_count');
    }

    private static function filter_by_product_min_price($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'min_price');
    }

    private static function filter_by_product_max_price($items, $params)
    {
        return self::_filter_by_product_ternary($items, $params, 'max_price');
    }

    private static function filter_by_product_total_sales($items, $params)
    {
        $period = shopAutobadgeHelper::getPeriod(!empty($params['period_type']) ? $params['period_type'] : 'alltime', $params);

        // Получаем продажи для товаров
        $total_sales = shopAutobadgeData::getTotalSales($items, $period);
        if ($total_sales) {
            foreach ($items as $k => $item) {
                if (!self::execute_operator($params['op'], $total_sales[self::getProductId($item)], $params['value'])) {
                    unset($items[$k]);
                }
            }
        } else {
            return array();
        }
        return $items;
    }

    private static function filter_by_product_number_sales($items, $params)
    {
        $period = shopAutobadgeHelper::getPeriod(!empty($params['period_type']) ? $params['period_type'] : 'alltime', $params);

        // Получаем продажи для товаров
        $total_number_sales = shopAutobadgeData::getTotalNumberSales($items, $period);
        if ($total_number_sales) {
            foreach ($items as $k => $item) {
                if (!self::execute_operator($params['op'], $total_number_sales[self::getProductId($item)], $params['value'])) {
                    unset($items[$k]);
                }
            }
        } else {
            return array();
        }
        return $items;
    }

    private static function filter_by_product_margin($items, $params)
    {
        foreach ($items as $k => $item) {
            $item['primary_purchase_price'] = isset($item['primary_purchase_price']) ? $item['primary_purchase_price'] : 0;
            if (!self::execute_operator($params['op'], ($item['primary_price'] - $item['primary_purchase_price']), $params['value'])) {
                unset($items[$k]);
            }
        }

        return $items;
    }

    private static function filter_by_product_margin_comp($items, $params)
    {
        foreach ($items as $k => $item) {
            $item['primary_compare_price'] = isset($item['primary_compare_price']) ? $item['primary_compare_price'] : 0;
            if (!self::execute_operator($params['op'], ($item['primary_compare_price'] - $item['primary_price']), $params['value'])) {
                unset($items[$k]);
            }
        }

        return $items;
    }

    private static function _filter_by_direct_item($items, $params, $name)
    {
        foreach ($items as $k => $item) {
            $item[$name] = isset($item[$name]) ? $item[$name] : '';
            if (!self::execute_operator($params['op'], $item[$name], $params['value'])) {
                unset($items[$k]);
            }
        }

        return $items;
    }

    private static function filter_by_product_sku($items, $params)
    {
        return self::_filter_by_direct_item($items, $params, 'sku_code');
    }

    private static function filter_by_product_sku_name($items, $params)
    {
        return self::_filter_by_direct_item($items, $params, 'sku_name');
    }

    private static function filter_by_product_price($items, $params)
    {
        return self::_filter_by_direct_item($items, $params, 'primary_price');
    }

    private static function filter_by_product_compare_price($items, $params)
    {
        return self::_filter_by_direct_item($items, $params, 'primary_compare_price');
    }

    private static function filter_by_product_purchase_price($items, $params)
    {
        return self::_filter_by_direct_item($items, $params, 'primary_purchase_price');
    }

    private static function _filter_by_product_date($items, $params, $name)
    {
        if ($params['value']) {
            foreach ($items as $k => $item) {
                if (empty($item['product'][$name]) || !self::execute_operator($params['op'], strtotime($item['product'][$name]), strtotime($params['value']))) {
                    unset($items[$k]);
                }
            }
        }

        return $items;
    }

    private static function filter_by_product_create($items, $params)
    {
        return self::_filter_by_product_date($items, $params, 'create_datetime');
    }

    private static function filter_by_product_edit($items, $params)
    {
        return self::_filter_by_product_date($items, $params, 'edit_datetime');
    }

    private static function _filter_by_product_media($items, $params, $name)
    {
        foreach ($items as $k => $item) {
            if (!self::execute_operator($params['op'], (int) !empty($item['product'][$name]), 1)) {
                unset($items[$k]);
            }
        }
        return $items;
    }

    private static function filter_by_product_video($items, $params)
    {
        return self::_filter_by_product_media($items, $params, 'video_url');
    }

    private static function filter_by_product_image($items, $params)
    {
        return self::_filter_by_product_media($items, $params, 'image_id');
    }

    private static function filter_by_product_age($items, $params)
    {
        if ($params['value']) {
            foreach ($items as $k => $item) {
                if (empty($item['product']['create_datetime'])) {
                    unset($items[$k]);
                    continue;
                }
                $age = (time() - strtotime($item['product']['create_datetime'])) / (60 * 60 * 24);
                if (!self::execute_operator($params['op'], $age, $params['value'])) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    private static function filter_by_product_stock($items, $params)
    {
        $params['field'] = $params['field'] == '' ? 'all' : $params['field'];
        $params['value'] = $params['value'] == '' ? 2147483647 : $params['value'];
        $params['sum_type'] = isset($params['sum_type']) ? $params['sum_type'] : 'not_sum';
        $type = $params['sum_type'] === 'not_sum' ? 'sku' : 'product';
        $skus_count = self::getSkuStocksCount($type);
        $product_skus_count = $product_items = array();
        foreach ($items as $k => $item) {
            $id = self::getProductId($item, $type);
            $key = $type . $id;
            // Если необходимо просуммировать артикулы
            if ($type === 'product') {
                $product_skus_count[$id] = isset($skus_count[$key]) ? $skus_count[$key] : [];
                $product_items[$id][] = $k;
            } // Если каждый артикул рассчитывается отдельно
            elseif (!self::checkStockAvailability($item['count'], $params, isset($skus_count[$key]) ? $skus_count[$key] : array())) {
                unset($items[$k]);
            }
        }
        // Расчет для случая, когда необходимо просуммировать артикулы
        if ($product_skus_count) {
            foreach ($product_skus_count as $product_id => $p) {
                if (!self::checkStockAvailability($p['count'], $params, $p)) {
                    foreach ($product_items[$product_id] as $i) {
                        unset($items[$i]);
                    }
                }
            }
        }
        return $items;
    }

    private static function checkStockAvailability($item_count, $params, $skus_count = array())
    {
        $count = 0;
        // Если у товара имеется разбиение по складам
        if ($skus_count) {
            $stocks = shopAutobadgeHelper::getStocks();
            if (($params['field'] == 'all' || $params['field'] == 'any' || $params['field'] == 'each') && $stocks) {
                $stop = 1;
                foreach ($stocks as $st_id => $st) {
                    // Определяем, с какими складами следует работать: виртуальными, реальными или обоими
                    if (($params['stock_type'] == 'real' && !is_numeric($st_id)) || ($params['stock_type'] == 'virt' && is_numeric($st_id))) {
                        continue;
                    }
                    // Если записи для склада не существует, значит на складе бесконечность или товар отсутствует полностью
                    if ($item_count == 0) {
                        $stock_count = 0;
                    } elseif (isset($skus_count[$st_id])) {
                        $stock_count = $skus_count[$st_id];
                    } else {
                        $stock_count = 2147483647;
                    }
                    // Остатки для любого склада
                    if ($params['field'] == 'any') {
                        if (self::execute_operator($params['op'], $stock_count, $params['value'])) {
                            $stop = 0;
                            break;
                        }
                    } // Остатки для каждого склада
                    elseif ($params['field'] == 'each') {
                        if (!self::execute_operator($params['op'], $stock_count, $params['value'])) {
                            return false;
                        }
                    } elseif ($params['field'] == 'all') {
                        $count = $stock_count === 2147483647 ? 2147483647 : ($count + $stock_count);
                    }
                }
                if ($params['field'] == 'any' && $stop) {
                    return false;
                } elseif ($params['field'] !== 'all') {
                    return true;
                } else {
                    $item_count = $count;
                }
            } else {
                $item_count = isset($skus_count[$params['field']]) ? $skus_count[$params['field']] : 2147483647;
            }
        }

        $item_count = $item_count === null ? 2147483647 : $item_count;
        if (!self::execute_operator($params['op'], $item_count, $params['value'])) {
            return false;
        }
        return true;
    }

    private static function filter_by_product_stock_change($items, $params)
    {
        $period = shopAutobadgeHelper::getPeriod(!empty($params['period_type']) ? $params['period_type'] : 'alltime', $params);

        // Получаем продажи для товаров
        $total_sales = shopAutobadgeData::getTotalSales($items, $period);
        if ($total_sales) {
            foreach ($items as $k => $item) {
                if (!self::execute_operator($params['op'], $total_sales[self::getProductId($item)], $params['value'])) {
                    unset($items[$k]);
                }
            }
        } else {
            return array();
        }
        return $items;
    }

    private static function filter_by_product_services($items, $params)
    {
        if ($params['field']) {
            $service_id = $params['field'];
            $service_variant_id = !empty($params['value']) ? $params['value'] : 0;

            foreach ($items as $k => $item) {
                if ($service_variant_id) {
                    $service = isset($item['product_services'][$service_id][$service_variant_id]);
                } else {
                    $service = isset($item['product_services'][$service_id]);
                }
                if (!self::execute_operator($params['op'], (int) $service, 1)) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    private static function filter_by_product_tags($items, $params)
    {
        $product_ids = array();
        foreach ($items as $k => $item) {
            $product_id = self::getProductId($item);
            $product_ids[$k] = $product_id;
        }
        $tags = shopAutobadgeData::getProductTags($product_ids);
        foreach ($product_ids as $k => $p_id) {
            if (!self::execute_operator($params['op'], $params['value'], isset($tags[$p_id]) ? $tags[$p_id] : array())) {
                unset($items[$k]);
            }
        }
        return $items;
    }

    private static function filter_by_prod_each_price($items, $params)
    {
        foreach ($items as $item) {
            if (!self::execute_operator($params['op'], $item['primary_price'], $params['value'])) {
                return array();
            }
        }
        return $items;
    }

    private static function filter_by_product_badge_type($items, $params)
    {
        foreach ($items as $k => $item) {
            $type = !empty($item['autobadge-type']) ? $item['autobadge-type'] : 'default';
            if (!self::execute_operator($params['op'], $type, $params['value'])) {
                unset($items[$k]);
            }
        }
        return $items;
    }

    /*     * **
     * Пользователь
     * * */

    private static function filter_by_ucat($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if ($params['value']) {
            try {
                // Проверяем наличие плагина Контакты PRO
                wa('contacts')->getPlugin('pro');
                $contact_categories = (new contactsViewModel())->getAllViews(null, true);
                if (isset($contact_categories[$params['value']])) {
                    if (!empty($contact_categories[$params['value']]['category_id'])) {
                        if (!self::execute_operator($params['op'], (int) (new waContactCategoriesModel())->inCategory($contact_id, $contact_categories[$params['value']]['category_id']), 1)) {
                            return array();
                        }
                    } elseif ($contact_categories[$params['value']]['type'] == 'search') {
                        $hash = $params['value'] ? 'view/' . $params['value'] : '';
                        $collection = new waContactsCollection($hash);
                        $users = $collection->getContacts('*', 0, $collection->count());
                        if (!self::execute_operator($params['op'], (int) isset($users[$contact_id]), 1)) {
                            return array();
                        }
                    }
                }
            } catch (Exception $ex) {
                if (!self::execute_operator($params['op'], (int) (new waContactCategoriesModel())->inCategory($contact_id, $params['value']), 1)) {
                    return array();
                }
            }
        }
        return $items;
    }

    private static function filter_by_user($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if (self::execute_operator($params['op'], (int) $contact_id, (int) $params['value'])) {
            return $items;
        }
        return array();
    }

    private static function filter_by_user_date($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if ($contact_id && $params['value']) {
            $create_datetime = self::$user->get("create_datetime");
            if ($create_datetime && self::execute_operator($params['op'], strtotime($create_datetime), strtotime($params['value']))) {
                return $items;
            }
        }
        return array();
    }

    /*     * **
     * Выполненные заказы
     * * */

    private static function filter_by_all_orders($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        $customer = (new shopCustomerModel())->getById($contact_id);
        $total_spent = $customer ? $customer['total_spent'] : 0;

        if (self::execute_operator($params['op'], $total_spent, $params['value'])) {
            return $items;
        }
        return array();
    }

    private static function filter_by_order_int($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if ($contact_id) {
            $order_model = new shopOrderModel();
            $sql = "SELECT SUM(total*rate) FROM " . $order_model->getTableName() . " WHERE paid_date IS NOT NULL AND contact_id = '" . (int) $contact_id . "'";
            if (!empty($params['period_type'])) {
                $period = shopAutobadgeHelper::getPeriod($params['period_type'], $params);
                if ($period['start']) {
                    $sql .= " AND paid_date >= '" . $period['start'] . "'";
                }
                if ($period['end']) {
                    $sql .= " AND paid_date <= '" . $period['end'] . "'";
                }
            }
            $total = $order_model->query($sql)->fetchField();
            if (self::execute_operator($params['op'], $total, $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_count_orders($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        $customer = (new shopCustomerModel())->getById($contact_id);
        $number_of_orders = $customer ? (int) $customer['number_of_orders'] : 0;
        if (self::execute_operator($params['op'], $number_of_orders, $params['value'])) {
            return $items;
        }
        return array();
    }

    private static function filter_by_order_count_int($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);

        if ($contact_id) {
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id;
            $hash .= shopAutobadgeHelper::convertPeriodToUrl($params);
            $collection = new shopOrdersCollection($hash);
            if (self::execute_operator($params['op'], $collection->count(), $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_order_prod($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);

        if ($contact_id && $params['value']) {
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id . '&items.' . $params['product_type'] . '_id=' . (int) $params['value'];
            $collection = new shopOrdersCollection($hash);
            if ($collection->count() > 0) {
                return $items;
            }
        }
        return array();
    }

    private static function _filter_by_order_items($orders, $items)
    {
        $order_items = array();
        foreach ($orders as $order) {
            if (!empty($order['items'])) {
                foreach ($order['items'] as $oi) {
                    if ($oi['type'] !== 'product') {
                        continue;
                    }
                    $order_items[$oi['sku_id']] = $oi['sku_id'];
                }
            }
        }
        foreach ($items as $k => $i) {
            if (!isset($order_items[$i['sku_id']])) {
                unset($items[$k]);
            }
        }
        return $items ? $items : array(self::getAbstractProduct());
    }

    private static function filter_by_order_prod_int($items, $params)
    {
        static $orders = array();

        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if ($contact_id) {
            $function_hash = self::getRequestHash($contact_id, $params);
            if (!isset($orders[$function_hash])) {
                $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id . ($params['value'] ? '&items.' . $params['product_type'] . '_id=' . (int) $params['value'] : '');
                $hash .= shopAutobadgeHelper::convertPeriodToUrl($params);
                $collection = new shopOrdersCollection($hash);
                $orders[$function_hash] = $collection->getOrders("*,items", 0, $collection->count());
            }
            if (count($orders[$function_hash]) > 0) {
                return self::_filter_by_order_items($orders[$function_hash], $items);
            }
        }
        return array();
    }

    private static function _filter_by_order_prod_cat($items, $params, $all = false)
    {
        static $orders = array();

        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if ($contact_id && $params['value']) {
            $function_hash = self::getRequestHash($contact_id, $params, $all);
            if (!isset($orders[$function_hash])) {
                $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id;
                $hash .= shopAutobadgeHelper::convertPeriodToUrl($params);
                $collection = new shopOrdersCollection($hash);
                $alias = $collection->addJoin('shop_order_items', ':table.order_id = o.id', ($params['field'] ? ':table.' . $params['product_type'] . '_id = ' . (int) $params['field'] : null));
                if ($all) {
                    $descendants = self::getModel('category')->descendants($params['value'], true)->fetchAll('id');
                    if ($descendants) {
                        $categories = array_keys($descendants);
                    }
                }
                $collection->addJoin('shop_category_products', ':table.product_id = ' . $alias . '.product_id', ':table.category_id ' . ($all ? 'IN (' . (!empty($categories) ? implode(",", $categories) : $params['value']) . ')' : '=' . (int) $params['value']));
                $orders[$function_hash] = $collection->getOrders("*,items", 0, $collection->count());
            }
            if (count($orders[$function_hash]) > 0) {
                return self::_filter_by_order_items($orders[$function_hash], $items);
            }
        }
        return array();
    }

    private static function filter_by_order_prod_cat($items, $params)
    {
        return self::_filter_by_order_prod_cat($items, $params);
    }

    private static function filter_by_order_prod_cat_all($items, $params)
    {
        return self::_filter_by_order_prod_cat($items, $params, true);
    }

    private static function filter_by_order_prod_cat_int($items, $params)
    {
        return self::_filter_by_order_prod_cat($items, $params);
    }

    private static function filter_by_order_prod_cat_all_int($items, $params)
    {
        return self::_filter_by_order_prod_cat($items, $params, true);
    }

    /*     * **
     * Дата и время
     * * */

    private static function filter_by_date($items, $params)
    {
        $order = shopAutobadgeData::getOrderInfo();

        if (!empty($order['id'])) {
            $date = strtotime(date("Y-m-d", strtotime($order['create_datetime'])));
        } else {
            $date = strtotime(date("Y-m-d"));
        }

        if ($params['value']) {
            if (self::execute_operator($params['op'], $date, strtotime($params['value']))) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_week($items, $params)
    {
        $order = shopAutobadgeData::getOrderInfo();

        if (!empty($order['id'])) {
            $week = date("w", strtotime($order['create_datetime']));
        } else {
            $week = date("w");
        }

        $params['value'] = (int) $params['value'];
        if ($params['value']) {
            $params['value'] = $params['value'] == 7 ? 0 : $params['value'];
            if ($week == $params['value']) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_time($items, $params)
    {
        $hour = (int) $params['hour'];
        $minute = (int) $params['minute'];

        if ($hour >= 23 || $hour <= 0) {
            $hour = 0;
        }
        if ($minute >= 60 || $minute <= 0) {
            $minute = 0;
        }

        $value = mktime($hour, $minute, 0, 12, 32, 1997);

        $order = shopAutobadgeData::getOrderInfo();
        if (!empty($order['id'])) {
            $now = mktime(date("G", strtotime($order['create_datetime'])), date("i", strtotime($order['create_datetime'])), 0, 12, 32, 1997);
        } else {
            $now = mktime(date('G'), date('i'), 0, 12, 32, 1997);
        }

        if (self::execute_operator($params['op'], $now, $value)) {
            return $items;
        }
        return array();
    }

    /*     * **
     * Переменные
     * * */

    private static function _filter_by_var($items, $params, $name)
    {
        $order = shopAutobadgeData::getOrderInfo();
        if (!empty($params['field'])) {
            // Если обрабатывается сделанный заказ
            if (!empty($order['id'])) {
                $param = !empty($order[$name]) ? $order[$name] : array();
            } // Если заказ еще не оформлен, а находится только в корзине
            else {
                $param = $name == 'autobadge_cookie' ? waRequest::cookie() : wa('shop')->getStorage()->getAll();
            }
            if (isset($param[$params['field']]) && self::execute_operator($params['op'], $param[$params['field']], $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_cookie($items, $params)
    {
        return self::_filter_by_var($items, $params, 'autobadge_cookie');
    }

    private static function filter_by_session($items, $params)
    {
        return self::_filter_by_var($items, $params, 'autobadge_session');
    }

    private static function _filter_by_request($items, $params, $name)
    {
        $order = shopAutobadgeData::getOrderInfo();
        if (!empty($params['field'])) {
            // Если обрабатывается сделанный заказ
            if (!empty($order['id'])) {
                $param = !empty($order[$name]) ? $order[$name] : array();
            } // Если заказ еще не оформлен, а находится только в корзине
            else {
                $request = wa('shop')->getStorage()->get($name);
                $request = $request ? (array) $request : array();
                $param = $name == 'autobadge_get' ? waRequest::get(null, array()) : ($name == 'autobadge_server' ? waRequest::server(null, array()) : waRequest::post(null, array()));
            }
            if (isset($param[$params['field']])) {
                if (self::execute_operator($params['op'], $param[$params['field']], $params['value'])) {
                    $value = array($params['field'] => $params['value']);
                    $request = !empty($request) ? array_merge($request, $value) : $value;
                    wa('shop')->getStorage()->set($name, $request);
                    return $items;
                } elseif (isset($request[$params['field']])) {
                    unset($request[$params['field']]);
                    wa('shop')->getStorage()->set($name, $request);
                }
            } elseif (isset($request[$params['field']]) && self::execute_operator($params['op'], $request[$params['field']], $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_get($items, $params)
    {
        return self::_filter_by_request($items, $params, 'autobadge_get');
    }

    private static function filter_by_post($items, $params)
    {
        return self::_filter_by_request($items, $params, 'autobadge_post');
    }

    private static function filter_by_server($items, $params)
    {
        return self::_filter_by_request($items, $params, 'autobadge_server');
    }

    /*     * **
     * Витрина
     * * */

    private static function filter_by_storefront($items, $params)
    {
        $order = shopAutobadgeData::getOrderInfo();
        if ($params['value']) {
            if (!empty($order['id'])) {
                $storefront = !empty($order['params']['storefront']) ? $order['params']['storefront'] : '';
            } else {
                $routing_url = wa('shop')->getRouting()->getRootUrl();
                $storefront = shopAutobadgeHelper::getDomain() . ($routing_url ? '/' . $routing_url : '');
            }
            $storefront = shopAutobadgeHelper::removeLastChar($storefront, '/');

            $params['value'] = shopAutobadgeHelper::removeLastChar($params['value'], '*');
            $params['value'] = shopAutobadgeHelper::removeLastChar($params['value'], '/');

            if (self::execute_operator($params['op'], $storefront, $params['value'])) {
                return $items;
            }
        } elseif ($params['field']) {
            wa("site");
            $domains = (new siteDomainModel())->getAll('name');
            if (!empty($order['id'])) {
                $storefront = !empty($order['params']['storefront']) ? $order['params']['storefront'] : '';
                $storefront = shopAutobadgeHelper::removeLastChar($storefront, '/');
                // Чтобы определить с нужного ли домена сделан заказ, мы сначала найдем домен, заданный в условии.
                // После этого проверим совпадает ли начало витрины с доменом
                foreach ($domains as $d_k => $d) {
                    if ($d['id'] == $params['field']) {
                        if (strpos($storefront, $d_k) !== false) {
                            return $items;
                        }
                    }
                }
            } else {
                $domain = wa('shop')->getRouting()->getDomain();
                if (isset($domains[$domain]) && $domains[$domain]['id'] == $params['field']) {
                    return $items;
                }
            }
        }
        return array();
    }

    private static function _filter_by_delpay($items, $params, $return_bool = false, $type = 'shipping')
    {
        $order = shopAutobadgeData::getOrderInfo();
        if (!empty($order['id'])) {
            $delpay_method = !empty($order['params'][$type == 'shipping' ? 'shipping_id' : 'payment_id']) ? $order['params'][$type == 'shipping' ? 'shipping_id' : 'payment_id'] : 0;
        } else {
            $order_params = wa('shop')->getStorage()->get('shop/checkout');
            $delpay_method = 0;
            if ($type == 'shipping' && !empty($order_params['shipping']['id'])) {
                $delpay_method = $order_params['shipping']['id'];
            } elseif ($type == 'payment' && !empty($order_params['payment'])) {
                $delpay_method = $order_params['payment'];
            }
        }
        if (self::execute_operator($params['op'], $delpay_method, $params['value'])) {
            return $return_bool ? true : $items;
        }
        return $return_bool ? false : array();
    }

    protected static function filter_by_shipping($items, $params, $return_bool = false)
    {
        return self::_filter_by_delpay($items, $params, $return_bool);
    }

    protected static function filter_by_payment($items, $params, $return_bool = false)
    {
        return self::_filter_by_delpay($items, $params, $return_bool, 'payment');
    }

    private static function filter_by_mobile($items, $params)
    {
        if (self::execute_operator($params['op'], waRequest::isMobile() ? 1 : 0, 1)) {
            return $items;
        }
        return array();
    }

    private static function filter_by_theme($items, $params)
    {
        if ($params['value']) {
            if (!self::execute_operator($params['op'], waRequest::getTheme(), $params['value'])) {
                return array();
            }
        }
        return $items;
    }

    private static function filter_by_product_page($items, $params)
    {
        foreach ($items as $k => $i) {
            $is_product_page = isset($i['autobadge-page']) && $i['autobadge-page'] == 'product';
            if (!self::execute_operator($params['op'], $is_product_page ? 1 : 0, 1)) {
                unset($items[$k]);
            }
        }

        return $items;
    }

    /**
     * Check, if operator exists
     *
     * @param string $operator
     * @return boolean|string
     */
    private static function prepare_operator($operator)
    {
        $instance = self::get_instance();
        $operator_func = 'operator_' . $operator;
        if (!method_exists($instance, $operator_func)) {
            return false;
        } else {
            return $operator_func;
        }
    }

    /**
     * Execute operator function
     *
     * @param string $operator
     * @param array|string $val1
     * @param array|string $val2
     * @param bool $range_value
     * @return bool
     */
    private static function execute_operator($operator, $val1, $val2, $range_value = false)
    {
        $operator_name = 'operator_' . $operator . ($range_value ? "_range" : "");
        return self::$operator_name($val1, $val2);
    }

    /**
     * Equal operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_eq($val1, $val2)
    {
        if (is_array($val2)) {
            return in_array($val1, $val2);
        } elseif (is_array($val1)) {
            return in_array($val2, $val1);
        } else {
            return $val1 == $val2;
        }
    }

    /**
     * Equal operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_eq_range($val1, $val2)
    {
        return ($val2 >= $val1[0] && $val2 <= $val1[1]);
    }

    /**
     * Not equal operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_neq($val1, $val2)
    {
        return !self::operator_eq($val1, $val2);
    }

    /**
     * Not equal operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_neq_range($val1, $val2)
    {
        return !self::operator_eq_range($val1, $val2);
    }

    /**
     * Greater operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_gt($val1, $val2)
    {
        if (is_array($val2)) {
            foreach ($val2 as $v) {
                if ((float) $val1 < (float) $v) {
                    return false;
                }
            }
        } elseif (is_array($val1)) {
            foreach ($val1 as $v) {
                if ((float) $v > (float) $val2) {
                    return true;
                }
            }
        } else {
            return (float) $val1 > (float) $val2;
        }
    }

    /**
     * Greater operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_gt_range($val1, $val2)
    {
        return self::operator_gt($val1, $val2);
    }

    /**
     * Great or equal operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_gte($val1, $val2)
    {
        if (is_array($val2)) {
            foreach ($val2 as $v) {
                if ((float) $val1 <= (float) $v) {
                    return false;
                }
            }
        } elseif (is_array($val1)) {
            foreach ($val1 as $v) {
                if ((float) $v >= (float) $val2) {
                    return true;
                }
            }
        } else {
            return (float) $val1 >= (float) $val2;
        }
    }

    /**
     * Great or equal operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_gte_range($val1, $val2)
    {
        return self::operator_gte($val1, $val2);
    }

    /**
     * Less operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_lt($val1, $val2)
    {
        return (float) $val1 === (float) $val2 ? false : !self::operator_gt($val1, $val2);
    }

    /**
     * Less operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_lt_range($val1, $val2)
    {
        return self::operator_lt($val1, $val2);
    }

    /**
     * Less or equal operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_lte($val1, $val2)
    {
        if (is_array($val2)) {
            foreach ($val2 as $v) {
                if ((float) $val1 >= (float) $v) {
                    return false;
                }
            }
        } elseif (is_array($val1)) {
            foreach ($val1 as $v) {
                if ((float) $v <= (float) $val2) {
                    return true;
                }
            }
        } else {
            return (float) $val1 <= (float) $val2;
        }
    }

    /**
     * Less or equal operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_lte_range($val1, $val2)
    {
        return self::operator_lte($val1, $val2);
    }

    /**
     * Equal number operator
     *
     * @param array|float $val1
     * @param array|float $val2
     * @return bool
     */
    private static function operator_eq_num($val1, $val2)
    {
        if (is_array($val2)) {
            return in_array((float) $val1, $val2);
        } elseif (is_array($val1)) {
            return in_array((float) $val2, $val1);
        } else {
            return (float) $val1 === (float) $val2;
        }
    }

    /**
     * Equal number operator for range feature
     *
     * @param array|float $val1
     * @param float $val2
     * @return bool
     */
    private static function operator_eq_num_range($val1, $val2)
    {
        return ((float) $val2 >= (float) $val1[0] && (float) $val2 <= (float) $val1[1]);
    }

    /**
     * Not equal number operator
     *
     * @param array|float $val1
     * @param array|float $val2
     * @return bool
     */
    private static function operator_neq_num($val1, $val2)
    {
        return !self::operator_eq_num($val1, $val2);
    }

    /**
     * Not equal number operator for range feature
     *
     * @param array|float $val1
     * @param float $val2
     * @return bool
     */
    private static function operator_neq_num_range($val1, $val2)
    {
        return !self::operator_eq_num_range($val1, $val2);
    }

    /**
     * Contains operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_cont($val1, $val2)
    {
        if (!is_array($val1) && !is_array($val2) && $val1 && $val2) {
            return mb_strpos($val1, $val2, 0, 'UTF-8') !== false;
        }
        return false;
    }

    /**
     * Contains operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_cont_range($val1, $val2)
    {
        return self::operator_eq_range($val1, $val2);
    }

    /**
     * Not contains operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_notcont($val1, $val2)
    {
        return !self::operator_cont($val1, $val2);
    }

    /**
     * Not contains operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_notcont_range($val1, $val2)
    {
        return self::operator_neq_range($val1, $val2);
    }

    /**
     * Begins operator
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_begins($val1, $val2)
    {
        if (!is_array($val1) && !is_array($val2) && $val1 && $val2) {
            return mb_strpos($val1, $val2, 0, 'UTF-8') === 0;
        }
        return false;
    }

    /**
     * Begins operator for range feature
     *
     * @param array|string $val1
     * @param array|string $val2
     * @return bool
     */
    private static function operator_begins_range($val1, $val2)
    {
        return self::operator_eq($val1[0], $val2);
    }

    /**
     * Get class instance
     *
     * @staticvar shopAutobadgeConditions|null $instance
     * @return shopAutobadgeConditions|null
     */
    protected static function get_instance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = get_class();
        }
        return $instance;
    }

    /**
     * Get product IDs from array of items
     *
     * @param array $items
     * @param string $product_type
     * @return array
     */
    private static function getProductIds($items, $product_type = 'product')
    {
        $ids = array();
        foreach ($items as $i) {
            $ids[] = self::getProductId($i, $product_type);
        }
        return $ids;
    }

    /**
     * Get all product items of group
     *
     * @param array|null $items
     * @return array
     */
    protected static function getAllItems($items = array())
    {
        self::$all_items = ($items !== null) ? $items : self::$all_items;
        return self::$all_items;
    }

}
