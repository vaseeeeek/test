<?php

class shopSkoneclickPluginBackendFormSaveController extends waJsonController{

    public function execute(){

        $post = waRequest::post();
        $errors = array();

        if(isset($post["shop_skoneclick"]) && !empty($post["shop_skoneclick"])){
            wa("shop")->getPlugin("skoneclick")->saveSettings($post["shop_skoneclick"]);
        }

        if(isset($post["shop_skoneclick_defines"]) && !empty($post["shop_skoneclick_defines"])){
            $definesModel = new shopSkoneclickDefinesModel();
            foreach($post["shop_skoneclick_defines"] as $name => $value){
                $definesModel->replace(array("name" => $name, "value" => $value));
            }
        }

        if(isset($post["shop_skoneclick_fields"]) && !empty($post["shop_skoneclick_fields"])){

            $sort = 0;
            $dataControls = new shopSkoneclickControlsModel();

            foreach($post["shop_skoneclick_fields"] as $control_id => $field){
                $field["title"] = trim($field["title"]);
                if(!$field["title"]){
                    $errors["text"] = "Ошибки в редакторе полей формы";
                    $errors["fields"][$control_id] = "Задан пустой загаловок";
                    continue;
                }
                if(!isset($field["additional"])){
                    $field["additional"] = "";
                }elseif(is_array($field["additional"])){
                    $field["additional"] = implode(",", $field["additional"]);
                }
                if(!isset($field["mask"])){
                    $field["mask"] = "";
                    $field["is_mask"] = 0;
                }else{
                    $field["is_mask"] = 1;
                }

                if(!isset($field["require"])){
                    $field["require"] = 0;
                }
                $field["require"] = (int)$field["require"];

                $data = array(
                    "control_id" => $control_id,
                    "title" => $field["title"],
                    "class" => $field["class"],
                    "additional" => $field["additional"],
                    "is_mask" => $field["is_mask"],
                    "mask" => $field["mask"],
                    "require" => $field["require"],
                    "sort" => $sort,
                );

                $dataControls->replace($data);

                $sort++;
            }
        }

        if(!empty($errors)){
            $this->errors = $errors;
            return false;
        }

        return true;

    }


}