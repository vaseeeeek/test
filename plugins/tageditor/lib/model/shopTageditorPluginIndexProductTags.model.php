<?php

class shopTageditorPluginIndexProductTagsModel extends waModel
{
    protected $table = 'shop_tageditor_index_product_tags';
    protected $id = array('product_id', 'tag_id');
}
