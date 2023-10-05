<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
// Чистка мусора
$files = array(
    wa()->getAppPath("plugins/quickorder/wa-apps/", "shop"),
    wa()->getAppPath("plugins/quickorder/README.txt", "shop"),
    wa()->getAppPath("plugins/quickorder/locale/", "shop"),
);

try {
    foreach ($files as $file) {
        if (file_exists($file)) {
            waFiles::delete($file, true);
        }
    }
} catch (Exception $e) {
    
}
try {
// Добавление настроек
    $path = wa()->getDataPath('plugins/quickorder/config.php', false, 'shop', true);
    if (file_exists($path)) {
        $settings = include $path;
        $default_settings = include shopQuickorderPlugin::path('config.php', true);
        if (empty($settings['cart_button_name'])) {
            $settings['cart_button_name'] = $default_settings['cart_button_name'];
        }
        if (empty($settings['comment'])) {
            $settings['comment'] = $default_settings['comment'];
        }
        if (empty($settings['order_text'])) {
            $settings['order_text'] = $default_settings['order_text'];
        }
        if (empty($settings['enable_frontend_cart_hook'])) {
            $settings['enable_frontend_cart_hook'] = $default_settings['enable_frontend_cart_hook'];
        }
        // Путь к файлу настроек
        $config_settings_file = shopQuickorderPlugin::path('config.php');
        // Записываем новые настройки
        waUtils::varExportToFile($settings, $config_settings_file);
    }
} catch (Exception $e) {
    
}