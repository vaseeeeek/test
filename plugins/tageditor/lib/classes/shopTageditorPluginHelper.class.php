<?php

class shopTageditorPluginHelper
{
    public static function getDefaultValues()
    {
        return @unserialize(shopTageditorPluginModels::waAppSettings()->get('shop.tageditor', 'default_values'));
    }

    public static function saveDefaultValues($data)
    {
        if ($data) {
            $default_values = self::getDefaultValues();
            foreach ($data as $item => $value) {
                $default_values[$item] = $value;
            }
            $default_values = array_filter($default_values);
        }

        if (ifempty($default_values)) {
            shopTageditorPluginModels::waAppSettings()->set('shop.tageditor', 'default_values', serialize($default_values));
        } else {
            shopTageditorPluginModels::waAppSettings()->del('shop.tageditor', 'default_values');
        }
    }

    public static function getTagData($tag)
    {
        static $result = array();
        if (!isset($result[$tag])) {
            $result[$tag] = shopTageditorPluginModels::tag()->getByName($tag);
        }
        return $result[$tag];
    }

    public static function getFieldAliases(&$data)
    {
        static $field_aliases;

        if (empty($data) || !is_array($data)) {
            $field_aliases = array();
        }

        if (!is_null($field_aliases)) {
            return $field_aliases;
        }

        $field_aliases = array();
        foreach ($data as $tag_field => &$field_value) {
            if (preg_match('@^field_alias:([a-z_]+)$@', $field_value, $m)) {
                if (wa()->getEnv() == 'frontend' || shopTageditorPluginModels::tag()->fieldExists($m[1])) {
                    $field_aliases[$tag_field] = $m[1];
                }
                $field_value = '';
            }
        }
        return $field_aliases;
    }

    public static function fieldIsAlias($value)
    {
        return preg_match('@^field_alias:[a-z_]+$@', $value);
    }

    public static function getSitemapItems()
    {
        $sitemap_tag_selection = wa('shop')->getPlugin('tageditor')->getSettings('sitemap_tag_selection');

        $check_types = false;
        $params = array();

        if ($sitemap_tag_selection != 'all') {
            $route_type_ids = wa()->getRouting()->getRoute('type_id');

            if ($route_type_ids) {
                $type_model = new shopTypeModel();
                $shop_type_ids = $type_model->select('id')->fetchAll(null, true);

                //ignore type IDs selected in route settigs which no longer exist in store settings
                $type_ids = array_intersect($shop_type_ids, $route_type_ids);

                if ($type_ids && array_diff($shop_type_ids, $type_ids)) {
                    //check if there are product types not selected in current route's settings
                    $check_types = true;
                    $params['type_ids'] = $type_ids;
                }
            }
        }

        switch ($sitemap_tag_selection) {
            case 'all':
                $sql = 'SELECT
                            t.id,
                            IF(tt.url IS NOT NULL AND LENGTH(tt.url) > 0, tt.url, t.name) as url,
                            NOW() as lastmod
                        FROM shop_tag t
                        LEFT JOIN shop_tageditor_tag tt
                            ON tt.id = t.id
                        WHERE t.count > 0';
                break;
            case 'cloud_index':
                $end = $check_types ?
                    'JOIN shop_tageditor_index_tag tit
                        ON tit.tag_id = t.id
                            AND tit.type_id IN (i:type_ids)
                    GROUP BY t.id' :
                    'WHERE t.count > 0';
                $sql = "SELECT
                            t.id,
                            IF(tt.url IS NOT NULL AND LENGTH(tt.url) > 0, tt.url, t.name) as url,
                            NOW() as lastmod
                        FROM shop_tag t
                        LEFT JOIN shop_tageditor_tag tt
                            ON tt.id = t.id
                        {$end}";
                break;
            case 'products_update_time':
                $type_check_condition = $check_types ? 'AND p.type_id IN (i:type_ids)' : '';
                $sql = "SELECT
                            t.id,
                            IF(tt.url IS NOT NULL AND LENGTH(tt.url) > 0, tt.url, t.name) as url,
                            GREATEST(
                                IFNULL(tt.edit_datetime, 0),
                                IFNULL(MAX(p.edit_datetime), MAX(p.create_datetime))
                            ) as lastmod
                        FROM shop_tag t
                        LEFT JOIN shop_tageditor_tag tt
                            ON tt.id = t.id
                        JOIN shop_product_tags pt
                            ON pt.tag_id = t.id
                        JOIN shop_product p
                            ON p.id = pt.product_id
                                AND p.status <> 0
                                {$type_check_condition}
                        GROUP BY t.id";
                break;
        }

        $model = new waModel();
        return $model->query($sql, $params)->fetchAll('id', true);
    }
}
