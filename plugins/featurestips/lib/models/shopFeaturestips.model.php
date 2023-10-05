<?php

class shopFeaturestipsModel extends waModel
{
    protected $table = 'shop_featurestips_tips';

    public function getTipsByTypeId($type_id)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE type_id = ".(int)$type_id." OR type_id = 0 OR type_id IS null";

        return $this->query($sql)->fetchAll();
    }

    public function getTipsGlobal()
    {
        $sql = "SELECT * FROM ".$this->table." WHERE global = 1";

        return $this->query($sql)->fetchAll();
    }
}