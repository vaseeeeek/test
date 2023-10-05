<?php

/**
 * Класс уведомления в телеграм
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginNotice
{
    private $settings;

    public function __construct()
    {
        $this->settings = shopClicklitePluginSettings::getInstance()->getSettings();
    }

    /**
     * Отправка сообщения в телеграм
     * @param string $message
     */
    public function notifyTelegram($message)
    {
        if($this->settings['telegram'])
        {
            $chatId = $this->settings['telegram_id'];
            $token = $this->settings['telegram_token'];

            if ($chatId && $token)
            {
                $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&parse_mode=html&text={$this->getMessageTelegram($message)}";
                $data = file_get_contents($url);

                if (!$data) {
                    waLog::log('Ошибка отправки телеграм!', 'shop/telegram.error.log');
                }
            }
            else
            {
                waLog::log('Не заданы настройки токена и чат id!', 'shop/telegram.error.log');
            }
        }
    }

    /**
     * Отправка сообщения в вконтакте
     * @param $order информация о заказе
     */
    public function notifyVk($order)
    {
        if(!$this->settings['vk'])
            return;

        $url = 'https://api.vk.com/method/messages.send';
        $id = $this->settings['vk_id'];
        $token = $this->settings['vk_token'];

        if($id && $token)
        {

            $view = wa()->getView();
            $order['id'] = shopHelper::encodeOrderId($order['id']);
            $view->assign('order', $order);

            $message = htmlspecialchars_decode($view->fetch('string:' . $this->settings['vk_templates']));
            $message = str_replace ('\n', "\n", $message);

            $params = array(
                'user_id' => $id,
                'message' => $message,
                'access_token' => $token,
                'v' => '5.73',
            );

            $result = file_get_contents($url, false, stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($params)
                )
            )));

            if(empty($result)) {
                waLog::log('Ошибка отправки vk! Сервис vk не доступен', 'shop/vk.error.log');
            }
        }
        else
        {
            waLog::log('Не заданы настройки токена или чат id!', 'shop/vk.error.log');
        }
    }

    /**
     * Вернет сформированное сообщение для телеграм
     * @param int $message
     * @return string
     */
    private function getMessageTelegram($message)
    {
        $nameSite = wa('shop')->getConfig()->getGeneralSettings('name');

        $messTelegram = urlencode('<b>Новый заказ на сайте - ' . $nameSite . '</b>') . '%0A';
        $messTelegram .= urlencode('Номер заказа: <b>' . shopHelper::encodeOrderId($message) . '</b>') . '%0A';

        return $messTelegram;
    }
}