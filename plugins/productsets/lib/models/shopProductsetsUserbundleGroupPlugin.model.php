<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsUserbundleGroupPluginModel extends shopProductsetsModel
{

    protected $table = 'shop_productsets_userbundle_group';

    /**
     * Get full information about user bundle groups
     *
     * @param int $id
     * @return array
     */
    public function getGroupsById($id)
    {
        $info = $this->select('*')->where('userbundle_id = ?', $id)->order('sort_id ASC')->fetchAll('id');
        $data = array();
        if ($info) {
            foreach ($info as $v) {
                $data['g' . $v['id']] = $v;
            }
            $item_model = new shopProductsetsUserbundleGroupItemPluginModel();
            $items = $item_model->select('*')->where('group_id IN (?)', array(array_keys($info)))->order('sort_id ASC')->fetchAll('id');
            foreach ($items as $item_id => $item) {
                $key = 'g' . $item['group_id'];
                $item_key = 'i';
                if (!isset($data[$key]['items'])) {
                    $data[$key]['items'] = array();
                    $data[$key]['types'] = array();
                }
                if ($item['type'] == 'product' || $item['type'] == 'sku') {
                    $data[$key]['items'][$item_key . $item_id] = $item;
                    $data[$key]['items'][$item_key . $item_id]['settings'] = json_decode($item['settings'], true);
                } else {
                    $data[$key]['types'][$item_key . $item_id] = $item;
                    $data[$key]['types'][$item_key . $item_id]['settings'] = json_decode($item['settings'], true);
                }
            }

            $data = $this->getSettings($data);
        }
        return $data;
    }

    /**
     * Delete group by userbundle ID
     *
     * @param int $id
     * @return bool|resource
     */
    private function deleteByGroupId($id)
    {
        return $this->deleteBySpecifiedField($id, 'id', new shopProductsetsUserbundleGroupItemPluginModel(), 'group_id');
    }

    /**
     * Delete all unneccesary groups
     *
     * @param int $userbundle_id
     */
    public function clean($userbundle_id)
    {
        // Все имеющиеся записи
        $ids = array_keys($this->getByField('userbundle_id', $userbundle_id, 'id'));
        $saved_ids = ifset($this->saved_ids[$this->getTableName()], []);
        // Удаляем ненужные записи
        if ($delete_ids = array_diff($ids, $saved_ids)) {
            foreach ($delete_ids as $id) {
                $this->deleteByGroupId($id);
            }
        }
    }
}
