<?php

class shopProductsDeleteFromCategoryController extends waJsonController
{
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'setscategories')) {
            throw new waRightsException(_w('Access denied'));
        }

        $model = new shopCategoryProductsModel();
        if (waRequest::post('hash', '')) {
            $model->clearCategory(waRequest::get('id'));
        } else {
            $model->deleteProducts(
                waRequest::get('id'),
                waRequest::post('product_id', array(), waRequest::TYPE_ARRAY_INT)
            );
        }
    }
}
