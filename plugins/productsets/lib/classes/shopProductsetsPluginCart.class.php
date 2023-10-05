<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginCart extends shopCart
{

    public function __construct()
    {
        if (wa()->getVersion() >= 8) {
            parent::__construct('', [
                'generate_code' => true
            ]);
        } else {
            parent::__construct();
        }
    }

    /**
     * Get cart items quantity
     *
     * @param array $sku_ids
     * @return array
     */
    public function getItemsQuantityBySkuIds($sku_ids)
    {
        $model = $this->getModel();
        $sql = "SELECT SUM(quantity) as quantity, sku_id, id FROM " . $model->getTableName() . " 
                WHERE code = s:code AND type = 'product' AND sku_id IN ('" . implode("','", $model->escape($sku_ids, 'int')) . "')
                GROUP BY (sku_id)";

        return $model->query($sql, array('code' => $this->code))->fetchAll('sku_id');
    }

    /**
     * Save set items
     *
     * @param array $items
     * @param int $set_id
     * @param int $bundle_id
     * @param bool $has_active_item
     */
    public function saveItems($items, $set_id, $bundle_id, $has_active_item)
    {
        $cart_id = (new shopProductsetsCartPluginModel())->insert(array(
            'productsets_id' => $set_id,
            'bundle_id' => $bundle_id,
            'code' => $this->code,
            'include_product' => $has_active_item ? 1 : 0
        ));
        if ($cart_id) {
            (new shopProductsetsCartItemsPluginModel())->save($cart_id, $items);
        }
    }

    /**
     * Get cart sets with selected items
     *
     * @return array
     */
    public function getCartSets()
    {
        return (new shopProductsetsCartPluginModel())->getCartSets($this->code);
    }

    /**
     * Delete information about the bundle from the cart
     *
     * @param int $bundle_id
     * @param int $set_id
     * @return mixed
     */
    public function deleteByBundleId($bundle_id, $set_id = 0)
    {
        return (new shopProductsetsCartPluginModel())->deleteByBundleId($bundle_id, $this->code, $set_id);
    }

    /**
     * Delete information about the bundle from the cart
     *
     * @return mixed
     */
    public function deleteByCode()
    {
        return (new shopProductsetsCartPluginModel())->deleteByCode($this->code);
    }

    /**
     * Get cart model. SS7, SS8 method
     * @return shopCartItemsModel
     */
    private function getModel()
    {
        if (method_exists($this, 'model')) {
            return $this->model();
        } else {
            return $this->model;
        }
    }

}