<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsBundlePluginModel extends shopProductsetsModel
{

    protected $table = 'shop_productsets_bundle';

    public function __construct()
    {
        $this->item_model = new shopProductsetsBundleItemPluginModel();
        parent::__construct();
    }

    /**
     * Delete bundle by productset ID
     *
     * @param int $id
     * @return bool|resource
     */
    public function deleteBySetId($id)
    {
        return $this->deleteBySpecifiedField($id, 'productsets_id', $this->item_model, 'bundle_id');
    }

    /**
     * Delete bundle by productset ID
     *
     * @param int $id
     * @return bool|resource
     */
    public function deleteByBundleId($id)
    {
        return $this->deleteBySpecifiedField($id, 'id', $this->item_model, 'bundle_id');
    }

    /**
     * Delete all unneccesary bundles
     *
     * @param int $set_id
     */
    public function clean($set_id)
    {
        // Все имеющиеся записи
        $ids = array_keys($this->getByField('productsets_id', $set_id, 'id'));
        $saved_ids = ifset($this->saved_ids[$this->getTableName()], []);
        // Удаляем ненужные записи
        if ($delete_ids = array_diff($ids, $saved_ids)) {
            foreach ($delete_ids as $id) {
                $this->deleteByBundleId($id);
            }
        }
    }

}
