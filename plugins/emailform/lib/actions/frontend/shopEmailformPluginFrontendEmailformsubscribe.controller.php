<?php

class shopEmailformPluginFrontendEmailformsubscribeController extends waJsonController
{
	public $table = 'shop_coupon';
	
    /*
     * return form template
     * @return array
     */
    public function execute()
    {
        $ajax = waRequest::isXMLHttpRequest();
		$action = waRequest::get('action', '', 'string');
		$name = trim(waRequest::post('name', '', 'string'));
		$email = trim(waRequest::post('email', '', 'string'));
		$phone = trim(waRequest::post('phone', '', 'string'));
		$urlReferer = trim(waRequest::post('urlReferer', '', 'string'));
		$error = '';

        if ($ajax) 
		{
			if ($action == 'formshow')
			{
				$plugin = wa('shop')->getPlugin('emailform');
				$dont_show_urls = $plugin->getSettings('dont_show_urls');
				$cookie = (intval($plugin->getSettings('cookie')) > 0) ? intval($plugin->getSettings('cookie')) : 30;
				$show = 1;
				
				$urlArr = parse_url($urlReferer);
				$dontShowUrls = explode(PHP_EOL, $dont_show_urls);

				foreach ($dontShowUrls as $url) {
					$url = trim($url);
					if (!$url) continue;

					if (strpos($url, '*') !== false) {
						$maskUrl = str_replace('*', '(.)*', $url);

						if (preg_match("/{$maskUrl}/", $urlReferer)) {
							$show = 0;

							break;
						}						
					} else {
						if ($urlArr['path'] == $url) {
							$show = 0;

							break;							
						}
					}
				}

				if ($show) {
					wa()->getResponse()->setCookie('emailform_show', time(), time() + 3600 * 24 * $cookie, '/');
				}
				
				$this->response = array(
					'result' => array(
						'is' => 'success',
						'show' => $show,
					)
				);
			}
			elseif ($action == 'subscribe')
			{
				$emailValidate = filter_var($email, FILTER_VALIDATE_EMAIL);
				if (!$emailValidate AND $email) {
					$error = 'validation email error';
				}
				
				$pluginm = new shopEmailformPluginModel();
				$res = $pluginm->getByField('email', $email);
				
				if ($res['id'] > 0 AND $email) {
					$error = 'email already added';
				} 
				
				if (!$error) 
				{
					$id = $pluginm->insert(array(
						'email' => $email,
						'name' => $name,
						'phone' => $phone,
						'datetime' => date("Y-m-d H:i:s"),
					));
					
					if ($id > 0) {
						waLog::log('new contact add (' . $name . ' ' . $email . ' ' . $phone . ')', "emailform/emailform.log");
					}
					
					$plugin = wa('shop')->getPlugin('emailform');
					$type = intval($plugin->getSettings('type'));				//Предложить за подписку
					$title = $plugin->getSettings('title');						//Заголовок формы
					$text = $plugin->getSettings('text');						//Описание формы
					$templateForm2 = $plugin->getSettings('templateForm2');		//Шаблон формы после подписки 
					$templateEmail = $plugin->getSettings('templateEmail');		//Шаблон письма (покупателю) 
					
					$discount = '';
					$coupon_code = '';
					$coupon_hours  = '';
					$coupon_code_flexdiscount = array();
					
					if ($type == 1) { //автогенерация купона
						//автоудаление неиспользованных купонов
						$couponm = new shopCouponModel();
						$resultDel = $couponm->query("DELETE FROM " . $this->table . " WHERE `used` = 0 AND `expire_datetime` < NOW()");
						
						//добавление нового купона
						$resultAdd = $this->create_coupon();
						if ($resultAdd['is'] == 'success') {
							//$discount = ($resultAdd['type'] != '$FS') ? $resultAdd['value'] . $resultAdd['type'] : 'Бесплатная доставка';
							if ($resultAdd['type'] == '$FS')
								$discount = 'Бесплатная доставка';
							elseif ($resultAdd['type'] == 'RUB')
								$discount = $resultAdd['value'] . ' рублей';
							else
								$discount = $resultAdd['value'] . $resultAdd['type'];

							$coupon_code = $resultAdd['code'];
							$coupon_hours = $resultAdd['hours'];
						}
					}
					
					if ($type == 2) { //сгенерированный купон
						$discount = $plugin->getSettings('coupon_value2');
						$coupon_code = $plugin->getSettings('coupon_code2');
						$coupon_hours = intval($plugin->getSettings('coupon_hours2'));
					}

					if ($type == 5 && class_exists('shopFlexdiscountPluginHelper')) { //плагин "Гибкие скидки и бонусы"
						if (preg_match('/\{generateCoupon\((\d+)\)\}/', $templateForm2, $outInTemplate)) {
							if ($outInTemplate[1] > 0) {
								try {
								    $coupon_code_flexdiscount[$outInTemplate[1]] = shopFlexdiscountPluginHelper::generateCoupon($outInTemplate[1]);
								} catch (Exception $e) {
								    //
								}
								$templateForm2 = str_replace($outInTemplate[0], $coupon_code_flexdiscount[$outInTemplate[1]], $templateForm2);
							}
						}

						if (preg_match('/\{generateCoupon\((\d+)\)\}/', $templateEmail, $outInEmail)) {
							if ($outInEmail[1] > 0) {
								if (!isset($coupon_code_flexdiscount[$outInEmail[1]])) {
									// генерируем новый купон (если в шаблоне и письме разные ID, то будут разные купоны)
									try {
									    $coupon_code_flexdiscount[$outInEmail[1]] = shopFlexdiscountPluginHelper::generateCoupon($outInEmail[1]);
									} catch (Exception $e) {
									    //
									}
								}
								$templateEmail = str_replace($outInEmail[0], $coupon_code_flexdiscount[$outInEmail[1]], $templateEmail);
							}
						}
					}
					
					$templateForm2 = str_replace(
						array('{$title}', '{$text}', '{$discount}', '{$coupon}', '{$hours}'), 
						array($title, $text, $discount, $coupon_code, $coupon_hours), 
						$templateForm2
					);
					
					if ($plugin->getSettings('sendemail') AND $email) {
						$mail_subject = trim($plugin->getSettings('subject'));						//Заголовок письма
						$mail_from = trim($plugin->getSettings('mailfrom'));						//E-mail адрес отправителя
						$shop_name = trim(wa('shop')->getConfig()->getGeneralSettings('name'));		//название магазина (из настроек магазина)
						$shop_email = trim(wa('shop')->getConfig()->getGeneralSettings('email'));	//основной email-адрес (из настроек магазина)
						$shop_phone = trim(wa('shop')->getConfig()->getGeneralSettings('phone'));	//номер телефона (из настроек магазина)

						$templateEmail = str_replace(
							array('{$title}', '{$text}', '{$discount}', '{$coupon}', '{$hours}', '{$name}', '{$email}', '{$phone}'), 
							array($title, $text, $discount, $coupon_code, $coupon_hours, $shop_name, $shop_email, $shop_phone), 
							$templateEmail
						);
						
						$mail = new waMailMessage();
						$mail->setBody(nl2br($templateEmail));
						$mail->setSubject($mail_subject);

						if (!$mail_from) {
							$modelSetting = new waAppSettingsModel();
							$emailFrom = $modelSetting->get('webasyst', 'sender');
							if (!$emailFrom) $emailFrom = $modelSetting->get('webasyst', 'email');
							if (!$emailFrom) $emailFrom = $shop_email;
							if (!$emailFrom) $emailFrom = shopHelper::getStoreEmail(null);
						} else {
							$emailFrom = $mail_from;
						}

						$mail->setTo($email);
						$mail->setFrom(trim($emailFrom));
						
						if ($mail->send()) {
							waLog::log('email send to (' . $email . ')', "emailform/emailform.log");
						}
					}
					
					$this->response = array(
						'is' => 'success',
						'template' => $templateForm2
					);
				}
				else
				{
					$this->response = array(
						'is' => 'error',
						'error' => $error
					);					
				}
			}
        }
    }

