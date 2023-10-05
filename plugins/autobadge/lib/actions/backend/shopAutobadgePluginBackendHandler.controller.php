<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginBackendHandlerController extends waJsonController
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

    /*
     * Change rule sort
     */

    private function ruleSortAction()
    {
        $id = waRequest::post('id');
        $before_id = (int) waRequest::post('before_id');
        $after_id = (int) waRequest::post('after_id');
        if ($id && ($before_id || $after_id)) {
            $model = new shopAutobadgePluginModel();
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
     * Save dimensions of live preview container
     */
    private function saveLivePreviewAction()
    {
        $id = (int) waRequest::post("id");
        $name = waRequest::post("name", "");
        $value = waRequest::post("value", "");
        if ($id && $value && in_array($name, array('width', 'height'))) {
            $model = new shopAutobadgeParamsPluginModel();
            $model->add($id, array('preview_' . $name => $value));
            $this->response = $value;
        }
    }

    /**
     * Save badge template
     */
    private function saveTemplateAction()
    {
        $name = waRequest::post("name");
        $name = $name ? $name : _wp("No name template");
        $settings = waRequest::post("settings");
        $template_id = waRequest::post("template_id", 0, waRequest::TYPE_INT);

        $sat = new shopAutobadgeTemplatePluginModel();

        if (waRequest::post("type") == 'edit' && $template_id) {
            $sat->updateById($template_id, array("name" => $name, "settings" => serialize($settings)));
        } else {
            $template_id = $sat->insert(array("name" => $name, "settings" => serialize($settings)));
        }
        $this->response = array('id' => $template_id, 'settings' => $settings, 'name' => shopAutobadgeHelper::secureString($name));
    }

    /**
     * Remove badge template
     */
    private function removeTemplateAction()
    {
        $template_id = waRequest::post("template_id", 0, waRequest::TYPE_INT);

        $sat = new shopAutobadgeTemplatePluginModel();
        $sat->deleteById($template_id);
    }

    /**
     * Export badge settings
     */
    private function exportTemplateAction()
    {
        $name = waRequest::post("name");
        $name = $name ? $name : _wp("No name template");
        $settings = waRequest::post("settings");

        $encoding = 'utf-8';
        // Заголовки
        $map = array('autobadge_name' => _wp("Template name"), 'autobadge_settings' => _w("Settings"));
        // Название файла
        $filename = sprintf('autobadge_single_export_%s_%s.csv', date('Y-m-d-H-i-s'), strtolower($encoding));
        $file = wa('shop')->getTempPath('autobadge/csv/export/' . $filename);
        $csv = new shopAutobadgeCsv($file, ';', $encoding);
        // Устанавливаем заголовки
        $csv->setMap($map);

        $record = array('autobadge_name' => $name, 'autobadge_settings' => serialize($settings));
        $csv->write($record);

        $this->response = _wp('Download:') . ' <a href="?plugin=autobadge&action=handler&data=exportDownload&file=' . basename($file) . '"><i class="icon16 ss excel"></i> ' . basename($file) . '</a></div>';
    }

    /**
     * Download export file
     */
    private function exportDownloadAction()
    {
        $filename = waRequest::get("file");

        $file = $filename == 'import_example.csv' ? wa('shop')->getAppPath('plugins/autobadge/templates/import_example' . (wa('shop')->getLocale() == 'en_US' ? '_en' : '') . '.csv', 'shop') : wa('shop')->getTempPath('autobadge/csv/export/' . $filename);
        if (file_exists($file)) {
            waFiles::readFile($file, $filename);
        }
    }

    /**
     * Change filter status
     */
    private function filterStatusAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $model = new shopAutobadgePluginModel();
        $status = waRequest::post("status", -1);
        if ($id && $status >= 0) {
            $model->updateById($id, array("status" => $status));
            $this->response = (int) $status;
        }
    }

    /**
     * Copy filter
     */
    private function copyFilterAction()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        if ($id) {
            $model = new shopAutobadgePluginModel();
            $clone_id = $model->duplicate($id);
            if ($clone_id) {
                $filter = $model->getFilter($clone_id);

                $html = "";
                $html .= '<tr class="filter-row" data-id="' . $filter['id'] . '">
                    <td class="filter-name">
                        <input type="checkbox" value="' . $filter['id'] . '" class="f-checker" />
                        <i class="icon16 sort" style="cursor: pointer"></i>
                        <a href="#/filter/copy/' . $filter['id'] . '" class="js-action" title="' . _wp('Copy filter') . '"><i class="icon16 ss orders-all"></i></a>
                        <a href="#/filter/status/' . $filter['id'] . '" class="js-action" title="' . _wp('Change status') . '"><i class="icon16-custom lightbulb-off"></i></a>
                        <a href="#/autobadge/filter/' . $filter['id'] . '" title="' . _wp('Open filter') . '">' . (!empty($filter['name']) ? shopAutobadgeHelper::secureString($filter['name']) : _wp('No name rule')) . '</a>
                    </td>
                    <td class="filter-icon"><a href="#/filter/delete/' . $filter['id'] . '" class="js-action" title="' . _wp('Delete') . '"><i class="icon16 delete"></i></a></td>
                </tr>';

                $this->response = $html;
            }
        }
    }

    /**
     * Delete filter
     */
    private function deleteFilterAction()
    {
        $ids = waRequest::post("ids");
        if ($ids) {
            (new shopAutobadgePluginModel())->delete($ids);
        }
    }

    /**
     * Save settings
     */
    private function settingsSaveAction()
    {
        $post = waRequest::post();

        $appId = 'shop.autobadge';
        $app_settings = new waAppSettingsModel();
        $app_settings->set($appId, 'parent_relative', !empty($post['parent_relative']) ? 1 : 0);
        $app_settings->set($appId, 'parent_visible', !empty($post['parent_visible']) ? 1 : 0);
        $app_settings->set($appId, 'delay_loading', !empty($post['delay_loading']) ? 1 : 0);
        $app_settings->set($appId, 'delay_loading_ajax', !empty($post['delay_loading_ajax']) ? 1 : 0);
        $app_settings->set($appId, 'z_index', !empty($post['z_index']) ? (int) $post['z_index'] : 0);
        $app_settings->set($appId, 'show_loader', !empty($post['show_loader']) ? 1 : 0);
        $app_settings->set($appId, 'frontend_products', !empty($post['frontend_products']) ? 1 : 0);
        $app_settings->set($appId, 'cart_disable', !empty($post['cart_disable']) ? 1 : 0);
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

        $collection = new shopAutobadgeProductsCollection('category/' . $category_id);
        $this->response['products'] = $collection->getProducts('*', $offset, $products_per_page);
        if ($this->response['products']) {
            $primary_currency = wa('shop')->getConfig()->getCurrency(true);
            foreach ($this->response['products'] as &$p) {
                // Генерируем ссылки для изображений
                if ($p['image_id']) {
                    $p['image'] = shopImage::getUrl(array('id' => $p['image_id'], 'filename' => $p['image_filename'], 'product_id' => $p['id'], 'ext' => $p['ext']), '48x48');
                }
                $p['name_secure'] = shopAutobadgeHelper::secureString($p['name']);
                $p['price'] = shop_currency($p['price'], $primary_currency, $primary_currency);
                if ($p['compare_price']) {
                    $p['compare_price'] = shop_currency($p['compare_price'], $primary_currency, $primary_currency);
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
            $primary_currency = wa('shop')->getConfig()->getCurrency(true);
            foreach ($skus as &$p) {
                // Генерируем ссылки для изображений
                if ($p['image_id']) {
                    $p['image'] = shopImage::getUrl($images[$p['image_id']], '48x48');
                }
                $p['name_secure'] = shopAutobadgeHelper::secureString(shopAutobadgeHelper::secureString($product['name']));
                $p['sku_name'] = $p['name'];
                $p['sku_name_secure'] = $p['sku_name'] ? shopAutobadgeHelper::secureString($p['sku_name']) : ($p['sku'] ? shopAutobadgeHelper::secureString($p['sku']) : _wp('sku ID') . ': #' . $p['id']);
                $p['price'] = shop_currency($p['primary_price'], $primary_currency, $primary_currency);
                if ($p['compare_price']) {
                    $p['compare_price'] = shop_currency($p['compare_price'], $product['currency'], $primary_currency);
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

        wa('contacts');

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
                $u['name_secure'] = shopAutobadgeHelper::secureString($u['name']);
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

    /*     * * 
     * Get options for conditions 
     * ** */

    private function getCategoryJsonAction()
    {
        // Категории товаров
        $categories = (new shopCategoryModel())->getTree(null);
        $categories = shopAutobadgeHelper::getCategoriesTree($categories);
        $this->response = shopAutobadgeHelper::getCategoriesTreeOptionsHtml($categories);
    }

    private function getSetJsonAction()
    {
        // Списки товаров
        $sets = (new shopSetModel())->getAll('id');
        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($sets);
    }

    private function getTypeJsonAction()
    {
        // Типы товаров
        $types = (new shopTypeModel())->getTypes(true);
        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($types);
    }

    private function getServicesJsonAction()
    {
        // Услуги
        $data_class = new shopAutobadgeData();
        $services = $data_class->getServicesData();

        $this->response = shopAutobadgeHelper::getServicesHtml($services);
    }

    private function getServicesVariantsJsonAction()
    {
        // Варианты услуг
        $data_class = new shopAutobadgeData();
        $services = $data_class->getServicesData();

        $this->response = shopAutobadgeHelper::getServicesVariantsHtml($services);
    }

    private function getTagsJsonAction()
    {
        // Теги
        $data_class = new shopAutobadgeData();
        $tags = $data_class->getTagsData();

        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($tags);
    }

    private function getParamsJsonAction()
    {
        // Параметры
        $data_class = new shopAutobadgeData();
        $params = $data_class->getParamsData();

        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($params);
    }

    private function getFeatureJsonAction()
    {
        // Характеристики товаров
        $features = (new shopFeatureModel())->getFeatures(true);
        $this->response = shopAutobadgeHelper::getFeaturesHtml($features);
    }

    private function getFeatureValuesJsonAction()
    {
        // Значения характеристик товаров
        $feature_id = waRequest::post("feature_id", 0, waRequest::TYPE_INT);
        if ($feature_id) {
            $features = (new shopFeatureModel())->getFeatures('id', $feature_id, 'id', true);
            if ($features) {
                $features = reset($features);
                $this->response = shopAutobadgeHelper::getFeaturesValuesHtml($features['values'], $feature_id);
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
        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($contact_categories);
    }

    private function getShippingJsonAction()
    {
        // Плагины доставки
        $data_class = new shopAutobadgeData();
        $shipping = $data_class->getShippingData();

        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($shipping);
    }

    private function getPaymentJsonAction()
    {
        // Плагины оплаты
        $data_class = new shopAutobadgeData();
        $payment = $data_class->getPaymentData();

        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($payment);
    }

    private function getStorefrontDomainsJsonAction()
    {
        // Домены и их правила маршрутизации
        wa('site');
        $domains = (new siteDomainModel())->getAll('id');
        foreach ($domains as &$dom) {
            $dom['name'] = $dom['title'] ? $dom['title'] : $dom['name'];
        }
        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($domains);
    }

    private function getStorefrontRoutesJsonAction()
    {
        wa('site');
        $routes = array();
        $domains = (new siteDomainModel())->getAll('id');
        foreach ($domains as $domain) {
            $routes[$domain['id']] = shopAutobadgeHelper::getRoutes($domain['name']);
        }
        $this->response = shopAutobadgeHelper::getStorefrontRoutesHtml($routes);
    }

    private function getStocksJsonAction()
    {
        // Склады
        $data_class = new shopAutobadgeData();
        $stocks = $data_class->getStocksData();

        $this->response = shopAutobadgeHelper::getSelectOptionsHtml($stocks);
    }

    private function getThemeDesignJsonAction()
    {
        /* Темы дизайна */
        $this->response = shopAutobadgeHelper::getSelectOptionsHtml(wa('shop')->getThemes());
    }

    private function saveSystemSettingsAction()
    {
        $app_model = new waAppSettingsModel();
        $system_settings = shopAutobadgeProfile::SETTINGS;
        $post = waRequest::post();
        $appId = array('shop', 'autobadge');
        foreach ($system_settings as $key) {
            $value = waRequest::post($key, 0);
            $app_model->set($appId, $key, $value);
        }
        $app_model->set($appId, 'ignore_plugins', serialize(!empty($post['ignore_plugins']) ? $post['ignore_plugins'] : array()));
        $app_model->set($appId, 'ignore_methods', !empty($post['ignore_methods']) ? $post['ignore_methods'] : '');
    }
}
