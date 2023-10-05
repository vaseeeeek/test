<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountGroupPluginModel extends waModel
{

    protected $table = 'shop_flexdiscount_group';

    /**
     * Create group
     * 
     * @return int
     */
    public function create()
    {
        return $this->insert(array("name" => _wp("Group name"), "combine" => "max"));
    }

    /**
     * Get groups with discount ids
     * 
     * @return array
     */
    public function getGroups()
    {
        $group_model = new shopFlexdiscountGroupDiscountPluginModel();
        $groups = $this->getAll('id');
        $fl_ids = $group_model->getAll('group_id', 2);
        foreach ($groups as &$g) {
            $g['items'] = !empty($fl_ids[$g['id']]) ? $fl_ids[$g['id']] : array();
        }
        return $groups;
    }

    /**
     * Delete discount group
     * 
     * @param int $group_id
     * @return bool
     */
    public function delete($group_id)
    {
        $group_model = new shopFlexdiscountGroupDiscountPluginModel();
        $sql = "DELETE g, gd FROM {$this->table} g "
                . "LEFT JOIN {$group_model->getTableName()} gd ON g.id = gd.group_id "
                . "WHERE g.id = '" . (int) $group_id . "'";
        return $this->exec($sql);
    }

}
