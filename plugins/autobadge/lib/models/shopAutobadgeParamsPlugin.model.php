<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgeParamsPluginModel extends waModel
{

    protected $table = 'shop_autobadge_params';

    /**
     * Add params
     * 
     * @param int $autobadge_id
     * @param array $params
     */
    public function add($autobadge_id, $params)
    {
        if ($params) {
            $old_params = $this->getParams($autobadge_id);
            $insert = array();
            foreach ($params as $p_id => $p) {
                if (is_array($p)) {
                    foreach ($p as $sub_p_id => $sub_p) {
                        if (!isset($old_params[$p_id][$sub_p_id])) {
                            $insert[] = array("autobadge_id" => $autobadge_id, "field" => $p_id, "ext" => $sub_p_id, "value" => $sub_p);
                        } else {
                            $this->updateByField(array("autobadge_id" => $autobadge_id, "field" => $p_id, "ext" => $sub_p_id), array("value" => $sub_p));
                        }
                    }
                } else {
                    if (!isset($old_params[$p_id])) {
                        $insert[] = array("autobadge_id" => $autobadge_id, "field" => $p_id, "ext" => '', "value" => $p);
                    } else {
                        $this->updateByField(array("autobadge_id" => $autobadge_id, "field" => $p_id), array("value" => $p));
                    }
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
     * @param array[int]|int $autobadge_ids
     * @return array
     */
    public function getParams($autobadge_ids)
    {
        $result = array();
        $sql = "SELECT * FROM {$this->getTableName()} WHERE autobadge_id ";
        if (is_array($autobadge_ids)) {
            $sql .= " IN ('" . implode("','", $this->escape($autobadge_ids, 'int')) . "')";
        } else {
            $sql .= " = '" . (int) $autobadge_ids . "'";
        }
        foreach ($this->query($sql) as $r) {
            if (!isset($result[$r['autobadge_id']])) {
                $result[$r['autobadge_id']] = array();
            }
            if ($r['ext']) {
                if (!isset($result[$r['autobadge_id']][$r['field']])) {
                    $result[$r['autobadge_id']][$r['field']] = array();
                }
                $result[$r['autobadge_id']][$r['field']][$r['ext']] = $r['value'];
            } else {
                $result[$r['autobadge_id']][$r['field']] = $r['value'];
            }
        }
        return is_array($autobadge_ids) ? $result : ($result ? reset($result) : $result);
    }

}
