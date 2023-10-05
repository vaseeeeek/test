<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

// Переносим
try {

    $model = new shopFlexdiscountPluginModel();
    $discounts = array();

    $index_exist = $model->query("SHOW INDEX FROM shop_flexdiscount  WHERE Key_name = 'm_v_c_t_c_s_c_d'")->fetch();
    if ($index_exist) {
        // Удаляем старый индекс
        $model->exec("ALTER TABLE shop_flexdiscount DROP INDEX m_v_c_t_c_s_c_d");
    }

    // Если ошибки не произошло, значит обновление не закончено
    $model->exec("SELECT value FROM shop_flexdiscount WHERE 0");

    // Группируем скидки. Данные группы будут иметь правило расчета "Максимум"
    foreach ($model->query("SELECT * FROM {$model->getTableName()}") as $r) {
        $key = $r['mask'] . '-' . $r['category_id'] . '-' . $r['type_id'] . '-' . $r['set_id'];
        if (!isset($discounts[$key])) {
            $discounts[$key] = array();
        }
        $discounts[$key][$r['id']] = $r;
    }

    if ($discounts) {
        $group_model = new shopFlexdiscountGroupPluginModel();
        $group_disc_model = new shopFlexdiscountGroupDiscountPluginModel();
        $params = array();
        foreach ($discounts as $group) {
            foreach ($group as $d) {

                /* Условия и цели */

                $conditions = array();
                $condition = '{"group_op":"and","conditions":[';
                switch ($d['mask']) {
                    // При покупке больше X любых единиц товара устанавливается скидка на все товары
                    case ">num";
                        preg_match("/\d+/", $d['value'], $matches);
                        $value = reset($matches);
                        $conditions[] = '{"op":"gt","value":"' . (int) $value . '","type":"num"}';
                        $target = '[{"target":"all"}]';
                        break;
                    // При покупке больше (или равно) X любых единиц товара устанавливается скидка на все товары при условии, что сумма покупки не менее Z
                    case ">=numX%sumZ";
                        preg_match_all("/\d+/", $d['value'], $matches);
                        if (!empty($matches[0][0])) {
                            $num = (int) $matches[0][0];
                            $min_sum = (float) $matches[0][1];
                            $conditions[] = '{"op":"gte","value":"' . $num . '","type":"num"},{"op":"gt","value":"' . $min_sum . '","type":"sum"}';
                            $target = '[{"target":"all"}]';
                        }
                        break;
                    // При покупке больше (или равно) X любых единиц товара устанавливается скидка на все товары при условии, что цена каждого товара не менее Z
                    case ">=numX#sumZ";
                        preg_match_all("/\d+/", $d['value'], $matches);
                        if (!empty($matches[0][0])) {
                            $num = (int) $matches[0][0];
                            $min_sum = (float) $matches[0][1];
                            $conditions[] = '{"op":"gte","value":"' . $num . '","type":"num"},{"op":"gt","value":"' . $min_sum . '","type":"prod_each_price"}';
                            $target = '[{"target":"all"}]';
                        }
                        break;
                    // При покупке больше X одинаковых товаров устанавливается скидка на все товары из этого списка
                    case ">%num";
                        preg_match("/\d+/", $d['value'], $matches);
                        $value = reset($matches);
                        $conditions[] = '{"field":"","op":"gt","value":"' . (int) $value . '","type":"num_prod"}';
                        $target = '[{"target":"all_true"}]';
                        break;
                    // При покупке больше Х одинаковых товаров устанавливается скидка на последующие товары из списка
                    case ">%num#";
                        preg_match("/\d+/", $d['value'], $matches);
                        $value = reset($matches);
                        $conditions[] = '{"field":"","op":"gt","value":"' . (int) $value . '","type":"num_prod"}';
                        $target = '[{"target":"all_true","details":{"field":"subsiquent","value":"' . (int) $value . '"}}]';
                        break;
                    // При покупке Х любых товаров устанавливается общая скидка
                    case "=num";
                        preg_match("/\d+/", $d['value'], $matches);
                        $value = reset($matches);
                        $conditions[] = '{op":"eq_num","value":"' . (int) $value . '","type":"num"}';
                        $target = '[{"target":"all"}]';
                        break;
                    // При покупке Х одинаковых товаров устанавливается скидка на эти товары
                    case "=%num";
                        preg_match("/\d+/", $d['value'], $matches);
                        $value = reset($matches);
                        $conditions[] = '{"field":"","op":"eq_num","value":"' . (int) $value . '","type":"num_prod"}';
                        $target = '[{"target":"all_true"}]';
                        break;
                    // Скидка  на каждый i-й одинаковый товар
                    case "%num";
                        preg_match("/\d+/", $d['value'], $matches);
                        $value = reset($matches);
                        $conditions[] = '{op":"gt","value":"0","type":"num"}';
                        $target = '[{"target":"all","details":{"field":"every","value":"' . (int) $value . '"}}]';
                        break;
                    // При покупке X одинаковых товаров устанавливается скидка на Y товаров из этого списка
                    case "numX%numY";
                    case "numX#numY";
                        preg_match_all("/\d+/", $d['value'], $matches);
                        if (!empty($matches[0][0])) {
                            $user_count1 = (int) $matches[0][0];
                            $user_count2 = (int) $matches[0][1];
                            if ($user_count1 >= $user_count2) {
                                $conditions[] = '{"op":"gt","value":"0","type":"num"}';
                                $target = '[{"target":"all_true","details":{"field":"multiple","value":["' . $user_count1 . '","' . $user_count2 . '"]}}]';
                            }
                        }
                        break;
                    // При покупке X любых товаров устанавливается скидка на Y товаров самой низкой цены из этого списка
                    case "numX#numY#";
                        preg_match_all("/\d+/", $d['value'], $matches);
                        if (!empty($matches[0][0])) {
                            $user_count1 = (int) $matches[0][0];
                            $user_count2 = (int) $matches[0][1];
                            $conditions[] = '{"op":"gt","value":"' . $user_count1 . '","type":"num"}';
                            $target = '[{"target":"all_true","details":{"field":"cheapest","value":"' . $user_count2 . '"}}]';
                        }
                        break;
                    // При покупке X любых товаров устанавливается скидка на 1шт Y единиц товаров самой низкой цены из этого списка. 
                    // Скидка начинает работать при цене одного из товаров не ниже Z
                    case "numX#numY#sumZ";
                    case "numX#numY#sumZ#";
                        preg_match_all("/\d+/", $d['value'], $matches);

                        if (!empty($matches[0][0])) {
                            $user_count1 = (int) $matches[0][0];
                            $user_count2 = (int) $matches[0][1];
                            $min_price = (float) $matches[0][2];
                            $conditions[] = '{"op":"gt","value":"' . $user_count1 . '","type":"num"},{"group_op":"and","conditions":[{"op":"gt","value":"' . $min_price . '","type":"product_price"}]}';
                            $target = '[{"target":"all","details":{"field":"cheapest","value":"' . $user_count2 . '"}}]';
                        }
                        break;
                    // Запрет на применение скидок
                    case "-";
                        $d['deny'] = 1;
                        $target = '[{"target":"all_true"}]';
                        break;
                    // Скидка все
                    case "=";
                        $target = '[{"target":"all_true"}]';
                        break;
                    // При покупке товаров на сумму, большую чем X, устанавливается скидка на определенную категорию, определенный тип товаров
                    case ">sum";
                        $sum = (float) substr($d['value'], 1);
                        $conditions[] = '{"group_op":"and","conditions":[{"op":"gt","value":"' . $sum . '","type":"sum"}]}';
                        $target = '[{"target":"all_true"}]';
                        break;
                    // При покупке в определенной категории определенный тип товаров на сумму, большую чем X, устанавливается общая скидка на весь заказ
                    case ">%sum";
                        $sum = (float) preg_replace("/\D/", "", $d['value']);
                        $conditions[] = '{"op":"gt","value":"' . $sum . '","type":"sum"}';
                        $target = '[{"target":"all"}]';
                        break;
                    // При покупке в определенной категории определенный тип товаров на сумму, большую чем X, устанавливается скидка только на эту же категорию, этот же тип товара
                    case ">%sum#";
                        $sum = (float) preg_replace("/\D/", "", $d['value']);
                        $conditions[] = '{"op":"gt","value":"' . $sum . '","type":"sum"}';
                        $target = '[{"target":"all_true"}]';
                        break;
                    // Cкидка для конкретной категории или типа товаров по общей сумме всех заказов покупателя
                    case ">customerTotal";
                        $total = preg_replace("/\D/", "", $d['value']);
                        $conditions[] = '{"op":"gt","value":"' . $total . '","type":"all_orders"}';
                        $target = '[{"target":"all_true"}]';
                        break;
                    default:
                        $target = '[{"target":"all"}]';
                        $d['status'] = 0;
                }


                if (!empty($d['set_id'])) {
                    $conditions[] = '{"op":"eq","value":"' . $d['set_id'] . '","type":"set"}';
                }

                if (!empty($d['contact_category_id'])) {
                    try {
                        // Проверяем наличие плагина Контакты PRO
                        wa('contacts')->getPlugin('pro');
                        $view_model = new contactsViewModel();
                        $contact_category_id = $view_model->select("id")->where("type='category' AND category_id = '" . (int) $d['contact_category_id'] . "'")->fetchField();
                        if (!$contact_category_id) {
                            $contact_category_id = (int) $d['contact_category_id'];
                        }
                    } catch (Exception $ex) {
                        $contact_category_id = (int) $d['contact_category_id'];
                    }
                    $conditions[] = '{"op":"eq","value":"' . $contact_category_id . '","type":"ucat"}';
                }

                if (!empty($d['category_id'])) {
                    $conditions[] = '{"op":"eq","value":"' . (int) $d['category_id'] . '","type":"cat_all"}';
                }

                if (!empty($d['type_id'])) {
                    $conditions[] = '{"op":"eq","value":"' . (int) $d['type_id'] . '","type":"type"}';
                }

                if (!empty($d['domain_id'])) {
                    $conditions[] = '{"op":"eq","type":"storefront","field":"' . (int) $d['domain_id'] . '","value":""}';
                }

                if (!empty($d['expire_datetime'])) {
                    $conditions[] = '{"op":"lt","value":"' . date("Y-m-d", strtotime($d['expire_datetime'])) . '","type":"date"}';
                }

                if (empty($d['category_id']) && empty($d['set_id']) && empty($d['type_id']) && empty($conditions)) {
                    $conditions[] = '{"op":"gt","value":"0","type":"num"}';
                }

                if (!empty($conditions)) {
                    $condition .= implode(',', $conditions);
                }
                $condition .= ']}';
                if (!empty($target)) {
                    $d['target'] = $target;
                }
                $d['conditions'] = $condition;

                /* Массив параметров */
                if (!empty($d['discount_percentage'])) {
                    $params[] = array("fl_id" => $d['id'], 'field' => 'discount_percentage', 'ext' => '', 'value' => $d['discount_percentage']);
                }
                if (!empty($d['discount'])) {
                    $params[] = array("fl_id" => $d['id'], 'field' => 'discount', 'ext' => '', 'value' => $d['discount']);
                    $primary_curr = shopFlexdiscountApp::get('system')['wa']->getConfig()->getCurrency(true);
                    $params[] = array("fl_id" => $d['id'], 'field' => 'discount_currency', 'ext' => '', 'value' => $primary_curr);
                }
                if (!empty($d['affiliate'])) {
                    $params[] = array("fl_id" => $d['id'], 'field' => 'affiliate', 'ext' => '', 'value' => $d['affiliate']);
                }
                if (!empty($d['affiliate_percentage'])) {
                    $params[] = array("fl_id" => $d['id'], 'field' => 'affiliate_percentage', 'ext' => '', 'value' => $d['affiliate_percentage']);
                }
                if (!empty($d['discounteachitem'])) {
                    $params[] = array("fl_id" => $d['id'], 'field' => 'discounteachitem', 'ext' => '', 'value' => 1);
                }

                /* Купоны */
                if (!empty($d['coupon_id'])) {
                    $cdm = new shopFlexdiscountCouponDiscountPluginModel();
                    $cdm->insert(array('coupon_id' => $d['coupon_id'], 'fl_id' => $d['id']), 2);
                }

                $model->updateById($d['id'], $d);
                unset($d);
            }
            // Если скидки необходимо добавить в группу
            if (count($group) > 1) {
                // Создаем группу расчета
                $group_id = $group_model->create();
                $group_disc_model->add($group_id, array_keys($group));
            }
        }

        /* Параметры */
        if (!empty($params)) {
            $params_model = new shopFlexdiscountParamsPluginModel();
            $params_model->multipleInsert($params);
        }
    }

    // Очищаем ненужные поля у таблиц
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN mask");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN value");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN discount");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN discount_percentage");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN affiliate");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN affiliate_percentage");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN set_id");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN contact_category_id");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN category_id");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN type_id");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN coupon_id");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN domain_id");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN discounteachitem");
    $model->exec("ALTER TABLE shop_flexdiscount DROP COLUMN expire_datetime");
} catch (waDbException $e) {
    
}