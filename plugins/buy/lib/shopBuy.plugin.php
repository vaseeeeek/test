<?php

/**
 * Simplified buy plugin
 * @author Hardman.com.ua
 */
class shopBuyPlugin extends shopPlugin
{

    public function frontendCheckout($params)
    {
        $step = ifempty($params, 'step', '');
        if(
            (waRequest::param('action') === 'checkout')
            && ($step !== 'success')
            && $this->getSettings('redirect')
        ) {
            wa()->getResponse()->redirect('/buy/', 302);
        }

        return $this->frontendCart();
    }

    public function frontendCart($params = null)
    {
        if(!$errors = self::checkCartItems()) {
            return '';
        }

        $res = '<script>alert(\'';
        foreach ($errors as $error) {
            $res .= $error.'\n';
        }
        $res .= '\')</script>';
        return $res;
    }

    public static function checkCartItems()
    {
        $cart = new shopCart();
        $cart_model = new shopCartItemsModel();

        $sql = 'SELECT SUM(quantity), sku_id'.
            ' FROM '.$cart_model->getTableName().
            ' WHERE code = s:code AND type = "product"'.
            ' GROUP BY sku_id';


        $qty_needed = $cart_model->query($sql, array('code' => $cart->getCode()))->fetchAll('sku_id', true);

        $errors = array();

        if(!$qty_needed) {
            return array();
        }

        $sku_model = new shopProductSkusModel();
        $qty_real = $sku_model->select('id, count')->where('id IN(:ids)', array('ids' => array_keys($qty_needed)))
            ->fetchAll('id', true);

        $sql = 'SELECT s.id, p.name product_name, s.name sku_name '.
            'FROM shop_product_skus s '.
            'JOIN shop_product p ON p.id = s.product_id '.
            'WHERE s.id IN (:ids)';

        $names = $sku_model->query($sql, array('ids' => array_keys($qty_needed)))->fetchAll('id');

        foreach ($qty_needed as $sku_id => $qty) {
            if(!empty($names[$sku_id])) {
                $name = $names[$sku_id]['product_name'].($names[$sku_id]['sku_name'] ? ' ('.$names[$sku_id]['sku_name'].')' : '');
            } else {
                continue;
                //$name = '#'.$sku_id;
            }

            if(!array_key_exists($sku_id, $qty_real)) {
                $errors[] = sprintf(_w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience.'), $name);
            } elseif(($qty_real[$sku_id] !== null) && ($qty_real[$sku_id] < $qty)) {
                if($qty_real[$sku_id] > 0) {
                    $errors[] = sprintf(_w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.'), $qty_real[$sku_id], $name);
                } else {
                    $errors[] = sprintf(_w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience.'), $name);
                }
            }
        }

        return $errors;
    }

}