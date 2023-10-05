<?php

class shopTageditorPluginFrontendTagAction extends shopFrontendTagAction
{
    public function execute()
    {
        $tag_name = waRequest::param('tag');
        $products_collection = new shopProductsCollection('tag/'.$tag_name);

        //take a subcollection into account
        $collection_type = waRequest::param('collection');
        if ($collection_type) {
            $collection_value = waRequest::param('value');
            switch ($collection_type) {
                case 'category':
                    $hash = 'category/'.$collection_value;
                    $shop_category_model = new shopCategoryModel();
                    $category = $shop_category_model->select('*')->where('id = ?', $collection_value)->fetchAssoc();
                    $subcollection_title = sprintf(_wp('category “%s”'), $category['name']);

                    $breadcrumbs = array();
                    $breadcrumbs_path = array_reverse($shop_category_model->getPath($collection_value));
                    $breadcrumbs_path[] = $category;
                    foreach ($breadcrumbs_path as $item) {
                        $breadcrumbs[] = array(
                            'url'  => wa()->getRouteUrl('/frontend/category', array('category_url' => waRequest::param('url_type') == 1 ? $item['url'] : $item['full_url'])),
                            'name' => $item['name']
                        );
                    }
                    break;
                case 'search':
                    $hash = 'search/query='.$collection_value;
                    $subcollection_title = sprintf(_wp('search “%s”'), $collection_value);

                    $breadcrumbs = array(
                        array(
                            'url' => wa()->getRouteUrl('/frontend/search').'?query='.urlencode($collection_value),
                            'name' => $collection_value,
                        )
                    );
                    break;
                case 'tag':
                    $subcollection_tag = shopTageditorPluginModels::tag()->getByUrl(waRequest::param('value'));
                    $hash = 'tag/'.$subcollection_tag['name'];
                    $subcollection_title = sprintf(_wp('tag “%s”'), $subcollection_tag['name']);

                    $breadcrumbs = array(
                        array(
                            'url' => wa()->getRouteUrl('/frontend/tag', array('plugin' => 'tageditor', 'url' => $collection_value), true),
                            'name' => $subcollection_tag['name'],
                        )
                    );
                    break;
            }

            if (!empty($hash)) {
                $collection_collection = new shopProductsCollection($hash);

                $collection_products = $collection_collection->getProducts('id', 0, $collection_collection->count());
                if ($collection_products) {
                    $collection_products = array_keys($collection_products);
                    $products_collection->addWhere('id IN ('.implode(',', $collection_products).')');
                }
            }
        }

        $this->setCollection($products_collection);

        $tag = shopTageditorPlugin::tag();

        $title = empty($tag['title']) ? $tag_name : $this->view->fetch('string:'.$tag['title']);
        if (!empty($subcollection_title)) {
            $title = sprintf(_wp('%s (%s)'), $title, $subcollection_title);
        }
        $this->view->assign('title', $title, true);

        foreach (array('title', 'description') as $meta) {
            $value = trim(ifset($tag['og_'.$meta]));
            if (strlen($value)) {
                $this->getResponse()->setOGMeta('og:'.$meta, $this->view->fetch('string:'.$value));
            }
        }

        $this->getResponse()->setOGMeta('og:type', 'product.group');
        $this->getResponse()->setOGMeta('og:url', wa()->getConfig()->getHostUrl().wa()->getConfig()->getRequestUrl(false, true));
        $this->getResponse()->setOGMeta('og:locale', wa()->getLocale());

        $this->getResponse()->setTitle($tag['title']);
        $this->addCanonical();

        if (!empty($breadcrumbs)) {
            $this->view->assign('breadcrumbs', $breadcrumbs);
        }

        /**
         * @event frontend_search
         * @return array[string]string $return[%plugin_id%] html output for search
         */
        $this->view->assign('frontend_search', wa()->event('frontend_search'));
        $this->setThemeTemplate('search.html');
    }
}
