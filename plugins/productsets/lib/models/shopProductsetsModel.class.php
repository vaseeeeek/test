<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsModel extends waModel
{
    protected $item_model;
    protected $saved_ids = array();
    protected $bundle_key = 'bundle_id';

    /**
     * Save data (insert or update)
     *
     * @param array $data
     * @return int
     */
    public function save($data)
    {
        if (!empty($data['id'])) {
            $this->updateById($data['id'], $data);
            $this->saved_ids[$this->getTableName()][$data['id']] = $data['id'];
            return $data['id'];
        } else {
            $id = $this->insert($data);
            $this->saved_ids[$this->getTableName()][$id] = $id;
            return $id;
        }
    }

    /**
     * Delete all unneccesary items
     *
     * @param int $bundle_id
     */
    public function clean($bundle_id)
    {
        // Все имеющиеся записи
        $item_ids = array_keys($this->getByField($this->bundle_key, $bundle_id, 'id'));
        $saved_ids = ifset($this->saved_ids[$this->getTableName()], []);
        // Удаляем ненужные записи
        if ($delete_ids = array_diff($item_ids, $saved_ids)) {
            $this->deleteById($delete_ids);
        }
        $this->saved_ids[$this->getTableName()] = array();
    }

    /**
     * Get set items
     *
     * @param int $set_id
     * @return array
     */
    public function getItemsBySetId($set_id)
    {
        $info = $this->fieldExists('sort_id') ? $this->select('*')->where('productsets_id = ?', $set_id)->order('sort_id ASC')->fetchAll('id') : $this->select('*')->where('productsets_id = ?', $set_id)->fetchAll('id');
        $data = array();
        if ($info) {
            foreach ($info as $v) {
                $data['b' . $v['id']] = $v;
            }
            $items = $this->item_model->select('*')->where('bundle_id IN (?)', array(array_keys($info)))->order('sort_id ASC')->fetchAll('id');
            foreach ($items as $item_id => $item) {
                $key = 'b' . $item['bundle_id'];
                $item_key = 'i';
                if (!isset($data[$key]['items'])) {
                    $data[$key]['items'] = array();
                }

                $settings = json_decode($item['settings'], true);
                if (empty($settings['_id'])) {
                    $settings['_id'] = $item_id;
                }

                // Альтернативный товар
                if (isset($item['parent_id']) && $item['parent_id'] > 0 && isset($data[$key]['items'][$item_key . $item['parent_id']])) {
                    if (!isset($data[$key]['items'][$item_key . $item['parent_id']]['alternative'])) {
                        $data[$key]['items'][$item_key . $item['parent_id']]['alternative'] = array();
                    }
                    $data[$key]['items'][$item_key . $item['parent_id']]['alternative'][$item_key . $item['id']] = $item;
                    $data[$key]['items'][$item_key . $item['parent_id']]['alternative'][$item_key . $item['id']]['settings'] = $settings;
                } else if ($item['product_id'] == 0 && $item['sku_id'] == 0) {
                    $data[$key]['active'] = $item;
                    $data[$key]['active']['_id'] = $item['id'];
                    $data[$key]['active']['settings'] = $settings;
                } else {
                    $data[$key]['items'][$item_key . $item_id] = $item;
                    $data[$key]['items'][$item_key . $item_id]['settings'] = $settings;
                }
            }
            $data = $this->getSettings($data);
        }
        return $data;
    }

    /**
     * Delete by field with join
     *
     * @param int $id
     * @param string $field
     * @param waModel $join_obj
     * @param string $join_on
     * @return bool|resource
     */
    protected function deleteBySpecifiedField($id, $field, $join_obj, $join_on)
    {
        $sql = "DELETE t1, t2 FROM {$this->table} t1
                LEFT JOIN {$join_obj->getTableName()} t2 ON t1.id = t2." . $this->escape($join_on) . "
                WHERE t1." . $this->escape($field) . " = '" . (int) $id . "'";
        return $this->exec($sql);
    }

    /**
     * Get settings
     *
     * @param array $data
     * @return array
     */
    protected function getSettings($data)
    {
        $is_frontend = wa()->getEnv() == 'frontend';
        foreach ($data as &$d) {
            $d['settings'] = json_decode($d['settings'], true);
            /* Для витрины переводим скидки в активную валюту */
            if ($is_frontend) {
                $d = $this->convertDiscounts($d);
            }
            unset($d);
        }
        return $data;
    }

    /**
     * Convert discounts to frontend currency
     *
     * @param array $data
     * @return array
     */
    private function convertDiscounts($data)
    {
        if ($data['settings']['discount_type'] !== 'each') {
            switch ($data['settings']['discount_type']) {
                case 'common':
                    $data['settings']['frontend_discount'] = ($data['settings']['discount'] && $data['settings']['currency'] !== '%') ? shop_currency($data['settings']['discount'], $data['settings']['currency'], null, false) : $data['settings']['discount'];
                    break;
                case 'avail':
                    $data['settings']['chain']['frontend_value'] = array();
                    foreach ($data['settings']['chain']['value'] as $k => $value) {
                        $data['settings']['chain']['frontend_value'][$k] = ($value && $data['settings']['chain']['currency'][$k] !== '%') ? shop_currency($value, $data['settings']['chain']['currency'][$k], null, false) : $value;
                    }
            }
        }
        return $data;
    }
}