    /*
     * return coupon code
     * @return string
     */
    private function generate_coupon_code()
    {
        $alphabet = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";
        $result = '';
        while (strlen($result) < 10) {
            $result .= $alphabet{mt_rand(0, strlen($alphabet)-1)};
        }

        return 'EF' . $result;
    }

    /*
     * add coupon in shop
     * @return array
     */
    private function create_coupon()
    {
        $plugin = wa('shop')->getPlugin('emailform');

        $coupon_hours = intval($plugin->getSettings('coupon_hours'));
        $coupon_type = intval($plugin->getSettings('coupon_type'));
        $coupon_type = str_replace(array(0, 1, 2), array('%', 'RUB', '$FS'), $coupon_type);
        $coupon_value = $plugin->getSettings('coupon_value');
        $coupon_value = floatval(str_replace(',', '.', $coupon_value));
        $coupon_comment = $plugin->getSettings('coupon_comment');

        $code = $this->generate_coupon_code();
        $time = $coupon_hours * 3600 + time();
        $expire_datetime = date('Y-m-d H:i:s', $time);
        $create_datetime = date('Y-m-d H:i:s', time());
        $create_contact_id = wa()->getUser()->getId();

        $coupon_data = array(
            'code' => $code,
            'type' => $coupon_type,
            'limit' => 1,
            'value' => $coupon_value,
            'comment' => $coupon_comment,
            'expire_datetime' => $expire_datetime,
            'create_datetime' => $create_datetime,
            'create_contact_id' => 0,
        );

        $couponm = new shopCouponModel();
        $new_coupon_id = $couponm->insert($coupon_data);

        if ($new_coupon_id > 0) {
            $return = array(
                'is' => 'success',
                'code' => $code,
                'type' => $coupon_type,
                'value' => $coupon_value,
                'hours' =>  $coupon_hours,
            );
            return $return;
        } else {
            return array(
                'is' => 'error',
            );
        }
    }
}
