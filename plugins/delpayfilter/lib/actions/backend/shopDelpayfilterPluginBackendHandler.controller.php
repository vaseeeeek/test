<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPluginBackendHandlerController extends waJsonController
{

    public function execute()
    {
        $method_name = waRequest::request("data", "default") . 'Action';

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
     * Change filter status
     */
    private function filterStatusAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $model = new shopDelpayfilterPluginModel();
        $status = waRequest::post("status", -1);
        if ($id && $status >= 0) {
            $model->updateById($id, array("status" => $status));
            $this->response = (int) $status;
        }
    }

    /**
     * Delete filter
     */
    private function deleteFilterAction()
    {
        $ids = waRequest::post("ids");
        if ($ids) {
            $model = new shopDelpayfilterPluginModel();
            $model->delete($ids);
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

        $collection = new shopProductsCollection('category/' . $category_id);
        $this->response['products'] = $collection->getProducts('*', $offset, $products_per_page);
        if ($this->response['products']) {
            // Генерируем ссылки для изображений
            foreach ($this->response['products'] as &$p) {
                if ($p['image_id']) {
                    $p['image'] = shopImage::getUrl(array('id' => $p['image_id'], 'filename' => $p['image_filename'], 'product_id' => $p['id'], 'ext' => $p['ext']), '48x48');
                }
                $p['name_secure'] = shopDelpayfilterHelper::secureString($p['name']);
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

        wa('contacts');

        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $hash = $category_id ? 'view/' . $category_id : '';
        } catch (Exception $ex) {
            $hash = $category_id ? 'category/' . $category_id : '';
        }

        $collection = new contactsCollection($hash);

        $this->response['users'] = $collection->getContacts('*', $offset, $per_page);
        if ($this->response['users']) {
            // Генерируем ссылки для изображений
            foreach ($this->response['users'] as &$u) {
                $u['name'] = waContactNameField::formatName($u);
                if (!trim($u['name'])) {
                    $u['name'] = '&lt;' . _wp("No name") . '&gt;';
                }
                $u['name_secure'] = shopDelpayfilterHelper::secureString($u['name']);
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

    /**
     * Copy filter
     */
    private function copyFilterAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        if ($id) {
            $model = new shopDelpayfilterPluginModel();
            $clone_id = $model->duplicate($id);
            if ($clone_id) {

                $plugins = array();
                $data_class = new shopDelpayfilterData();
                // Плагины доставки
                $plugins['shipping'] = $data_class->getShippingData();
                // Плагины оплаты
                $plugins['payment'] = $data_class->getPaymentData();

                $filter = $model->getFilter($clone_id);

                if ($filter['target']) {
                    $filter['payment'] = $filter['shipping'] = array();
                    $targets = shopDelpayfilterConditions::decode($filter['target']);
                    foreach ($targets as $t) {
                        if (!empty($t['condition']['value']) && !empty($plugins[$t['target']][$t['condition']['value']])) {
                            $filter[$t['target']][$t['condition']['value']] = shopDelpayfilterHelper::secureString($plugins[$t['target']][$t['condition']['value']]['name']);
                        }
                    }
                }

                $html = "";
                $html .= '<tr class="filter-row" data-id="' . $filter['id'] . '">
                    <td class="filter-name">
                        <input type="checkbox" value="' . $filter['id'] . '" class="f-checker" />
                        <a href="#/filter/copy/' . $filter['id'] . '" class="js-action" title="' . _wp('Copy filter') . '"><i class="icon16 ss orders-all"></i></a>
                        <a href="#/filter/status/' . $filter['id'] . '" class="js-action" title="' . _wp('Change status') . '"><i class="icon16-custom lightbulb-off"></i></a>
                        <a href="#/delpayfilter/filter/' . $filter['id'] . '" title="' . _wp('Open filter') . '">' . (!empty($filter['description']) ? shopDelpayfilterHelper::secureString($filter['description']) . (!empty($filter['name']) ? ', ' : '') : '') . (!empty($filter['name']) ? shopDelpayfilterHelper::secureString($filter['name']) : '') . (empty($filter['description']) && empty($filter['name']) ? _wp('No name filter') : '') . '</a>
                    </td>
                    <td class="filter-target s-shipping">';
                if (!empty($filter['shipping'])) {
                    foreach ($filter['shipping'] as $s) {
                        $html .= '<div>' . $s . '</div>';
                    }
                }
                $html .= '                        
                    </td>
                    <td class="filter-target s-payment">';
                if (!empty($filter['payment'])) {
                    foreach ($filter['payment'] as $p) {
                        $html .= '<div>' . $p . '</div>';
                    }
                }
                $html .= '
                    </td>
                    <td class="filter-icon"><a href="#/filter/delete/' . $filter['id'] . '" class="js-action" title="' . _wp('Delete') . '"><i class="icon16 delete"></i></a></td>
                </tr>';

                $this->response = $html;
            }
        }
    }

    /*     * * 
     * Get options for conditions 
     * ** */

    private function getCategoryJsonAction()
    {
        // Категории товаров
        $scm = new shopCategoryModel();
        $categories = $scm->getTree(null);
        $categories = shopDelpayfilterHelper::getCategoriesTree($categories);
        $this->response = shopDelpayfilterHelper::getCategoriesTreeOptionsHtml($categories);
    }

    private function getSetJsonAction()
    {
        // Списки товаров
        $ssm = new shopSetModel();
        $sets = $ssm->getByField('type', shopSetModel::TYPE_STATIC, 'id');
        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($sets);
    }

    private function getTypeJsonAction()
    {
        // Типы товаров
        $stm = new shopTypeModel();
        $types = $stm->getTypes(true);
        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($types);
    }

    private function getServicesJsonAction()
    {
        // Услуги
        $data_class = new shopDelpayfilterData();
        $services = $data_class->getServicesData();

        $this->response = shopDelpayfilterHelper::getServicesHtml($services);
    }

    private function getOrderStatusJsonAction()
    {
        // Статусы заказа
        $data_class = new shopDelpayfilterData();
        $order_status = $data_class->getOrderStatusData();

        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($order_status);
    }

    private function getStocksJsonAction()
    {
        // Склады
        $data_class = new shopDelpayfilterData();
        $stocks = $data_class->getStocksData();

        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($stocks);
    }

    private function getServicesVariantsJsonAction()
    {
        // Варианты услуг
        $data_class = new shopDelpayfilterData();
        $services = $data_class->getServicesData();

        $this->response = shopDelpayfilterHelper::getServicesVariantsHtml($services);
    }

    private function getFeatureJsonAction()
    {
        // Характеристики товаров
        $sfm = new shopFeatureModel();
        $features = $sfm->getFeatures(true);
        $this->response = shopDelpayfilterHelper::getFeaturesHtml($features);
    }

    private function getFeatureValuesJsonAction()
    {
        // Значения характеристик товаров
        $sfm = new shopFeatureModel();
        $features = $sfm->getFeatures(true, null, 'id', true);
        $this->response = shopDelpayfilterHelper::getFeaturesValuesHtml($features);
    }

    private function getUserCategoryJsonAction()
    {
        // Категории контакта
        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $view_model = new contactsViewModel();
            $contact_categories = $view_model->getAllViews(null, true);
            contactsViewModel::setIcons($contact_categories);
        } catch (Exception $ex) {
            $ccm = new waContactCategoryModel();
            $contact_categories = $ccm->getAll('id');
        }
        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($contact_categories);
    }

    private function getUserDataJsonAction()
    {
        // Данные пользователя
        $data_class = new shopDelpayfilterData();
        $user_data = $data_class->getUserData();

        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($user_data);
    }

    private function getShippingJsonAction()
    {
        // Плагины доставки
        $data_class = new shopDelpayfilterData();
        $shipping = $data_class->getShippingData();

        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($shipping);
    }

    private function getPaymentJsonAction()
    {
        // Плагины оплаты
        $data_class = new shopDelpayfilterData();
        $payment = $data_class->getPaymentData();

        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($payment);
    }

    private function getCountryJsonAction()
    {
        // Страны
        $data_class = new shopDelpayfilterData();
        $countries = $data_class->getCountryData();

        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($countries['fields']);
    }

    private function getRegionJsonAction()
    {
        // Значения регионов
        $dynamic_id = waRequest::post("dynamic_id");
        if ($dynamic_id) {
            $data_class = new shopDelpayfilterData();
            $regions = $data_class->getRegionsData($dynamic_id);
            if ($regions) {
                $regions = reset($regions);
                $this->response = "<option value=''></option>" . shopDelpayfilterHelper::getDynamicValuesHtml($regions, $dynamic_id);
            }
        }
    }

    private function getStorefrontDomainsJsonAction()
    {
        // Домены и их правила маршрутизации
        wa('site');
        $domain_model = new siteDomainModel();
        $domains = $domain_model->getAll('id');
        foreach ($domains as &$dom) {
            $dom['name'] = $dom['title'] ? $dom['title'] : $dom['name'];
        }
        $this->response = shopDelpayfilterHelper::getSelectOptionsHtml($domains);
    }

    private function getStorefrontRoutesJsonAction()
    {
        wa('site');
        $domain_model = new siteDomainModel();
        $domains = $domain_model->getAll('id');
        foreach ($domains as $domain) {
            $routes[$domain['id']] = shopDelpayfilterHelper::getRoutes($domain['name']);
        }
        $this->response = shopDelpayfilterHelper::getStorefrontRoutesHtml($routes);
    }

}
