<?php

class shopSeoratingsPluginBackendRatingProductsActions extends shopSeoratingsBackendRestController
{
  /**
   * @var shopSeoratingsRatingProductsModel
   */
  protected $model;

  public function __construct()
  {
    $this->model = new shopSeoratingsRatingProductsModel();
  }

  public function defaultAction()
  {
    $id = waRequest::request('id', 0, waRequest::TYPE_INT);
    $items = $this->model->getAllRatingProducts($id);

    /*$shop = $helper = new shopViewHelper(wa('shop'));
    array_walk($items, function (&$product) use ($shop) {
      $product['image_url'] = $shop->imgUrl([
        'id' => $product['image_id'],
        'product_id' => $product['product_id'],
        'image_filename' => $product['image_filename'],
        'ext' => $product['ext'],
      ], '48x48', true);
    });*/

    $this->response = $items;
  }

  public function sortAction()
  {
    $this->model->updateSort($_POST['data']);
  }

  /**
   * @throws waException
   */
  public function addProductsAction()
  {
    $hash = waRequest::request('hash', false);
    $rating_id = waRequest::request('rating_id', null, waRequest::TYPE_INT);
    $replace = waRequest::request('replace_products', null, waRequest::TYPE_INT);

    if ($replace) {
      $this->model->deleteProductsByRatingId($rating_id);
    }

    if ($hash) {
      $collection = new shopProductsCollection($hash);
      $products = array_keys($collection->getProducts('id', 0, 99999));
    } else {
      $products = waRequest::request('product_id', [], waRequest::TYPE_ARRAY_INT);
    }

    $productIds = array_keys($this->model->getRatingProducts($rating_id));
    $maxSortValue = $this->model->getMaxSortValueForGivenRating($rating_id);
    $result = [];

    array_walk($products, function ($id) use (&$result, $rating_id, $productIds, &$maxSortValue) {
      if (count($productIds)) {
        if (!in_array($id, $productIds)) {
          array_push($result, ['rating_id' => $rating_id, 'product_id' => $id, 'sort' => ++$maxSortValue]);
        }
      } else {
        static $sort = 1;
        array_push($result, ['rating_id' => $rating_id, 'product_id' => $id, 'sort' => $sort++]);
      }
    });

    $this->model->multipleInsert($result);
  }

  protected function fetchData()
  {
    return json_decode(waRequest::post('model'), true);
  }
}
