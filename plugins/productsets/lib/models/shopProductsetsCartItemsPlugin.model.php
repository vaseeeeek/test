<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsCartItemsPluginModel extends waModel
{

    protected $table = 'shop_productsets_cart_items';

    /**
     * Save set items
     *
     * @param int $cart_id
     * @param array $items
     */
    public function save($cart_id, $items)
    {
        $data = array();
        foreach ($items as $item) {
            $data[] = array(
                'cart_id' => $cart_id,
                'sku_id' => $item['sku_id'],
                'product_id' => $item['product_id'],
                'bundle_item_id' => $item['bundle_item_id'],
                'quantity' => $item['quantity'],
                'is_active' => !empty($item['is_active']) ? 1 : 0,
                'sort' => $item['sort']
            );
        }
        $this->multipleInsert($data);
    }

}
