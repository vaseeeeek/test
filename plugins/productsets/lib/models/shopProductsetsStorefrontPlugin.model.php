<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsStorefrontPluginModel extends waModel
{

    protected $table = 'shop_productsets_storefront';

    /**
     * @param int $id
     * @param array $storefronts
     * @param string $operator
     */
    public function save($id, $storefronts, $operator = 'eq')
    {
        // Очищаем значения для комплекта
        $this->deleteByField('productsets_id', $id);
        if ($storefronts && !isset($storefronts['all']) && !in_array('all', $storefronts)) {
            $data = array();
            foreach ($storefronts as $storefront) {
                $data[] = array('productsets_id' => $id, 'operator' => $operator, 'storefront' => $storefront);
            }
            $this->multipleInsert($data);
        }
    }
}
