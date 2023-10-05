<?php

class shopArrivedPluginSettingsSaveController extends waJsonController
{

    public function execute()
    {
        $settings =  array();
        $errors =  array();
        $email = trim(waRequest::post('email'));
        $expiration = waRequest::post('expiration');
        $settings['sms_sender_id'] = waRequest::post('sms_sender_id');
		$mail_subject = trim(waRequest::post('mail_subject'));
		$plink_title = trim(waRequest::post('plink_title'));
		$clink_title = trim(waRequest::post('clink_title'));
		$popup_success = trim(waRequest::post('popup_success'));
		$templateMail = trim(waRequest::post('templateMail'));
		$templateSMS = trim(waRequest::post('templateSMS'));
		$settings['admin_email'] = trim(waRequest::post('admin_email'));

        $settings['send_type'] = waRequest::post('send_type');

        if(waRequest::post('set_expiration')) {
			if($expiration!="") {
				$expiration = explode(",",$expiration);
				$row = array();
				foreach($expiration as $i) {
					$i=trim($i);
					if($i!="") {
						$row[] = (int)$i;
					}
				}
				$settings['expiration'] = implode(",",$row);
			} else {
				$settings['expiration'] = "";
			}
		} else {
			$settings['expiration'] = "";
		}
		$validator = new waEmailValidator();
		if($email!="") {
			if($validator->isValid($email)) {
				$settings['email'] = $email;
			} else {
				$errors[] = '"'.$email.'" - некорректный email';
			}
		} else if($email=="" && $settings['send_type']!="sms") {
			$errors[] = 'Не указан email отправителя';
		}
		if($settings['admin_email']!="") {
			if(!$validator->isValid($settings['admin_email'])) {
				$errors[] = '"'.$settings["admin_email"].'" - email администратора указан некорректно';
			}
		}
		if($plink_title!="") {
			$settings['plink_title'] = $plink_title;
		} else {
			$errors[] = 'Текст ссылки на странице продукта не может быть пустым';
		}
		if($clink_title!="") {
			$settings['clink_title'] = $clink_title;
		} else {
			$errors[] = 'Текст ссылки в каталоге не может быть пустым';
		}
		if($popup_success!="") {
			$settings['popup_success'] = $popup_success;
		} else {
			$errors[] = 'Текст уведомления об успешно принятой заявке не может быть пустым';
		}
		if($settings['send_type']=="sms") {
			$settings['templateMail'] = $templateMail;
		} else {
			if($templateMail!="") {
				$settings['templateMail'] = $templateMail;
			} else {
				$errors[] = 'Шаблон E-Mail сообщения пуст';
			}
		}
		if($settings['send_type']=="email") {
			$settings['templateSMS'] = $templateSMS;
		} else {
			if($templateSMS!="") {
				$settings['templateSMS'] = $templateSMS;
			} else {
				$errors[] = 'Шаблон E-Mail сообщения пуст';
			}
		}
		if($settings['send_type']!="email") {
			foreach($settings['sms_sender_id'] as $domain=>$sid) {
				if($sid!="" && (strlen($sid)<3 || strlen($sid)>11))
					$errors[] = 'Некорректно указан отправитель SMS для домена '.$domain;
			}
		}
		$settings['mail_subject'] = $mail_subject;
		$settings['popup_title'] = trim(waRequest::post('popup_title'));
		$settings['terms_url'] = trim(waRequest::post('terms_url'));
		$settings['enable_hook'] = waRequest::post('enable_hook')==1 ? 1 : 0;
		// save main settings
		if(empty($errors)) {
			$config_settings_file = shopArrivedPlugin::path('config.php');
			if (!waUtils::varExportToFile($settings, $config_settings_file)) {
				$this->errors['messages'][] = "Поля настроек сохранить не удалось";
			}
        } else {
			foreach($errors as $error) {
				$this->errors['messages'][] = $error;
			}
		}
    }

}