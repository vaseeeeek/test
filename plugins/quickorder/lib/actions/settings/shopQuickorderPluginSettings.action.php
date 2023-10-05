<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $this->view->assign('status', (new waAppSettingsModel())->get('shop.quickorder', 'status'));
        $this->view->assign('storefronts', (new shopQuickorderPluginHelper())->getStorefronts());
        $this->view->assign('plugin_url', wa()->getPlugin('quickorder')->getPluginStaticUrl());
        $this->view->assign('version', wa()->getPlugin('quickorder')->getVersion());
        $this->view->assign('ver', $this->unique_str($this->getDomain()));
    }

    private function unique_str($a)
    {
        $b = 'quickorder';
        $c = mb_strlen($a, 'UTF-8');
        $d = strlen($b);
        for ($i = 0; $i < $c; $i++) {
            for ($j = 0; $j < $d; $j++) {
                $a[$i] = $a[$i] ^ $b[$j];
            }
        }
        return base64_encode($a);
    }

    private function getDomain()
    {
        $domain = $this->getConfig()->getDomain();
        if (strpos($domain, ":") !== false) {
            $domain = substr($domain, 0, strpos($domain, ":"));
        }
        if (strpos($domain, "/index.php") !== false) {
            $domain = substr($domain, 0, strpos($domain, "/index.php"));
        }

        return $domain;
    }

}