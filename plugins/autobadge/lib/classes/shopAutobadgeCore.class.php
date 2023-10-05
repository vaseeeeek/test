<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgeCore extends shopAutobadgeConditions
{

    private static $js_settings = array();

    /**
     * Get badges to product
     *
     * @param array $params
     * @param array $filters
     * @param array $prod
     *
     * @return array
     * @throws waException
     */
    public static function getBadges($params, $filters, $prod)
    {
        static $completed_products = array();

        $sku_id = $prod['sku_id'];
        $quantity = !empty($prod['quantity']) ? $prod['quantity'] : 1;
        $key = $sku_id . '-' . $prod['autobadge-type'] . '-' . $prod['autobadge-page'];

        // Проверяем, был ли обработан товар и была ли присвоена ему наклейка
        if (isset($completed_products[$key][$quantity])) {
            return $completed_products[$key][$quantity];
        }

        $badges = array();

        if ($filters) {

            $default_badge = 0;
            $items = $params['order']['items'];

            self::$user = $params['contact'];
            self::setOrderInfo($params['order']);

            // Выполняем предварительную обработку товаров
            shopAutobadgeHelper::workupProducts($items, $prod);

            self::getAllItems($items);

            // Настройки
            $settings = shopAutobadgeHelper::getSettings();

            $inc_z_index = !empty($settings['z_index']) ? (int) $settings['z_index'] : 0;
            // Выполняем перебор фильтров
            foreach ($filters as $rule) {
                if ($rule['status']) {
                    // Условия 
                    $conditions = self::decode($rule['conditions']);

                    // Товары, удовлетворяющие условиям
                    $result_items = $conditions ? self::filter_items($items, $conditions->group_op, $conditions->conditions) : $items;
                    if ($product = self::productInArray($sku_id, $result_items)) {
                        shopAutobadgeHelper::prepareProduct($product);
                        // Создаем наклейки
                        $target = self::decode($rule['target']);
                        $target_count = count($target) + $inc_z_index;
                        foreach ($target as $k => $t) {
                            // Если используется дефолтная наклейка
                            if (strpos($t->target, 'default-') !== false && !$default_badge) {
                                $badges['default'] = substr($t->target, 8);
                                $default_badge = 1;
                            } elseif (!empty($t->conditions)) {
                                $badge_class = 'ab-' . $rule['id'] . '-' . $k;
                                $badges[] = shopAutobadgeGenerator::createBadge($product, $t->conditions, $rule['params'], $badge_class, $target_count - $k);

                                // Для наклеек №5 сохраняем настройки, чтобы впоследствии использовать их на витрине
                                if ($t->conditions->id == 'ribbon-5' && !isset(self::$js_settings[$badge_class])) {
                                    self::$js_settings[$badge_class] = $t->conditions->settings;
                                }
                            }
                        }
                        $inc_z_index += $target_count;
                    }
                }
            }
        }
        $completed_products[$key][$quantity] = $badges;

        return $badges;
    }

    /**
     * Check, if product ID in array of cart items
     *
     * @param int $sku_id
     * @param array $items
     * @return boolean|array
     */
    private static function productInArray($sku_id, $items)
    {
        if ($items) {
            foreach ($items as $it) {
                if ($it['sku_id'] == $sku_id) {
                    return $it;
                }
            }
        }
        return false;
    }

    /**
     * Get settings for ribbon-5 to use it in Storefront
     *
     * @return array
     */
    public static function getJsSettings()
    {
        return self::$js_settings;
    }

}
