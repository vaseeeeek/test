<?php

class shopArrivedPluginFrontendArrivedAddController extends waJsonController
{

	public function execute()
	{
		$settings = include shopArrivedPlugin::path('config.php');
		$data = waRequest::post();
		$email = isset($data['email']) ? trim($data['email']) : "";
		$phone = isset($data['phone']) ? trim($data['phone']) : "";
			$phone=str_replace(' ','',$phone);
			$phone=preg_replace("/[^0-9 \+]/u", "", $phone);
		$pid = (int)$data['plugin_arrived_pid'];
		$sku = $data['plugin_arrived_skuid']!="" ? (int)$data['plugin_arrived_skuid'] : "";
		$expiration = (int)$data['expiration'];
		$error = "";
		if($pid>0)
		{
			$validator = new waEmailValidator();
			if($settings['send_type']=="email") // peremudril aka nedodumal
			{
				if($email=="" || !$validator->isValid($email))
					$error = 'Указан некорректный адрес E-Mail';
			}
			elseif($settings['send_type']=="sms" && $error=="")
			{
				if(strlen($phone)<10 || strlen($phone)>17)
					$error = "Некорректно указан номер телефона";
			}
			elseif($settings['send_type']=="email_or_sms" && $error=="")
			{
				if($phone=="" && $email=="") {
					$error = "Контактные данные не указаны";
				} else {
					if($phone!="" && (strlen($phone)<10 || strlen($phone)>17))
						$error = "Некорректно указан номер телефона";
					if($email!="" && !$validator->isValid($email))
						$error = 'Указан некорректный адрес E-Mail';
				}
			}
			elseif($settings['send_type']=="email_and_sms" && $error=="")
			{
				if($phone=="" || $email=="") {
					$error = "Заполните все поля";
				} else {
					if($phone!="" && (strlen($phone)<10 || strlen($phone)>17))
						$error = "Некорректно указан номер телефона";
					if($email!="" && !$validator->isValid($email))
						$error = 'Указан некорректный адрес E-Mail';
				}
			}
			if(!empty($settings['terms_url']) && !isset($data['terms']))
				$error = 'Вы должны прочитать и принять Условия предоставления услуг';
			if($error=="")
			{
				$uid = wa()->getUser()->isAuth() ? wa()->getUser()->getId() : "";
				$current_domain = wa()->getRouting()->getDomain(null, true);
				$current_route_url = wa()->getRouting()->getRouteParam('url');
				$model = new shopArrivedModel();
				$expiration = $expiration>0 ? date("Y-m-d H:i:s",strtotime("+".$expiration." day")) : "";
				if($email!="")
					$model->deleteByField(array('user_id'=>$uid,'product_id'=>$pid,'sku_id'=>$sku,'email'=>$email,'domain'=>$current_domain,'route_url'=>$current_route_url,'sended'=>0));
				if($phone!="")
					$model->deleteByField(array('user_id'=>$uid,'product_id'=>$pid,'sku_id'=>$sku,'phone'=>$phone,'domain'=>$current_domain,'route_url'=>$current_route_url,'sended'=>0));
				$data = array('user_id'=>$uid,'product_id'=>$pid,'sku_id'=>$sku,'email'=>$email,'phone'=>$phone,'expiration'=>$expiration,'domain'=>$current_domain,'route_url'=>$current_route_url,'created'=>date("Y-m-d H:i:s"));
				$model->insert($data);
				if($settings['admin_email']!="") {
					$view = wa()->getView();
					$product = new shopProduct($pid);
					$skus_model = new shopProductSkusModel();
					$product['sku'] = $skus_model->getById($sku);
					$view->assign('product', $product);
					$message = new waMailMessage("Уведомление о поступлении - новая заявка", $view->fetch(wa()->getAppPath("plugins/arrived", "shop")."/templates/admin_email.html"));
					$message->setFrom($settings['email'], wa('shop')->getConfig()->getGeneralSettings('name'));
					$message->setTo($settings['admin_email']);
					$message->send();
				}
			} else {
				$this->errors = $error;
			}
		}
		else
		{
			$this->errors = "Произошла ошибка! Перезагрузите страницу и повторите запрос";
		}
	}

}