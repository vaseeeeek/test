<?php

class shopListfeaturesPluginHelper
{
    public static function getPlugin()
    {
        static $plugin;
        if (empty($plugin)) {
            $plugin = new shopListfeaturesPlugin(wa('shop')->getConfig()->getPluginInfo('listfeatures') + array('id' => 'listfeatures'));
        }
        return $plugin;
    }

    public static function getView()
    {
        static $view;
        if (empty($view)) {
            $view = wa()->getView();
        }
        return $view;
    }

    public static function getSettlementHash()
    {
        static $hash;
        if (empty($hash)) {
            $routing = wa()->getRouting();
            $domain_id = self::getModel()->query(
                'SELECT id
                FROM site_domain
                WHERE name = s:0',
                $routing->getDomain()
            )->fetchField();
            $hash = md5($domain_id.'/'.$routing->getRoute('url'));
        }
        return $hash;
    }

    public static function getSettlements()
    {
        static $settlements;
        if (empty($settlements)) {
            $settlements = array();
            if ($routing = include wa()->getConfig()->getPath('config', 'routing')) {
                foreach ($routing as $domain => $routes) {
                    if (is_array($routes)) {
                        foreach ($routes as $route) {
                            $settlements[$domain.'/'.$route['url']] = ifset($route['type_id'], 0);
                        }
                    }
                }
                ksort($settlements);
            }
        }
        return $settlements;
    }

    public static function getHashSettlements()
    {
        static $result;

        if (!$result) {
            try {
                if (!($routing = include wa()->getConfig()->getPath('config', 'routing')) || !is_array($routing)) {
                    throw new Exception();
                }

                $domain_ids = self::getModel()->query(
                    'SELECT
                        id,
                        name
                    FROM site_domain
                    WHERE name IN(s:domains)',
                    array(
                        'domains' => array_keys($routing),
                    )
                )->fetchAll('name', true);

                if (!$domain_ids) {
                    throw new Exception();
                }

                foreach ($routing as $domain => $routes) {
                    if (!is_array($routes)) {
                        continue;
                    }
                    foreach ($routes as $route) {
                        if (empty($route['url']) || empty($route['app'])) {
                            continue;
                        }
                        $settlements[$domain_ids[$domain].'/'.$route['url']] = $domain.'/'.$route['url'];
                    }
                }
                ksort($settlements);

                $result = array();
                foreach ($settlements as $key => $settlement) {
                    $hash = md5($key);
                    $result[$hash] = $settlement;
                }
            } catch (Exception $e) {
                //
            }
        }

        return $result;
    }

    public static function getSettlementConfig($settlement = null, $set_id = null, $field = null)
    {
        if (is_null($settlement)) {
            $settlement = self::getSettlementHash();
        }

        static $config;
        if (empty($config)) {
            $config = self::getPlugin()->getSettings($settlement);
        }

        /**
         * Dynamically add missing sets for newly added settlement when trying to read its config.
         * Missing sets are added only for current settlement.
         * Makes sense in backend only.
         *
         * To disable this check with LARGE settlement lists, create file wa-config/apps/shop/plugins/listfeatures.php with
         *
         * <?php
         * return array(
         *     'disable_new_route_check' = true,
         * );
         */
        $plugin_config = wa()->getConfig()->getConfigFile('apps/shop/plugins/listfeatures');
        if (ifset($plugin_config['disable_new_route_check']) && wa()->getEnv() == 'backend') {
            $all_settlements_config = self::getAllSettlementsConfig();
            if (is_array($all_settlements_config)) {
                $max_set_count = 0;
                foreach ($all_settlements_config as $settlement_config) {
                    if (is_array($settlement_config)) {
                        $max_set_count = max($max_set_count, count($settlement_config));
                    }
                }
                if (!$config) {
                    $config = array();
                }
                if (count($config) < $max_set_count) {
                    for ($i = 1; $i <= $max_set_count; $i++) {
                        if (!array_key_exists($i, $config)) {
                            $config[$i] = array();
                        }
                    }
                }
            }
        }

        //continue as normal
        if (!is_null($set_id)) {
            if (!is_null($field)) {
                $result = ifset($config[$set_id][$field]);
            } else {
                $result = ifset($config[$set_id]);
            }
        } else {
            $result = $config;
        }
        return ifempty($result, array());
    }

