<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsUserbundlePluginModel extends shopProductsetsModel
{

    protected $table = 'shop_productsets_userbundle';

    public function __construct()
    {
        $this->bundle_key = 'userbundle_id';
        $this->item_model = new shopProductsetsUserbundleItemPluginModel();
        parent::__construct();
    }

    /**
     * Delete all
     *
     * @param int $id
     * @return bool|resource
     */
    public function deleteBySetId($id)
    {
        $puim = new shopProductsetsUserbundleItemPluginModel();
        $pugm = new shopProductsetsUserbundleGroupPluginModel();
        $pugim = new shopProductsetsUserbundleGroupItemPluginModel();
        $sql = "DELETE pu, puim, pugm, pugim FROM {$this->table} pu
                LEFT JOIN {$puim->getTableName()} puim ON pb.id = puim.bundle_id
                LEFT JOIN {$pugm->getTableName()} pugm ON pb.id = pugm.userbundle_id
                LEFT JOIN {$pugim->getTableName()} pugim ON pugm.id = pugim.group_id
                WHERE pb.productsets_id = '" . (int) $id . "'";
        return $this->exec($sql);
    }

    public function getUserItemsBySetId($set_id)
    {
        $data = array();
        $general = $this->getItemsBySetId($set_id);
        if ($general) {
            $general = reset($general);
            $data['id'] = $general['id'];
            $data['settings'] = $general['settings'];
            // Получаем группы
            $pugm = new shopProductsetsUserbundleGroupPluginModel();
            $data['groups'] = $pugm->getGroupsById($general['id']);

            // Получаем данные об активном товаре и обязательных
            $data['active'] = $data['required'] = array();
            if (!empty($general['items'])) {
                foreach ($general['items'] as $item_id => $item) {
                    // Обязательные товары
                    $data['required'][ $item_id] = $item;
                }
            }
            // Активный товар
            if (!empty($general['active'])) {
                $data['active'] = $general['active'];
                $data['active']['settings']['_id'] = $general['active']['_id'];
            }
        }
        return $data;
    }

}
