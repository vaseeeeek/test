<?php

require_once __DIR__ . '/../vendors/autoload.php';

use WebPConvert\Convert\ConverterFactory;
use WebPConvert\Convert\Converters\Stack;

class shopCwebpPluginStack extends Stack
{
    public static function check()
    {
        $source = wa()->getAppPath('plugins/cwebp/img/cwebp.jpg', 'shop');
        $destination = wa()->getTempPath('cwebp/cwebp.webp', 'shop');
        $c = self::createInstance($source, $destination, self::getMinimalOptions());
        return $c->checkOperationalityInternal();
    }

    public function checkOperationalityInternal()
    {
        $converters = [];
        $all = shopCwebpPluginConvert::getAllConverters();
        foreach ($all as $k => $c) {
            $id = $c['value'];
            try {
                $converter = ConverterFactory::makeConverter(
                    $id,
                    $this->source,
                    $this->destination,
                    self::getMinimalOptions()
                );
                $converter->checkOperationality();
                $converter->checkConvertability();
                $converters[$id] = $id;
            } catch (\Exception $e) {
            }
        }
        return $converters;
    }

    public static function getMinimalOptions()
    {
        return [
            'api-key' => shopCwebpPluginConvert::WPC_API_KEY,
            'api-url' => shopCwebpPluginConvert::WPC_API_URL,
        ];
    }

    protected function doActualConvert()
    {
        if (strtolower(pathinfo($this->source, PATHINFO_EXTENSION)) === 'png') {
            $this->options['converters'] = array_diff($this->options['converters'], ['gd']);
            if (!in_array('wpc', $this->options['converters'])) {
                $this->options['converters'][] = 'wpc';
            }
        }
        parent::doActualConvert();
    }
}
