<?php
class shopFrequent_questionsPluginModel extends waModel
{
    protected $table = 'shop_frequent_questions';
    
    public function fqTableErase () 
    {
        return $this->query("DELETE FROM ".$this->table);
    }
}