<?php

return array(
    'enabled' => array(
        'title' => 'Статус плагина',	
        'control_type' => waHtmlControl::SELECT,
		'options' => array(
			array(
				'value' => '0',
				'title' => 'Отключен',
			),
			array(
				'value' => '1',
				'title' => 'Включен',
			),
		),
    ),
	'plugins' => array(
        'title' => 'Доработки страниц настроек плагинов',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Увеличивает размер шрифта, добавляет отступы, исправляет "Textarea", итп правки на страницах настроек плагинов со стандартными настройками.
		<style>
		#wa-plugins-content form[action*="id=fixbackend"] {margin-top: 20px !important;}html body #wa-plugins-content form[action*="id=fixbackend"] .field {display: inline-block !important; width: 100% !important; margin-bottom: 15px !important; padding-bottom: 15px !important; border-bottom: 1px solid #f0f0f0 !important;}html body #wa-plugins-content form[action*="id=fixbackend"] .field:last-child {margin-bottom: 0 !important; border-bottom: none !important;}html body #wa-plugins-content form[action*="id=fixbackend"] .field .name {color: black !important; margin-bottom: 12px !important; line-height: 16px !important; font-size: 14px !important; position: relative !important; bottom: 5px !important; display: inline-block !important; margin-bottom: 0 !important; padding-bottom: 0 !important;}html body #wa-plugins-content form[action*="id=fixbackend"] .field .name label {cursor: default !important;}html body #wa-plugins-content form[action*="id=fixbackend"] .field .value span.hint {color: #444141 !important; font-size: 0.9em !important; line-height: 1.2em !important; display: inline-block !important; margin-top: 7px !important;}html body #wa-plugins-content form[action*="id=fixbackend"] .field .value input, #wa-plugins-content form[action*="id=fixbackend"] .field .value select {cursor: pointer !important;}html body #wa-plugins-content form[action*="id=fixbackend"] .field .value textarea {min-width: 520px !important; max-width: 100% !important; box-sizing: border-box !important; min-height: 100px !important; padding: 5px !important;}html body #wa-plugins-content .form {max-width: 100% !important;}; 
		</style>
		',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'order' => array(
        'title' => 'Доработки страниц заказов',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Увеличивает размеры шрифта; добавляет фон и отступы для артикулов; увеличивает и добавляет отступы между кнопками выполнения действий с заказами; выводит категории покупателя (при наличии); улучшает формы плагина "Комментарии к заказу" (ID: «bnpcomments»); итп правки.',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'product' => array(
        'title' => 'Доработки страниц редактирования товаров',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Фиксирует кнопку редактирования товара а также кнопку плагина "Генерация ЧПУ в карточке товара" (ID: «copychpu»); увеличивает ширину полей при редактировании товара; добавляет разделительную полосу между характеристиками; итп правки.',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'category' => array(
        'title' => 'Доработки страниц редактирования категорий',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Фиксирует блок с кнопками "Настройки категории" и "Удалить категорию"; увеличивает ширину полей при редактировании категории; итп правки.',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'category_list' => array(
        'title' => 'Доработки блока со списком категорий',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Увеличивает кликабельную область иконки разворачивания/сворачивания категории; фиксирует иконку добавления новой категории; упрощает перемещения категорий; стилизует кнопки расширения блока с категориями.',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'table' => array(
        'title' => 'Доработки таблиц с товарами для редактирования',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Увеличивает размеры флажков для выделения товаров; улучшает форму плагина "Массовое редактирование характеристик" (ID: «productfeatures»); ярко выделяет только что измененные цены; подсвечивает красным цветом незаполненные цены.
		<style>.fixbackend_hint {background: #dcdcdc; padding: 2px 6px; margin-top: 2px; display: inline-block;}</style>
		<br><br>
		<i>Чтобы увеличить ширину столбца "Название" вставьте в поле "Дополнительный CSS" код (установите нужное значение вместо "500px"):</i>
		<br>
		<span class="fixbackend_hint">#s-product-list-table-container .s-product-name {min-width: 500px !important;}</span>
		',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'custum_css' => array(
        'title' => 'Дополнительный CSS',
        'description' => 'Здесь можно добавить произвольный CSS код для бекенда приложения "Магазин". Содержимое поля будет выведено в исходном коде бекенда в теге &lt;style&gt;.',
		'placeholder' => '.selector {
	color: black;
}',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
	'custum_js' => array(
        'title' => 'Дополнительный JS',
        'description' => 'Здесь можно добавить произвольный JS код для бекенда приложения "Магазин". Содержимое поля будет выведено в исходном коде бекенда как есть. Тег &lt;script&gt; нужно прописывать в коде.',
        'placeholder' => "<script>
	alert( 'Hello world!' );
</script>",
		'control_type' => waHtmlControl::TEXTAREA,
    ),
	'button_hover' => array(
        'title' => 'Доработки кнопок (на разных страницах)',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Добавляет тени и небольшую прозрачность для кнопок с классом "button" при наведении курсора; меняет тип курсора на "pointer".',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'elements_selected' => array(
        'title' => 'Доработки активных вкладок (на разных страницах)',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Добавляет тени и закругленные углы для элементов с классом "selected"; меняет тип курсора на "default".',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'product_hints' => array(
        'title' => 'Доработки определенных элементов на странице редактирования товаров',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Добавляет красные границы для полей "Теги", "Заголовок страницы", "META Keywords", "META Description", итп  на странице редактирования товара.',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'list_and_features_hints' => array(
        'title' => 'Доработки определенных списков товаров и характеристик товаров',	
        'control_type' => waHtmlControl::SELECT,
		'description' => 'Выделяет списки товаров красным цветом, если они содержат в ID фрагмент "_auto_" и зеленым цветом - если "_hand_"; тоже самое для характеристик товаров: на страницах редактирования товаров и на форме плагина "Массовое редактирование товаров".',
		'options' => array(
			array(
				'value' => '1',
				'title' => 'ВКЛ',
			),
			array(
				'value' => '0',
				'title' => 'откл',
			),
		),
    ),
	'mainmenu_links' => array(
        'title' => 'Уменьшить размер вкладок в основном меню приложения',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Уменьшает размер шрифта и отступы, чтобы больше вкладок помещалось на странице перед кнопкой "Ещё"',
    ),
	'list_hide_id' => array(
        'title' => 'Скрыть в списках товаров ID списков',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в списках товаров ID.',
    ),
	'table_hide_name' => array(
        'title' => 'Скрыть в таблице (вид: "Таблица") столбец "Название"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Таблица") столбец "Название", выводит блок с предупреждением.',
    ),
	'table_hide_price' => array(
        'title' => 'Скрыть в таблице (вид: "Таблица") столбец "Цена"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Таблица") столбец "Цена", выводит блок с предупреждением.',
    ),
	'table_hide_stock' => array(
        'title' => 'Скрыть в таблице (вид: "Таблица") столбец "В наличии"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Таблица") столбец "В наличии", выводит блок с предупреждением.',
    ),
	'tableskus_hide_name' => array(
        'title' => 'Скрыть в таблице (вид: "Артикулы") столбец "Название"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Артикулы") столбец "Название", выводит блок с предупреждением.',
    ),
	'tableskus_hide_purchase_price' => array(
        'title' => 'Скрыть в таблице (вид: "Артикулы") столбец "Закупочная цена"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Артикулы") столбец "Закупочная цена", выводит блок с предупреждением.',
    ),
	'tableskus_hide_compare_price' => array(
        'title' => 'Скрыть в таблице (вид: "Артикулы") столбец "Зачеркнутая цена"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Артикулы") столбец "Зачеркнутая цена", выводит блок с предупреждением.',
    ),
	'tableskus_hide_price' => array(
        'title' => 'Скрыть в таблице (вид: "Артикулы") столбец "Цена"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Артикулы") столбец "Цена", выводит блок с предупреждением.',
    ),
	'tableskus_hide_stock' => array(
        'title' => 'Скрыть в таблице (вид: "Артикулы") столбец "В наличии"',	
        'control_type' => waHtmlControl::CHECKBOX,
		'description' => 'Скрывает в таблице с товарами для редактирования (вид отображения товаров: "Артикулы") столбец "В наличии", выводит блок с предупреждением.',
    ),
);