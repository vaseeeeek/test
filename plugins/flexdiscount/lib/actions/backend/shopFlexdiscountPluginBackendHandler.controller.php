<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginBackendHandlerController extends waJsonController
{

    public function execute()
    {
        $method_name = waRequest::request("data", "default") . 'Action';
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();

        if (!$user->isAdmin() && $method_name !== 'orderEditSaveCouponAction') {
            if (($method_name == 'editSettingsAction' && !$user->getRights("shop", "flexdiscount_settings")) || ($method_name !== 'editSettingsAction' && !$user->getRights("shop", "flexdiscount_rules"))) {
                throw new waRightsException();
            }
        }

        if (method_exists($this, $method_name)) {
            $this->$method_name();
        } else {
            $this->errors = 1;
        }
    }

    private function defaultAction()
    {

    }

    /**
     * Change discount status
     */
    private function discountStatusAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $status = waRequest::post("status", -1);
        if ($id && $status >= 0) {
            (new shopFlexdiscountPluginModel())->updateById($id, array("status" => $status));
            $this->response = (int) $status;
        }
    }

    /**
     * Change discount frontend sort
     */
    private function discountFrontendSortAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $sort = waRequest::post("sort", -1);
        if ($id && $sort >= 0) {
            (new shopFlexdiscountPluginModel())->updateById($id, array("frontend_sort" => (int) $sort));
        }
    }

    /**
     * Get more rules for group
     */
    private function discountShowMoreAction()
    {
        $group_id = waRequest::post("group_id", 0, waRequest::TYPE_INT);
        $per_page = waRequest::post("per_page", 0, waRequest::TYPE_INT);

        $model = new shopFlexdiscountPluginModel();
        $groups = $model->getDiscounts(array("coupons" => 1));
        if (isset($groups[$group_id])) {
            $discounts = isset($groups[$group_id]['items']) ? $groups[$group_id]['items'] : $groups[$group_id];
            if ($discounts && count($discounts) > $per_page) {
                array_splice($discounts, 0, $per_page);
            }
            $html = "";
            foreach ($discounts as $d) {
                $d['group_id'] = $group_id;
                $html .= shopFlexdiscountHelper::buildRuleHTMLCode($d);
            }
            $this->response = $html;
        }
    }

    /**
     * Customize discount columns
     */
    private function customizeColumnsAction()
    {
        $columns = waRequest::post("columns", array());

        $true_columns = array("coupons" => 1, "discount" => 3, "affil" => 3);
        $columns = array_intersect($columns, array_keys($true_columns));

        (new shopFlexdiscountSettingsPluginModel())->save(array('columns' => serialize($columns)));

        // Определяем ширину колонки с названием
        $this->response['weight'] = 0;
        if ($columns) {
            foreach ($columns as $c) {
                $this->response['weight'] += $true_columns[$c];
            }
        }
        $this->response['show'] = $columns;
        $this->response['hide'] = array_diff(array_keys($true_columns), $columns);
    }

    /**
     * Customize coupon columns
     */
    private function customizeCouponColumnsAction()
    {
        $post_columns = waRequest::post("columns", array());
        $columns = array();
        if ($post_columns) {
            foreach ($post_columns as $c) {
                $columns[$c] = 1;
            }
        }
        (new shopFlexdiscountSettingsPluginModel())->save(array('coupon_columns' => serialize($columns)));
    }

    /**
     * Copy discount
     */
    private function copyDiscountAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        if ($id) {
            $model = new shopFlexdiscountPluginModel();
            $clone_id = $model->duplicate($id);
            if ($clone_id) {
                $discount = $model->getDiscount($clone_id);
                $this->response = shopFlexdiscountHelper::buildRuleHTMLCode($discount);
            }
        }
    }

    /**
     * Delete discount
     */
    private function deleteDiscountAction()
    {
        $ids = waRequest::post("ids");
        if ($ids) {
            (new shopFlexdiscountPluginModel())->delete($ids);
        }
    }

    /**
     * Edit discount from catalog
     */
    private function editDiscountAction()
    {
        $id = waRequest::post("id");
        $percentage = shopFlexdiscountApp::getFunction()->floatVal(waRequest::post("percentage", 0));
        $fixed_value = shopFlexdiscountApp::getFunction()->floatVal(waRequest::post("fixed", 0));
        $type = waRequest::post("type");
        $type = $type == 'affiliate' ? 'affiliate' : 'discount';
        $currency = waRequest::post("currency");

        if ($id) {
            $model = new shopFlexdiscountParamsPluginModel();
            if ($percentage) {
                $model->insert(array('fl_id' => $id, 'field' => $type . '_percentage', 'value' => $percentage), 1);
                $this->response['percentage'] = $percentage;
            } else {
                $model->deleteByField(array('fl_id' => $id, 'field' => $type . '_percentage'));
            }
            if ($fixed_value) {
                $model->insert(array('fl_id' => $id, 'field' => $type, 'value' => $fixed_value), 1);
                $this->response['fixed'] = $fixed_value;
            } else {
                $model->deleteByField(array('fl_id' => $id, 'field' => $type));
            }
            if ($type == 'discount') {
                if ($currency) {
                    $model->insert(array('fl_id' => $id, 'field' => 'discount_currency', 'value' => $currency), 1);
                    $this->response['currency'] = $currency;
                } else {
                    $model->deleteByField(array('fl_id' => $id, 'field' => 'discount_currency'));
                }
            }
        }
    }

    /**
     * Get products by category
     */
    private function getProductsAction()
    {
        $products_per_page = 50;
        $category_id = waRequest::post("category", 0, waRequest::TYPE_INT);
        $page = waRequest::post("page", 1, waRequest::TYPE_INT);
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $products_per_page;

        $collection = new shopFlexdiscountProductsCollection('category/' . $category_id);
        $this->response['products'] = $collection->getProducts('*', $offset, $products_per_page);
        if ($this->response['products']) {
            $primary_currency = shopFlexdiscountApp::get('system')['primary_currency'];
            foreach ($this->response['products'] as &$p) {
                // Генерируем ссылки для изображений
                if ($p['image_id']) {
                    $p['image'] = shopImage::getUrl(array('id' => $p['image_id'], 'filename' => $p['image_filename'], 'product_id' => $p['id'], 'ext' => $p['ext']), '48x48');
                }
                $p['name_secure'] = waString::escapeAll($p['name']);
                $p['price'] = shopFlexdiscountApp::getFunction()->shop_currency($p['price'], $primary_currency, $primary_currency);
                if ($p['compare_price']) {
                    $p['compare_price'] = shopFlexdiscountApp::getFunction()->shop_currency($p['compare_price'], $primary_currency, $primary_currency);
                }
                unset($p['skus']);
            }
        }
        $count = $collection->count();
        $max_page = ceil($count / $products_per_page);
        if ($max_page == $page) {
            $this->response['end'] = true;
        } else {
            $this->response['end'] = false;
            $this->response['page'] = $page + 1;
        }
    }

    private function getProductSkusAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $product = new shopProduct($id);
        if ($product) {
            $skus = $product->getSkus();
            $images = $product->getImages();
            $primary_currency = shopFlexdiscountApp::get('system')['primary_currency'];
            foreach ($skus as &$p) {
                // Генерируем ссылки для изображений
                if ($p['image_id']) {
                    $p['image'] = shopImage::getUrl($images[$p['image_id']], '48x48');
                }
                $p['name_secure'] = waString::escapeAll(waString::escapeAll($product['name']));
                $p['sku_name'] = $p['name'];
                $p['sku_name_secure'] = $p['sku_name'] ? waString::escapeAll($p['sku_name']) : ($p['sku'] ? waString::escapeAll($p['sku']) : _wp('sku ID') . ': #' . $p['id']);
                $p['price'] = shopFlexdiscountApp::getFunction()->shop_currency($p['primary_price'], $primary_currency, $primary_currency);
                if ($p['compare_price']) {
                    $p['compare_price'] = shopFlexdiscountApp::getFunction()->shop_currency($p['compare_price'], $product['currency'], $primary_currency);
                }
            }
            $this->response = $skus;
        }
    }

    /**
     * Get users by view
     */
    private function getUsersAction()
    {
        $per_page = 50;
        $category_id = waRequest::post("category", 0, waRequest::TYPE_INT);
        $page = waRequest::post("page", 1, waRequest::TYPE_INT);
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $per_page;

        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $hash = $category_id ? 'view/' . $category_id : '';
        } catch (Exception $ex) {
            $hash = $category_id ? 'category/' . $category_id : '';
        }

        $collection = new waContactsCollection($hash);

        $this->response['users'] = $collection->getContacts('*', $offset, $per_page);
        if ($this->response['users']) {
            // Генерируем ссылки для изображений
            foreach ($this->response['users'] as &$u) {
                $u['name'] = waContactNameField::formatName($u);
                if (!trim($u['name'])) {
                    $u['name'] = '&lt;' . _wp("No name") . '&gt;';
                }
                $u['name_secure'] = waString::escapeAll($u['name']);
                if (isset($u['photo'])) {
                    $c = new waContact($u['id']);
                    $u['photo'] = $c->getPhoto();
                }
                unset($u);
            }
        }
        $count = $collection->count();
        $max_page = ceil($count / $per_page);
        if ($max_page == $page) {
            $this->response['end'] = true;
        } else {
            $this->response['end'] = false;
            $this->response['page'] = $page + 1;
        }
    }

    private function getCountryJsonAction()
    {
        // Страны
        $data_class = new shopFlexdiscountData();
        $countries = $data_class->getCountryData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($countries['fields']);
    }

    private function getRegionJsonAction()
    {
        // Значения регионов
        $dynamic_id = waRequest::post("dynamic_id");
        if ($dynamic_id) {
            $data_class = new shopFlexdiscountData();
            $regions = $data_class->getRegionsData($dynamic_id);
            if ($regions) {
                $regions = reset($regions);
                $this->response = "<option value=''></option>" . shopFlexdiscountHelper::getDynamicValuesHtml($regions, $dynamic_id);
            }
        }
    }

    /**
     * Add coupons to discount
     */
    private function addCouponsAction()
    {
        $ids = waRequest::post("ids");
        $fl_id = (int) waRequest::post("fl_id", 0);
        if ($ids && $fl_id) {
            (new shopFlexdiscountCouponDiscountPluginModel())->multipleInsert(array("coupon_id" => $ids, "fl_id" => $fl_id));
        }
    }

    /**
     * Remove coupons from discount
     */
    private function removeCouponsAction()
    {
        $ids = waRequest::post("ids");
        $fl_id = (int) waRequest::post("fl_id", 0);
        if ($ids && $fl_id) {
            (new shopFlexdiscountCouponDiscountPluginModel())->deleteByField(array("coupon_id" => $ids, "fl_id" => $fl_id));
        }
    }

    /**
     * Delete coupons totally
     */
    private function deleteCouponsAction()
    {
        $ids = waRequest::post("ids");
        if ($ids) {
            (new shopFlexdiscountCouponPluginModel())->delete($ids);
        }
    }

    /*     * * 
     * Get options for conditions 
     * ** */

    private function getCategoryJsonAction()
    {
        // Категории товаров
        $categories = (new shopCategoryModel())->getTree(null);
        $categories = shopFlexdiscountHelper::getCategoriesTree($categories);
        $this->response = shopFlexdiscountHelper::getCategoriesTreeOptionsHtml($categories);
    }

    private function getSetJsonAction()
    {
        // Списки товаров
        $sets = (new shopSetModel())->getByField('type', shopSetModel::TYPE_STATIC, 'id');
        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($sets);
    }

    private function getTypeJsonAction()
    {
        // Типы товаров
        $types = (new shopTypeModel())->getTypes(true);
        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($types);
    }

    private function getServicesJsonAction()
    {
        // Услуги
        $data_class = new shopFlexdiscountData();
        $services = $data_class->getServicesData();

        $this->response = shopFlexdiscountHelper::getServicesHtml($services);
    }

    private function getServicesVariantsJsonAction()
    {
        // Варианты услуг
        $data_class = new shopFlexdiscountData();
        $services = $data_class->getServicesData();

        $this->response = shopFlexdiscountHelper::getServicesVariantsHtml($services);
    }

    private function getStocksJsonAction()
    {
        // Склады
        $data_class = new shopFlexdiscountData();
        $stocks = $data_class->getStocksData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($stocks);
    }

    private function getTagsJsonAction()
    {
        // Теги
        $data_class = new shopFlexdiscountData();
        $tags = $data_class->getTagsData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($tags);
    }

    private function getParamsJsonAction()
    {
        // Параметры
        $data_class = new shopFlexdiscountData();
        $params = $data_class->getParamsData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($params);
    }

    private function getFeatureJsonAction()
    {
        // Характеристики товаров
        $features = (new shopFeatureModel())->getFeatures(true);
        $this->response = shopFlexdiscountHelper::getFeaturesHtml($features);
    }

    private function getFeatureValuesJsonAction()
    {
        // Значения характеристик товаров
        $feature_id = waRequest::post("feature_id", 0, waRequest::TYPE_INT);
        if ($feature_id) {
            $features = (new shopFeatureModel())->getFeatures('id', $feature_id, 'id', true);
            if ($features) {
                $features = reset($features);
                $this->response = shopFlexdiscountHelper::getFeaturesValuesHtml($features['values'], $feature_id);
            }
        }
    }

    private function getUserCategoryJsonAction()
    {
        // Категории контакта
        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $contact_categories = (new contactsViewModel())->getAllViews(null, true);
            contactsViewModel::setIcons($contact_categories);
        } catch (Exception $ex) {
            $contact_categories = (new waContactCategoryModel())->getAll('id');
        }
        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($contact_categories);
    }

    private function getUserDataJsonAction()
    {
        // Данные пользователя
        $data_class = new shopFlexdiscountData();
        $user_data = $data_class->getUserData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($user_data);
    }

    private function getCustomerSourceJsonAction()
    {
        // Данные пользователя
        $data_class = new shopFlexdiscountData();
        $customer_source = $data_class->getCustomerSource();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($customer_source);
    }

    private function getShippingJsonAction()
    {
        // Плагины доставки
        $data_class = new shopFlexdiscountData();
        $shipping = $data_class->getShippingData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($shipping);
    }

    private function getPaymentJsonAction()
    {
        // Плагины оплаты
        $data_class = new shopFlexdiscountData();
        $payment = $data_class->getPaymentData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($payment);
    }

    private function getStorefrontDomainsJsonAction()
    {
        // Домены и их правила маршрутизации
        wa('site');
        $domains = (new siteDomainModel())->getAll('id');
        foreach ($domains as &$dom) {
            $dom['name'] = $dom['title'] ? $dom['title'] : $dom['name'];
        }
        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($domains);
    }

    private function getStorefrontRoutesJsonAction()
    {
        wa('site');
        $routes = array();
        $domains = (new siteDomainModel())->getAll('id');
        $helper = new shopFlexdiscountHelper();
        foreach ($domains as $domain) {
            $routes[$domain['id']] = $helper->getRoutes($domain['name']);
        }
        $this->response = shopFlexdiscountHelper::getStorefrontRoutesHtml($routes);
    }

    private function getOrderStatusJsonAction()
    {
        // Статусы заказа
        $data_class = new shopFlexdiscountData();
        $order_status = $data_class->getOrderStatusData();

        $this->response = shopFlexdiscountHelper::getSelectOptionsHtml($order_status);
    }

    /*
     * Change discount sort
     */

    private function discountSortAction()
    {
        $id = waRequest::post('id');
        $before_id = (int) waRequest::post('before_id');
        $after_id = (int) waRequest::post('after_id');
        if ($id && ($before_id || $after_id)) {
            $model = new shopFlexdiscountPluginModel();
            if ($before_id && !$after_id) {
                $before_sort = $model->query("SELECT sort FROM {$model->getTableName()} WHERE id = $before_id")->fetchField('sort');
                $sort = $model->query("SELECT sort FROM {$model->getTableName()} WHERE sort > $before_sort ORDER BY id LIMIT 1")->fetchField('sort');
                if ($sort) {
                    $model->exec("UPDATE {$model->getTableName()} SET sort = sort + " . (is_array($id) ? count($id) : 1) . " WHERE sort >= $sort");
                }
            } else if ($after_id) {
                $sort = $model->query("SELECT sort FROM {$model->getTableName()} WHERE id = $after_id")->fetchField('sort');
                $model->exec("UPDATE {$model->getTableName()} SET sort = sort + " . (is_array($id) ? count($id) : 1) . " WHERE sort >= $sort");
            }
            if (empty($sort)) {
                $sort = $model->query("SELECT MAX(sort) sort FROM {$model->getTableName()}")->fetchField('sort') + 1;
            }
            if (is_array($id)) {
                foreach ($id as $d_id) {
                    $model->updateById($d_id, array('sort' => $sort++));
                }
            } else {
                $model->updateById($id, array('sort' => $sort++));
            }
        }
    }

    /**
     * Create discount group
     */
    private function createGroupAction()
    {
        $this->response = (new shopFlexdiscountGroupPluginModel())->create();
    }

    /**
     * Add discounts to group
     */
    private function addToGroupAction()
    {
        $fl_id = waRequest::post('fl_id');
        $group_id = (int) waRequest::post('group_id');
        if ($group_id && $fl_id) {
            $group_model = new shopFlexdiscountGroupDiscountPluginModel();
            $group_model->del(null, $fl_id);
            $group_model->add($group_id, $fl_id);
        }
    }

    /**
     * Remove discount from group
     */
    private function removeFromGroupAction()
    {
        $fl_id = waRequest::post('fl_id');
        $group_id = (int) waRequest::post('group_id', 0);
        if ($fl_id) {
            (new shopFlexdiscountGroupDiscountPluginModel())->del($group_id, $fl_id);
        }
    }

    /**
     * Change group name or combine value
     */
    private function changeGroupAction()
    {
        $group_id = (int) waRequest::post('group_id');
        $element = waRequest::post('element');
        if ($group_id) {
            $group_model = new shopFlexdiscountGroupPluginModel();
            if ($element == 'name') {
                $name = waRequest::post('name', _wp("Group name"));
                $name = trim($name) ? $name : _wp("Group name");
                $name = mb_substr($name, 0, 50, "UTF-8");
                $group_model->updateById($group_id, array("name" => $name));
                $this->response = $name;
            } elseif ($element == 'combine') {
                $combine = waRequest::post('combine');
                if (in_array($combine, array('max', 'mpr', 'min', 'sum'))) {
                    $group_model->updateById($group_id, array("combine" => $combine));
                }
            }
        }
    }

    /**
     * Remove group
     */
    private function removeGroupAction()
    {
        $group_id = (int) waRequest::post('group_id');
        if ($group_id) {
            (new shopFlexdiscountGroupPluginModel())->delete($group_id);
        }
    }

    /**
     * Add, change order coupons from backend
     */
    private function orderEditSaveCouponAction()
    {
        $order_id = (int) waRequest::post('order_id');
        $code = waRequest::post('code', '');
        $old_code = waRequest::post('old_code', '');
        if ($order_id) {
            $coupon_model = new shopFlexdiscountCouponPluginModel();
            // Проверяем, существует ли переданный купон
            $coupon = $coupon_model->getByField("code", $code);
            $com = new shopFlexdiscountCouponOrderPluginModel();
            // Если необходимо изменить существующий купон
            if ($old_code) {
                $old_coupon = $coupon_model->getByField("code", $old_code);
                // Если купон необходимо стереть
                if (!$code) {
                    $com->deleteByField(array("order_id" => $order_id, "code" => $old_code));
                } elseif (!$old_coupon || ($old_coupon['code'] !== $coupon['code'])) {
                    $update = array("code" => $code);
                    // Если переданный купон существует, то обнуляем данные старого купона
                    if ($coupon) {
                        $update['discount'] = $update['affiliate'] = 0;
                        $update['datetime'] = date("Y-m-d H:i:s");
                        $update['coupon_id'] = $coupon['id'];
                        $update['reduced'] = 0;
                    }
                    $com->updateByField(array("order_id" => $order_id, "code" => $old_code), $update);
                }
            } elseif ($code) {
                $insert = array(
                    "order_id" => $order_id,
                    "code" => $code,
                    "datetime" => date("Y-m-d H:i:s"),
                    "discount" => 0,
                    "affiliate" => 0,
                    "coupon_id" => $coupon ? $coupon['id'] : 0,
                    "reduced" => 0
                );
                $com->insert($insert);
            }
            $this->response = waString::escapeAll($code);
        }
    }

    /**
     * Edit flexdiscount settings
     */
    private function editSettingsAction()
    {
        $param = waRequest::post('param');
        $ext = waRequest::post('ext', '');
        $value = waRequest::post('value');
        if ($param) {
            (new shopFlexdiscountSettingsPluginModel())->insert(array('field' => $param, 'ext' => $ext, 'value' => $value), 1);
        }
    }

    private function saveSystemSettingsAction()
    {
        $app_model = new waAppSettingsModel();
        $system_settings = shopFlexdiscountProfile::SETTINGS;
        foreach ($system_settings as $key) {
            $value = waRequest::post($key, 0);
            $app_model->set(array('shop', 'flexdiscount'), $key, $value);
        }
    }

    private function pluginStatusAction()
    {
        (new waAppSettingsModel())->set('shop', 'discount_flexdiscount', waRequest::post('enable') ? 1 : null);
    }

}
