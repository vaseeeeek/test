<?php
class shopFavoritesPluginFrontendFavoritesAction extends shopFrontendAction
{

    public function execute()
    {
        $user = new waAuthUser();
        if (!$user->isAuth()) {
            throw new waException('Page not found', 404);
        }

        $this->getResponse()->setTitle('Избранное');
        $this->setLayout(new shopFrontendLayout());
        $this->layout->assign('breadcrumbs', $this->getBreadcrumbs());

        $model_favorites = new shopFavoritesModel();
        $product_ids = $model_favorites->getFavorites($user->getId());

        $collection = new shopProductsCollection();
        if ($product_ids) {
            $collection->addWhere('id IN ('.  implode(', ', $product_ids).')');
        } else {
            $collection->addWhere('id = 0');
        }
        $this->setCollection($collection);

        $this->view->assign('title', 'Избранное');
        $frontend_search = wa()->event('frontend_search');

        $this->view->assign('frontend_search', $frontend_search);
        waSystem::popActivePlugin();

        $t = wa()->getSetting('template', '', array('shop', 'favorites'));
        if (!$t) {
            $t = 'search.html';
        }
        $this->setThemeTemplate($t);
    }

    public function getBreadcrumbs()
    {
        return array(
            array(
                'name' => _w('My account'),
                'url' => wa()->getRouteUrl('/frontend/my')
            )
        );
    }
}