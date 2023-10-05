<?php

class shopFishkiPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        if ($_GET["fishki"]) {
            if ($_GET["fishki"] == 'transclear') {
                $product_model = new shopProductModel();
                $sql = 'UPDATE shop_product SET summary=replace(summary,"\r\n","")';
                $product_model->query($sql);
            }
            if ($_GET["fishki"] == 'transbr') {
                $product_model = new shopProductModel();
                $sql = 'UPDATE shop_product SET summary=replace(summary,"\r\n","\<br\>")';
                $product_model->query($sql);
            }

            header('Location: /webasyst/shop/?action=plugins#/fishki');
        }
    }
}