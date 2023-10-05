<?php
class shopCleanupPluginBackendRunAction extends waViewAction
{
    private function stocksLog_del()
    {
        $model = new waModel();
        $model->exec('TRUNCATE TABLE `shop_product_stocks_log`;');
    }
    
    private function waLog_del()
    {
        $model = new waModel();
        $model->exec('DELETE FROM `wa_log` WHERE `wa_log`.`app_id` = "shop"');
    }
    
    private function workflow_del()
    {
        $workflow=new shopWorkflow();
        $file = wa()->getConfig()->getAppsPath('shop', 'lib/config/data/workflow.php');
        if (file_exists($file)) {
            $config = include($file);
            $workflow->setconfig($config);
        } else {
            return _wp('<h2>File workflow.php not found<h2>');
        }
    }

    private function wFlowAction_del($action)
    {
        $app_config=wa()->getConfig()->getAppConfig('shop');
        $path=$app_config->getConfigPath('workflow.php', true);
        if (file_exists($path)) {
            $workflow=include($path);
            if (is_array($action)) {
                foreach ($action as $id) {
                    unset($workflow['actions'][$id]);
                }
            } else {
                unset($workflow['actions'][$action]);
            }
            waUtils::varExportToFile($workflow, $path, true);
        }
    }
    
    private function orders_del($date = null, $date_from = null)
    {
        $orders = new shopOrderModel();
        $sql_list="SELECT id FROM shop_order WHERE state_id='deleted'";
        if ($date) {
            $sql_list.=" AND create_datetime < '".$orders->escape($date)."'";
        }
        if ($date_from) {
            $sql_list.=" AND create_datetime > '".$orders->escape($date_from)."'";
        }
        $deleted_orders = $orders->query($sql_list)->fetchAll();
        if (empty($deleted_orders)) {
            return;
        }
        $num=array();
        foreach ($deleted_orders as $order) {
                array_push($num, $order['id']);
        }
        $todelete=implode(',', $num);
        $sql="Delete from shop_order where shop_order.id IN (".$todelete.");";
            $orders->exec($sql);
        $sql="Delete from shop_order_items where order_id IN (".$todelete.");";
            $orders->exec($sql);
        $sql="Delete from shop_order_log where order_id IN (".$todelete.");";
            $orders->exec($sql);
        $sql="Delete from shop_order_log_params where order_id IN (".$todelete.");";
            $orders->exec($sql);
        $sql="Delete from shop_order_params where order_id IN (".$todelete.");";
            $orders->exec($sql);
        $sql="Delete from shop_affiliate_transaction where order_id IN (".$todelete.");";
            $orders->exec($sql);
        $sql="DELETE wa_transaction, wa_transaction_data  FROM wa_transaction  INNER JOIN wa_transaction_data WHERE wa_transaction.id= wa_transaction_data.transaction_id and wa_transaction.order_id IN (".$todelete.");";
            $orders->exec($sql);
        foreach ($num as $order) {
            $orders->recalculateProductsTotalSales($order);
        }
        unset($order);
        return;
    }

    private function reviews_del()
    {
        $recalc=new shopProductReviewsModel();
        $deleted_reviews=$recalc->getByField('status', 'deleted', true);
        foreach ($deleted_reviews as $review) {
            $product_model = new shopProductModel();
            $product = $product_model->getById($review['product_id']);
            $update = array(
                'rating' => (
                    $product['rating']*$product['rating_count'] - $review['rate'])/($product['rating_count'] - 1),'rating_count' => $product['rating_count'] - 1
                );
            $product_model->updateById($review['product_id'], $update);
            $recalc->deleteById($review['id']);
        }
        unset($review);
    }

    private function images_del($cats = null, $sets = null, $types = null)
    {
        $images=new shopProductImagesModel();
        $productModel = new shopProductModel();
        $sql="SELECT product_id FROM shop_product_images";
        if ($cats) {
            $sql="SELECT product_id FROM shop_category_products where category_id = '".$cats."'";
        }
        if ($sets) {
            $sql="SELECT product_id FROM shop_set_products where set_id = '".$sets."'";
        }
        if ($types) {
            $sql="SELECT id as product_id FROM shop_product where type_id = '".$types."'";
        }
        if ($sets && $types) {
            $sql="SELECT id as product_id FROM `shop_product` AS t1 LEFT JOIN `shop_set_products` AS t2 ON t1.product_id = t2.product_id WHERE t1.type_id='".$types."' AND t2.set_id='".$sets."'";
        }
        $products=$images->query($sql)->fetchAll();
        $products_id = array();
        foreach ($products as $row) {
            $products_id[]=$row['product_id'];
            $productModel->updateById($row['product_id'], array('image_id'=>'null'));
            $productModel->exec("UPDATE `shop_product_skus` SET image_id = NULL WHERE product_id = ".$row['product_id'].";");
        }
        $images->deleteByProducts($products_id, true);
        unset($row);
    }