    public static function getAllSettlementsConfig()
    {
        $hash_settlements = self::getHashSettlements();
        $settings = self::getPlugin()->getSettings();

        $config = array();
        foreach ($settings as $key => $value) {
            if (!isset($hash_settlements[$key])) {
                continue;
            }
            $config[$key] = $value;
        }

        return $config;
    }

    public static function saveSettlementConfig($params)
    {
        /**
         * settlement => array(
         *     set_id => array(
         *         'features' => array(
         *             feature_id => array(
         *                 //optional feature options
         *             )
         *         ),
         *         'options' => array(
         *             //optional set options
         *         ),
         *     )
         * )
         */

        extract($params);
        /**
         * @var $settlement string Settlement to save config for
         * @var $set_id int|null Null to add new set to all settlements
         * @var $feature_ids array|null Null to remove set from all settlements
         * @var $options array|null
         */

        $config = self::getAllSettlementsConfig();
        $settlement_hashes = array_keys(self::getHashSettlements());
        $update_all = false;

        if (!$set_id) {    //add new set to all settlements
            $update_all = true;
            $set_id = $new_set_id = isset($config[$settlement]) && $config[$settlement] ? (int) max(array_keys($config[$settlement])) + 1 : 1;
        }

        if ($feature_ids === null) {    //remove set from all settlements
            $update_all = true;
            foreach ($settlement_hashes as $s) {
                if (isset($config[$s][$set_id])) {
                    unset($config[$s][$set_id]);
                }
            }
        } else {    //save feature ids for new or specified set of specified settlement
            foreach ($settlement_hashes as $s) {
                //add new empty set for all settlements, if it does not exist
                //for newly added settlements: also add empty sets with previous IDs if they do not yet exist
                $set_id_counter = 0;
                while (++$set_id_counter <= $set_id) {
                    if (!isset($config[$s][$set_id_counter]['features'])) {
                        $config[$s][$set_id_counter]['features'] = array();
                    }
                }
                if ($s == $settlement) {    //save feature set for selected settlement only
                    //remove unselected features from config
                    foreach ($config[$s][$set_id]['features'] as $feature_id => $feature_config) {
                        if (!in_array($feature_id, $feature_ids)) {
                            unset($config[$s][$set_id]['features'][$feature_id]);
                        }
                    }
                    //add newly selected features to config while keeping existing ones unchanged
                    foreach ($feature_ids as $feature_id) {
                        if (!in_array($feature_id, array_keys($config[$s][$set_id]['features']))) {
                            $config[$s][$set_id]['features'][$feature_id] = array();
                        }
                    }
                }
            }
            if (isset($options)) {
                $config[$settlement][$set_id]['options'] = $options;
            }
        }

        if ($update_all) {
            self::getPlugin()->saveSettings($config);
        } else {
            self::getPlugin()->saveSettings(array($settlement => $config[$settlement]));
        }
        return ifset($new_set_id, $set_id);
    }

