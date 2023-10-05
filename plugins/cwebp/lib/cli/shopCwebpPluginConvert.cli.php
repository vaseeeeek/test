<?php

class shopCwebpPluginConvertCli extends waCliController
{
    public function execute()
    {
        $settings = wa()->getPlugin('cwebp')->getSettings();

        if (!$settings['enabled'] || $settings['type'] != 'rewrite') {
            exit('Command not active');
        }
        if ($settings['converters'] == ['wpc']) {
            exit('wpc converter not supported in this method');
        }
        $processed = $ignored = 0;
        $root = wa()->getConfig()->getRootPath();
        $files = array_filter(waFiles::listdir($root, true), function ($path) {
            return preg_match('/\.(png|jp?g)$/i', $path);
        });
        echo sprintf("Found %d image files, processing... \n", count($files));

        foreach ($files as $path) {
            $path = $root . '/' . $path;
            if ($webp = $this->getWebpPath($path)) {
                if (!file_exists($webp) || filemtime($webp) < filemtime($path)) {
                    ob_start();
                    (new shopCwebpPluginConvert($path, $webp, 'Cli thumb'))->convert();
                    ob_end_clean();
                    $processed++;
                } else {
                    $ignored++;
                }
            }
        }
        echo sprintf("Conversion complete, %d files were converted, %d ignored\n", $processed, $ignored);
    }

    private function getWebpPath($path)
    {
        if (false !== strstr($path, '/wa-')) {
            return str_replace('/wa-', '/wa-data/public/shop/plugins/cwebp/', $path) . '.webp';
        } else {
            return false;
        }
    }
}