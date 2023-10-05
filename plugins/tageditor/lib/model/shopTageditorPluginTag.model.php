<?php

class shopTageditorPluginTagModel extends waModel
{
    protected $table = 'shop_tageditor_tag';

    public function getByName($name)
    {
        $query = $this->query(
            'SELECT t.name, tt.*
            FROM shop_tageditor_tag tt
            JOIN shop_tag t
                ON tt.id = t.id
            WHERE t.name IN(s:name)',
            array(
                'name' => $name
            )
        );
        return is_array($name) ? $query->fetchAll('name') : $query->fetchAssoc();
    }

    public function getByUrl($url)
    {
        static $result = array();

        if (!isset($result[$url])) {
            $result[$url] = $this->query(
                'SELECT
                    t.id as tag_id,
                    t.name,
                    tt.*
                FROM shop_tag t
                LEFT JOIN shop_tageditor_tag tt
                    ON tt.id = t.id
                WHERE tt.url = s:0
                    OR t.name = s:0
                LIMIT 1',
                $url
            )->fetchAssoc();
        }

        return $result[$url];
    }

    public function getNameByCustomUrl($url)
    {
        return $this->query(
            'SELECT t.name
            FROM shop_tag t
            JOIN shop_tageditor_tag tt
                ON tt.id = t.id
            WHERE tt.url = s:0
            LIMIT 1',
            $url
        )->fetchField();
    }

    public function updateAll($data)
    {
        $data['edit_datetime'] = date('Y-m-d H:i:s');
        $values = array();
        foreach ($data as $field => $value) {
            $values[] = $this->escapeField($field).' = '.$this->getFieldValue($field, $value);
        }
        $this->exec('UPDATE '.$this->table.' SET '.implode(', ', $values));
    }

    public function getFieldValue($field, $value)
    {
        return parent::getFieldValue($field, $value);
    }

    public function escapeField($field)
    {
        return parent::escapeField($field);
    }
}
