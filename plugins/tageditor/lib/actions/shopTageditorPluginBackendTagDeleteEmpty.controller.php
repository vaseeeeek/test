<?php

class shopTageditorPluginBackendTagDeleteEmptyController extends waController
{
    public function execute()
    {
        $empty_tags = shopTageditorPluginModels::shopTag()
            ->select('id')
            ->where('count = 0')
            ->fetchAll(null, true);

        if ($empty_tags) {
            shopTageditorPluginModels::shopTag()->deleteByField(array(
                'count' => 0,
            ));

            shopTageditorPluginModels::tag()->deleteByField(array(
                'id' => $empty_tags,
            ));
        }
    }
}