    private static function missingImages()
    {
        $product_image_model = new shopProductImagesModel();
        if ($product_images = $product_image_model->getAll()) {
            $missing_image_products = array();
            foreach ($product_images as $image) {
                if (!in_array($image['product_id'], $missing_image_products) && !file_exists(shopImage::getPath($image))) {
                    $missing_image_products[] = $image['id'];
                }
            }
            if (!empty($missing_image_products)) {
                $product_image_model->deleteById($missing_image_products);
            }
        }
    }
    
    private static function originalImages($all = false)
    {
        $path = wa('shop')->getDataPath('products');
        if (!$all) {
            $list = waFiles::listdir($path, 1);
            $list = array_filter($list, function($var) {
                preg_match('/(.original.\w{3,4})$/', $var, $match);
                return !empty($match);
            });
            return $count;
        } else {
            $list = waFiles::listdir($path);
        }
        $count = 0;
        foreach ($list as $image) {
            waFiles::delete($path.DIRECTORY_SEPARATOR.$image, true);
            $count++;
        }
        return $count;
    }
    
    public static function fixThumb()
    {
        $path = wa()->getDataPath('products', true, 'shop');
        waFiles::delete($path.'/thumb.php');
        waFiles::write($path.'/thumb.php', '<?php
        $file = realpath(dirname(__FILE__)."/../../../../")."/wa-apps/shop/lib/config/data/thumb.php";
        
        if (file_exists($file)) {
            include($file);
        } else {
            header("HTTP/1.0 404 Not Found");
        }
        ');
        waFiles::copy(wa()->getAppPath('lib/config/data/.htaccess', 'shop'), $path.'/.htaccess');
    }
    
    private function badges_del()
    {
        $images=new shopProductImagesModel();
        $product = new shopProductModel();
        $sql='UPDATE `shop_product_images` SET badge_type=NULL,badge_code=NULL;';
        $images->exec($sql);
        $sql="UPDATE `shop_product` SET badge=NULL;";
        $product->exec($sql);
        unset($row);
    }

    public function execute()
    {
        $message='OK';
        $options = waRequest::post();
        if (!empty($options['WFactions'])) {
            $this->wFlowAction_del($options['WFactions']);
        }
        if (!empty($options['CATs'])) {
            $delcat=new shopCategoryModel();
            foreach ($options['CATs'] as $id) {
                $id=preg_replace("/[^0-9]/", "", $id);
                if (!empty($options['limitCatImages'])&& $options['limitCatImages']=='true') {
                    $this->images_del($id);
                } else {
                    $delcat->delete($id);
                }
            }
        }
        if (!empty($options['TAGs'])) {
            $deltag = new shopTagModel();
            $delproducttag = new shopProductTagsModel();
            foreach ($options['TAGs'] as $id) {
                $id=preg_replace("/[^0-9]/", "", $id);
                $deltag->deleteById($id);
                $delproducttag->deleteByField('tag_id', $id);
            }
        }
        if (!empty($options['options'])) {
            if ($options['options']=='orders') {
                if (!empty($options['orderfilter'])||!empty($options['orderfilter_from'])) {
                    $this->orders_del($options['orderfilter'], $options['orderfilter_from']);
                } else {
                    $this->orders_del();
                }
            }
            if ($options['options']=='reviews') {
                $this->reviews_del();
            }
            if ($options['options']=='images') {
                if (!empty($options['imagefilter'])&&!empty($options['imagefilterBytype'])) {
                    $this->images_del(null, $options['imagefilter'], $options['imagefilterBytype']);
                } elseif (!empty($options['imagefilter'])) {
                    $this->images_del(null, $options['imagefilter']);
                } elseif (!empty($options['imagefilterBytype'])) {
                    $this->images_del(null, null, $options['imagefilterBytype']);
                } else {
                    $this->images_del();
                }
            }
            if ($options['options']=='missingimages') {
                $this->missingImages();
            }
            if ($options['options']=='originalimages') {
                $this->originalImages();
            }
            if ($options['options']=='sourceimages') {
                $this->originalImages(true);
            }
            if ($options['options']=='fixthumb') {
                $this->fixThumb();
            }
            if ($options['options']=='badges') {
                $this->badges_del();
            }
            if ($options['options']=='stocks') {
                $this->stocksLog_del();
            }
            if ($options['options']=='walog') {
                $this->waLog_del();
            }
            if ($options['options']=='workflow') {
                $message=$this->workflow_del();
            }
        }
        $this->view->assign('message', empty($message)?'OK':$message);
    }
}
