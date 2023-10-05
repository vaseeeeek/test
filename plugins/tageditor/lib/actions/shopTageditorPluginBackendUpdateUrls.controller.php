<?php

class shopTageditorPluginBackendUpdateUrlsController extends waController
{
    private $tag_model;

    public function __construct()
    {
        $this->tag_model = shopTageditorPluginModels::tag();
    }

    public function execute()
    {
        if (!$this->getUser()->isAdmin('shop')) {
            return;
        }

        $ignore_custom_urls = (bool) waRequest::post('ignore_custom_urls', 0, waRequest::TYPE_INT);

        $shop_tags = shopTageditorPluginModels::shopTag()->select('id, name')->fetchAll('id', true);
        $plugin_tags = $this->tag_model->select('id, url, edit_datetime')->fetchAll('id');

        $tag_urls = array();
        $edit_datetime = date('Y-m-d H:i:s');

        if ($plugin_tags) {
            foreach ($plugin_tags as &$plugin_tag) {
                if ($ignore_custom_urls) {
                    $plugin_tag['url'] = '';
                    $plugin_tag['edit_datetime'] = $edit_datetime;
                }

                if (strlen($plugin_tag['url']) && !in_array($plugin_tag['url'], $tag_urls)) {
                    $tag_urls[] = $plugin_tag['url'];
                }
            }
            unset($plugin_tag);
        }

        foreach ($shop_tags as $tag_id => $tag_name) {
            if (!isset($plugin_tags[$tag_id])) {
                $plugin_tags[$tag_id] = array(
                    'id' => $tag_id,
                    'url' => '',
                    'edit_datetime' => $edit_datetime,
                );
            }

            if (empty($plugin_tags[$tag_id]['url'])) {
                $tag_url = $tag_url_value = shopHelper::transliterate($tag_name);
                while (in_array($tag_url_value, $tag_urls)) {
                    $counter = ifset($counter, 1);
                    $tag_url_value = $tag_url.'-'.($counter++);
                }
                $plugin_tags[$tag_id]['url'] = $tag_url_value;
                if (!in_array($tag_url_value, $tag_urls)) {
                    $tag_urls[] = $tag_url_value;
                }
            }
        }

        if ($plugin_tags) {
            $sqls = $this->getMultipleInsertSqls($plugin_tags);
            if ($sqls) {
                foreach ($sqls as $sql) {
                    $this->tag_model->exec($sql);
                }
            }
        }
    }

    /**
     * Generate multipleInsert()-like SQLs with 'ON DUPLICATE KEY UPDATE' clause
     */
    private function getMultipleInsertSqls($data)
    {
        //get fields and comma-separated values
        $values = array();
        foreach ($data as $tag) {
            $row_values = array();
            foreach ($tag as $field => $value) {
                if ($this->tag_model->fieldExists($field)) {
                    $row_values[$this->tag_model->escapeField($field)] = $this->tag_model->getFieldValue($field, $value);
                }
            }
            if (!isset($fields)) {
                $fields = array_keys($row_values);
            }
            $values[] = implode(',', $row_values);
        }

        //generate SQLs
        $start = 'INSERT INTO '.$this->tag_model->getTableName().' ('.implode(',', $fields).') VALUES';
        $on_duplicate_clause = $this->getOnDuplicateKeyUpdateClause($data);
        $max_allowed_packet = $this->getMaxAllowedPacketValue();

        $sqls = array();
        $sql = $start;
        foreach ($values as $entry) {
            $new_value = ($sql == $start ? '' : ',').'('.$entry.')';
            if (strlen($sql.$new_value.$on_duplicate_clause) > $max_allowed_packet) {
                //SQL is too long
                //ignore new value, accept previously added values, create a new SQL starting with new value, and continue
                $sqls[] = $sql.$on_duplicate_clause;
                $sql = $start.'('.$entry.')';
            } else {
                //SQL is not too long, accept it, and continue
                $sql .= $new_value;
            }
        }

        //accept remaining value
        $sqls[] = $sql.$on_duplicate_clause;

        return $sqls;
    }

    private function getOnDuplicateKeyUpdateClause($data)
    {
        $clause = ' ON DUPLICATE KEY UPDATE ';

        $values = array();
        foreach ($data as $i => $entry) {
            foreach ($entry as $field => $value) {
                if (is_array($this->tag_model->getTableId()) && in_array($field, $this->tag_model->getTableId())
                || $field == $this->tag_model->getTableId()) {
                    continue;
                }

                if ($this->tag_model->fieldExists($field)) {
                    $values[$i][$field] = $this->tag_model->getFieldValue($field, $value);
                }
            }
        }

        $comma = false;
        foreach (reset($values) as $field => $v) {
            if ($comma) {
                $clause .= ',';
            } else {
                $comma = true;
            }
            $field = $this->tag_model->escapeField($field);
            $clause .= $field." = VALUES(".$field.")";
        }

        return $clause;
    }

    private function getMaxAllowedPacketValue()
    {
        $row = $this->tag_model->query('SHOW VARIABLES LIKE "max_allowed_packet"')->fetchRow();
        if (is_array($row) && count($row)) {
            foreach ($row as $value) {
                if (wa_is_int($value) && (int) $value > 0) {
                    $result = $value;
                    break;
                }
            }
        }

        if (empty($result)) {
            $result = 1048576; //default limit of 1MB if we cannot get the real value
        }

        return $result;
    }
}
