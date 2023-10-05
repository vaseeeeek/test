<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginBackendLayout extends waLayout
{

    public function execute()
    {
        $plugin = wa()->getPlugin('productsets');
        $this->assign('js_locale_strings', (new shopProductsetsPluginHelper())->getJsLocaleStrings());
        $this->assign('plugin_url', $plugin->getPluginStaticUrl());
        $this->assign('version', $plugin->getVersion());
    }

}
