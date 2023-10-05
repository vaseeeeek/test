<?php

//заполнить информацию о плагине
$app_id = 'shop';
$plugin_id = 'phonemask'; // (!!!) не забыть изменить при копипасте!
$plugin_version = wa($app_id)->getPlugin($plugin_id)->getVersion();
$arr = explode('.', $plugin_version);
foreach ($arr as $i) {
	if (strlen($i) > 7) {
		$replaced_text = '.' . $i;
		$plugin_version = str_replace($replaced_text, '', $plugin_version);
	}
}

//заполнить информацию о разработчике
$docs_fullpath = 'https://chikurov-seo.ru/product/' . $plugin_id . '/docs/';
$developer_site = 'https://chikurov-seo.ru';
$developer_id = '1200329';
$developer_name = 'Веб-студия Анатолия Чикурова';
$developer_promo = '<p><a href="https://t.me/seo_flood" target="_blank"> >> Telegram канал разработчика</a><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i></p><p class="telegram_invitation"><i>Вступайте в телеграм канал! Вас ждут полезные новости и анонсы, приглашения на бесплатные вебинары, а также чат с коллегами и разработчиками, которые готовы помочь с решением вашей проблемы! (<a href="https://telegram.org/" target="_blank">скачать Telegram</a>)</i></p>';

//скрипты и стили для страницы настроек плагина
$files = '<link rel="stylesheet" href="../../wa-content/js/farbtastic/farbtastic.css?v1.14.7" type="text/css"><script type="text/javascript" src="../../wa-content/js/farbtastic/farbtastic.js?v1.14.7"></script><link href="../../wa-apps/shop/plugins/' . $plugin_id . '/css/settings.css?v=' . $plugin_version . '" rel="stylesheet"><script src="../../wa-apps/shop/plugins/' . $plugin_id . '/js/settings.js?v=' . $plugin_version . '"></script>';


