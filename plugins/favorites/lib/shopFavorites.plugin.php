<?php
/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
class shopFavoritesPlugin extends shopPlugin
{
    /**
     * @var array
     */
    protected static $product_ids;
    /**
     * @var shopFavoritesPlugin
     */
    protected static $plugin;
    protected static $url;
    /**
     * добавляет ссылки добавить/удалить избранное в карточке товаров
     * @param frontendProduct $product
     * @return array
     */
    public function frontendProduct($product)
    {
        if (!wa()->getUser()->isAuth()) {
            return;
        }
        $hook = $this->getSettings('hook');
        if ($hook) {
            return array(
                $hook => self::product($product['id'])
            );
        }
    }

    public static function inFavorites($product_id)
    {
        if (self::$product_ids === null) {
            $favorites_model = new shopFavoritesModel();
            self::$product_ids = $favorites_model->getFavorites(wa()->getUser()->getId());
        }
        return in_array($product_id, self::$product_ids);
    }

    public static function product($product_id, $link = true)
    {
        if (!wa()->getUser()->isAuth()) {
            return '';
        }
        if (is_array($product_id) || $product_id instanceof shopProduct) {
            $product_id = $product_id['id'];
        }
        if (self::$product_ids === null) {
            $favorites_model = new shopFavoritesModel();
            self::$product_ids = $favorites_model->getFavorites(wa()->getUser()->getId());
        }
        if (self::$plugin === null) {
            self::$plugin = wa('shop')->getPlugin('favorites');
        }
        if (!self::$url) {
            self::$url = wa()->getRouteUrl('shop/frontend/my').'favorites/';
        }
        $html = '<div class="shop_favorites" data-product-id="'.$product_id.'">';
        if (in_array($product_id, self::$product_ids)) {
            $html .= self::$plugin->delHtml(self::$url, count(self::$product_ids), $link && (waRequest::param('plugin') != 'favorites'));
        } else {
            $html .= '<a href="'.self::$url.'add/" class="add">'.self::$plugin->getSettings('add').'</a>';
        }
        $html .= '</div>';
        return $html;
    }

    public function delHtml($url, $count, $link = true)
    {
        $code = $this->getSettings('del');
        if (strpos($code, 'class="del"') !== false) {
            if (!$link) {
                $code = preg_replace('/<a\shref="%url%"[^>]*>.*?<\/a>/uis', '', $code);
            }
            return str_replace(array('%url%', '%count%'), array($url, '<span class="count">'.$count.'</span>'), $code);
        } else {
            $code = '<a href="'.$url.'del/" class="del">'.$code.'</a>';
            if ($link) {
                $code .= ' <a href="'.$url.'">'.$this->getSettings('my').' <span class="count">'.$count.'</span></a>';
            }
            return $code;
        }
    }

    /**
     * добавляет урл на избранное в ЛК
     * @return string
     */
    public function frontendMy()
    {
        $favorites_model = new shopFavoritesModel();
        $c = $favorites_model->countByField(array('contact_id' => wa()->getUser()->getId()));
        return '<a href="'.wa()->getRouteUrl('shop/frontend/my').'favorites/">'.$this->getSettings('my').' ('.$c.')</a>';
    }

    public function frontendMyNav()
    {
        $favorites_model = new shopFavoritesModel();
        $c = $favorites_model->countByField(array('contact_id' => wa()->getUser()->getId()));
        return '<a href="'.wa()->getRouteUrl('shop/frontend/my').'favorites/">'.$this->getSettings('my').' ('.$c.')</a>';
    }

    /**
     * подставляет JS в header
     * @return boolean|string
     */
    public function frontendHead()
    {
        return '<script type="text/javascript">$(function(){$(document).on("click",".shop_favorites a.add,.shop_favorites a.del",function(){var b=$(this).closest(".shop_favorites");$.post($(this).attr("href"),{product_id:b.data("product-id")},function(a){"ok"==a.status?(b.html(a.data.html),$(".shop_favorites .count").html(a.data.count)):alert(a.errors)},"json");return!1})})</script>';
    }

    public static function getProducts($limit = null)
    {
        $favorites_model = new shopFavoritesModel();
        $product_ids = $favorites_model->getFavorites(wa()->getUser()->getId());
        if ($product_ids) {
            $collection = new shopProductsCollection('id/'.implode(',', $product_ids));
            return $collection->getProducts('*', 0, $limit);
        } else {
            return array();
        }
    }

    public static function countProducts()
    {
        $favorites_model = new shopFavoritesModel();
        return $favorites_model->countByField(array('contact_id' => wa()->getUser()->getId()));
    }
}
