<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 * 
 */

class shopDelpayfilterConditions extends shopDelpayfilterData
{

    // Старшинство типов условий
    private static $type_precedence = array(
        'ucat' => 0, 'user' => 0, 'user_date' => 0, 'user_data' => 0, 'product_stock' => 0, 'user_country' => 0, 'shipping' => 0, 'user_city' => 0,
        'user_auth' => 0, 'all_orders' => 0, 'order_int' => 0,
        'count_orders' => 0, 'order_count_int' => 0, 'order_prod' => 0, 'order_prod_int' => 0, 'order_prod_cat' => 0, 'order_prod_cat_all' => 0,
        'order_prod_cat_int' => 0, 'order_prod_cat_all_int' => 0, 'date' => 0, 'week' => 0, 'time' => 0, 'cookie' => 0, 'session' => 0, 'get' => 0,
        'post' => 0, 'not_isset_post' => 0, 'not_isset_get' => 0, 'storefront' => 0, 'order_status' => 0,
        'cat' => 1, 'cat_all' => 1, 'set' => 1, 'type' => 1, 'product' => 1, 'feature' => 1, 'services' => 1,
        'num' => 2, 'num_prod' => 2, 'num_cat' => 2, 'num_cat_all' => 2, 'num_set' => 2, 'num_type' => 2, 'num_feat' => 2, 'num_items' => 2, 'total' => 2, 'sum' => 2, 'sum_cat' => 2, 'sum_cat_all' => 2,
        'num_all_cat' => 2, 'num_all_cat_all' => 2, 'num_all_set' => 2, 'num_all_type' => 2, 'sum_feat' => 2, 'total_feat' => 2, 'prod_price' => 2, 'prod_each_price' => 2, 'shipping_price' => 2
    );
    private static $optsEqNe;
    private static $optsAll;
    private static $optCats;
    private static $optsText;
    private static $optsNum;
    private static $optsNum2;
    private static $optsSumSku;
    private static $optsStocks;
    protected static $types;
    protected static $targets;
    protected static $total = 0;

    /**
     * Decode JSON object
     *
     * @param string $json
     * @return array
     */
    public static function decode($json)
    {
        return is_string($json) ? shopDelpayfilterHelper::object_to_array(json_decode($json)) : $json;
    }

