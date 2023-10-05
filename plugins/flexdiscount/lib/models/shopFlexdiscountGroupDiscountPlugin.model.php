<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountGroupDiscountPluginModel extends waModel
{

    protected $table = 'shop_flexdiscount_group_discount';

    /**
     * Add discounts to group
     * 
     * @param int $group_id
     * @param array[int]|int $fl_id
     */
    public function add($group_id, $fl_id)
    {
        $data = array();
        if (is_array($fl_id)) {
            foreach ($fl_id as $id) {
                $data[] = array("group_id" => $group_id, "fl_id" => (int) $id);
            }
        } else {
            $data[] = array("group_id" => $group_id, "fl_id" => (int) $fl_id);
        }
        $this->multipleInsert($data);
    }

    /**
     * Delete discounts from group
     * 
     * @param int|null $group_id
     * @param array[int]|int $fl_id
     */
    public function del($group_id, $fl_id)
    {
        if ($group_id) {
            $this->deleteByField(array("group_id" => $group_id, "fl_id" => $fl_id));
        } else {
            $this->deleteByField(array("fl_id" => $fl_id));
        }
    }

}
