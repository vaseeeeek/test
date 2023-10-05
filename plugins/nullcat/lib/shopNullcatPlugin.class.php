<?php

/**
 * Class shopNullcatPlugin
 */
class shopNullcatPlugin extends shopPlugin
{
    /**
     * @return array|bool
     * @throws waDbException
     * @throws waException
     */
    public function noCategories()
    {
        if (!$this->getSettings('enabled')) {
            return false;
        }
        //TODO get from collection
        $model = new shopProductModel();
        $products = $model->query('SELECT id FROM ' . $model->getTableName() . ' WHERE category_id IS NULL');
        $count = $products->count();
        $html = '<li id="nullcat-plugin" class="gray"><span class="counters"><span class="count">'.$count.'</span>
        </span><a href="#/products/hash=nullcat"><i class="icon16 folders"></i><span class="name">'
            . _wp('Without categories') .
        '</span></a></li>';
        $html .= '<script>let nullcat = $("#nullcat-plugin").detach();
            $(window).on("load", function(){$(nullcat).prependTo($("ul.menu-v:first","#s-category-list"))} );</script>';

        return [
            'sidebar_section' => $html
        ];
    }

    /**
     * @param $params
     * @return bool
     * @throws waException
     */
    public function noCategoriesCollection($params)
    {
        if (!$this->getSettings('enabled')) {
            return false;
        }
        /* @var shopProductsCollection $collection */
        $collection = $params['collection'];
        $hash = $collection->getHash();

        if ($hash[0] === 'nullcat') {
            $collection->addWhere('category_id IS NULL');
            if ($params['auto_title']) {
                $collection->addTitle(_wp('Without categories'));
            }
        }
        return true;
    }
}
