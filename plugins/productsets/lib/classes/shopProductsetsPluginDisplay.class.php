<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginDisplay
{
    private $css_generator;

    /**
     * @param array|int|null $product - null means not include product in the set
     * @param array $params
     *     $params = [
     *         'category' => (int|array) check availability for category
     *         'type' => (string) bundle|userbundle|all. Choose, what should we display
     *         'show_userbundle_form' => (null|bool) type of userbundle: default by settings (null), button (false), form (true)
     *         'show_before_after_html' => (bool) display before/after html code and set title
     *         'set_id' => (int) specify set ID
     *         'is_product_hook' => (bool) does it call from product hook
     *         'is_category_hook' => (bool) does it call from category hook
     *     ]
     * @return string
     */
    public function show($product = null, $params = [])
    {
        static $sets_storage = array();

        waSystem::pushActivePlugin('productsets', 'shop');

        $default_params = [
            'category' => [],
            'ondemand' => null,
            'type' => 'all',
            'show_userbundle_form' => null,
            'show_before_after_html' => true,
            'set_id' => 0,
            'is_product_hook' => false,
            'is_category_hook' => false,
        ];
        $params = array_merge($default_params, $params);

        // Проверяем наличие товара
        if ($product !== null) {
            $product = (new shopProductsetsData())->getProductData()->getProduct($product);

            if (!$product) {
                return '';
            }
        }

        $category_id = $params['category'] ? (is_array($params['category']) ? $params['category']['id'] : (int) $params['category']) : 0;
        $params['ondemand'] = !is_string($params['ondemand']) ? null : waLocale::transliterate($params['ondemand']);
        // Формируем ключ для кеша
        $key = ($product ? 'p-' . $product['id'] : '') . ($category_id ? 'c-' . $category_id : '') . (!empty($params['ondemand']) ? 'd-' . $params['ondemand'] : '') . ($params['set_id'] ? 's-' . $params['set_id'] : '');
        if (!$key) {
            return '';
        }

        $this->css_generator = new shopProductsetsPluginGenerator();

        // Получаем доступные наборы
        if (isset($sets_storage[$key])) {
            $sets = $sets_storage[$key];
        } else {
            $sets = $this->getSets($product, $params);
            $sets_storage[$key] = $sets;
        }

        // Удаляем лишние типы наборов
        foreach ($sets as $set_id => $set) {
            if (!empty($params['set_id']) && $params['set_id'] !== $set_id) {
                unset($sets[$set_id]);
            } else {
                if ($params['type'] == 'bundle' && !empty($set['user_bundle'])) {
                    unset($sets[$set_id]['user_bundle']);
                } elseif ($params['type'] == 'userbundle' && !empty($set['bundle'])) {
                    unset($sets[$set_id]['bundle']);
                }
                // Если у комплекта нет никаких наборов, удаляем комплект
                if (empty($set['bundle']) && empty($set['user_bundle'])) {
                    unset($sets[$set_id]);
                }
            }
        }

        $result = $params['is_product_hook'] ? array('cart' => '', 'block' => '') : '';

        if ($sets) {

            $templates = (new shopProductsetsPluginHelper())->getTemplates(true);

            $template_path = $templates['sets']['frontend_path'];

            $view = new waSmarty3View(wa());
            if ($params['category']) {
                $category_id = is_array($params['category']) ? $params['category']['id'] : (int) $params['category'];
                $view->assign('category_id', $category_id);
            }
            $view->assign('version', wa('shop')->getPlugin('productsets')->getVersion());
            $view->assign('plugin_url', wa()->getAppStaticUrl('shop') . "plugins/productsets");
            $view->assign('force_form', $params['show_userbundle_form']);
            $view->assign('show_before_after_html', $params['show_before_after_html']);
            $view->assign('templates', $templates);

            // Если происходит вызов метода из шаблона - выводим все наборы, в ином случае - подразделяем наборы по местам вывода хука
            if (waConfig::get('is_template')) {
                $view->assign('sets_list', $sets);
                $result .= $view->fetch($template_path);
            } else {
                foreach ($sets as $k => $set) {
                    $view->assign('sets_list', array($set));
                    if ($params['is_product_hook'] && !empty($set['settings']['product']['output_place']) && isset($result[$set['settings']['product']['output_place']])) {
                        $result[$set['settings']['product']['output_place']] .= $view->fetch($template_path);
                        if (waRequest::isXMLHttpRequest()) {
                            $result[$set['settings']['product']['output_place']] .= $this->getCss($set);
                            unset($sets[$k]);
                        }
                    } elseif ((!$params['is_product_hook'] && !$params['is_category_hook']) ||
                        ($params['is_category_hook'] && !empty($set['settings']['category']['output_place']) && $set['settings']['category']['output_place'] == 'default')
                    ) {
                        $result .= $view->fetch($template_path);
                        if (waRequest::isXMLHttpRequest()) {
                            $result .= $this->getCss($set);
                            unset($sets[$k]);
                        }
                    } else {
                        unset($sets[$k]);
                    }
                }
            }

            // CSS стили
            if ($sets) {
                $inline_css = '';
                foreach ($sets as $set) {
                    $inline_css .= $this->css_generator->getStyles($set);
                }
                if ($inline_css) {
                    $inline_css = '<style id="productsets-inline-styles">' . $inline_css . '</style>';
                    $inline_css = $this->css_generator->getGoogleFont() . $inline_css;
                    if (waRequest::isXMLHttpRequest() && is_string($result)) {
                        $result = $inline_css . $result;
                    } elseif (!waRequest::isXMLHttpRequest()) {
                        (new waRuntimeCache('productsets_css'))->set($inline_css);
                    }
                }
            }
        }

        waSystem::popActivePlugin();

        return $result;
    }

    private function getSets($product, $params)
    {
        // Получаем все комплекты для текущей витрины
        $set_model = new shopProductsetsPluginModel();
        $sets = $set_model->getSets();

        if ($sets) {
            // Настройки отображения
            $sm = new shopProductsetsSettingsPluginModel();
            $settings = $sm->getSettings(array_keys($sets), null, true);

            $validation = new shopProductsetsPluginValidation();

            $sku_ids = array();
            $data_class = (new shopProductsetsData())->getProductData();
            foreach ($sets as $k => &$set) {
                $set['settings'] = isset($settings[$set['id']]) ? $settings[$set['id']] : array();

                // Проверка доступности комплектов. Вкладка "Отображение"
                if (false === ($validation->isSetAvailableForProduct($set, $product)
                                    && $validation->isSetAvailableForCategory($set, $params['category'])
                                    && $validation->isSetAvailableForDemand($set, $params['ondemand']))
                ) {
                    unset($sets[$k]);
                    continue;
                }

                $validation->removeInactiveBundles($set);

                // Получаем недостающую информацию о комплекте
                $set = $set_model->fillMissingData($set);

                if (!empty($set['bundle'])) {
                    foreach ($set['bundle'] as $j => $bundle) {
                        // Проверка доступности набора
                        if (!$validation->isBundleAvailable($bundle)) {
                            unset($set['bundle'][$j]);
                            continue;
                        }
                        // Собираем ID артикулов
                        $sku_ids += $data_class->getBundleSkuIds($bundle);
                    }
                }

                if (!empty($set['user_bundle'])) {
                    // Проверка доступности набора
                    if ($validation->isBundleAvailable($set['user_bundle'], 'userbundle', $set['id'])) {
                        // Собираем ID артикулов
                        if (!empty($set['user_bundle']['groups'])) {

                            /* Для фронтенда получаем товары из категорий, списков, типов товаров */
                            if (wa()->getEnv() == 'frontend') {
                                foreach ($set['user_bundle']['groups'] as &$group) {
                                    if (!empty($group['types'])) {
                                        $items = (new shopProductsetsData())->getProductData()->getGroupTypesProducts($group['types']);
                                        $group['items'] = !empty($group['items']) ? $group['items'] + $items : $items;
                                    }
                                }
                                unset($group);
                            }

                            foreach ($set['user_bundle']['groups'] as $group) {
                                if (!empty($group['items'])) {
                                    foreach ($group['items'] as $item) {
                                        $sku_ids[$item['sku_id']] = $item['sku_id'];
                                    }
                                }
                            }
                        }
                        if (!empty($set['user_bundle']['required'])) {
                            foreach ($set['user_bundle']['required'] as $item) {
                                $sku_ids[$item['sku_id']] = $item['sku_id'];
                            }
                        }
                    } else {
                        unset($set['user_bundle']);
                    }
                }
                unset($set);
            }

            // Если передан товар, добавляем его в список на обработку
            if ($product) {
                $sku_ids[$product['sku_id']] = $product['sku_id'];
            }
            $data = (new shopProductsetsData())->getProductData($sku_ids);
            if ($product) {
                $data->setActiveProduct($product);
            }
            foreach ($sets as $k => &$set) {
                // Приводим товары к нужному формату
                $set = $data->normalizeProducts($set);
                // Подсчитываем скидки, меняем цены
                $set = $data->calculateDiscounts($set);
                $this->prepare($set);
            }
        }
        return $sets;
    }

    /**
     * Prepare set before displaying
     *
     * @param array $set
     * @return array
     */
    private function prepare(&$set)
    {
        if (!empty($set['user_bundle'])) {
            // Если количество минимальных товаров превышает количество товаров в наборе - скрываем набор
            if (!empty($set['user_bundle']['settings']['min']) && $set['user_bundle']['settings']['min'] > $set['user_bundle']['items_quantity']) {
                unset($set['user_bundle']);
            }

            // Если количество обязательных товаров превышает максимально допустимое, удаляем возможность выбора других товаров
            if (!empty($set['user_bundle']['settings']['max'])) {
                $required_items_count = (isset($set['user_bundle']['active']) ? 1 : 0) + (isset($set['user_bundle']['required']) ? count($set['user_bundle']['required']) : 0);
                if ($required_items_count >= $set['user_bundle']['settings']['max'] && isset($set['user_bundle']['groups'])) {
                    unset($set['user_bundle']['groups']);
                }
            }

            if (isset($set['user_bundle']['settings']['params'])) {
                unset($set['user_bundle']['settings']['params']);
            }
        }
        return $set;
    }

    private function getCss($set)
    {
        $inline_css = $this->css_generator->getStyles($set);
        if ($inline_css) {
            $inline_css = '<style>' . $inline_css . '</style>';
            $inline_css = $this->css_generator->getGoogleFont() . $inline_css;
        }
        return $inline_css;
    }
}