<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountOrderParamsPluginModel extends waModel
{

    protected $table = 'shop_flexdiscount_order_params';

    public function set($order_id, $params)
    {
        $insert = array();
        foreach ($params as $name => $value) {
            $insert[] = array("order_id" => $order_id, "name" => $name, "value" => $value);
        }
        if ($insert) {
            $this->multipleInsert($insert);
        }
    }

    public function get($order_id, $name = null)
    {
        $result = array();
        if (!$name) {
            foreach ($this->getByField("order_id", $order_id, true) as $r) {
                $result[$r['name']] = $r['value'] ? json_decode($r['value'], true) : $r['value'];
            }
        } else {
            $r = $this->getByField(array("order_id" => $order_id, "name" => $name));
            $result = $r['value'] ? json_decode($r['value'], true) : $r['value'];
        }
        return $result;
    }

}
