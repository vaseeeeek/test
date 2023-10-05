<?php

class shopTageditorPlugin extends shopPlugin
{
    const BACKEND_TAG_LIMIT = 1000;

    /**
     * EVENT HANDLERS
     */

    public function backendProducts()
    {
        $result = array();

        foreach (array(
            'sidebar_top_li',
            'sidebar_section',
        ) as $type) {
            $result[$type] = $this->getBackendProductsTemplate($type);
        }

        return $result;
    }

    private function getBackendProductsTemplate($type)
    {
        static $template;
        if (!$template) {
            $template = $this->path.'/templates/includes/products.html';
        }

        $view = wa()->getView();
        $view->assign(array(
            'type' => $type,
        ));

        switch ($type) {
            case 'sidebar_section':
                $view->assign(array(
                    'version'                   => $this->getVersion(),
                    'js_shop_cloud_auto_update' => $this->getSettings('shop_cloud_auto_update') ? 'true': 'false',
                ));
                break;
        }

        return $view->fetch($template);
    }

    public function frontendHead()
    {
        if (waRequest::param('action') != 'tag') {
            return;
        }

        $tag = self::tag();
        foreach (array('title', 'keywords', 'description') as $meta) {
            $meta_value = ifset($tag['meta_'.$meta]);
            if (strlen($meta_value)) {
                wa()->getResponse()->setMeta($meta, wa()->getView()->fetch('string:'.$meta_value));
            }
        }
    }

    public function frontendSearch()
    {
        if ($this->getSettings('seo_text_location') == 'hook') {
            return self::seoText();
        }
    }

    public function sitemap($params)
    {
        if (!$this->getSettings('add_sitemap')) {
            return;
        }

        if ((bool) (int) $this->getSettings('custom_sitemap_url')) {
            return;
        }

        $tags = shopTageditorPluginHelper::getSitemapItems();

        if (!$tags) {
            return;
        }

        $sitemap = array();
        foreach ($tags as $tag_id => $tag) {
            $sitemap[] = array(
                'loc'        => wa()->getRouteUrl('shop/frontend/tag', array('tag' => urlencode($tag['url'])), true),
                'lastmod'    => date('c', strtotime($tag['lastmod'])),
                'changefreq' => $this->getSettings('sitemap_changefreq'),
                'priority'   => round(intval($this->getSettings('sitemap_priority'))/100, 2),
            );
        }

        return $sitemap;
    }

    public function productSave($params)
    {
        if ($this->getSettings('shop_cloud_auto_update')) {
            $index = new shopTageditorPluginIndex($params['data']['id']);
            $index->updateProducts();
        }
    }

    public function productDelete($params)
    {
        if ($this->getSettings('shop_cloud_auto_update')) {
            $index = new shopTageditorPluginIndex($params['ids']);
            $index->updateProducts();
        }
    }

    public function reset()
    {
        foreach (array(
            shopTageditorPluginModels::tag(),
            shopTageditorPluginModels::indexTag(),
            shopTageditorPluginModels::indexProductTags(),
        ) as $model) {
            $model->truncate();
        }

        waFiles::delete(wa('shop')->getDataPath('plugins/tageditor', true), true);
    }

    public function rightsConfig(waRightConfig $config)
    {
        $config->addItem('tageditor_header', _wp('Tag editor'), 'header');
        $config->addItem('tageditor_save_default_values', _wp('can save common values for all tags'));
    }

    public function routing($route = array())
    {
        if ($this->getSettings('custom_cloud_urls') == 'subcollection') {
            $result = array(
                'tag/<url>/<collection>/<value>/' => 'frontend/tag',
                'tag/<url>/' => 'frontend/tag',
            );
        } else {
            $result = array(
                'tag/<url>/' => 'frontend/tag',
            );
        }

        if ((bool) (int) $this->getSettings('add_sitemap') && (bool) (int) $this->getSettings('custom_sitemap_url')) {
            $result['tageditor/sitemap/'] = 'frontend/sitemap';
        }

        //available either only for backend code or anywhere, if enabled in both settings options
        if (wa()->getEnv() == 'backend'
        || (bool) (int) $this->getSettings('add_sitemap') && (bool) (int) $this->getSettings('custom_sitemap_url')) {
            $result['tageditor/sitemap-index/'] = 'frontend/sitemapIndex';
        }

        return $result;
    }