    public static function saveFeatureOptions($params)
    {
        extract($params);
        /**
         * @var $settlement string
         * @var $set_id int
         * @var $feature_id int
         * @var $options array
         */

        //save feature meta to feature table
        $listfeatures_feature_model = new shopListfeaturesPluginFeatureModel();

        $key_fields = array(
            'settlement' => $settlement,
            'set_id'     => $set_id,
            'feature_id' => $feature_id,
        );

        $meta_fields = array();
        if (isset($options['meta_keywords'])) {
            $meta_fields['meta_keywords'] = trim($options['meta_keywords']);
            unset($options['meta_keywords']);
        } else {
            $meta_fields['meta_keywords'] = '';
        }

        if (isset($options['meta_description'])) {
            $meta_fields['meta_description'] = trim($options['meta_description']);
            unset($options['meta_description']);
        } else {
            $meta_fields['meta_description'] = '';
        }

        if ($meta_fields) {
            if (array_filter($meta_fields, 'strlen')) {
                //save data if there is something to save
                $listfeatures_feature_model->insert($key_fields + $meta_fields, 1);
            } else {
                //or remove useless empty records from database table
                $listfeatures_feature_model->deleteByField($key_fields);
            }
        }

        //save the rest to wa_app_settings
        $config = self::getSettlementConfig($settlement);

        $config[$set_id]['features'][$feature_id] = $options;
        self::getPlugin()->saveSettings(array($settlement => $config));
    }

    public static function getTemplates()
    {
        $settings = self::getPlugin()->getSettings();
        $templates = array();
        foreach ($settings as $key => $value) {
            if (preg_match('/^template(\d+)$/', $key, $matches)) {
                $templates[$matches[1]] = $value;
            }
        }
        ksort($templates);
        return $templates;
    }

    public static function getTemplate($id = null)
    {
        if (wa_is_int($id) && $id) {
            $template = self::getPlugin()->getSettings('template'.$id);
            return ifempty($template, self::getTemplate());
        } else {
            return file_get_contents(wa('shop')->getAppPath('plugins/listfeatures/templates/features.html'));
        }
    }

    public static function saveTemplate($id, $source)
    {
        if (!$id) {    //create new template
            $existing_templates = array_keys(self::getTemplates());
            if ($existing_templates) {
                $max = max($existing_templates);
                $new_id = $max + 1;
                for ($i = 1; $i < $max; $i++) {
                    if (!in_array($i, $existing_templates)) {
                        $new_id = $i;
                        break;
                    }
                }
            } else {
                $new_id = 1;
            }
            $id = $new_id;
        }

        self::getPlugin()->saveSettings(array('template'.$id => $source));
        return ifset($new_id);
    }

    public static function deleteTemplate($id)
    {
        self::getPlugin()->saveSettings(array('template'.$id => null));
    }

    public static function isFeatureType($feature_id, $type)
    {
        static $feature_model;
        if (empty($feature_model)) {
            $feature_model = new shopFeatureModel();
        }

        static $feature;
        if (!isset($feature[$type])) {
            switch ($type) {
                case 'color':
                    $feature[$type] = (bool) $feature_model->countByField(array('id' => $feature_id, 'type' => 'color'));
                    break;
                case 'multiple':
                    $feature[$type] = $feature_model->countByField(array('id' => $feature_id, 'multiple' => 1))
                        || in_array($feature_id, array('tags', 'categories', 'skus', 'pages'));
                    break;
                case 'selectable':
                    $feature[$type] = (bool) $feature_model->countByField(array('id' => $feature_id, 'selectable' => 1));
                    break;
                case 'link':
                    $feature[$type] = in_array($feature_id, array('tags', 'categories')) || self::isFeatureType($feature_id, 'selectable');
                    break;
                case 'extra':
                    $feature[$type] = in_array($feature_id, array('tags', 'categories', 'skus'));
                    break;
                case 'filter':
                    $feature[$type] = wa_is_int($feature_id);
                    break;
            }
        }
        return $feature[$type];
    }

    public static function getModel()
    {
        static $model;
        if (!$model) {
            $model = new waModel();
        }
        return $model;
    }

    public static function settlementPunycodeToUtf($settlement)
    {
        $has_asterisk = strpos($settlement, '*') !== false;
        $settlement_parts = explode('/', rtrim($settlement, '*'));
        foreach ($settlement_parts as &$settlement_part) {
            $settlement_part = shopListfeaturesPluginIdna::decode($settlement_part);
        }
        unset($settlement_part);
        return implode('/', $settlement_parts).($has_asterisk ? '*' : '');
    }
}
