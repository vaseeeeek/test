<?php

$routing = wa()->getRouting();
$domain_routes = $routing->getByApp('shop');
$plugin_arrived['sms_sender_id'] = array();
$plugin_arrived['any_domain_is_correct'] = false;
foreach ($domain_routes as $domain => $routes) {
	if($domain == waRequest::server('HTTP_HOST')) {
		$plugin_arrived['any_domain_is_correct'] = true;
	}
	$plugin_arrived['sms_sender_id'][$domain] = substr(preg_replace("/(www\.)?([a-zA-Z0-9-]+)(\..*)/is","$2",$domain),0,11);
}
if(!$plugin_arrived['any_domain_is_correct']) {
	$plugin_arrived['sms_sender_id'][waRequest::server('HTTP_HOST')] = substr(preg_replace("/(www\.)?([a-zA-Z0-9_-]+)(\..*)/is","$2",waRequest::server('HTTP_HOST')),0,11);
}

return array (
  'send_type' => 'email',
  'sms_sender_id' => $plugin_arrived['sms_sender_id'],
  'expiration' => '7,30,60,90,360',
  'email' => 'noreply@'.preg_replace("/(www\.)(.*)/is","$2",waRequest::server('HTTP_HOST')),
  'plink_title' => 'Сообщить о поступлении',
  'clink_title' => 'Сообщить о поступлении',
  'popup_success' => '<strong>Ваша просьба принята!</strong>
<br /><br />Вы получите уведомление о поступлении товара в продажу на указанные Вами контакты',
  'templateSMS' => 'Товар «{$product.name|escape}» появился в наличии.\nПосмотреть: {$product.frontend_url}{if $email_sended} \nПодробная информация отправлена на {$user_email}{/if}',
  'templateMail' => '<p>Здравствуйте!</p>
<p>Вами была подана заявка с просьбой уведомить вас о поступлении в продажу «<strong>{$product.name|escape}</strong>».
<br>Сообщаем вам, что в данный момент товар имеется в наличии.</p>
<p>Посмотреть и купить его вы можете на странице: <a href="{$product.frontend_url}">{$product.frontend_url}</a></p>
<p> </p>
<p>Спасибо, что выбрали магазин «{$wa->shop->settings("name")|escape}»!</p>
<p>--<br>
{$wa->shop->settings("name")|escape}<br>
<a href="mailto:{$wa->shop->settings(\'email\')}">{$wa->shop->settings(\'email\')}</a><br>
{$wa->shop->settings("phone")}<br></p>',
  'mail_subject' => 'Интересующий вас товар поступил в продажу',
  'popup_title' => 'Сообщить о поступлении товара',
  'terms_url' => '',
  'enable_hook' => 1,
  'admin_email' => '',
);