    /**
     * OVERRIDDEN
     */

    public function saveSettings($settings = array())
    {
        $shop_cloud_auto_update = $this->getSettings('shop_cloud_auto_update');

        //Clear index tables every time auto-update setting is toggled
        if (ifset($settings['shop_cloud_auto_update']) != $shop_cloud_auto_update) {
            shopTageditorPluginModels::indexProductTags()->truncate();
            shopTageditorPluginModels::indexTag()->truncate();
        }

        parent::saveSettings($settings);
    }

    public function getVersion()
    {
        $version = parent::getVersion();
        if (waSystemConfig::isDebug()) {
            $version .= '.'.time();
        }
        return $version;
    }

    /**
     * SETTINGS CONTROLS
     */

    public static function settingsTopHint()
    {
        return self::getSettingsField('top');
    }

    public static function settingsHeaderSitemap()
    {
        return self::getSettingsField('sitemap');
    }

    public static function settingsHeaderCustomCloud()
    {
        return self::getSettingsField('custom_cloud', 'header');
    }

    public static function settingsHintCustomCloud()
    {
        return self::getSettingsField('custom_cloud', 'control');
    }

    public static function settingsHeaderShopCloud()
    {
        return self::getSettingsField('shop_cloud', 'header');
    }

    public static function settingsHintShopCloud()
    {
        return self::getSettingsField('shop_cloud', 'control');
    }

    public static function settingsSitemapCustomUrl($name, $params)
    {
        return waHtmlControl::getControl(waHtmlControl::CHECKBOX, 'custom_sitemap_url', array(
            'namespace' => 'shop_tageditor',
            'description' => sprintf(
                _wp('Publish tag URLs in a separate Sitemap file at <a href="%1$s" target="_blank">%1$s</a> <i class="icon16 new-window"></i>.'),
                wa()->getRouteUrl('/frontend/sitemap-index/', array('plugin' => 'tageditor'), true)
            )
                .'<br>'
                ._wp('Use this option if you have a large number of tags and need to specify a separate Sitemap file URL in <tt>robots.txt</tt>.'),
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'value' => 1,
            'checked' => (bool) (int) $params['value'],
        ));
    }

    private static function getSettingsField($id, $type = null)
    {
        static $template;
        if (!$template) {
            $template = wa()->getAppPath('plugins/tageditor/templates/includes/settings.html', 'shop');
        }

        $view = wa()->getView();
        $view->assign(compact('id', 'type'));

        return $view->fetch($template);
    }

    /**
     * STATIC HELPERS
     */

    public static function tag($field = null)
    {
        waConfig::set('is_template', false);

        //return tag data in frontend only for tag's action
        if (waRequest::param('action') != 'tag') {
            return;
        }

        //cache tag data for future calls
        static $tag;
        if (is_null($tag)) {
            $tag = shopTageditorPluginModels::tag()->getByUrl(waRequest::param('url'));
        }

        //if no tag was found, return nothing
        if (!is_array($tag) || empty($tag['tag_id'])) {
            return;
        }

        //apply default values
        $default_values = shopTageditorPluginHelper::getDefaultValues();
        if ($default_values && is_array($default_values)) {
            foreach ($default_values as $default_field_name => $default_value) {
                if (!isset($tag[$default_field_name])) {
                    $tag[$default_field_name] = $default_value;
                }
            }
        }

        //copy values from aliase fields
        $field_aliases = shopTageditorPluginHelper::getFieldAliases($tag);
        if ($field_aliases) {
            foreach ($field_aliases as $field_name => $field_alias) {
                $tag[$field_name] = ifset($tag[$field_alias]);
            }
        }

        if (empty($tag['meta_title'])) {
            $tag['meta_title'] = sprintf('%s — %s', $tag['name'], wa('shop')->getConfig()->getGeneralSettings('name'));
        }

        if ($field) {
            return isset($tag[$field]) ? wa()->getView()->fetch('string:'.$tag[$field]) : '';
        } else {
            return $tag;
        }
    }

    /**
     * SEO text
     */

    public static function seoText()
    {
        if ((bool) (int) wa('shop')->getPlugin('tageditor')->getSettings('seo_first_page') && waRequest::get('page', 1, waRequest::TYPE_INT) > 1) {
            return;
        }

        return self::tag('description');
    }

