<?php

class shopBuy1clickGenerateCSSFile extends shopBuy1clickGenerateFile {

    private $storefront_id;

    public function __construct($storefront_id, $path = null)
    {
        $this->storefront_id = $storefront_id;
        parent::__construct(self::getSafeFileName($storefront_id) , '.css', '/css/', $path);
    }

    public function compileAndSave() {
        $settings = shopBuy1clickPlugin::getContext()->getSettingsService();
        $product = $settings->getSettings($this->storefront_id, 'product')->toArray();
        $cart = $settings->getSettings($this->storefront_id, 'cart')->toArray();
        $wa = wa();
        $view = $wa->getView();
        $view->assign(array(
            'product_settings' => $product,
            'cart_settings' => $cart
        ));
        $data = $view->fetch($wa->getAppPath('plugins/buy1click/templates/Style.html', shopBuy1clickPlugin::SHOP_ID));

        $this->save($data);

        return $data;
    }


    public static function getSafeFileName($storefront_id) {
        $storefront = str_replace(['/', '*'], '_', $storefront_id);
        return 'style_' . $storefront;
    }
}