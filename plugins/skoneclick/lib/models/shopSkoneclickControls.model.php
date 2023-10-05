<?php

class shopSkoneclickControlsModel extends waModel{

    protected $table = 'shop_skoneclick_controls';

    public function getControls(){

        $data = $this->query("SELECT t1.* FROM shop_skoneclick_controls t1 ORDER BY t1.sort ASC")->fetchAll();

        $result = array();
        foreach($data as $item){
            $result[$item['control_id']] = $item;
        }

        return $result;

    }

    public function getControlsByForm(){

        $data = $this->getControls();

        $result = array();
        foreach($data as $item){
            if($item['class'] == "waContactAddressField"){
                $result["address"]["fields"][$item["control_id"]] = array(
                    "localized_names" => $item["title"],
                    "required" => !!$item["require"]
                );
            }else{
                $result[$item['control_id']] = array(
                    "localized_names" => $item["title"],
                    "required" => !!$item["require"]
                );
                if($item['class'] == "waContactPhoneField" && $item["is_mask"] && $item["mask"]){
                    $result[$item['control_id']]["mask"] = $item["mask"];
                }
            }
            $result[$item['control_id']]["class"] = $item['class'];
        }

        return $result;
    }

}
