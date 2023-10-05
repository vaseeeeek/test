<?php

class shopCleanupPluginBackendActions extends waJsonController
{

    public function execute()
    {
        if(waRequest::get('action') == 'deleteOrder') {
            $this->deleteOrder();
        }
    }

    public function __construct()
    {
        if (!$this->getUser()->isAdmin('shop')) {
            throw new waRightsException(_ws('Access denied'));
        }
    }

    public static function deleteOrder()
    {
        $order = waRequest::post('order', null, 'int');
        if (!$order) return;
        $orders = new shopOrderModel();
        $sql_list="SELECT id FROM shop_order WHERE state_id='deleted'";
        $deleted_orders = $orders->query($sql_list)->fetchAll();
        $num=array();
        $sql="Delete from shop_order where shop_order.id = ?;";
            $orders->exec($sql, $order);
        $sql="Delete from shop_order_items where order_id = ?;";
            $orders->exec($sql, $order);
        $sql="Delete from shop_order_log where order_id = ?;";
            $orders->exec($sql, $order);
        $sql="Delete from shop_order_log_params where order_id = ?;";
            $orders->exec($sql, $order);
        $sql="Delete from shop_order_params where order_id = ?;";
            $orders->exec($sql, $order);
        $sql="Delete from shop_affiliate_transaction where order_id = ?;";
            $orders->exec($sql, $order);
        $sql="DELETE wa_transaction, wa_transaction_data FROM wa_transaction INNER JOIN wa_transaction_data WHERE wa_transaction.id = wa_transaction_data.transaction_id and wa_transaction.order_id = ?;";
            $orders->exec($sql, $order);
            $orders->recalculateProductsTotalSales($order);
        return;
    }

    public static function showcategory()
    {
        $category_model = new shopCategoryModel();
        $categories = $category_model->getFullTree('id, left_key, right_key, parent_id, depth, name, count, type, status, include_sub_categories');
        if (!empty($categories)) {
            foreach ($categories as &$item) {
                if (!isset($item['children_count'])) {
                    $item['children_count'] = 0;
                }
                if (!isset($item['total_count'])) {
                    $item['total_count'] = $item['count'];
                }
                if (isset($categories[$item['parent_id']])) {
                    $parentcat = &$categories[$item['parent_id']];
                    if (!isset($parentcat['children_count'])) {
                        $parentcat['children_count'] = 0;
                    }
                    if (!isset($parentcat['total_count'])) {
                        $parentcat['total_count'] = $parentcat['count'];
                    }
                    ++$parentcat['children_count'];
                    $parentcat['total_count'] += $item['count'];
                    unset($parentcat);
                }
            }
            unset($item);
            return $categories;
        }
    }

    public static function showtags()
    {
        $tags_model = new shopTagModel();
        $tags = $tags_model->getCloud();
        function cmp($a, $b)
        {
            return strnatcmp($a["name"], $b["name"]);
        }
        usort($tags, "cmp");
        return $tags;
    }

    public static function orders($action)
    {
        $orders = new shopOrderModel();
        return $orders->getStateCounters('deleted');
    }

    public static function reviews($action)
    {
        $recalc=new shopProductReviewsModel();
        return $recalc->countByField('status', 'deleted');
    }

    public static function images($action)
    {
        $images=new shopProductImagesModel();
        return $images->countAll();
    }

    public static function badges($action)
    {
        $badges=new shopProductModel();
        $sql="SELECT id FROM ".$badges->getTableName()." WHERE badge !='' ";
        return $badges->query($sql)->count();
    }
    
    public static function missingImages()
    {
        $product_image_model = new shopProductImagesModel();
        if ($product_images = $product_image_model->getAll()) {
            $missing_image_products = array();
            foreach ($product_images as $image) {
                if (!in_array($image['product_id'], $missing_image_products) && !file_exists(shopImage::getPath($image))) {
                    $missing_image_products[] = $image['id'];
                }
            }
            return count($missing_image_products);
        }
    }
}
