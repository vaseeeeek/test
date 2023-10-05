<?php

class shopTageditorPluginFrontendSitemapIndexController extends waController
{
    public function execute()
    {
        $sitemap_config = new shopTageditorPluginSitemapIndexConfig();
        $sitemap_config->display();
    }
}
