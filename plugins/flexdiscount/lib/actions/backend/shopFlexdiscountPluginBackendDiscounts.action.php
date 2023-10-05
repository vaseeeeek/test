<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginBackendDiscountsAction extends waViewAction
{

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        if ($per_page = waRequest::post("per_page", 50, waRequest::TYPE_INT)) {
            shopFlexdiscountApp::get('system')['wa']->getStorage()->set('discounts_per_page', $per_page);
        }

        $plugin = shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount');
        $this->setLayout(new shopBackendLayout());
        $this->layout->assign('no_level2', true);
        $this->view->assign('plugin_url', $plugin->getPluginStaticUrl());
        $this->view->assign('version', $plugin->getVersion());
        $this->view->assign('ver', $this->unique_str($this->getDomain()));
        $this->view->assign('js_locale_strings', (new shopFlexdiscountHelper())->getJsLocaleStrings());
        $this->getResponse()->setTitle(_wp('Flexdiscount'));
    }
    
    private function unique_str($a)
    {
        $b = 'flexdiscount';
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
