<?php

class shopSkoneclickValidate{

    public static function validateForm($data, &$errors = array()){

        $fieldsData = shopSkoneclickData::getDataContactByForm();
        $errors = array();

        $i = 0;

        foreach($data as $key => $item){
            if($key == "address" && is_array($item)){
                foreach($item as $name => $field){
                    if(isset($fieldsData["address"]["fields"][$name]["required"]) && !empty($fieldsData["address"]["fields"][$name]["required"]) && !trim($field)){
                        $errors[$i] = "Обязательно для заполнения";
                    }
                    $i++;
                }
            }elseif(is_string($item)){
                if(isset($fieldsData[$key]["required"])){
                    if(!empty($fieldsData[$key]["required"]) && !trim($item)){
                        $errors[$i] = "Обязательно для заполнения";
                    }
                    if($fieldsData[$key]["class"] == "waContactEmailField" && $item){
                        $emails = array();
                        if(wa()->getUser()->isAuth()){
                            $emailsCurrent = wa()->getUser()->get("email");
                            if(!empty($emailsCurrent)){
                                foreach($emailsCurrent as $email){
                                    $emails[] = trim($email["value"]);
                                }
                            }
                        }
                        $emailValidator = new waEmailValidator();
                        if(!$emailValidator->isValid($item)){
                            $errors[$i] = $emailValidator->getErrors();
                        }

                        $contactModel = new waContactModel();
                        if($contactModel->getByEmail($item, true) && array_search(trim($item), $emails) === false){
                            $errors[$i] = "Email уже зарегестрирован";
                        }
                    }
                }
                $i++;
            }else{
                $i++;
            }

        }

        if(!empty($errors)){
            return false;
        }

        return true;

    }

}