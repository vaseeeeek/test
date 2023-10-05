<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginCartItemsModel extends waModel
{
    protected $table = 'shop_quickorder_cart_items';

    /**
     *
     * @param shopQuickorderPluginCart $cart
     * @param bool|int|string $check_count bool or stock_id or 'v<virtualstock_id>'
     * @return array
     * @throws waException
     */
    public function getNotAvailableProducts(shopQuickorderPluginCart $cart, $check_count)
    {
        $code = $cart->getCode();

        if ($cart->getType() == 'product') {
            $cart->addItems();
        }

        $count_join = '';
        $count_condition = '';
        $count_field = 's.count';
        if ($check_count) {
            if (is_string($check_count) && $check_count{0} == 'v') {
                // Virtual stock id: check against sum of several stock counts
                $virtualsku_id = substr($check_count, 1);
                if (wa_is_int($virtualsku_id)) {
                    $sql = "SELECT stock_id FROM shop_virtualstock_stocks WHERE virtualstock_id=?";
                    $stock_ids = array_keys($this->query($sql, $virtualsku_id)->fetchAll('stock_id'));
                    if ($stock_ids) {
                        $sql = "SELECT DISTINCT sku_id FROM {$this->table} WHERE type = 'product' AND code = ?";
                        $sku_ids = array_keys($this->query($sql, array($code))->fetchAll('sku_id'));

                        if ($sku_ids) {
                            $count_field = 't.count';
                            $count_join = "LEFT JOIN (
                                               SELECT ps.sku_id, SUM(ps.count) AS count
                                               FROM shop_product_stocks AS ps
                                               WHERE ps.stock_id IN (" . join(',', $stock_ids) . ")
                                                   AND ps.sku_id IN (" . join(',', $sku_ids) . ")
                                               GROUP BY ps.sku_id
                                               HAVING (COUNT(ps.stock_id)) >= " . count($stock_ids) . "
                                           ) AS t ON t.sku_id=ci.sku_id";
                        }
                    }
                }
            } elseif (wa_is_int($check_count)) {
                // Normal stock id: check against stock count
                $count_field = "ps.count";
                $count_join = "LEFT JOIN shop_product_stocks AS ps
                                   ON ps.sku_id = ci.sku_id AND ps.stock_id = '{$check_count}'";
            } else {
                // No stock specified; check against total count of the SKU
                $count_field = 's.count';
            }
            $count_condition = "OR ({$count_field} IS NOT NULL AND ci.quantity > {$count_field})";
        }

        $sql = "SELECT ci.id, p.name, s.name AS sku_name, s.available, {$count_field} AS `count`
                FROM {$this->table} AS ci
                    JOIN shop_product AS p
                        ON ci.product_id = p.id
                    JOIN shop_product_skus AS s
                        ON ci.sku_id = s.id
                    {$count_join}
                WHERE ci.type = 'product'
                    AND ci.code = s:code
                    AND (s.available = 0 {$count_condition})";

        $result = $this->query($sql, array('code' => $code))->fetchAll();

        if ($cart->getType() == 'product') {
            $cart->removeItems();
        }

        return $result;
    }

    public function getItems($code)
    {
        return $this->getByField('code', $code, 'id');
    }
}