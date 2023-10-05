<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPluginModel extends waModel
{

    protected $table = 'shop_delpayfilter';

    /**
     * Get all filters
     * 
     * @param array $filter
     * @return array
     */
    public function getFilters($filter = array())
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1 ORDER BY id ASC";
        // Выбираем определенное количество записей
        if (!empty($filter['limit'])) {
            $sql .= " LIMIT " . (int) $filter['limit']['offset'] . "," . (int) $filter['limit']['length'];
        }
        $result = $this->query($sql)->fetchAll('id');

        return $result;
    }

    /**
     * Get one filter
     * 
     * @param int $id
     * @return aray
     */
    public function getFilter($id)
    {
        $filter = array();
        if ($id) {
            $sql = "SELECT * FROM {$this->table} WHERE id = '" . (int) $id . "'";
            $filter = $this->query($sql)->fetchAssoc();
        }

        return $filter;
    }

    /**
     * Duplicate
     * 
     * @param int $id
     * @return int
     */
    public function duplicate($id)
    {
        $filter = $this->getById($id);
        if ($filter) {
            // Основные данные
            unset($filter['id']);
            $filter['status'] = 0;
            $clone_id = $this->insert($filter);
            return $clone_id;
        }
        return 0;
    }

    /**
     * Delete filters
     * 
     * @param array|int $ids
     * @return boolean
     */
    public function delete($ids)
    {
        $where = "";
        if (is_array($ids)) {
            $where .= " IN ('" . implode("','", $this->escape($ids, 'int')) . "')";
        } else {
            $where .= " = '" . (int) $ids . "'";
        }

        $sql = "DELETE f FROM {$this->table} f WHERE f.id $where";
        return $this->exec($sql);
    }

}