//настройки (блоки визуально выделены отступами)
//документация по настройкам плагина: https://chikurov-seo.ru/instruktsii/instruktsiya-po-razrabotke-plagina/
return array(
    
	//////////////////////////// must have block
		'plugin_info_title' => array(
			'control_type' => waHtmlControl::TITLE,
			'value' => 'Информация о плагине и авторе',
			'description' => '
				<div class="plugin_docs">
					<p>ID плагина: <span id="plugin_docs_plugin_id">' . $plugin_id . '</span></p>
					<p>Текущая версия плагина: <span id="plugin_docs_plugin_version">' . $plugin_version . '</span></p>
					<p><a href="https://www.webasyst.ru/store/plugin/' . $app_id . '/' . $plugin_id . '/" target="_blank"> >> Страница плагина в магазине Webasyst</a></p>
					<p><span id="plugin_docs_plugin_docs_link"><a href="' . $docs_fullpath . '" target="_blank"> >> Документация к плагину</a></span></p><br>
					<p><b>@ ' . $developer_name . '</b></p>
					<p><a href="https://www.webasyst.ru/store/developer/' . $developer_id . '/" target="_blank"> >> Все плагины разработчика в магазине Webasyst</a></p>
					<p><a href="' . $developer_site  . '" target="_blank"> >> Сайт разработчика</a></p>
					'. $developer_promo . '
				</div>
				' . $files . '
			',
		),
		'enabled_title' => array(
			'control_type' => waHtmlControl::TITLE,
			'value' => 'Статус плагина',	
		),
		'enabled' => array(	
			'value' => 'Статус плагина',
			'control_type' => waHtmlControl::SELECT,
			'options' => array(
				array(
					'value' => '0',
					'title' => 'Откл',
				),
				array(
					'value' => '1',
					'title' => 'ВКЛ',
				),
			),
		),
		'export_title' => array(
			'control_type' => waHtmlControl::TITLE,
			'value' => 'Экспорт настроек плагина',
			'description' => '
				<span class="settings_export button red">Экспортировать</span>'
			,
		),
		'import_title' => array(
			'control_type' => waHtmlControl::TITLE,
			'value' => 'Импорт настроек плагина',
			'description' => '
				<span class="settings_import button red">Импортировать</span>'
			,
		),
        'lastsave_version' => array(
            'control_type' => waHtmlControl::HIDDEN,
            'value' => '0',
        ),
	////////////////////////////
	

	'frontend_title' => array(
		'control_type' => waHtmlControl::TITLE,
		'value' => 'Работа плагина во фронтенде',
	),
	'order_page' => array(
        'title' => 'Вывод маски на странице "Оформление заказа в корзине" (/order/)',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Для корректной работы плагина <b>обязательно</b> отключите маску для поля "Телефон" в настройках других продуктов.</span>
			</div>
		',
    ),
	'checkout_page' => array(
        'title' => 'Вывод маски на странице пошагового оформления заказа (/checkout/)',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Для корректной работы плагина <b>обязательно</b> отключите маску для поля "Телефон" в настройках других продуктов.</span>
			</div>'
		,
    ),
	'signup_page' => array(
        'title' => 'Вывод маски на странице регистрации (/signup/)',
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Чтобы плагин работал на странице регистрации, необходимо чтобы страница регистрации работала на поселении приложения "Магазин" (на поселении других приложений плагин работать не будет).</span>
				<span class="xmp2_info">Редактирование настроек личного кабинета осуществляется в разделе "Сайт" --> "*Витрина сайта*" --> "Личный кабинет".</span>
			</div>'
		,
    ),
	'login_page' => array(
        'title' => 'Вывод маски на странице авторизации (/login/)',
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Чтобы плагин внедрял маску на поле "Телефон" на странице авторизации, необходимо произвести настройки личного кабента согласно инструкции, а также настроить поле "Телефон" определенным образом (см. документацию). </span>
			</div>
			<xmp class="new_setting">3.1.0</xmp>
			<span class="setting_docs_link"></span>
		',
    ),
	'my_page' => array(
        'title' => 'Вывод маски на странице личного кабинета (/my/)',
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
    ),
	'plugin_buy1click_form' => array(
        'title' => 'Вывод маски на форме плагина «Купить в 1 клик» (Bodysite) (id: buy1click)',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => 'product',
				'title' => 'ВКЛ (только на страницах товаров)',
			),
			array(
				'value' => 'product/cart',
				'title' => 'ВКЛ (только на страницах товаров и в корзине)',
			),
			array(
				'value' => 'cart',
				'title' => 'ВКЛ (только в корзине)',
			),
			array(
				'value' => 'everywhere',
				'title' => 'ВКЛ (везде, в т.ч. в листинге товаров)',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Для корректной работы плагина <b>обязательно</b> отключите маску для поля "Телефон" в настройках плагина "Купить в 1 клик"</span>
			</div>
		',
    ),
	//'plugin_quickorder_form' => array(
    //    'title' => 'Вывод маски на форме плагина «Купить в один клик» (Игорь Гапонов) (id: quickorder)',	
    //    'control_type' => waHtmlControl::SELECT,
	//	'options' => array(
	//		array(
	//			'value' => '0',
	//			'title' => 'откл',
	//		),
	//		array(
	//			'value' => '1',
	//			'title' => 'ВКЛ',
	//		),
	//	),
    //),
	'plugin_buy1step_form' => array(
        'title' => 'Вывод маски на форме плагина «Заказ в 1 шаг» (Bodysite) (id: buy1step)',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Для корректной работы плагина <b>обязательно</b> отключите маску для поля "Телефон" в настройках плагина "Заказ в 1 шаг"</span>
			</div>
		',
    ),
	'plugin_ordercall_form' => array(
        'title' => 'Вывод маски на форме плагина «Заказ обратного звонка» (Bodysite) (id: ordercall)',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Для корректной работы плагина обязательно отключите маску для поля "Телефон" в настройках плагина "Заказ обратного звонка"</span>
			</div>
		',
    ),
	'backend_title' => array(
		'control_type' => waHtmlControl::TITLE,
		'value' => 'Работа плагина в бекенде',
	),
	'backend_edit_order_page' => array(
		'title' => 'Вывод маски на форме создания/редактирования заказа в бекенде',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Возможны ошибки при редактировании старых заказов, введенных без маски! Рекомендуется использовать с осторожностью или отключить данную настройку.</span>
			</div>
		',
	),
	'helpers_title' => array(
		'control_type' => waHtmlControl::TITLE,
		'value' => 'Работа плагина совместно с другими продуктами',
	),
	'helpers' => array(
        'title' => 'Вывод маски на прочих формах, интегрированных с плагином через хелпер',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'откл',
			),
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">Подробная инструкция по интеграции с плагином представена в документации к плагину</span>
			</div>
		',
    ),
	'settings_title' => array(
		'control_type' => waHtmlControl::TITLE,
		'value' => 'Дополнительные настройки',
	),
	'placeholder' => array(
        'title' => 'Включить подсказку для поля (placeholer)?',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">При отключении: скрывает подсказу для поля, к которому применена маска, - даже если подсказку добавляют сторонние плагины.</span>
				<span class="xmp1_info">При включении: выводит подсказку в формате маски.</span>
			</div>
		',
    ),
	'redborder' => array(
        'title' => 'Когда выводить красную границу при снятии фокуса с поля?',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '1',
				'title' => 'Всегда',
			),
			array(
				'value' => '2',
				'title' => 'Только для полей, обязательных для заполнения',
			),
			array(
				'value' => '0',
				'title' => 'Никогда',
			),
		),
		'description' => '
			<div class="hint_code_info">
				<span class="xmp1_info">При выборе опции "Всегда" - плагин всегда делает границы поля красными при снятии фокуса с поля.</span>
				<span class="xmp1_info">При выборе опции "Только для полей, обязательных для заполнения" - плагин проверяет соседний элемент на странице и ищет у него потомка, у которого в классе имеется фрагмент "required" (т.е., грубо говоря, ищет "звездочку"). Если такой элемент найден, то при снятии фокуса с элемента плагин делает границы поля красными.</span>
				<span class="xmp1_info">При выборе опции "Никогда" - плагин самостоятельно не меняет цвет границ поля (но это могут делать скрипты других продуктов: например, в оформлении заказа в корзине сам Shop Script сделает границы поля красными при снятии фокуса с элемента).</span>
			</div>
			<xmp class="new_setting">3.1.0</xmp>
		',
    ),
);