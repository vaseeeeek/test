<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountParamsPluginModel extends waModel
{

    protected $table = 'shop_flexdiscount_params';

    /**
     * Add params
     * 
     * @param int $fl_id
     * @param array $params
     */
    public function add($fl_id, $params)
    {
        // Очищаем параметры
        $this->deleteByField('fl_id', $fl_id);

        if ($params) {
            $insert = array();
            foreach ($params as $p_id => $p) {
                if (is_array($p)) {
                    foreach ($p as $sub_p_id => $sub_p) {
                        $insert[] = array("fl_id" => $fl_id, "field" => $p_id, "ext" => $sub_p_id, "value" => $sub_p);
                    }
                } else {
                    $insert[] = array("fl_id" => $fl_id, "field" => $p_id, "ext" => '', "value" => $p);
                }
            }
            if ($insert) {
                $this->multipleInsert($insert);
            }
        }
    }

    /**
     * Get params for discounts
     * 
     * @param array[int]|int $fl_ids
     * @return array
     */
    public function getParams($fl_ids)
    {
        $result = array();
        $sql = "SELECT * FROM {$this->getTableName()} WHERE fl_id ";
        if (is_array($fl_ids)) {
            $sql .= " IN ('" . implode("','", $this->escape($fl_ids, 'int')) . "')";
        } else {
            $sql .= " = '" . (int) $fl_ids . "'";
        }
        foreach ($this->query($sql) as $r) {
            if (!isset($result[$r['fl_id']])) {
                $result[$r['fl_id']] = array();
            }
            if ($r['ext']) {
                if (!isset($result[$r['fl_id']][$r['field']])) {
                    $result[$r['fl_id']][$r['field']] = array();
                }
                $result[$r['fl_id']][$r['field']][$r['ext']] = ($r['ext'] == 'filter_by' || $r['ext'] == 'ignore_deny') ? @unserialize($r['value']) : $r['value'];
            } else {
                $result[$r['fl_id']][$r['field']] = $r['value'];
            }
        }
        return is_array($fl_ids) ? $result : ($result ? reset($result) : $result);
    }

}
