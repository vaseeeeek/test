<?php

class shopFavoritesModel extends waModel
{
    protected $table = 'shop_favorites';

    public function inFavorites($product_id, $contact_id)
    {
        return (bool)$this->getByField(array(
            'contact_id' => (int)$contact_id,
            'product_id' => (int)$product_id
        ))->fetch();
    }

    public function getFavorites($contact_id)
    {
        if (!$contact_id) {
            return array();
        }
        return $this->select('product_id')->where('contact_id = '.(int)$contact_id)->fetchAll(false, true);
    }

    public function getTopProducts($limit = 10)
    {
        $sql = "SELECT p.*, count(*) users FROM shop_product p JOIN ".$this->table." f ON p.id = f.product_id
                GROUP BY p.id ORDER BY users DESC LIMIT ".(int)$limit;
        return $this->query($sql)->fetchAll();
    }
}