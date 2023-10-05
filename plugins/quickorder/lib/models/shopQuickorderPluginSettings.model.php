<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginSettingsModel extends waModel
{
    protected $table = 'shop_quickorder_settings';

    /**
     * Get plugin settings for storefront
     *
     * @param string $storefront
     * @return array
     */
    public function getSettings($storefront = 'all')
    {
        $settings = array();
        $sql = "SELECT * FROM {$this->table} WHERE `storefront` = s:storefront";
        foreach ($this->query($sql, array('storefront' => $storefront)) as $r) {
            if (!empty($r['ext'])) {
                if (!isset($settings[$r['field']])) {
                    $settings[$r['field']] = array();
                }
                $settings[$r['field']][$r['ext']] = $r['value'];
            } else {
                $settings[$r['field']] = $r['value'];
            }
        }
        return $settings;
    }

    /**
     * Save storefront settings and delete previous
     *
     * @param string $storefront
     * @param array $settings
     * @return bool|resource
     */
    public function set($storefront, $settings)
    {
        $config = include(wa()->getAppPath('plugins/quickorder/lib/config/templates.php'));
        if (isset($config['templates'])) {
            $sql = "DELETE FROM {$this->table} WHERE storefront = s:storefront AND field NOT IN ('" . implode("_tmpl','", array_keys($config['templates'])) . "_tmpl')";
            $this->exec($sql, ['storefront' => $storefront]);
        } else {
            $this->deleteByField(array('storefront' => $storefront));
        }

        $query = array();
        if ($settings && is_array($settings)) {
            foreach ($settings as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $ext => $v2) {
                        $query[] = "('" . $this->escape($storefront) . "', '" . $this->escape($k) . "', '" . $this->escape($ext) . "', '" . $this->escape($v2) . "')";
                    }
                } else {
                    $query[] = "('" . $this->escape($storefront) . "', '" . $this->escape($k) . "', '', '" . $this->escape($v) . "')";
                }
            }
        }
        if ($query) {
            $sql = "INSERT INTO {$this->table} (`storefront`, `field`, `ext`, `value`) VALUES " . implode(",", $query) . " ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)";
            return $this->exec($sql);
        }
        return true;
    }
}