<?php

class shopSkoneclickData{

    public static function getCheckoutControls(){

        $controls = array();
        $controls_checkout = wa('shop')->getConfig()->getCheckoutSettings();
        $controls_checkout = $controls_checkout["contactinfo"]["fields"];
        $controls_all = waContactFields::getAll();

        foreach($controls_checkout as $key => $control){
            $control["is_mask"] = false;
            if(isset($controls_all[$key]) && is_object($controls_all[$key])){
                $object = $controls_all[$key];
                $control["class"] = get_class($object);
                if(is_a($object, "waContactPhoneField")){
                    $control["is_mask"] = true;
                }
                if(!isset($control["localized_names"])){
                    $control["localized_names"] = $object->getName();
                }
                if(is_a($object, "waContactAddressField") && isset($control["fields"]) && !empty($control["fields"])){
                    foreach($control["fields"] as $k => $field){
                        $objectAddress = $controls_all["address"];
                        $fieldsAddress = $objectAddress->getFields();
                        if(!isset($fieldsAddress[$k])){
                            continue;
                        }
                        $objectField = $fieldsAddress[$k];
                        $field["class"] = $control["class"];
                        $field["is_mask"] = false;
                        if(!isset($field["localized_names"])){
                            $field["localized_names"] = $objectField->getName();
                        }
                        $controls[$k] = $field;
                    }
                }else{
                    $controls[$key] = $control;
                }

            }
        }

        return $controls;

    }

    public static function initDefaultData(){

        $checkout_controls = shopSkoneclickData::getCheckoutControls();
        $controlsModel = new shopSkoneclickControlsModel();
        $iteration = 0;

        $controlsModel->truncate();

        foreach($checkout_controls as $name => $item){

            if($item["class"] == "waContactAddressField"){
                continue;
            }

            $data = array(
                "control_id" => $name,
                "title" => $item["localized_names"],
                "class" => $item["class"],
                "is_mask" => $item["is_mask"],
                "mask" => $item["is_mask"] ? "+7(###)###-##-##" : "",
                "require" => $item["class"] == "waContactPhoneField" ? 1 : 0,
                "sort" => $iteration
            );

            $controlsModel->replace($data);

            $iteration++;
        }

    }

    public static function getDataContactByForm(){

        $controlsModel = new shopSkoneclickControlsModel();
        $controlsData = $controlsModel->getControlsByForm();

        if(wa()->getUser()->isAuth()){
            $contact = wa()->getUser();
            foreach($controlsData as $name => $control){
                $field = $contact->get($name);
                if(isset($field) && !empty($field)){
                    if(is_array($field) && $name == "address"){
                        if(isset($field[0]["data"])){
                            foreach($field[0]["data"] as $n => $value){
                                if(isset($controlsData[$name]["fields"][$n])){
                                    $controlsData[$name]["fields"][$n]["value"] = $value;
                                }
                            }
                            break;
                        }
                    }elseif(is_array($field)){
                        if(!isset($field[0]["value"]["data"])){
                            $controlsData[$name]["value"] = $field[0]["value"];
                        }
                    }elseif(is_string($field)){
                        $controlsData[$name]["value"] = $field;
                    }

                }
            }
        }

        return $controlsData;

    }

}