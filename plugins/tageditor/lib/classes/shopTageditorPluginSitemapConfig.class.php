<?php

class shopTageditorPluginSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        if (wa()->getRouting()->getRoute('private')) {
            return;
        }
        $tags = shopTageditorPluginHelper::getSitemapItems();

        if (!$tags) {
            return;
        }

        $plugin = wa('shop')->getPlugin('tageditor');

        foreach ($tags as $tag) {
            $this->addUrl(
                wa()->getRouteUrl('shop/frontend/tag', array('tag' => urlencode($tag['url'])), true),
                date('c', strtotime($tag['lastmod'])),
                $plugin->getSettings('sitemap_changefreq'),
                round(intval($plugin->getSettings('sitemap_priority'))/100, 2)
            );
        }
    }

    public function display()
    {
        ob_start();
        $this->execute();
        $content = ob_get_clean();

        if (strlen(trim($content))) {
            $system = waSystem::getInstance();
            $system->getResponse()->addHeader('Content-Type', 'application/xml; charset=UTF-8');
            $system->getResponse()->sendHeaders();

            echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="'.wa('shop')->getPlugin('tageditor')->getPluginStaticUrl(true).'xsl/sitemap/sitemap.xsl"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
'.$content.'</urlset>';
        } else {
            throw new waException('', 404);
        }
    }
}
