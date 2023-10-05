<?php

class shopSkoneclickContactsForm extends waContactForm{

    /* Прототип shopContactForm */

    protected $antispam;
    protected $antispam_captcha;

    public function __construct($fields = array(), $options = array()){
        parent::__construct($fields, $options);
        $this->antispam = false;
        $this->antispam_captcha = false;
    }

    public function html($field_id = null, $with_errors = true, $placeholders = false){
        $html = parent::html($field_id, $with_errors, $placeholders);
        if(!$field_id && $this->antispam){
            if($this->antispam_captcha){
                $html .= '<div class="wa-field"><div class="wa-value">';
                $html .= wa('shop')->getCaptcha()->getHtml(ifset($this->errors['captcha']));
                if(isset($this->errors['captcha'])){
                    $html .= '<em class="wa-error-msg">' . $this->errors['captcha'] . '</em>';
                }
                $html .= '</div></div>';
            }else{
                $code = waString::uuid();
                wa()->getStorage()->set('shop/checkout_code', $code);
                $html .= '<input type="hidden" name="checkout_code" value="">';
                $html .= <<<HTML
<script type="text/javascript">$('input[name="checkout_code"]').val("{$code}");</script>
HTML;
            }
            $html .= '<input type="text" style="display: none" name="address" value=" ">';
        }
        return $html;
    }

    public function isValidAntispam(){
        if($this->antispam){
            if(waRequest::method() == 'post'){
                $is_spam = false;
                if($this->antispam_captcha){
                    if(!wa('shop')->getCaptcha()->isValid(null, $error)){
                        $this->errors['captcha'] = $error ? $error : _ws('Invalid captcha');
                    }
                }else{
                    $checkout_code = waRequest::post('checkout_code');
                    if(!$checkout_code || ($checkout_code !== wa()->getStorage()->get('shop/checkout_code'))){
                        $is_spam = true;
                    }
                }
                $check_address = waRequest::post('address');
                if($check_address !== ' '){
                    $is_spam = true;
                }
                if($is_spam){
                    $this->errors['spam'] = _w('Something went wrong while processing your data. Please contact store team directly regarding your order. A notification about this error has been sent to the store admin.');
                }
            }
            return empty($this->errors['spam']) && empty($this->errors['captcha']);
        }
        return true;
    }

    public static function loadConfig($params, $options = array()){
        $config = self::readConfig($params);
        $form = new self($config['fields'], $options);
        $form->setValue($config['values']);
        return $form;
    }

    protected static function readConfig($params){

        if(is_array($params)){
            $fields_config = $params;
        }

        $fields = array();
        $values = array(); // hidden field values known beforehand
        foreach($fields_config as $full_field_id => $opts){
            if($opts instanceof waContactField){
                $f = clone $opts;
            }elseif(is_array($opts)){
                // Allow to specify something like 'phone.home' as field_id in config file.
                $fid = explode('.', $full_field_id, 2);
                $fid = $fid[0];

                $f = waContactFields::get($fid);
                if(!$f){
                    waLog::log('ContactField ' . $fid . ' not found.');
                    continue;
                }else{
                    // Prepare fields parameter for composite field
                    if($f instanceof waContactCompositeField && !empty($opts['fields'])){
                        if(!is_array($opts['fields'])){
                            unset($opts['fields']);
                        }else{
                            $old_subfields = $f->getFields();
                            $subfields = array();
                            foreach($opts['fields'] as $sfid => $sfopts){
                                if(empty($old_subfields[$sfid])){
                                    waLog::log('Field ' . $fid . ':' . $sfid . ' not found and is ignored in ' . (is_array($params) ? 'config' : $params));
                                    continue;
                                }
                                $subfields[$sfid] = self::getClone($old_subfields[$sfid], $sfopts);
                                if($subfields[$sfid] instanceof waContactHiddenField){
                                    if(empty($values[$full_field_id]['data'])){
                                        $values[$full_field_id] = array('data' => array());
                                    }
                                    $values[$full_field_id]['data'][$sfid] = $subfields[$sfid]->getParameter('value');
                                }
                            }

                            $opts['fields'] = $subfields;
                        }
                    }

                    $f = self::getClone($f, $opts);
                    if($f instanceof waContactHiddenField){
                        $values[$full_field_id] = $f->getParameter('value');
                    }
                }
            }else{
                waLog::log('Field ' . $full_field_id . ' has incorrect format and is ignored in ' . $params);
                continue;
            }

            $fields[$full_field_id] = $f;
        }
        return array(
            'fields' => $fields,
            'values' => $values
        );
    }
}