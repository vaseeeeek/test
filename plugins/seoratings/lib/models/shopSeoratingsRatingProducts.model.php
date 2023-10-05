<?php

class shopSeoratingsRatingProductsModel extends waModel
{
  const PRODUCTS_LIMIT = 1000;

  protected $table = 'shop_seoratings_rating_products';

  public function getAllRatingProducts($ratingId)
  {
    $ratingId = (int)$ratingId;

    return $this
      ->query("select rp.id, rp.rating_id, rp.sort, sp.name, sp.id as `product_id`, sp.image_id, sp.ext, sp.image_filename from {$this->table} as rp join shop_product as sp on rp.product_id = sp.id where rp.rating_id = {$ratingId} order by sort")
      ->fetchAll();
  }

  public function getRatingProducts($ratingId, $reverse = false)
  {
    $ratingId = (int)$ratingId;
    $productIds = $this->getProductIds($ratingId);

    if (!count($productIds)) {
      return [];
    }

    $productIds = array_map(function ($item) {
      return $item['product_id'];
    }, $productIds);

    $productModel = new shopProductModel();

    $flatProductIds = join(',', $productIds);

    $rows = $productModel
      ->query("select `sp`.*, `ssrp`.sort as `seoratings_position` from `shop_product` as `sp` join `shop_seoratings_rating_products` as `ssrp` on `sp`.id = `ssrp`.product_id  where `sp`.id in ({$flatProductIds}) and `ssrp`.rating_id = {$ratingId} order by `ssrp`.sort " . ($reverse ? 'desc' : 'asc'))
      ->fetchAll();
    $products = [];

    $category_model = new shopCategoryModel();
    $categories = $category_model->getFullTree('id, full_url');

    foreach ($rows as $item) {
      $params = ['product_url' => $item['url']];
      if (array_key_exists($item['category_id'], $categories)) {
        $params['category_url'] = $categories[$item['category_id']]['full_url'];
      }
      $item['frontend_url'] = wa()->getRouteUrl('shop/frontend/product', $params);
      $products[$item['id']] = $item;
    }

    return $products;
  }
//
//  public function getProductsForRatingWithProductIdsAsKeys($ratingId)
//  {
//    $products = $this->getRatingProducts($ratingId);
//    dump($products);
//    die();
//  }
//  
  public function getProductsByIds(array $ids)
  {
    return $this->select('*')->where('product_id IN (i:ids)', ['ids' => $ids])->fetchAll();
  }

  public function getProductIds($ratingId)
  {
    $ratingId = (int)$ratingId;

    return $this
      ->query("select `product_id` from {$this->table}  where `rating_id` = {$ratingId} order by `sort`")
      ->fetchAll();
  }

  public function deleteProductsByRatingId($id)
  {
    $this->deleteByField('rating_id', $id);
  }

  public function updateSort(array $fields)
  {
    array_map(function ($model) {
      $this->updateById($model['id'], $model);
    }, $fields);
  }

  protected function getProductSkus($product)
  {
    $skus_model = new shopProductSkusModel();
    $rows = $skus_model->select('*')->where('product_id IN (i:ids)', ['ids' => $product['id']])->order('sort')->fetchAll();
    shopRounding::roundSkus($rows);

    $skus = [];
    foreach ($rows as $row) {
      $skus[$row['product_id']][] = $row;
    }

    return $skus;
  }

  public function getMaxSortValueForGivenRating($rating_id)
  {
    $result = $this->select('max(sort) max')->where('rating_id=' . (int)$rating_id)->fetchAssoc();

    return $result['max'];
  }

  public function getProductsFromSet($setId, $reverse = false)
  {
    $shopViewHelper = new shopViewHelper(wa('shop'));
    $products = $shopViewHelper->productSet($setId);
    $counter = 0;
    array_walk($products, function (&$item) use (&$counter) {
      $item['seoratings_position'] = ++$counter;
    });

    if ($reverse) {
      return array_reverse($products, true);
    }

    return $products;
  }

  public function findPositionsInOtherRatings($rating, &$products)
  {
    $seoratingsRatingModel = new shopSeoratingsRatingModel();

//    $productRatings = $seoratingsRatingModel->getProductRatings(join(', ',array_map(function ($product) {
//      return $product['id'];
//    }, $products)));

    $productRatings = $seoratingsRatingModel->getAll();

    $previousPositions = [];
    foreach ($productRatings as $_rating) {
      if ($rating['id'] !== $_rating['id']) {
        array_walk($products, function (&$item) use ($_rating) {
          $item['previous_positions'] = [];
        });
        $data = [
          'id' => $_rating['id'],
          'title' => $_rating['title'],
          'products' => $this->getRatingProducts($_rating['id'], $_rating['reverse']),
          'frontend_url' => $_rating['frontend_url'],
        ];
        if ($_rating['type'] === 'set') {
          $data['products'] = $this->getProductsFromSet($_rating['set_id']);
        } else {
          $data['products'] = $this->getRatingProducts($_rating['id']);
        }
        $previousPositions[] = $data;
      }
    }

    if (count($previousPositions)) {
      foreach ($products as &$p) {
        foreach ($previousPositions as $_rating) {
          foreach ($_rating['products'] as $product) {
            if ($product['id'] === $p['id']) {
              $p['previous_positions'][$_rating['id']] = [
                'title' => $_rating['title'],
                'position' => $product['seoratings_position'],
                'frontend_url' => $_rating['frontend_url'],
              ];
              break;
            }
          }
        }
      }
    }
  }

  public function getRatingsWithProducts($ratingIds)
  {
    $query = "
        select `sr`.*, `srp`.`sort` 
        from `shop_seoratings_rating_products` as `srp` 
        join `{$this->table}` as `sr` 
        on `sr`.`id` = `srp`.`rating_id` where `srp`.`product_id` in ({$ratingIds}) 
        group by `srp`.`rating_id`";

    $result = $this
      ->query($query)
      ->fetchAll();
  }
}
