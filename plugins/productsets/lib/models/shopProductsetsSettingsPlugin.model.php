<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsSettingsPluginModel extends waModel
{

    protected $table = 'shop_productsets_settings';

    /**
     * Get all settings
     *
     * @param int|array $set_id
     * @param string|null $field
     * @param bool $serialize_value
     * @return array
     */
    public function getSettings($set_id = 0, $field = null, $serialize_value = false)
    {
        $settings = array();
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $sql .= " productsets_id IN (?)";

        if ($field) {
            $sql .= " AND field = '" . $this->escape($field) . "'";
        }
        $result = $this->query($sql, array($set_id));
        if ($result) {
            foreach ($result as $r) {
                $ref = null;
                $value = $serialize_value && !in_array($r['field'], ['appearance', 'appearance_settings']) ? json_decode($r['value'], true) : $r['value'];
                $value = $serialize_value && $value === null ? $r['value'] : $value;
                $key = is_int($set_id) ? $r['field'] : $r['productsets_id'];
                if (!isset($settings[$key])) {
                    $settings[$key] = array();
                }
                if (is_int($set_id)) {
                    $ref = $settings[$r['field']];
                    $settings[$r['field']] = &$ref;
                } else {
                    if (!isset($settings[$r['productsets_id']][$r['field']])) {
                        $settings[$r['productsets_id']][$r['field']] = array();
                    }
                    $ref = $settings[$r['productsets_id']][$r['field']];
                    $settings[$r['productsets_id']][$r['field']] = &$ref;
                }
                if ($r['ext']) {
                    $ref[$r['ext']] = $value;
                } else {
                    $ref = $value;
                }
                unset($ref);
            }
        }
        return $settings;
    }

    /**
     * Save settings
     *
     * @param array $settings
     * @param int $pset_id
     * @param bool $serialize_value
     * @return boolean
     */
    public function save($settings, $pset_id = 0, $serialize_value = false)
    {
        $query = array();
        if ($settings && is_array($settings)) {
            foreach ($settings as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $ext => $v2) {
                        $v2 = $serialize_value ? json_encode($v2, JSON_UNESCAPED_UNICODE) : $this->escape($v2);
                        $query[] = "('" . $pset_id . "', '" . $this->escape($k) . "', '" . $this->escape($ext) . "', '" . $v2 . "')";
                    }
                } else {
                    $v = $serialize_value ? json_encode($v, JSON_UNESCAPED_UNICODE) : $this->escape($v);
                    $query[] = "('" . $pset_id . "', '" . $this->escape($k) . "', '', '" . $v . "')";
                }
            }
        }
        if ($query) {
            $sql = "INSERT INTO {$this->table} (`productsets_id`, `field`, `ext`, `value`) VALUES " . implode(",", $query) . " ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)";
            return $this->exec($sql);
        }
        return true;
    }

    /**
     * @param int $set_id
     */
    public function deleteDisplaySettings($set_id)
    {
        $this->deleteByField(array('productsets_id' => $set_id, 'field' => 'product'));
        $this->deleteByField(array('productsets_id' => $set_id, 'field' => 'category'));
    }

    /**
     * @param int $set_id
     */
    public function deleteOtherSettings($set_id)
    {
        $this->deleteByField(array('productsets_id' => $set_id, 'field' => 'other'));
    }
}
