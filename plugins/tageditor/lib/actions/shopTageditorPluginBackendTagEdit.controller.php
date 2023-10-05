<?php

class shopTageditorPluginBackendTagEditController extends waJsonController
{
    public function execute()
    {
        $tag_id = waRequest::post('id', null, waRequest::TYPE_INT);
        $name = waRequest::post('name');
        $data = waRequest::post('data', array(), waRequest::TYPE_ARRAY_TRIM);
        $default_values = waRequest::post('default_values', array(), waRequest::TYPE_ARRAY_TRIM);
        $use_field_aliases = waRequest::post('use_field_alias', array(), waRequest::TYPE_ARRAY_TRIM);

        try {
            if (!shopTageditorPluginModels::shopTag()->getById($tag_id)) {
                throw new Exception(_wp('This tag does not exist any more. <span class="reload">Reload tag list</span>.'));
            }

            $tag_exists = shopTageditorPluginModels::shopTag()->
                select('COUNT(*) > 0')->
                where('id <> i:id AND name = s:name', array(
                    'id'   => $tag_id,
                    'name' => $name,
                ))->
                fetchField();

            if ($tag_exists) {
                throw new Exception(_wp('This tag already exists.'));
            }

            if (!$data['sort_products']) {
                $data['sort_products'] = null;
            }

            $url = strlen($data['url']) ? $data['url'] : $name;
            $same_url = shopTageditorPluginModels::tag()->getByUrl($url);

            if ($same_url && ifset($same_url['tag_id']) != $tag_id) {
                throw new Exception(sprintf(
                    _wp('There is already another tag (<strong>%s</strong>) accessible at the same URL <strong>%s</strong>.'),
                    $same_url['name'],
                    $url
                ));
            }

            //update tag name
            shopTageditorPluginModels::shopTag()->updateById($tag_id, array(
                'name' => $name
            ));

            //use other fields' values
            $use_field_aliases = array_filter($use_field_aliases, 'strlen');

            if ($use_field_aliases) {
                foreach ($use_field_aliases as $alias_field => $alias) {
                    $data[$alias_field] = "field_alias:{$alias}";
                }
            }

            //save tag data
            $data['id'] = $tag_id;
            $data['edit_datetime'] = date('Y-m-d H:i:s');

            shopTageditorPluginModels::tag()->insert($data, 1);

            //save & apply default values
            if (wa()->getUser()->getRights('shop', 'tageditor_save_default_values')) {
                foreach (array_keys($default_values) as $field) {
                    $default_values[$field] = $data[$field];
                }
                if ($default_values) {
                    shopTageditorPluginModels::tag()->updateAll($default_values);
                    shopTageditorPluginHelper::saveDefaultValues($default_values);
                }
            }

            $this->response['url'] = urlencode($name);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
}
