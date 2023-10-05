<?php

class shopEmailformPluginModel extends waModel
{
    protected $table = 'shop_emailform_emails';

    public function deleteAll()
    {
        return $this->query("DELETE FROM {$this->table}");
    }
}
