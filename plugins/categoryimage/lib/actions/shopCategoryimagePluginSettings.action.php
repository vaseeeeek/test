<?php

class shopCategoryimagePluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $key = array('shop', 'categoryimage');
        $app_settings_model = new waAppSettingsModel();
        $sizes = explode(';', $app_settings_model->get($key, 'sizes', '96'));

        foreach ($sizes as &$size) {
            $size_info = shopImage::parseSize((string)$size);
            $type   = $size_info['type'];
            $width  = $size_info['width'];
            $height = $size_info['height'];
            $size = array('type' => $type, 'text' => $size);
            if ($type == 'max' || $type == 'crop' || $type == 'width') {
                $size['size'] = $width;
            } else if ($type == 'height') {
                $size['size'] = $height;
            } elseif ($type == 'rectangle') {
                $size['size'] = array($width, $height);
            }
        }
        unset($size);
        $this->view->assign('sizes', $sizes);
        $this->view->assign('sharpen', $app_settings_model->get($key, 'sharpen', 1));
    }
}