    /**
     * Init conditions
     *
     * @param array $conditions
     * @param array $targets
     * @return boolean
     */
    public static function init($conditions, $targets)
    {
        static $inited = false;
        if ($inited) {
            return true;
        }

        self::initConstants();
        self::getTypesData($conditions, $targets);

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
        $currency = waCurrency::getInfo(wa()->getConfig()->getCurrency(true));
        self::$types = array(
            'cat' => array(_wp('Category'), self::$optsEqNe, self::$optCats),
            'cat_all' => array(_wp('Category and subcategories'), self::$optsEqNe, self::$optCats),
            'set' => array(_wp('Product set'), self::$optsEqNe, array('type' => 'set', 'placeholder' => _wp('Select product set'), 'id' => 'set', 'name' => 'value')),
            'type' => array(_wp('Product type'), self::$optsEqNe, array('type' => 'type', 'placeholder' => _wp('Select product type'), 'id' => 'type', 'name' => 'value')),
            'product' => array(_wp('Product'), self::$optsEqNe, array('type' => 'product', 'link' => _wp("select product"), 'name' => 'value')),
            'feature' => array(
                _wp('Product feature'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select', 'width' => '350px'),
                self::$optsAll,
                array('type' => 'feature', 'id' => 'featureValue', 'name' => 'value', 'placeholder' => _wp('Select feature value'), 'class' => 'feature-value', 'width' => '350px'),
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'hidden' => 1, 'params' => array('style' => 'width: 150px; min-width: 150px', 'class' => 'feature-value-input'))
            ),
            'services' => array(
                _wp('Service'),
                self::$optsEqNe,
                array('type' => 'services', 'placeholder' => _wp('Select service'), 'id' => 'services', 'name' => 'field', 'class' => 'feature-select s-services', 'width' => '350px'),
                array('type' => 'services', 'id' => 'servicesVariants', 'name' => 'value', 'placeholder' => _wp('Select service variant'), 'class' => 'feature-value', 'width' => '350px'),
            ),
            'product_stock' => array(
                _wp('Product stock count'),
                self::$optsSumSku,
                _wp('on '),
                array('type' => 'stocks', 'placeholder' => _wp('all stocks'), 'id' => 'stocks', 'class' => 'stocks-select', 'name' => 'field'),
                self::$optsStocks,
                self::$optsNum,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px', 'placeholder' => '∞'))
            ),
            'num' => array(_wp('Total quantity of all products'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'num_prod' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_cat' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp('from category'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_cat_all' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp('from category and subcategories of'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_set' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp('from set'), self::$optsEqNe, array('type' => 'set', 'placeholder' => _wp('Select product set'), 'id' => 'set', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_type' => array(_wp('Quantity of product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp('from type'), self::$optsEqNe, array('type' => 'type', 'placeholder' => _wp('Select product type'), 'id' => 'type', 'name' => 'ext'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_all_cat' => array(_wp('Quantity of all products from category'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_all_cat_all' => array(_wp('Quantity of all products from category and subcategories'), self::$optsEqNe, array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_all_set' => array(_wp('Quantity of all products from set'), self::$optsEqNe, array('type' => 'set', 'placeholder' => _wp('Select product set'), 'id' => 'set', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_all_type' => array(_wp('Quantity of all products from type'), self::$optsEqNe, array('type' => 'type', 'placeholder' => _wp('Select product type'), 'id' => 'type', 'name' => 'field'), self::$optsNum2, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'num_feat' => array(
                _wp('Quantity of products with features'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select', 'width' => '350px'),
                self::$optsAll,
                array('type' => 'feature', 'id' => 'featureValue', 'name' => 'ext', 'placeholder' => _wp('Select feature value'), 'class' => 'feature-value', 'width' => '350px'),
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext', 'hidden' => 1, 'params' => array('style' => 'width: 90px', 'class' => 'feature-value-input')),
                self::$optsNum2,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')),
            ),
            'num_items' => array(_wp('Quantity of unique items'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'total' => array(_wp('Order price with discount'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'sum' => array(_wp('Total price of all products'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'sum_cat' => array(_wp('Total price of products'), _wp('from category'), array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), $currency['sign']),
            'sum_cat_all' => array(_wp('Total price of products'), _wp('from category and subcategories of'), array('type' => 'category', 'placeholder' => _wp('select category'), 'id' => 'cat', 'name' => 'field'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), $currency['sign']),
            'sum_feat' => array(
                _wp('Total price of all products with features'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select', 'width' => '350px'),
                self::$optsAll,
                array('type' => 'feature', 'id' => 'featureValue', 'name' => 'ext', 'placeholder' => _wp('Select feature value'), 'class' => 'feature-value', 'width' => '350px'),
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext', 'hidden' => 1, 'params' => array('style' => 'width: 90px', 'class' => 'feature-value-input')),
                self::$optsNum2,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')),
                $currency['sign']
            ),
            'total_feat' => array(
                _wp('Total sum of features values'),
                array('type' => 'feature', 'name' => 'field', 'placeholder' => _wp('Select product feature'), 'class' => 'feature-select extrem-feature-select', 'width' => '350px'),
                self::$optsNum,
                array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'hidden' => 1, 'params' => array('style' => 'width: 150px; min-width: 150px', 'class' => 'feature-value-input'))
            ),
            'prod_price' => array(_wp('Price of any product'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'prod_each_price' => array(_wp('Price of each product'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'ucat' => array(_wp('User category'), self::$optsEqNe, array('type' => 'ucat', 'placeholder' => _wp('Select user category'), 'id' => 'ucat', 'name' => 'value')),
            'user' => array(_wp('Contact'), self::$optsEqNe, array('type' => 'user', 'link' => _wp('select contact'), 'id' => 'user', 'name' => 'value')),
            'user_date' => array(_wp('Contact create datetime'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker'))),
            'user_country' => array(
                _wp('Contact country and region'),
                self::$optsEqNe,
                array('type' => 'dynamic', 'placeholder' => _wp('Select country'), 'id' => 'country', 'name' => 'field', 'class' => 'dynamic-select', 'width' => '350px', 'data-value-url' => 'region'),
                array('type' => 'dynamic', 'id' => 'region', 'dynamic_data_id' => 'country', 'name' => 'value', 'placeholder' => _wp('Select region'), 'class' => 'dynamic-value-template', 'width' => '350px'),
            ),
            'user_city' => array(_wp('User city'), self::$optsText, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 450px; min-width: 450px'))),
            'user_data' => array(_wp('User data'), array('type' => 'userData', 'placeholder' => _wp('Select data'), 'id' => 'user_data', 'name' => 'field'), self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'user_auth' => array(_wp('Contact'), self::$optsEqNe, _wp('authorized')),
            'shipping' => array(_wp('Shipping'), self::$optsEqNe, array('type' => 'shipping', 'placeholder' => _wp('Select shipping'), 'id' => 'shipping', 'name' => 'value')),
            'payment' => array(_wp('Payment'), _wp('equals'), array('type' => 'payment', 'placeholder' => _wp('Select payment'), 'id' => 'payment', 'name' => 'value')),
            'all_orders' => array(_wp('Total sum of all orders'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
            'order_int' => array(_wp('Sum of orders for period from'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker')), _wp('to'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker')), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px')), $currency['sign']),
            'count_orders' => array(_wp('Quantity of all orders'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'order_count_int' => array(_wp('Quantity of orders for period from'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker')), _wp('to'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker')), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 90px'))),
            'order_prod' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("select product"), 'name' => 'value')),
            'order_prod_int' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'value'), _wp('for period from'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker')), _wp('to'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker'))),
            'order_prod_cat' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp("from category"), self::$optCats),
            'order_prod_cat_all' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp("from category and subcategories of"), self::$optCats),
            'order_prod_cat_int' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp("from category"), self::$optCats, _wp('for period from'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext1', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker')), _wp('to'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext2', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker'))),
            'order_prod_cat_all_int' => array(_wp('Orders have product'), array('type' => 'product', 'link' => _wp("any product"), 'name' => 'field'), _wp("from category and subcategories of"), self::$optCats, _wp('for period from'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext1', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker')), _wp('to'), array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'ext2', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker'))),
            'order_status' => array(_wp('Status of any order'), self::$optsEqNe, array('type' => 'orderStatus', 'placeholder' => _wp('Select status'), 'id' => 'orderStatus', 'name' => 'value')),
            'date' => array(_wp('Date'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 120px', 'class' => 'init-datepicker'))),
            'week' => array(_wp('Day of week'), array('type' => 'select', 'control_type' => waHtmlControl::SELECT, 'name' => 'value', 'params' => array('style' => 'width: 110px', 'options' => array(array('title' => _wp("Monday"), 'value' => 1), array('title' => _wp("Tuesday"), 'value' => 2), array('title' => _wp("Wednesday"), 'value' => 3), array('title' => _wp("Thursday"), 'value' => 4), array('title' => _wp("Friday"), 'value' => 5), array('title' => _wp("Saturday"), 'value' => 6), array('title' => _wp("Sunday"), 'value' => 7))))),
            'time' => array(_wp('Time'), self::$optsNum, array('type' => 'time')),
            'cookie' => array('$_COOKIE["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'session' => array('$_SESSION["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'get' => array('$_GET["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'post' => array('$_POST["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]', self::$optsAll, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px'))),
            'not_isset_post' => array(_wp('Not isset') . ' $_POST["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]'),
            'not_isset_get' => array(_wp('Not isset') . ' $_GET["', array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'field', 'params' => array('style' => 'width: 150px; min-width: 150px')), '"]'),
            'storefront' => array(
                _wp('Storefront'),
                self::$optsEqNe,
                array('type' => 'storefront', 'placeholder' => _wp('Select domain'), 'width' => '350px', 'class' => 'storefront-domain', 'name' => 'field'),
                array('type' => 'storefront', 'placeholder' => _wp('Select route'), 'id' => 'storefrontRoutes', 'hidden' => 1, 'width' => '350px', 'class' => 'storefront-route', 'name' => 'value'),
            ),
            'shipping_price' => array(_wp('Shipping price'), self::$optsNum, array('type' => 'input', 'control_type' => waHtmlControl::INPUT, 'name' => 'value', 'params' => array('style' => 'width: 150px; min-width: 150px')), $currency['sign']),
        );

        self::$targets = array(
            'shipping' => _wp('Shipping'),
            'payment' => _wp("Payment"),
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
        $group_result_items = array();
        $has_group = $has_single_rule = 0;

        // Сортируем условия по старшинству
        $conditions = self::sort_by_precedence($conditions);

        foreach ($conditions as $c) {
            // Если перед нами группа скидок, разбираем ее
            if (isset($c['group_op'])) {
                $has_group = 1;
                $conditions2 = self::decode($c['conditions']);
                $result = self::filter_items($items, $c['group_op'], $conditions2);
                if ($group_andor == 'and' && !$result) {
                    $result_items = $group_result_items = array();
                    break;
                }
                $group_result_items += $result;
            } else {
                $has_single_rule = 1;
                if ($has_group) {
                    $result_items = $group_result_items;
                    $has_group = 0;
                }
                // Проверяем работоспособность оператора
                if (isset($c['op']) && !self::prepare_operator($c['op'])) {
                    continue;
                }
                // Фильтруем товары по типу
                $function_name = 'filter_by_' . $c['type'];
                if (method_exists($instance, $function_name)) {
                    $filtered_items = self::$function_name($group_andor == 'and' ? $result_items : $items, $c);
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
        if ($has_group && !$has_single_rule) {
            $result_items = $group_result_items;
        }

        return $result_items;
    }

    /**
     * Sort conditions by precedence
     *
     * @param array $conditions
     * @return array
     */
    private static function sort_by_precedence($conditions)
    {
        $sorted_conditions = array(0 => array(), 1 => array(), 2 => array());
        foreach ($conditions as $c) {
            if (isset($c['group_op'])) {
                $sorted_conditions[0][] = $c;
            } else {
                if (isset(self::$type_precedence[$c['type']])) {
                    $sorted_conditions[self::$type_precedence[$c['type']]][] = $c;
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
     * Фильтрующие условия. Возвращают массив
     * * */

    protected static function filter_by_cat($items, $params)
    {
        if (!empty($params['value'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if (!self::execute_operator($params['op'], $product_id, shopDelpayfilterData::getCategoryProducts($params['value']))) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    protected static function filter_by_cat_all($items, $params)
    {
        if (!empty($params['value'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if (!self::execute_operator($params['op'], $product_id, shopDelpayfilterData::getCategoryProducts($params['value'], true))) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    protected static function filter_by_set($items, $params)
    {
        if (!empty($params['value'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if (!self::execute_operator($params['op'], $product_id, shopDelpayfilterData::getSetProducts($params['value']))) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    protected static function filter_by_type($items, $params)
    {
        if (!empty($params['value'])) {
            foreach ($items as $k => $item) {
                $type_id = isset($item['type_id']) ? (int) $item['type_id'] : (isset($item['product']['type_id']) ? (int) $item['product']['type_id'] : 0);
                if (!self::execute_operator($params['op'], $type_id, $params['value'])) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    protected static function filter_by_services($items, $params)
    {
        $all_services = shopDelpayfilterData::getServices();
        if ($params['field'] && empty($params['value'])) {
            $service = $params['field'];
            $services = array_keys($all_services);
        } elseif (!empty($params['field']) && !empty($params['value'])) {
            $service = $params['value'];
            $services = !empty($all_services[$params['field']]) ? $all_services[$params['field']] : array();
        } else {
            return array();
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
                $product_id = self::getProductId($item);
                if (!self::execute_operator($params['op'], $product_id, $params['value'])) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }

    private static function _filter_by_feature($items, $feature_id, $operator, $value)
    {
        $result_items = array();
        // Если не было передано характеристики, прерываем обработку
        if (!$feature_id) {
            return $items;
        }
        // Выбираем товары
        foreach ($items as $k => $item) {
            $items[$k]['product_id'] = self::getProductId($item);
        }

        // Получаем все характеристики и присваиваем товарам их характеристики
        $features = shopDelpayfilterData::getFeatures($items);
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

    private static function filter_by_product_stock($items, $params)
    {
        $params['field'] = $params['field'] == '' ? 'all' : $params['field'];
        $params['value'] = $params['value'] == '' ? 2147483647 : $params['value'];
        $params['sum_type'] = isset($params['sum_type']) ? $params['sum_type'] : 'not_sum';
        $skus_count = shopDelpayfilterData::getSkuStocksCount($items);
        $product_skus_count = $product_items = array();
        foreach ($items as $k => $item) {
            $id = self::getProductId($item, $params['sum_type'] == 'not_sum' ? 'sku' : 'product');
            // Если необходимо просуммировать артикулы
            if ($params['sum_type'] == 'sum') {
                if (!isset($product_skus_count[$id])) {
                    $product_skus_count[$id] = array('count' => 0);
                }
                $stocks = shopDelpayfilterHelper::getStocks();
                if ($stocks) {
                    // Суммируем значение остатков на складах
                    foreach ($stocks as $st_id => $st) {
                        if (isset($skus_count[$item['sku_id']][$st_id])) {
                            if (!isset($product_skus_count[$id][$st_id])) {
                                $product_skus_count[$id][$st_id] = 0;
                            }
                            $product_skus_count[$id][$st_id] += $skus_count[$item['sku_id']][$st_id];
                        } else {
                            $product_skus_count[$id][$st_id] = 2147483647;
                        }
                    }
                }

                $product_skus_count[$id]['count'] = ($item['count'] === 2147483647 ? 2147483647 : ($item['count'] + $product_skus_count[$id]['count']));
                $product_items[$id][] = $k;
            } // Если каждый артикул рассчитывается отдельно
            elseif (!self::checkStockAvailability($item['count'], $params, isset($skus_count[$id]) ? $skus_count[$id] : array())) {
                unset($items[$k]);
            }
        }
        // Расчет для случая, когда необходимо просуммировать артикулы
        if ($product_skus_count) {
            foreach ($product_skus_count as $product_id => $p) {
                if (!self::checkStockAvailability($product_skus_count[$product_id]['count'], $params, $product_skus_count[$product_id])) {
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
            $stocks = shopDelpayfilterHelper::getStocks();
            if (($params['field'] == 'all' || $params['field'] == 'any' || $params['field'] == 'each') && $stocks) {
                $stop = 1;
                foreach ($stocks as $st_id => $st) {
                    // Определяем, с какими складами следует работать: виртуальными, реальными или обоими
                    if (($params['stock_type'] == 'real' && !is_numeric($st_id)) || ($params['stock_type'] == 'virt' && is_numeric($st_id))) {
                        continue;
                    }
                    // Если записи для склада не существует, значит на складе бесконечность
                    $stock_count = isset($skus_count[$st_id]) ? $skus_count[$st_id] : 2147483647;
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

        if (!self::execute_operator($params['op'], $item_count, $params['value'])) {
            return false;
        }
        return true;
    }

    /*     * **
     * Вычислительные условия. Возвращают булево значение
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
        return $items ? $items : self::getAbstractProduct();
    }

    private static function filter_by_num_prod($items, $params)
    {
        foreach ($items as $k => $item) {
            $product_id = self::getProductId($item);
            if ($params['field'] && $product_id !== (int) $params['field']) {
                unset($items[$k]);
                continue;
            }
            if (!self::execute_operator($params['op'], $item['quantity'], $params['value'])) {
                unset($items[$k]);
            }
        }
        return $items;
    }

    private static function filter_by_num_cat($items, $params)
    {
        if (!empty($params['ext'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if ($params['field'] && $product_id !== (int) $params['field']) {
                    unset($items[$k]);
                    continue;
                }

                $quantity = 0;
                if (self::execute_operator($params['op'], $product_id, shopDelpayfilterData::getCategoryProducts($params['ext']))) {
                    $quantity += $item['quantity'];
                }

                if (!self::execute_operator($params['op2'], $quantity, $params['value'])) {
                    unset($items[$k]);
                }
            }
            return $items;
        }
        return array();
    }

    private static function filter_by_num_cat_all($items, $params)
    {
        if (!empty($params['ext'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if ($params['field'] && $product_id !== (int) $params['field']) {
                    unset($items[$k]);
                    continue;
                }
                $quantity = 0;
                if (self::execute_operator($params['op'], $product_id, shopDelpayfilterData::getCategoryProducts($params['ext'], true))) {
                    $quantity += $item['quantity'];
                }

                if (!self::execute_operator($params['op2'], $quantity, $params['value'])) {
                    unset($items[$k]);
                }
            }
            return $items;
        }
        return array();
    }

    private static function filter_by_num_all_cat($items, $params)
    {
        if (!empty($params['field'])) {
            $result_items = self::filter_by_cat($items, array('op' => $params['op'], 'value' => $params['field']));
            return self::_filter_by_num($result_items, $params['op2'], $params['value']);
        }
        return array();
    }

    private static function filter_by_num_all_cat_all($items, $params)
    {
        if (!empty($params['field'])) {
            $result_items = self::filter_by_cat_all($items, array('op' => $params['op'], 'value' => $params['field']));
            return self::_filter_by_num($result_items, $params['op2'], $params['value']);
        }
        return array();
    }

    private static function filter_by_num_all_set($items, $params)
    {
        if (!empty($params['field'])) {
            $result_items = self::filter_by_set($items, array('op' => $params['op'], 'value' => $params['field']));
            return self::_filter_by_num($result_items, $params['op2'], $params['value']);
        }
        return array();
    }

    private static function filter_by_num_all_type($items, $params)
    {
        if (!empty($params['field'])) {
            $result_items = self::filter_by_type($items, array('op' => $params['op'], 'value' => $params['field']));
            return self::_filter_by_num($result_items, $params['op2'], $params['value']);
        }
        return array();
    }

    private static function filter_by_num_set($items, $params)
    {
        if (!empty($params['ext'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if ($params['field'] && $product_id !== (int) $params['field']) {
                    unset($items[$k]);
                    continue;
                }
                $quantity = 0;
                if (self::execute_operator($params['op'], $product_id, shopDelpayfilterData::getSetProducts($params['ext']))) {
                    $quantity += $item['quantity'];
                }
                if (!self::execute_operator($params['op2'], $quantity, $params['value'])) {
                    unset($items[$k]);
                }
            }
            return $items;
        }
        return array();
    }

    private static function filter_by_num_type($items, $params)
    {
        if (!empty($params['ext'])) {
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if ($params['field'] && $product_id !== (int) $params['field']) {
                    unset($items[$k]);
                    continue;
                }

                $quantity = 0;
                $type_id = isset($item['type_id']) ? (int) $item['type_id'] : (isset($item['product']['type_id']) ? (int) $item['product']['type_id'] : 0);
                if (self::execute_operator($params['op'], $type_id, $params['ext'])) {
                    $quantity += $item['quantity'];
                }

                if (!self::execute_operator($params['op2'], $quantity, $params['value'])) {
                    unset($items[$k]);
                }
            }
            return $items;
        }
        return array();
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

    private static function filter_by_total($items, $params)
    {
        if (!self::execute_operator($params['op'], self::$total, $params['value'])) {
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

    private static function filter_by_sum_cat($items, $params)
    {
        if (!empty($params['field'])) {
            $total_price = 0;
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if (self::execute_operator('eq', $product_id, shopDelpayfilterData::getCategoryProducts($params['field']))) {
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

    private static function filter_by_sum_cat_all($items, $params)
    {
        if (!empty($params['field'])) {
            $total_price = 0;
            foreach ($items as $k => $item) {
                $product_id = self::getProductId($item);
                if (self::execute_operator('eq', $product_id, shopDelpayfilterData::getCategoryProducts($params['field'], true))) {
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

    private static function filter_by_total_feat($items, $params)
    {
        if (!empty($params['field'])) {
            $feature_id = $params['field'];
            // Суммируем значение характеристики
            $feature = 0;
            // Получаем все характеристики и присваиваем товарам их характеристики
            shopDelpayfilterData::getFeatures($items);
            foreach ($items as $it) {
                $product = $it['product'];
                $item_sku_id = self::getProductId($it, 'sku');
                if (isset($product['selectable_features'][$item_sku_id]) && isset($product['selectable_features'][$item_sku_id][$feature_id])) {
                    $feature_sku_value = $product['selectable_features'][$item_sku_id][$feature_id];
                    if (isset($product['features'][$feature_id][$feature_sku_value])) {
                        // Если у товара существует характеристика, и она является объектом
                        if (is_object($product['features'][$feature_id][$feature_sku_value]) && $product['features'][$feature_id][$feature_sku_value] instanceof shopDimensionValue) {
                            $feature += $product['features'][$feature_id][$feature_sku_value]->value_base_unit * $it['quantity'];
                        } // Обработка других типов характеристик
                        else {
                            $feature += (float) $product['features'][$feature_id][$feature_sku_value] * $it['quantity'];
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
                            $feature += (float) $product['features'][$feature_id] * $it['quantity'];
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

    private static function filter_by_prod_price($items, $params)
    {
        foreach ($items as $k => $item) {
            if (!self::execute_operator($params['op'], $item['primary_price'], $params['value'])) {
                unset($items[$k]);
            }
        }
        return $items;
    }

    private static function filter_by_prod_each_price($items, $params)
    {
        foreach ($items as $k => $item) {
            if (!self::execute_operator($params['op'], $item['primary_price'], $params['value'])) {
                return array();
            }
        }
        return $items;
    }

    /*     * **
     * Уточняющие условия.
     * * */

    private static function filter_by_ucat($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if ($params['value']) {
            try {
                // Проверяем наличие плагина Контакты PRO
                wa('contacts')->getPlugin('pro');
                $contact_categories = wao(new contactsViewModel())->getAllViews(null, true);
                if (isset($contact_categories[$params['value']])) {
                    if (!empty($contact_categories[$params['value']]['category_id'])) {
                        if (!self::execute_operator($params['op'], (int) wao(new waContactCategoriesModel())->inCategory($contact_id, $contact_categories[$params['value']]['category_id']), 1)) {
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
                if (!self::execute_operator($params['op'], (int) wao(new waContactCategoriesModel())->inCategory($contact_id, $params['value']), 1)) {
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

    private static function filter_by_order_status($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        if ($params['value']) {
            $collection = new shopOrdersCollection('search/state_id=' . $params['value'] . '&contact_id=' . $contact_id);
            $has_status = $collection->count() ? 1 : 0;
            if (!self::execute_operator($params['op'], $has_status, 1)) {
                return array();
            }
        }
        return $items;
    }

    private static function _filter_by_user_address($items, $params, $type = 'country')
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        $value = $type == 'country' ? $params['field'] : $params['value'];

        if ($value) {
            $addresses = self::getUserAddress($contact_id);
            if ($type == 'country') {
                $countries = array();
                foreach ($addresses as $addr) {
                    if ($addr['ext'] == 'shipping') {
                        $country = !empty($addr['data']['country']) ? $addr['data']['country'] : wa()->getSetting('country');
                        $countries[$country] = $addr['data']['country'] . (!empty($addr['data']['region']) ? '-' . $addr['data']['region'] : '');
                    }
                }
                if ($countries) {
                    foreach ($countries as $country => $region) {
                        if ((empty($params['value']) && self::execute_operator($params['op'], $country, $value)) || (!empty($params['value']) && self::execute_operator('eq', $country, $value) && self::execute_operator($params['op'], $region, $params['value']))) {
                            return $items;
                        }
                    }
                }
            } else {
                $cities = array();
                foreach ($addresses as $addr) {
                    if ($addr['ext'] == 'shipping' && !empty($addr['data']['city'])) {
                        $cities[] = $addr['data']['city'];
                    }
                }
                if ($cities) {
                    foreach ($cities as $city) {
                        if (self::execute_operator($params['op'], $city, $value)) {
                            return $items;
                        }
                    }
                }
            }
        }
        return array();
    }

    private static function filter_by_user_country($items, $params)
    {
        return self::_filter_by_user_address($items, $params);
    }

    private static function filter_by_user_city($items, $params)
    {
        return self::_filter_by_user_address($items, $params, 'city');
    }

    private static function filter_by_user_data($items, $params)
    {
        $user = wa()->getUser();
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        $order_params = shopDelpayfilterData::getCurrentOrderParams();
        if ($contact_id) {
            $user = self::$user;
        } else {
            if ($contact = $order_params['contact']) {
                $user = $contact;
            }
        }

        if (waRequest::param('plugin', '') == 'quickorder' && $order_params['contact']) {
            $user = $order_params['contact'];
        }
        $instance = self::get_instance();
        if ($params['field'] && $user) {
            $value = $instance->getFirstData($user, $params['field']);
            if (!self::execute_operator($params['op'], $value, $params['value'])) {
                return array();
            }
        }
        return $items;
    }

    private static function filter_by_user_auth($items, $params)
    {
        if (!self::execute_operator($params['op'], wa()->getUser()->getId() ? 1 : 0, 1)) {
            return array();
        }
        return $items;
    }

    private static function filter_by_all_orders($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);
        $customer = wao(new shopCustomerModel())->getById($contact_id);
        if ($customer && self::execute_operator($params['op'], $customer['total_spent'], $params['value'])) {
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
            if ($params['field']) {
                $sql .= " AND paid_date >= '" . shopDelpayfilterHelper::secureString($params['field']) . "'";
            }
            if ($params['ext']) {
                $sql .= " AND paid_date <= '" . shopDelpayfilterHelper::secureString($params['ext']) . "'";
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
        $cm = new shopCustomerModel();
        $customer = $cm->getById($contact_id);
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
            if ($params['field']) {
                $hash .= "&paid_date>=" . shopDelpayfilterHelper::secureString($params['field']);
            }
            if ($params['ext']) {
                $hash .= "&paid_date<=" . shopDelpayfilterHelper::secureString($params['ext']);
            }
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
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id . '&items.product_id=' . (int) $params['value'];

            $collection = new shopOrdersCollection($hash);
            if ($collection->count() > 0) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_order_prod_int($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);

        if ($contact_id) {
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id . ($params['value'] ? '&items.product_id=' . (int) $params['value'] : '');
            if ($params['field']) {
                $hash .= "&paid_date>=" . shopDelpayfilterHelper::secureString($params['field']);
            }
            if ($params['ext']) {
                $hash .= "&paid_date<=" . shopDelpayfilterHelper::secureString($params['ext']);
            }
            $collection = new shopOrdersCollection($hash);
            if ($collection->count() > 0) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_order_prod_cat($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);

        if ($contact_id && $params['value']) {
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id;
            $collection = new shopOrdersCollection($hash);
            $alias = $collection->addJoin('shop_order_items', ':table.order_id = o.id', ($params['field'] ? ':table.product_id = ' . (int) $params['field'] : null));
            $collection->addJoin('shop_category_products', ':table.product_id = ' . $alias . '.product_id', ':table.category_id = ' . (int) $params['value']);
            if ($collection->count() > 0) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_order_prod_cat_all($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);

        if ($contact_id && $params['value']) {
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id;
            $collection = new shopOrdersCollection($hash);
            $alias = $collection->addJoin('shop_order_items', ':table.order_id = o.id', ($params['field'] ? ':table.product_id = ' . (int) $params['field'] : null));
            $categories = shopDelpayfilterData::getCategoriesTree();
            $collection->addJoin('shop_category_products', ':table.product_id = ' . $alias . '.product_id', ':table.category_id IN (' . (!empty($categories[$params['value']]['children']) ? implode(",", $categories[$params['value']]['children']) : $params['value']) . ')');
            if ($collection->count() > 0) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_order_prod_cat_int($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);

        if ($contact_id && $params['value'] && ($params['ext1'] || $params['ext2'])) {
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id;
            if ($params['ext1']) {
                $hash .= "&paid_date>=" . shopDelpayfilterHelper::secureString($params['ext1']);
            }
            if ($params['ext2']) {
                $hash .= "&paid_date<=" . shopDelpayfilterHelper::secureString($params['ext2']);
            }
            $collection = new shopOrdersCollection($hash);
            $alias = $collection->addJoin('shop_order_items', ':table.order_id = o.id', ($params['field'] ? ':table.product_id = ' . (int) $params['field'] : null));
            $collection->addJoin('shop_category_products', ':table.product_id = ' . $alias . '.product_id', ':table.category_id = ' . (int) $params['value']);
            if ($collection->count() > 0) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_order_prod_cat_all_int($items, $params)
    {
        $contact_id = (self::$user ? (self::$user->getId() ? self::$user->getId() : 0) : 0);

        if ($contact_id && $params['value'] && ($params['ext1'] || $params['ext2'])) {
            $hash = 'search/paid_date!=NULL&contact_id=' . $contact_id;
            if ($params['ext1']) {
                $hash .= "&paid_date>=" . shopDelpayfilterHelper::secureString($params['ext1']);
            }
            if ($params['ext2']) {
                $hash .= "&paid_date<=" . shopDelpayfilterHelper::secureString($params['ext2']);
            }
            $collection = new shopOrdersCollection($hash);
            $alias = $collection->addJoin('shop_order_items', ':table.order_id = o.id', ($params['field'] ? ':table.product_id = ' . (int) $params['field'] : null));
            $categories = shopDelpayfilterData::getCategoriesTree();
            $collection->addJoin('shop_category_products', ':table.product_id = ' . $alias . '.product_id', ':table.category_id IN (' . (!empty($categories[$params['value']]['children']) ? implode(",", $categories[$params['value']]['children']) : $params['value']) . ')');
            if ($collection->count() > 0) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_date($items, $params)
    {
        $date = strtotime(date("Y-m-d"));

        if ($params['value']) {
            if (self::execute_operator($params['op'], $date, strtotime($params['value']))) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_week($items, $params)
    {
        $week = date("w");

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

        if ($hour > 23 || $hour <= 0) {
            $hour = 0;
        }
        if ($minute >= 60 || $minute <= 0) {
            $minute = 0;
        }

        $value = mktime($hour, $minute, 0, 12, 32, 1997);

        $now = mktime(date('G'), date('i'), 0, 12, 32, 1997);

        if (self::execute_operator($params['op'], $now, $value)) {
            return $items;
        }
        return array();
    }

    private static function filter_by_cookie($items, $params)
    {
        if (!empty($params['field'])) {
            $param = waRequest::cookie();
            if (isset($param[$params['field']]) && self::execute_operator($params['op'], $param[$params['field']], $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_session($items, $params)
    {
        if (!empty($params['field'])) {
            $param = wa()->getStorage()->getAll();
            if (isset($param[$params['field']]) && self::execute_operator($params['op'], $param[$params['field']], $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_get($items, $params)
    {
        if (!empty($params['field'])) {
            $get = wa()->getStorage()->get('delpayfilter_get');
            $get = $get ? (array) $get : array();
            $param = waRequest::get(null, array());
            if (isset($param[$params['field']])) {
                if (self::execute_operator($params['op'], $param[$params['field']], $params['value'])) {
                    $value = array($params['field'] => $params['value']);
                    $get = !empty($get) ? array_merge($get, $value) : $value;
                    wa()->getStorage()->set('delpayfilter_get', $get);
                    return $items;
                } elseif (isset($get[$params['field']])) {
                    unset($get[$params['field']]);
                    wa()->getStorage()->set('delpayfilter_get', $get);
                }
            } elseif (isset($get[$params['field']]) && self::execute_operator($params['op'], $get[$params['field']], $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_post($items, $params)
    {
        if (!empty($params['field'])) {
            $post = wa()->getStorage()->get('delpayfilter_post');
            $post = $post ? (array) $post : array();
            $param = waRequest::post(null, array());
            if (isset($param[$params['field']])) {
                if (self::execute_operator($params['op'], $param[$params['field']], $params['value'])) {
                    $value = array($params['field'] => $params['value']);
                    $post = !empty($post) ? array_merge($post, $value) : $value;
                    wa()->getStorage()->set('delpayfilter_post', $post);
                    return $items;
                } elseif (isset($post[$params['field']])) {
                    unset($post[$params['field']]);
                    wa()->getStorage()->set('delpayfilter_post', $post);
                }
            } elseif (isset($post[$params['field']]) && self::execute_operator($params['op'], $post[$params['field']], $params['value'])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_not_isset_post($items, $params)
    {
        if (!empty($params['field'])) {
            $post = wa()->getStorage()->get('delpayfilter_post');
            $post = $post ? (array) $post : array();
            $param = waRequest::post(null, array());
            $request = $post + $param;
            if (!isset($request[$params['field']])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_not_isset_get($items, $params)
    {
        if (!empty($params['field'])) {
            $get = wa()->getStorage()->get('delpayfilter_get');
            $get = $get ? (array) $get : array();
            $param = waRequest::get(null, array());
            $request = $get + $param;
            if (!isset($request[$params['field']])) {
                return $items;
            }
        }
        return array();
    }

    private static function filter_by_storefront($items, $params)
    {
        if ($params['value']) {

            $routing_url = wa()->getRouting()->getRootUrl();
            $storefront = shopDelpayfilterHelper::getDomain() . ($routing_url ? '/' . $routing_url : '');

            if (self::execute_operator($params['op'], $storefront, $params['value']) || self::execute_operator($params['op'], $storefront . '*', $params['value']) || self::execute_operator($params['op'], $storefront . '/*', $params['value'])) {
                return $items;
            }
        } elseif ($params['field']) {
            wa("site");
            $domain_model = new siteDomainModel();
            $domains = $domain_model->getAll('name');
            $domain = wa()->getRouting()->getDomain();
            if (isset($domains[$domain]) && $domains[$domain]['id'] == $params['field']) {
                return $items;
            }
        }
        return array();
    }

    private static function _filter_by_delpay($items, $params, $return_bool = false, $type = 'shipping')
    {
        $order_params = self::getCurrentOrderParams();
        $delpay_method = !empty($order_params[$type == 'shipping' ? 'shipping' : 'payment']) ? ($type == 'shipping' ? $order_params['shipping']['id'] : $order_params['payment']) : 0;
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

    private static function filter_by_shipping_price($items, $params, $return_bool = false, $type = 'shipping')
    {
        $order_params = self::getCurrentOrderParams();
        if (!empty($order_params['shipping']['id'])) {
            $shipping_id = $order_params['shipping']['id'];
            $rate_id = !empty($order_params['shipping']['rate_id']) ? $order_params['shipping']['rate_id'] : 0;
            // Стоимость доставки
            $shipping_class = new shopCheckoutShipping();

            $plugin_info = wao(new shopPluginModel())->getById($shipping_id);
            $plugin = shopShipping::getPlugin($plugin_info['plugin'], $shipping_id);
            $total = self::$total;
            $currency = $plugin->allowedCurrency();
            $current_currency = wa()->getConfig()->getCurrency(false);

            // Игнорируем хук frontend_products, чтобы не было рекурсии
            waRequest::setParam('flexdiscount_skip_frontend_products', 1);
            $shipping_items = array();
                $all_items = self::getOrderItems();
                foreach ($all_items as $ai) {
                    if ($ai['type'] == 'product') {
                        $shipping_items[] = $ai['product'];
                    }
                }
            // Учитываем плагин "Купить в 1 клик" (quickorder)
            if (waRequest::param('plugin', '') == 'quickorder') {
                $customer = waRequest::post('customer_' . $shipping_id);
                $shipping_cl = new shopQuickorderPluginWaShipping($order_params['quickorder_cart']);
                $address = ifset($customer['address.shipping'], $shipping_cl->getAddress());
                $rates = $shipping_cl->getSingleShippingRates($shipping_id, $shipping_items, $address, $total, true);
            } else {
                $rates = $plugin->getRates($shipping_items, $shipping_class->getAddress($order_params['contact']), array('total_price' => $total));
            }
            waRequest::setParam('flexdiscount_skip_frontend_products', null);

            if ($rates && !is_string($rates)) {
                if (!$rate_id) {
                    $rate_id = key($rates);
                }
                if (isset($rates[$rate_id])) {
                    $rate = $rates[$rate_id];
                } else {
                    $rate = array('rate' => 0);
                }
                if ($rate['rate']) {
                    if (is_array($rate['rate'])) {
                        $rate['rate'] = max($rate['rate']);
                    }
                    $rate['rate'] = shop_currency($rate['rate'], $currency, wa()->getConfig()->getCurrency(true), false);
                    // rounding
                    if ($rate['rate'] && wa()->getSetting('round_shipping')) {
                        $rate['rate'] = shopRounding::roundCurrency($rate['rate'], $current_currency);
                    }
                }
                if (self::execute_operator($params['op'], $rate['rate'], $params['value'])) {
                    return $return_bool ? true : $items;
                }
            }
        }

        return $return_bool ? false : array();
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
        return $val1 == $val2 ? false : !self::operator_gt($val1, $val2);
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
     *
     * @param array $item
     * @param string $product_type - sku or product
     * @return int
     */
    private static function getProductId($item, $product_type = 'product')
    {
        if ($product_type == 'sku') {
            return isset($item['sku_id']) ? (int) $item['sku_id'] : (isset($item['product']['sku_id']) ? (int) $item['product']['sku_id'] : 0);
        } else {
            return isset($item['product_id']) ? (int) $item['product_id'] : (isset($item['product']['id']) ? (int) $item['product']['id'] : 0);
        }
    }

    /**
     * Get class instance
     *
     * @return shopDelpayfilterConditions|null
     */
    protected static function get_instance()
    {
        static $instance = null;
        if ($instance === null) {
            $class_name = get_class();
            $instance = new $class_name();
        }
        return $instance;
    }

}
