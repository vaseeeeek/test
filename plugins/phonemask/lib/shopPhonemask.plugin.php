<?php

/*
 * @author Anatoly Chikurov <anatoly@chikurov-seo.ru>
 */

class shopPhonemaskPlugin extends shopPlugin
{	
	//храним информацию подключали ли уже библиотеку или еще нет
	private static $is_output_library = false;
	public static function getIsOutputLibrary() {
		return self::$is_output_library;
	}
	public static function setIsOutputLibrary($is_output_library) {
		self::$is_output_library = $is_output_library;
	}
	
	//храним порядковые номера вызовов хелперов
	private static $helper_number = 0;
	public static function getHelperNumber() {
		return self::$helper_number;
	}
	public static function setHelperNumber($helper_number) {
		self::$helper_number = $helper_number;
	}
	
	public function frontendOrder() {
		if (!$this->getSettings('enabled')) {
			return;
		}
		
		$html = '';
		
		//оформление заказа в корзине
		if ($this->getSettings('order_page')) {
			$selector = '.wa-order-form-wrapper .wa-field-wrapper .wa-phone';
			$page_type = 'order_page';
			
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		return $html;
    }
	
	public function frontendCheckout() {
		if (!$this->getSettings('enabled')) {
			return;
		}
		
		$html = '';
		
		//пошаговое оформление заказа
		if ($this->getSettings('checkout_page')) {
			$selector = 'input[name*="[phone]"]';
			$page_type = 'checkout_page';
			
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		//заказ в 1 шаг (Bodysite)
		if ($this->getSettings('plugin_buy1step_form')) {
			$selector = '.buy1step-page .wa-field-phone input[name*="[phone]"]';
			$page_type = 'buy1step_form';
		
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		return $html;
    }
	
	public function frontendProduct() {
		if (!$this->getSettings('enabled')) {
			return;
		}
		
		$html = '';
		
		//купить в 1 клик (Bodysite) - страница товара
		if ($this->getSettings('plugin_buy1click_form') == 'product' || $this->getSettings('plugin_buy1click_form') == 'product/cart') {
			$selector = '.buy1click-form .buy1click-form__field .buy1click-form-field__input[type="tel"]';
			$page_type = 'buy1click_form';
		
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		return array(
			'menu' => $html,
		);
	}
	
	public function frontendCart() {
		if (!$this->getSettings('enabled')) {
			return;
		}
		
		$html = '';
		
		//купить в 1 клик (Bodysite) - корзина
		if ($this->getSettings('plugin_buy1click_form') == 'cart' || $this->getSettings('plugin_buy1click_form') == 'product/cart') {
			$selector = '.buy1click-form .buy1click-form__field .buy1click-form-field__input[type="tel"]';
			$page_type = 'buy1click_form';
		
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		//заказ в 1 шаг (Bodysite)
		if ($this->getSettings('plugin_buy1step_form')) {
			$selector = '.buy1step-page .wa-field-phone input[name*="[phone]"]';
			$page_type = 'buy1step_form';
		
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		return $html;
	}
	
	public function frontendFooter() {
		$this->getSettingsDump(); //экспорт настроек плагина
		if (!$this->getSettings('enabled')) {
			return;
		}
		
		$html = '';
		
		//страница регистрации
		if ($this->getSettings('signup_page')) {
			
			$url = $_SERVER['REQUEST_URI'];
			$is_signup_page = self::isSignUpPage($url); //'true' if has '/signup' in URL
			
			if ($is_signup_page) {
				$selector = 'input[name*="[phone]"]';
				$page_type = 'signup_page';
			
				$this->outputLibrary();
				$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
			}
		}
		
		//страница личного кабинета
		if ($this->getSettings('my_page')) {
			
			$url = $_SERVER['REQUEST_URI'];
			$is_my_page = self::isMyPage($url); //'true' if has '/my' in URL
			
			if ($is_my_page) {
                $is_authorized = wa()->getUser()->getId();
                if ($is_authorized) {
                    $selector = 'input[name*="[phone]"]';
                    $page_type = 'my_page_authorized';
                } else {
                    //WA пожадничал классы. Приходится делать это дерьмо, чтобы случайно не накинуть телефонную маску на поле с Email
                    $selector = '.js-login-form-fields-block input[placeholder*="елефон"]:not([placeholder*="ail"]):not([placeholder*="айл"]):not([placeholder*="эйл"]):not([placeholder*="очта"]):not([type*="pass"])';
                    $page_type = 'my_page_not_authorized';
                }



				$this->outputLibrary();
				$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
			}
		}
		
		//страница авторизации
		if ($this->getSettings('login_page')) {

			$url = $_SERVER['REQUEST_URI'];
			$is_login_page = self::isLoginPage($url); //'true' if has '/login' in URL
			
			if ($is_login_page) {
				
				//WA пожадничал классы. Приходится делать это дерьмо, чтобы случайно не накинуть телефонную маску на поле с Email
				$selector = '.js-login-form-fields-block input[placeholder*="елефон"]:not([placeholder*="ail"]):not([placeholder*="айл"]):not([placeholder*="эйл"]):not([placeholder*="очта"]):not([type*="pass"])'; 
				$page_type = 'login_page';
			
				$this->outputLibrary();
				$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
			}
		}
		
		//купить в 1 клик (Bodysite) - везде
		if ($this->getSettings('plugin_buy1click_form') == 'everywhere') {
			$selector = '.buy1click-form .buy1click-form__field .buy1click-form-field__input[type="tel"]';
			$page_type = 'buy1click_form';
		
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		//заказ обратного звонка (Bodysite) - везде
		if ($this->getSettings('plugin_ordercall_form')) {
			$selector = '.oc input[type="tel"]';
			$page_type = 'ordercall_form';
		
			$this->outputLibrary();
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		//подключаем библиоетеку везде, если в плагине включены хелперы
		if ($this->getSettings('helpers')) {
			$this->outputLibrary();
		}
		
		return $html;
	}
	
	public function backendOrderEdit() {
		if (!$this->getSettings('enabled')) {
			return;
		}
		
		$html = '';
		
		if ($this->getSettings('backend_edit_order_page')) {
			$selector = '.s-order-customer-details input[name*="[phone]"]';
			$page_type = 'edit_order_page';
		
			$html .= '<script src="/wa-apps/shop/plugins/phonemask/js/jquery.maskedinput.min.js"></script>';
			$html .= self::getScripts($selector, $page_type, $this->getSettings('placeholder'));
		}
		
		return $html;
	}
	
	//хелпер для сторонних разработчиков
	public static function getPhonemaskScripts($selector, $placeholder = true) {

		if (!wa('shop')->getPlugin('phonemask')->getSettings('enabled')) {
			return;
		}
		
		$html = '';
		
		//выдаем скрипты по хелперу
		if (wa('shop')->getPlugin('phonemask')->getSettings('helpers')) {
			
			//уникализируем
			self::setHelperNumber(self::getHelperNumber() + 1);
			$helper_number = self::getHelperNumber();
			$helper_page_type = 'somepage_' . $helper_number;
			
			$html = self::getScripts($selector, $helper_page_type, $placeholder);
		}
		
		return $html;
	}
	
	//получение скриптов
	public static function getScripts($selector, $page_type, $placeholder) {
		$view = wa()->getView();
		
		$settings = wa('shop')->getPlugin('phonemask')->getSettings();
		$view->assign('settings', $settings);
		$view->assign('selector', $selector);
		$view->assign('page_type', $page_type);
		$view->assign('placeholder', $placeholder);
		$html = $view->fetch(wa()->getAppPath('plugins/phonemask/templates/scripts.html'));

		return $html;
	}
	
	//подключение библиотеки
	public function outputLibrary() {
		if(!self::getIsOutputLibrary()) {
			$this->addJs('js/jquery.maskedinput.min.js');
		}
		self::setIsOutputLibrary(true);
	}
	
	//проверка URL-адреса на наличие фрагмента "/signup"
	public static function isSignUpPage($url) {
		if (strpos($url,'/signup') !== false) {
			return true;
		}
		return false;
	}
	
	//проверка URL-адреса на наличие фрагмента "/login"
	public static function isLoginPage($url) {
		if (strpos($url,'/login') !== false) {
			return true;
		}
		return false;
	}
	
	//проверка URL-адреса на наличие фрагмента "/my"
	public static function isMyPage($url) {
		if (strpos($url,'/my') !== false) {
			return true;
		}
		return false;
	}
	
	
	// экспорт настроек
	public function backendMenu() {
		$this->getSettingsDump();
		return;
	}
	public function getSettingsDump() {
		$plugin_id = 'phonemask'; //не забыть указать id плагина при копипасте!!
		if (waRequest::request('shop_' . $plugin_id . '_settings') == 1) {
			echo '
				<div class="dump_info">
					<span><b>Установленная версия плагина на данном проекте:</b> <b class="b-bitch">' . wa('shop')->getPlugin($plugin_id)->getVersion() . '</b><span>
					<h3>Инструкция по копированию дампа:</h3>
					<ol>
						<li>Убетитесь, что вы хотите импортировать настройки на проект, на котором установлена <b class="b-bitch">такая же</b> версия плагина</li>
						<li>Нажмите на серый блок</li>
						<li>Убетитесь, что все содержимое серого блока выделилось</li>
						<li>Скопируйте выделенный текст</li>
						<li>Вставьте его на форму импорта настроек плагина, не внося никаких изменений</li>
					</ol>
					
					<h3>Дамп настроек плагина ' . $plugin_id . ':</h3>
				</div>
			';
			echo '
				<style>
					pre { background: #eaeaea; display: inline-block; padding: 10px; border-radius: 10px; user-select: all; cursor: pointer; margin-top: 0; }
					.dump_info {  display: block; width: 100%; }
					.dump_info span { display: block; }
					b.b-bitch { font-weight: 900; text-decoration: underline; color: black; font-size: 1.2em; text-decoration-color: red; }
				</style>
			';
			wa_dump($this->getSettings());
		}
	}
}