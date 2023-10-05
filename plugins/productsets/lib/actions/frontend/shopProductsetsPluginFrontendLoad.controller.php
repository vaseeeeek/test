<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginFrontendLoadController extends shopProductsetsPluginJsonController
{
    public function execute()
    {
        $product_id = waRequest::post('product_id', null, waRequest::TYPE_INT);
        $set_id = waRequest::post('set_id', 0, waRequest::TYPE_INT);
        $category_id = waRequest::post('category_id', 0, waRequest::TYPE_INT);

        waRequest::setParam('productsets_hide_title', 1);
        $this->response = (new shopProductsetsPluginDisplay())->show($product_id, [
            'type' =>'userbundle',
            'category' => $category_id,
            'show_userbundle_form' => 1,
            'show_before_after_html' => false,
            'set_id' => $set_id
        ]);
    }
}