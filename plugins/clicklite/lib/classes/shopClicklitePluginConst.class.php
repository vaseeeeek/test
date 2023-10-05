<?php

/**
 * Класс констант плагина
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginConst
{
    /**
     * Название плагина
     * @return string
     */
    public function getNamePlugin()
    {
        return 'clicklite';
    }

    /**
     * Возвращает массив настроек по умолчанию
     * @return array
     */
    public function getSettingsDefault()
    {
        return array(
            'plugin_info'      => wa()->getConfig()->getAppConfig('shop')->getPluginInfo($this->getNamePlugin()),
            'status'           => 0,
            'comment'          => '* * * Покупка в один клик * * *',
            'thank'            => '<h3>Ваш заказ оформлен!</h3>
<p>Номер заказа: <b>$orderId</b></p>',
            'politika'         => 'Нажимая на кнопку, вы даете согласие на обработку своих персональных данных и соглашаетесь с <a href="#ссылка на политику" target="_blank">политикой конфиденциальности</a>',
            'policy_checkbox'  => 0,
            'button_view'      => 0,
            'vk'               => 0,
            'vk_id'            => '',
            'vk_token'         => '',
            'vk_templates'     => 'Новый заказ №-{$order.id} на сайте {$wa->shop->settings("name")|escape}
{if $order.contact.name}Пользователь: {$order.contact.name}.\n{/if}
{if $order.contact.email}Эл. адрес: {$order.contact.email}.\n{/if}
{if $order.contact.phone}Телефон: {$order.contact.phone}.\n{/if}
{if $order.comment}Комментарий: {$order.comment}.\n{/if}
Общая сумма: {$order.total}.',
            'telegram'         => 0,
            'telegram_id'      => '',
            'telegram_token'   => '',
            'yandex'           => array(
                'counter'      => '',
                'click'        => '',
                'send'         => '',
                'fail'         => '',
            ),
            'ecommerce'        => 0,
            'frontend_footer'  => 0,
            'mask'             => 0,
            'mask_view'        => '+7 (999) 999-99-99',
            'count'            => 0,
            'style_enable'     => 0,
            'script_enable'    => 0,
            'product_hook'     => 0,
            'product_name'     => 'Купить в 1 клик',
            'product_class'    => '',
            'list_name'        => 'Купить в 1 клик',
            'list_class'       => '',
            'cart_hook'        => 0,
            'cart_name'        => 'Купить в 1 клик',
            'cart_class'       => '',
            'version'          => 'version',
        );
    }

    /**
     * файлы css, js, templ которые можно редактировать и сохранять
     * @return array
     */
    public function getFileForEditAndSave()
    {
        return array(
            'style'  => 'css/' . $this->getNamePlugin() . '.css',
            'script' => 'js/' . $this->getNamePlugin() . '.js',
            'templ'  => 'templates/FrontendDisplay.html',
            'templ_form'  => 'templates/FrontendForm.html',
        );
    }
}