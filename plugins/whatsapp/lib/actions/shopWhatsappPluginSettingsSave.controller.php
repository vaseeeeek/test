<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopWhatsappPluginSettingsSaveController extends waJsonController
{

    public function execute()
    {
        // Дефолтные настройки
        $default_settings = include shopWhatsappPlugin::path('config.php', true);
        $settings = waRequest::post('settings', array());
        $save_settings['enable'] = isset($settings['enable']) ? intval($settings['enable']) : '0';
        $save_settings['button_name'] = $settings['button_name'] ? $settings['button_name'] : $default_settings['button_name'];
        $save_settings['output_places'] = !empty($settings['output_places']) ? $settings['output_places'] : array();
        $save_settings['onclick'] = $settings['onclick'];
        $save_settings['message'] = trim($settings['message']) ? mb_substr($settings['message'], 0, 200, 'UTF-8') : "";
        $save_settings['only_mobile'] = !empty($settings['only_mobile']) ? 1 : 0;

        foreach (array('background', 'text') as $button_style) {
            if (isset($settings['button'][$button_style])) {
                $save_settings['button'][$button_style] = $settings['button'][$button_style] ? substr($settings['button'][$button_style], 0, 6) : "";
            }
        }

        $save_settings['button']['width'] = !empty($settings['button']['width']) ? (int) $settings['button']['width'] : "";
        $save_settings['button']['height'] = !empty($settings['button']['height']) ? (int) $settings['button']['height'] : "";
        $save_settings['button']['size'] = !empty($settings['button']['size']) ? (int) $settings['button']['size'] : "";
        $save_settings['button']['padding'] = !empty($settings['button']['padding']) ? $settings['button']['padding'] : "";

        $save_settings['border']['color'] = $settings['border']['color'] ? substr($settings['border']['color'], 0, 6) : "";
        $save_settings['border']['width'] = (int) $settings['border']['width'];
        $save_settings['border']['radius'] = (int) $settings['border']['radius'];
        $save_settings['border']['style'] = $settings['border']['style'];

        // Путь к файлу настроек
        $config_settings_file = shopWhatsappPlugin::path('config.php');
        // Записываем новые настройки
        if (!waUtils::varExportToFile($save_settings, $config_settings_file)) {
            $this->errors['messages'][] = _wp('Cannot save settings');
        }
    }

}
