<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsCartPluginModel extends waModel
{

    protected $table = 'shop_productsets_cart';

    /**
     * Get cart sets with selected items
     *
     * @param string $code
     * @return array
     */
    public function getCartSets($code)
    {
        $data = array();
        $items_model = new shopProductsetsCartItemsPluginModel();
        $bundle_item_model = new shopProductsetsBundleItemPluginModel();
        $sql = "SELECT c.*, ci.*, bi.parent_id FROM {$this->table} c 
                LEFT JOIN {$items_model->getTableName()} ci ON ci.cart_id = c.id
                LEFT JOIN {$bundle_item_model->getTableName()} bi ON ci.bundle_item_id = bi.id
                WHERE c.code = s:code
                ORDER BY c.id ASC, ci.sort ASC";
        foreach ($this->query($sql, array('code' => $code)) as $k => $row) {
            if (!isset($data[$row['productsets_id']])) {
                $data[$row['productsets_id']] = array();
            }
            $key = $row['id'];
            if (!isset($data[$row['productsets_id']][$key])) {
                $data[$row['productsets_id']][$key] = array(
                    'bundle_id' => $row['bundle_id'],
                    'include_product' => $row['include_product'],
                    'items' => []
                );
            }
            $data[$row['productsets_id']][$key]['items']['i' . $row['bundle_item_id']] = $row;
        }

        return $data;
    }

    /**
     * Delete information about the bundle from the cart
     *
     * @param int $bundle_id
     * @param string $code
     * @param int $set_id
     * @return mixed
     */
    public function deleteByBundleId($bundle_id, $code, $set_id = 0)
    {
        $items_model = new shopProductsetsCartItemsPluginModel();
        $sql = "DELETE c, ci FROM {$this->table} c 
                LEFT JOIN {$items_model->getTableName()} ci ON ci.cart_id = c.id
                WHERE c.bundle_id = i:bundle_id AND c.code = s:code";
        if ($set_id !== 0) {
            $sql .= " AND c.productsets_id = '" . (int) $set_id . "'";
        }
        return $this->exec($sql, array('bundle_id' => $bundle_id, 'code' => $code));
    }

    /**
     * Delete information about the bundle from the cart
     *
     * @param string $code
     * @return mixed
     */
    public function deleteByCode($code)
    {
        $items_model = new shopProductsetsCartItemsPluginModel();
        $sql = "DELETE c, ci FROM {$this->table} c 
                LEFT JOIN {$items_model->getTableName()} ci ON ci.cart_id = c.id
                WHERE c.code = s:code";
        return $this->exec($sql, array('code' => $code));
    }

    /**
     * Delete items
     *
     * @param array $params
     * @return bool
     */
    public function delete($params)
    {
        $pscim = new shopProductsetsCartItemsPluginModel();
        $sql = "DELETE c, ci FROM {$this->table} c
                LEFT JOIN {$pscim->getTableName()} ci ON c.id = ci.spci_id
                WHERE c.code = s:code AND c.productsets_id = i:productsets_id AND c.usercreate = i:usercreate";
        return $this->exec($sql, array('code' => $params['code'], 'productsets_id' => $params['productsets_id'], 'usercreate' => $params['usercreate']));
    }

}
