<?php

class shopSaleskuPluginBackendCreateFilesController extends waJsonController {
    
    public function execute() {
        $storefront = waRequest::get('storefront');
        if(empty($storefront)) {
            $storefront = shopSaleskuPlugin::GENERAL_STOREFRONT;
        }
        /* Для общих настроек всех витрин запрещаем создание шаблонов */
        if($storefront == shopSaleskuPlugin::GENERAL_STOREFRONT) {
            return;
        }
        $settings = shopSaleskuPlugin::getPluginSettings($storefront);
        $storefront = $settings->getStorefront();
        $templates = new shopSaleskuPluginTemplates($settings);
        /* Получаем все темы для витрины и добавляем файлы плагина */
        foreach ($storefront->getThemes() as $type => $theme) {
            if($theme) {
                foreach ($templates->getThemeTemplates() as $k => $name) {
                    $theme->addFile($name, '');
                    $theme->save();
                    $this->logAction('template_add', $name);
                    $content = $templates->getTemplatePluginContent($k);
                    $file_path = $theme->getPath().'/'.$name;
                    /* Если файл не был создан ранее, создаем */
                    if (!file_exists($file_path)) {
                        waFiles::write($file_path, $content);
                    }
                }
            }
        }
    }
}
