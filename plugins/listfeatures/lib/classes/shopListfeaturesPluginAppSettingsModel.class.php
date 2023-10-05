<?php

class shopListfeaturesPluginAppSettingsModel extends waAppSettingsModel
{
    /**
     * Overridden to use multipleInsert() for saving numerous settings with one query.
     * @see waAppSettingsModel::set()
     */
    public function set($app_id, $name, $value = null)
    {
        if (is_array($app_id)) {
            $key = $app_id[0].'.'.$app_id[1];
            $app_id = $app_id[0];
        } elseif (strpos($app_id, '.') !== false) {
            $key = $app_id;
            $app_id = substr($key, 0, strpos($key, '.'));
        }

        $this->getCache($app_id)->delete();

        if (is_array($name) && count($name) > 1) {    //multi-entry array: insert all rows with one SQL query
            $settings = $name;
            $new_data = array();
            foreach ($settings as $name => $value) {
                self::$settings[$key][$name] = $value;
                $new_data[] = array(
                    'app_id' => $key,
                    'name'   => $name,
                    'value'  => is_array($value) ? json_encode($value) : $value,
                );
            }
            $this->deleteByField(array(
                'app_id' => $key,
                'name'   => array_keys($settings),
            ));

            $this->multipleInsert($new_data);
        } else {
            //one-entry array: convert to simple name and value
            if (is_array($name)) {
                $value = reset($name);
                $name = key($name);
            }

            self::$settings[$key][$name] = $value;

            $value = is_array($value) ? json_encode($value) : $value;
            if ($this->getByField(array('app_id' => $key, 'name' => $name))) {
                $this->updateByField(array('app_id' => $key, 'name' => $name), array('value' => $value));
            } else {
                $this->insert(array('app_id' => $key, 'name' => $name, 'value' => $value));
            }
        }
    }
}
