<?php

class shopTageditorPluginFrontendSitemapController extends waController
{
    public function execute()
    {
        $sitemap_config = new shopTageditorPluginSitemapConfig();
        $sitemap_config->display();
    }
}
