<?php

class shopTageditorPluginModels
{
    /* plugin models */

    public static function tag()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $model;
        if (!$model) {
            $model = new shopTageditorPluginTagModel();
        }
        return $model;
    }

    public static function indexTag()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $model;
        if (!$model) {
            $model = new shopTageditorPluginIndexTagModel();
        }
        return $model;
    }

    public static function indexProductTags()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $model;
        if (!$model) {
            $model = new shopTageditorPluginIndexProductTagsModel();
        }
        return $model;
    }

    /* shop models */

    public static function shopProduct()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $model;
        if (!$model) {
            $model = new shopProductModel();
        }
        return $model;
    }

    public static function shopTag()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $model;
        if (!$model) {
            $model = new shopTagModel();
        }
        return $model;
    }

    public static function shopProductTags()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $model;
        if (!$model) {
            $model = new shopProductTagsModel();
        }
        return $model;
    }

    /* system models */

    public static function waAppSettings()
    {
        if (waConfig::get('is_template')) {
            return;
        }

        static $model;
        if (!$model) {
            $model = new waAppSettingsModel();
        }
        return $model;
    }
}
