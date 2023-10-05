<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginModel extends waModel
{

    protected $table = 'shop_flexdiscount';

    /**
     * Get all discounts with coupons
     *
     * @param array $filter
     * @return array
     */
    public function getDiscounts($filter = array())
    {
        $sql = "SELECT f.*,";
        $sql .= " IF(f.frontend_sort='-1', f.sort, f.frontend_sort) as frontend_sort FROM {$this->table} f";
        $sql .= " WHERE 1";
        if (isset($filter['status'])) {
            $sql .= " AND status = '" . (int) $filter['status'] . "'";
        }
        if (isset($filter['deny'])) {
            $sql .= " AND deny = '" . (int) $filter['deny'] . "'";
        }
        if (isset($filter['ids'])) {
            $sql .= " AND id ";
            if (is_array($filter['ids'])) {
                $sql .= "IN ('" . implode("','", $this->escape($filter['ids'], 'int')) . "')";
            } else {
                $sql .= "= '" . (int) $filter['ids'] . "'";
            }
        }
        $sql .= " ORDER BY sort ASC";
        // Выбираем определенное количество записей
        if (!empty($filter['limit'])) {
            $sql .= " LIMIT " . (int) $filter['limit']['offset'] . "," . (int) $filter['limit']['length'];
        }
        $result = $this->query($sql)->fetchAll('id');

        // Получаем параметры скидок
        $params_model = new shopFlexdiscountParamsPluginModel();
        $ids = $result ? array_keys($result) : array();
        $params = $params_model->getParams($ids);

        // Купоны
        if (isset($filter['coupons'])) {
            $sfcdm = new shopFlexdiscountCouponDiscountPluginModel();
            foreach ($sfcdm->query("SELECT COUNT(*) as count, fl_id FROM {$sfcdm->getTableName()} WHERE fl_id IN ('" . implode("','", $ids) . "') GROUP BY fl_id") as $r) {
                $result[$r['fl_id']]['coupons'] = $r['count'];
            }
        }

        // Группируем скидки
        $group_model = new shopFlexdiscountGroupPluginModel();
        $groups = $group_model->getGroups();
        $return = $groups;
        if ($groups) {
            foreach ($groups as $group_id => $gr) {
                if (!empty($gr['items'])) {
                    $return[$group_id]['items'] = array();
                    foreach ($gr['items'] as $gi) {
                        if (isset($result[$gi])) {
                            $key = (!empty($filter['id_as_key']) ? $result[$gi]['id'] : $result[$gi]['sort']);
                            $return[$group_id]['items'][$key] = $result[$gi];
                            if (isset($params[$gi])) {
                                $return[$group_id]['items'][$key] += $params[$gi];
                            }
                            unset($result[$gi]);
                        }
                    }
                    $return[$group_id]['items'] = shopFlexdiscountHelper::sortRules($return[$group_id]['items']);
                }
            }
        }
        if ($result) {
            $return[0] = array();
            foreach ($result as $r) {
                $key = (!empty($filter['id_as_key']) ? $r['id'] : $r['sort']);

                while (isset($return[0][$key])) {
                    $key = uniqid();
                }

                $return[0][$key] = $r;
                if (isset($params[$r['id']])) {
                    $return[0][$key] += $params[$r['id']];
                }
            }
        }

        return $return;
    }

    /**
     * Get discount info
     *
     * @param int $id
     * @return array
     */
    public function getDiscount($id)
    {
        $discount = array();
        if ($id) {
            $sql = "SELECT *, IF(frontend_sort='-1', sort, frontend_sort) as frontend_sort FROM {$this->table} WHERE id = '" . (int) $id . "'";
            $discount = $this->query($sql)->fetchAssoc();
            if ($discount) {
                // Получаем параметры скидок
                $params_model = new shopFlexdiscountParamsPluginModel();
                $discount = array_merge($discount, $params_model->getParams($id));
                // Получаем информацию о количестве купонов и генераторов
                $coupon_model = new shopFlexdiscountCouponPluginModel();
                $clean_coupon = !empty($discount['clean_coupon']) ? 1 : 0;
                $discount['coupons'] = $coupon_model->getCoupons($id, $clean_coupon);

                // Принадлежность к группе
                $sfgd = new shopFlexdiscountGroupDiscountPluginModel();
                $group_id = $sfgd->select("group_id")->where("fl_id = '" . (int) $id . "'")->fetchField();
                $discount['group_id'] = $group_id ? $group_id : 0;
            }
        }

        return $discount;
    }

    /**
     * Duplicate discount
     *
     * @param int $id
     * @return int
     */
    public function duplicate($id)
    {
        $discount = $this->getById($id);
        if ($discount) {
            $sort = $discount['sort'];

            // Основные данные
            unset($discount['id']);
            $discount['status'] = 0;
            $discount['sort']++;
            $clone_id = $this->insert($discount);
            // Изменяем сортировку правил
            $this->exec("UPDATE {$this->table} SET sort = sort + 1 WHERE sort > '" . (int) $sort . "' AND id <> '" . (int) $clone_id . "'");

            // Купоны
            $sfcd = new shopFlexdiscountCouponDiscountPluginModel();
            $sfcd->exec("INSERT INTO {$sfcd->getTableName()} (`coupon_id`, `fl_id`)
                         SELECT `coupon_id`, \"{$clone_id}\" FROM {$sfcd->getTableName()} WHERE fl_id=\"{$id}\"");

            // Принадлежность к группе
            $sfgd = new shopFlexdiscountGroupDiscountPluginModel();
            $group_id = $sfgd->select("group_id")->where("fl_id = '" . (int) $id . "'")->fetchField();
            if ($group_id) {
                $sfgd->add($group_id, $clone_id);
            }

            // Параметры
            $sfpm = new shopFlexdiscountParamsPluginModel();
            $sfpm->exec("INSERT INTO {$sfpm->getTableName()} (`fl_id`, `field`, `ext`, `value`)
                         SELECT \"{$clone_id}\", `field`, `ext`, `value` FROM {$sfpm->getTableName()} WHERE fl_id=\"{$id}\"");

            return $clone_id;
        }
        return 0;
    }

    /**
     * Maximum sort number
     *
     * @return int
     */
    public function getMaxSort()
    {
        return (int) $this->select("MAX(sort)")->fetchField();
    }

    /**
     * Delete discount rules
     *
     * @param array|int $ids
     * @return boolean
     */
    public function delete($ids)
    {
        $where = "";
        if (is_array($ids)) {
            $where .= " IN ('" . implode("','", $this->escape($ids, 'int')) . "')";
        } else {
            $where .= " = '" . (int) $ids . "'";
        }

        $sfgd = new shopFlexdiscountGroupDiscountPluginModel();
        $sfp = new shopFlexdiscountParamsPluginModel();
        $sfcd = new shopFlexdiscountCouponDiscountPluginModel();
        $sql = "DELETE d, sfgd, sfp, sfcd FROM {$this->table} d "
            . "LEFT JOIN {$sfgd->getTableName()} sfgd ON sfgd.fl_id = d.id "
            . "LEFT JOIN {$sfp->getTableName()} sfp ON sfp.fl_id = d.id "
            . "LEFT JOIN {$sfcd->getTableName()} sfcd ON sfcd.fl_id = d.id "
            . "WHERE d.id $where";
        return $this->exec($sql);
    }

    /**
     * Multiple insert ignore
     *
     * @param array $data
     * @return waDbResultInsert|bool
     */
    public function multipleIgnoreInsert($data)
    {
        if (!$data) {
            return true;
        }
        $values = array();
        $fields = array();
        if (isset($data[0])) {
            foreach ($data as $row) {
                $row_values = array();
                foreach ($row as $field => $value) {
                    if (isset($this->fields[$field])) {
                        $row_values[$this->escapeField($field)] = $this->getFieldValue($field, $value);
                    }
                }
                if (!$fields) {
                    $fields = array_keys($row_values);
                }
                $values[] = implode(',', $row_values);
            }
        } else {
            $multi_field = false;
            $row_values = array();
            foreach ($data as $field => $value) {
                if (isset($this->fields[$field])) {
                    if (is_array($value) && !$multi_field) {
                        $multi_field = $field;
                        $row_values[$this->escapeField($field)] = '';
                    } else {
                        $row_values[$this->escapeField($field)] = $this->getFieldValue($field, $value);
                    }
                }
            }
            $fields = array_keys($row_values);
            if ($multi_field) {
                foreach ($data[$multi_field] as $v) {
                    $row_values[$this->escapeField($multi_field)] = $this->getFieldValue($multi_field, $v);
                    $values[] = implode(',', $row_values);
                }
            } else {
                $values[] = implode(',', $row_values);
            }
        }
        if ($values) {
            $sql = "INSERT IGNORE INTO " . $this->table . " (" . implode(',', $fields) . ") VALUES (" . implode('), (', $values) . ")";
            return $this->query($sql);
        }
        return true;
    }

}
