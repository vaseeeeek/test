<?php

class shopTageditorPluginBackendTagDeleteController extends waJsonController
{
    public function execute()
    {
        $tag_id = waRequest::post('id');

        if ($tag_id == 'all') {
            if (wa()->getUser()->isAdmin('shop')) {
                shopTageditorPluginModels::shopTag()->truncate();
                shopTageditorPluginModels::shopProductTags()->truncate();
                shopTageditorPluginModels::tag()->truncate();
            } else {
                $this->errors[] = _wp('Only an administrator is allowed to delete all tags.');
            }
        } else {
            $tag_id = (int) $tag_id;
            if ($tag_id) {
                shopTageditorPluginModels::shopTag()->deleteById($tag_id);
                shopTageditorPluginModels::shopProductTags()->deleteByField('tag_id', $tag_id);
                shopTageditorPluginModels::tag()->deleteById($tag_id);

                //One tag above limit has just been deleted so we can show all tags now
                if (shopTageditorPluginModels::shopTag()->countAll() == shopTageditorPlugin::BACKEND_TAG_LIMIT) {
                    $this->response['tags'] = wao(new shopTageditorPluginBackendAction(array('is_ajax' => true)))->display(false);
                }
            }
        }
    }
}
