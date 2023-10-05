<?php

class shopCategoryimagePluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $key = array('shop', 'categoryimage');

        $sizes = waRequest::post('sizes');
        if ($types = waRequest::post('size_type', array())) {
            $image_size = waRequest::post('size', array());
            $width = waRequest::post('width', array());
            $height = waRequest::post('height', array());
            foreach ($types as $k => $type) {
                if ($type == 'rectangle') {
                    $w = $this->checkSize($width[$k]);
                    $h = $this->checkSize($height[$k]);
                    if ($w && $h) {
                        $sizes[] = $w.'x'.$h;
                    }
                } else {
                    $size = $this->checkSize($image_size[$k]);
                    if (!$size) {
                        continue;
                    }
                    switch ($type) {
                        case 'crop':
                            $sizes[] = $size.'x'.$size;
                            break;
                        case 'height':
                            $sizes[] = '0x'.$size;
                            break;
                        case 'width':
                            $sizes[] = $size.'x0';
                            break;
                        case 'max':
                            $sizes[] = $size;
                            break;
                    }
                }
            }
        }
        $sizes = array_unique($sizes);

        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set($key, 'sizes', implode(';', $sizes));
        $app_settings_model->set($key, 'sharpen', waRequest::post('sharpen', 0));
    }

    protected function checkSize($size)
    {
        $size = (int)$size;
        if ($size <= 0) {
            return false;
        }
        return $size;
    }
}