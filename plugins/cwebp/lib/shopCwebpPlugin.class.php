<?php

require __DIR__ . '/vendors/autoload.php';

class shopCwebpPlugin extends shopPlugin
{
    public static $cron = [];

    public static function getPlugin()
    {
        static $plugin;
        if (!$plugin) {
            $plugin = wa('shop')->getPlugin('cwebp');
        }
        return $plugin;
    }

    public function frontendHook()
    {
        if ($this->getSettings('enabled') && wa()->getEnv() === 'frontend' && self::checkWebpSupport() && !isset(wa()->getView()->smarty->registered_filters['output']['shopCwebpPlugin_filter'])) {
            wa()->getView()->smarty->registerFilter('output', [$this, 'filter']);
        }
    }

    public function filter($source, Smarty_Internal_Template $template)
    {
        $re = ';(wa-\w+[^"\'}{\[\]]*?\.(jpg|jpeg|png))([^\.]);i';
        $source = preg_replace_callback($re, function ($m) {
            if ($path = shopCwebpPlugin::getWebpPath($m[1])) {
                return $path . $m[3];
            }
            return $m[0];
        }, $source);
        if (!empty(self::$cron) && $this->getSettings('type') === 'cron') {
            (new shopCwebpPluginQueueModel())->multipleInsertIgnore(self::$cron);
        }
        return $source;
    }

    /**
     * @param $path string path to original image/thumb
     * @return bool|mixed
     * @throws waException
     */
    public static function getWebpPath($path)
    {
        $or_path = wa()->getConfig()->getRootPath() . '/' . $path;
        if (!file_exists($or_path)) {
            if (!file_exists(wa()->getConfig()->getRootPath() . '/' . self::replaceSlashes($path))) {
                return false;
            }
            $path = self::replaceSlashes($path);
            $or_path = wa()->getConfig()->getRootPath() . '/' . $path;
            $slashes = true;
        }
        $ext_id = array_search(pathinfo($path, PATHINFO_EXTENSION), ['jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG']);

        if (($ext_id == 4 || $ext_id == 5) && self::getPlugin()->getSettings('no_png')) {
            return false;
        }
        $path = self::replace_extension($path, 'webp');

        if (false !== strpos($path, 'public/shop/products')) {
            $path = str_replace('shop/products', "shop/products/0$ext_id/webp", $path);
        } elseif (false !== strpos($path, 'wa-') && !self::getPlugin()->getSettings('products_only')) {
            $path = str_replace('wa-', "wa-data/public/shop/products/1$ext_id/webp/", $path);
        } else {
            return false;
        }
        $file_path = wa()->getConfig()->getRootPath() . '/' . $path;
        if (file_exists($file_path) && filemtime($or_path) > filemtime($file_path)) {
            waFiles::delete($file_path);
            waLog::log('Expired file removed: ' . $file_path, 'cwebp.log');
        }
        if (file_exists($file_path) || self::getPlugin()->getSettings('type') === 'ondemand') {
            if (isset($slashes) && $slashes) {
                return self::replaceSlashes($path);
            }
            return $path;
        }
        self::$cron[] = [
            'source' => $or_path,
            'destination' => $file_path,
        ];
        return false;
    }

    public static function replace_extension($filename, $new_extension)
    {
        $info = pathinfo($filename);
        return ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '')
            . $info['filename']
            . '.'
            . $new_extension;
    }

    public function deleteImage($image)
    {
        $path = shopImage::getThumbsPath($image);
        $ext_id = array_search(waFiles::extension($image['original_filename']), ['jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG']);
        $path = str_replace('shop/products', "shop/products/0$ext_id/webp", $path);
        waFiles::delete($path);
    }

    public static function getSettingsButtons()
    {
        $view = wa()->getView();
        return $view->fetch(self::getPlugin()->path . '/templates/settings.html');
    }

    public function saveSettings($settings = array())
    {
        parent::saveSettings($settings);
        $target_path = wa()->getDataPath('products/', true, 'shop');
        $target = $target_path . 'thumb.php';
        $modded_file = '<?php
$file = dirname(__FILE__)."/../../../../"."/wa-apps/shop/lib/config/data/thumb.php";
$webp = dirname(__FILE__)."/../../../../"."/wa-apps/shop/plugins/cwebp/lib/config/thumb.php";

if (false !== strpos($_SERVER["REQUEST_URI"], "/webp/") && file_exists($webp)) {
    include($webp);
} elseif (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
';
        $original_file = '<?php
$file = dirname(__FILE__)."/../../../../"."/wa-apps/shop/lib/config/data/thumb.php";

if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
';
        if ($settings['type'] == 'rewrite' || !$settings['enabled']) {
            waFiles::write($target, $original_file);
        } else {
            waFiles::write($target, $modded_file);
        }

        $settings = $this->getSettings();
        waFiles::write(wa()->getDataPath('plugins/cwebp/settings.json'), json_encode($settings, true));
    }

    public static function replaceSlashes($path)
    {
        if (false !== strpos($path, "\/")) {
            return str_replace("\/", "/", $path);
        }
        return str_replace("/", "\/", $path);
    }

    public static function checkWebpSupport()
    {
        if (stripos(waRequest::getUserAgent(), 'nowebp')) {
            return false;
        }

        // Yes, if webp accepted on page
        if (stripos(waRequest::server('HTTP_ACCEPT'), 'webp')) {
            return true;
        }

        // Yes, if Chrome version 32+
        $re = '/Chrome\/(\d\d)/';
        preg_match($re, waRequest::getUserAgent(), $matches);
        if (isset($matches[1]) && $matches[1] >= 32) {
            return true;
        }

        // Yes, if Firefox version 65+
        $re = '/Firefox\/(\d\d)/';
        preg_match($re, waRequest::getUserAgent(), $matches);
        if (isset($matches[1]) && $matches[1] >= 65) {
            return true;
        }

        // Yes, if iOS version 14+
        $re = '/iPhone OS (\d\d)/';
        preg_match($re, waRequest::getUserAgent(), $matches);
        if (isset($matches[1]) && $matches[1] >= 14) {
            return true;
        }
    }
}
