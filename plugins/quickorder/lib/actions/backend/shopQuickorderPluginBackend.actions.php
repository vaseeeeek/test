<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginBackendActions extends waJsonActions
{

    /**
     * Restore original frontend template
     */
    public function restoreTemplateAction()
    {
        $id = waRequest::post('id');
        $storefront = waRequest::post('storefront', 'all');
        $settings = (new shopQuickorderPluginSettingsModel())->getSettings($storefront);

        $templates = (new shopQuickorderPluginHelper())->getTemplates($settings);
        if (isset($templates[$id])) {
            if (waRequest::post('delete')) {
                (new shopQuickorderPluginSettingsModel())->deleteByField([
                    'storefront' => $storefront,
                    'field' => $id . '_tmpl',
                ]);
            }
            $this->response = file_get_contents($templates[$id]['path']);
        }
    }

    /**
     * Save frontend template
     */
    public function saveTemplateAction()
    {
        $key = waRequest::post('key');
        $template = waRequest::post('template');
        $storefront = waRequest::post('storefront', 'all');

        $model = new shopQuickorderPluginSettingsModel();
        $model->insert([
            'storefront' => $storefront,
            'field' => $key . '_tmpl',
            'value' => $template
        ], 1);
    }
}
