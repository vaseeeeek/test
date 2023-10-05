<?php

class shopTageditorPluginBackendAutocompleteController extends waController
{
    public function execute()
    {
        $query = waRequest::get('term', '', waRequest::TYPE_STRING_TRIM);
        $tags = shopTageditorPluginModels::shopTag()
            ->query('
                SELECT
                    st.id,
                    st.name as value,
                    IF(t.url IS NULL OR LENGTH(t.url) = 0, st.name, t.url) as url
                FROM shop_tag st
                LEFT JOIN shop_tageditor_tag t
                    ON t.id = st.id
                WHERE name like "%'.shopTageditorPluginModels::shopTag()->escape($query, 'like').'%"
            ')
            ->fetchAll();

        echo json_encode($tags);
    }
}
