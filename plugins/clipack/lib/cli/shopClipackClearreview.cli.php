<?php
class shopClipackClearreviewCli extends waCliController
{
    /*
     * Очистка удаленных отзывов
     */
    public function execute()
    {
        $model = new waModel();
        $sql = "SELECT id FROM `shop_product_reviews` WHERE status = 'deleted'";
        $neededReview = $model->query($sql)->fetchAll();

        foreach ($neededReview as $key => $review) {
            $id = $review['id'];
            $sql = "DELETE FROM shop_product_reviews_images WHERE review_id = ?";
            $model->exec($sql, $id);
        }

        $sql = "DELETE FROM shop_product_reviews WHERE status = 'deleted'";
        $model->exec($sql);

//        "SELECT * FROM `shop_product_reviews_images`"
//        $sql = "DELETE FROM shop_zadarma_statistic WHERE callstart < ?";
//        $model->exec($sql, $maxDate);
    }

}
