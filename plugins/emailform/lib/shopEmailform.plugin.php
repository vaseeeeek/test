<?php

class shopEmailformPlugin extends shopPlugin
{
	
	private $carts_url = false;
	private $subscribe_url = false;
	private $mailer_form_id = 0;

	/* 
	 * integrationPlugins function
	 * @set carts_url, subscribe_url
	 */
	private function integrationPlugins()
	{
		/* Если установлен плагин "Брошенные корзины", то отправляем запрос на него */
		$plugins = wa()->getConfig()->getAppConfig('shop')->getPlugins();
		if (isset($plugins['carts'])) {
			$this->carts_url = wa()->getRouteUrl('shop/frontend/save', array('plugin' => 'carts'));
		}

		/* Для отправки почты на подписку в приложение "Рассылки" */
		$plugin = wa('shop')->getPlugin('emailform');
		$mailer_form_id = intval($plugin->getSettings('mailer_form_id'));
		$subscribe_url = wa()->getRouteUrl('mailer/frontend/subscribe/');
		if ($mailer_form_id > 0 AND $subscribe_url) {
			$this->mailer_form_id = $mailer_form_id;
			$this->subscribe_url = $subscribe_url;
		}
	}

    /*
	 * showForm function
	 * get type of show form ('popup' - auto, 'static' - manual)
     * @return string 
     */
	private function showForm()
	{
		$root = wa()->getRootUrl();
		$front = wa()->getRouteUrl('shop/frontend');
		$plugin = wa('shop')->getPlugin('emailform');

		$type = intval($plugin->getSettings('type'));					//Предложить за подписку
		$show = intval($plugin->getSettings('show'));					//Показать форму
		$show_wait = intval($plugin->getSettings('show_wait')) * 1000; 	//С задержкой (секунд)
		$field_name = intval($plugin->getSettings('field_name'));		//Поле "Имя" 
		$field_email = intval($plugin->getSettings('field_email'));		//Поле "e-mail" 
		$field_phone = intval($plugin->getSettings('field_phone'));		//Поле "телефон" 
		$pdn = intval($plugin->getSettings('pdn'));						//Обработка персональных данных 
		
		$cssselector = $plugin->getSettings('cssselector');				//CSS селектор
		$title = $plugin->getSettings('title');							//Заголовок формы
		$text = $plugin->getSettings('text');							//Описание формы
		$pdn_text  = $plugin->getSettings('pdn_text');					//Текст персональных данных
		$submit_value  = $plugin->getSettings('submit_value');			//Название кнопки 

		$templateForm = $plugin->getSettings('templateForm');			//Шаблон формы подписки
		$templateJs = $plugin->getSettings('templateJs');				//Дополнительные скрипты
		$templateCss = $plugin->getSettings('templateCss');				//Дополнительные стили

		$discount = '';
		if ($type == 1) { //автогенерация купона
			$coupon_hours = intval($plugin->getSettings('coupon_hours'));
			$coupon_type = intval($plugin->getSettings('coupon_type'));
			$coupon_type = str_replace(array(0, 1, 2), array('%', ' рублей', 'Бесплатная доставка'), $coupon_type);
			$coupon_value = $plugin->getSettings('coupon_value');
			$coupon_value = floatval(str_replace(',', '.', $coupon_value));
			$discount = $coupon_value . $coupon_type;

			if ($coupon_type != 'Бесплатная доставка' AND (!$coupon_hours OR !$coupon_value)) return '';
		}

		if ($type == 2) { //сгенерированный купон
			$coupon_hours2 = intval($plugin->getSettings('coupon_hours2'));
			$coupon_value2 = $plugin->getSettings('coupon_value2');
			$coupon_code2 = $plugin->getSettings('coupon_code2');
			$discount = $coupon_value2;

			if (!$coupon_value2 OR !$coupon_code2 OR !$coupon_hours2) return '';
		}

		$templateForm = str_replace(array('{$title}', '{$text}', '{$discount}'), array($title, $text, $discount), $templateForm);
		
		$head = "\r\n<link rel='stylesheet' href='{$root}wa-apps/shop/plugins/emailform/css/style.css'>\r\n";
		if ($templateCss) $head .= "<style>".$templateCss."</style>";

		$head .= "<script type='text/javascript'>if (typeof(jQuery) == 'undefined') {
			document.write('<script src=\"{$root}wa-apps/shop/plugins/emailform/js/jquery-3.1.0.min.js\" type=\"text/javascript\"><\/script>');
		} </script>\r\n";
		$head .= "<script type='text/javascript'>var emailformGlobalFrontend = \"{$front}\"</script>\r\n";
		if ($cssselector) $head .= "<script type='text/javascript'>var emailformGlobalSelector = \"{$cssselector}\"</script>\r\n";
		if ($templateJs) $head .= "<script type='text/javascript'>" . $templateJs . "</script>";
		$head .= "<script type='text/javascript' src=\"{$root}wa-apps/shop/plugins/emailform/js/emailform.js\"></script>\r\n";
		
		//показывать автоматически окно только если не установлена кука
		if (!waRequest::cookie('emailform_show', '', 'string')) {

			if ($show == 0) $head .= "
			<script type='text/javascript'>
			$(function(){ 
				setTimeout(emailformShow, {$show_wait});
			});
			</script>\r\n";
			
			if ($show == 1) $head .= "
			<script type='text/javascript'>
			var alert_flag = true;
			$(function(){  
				$(document).mouseleave(function(e){
					if (e.clientY < 0) {
						if (alert_flag) {
							emailformShow(); 
						}
				    }
				});
			});
			</script>\r\n";

		}

		/* Интеграция с плагином и приложением */
		$this->integrationPlugins();
		
		$view = wa()->getView();
		$view->assign('templateForm', $templateForm);
		$view->assign('field_name', $field_name);
		$view->assign('field_email', $field_email);
		$view->assign('field_phone', $field_phone);
		$view->assign('pdn', $pdn);
		$view->assign('pdn_text', $pdn_text);
		$view->assign('submit_value', $submit_value);
		$view->assign('carts_url', $this->carts_url);
		$view->assign('subscribe_url', $this->subscribe_url);
		$view->assign('mailer_form_id', $this->mailer_form_id);
		$path = wa()->getAppPath('plugins/emailform/templates/popup.html', 'shop');
		$html = $view->fetch($path);

		return $head . $html;
	}

    /*
     * @event frontend_footer
     * @return string
     */
	public function frontendFooter()
	{

		$switchoff = intval($this->getSettings('switchoff'));

		/* Не показываем если плагин выключен (-ИЛИ авторизованный пользователь)) */ 
		if (!$switchoff) { 
			return '';
		}

        if (!isset($_COOKIE["luckyNumber"])){
            $luckyNumber = rand(1, 100);
            setcookie("luckyNumber", $luckyNumber);
        } else {
            $luckyNumber = $_COOKIE["luckyNumber"];
        }

		if ($luckyNumber >= 1 && $luckyNumber <= 7) {
            return $this->showForm();
        }
	}
	
}
