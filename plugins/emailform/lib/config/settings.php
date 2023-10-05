<?php
return array(
    'switchoff' => array(
        'title' => 'Switchoff',
        'value' => 0,
        'control_type' => waHtmlControl::SELECT,
    ), 
	
    'type' => array(
        'title' => 'Type',
        'value' => 0,
        'control_type' => waHtmlControl::SELECT,
    ), 
	
    'show' => array(
        'title' => 'Show',
        'value' => 0,
        'control_type' => waHtmlControl::SELECT,
    ), 
    'show_wait' => array(
        'title' => 'Show Wait',
        'value' => '3',
        'control_type' => waHtmlControl::INPUT,
    ),

    'field_name' => array(
        'title' => 'Field Name',
        'value' => 1,
        'control_type' => waHtmlControl::SELECT,
    ),
    'field_email' => array(
        'title' => 'Field Email',
        'value' => 2,
        'control_type' => waHtmlControl::SELECT,
    ),
    'field_phone' => array(
        'title' => 'Field Phone',
        'value' => 1,
        'control_type' => waHtmlControl::SELECT,
    ),
    'pdn' => array(
        'title' => 'Pdn',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'dont_show_urls' => array(
        'title' => 'Dont show in this urls',
        'value' => '',
        'control_type' => waHtmlControl::TEXTAREA,
    ),

    'title' => array(
        'title' => 'Form title',
        'value' => 'Скидки, купоны и акции',
        'control_type' => waHtmlControl::INPUT,
    ),
    'text' => array(
        'title' => 'Form text',
        'value' => 'Подпишитесь на наши рассылки и получайте скидки, купоны и акции!',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'pdn_text' => array(
        'title' => 'Pdn Text',
        'value' => 'Я принимаю условия <a href="---ВСТАВЬТЕ СЮДА ССЫЛКУ НА ДОКУМЕНТ!---" target="_blank">политики обработки персональных данных</a>',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'submit_value' => array(
        'title' => 'Submit Value',
        'value' => 'Подписаться',
        'control_type' => waHtmlControl::INPUT,
    ),

    'coupon_type' => array(
        'title' => 'Coupon Type',
        'value' => 0,
        'control_type' => waHtmlControl::SELECT,
    ), 
    'coupon_value' => array(
        'title' => 'Coupon Value',
        'value' => '10',
        'control_type' => waHtmlControl::INPUT,
    ),
    'coupon_hours' => array(
        'title' => 'Coupon Hours',
        'value' => '24',
        'control_type' => waHtmlControl::INPUT,
    ),
    'coupon_comment' => array(
        'title' => 'Coupon Comment',
        'value' => 'Автогенерация EmailForm',
        'control_type' => waHtmlControl::INPUT,
    ),
	
    'coupon_value2' => array(
        'title' => 'Coupon Value2',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'coupon_code2' => array(
        'title' => 'Coupon Code2',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'coupon_hours2' => array(
        'title' => 'Coupon Hours2',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),

    'cookie' => array(
        'title' => 'Cookie',
        'value' => '30',
        'control_type' => waHtmlControl::INPUT,
    ),

    'templateForm' => array(
        'title' => 'templateForm',
        'value' => '<div class="ef-title">{$title}</div> 
<div class="ef-text">{$text}</div>',
        'control_type' => waHtmlControl::TEXTAREA,
    ),	
    'templateForm2' => array(
        'title' => 'templateForm2',
        'value' => '<div class="ef-title">{$title}</div> 
<div class="ef-text">{$text}</div>
<div class="ef-text-success">Спасибо за подписку!</div>',
        'control_type' => waHtmlControl::TEXTAREA,
    ),

    'sendemail' => array(
        'title' => 'Send Email',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
	'subject'  => array(
        'title' => 'Subject Email',
        'value' => 'Спасибо за подписку',
        'control_type' => waHtmlControl::INPUT,
    ),
    'mailfrom'  => array(
        'title' => 'From Email',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'templateEmail' => array(
        'title' => 'templateEmail',
        'value' => '<p>Спасибо, вы успешно подписались на рассылку!</p>
<br/>---<br/>
{$name}<br/>
{$email}<br/>
{$phone}',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
	
    'delimiter' => array(
        'title' => 'Delimiter',
        'value' => ';',
        'control_type' => waHtmlControl::INPUT,
    ),
	
    'mailer_form_id' => array(
        'title' => 'Mailer Form ID',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),

    'cssselector' => array(
        'title' => 'CSS selector',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),

    'templateCss' => array(
        'title' => 'templateCss',
        'value' => "#emailform_popup .visible {
    /*Фоновая картинка формы*/
    /*background-image: url(/wa-apps/shop/plugins/emailform/img/fon2.png);*/
  
    /*Фоновый цвет подложки*/
    /*background-color: #eeeae9;*/
  
    /*Цвет шрифта*/
    /*color: #333;*/
}

#emailform_popup input[type=submit].ef-submit-input {
    /*Цвет и градиент кнопки Подписаться*/
    /*background: #fcd630 linear-gradient(to bottom, rgba(255,255,255,0.5) 0%,rgba(255,255,255,0.0) 100%);*/
}",
        'control_type' => waHtmlControl::TEXTAREA,
    ),
	
    'templateJs' => array(
        'title' => 'templateJs',
        'value' => "//успешная подписка
function emailform_event_subscribe_success() {
    //yaCounterXXXXXX.reachGoal('TARGET_NAME');
    //return true;
}

//успешная отправка данных формы
function emailform_event_submit_success() {
    //return true;
}

//нажатие на кнопку 'Отправить'
function emailform_event_submit_click() {
    //return true;
}

//открытие формы подписки
function emailform_event_form_show() {
    //return true;
}",
        'control_type' => waHtmlControl::TEXTAREA,
    ),
);
