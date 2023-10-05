<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsParamsPluginModel extends shopProductsetsModel
{
    protected $table = 'shop_productsets_params';

    /**
     * Get all parameters
     *
     * @param array|int $ids
     * @return array|mixed
     */
    public function getParams($ids)
    {
        $result = [];
        foreach ($this->query("SELECT * FROM {$this->table} WHERE productsets_id IN (?)", [$ids]) as $r) {
            $result[$r['productsets_id']][$r['param']] = $r['value'];
        }

        return is_array($ids) ? $result : reset($result);
    }

    /**
     * Save parameters to set
     *
     * @param int $id
     * @param array $dirty_params
     * @return int|void
     */
    public function save($id, $dirty_params = [])
    {
        $params = [];

        if (!empty($dirty_params['name'])) {
            foreach ($dirty_params['name'] as $k => $param) {
                if (!empty($param) && isset($dirty_params['value'][$k])) {
                    $params[] = ['productsets_id' => $id, 'param' => $param, 'value' => $dirty_params['value'][$k]];
                }
            }
        }
        $this->deleteByField('productsets_id', $id);

        $this->multipleInsert($params);
    }

}