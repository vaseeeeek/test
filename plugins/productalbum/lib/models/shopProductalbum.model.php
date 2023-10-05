<?php

class shopProductalbumModel extends waModel
{
    protected $table = 'shop_productalbum';

    /* Добавление новой связи между продуктом и альбомом */
    public function addAlbumToProduct($productId, $albumId)
    {
        // Проверяем, существует ли уже запись с данным product_id
        $existing = $this->select('product_id')
                         ->where('product_id = i:id', array('id' => $productId))
                         ->fetch();

        if ($existing) {
            // Если запись существует, обновляем её
            $this->updateByField('product_id', $existing['product_id'], array('album_id' => $albumId));
        } else {
            // Если записи нет, создаём новую
            $this->insert(array(
                'product_id' => $productId,
                'album_id' => $albumId,
            ));
        }
    }

    /* Удаление связи между продуктом и альбомом */
    public function removeAlbumFromProduct($productId, $albumId)
    {
        $where = "product_id = i:product AND album_id = i:album";
        return $this->delete($where, array('product' => $productId, 'album' => $albumId));
    }

    /* Получение всех альбомов, связанных с продуктом */
    public function getAlbumsByProduct($productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = i:id";
        return $this->query($sql, array('id' => $productId))->fetchAssoc();
    }
}
