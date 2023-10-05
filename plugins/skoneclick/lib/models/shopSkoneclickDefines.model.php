<?php

class shopSkoneclickDefinesModel extends waModel{

    protected $table = 'shop_skoneclick_defines';

    public function getDefines(){

        $definesArray = $this->getAll();
        $defines = array();

        foreach($definesArray as $data){
            $defines[$data["name"]] = $data["value"];
        }

        return $defines;

    }

    public function initActive(){

        $this->updateByField("name", "init", array("value" => 1));

    }

}
