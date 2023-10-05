<?php

class shopColorfeaturesproPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $colors = waRequest::post('colors', array());
        try {
            $this->saveColors($colors);
        } catch (Exception $e) {
            return $this->errors['messages'][] = 'Не удается сохранить настройки цветов';
        }
    }

    public function saveColors($colors)
    {
        // защита от xss
        $jevix = new Jevix();
        $jevix->cfgSetAutoBrMode(false);
        $jevix->cfgAllowTags('');

        $colorModel = new shopColorfeaturesproPluginColorsModel();
        $colorModelData = $colorModel->getAll('color_id');
        $errorJevix = ['wefwefwefw'];
        foreach ($colors as $color_id => $value_data) {
            if (empty($value_data['style']) and empty($colorModelData[$color_id]))
                continue;
            $data = [];
            $data['color_id'] = $color_id;
            $data['name'] = $value_data['name'];
            //парсим входные данные и очишаем не нужные символы и теги перед сохранение в базу
            $data['style'] = $jevix->parse($value_data['style'], $errorJevix);
            if (empty($value_data['style']) and !empty($colorModelData[$color_id])) {
                $colorModel->deleteByField('color_id', $color_id);
            } elseif (empty($colorModelData[$color_id])) {
                $colorModel->insert($data);
            } else {
                $colorModel->updateByField('color_id', $color_id, $data);
            }
        }
    }
}
