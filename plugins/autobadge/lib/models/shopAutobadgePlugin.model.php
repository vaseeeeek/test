<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginModel extends waModel
{

    protected $table = 'shop_autobadge';

    /**
     * Get all filters
     * 
     * @param array $filter
     * @return array
     */
    public function getFilters($filter = array())
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1";
        if (wa('shop')->getEnv() == 'frontend') {
            $sql .= " AND status = '1'";
        }
        $sql .= " ORDER BY sort " . (wa('shop')->getEnv() == 'frontend' ? 'DESC' : 'ASC');
        // Выбираем определенное количество записей
        if (!empty($filter['limit'])) {
            $sql .= " LIMIT " . (int) $filter['limit']['offset'] . "," . (int) $filter['limit']['length'];
        }
        $result = $this->query($sql)->fetchAll('id');
        if ($result) {
            $params_model = new shopAutobadgeParamsPluginModel();
            $params = $params_model->getParams(array_keys($result));
            foreach ($result as &$r) {
                $r['params'] = !empty($params[$r['id']]) ? $params[$r['id']] : array();
            }
        }

        return $result;
    }

    /**
     * Get one filter
     * 
     * @param int $id
     * @return array|null
     */
    public function getFilter($id)
    {
        $filter = array();
        if ($id) {
            $sql = "SELECT * FROM {$this->table} WHERE id = '" . (int) $id . "'";
            $filter = $this->query($sql)->fetchAssoc();
            if ($filter) {
                $params_model = new shopAutobadgeParamsPluginModel();
                $params = $params_model->getParams($id);
                $filter += $params;
            }
        }

        return $filter;
    }

    /**
     * Maximum sort number
     * 
     * @return int
     */
    public function getMaxSort()
    {
        return (int) $this->select("MAX(sort)")->fetchField();
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

        $params_model = new shopAutobadgeParamsPluginModel();

        $sql = "DELETE a, p FROM {$this->table} a "
                . "LEFT JOIN {$params_model->getTableName()} p ON p.autobadge_id = a.id "
                . "WHERE a.id $where";
        return $this->exec($sql);
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
            $sort = $filter['sort'];

            // Основные данные
            unset($filter['id']);
            $filter['status'] = 0;
            $filter['sort'] ++;
            $clone_id = $this->insert($filter);
            // Изменяем сортировку фильтров
            $this->exec("UPDATE {$this->table} SET sort = sort + 1 WHERE sort > '" . (int) $sort . "' AND id <> '" . (int) $clone_id . "'");

            // Параметры
            $sapm = new shopAutobadgeParamsPluginModel();
            $params = $sapm->getParams($id);
            if ($params) {
                $sapm->add($clone_id, $params);
            }
            return $clone_id;
        }
        return 0;
    }

}