    public static function seoTextExtra()
    {
        if ((bool) (int) wa('shop')->getPlugin('tageditor')->getSettings('seo_first_page') && waRequest::get('page', 1, waRequest::TYPE_INT) > 1) {
            return;
        }

        return self::tag('description_extra');
    }

    /**
     * Custom tag URLs
     */

    public static function tags($tags)
    {
        waConfig::set('is_template', false);

        $tag_is_array = is_array(reset($tags));

        if ($tag_is_array) {
            $tag_names = array();
            foreach ($tags as $tag) {
                if (isset($tag['name'])) {
                    $tag_names[] = $tag['name'];
                }
            }
        } else {
            $tag_names = $tags;
        }

        if ($tag_names) {
            $tag_data = shopTageditorPluginModels::tag()->getByName($tag_names);
            foreach ($tags as &$tag) {
                $tag_name = $tag_is_array ? ifset($tag['name']) : $tag;
                if (!is_null($tag_name)) {
                    $custom_url = strlen(ifset($tag_data[$tag_name]['url'])) ? $tag_data[$tag_name]['url'] : null;
                    if ($tag_is_array) {
                        if (!is_null($custom_url)) {
                            $tag['uri_name'] = $custom_url;
                        }
                    } else {
                        $tag = array(
                            'name'     => $tag,
                            'uri_name' => !is_null($custom_url) ? $custom_url : $tag,
                        );
                    }
                }
            }
            unset($tag);
        }

        return $tags;
    }

    /**
     * Custom tag cloud
     */

    public static function cloud($sort = 'name', $count = null)
    {
        waConfig::set('is_template', false);

        if (waRequest::isXMLHttpRequest() && waRequest::get('page', 1, waRequest::TYPE_INT) > 1) {
            return;
        }

        $view = wa()->getView();
        $vars = $view->getVars();
        $model = new waModel();
        $subcollection_urls = wa('shop')->getPlugin('tageditor')->getSettings('custom_cloud_urls') == 'subcollection';
        $custom_cloud_show_everywhere = (bool) (int) wa('shop')->getPlugin('tageditor')->getSettings('custom_cloud_show_all');

        /**
         * First try to get collection name from plugin params, if it's a subcollection filter.
         * If empty, try then to get collection name from app params.
         */
        $collection = array(
            'name' => waRequest::param('collection', waRequest::param('action')),
        );

        if (in_array($collection['name'], array('category', 'search', 'tag'))) {
            switch ($collection['name']) {
                case 'category':
                    $collection['value'] = waRequest::param('value', 0, waRequest::TYPE_INT);
                    if (!$collection['value'] && isset($vars['category']['id'])) {
                        $collection['value'] = (int) $vars['category']['id'];
                    }
                    $collection['url'] = $collection['value'];
                    $hash = 'category/'.$collection['value'];
                    break;
                case 'search':
                    $collection['value'] = waRequest::param('value');
                    if (!strlen($collection['value'])) {
                        $collection['value'] = waRequest::get('query');
                    }
                    $collection['url'] = $collection['value'];
                    $hash = 'search/query='.$collection['value'];
                    break;
                case 'tag':
                    if (strlen(waRequest::param('collection'))) {
                        //subcollection page
                        $collection_tag = shopTageditorPluginModels::tag()->getByUrl(waRequest::param('value'));
                        $collection['value'] = $collection_tag['name'];
                        $collection['url'] = waRequest::param('value');
                    } else {
                        //collection page
                        $collection['value'] = waRequest::param('tag');
                        $collection['url'] = waRequest::param('url');
                    }
                    $hash = 'tag/'.$collection['value'];
                    break;
            }
        } else {
            $collection['name'] = null;
        }

        $where = array();
        $order = array();
        $params = array();

        if (empty($hash)) {
            //home page or any other page without recognized hash

            $view_helper = new waViewHelper(wa()->getView());
            $current_url = $view_helper->currentUrl(false, true);
            $shop_home_url = wa()->getRouteUrl('shop/frontend', waRequest::param());

            if ($current_url == $shop_home_url || $custom_cloud_show_everywhere) {
                $where[] = 't.count > 0';
            } else {
                return;
            }
        } else {
            //recognized product listing page

            $product_collection = new shopProductsCollection($hash);
            $products = $product_collection->getProducts('id', 0, $product_collection->count(), false);

            if ($products) {
                $where[] = 'pt.product_id IN(i:product_ids)';
                $params['product_ids'] = array_keys($products);
            } else {
                return;
            }
        }

        if (in_array($sort, array('abc', 'name'))) {
            //support 'abc' for backward compatibility
            $order[] = 't.name';
        } elseif ($sort == 'count') {
            $order[] = 't.count DESC, t.name';
        }

        //do not show selected tags in custom tag cloud
        $selected_tags_urls = array();

        //current tag page
        if ($collection['name'] == 'tag') {
            $selected_tags_urls[] = $collection['url'];
        }

        //tag selected in a subcollection's custom cloud
        if (strlen(waRequest::param('collection'))) {
            $selected_tags_urls[] = waRequest::param('url');
        }

        if ($selected_tags_urls) {
            $where[] = 'NOT (
                tt.url IS NOT NULL
                    AND tt.url IN (s:selected_tags_urls)
                OR t.name IN (s:selected_tags_urls)
            )';
            $params['selected_tags_urls'] = $selected_tags_urls;
        }

