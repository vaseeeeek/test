<?php

class shopTageditorPluginSitemapIndexConfig extends waSitemapConfig
{
    public function execute()
    {
        $shop_routes = wa()->getRouting()->getByApp('shop');
        $domain = wa()->getRouting()->getDomain();
        $domain_routes = $shop_routes[$domain];

        if (!$domain_routes) {
            return;
        }

        foreach ($domain_routes as $domain_route) {
            if (!empty($domain_route['private'])) {
                continue;
            }

            echo '<sitemap>
    <loc>'.wa()->getRouteUrl('shop/frontend/sitemap', array('plugin' => 'tageditor'), true, $domain, $domain_route['url']).'</loc>
    <lastmod>'.date('c').'</lastmod>
</sitemap>';
        }
    }

    public function display()
    {
        $response = wa()->getResponse();
        $response->addHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->sendHeaders();

        echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="'.wa('shop')->getPlugin('tageditor')->getPluginStaticUrl(true).'xsl/sitemap/sitemap-index.xsl"?>
<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"
         xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $this->execute();

        echo '</sitemapindex>';
    }
}
