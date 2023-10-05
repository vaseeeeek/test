<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginModelHelper extends waModel
{
    protected $alias;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Build SQL string from filter values
     *
     * @param array $filter
     * @param array $values
     * @return string
     */
    protected function addFilterValues($filter, $values)
    {
        $sql = '';
        foreach ($values as $key => $value) {
            $field_key = ifempty($value, 'key', $key);
            $not = ifset($value, 'not', false);
            $sql .= $value['func'] == 'isset' ? $this->addFilterIfIsset($filter, $key, $value['type'], $field_key, $not) : $this->addFilterIfEmpty($filter, $key, $value['type'], $field_key, $not);
        }
        return $sql;
    }

    /**
     * Check, if value isset in filter. Return SQL string with filter values
     *
     * @param array $filter - all available filters
     * @param string $key - key of the $filter
     * @param string $type - type of filter value: string|int
     * @param string $field_key - key of the table. By default is similar to $key
     * @param bool $not
     * @return string
     */
    protected function addFilterIfIsset($filter, $key, $type = 'string', $field_key = '', $not = false)
    {
        $sql = '';
        if (isset($filter[$key])) {
            $sql .= $this->toString($filter, $key, $type, $field_key, $not);
        }
        return $sql;
    }

    /**
     * Check, if value is not empty in filter. Return SQL string with filter values
     *
     * @param array $filter - all available filters
     * @param string $key - key of the $filter
     * @param string $type - type of filter value: string|int
     * @param string $field_key - key of the table. By default is similar to $key
     * @param bool $not
     * @return string
     */
    protected function addFilterIfEmpty($filter, $key, $type = 'string', $field_key = '', $not = false)
    {
        $sql = '';
        if (!empty($filter[$key])) {
            $sql .= $this->toString($filter, $key, $type, $field_key, $not);
        }
        return $sql;
    }

    /**
     * Return SQL string with limit
     *
     * @param array $filter
     * @return string
     */
    protected function addLimit($filter)
    {
        $sql = '';
        if (!empty($filter['limit'])) {
            $sql .= " LIMIT " . (int) $filter['limit']['offset'] . "," . (int) $filter['limit']['length'];
        }
        return $sql;
    }

    /**
     * Convert array to SQL string or return filter value
     *
     * @param array $filter
     * @param string $key
     * @param string $type
     * @param string $field_key
     * @param bool $not
     * @return string
     */
    private function toString($filter, $key, $type, $field_key = '', $not = false)
    {
        $sql = '';
        $field_key = $field_key === '' ? $this->escape($key) : $this->escape($field_key);
        $sql .= " AND {$this->alias}.{$field_key}";
        if (is_array($filter[$key])) {
            $sql .= ($not ? ' NOT' : '') . " IN ('" . implode("','", $this->escape($filter[$key], $type)) . "')";
        } else {
            $sql .= ($not ? ' <>' : ' =') . " '" . $this->escape($filter[$key], $type) . "'";
        }
        return $sql;
    }
}