        //generate & execute SQL to get displayed tags
        $where = $where ? sprintf('WHERE (%s)', implode(') AND (', $where)) : '';
        $order = $order ? sprintf('ORDER BY %s', implode(', ', $order)) : '';
        $limit = abs($count = (int) $count) ? sprintf('LIMIT %u', $count) : '';

        $tags = $model->query(
            "SELECT
                t.name as name,
                IF(tt.url IS NOT NULL AND LENGTH(tt.url) > 0, tt.url, t.name) as url
            FROM shop_tag t
            LEFT JOIN shop_tageditor_tag tt
                ON tt.id = t.id
            JOIN shop_product_tags pt
                ON pt.tag_id = t.id
            $where
            GROUP BY t.id
            $order
            $limit",
            $params
        )->fetchAll();

        if (empty($tags)) {
            return;
        }

        $view->assign('tags', $tags);
        $view->assign('subcollection_urls', $subcollection_urls);

        if ($subcollection_urls) {
            $view->assign('collection', $collection);
        }

        return $view->fetch(wa()->getAppPath('/plugins/tageditor/templates/includes/cloud.html', 'shop'));
    }

    /**
     * Returns tag cloud array with product type and status taken into account.
     *
     * @param int|null $limit Tag count limit.
     * @param array|null $params Parameters for displaying product tags in website sections powered by other apps than Shop-Script,
     *  in whose route settings Store's type IDs are not available or selected. Only one of the parameters at a time makes sense, but 'all_types' has a higher priority over
     *  'route' for simplicity. In Shop-Script storefronts these parameters are ignored.
     *  $params['all_types'] bool|null True or equivalent, if tags available for all product types must be displayed.
     *  $params['route'] string|null Exlicitly specified route URL whose settings' product types must be taken into account.
     * @return array Array of tags in usual format.
     */
    public static function shopCloud($limit = null, $params = null)
    {
        waConfig::set('is_template', false);

        try {
            if (waRequest::param('app') == 'shop') {
                $type_ids = waRequest::param('type_id');
            } else {
                if (!empty($params['all_types'])) {
                    //Show tags for all product types
                    $type_ids = null;
                } else {
                    //Either use product types from specified route or try to get them from existing routes' settings.

                    //Read routing config first to avoid an extra SQL query, which might return only 1 existing product type right away.
                    //Or might not, and would thus unreasonably increase the frontend page generation time.

                    $routing = wa()->getRouting();
                    $domain = $routing->getDomain();
                    $shop_routes = $routing->getByApp('shop');

                    $route = trim(ifset($params['route']));
                    if (strlen($route)) {
                        //getting product types from specified route's settings
                        foreach ($shop_routes[$domain] as $domain_shop_route) {
                            if ($domain_shop_route['url'] == $route) {
                                $type_ids = ifset($domain_shop_route['type_id']);
                                break;
                            }
                        }
                    }

                    if (!isset($type_ids)) {
                        //If failed above, trying to get product types from existing routes' configs

                        //If 'route' param is specified and we have reached this point,
                        //then the specified route does not exist, and we still have to try and guess product types as if no route were specified.
                        if (count($shop_routes[$domain]) == 1) {
                            //Simple case: there is only 1 shop route in current site
                            $type_ids = ifset($shop_routes[$domain]['type_id']);
                        } else {
                            //Getting all type_id values of multiple routes
                            $routes_type_ids = array();
                            foreach ($shop_routes[$domain] as $domain_shop_route) {
                                //paranoid check
                                if (!isset($domain_shop_route['type_id'])) {
                                    continue;
                                }
                                if (is_array($domain_shop_route['type_id'])) {
                                    sort($domain_shop_route['type_id']);
                                    $route_type_ids = array_values($domain_shop_route['type_id']);
                                    if (!in_array($route_type_ids, $routes_type_ids)) {
                                        $routes_type_ids[] = $route_type_ids;
                                    }
                                } else {
                                    if (!in_array($domain_shop_route['type_id'], $routes_type_ids)) {
                                        $routes_type_ids[] = $domain_shop_route['type_id'];
                                    }
                                }
                            }

                            //If we have collected selected type IDs from current site's Store routes' settings
                            if ($routes_type_ids) {
                                if (count($routes_type_ids) == 1) {
                                    //All routes have the same type_id values
                                    $type_ids = reset($routes_type_ids);

                                    //Convert array with single item '0' to null so that type_id=0 is not passed as parameter to SQL query builder
                                    if (count($type_ids) == 1 && !reset($type_ids)) {
                                        $type_ids = null;
                                    }
                                } else {
                                    //Some routes have different type_id values.
                                    //Show tags for all types!
                                    $type_ids = null;
                                }
                            } else {
                                //There are no 'type_id' values specified for any of the available routes.
                                //Show tags for all product types then.
                                $type_ids = null;
                            }
                        }
                    }
                }
            }

            $limit = (int) $limit;
            $params = array();
            if ($type_ids) {
                $where = 'WHERE ti.type_id IN(i:type_ids)';
                $params['type_ids'] = $type_ids;
            }

            $model = new waModel();
            $tags = $model->query(
                'SELECT
                    ti.tag_id,
                    t.name,
                    IF(tt.url IS NOT NULL AND LENGTH(tt.url) > 0, tt.url, t.name) as url,
                    SUM(ti.count) as count
                FROM shop_tag t
                LEFT JOIN shop_tageditor_tag tt
                    ON tt.id = t.id
                JOIN shop_tageditor_index_tag ti
                    ON ti.tag_id = t.id
                '.ifset($where).'
                GROUP BY ti.tag_id
                ORDER BY '.($limit ? 'count DESC, name' : 'name').'
                '.($limit ? 'LIMIT '.$limit : ''),
                $params
            )->fetchAll();

            if (!$tags) {
                throw new Exception();
            }

            $cloud_max_size = 150;
            $cloud_min_size = 80;
            $cloud_max_opacity = 100;
            $cloud_min_opacity = 30;

            $first = reset($tags);
            $max_count = $min_count = $first['count'];
            foreach ($tags as $tag) {
                if ($tag['count'] > $max_count) {
                    $max_count = $tag['count'];
                }
                if ($tag['count'] < $min_count) {
                    $min_count = $tag['count'];
                }
            }

            $diff = $max_count - $min_count;
            if ($diff > 0) {
                $step_size = ($cloud_max_size - $cloud_min_size) / $diff;
                $step_opacity = ($cloud_max_opacity - $cloud_min_opacity) / $diff;
            }

            foreach ($tags as &$tag) {
                if ($diff > 0) {
                    $tag['size'] = ceil($cloud_min_size + ($tag['count'] - $min_count) * $step_size);
                    $tag['opacity'] = number_format(($cloud_min_opacity + ($tag['count'] - $min_count) * $step_opacity) / 100, 2, '.', '');
                } else {
                    $tag['size'] = ceil(($cloud_max_size + $cloud_min_size) / 2);
                    $tag['opacity'] = number_format($cloud_max_opacity, 2, '.', '');
                }

                $tag['uri_name'] = ifempty($tag['url'], $tag['name']);

                if (strpos($tag['uri_name'], '/') !== false) {
                    $tag['uri_name'] = explode('/', $tag['uri_name']);
                    $tag['uri_name'] = array_map('urlencode', $tag['uri_name']);
                    $tag['uri_name'] = implode('/', $tag['uri_name']);
                } else {
                    $tag['uri_name'] = urlencode($tag['uri_name']);
                }
            }
            unset($tag);

            return $tags;
        } catch (Exception $e) {
            return array();
        }
    }
}
