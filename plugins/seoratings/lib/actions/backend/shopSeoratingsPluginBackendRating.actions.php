<?php

class shopSeoratingsPluginBackendRatingActions extends shopSeoratingsBackendRestController
{
  /**
   * @var shopSeoratingsRatingModel
   */
  protected $model;

  public function __construct()
  {
    $this->model = new shopSeoratingsRatingModel();
  }

  public function getAllAction()
  {
    $this->response = $this->model->getAll();
  }

  public function editAction()
  {
    $data = $this->model->getById(waRequest::get('id', false, waRequest::TYPE_INT));
    $this->response = $data;
  }

  public function duplicateAction()
  {
    $ratingId = waRequest::get('id', false, waRequest::TYPE_INT);
    $rating = $this->model->getById($ratingId);
    $rating['url'] = md5(time());
    unset($rating['id']);
    unset($rating['frontend_url']);
    unset($rating['image']);
    unset($rating['image_thumb']);
    $rating = $this->encodeModelData($rating);
    $duplicateRatingId = $this->model->insert($rating);

    if ($rating['type'] === 'product') {
      $ratingsProductsModel = new shopSeoratingsRatingProductsModel();
      $products = $ratingsProductsModel->getByField('rating_id', $ratingId, true);
      array_walk($products, function (&$item) use ($duplicateRatingId) {
        unset($item['id']);
        $item['rating_id'] = $duplicateRatingId;
      });
      $ratingsProductsModel->multipleInsert($products);
    }
  }

  public function imageUploadAction()
  {
    $ratingId = waRequest::get('id', false, waRequest::TYPE_INT);
    $dataPath = wa()->getDataPath("plugins/seoratings/images/ratings/", true, 'shop');
    $dataUrl = wa()->getDataUrl("plugins/seoratings/images/ratings/", true, 'shop');
    $image = waRequest::file('image');
    if ($image->uploaded() && in_array(strtolower($image->extension), ['jpg', 'jpeg', 'png', 'gif'])) {
      $imagePath = $dataPath . $ratingId . '.' . $image->extension;
      $imageUrl = $dataUrl . $ratingId . '.' . $image->extension;
      if (file_exists($imagePath)) {
        waFiles::delete($imagePath);
      }
      $image->moveTo($imagePath);
      try {
        if ($thumb_img = shopImage::generateThumb($imagePath, '200x0')) {
          $imageThumbPath = $dataPath . $ratingId . '_thumb' . '.' . $image->extension;
          if (file_exists($imageThumbPath)) {
            waFiles::delete($imageThumbPath);
          }
          $imageThumbUrl = $dataUrl . $ratingId . '_thumb' . '.' . $image->extension;
          $thumb_img->save($imageThumbPath);
          $this->model->updateById($ratingId, [
            'image' => $imageUrl,
            'image_thumb' => $imageThumbUrl,
          ]);
          $this->response = [
            'message' => 'Загрузка завершена',
            'img_thumb' => $imageThumbUrl,
          ];

          return;
        }
      } catch (waException $e) {
        $this->response = [
          'message' => $e->getMessage(),
        ];
      }
    }
    $this->response = [
      'message' => "Ошибка загрузки.",
    ];
  }

  public function imageRemoveAction()
  {
    $ratingId = waRequest::post('id', false, waRequest::TYPE_INT);
    $rating = $this->model->getById($ratingId);
    if (empty($rating['image'])) {
      $this->response = ['message' => 'Загрузите изображение что бы его удалить', 'status' => 'errors'];

      return;
    }
    $dataPath = wa()->getDataPath("plugins/seoratings/images/ratings/", true, 'shop');
    $imageExtension = pathinfo($rating['image'], PATHINFO_EXTENSION);
    $imagePath = $dataPath . $ratingId . '.' . $imageExtension;
    $imageThumbPath = $dataPath . $ratingId . '_thumb' . '.' . $imageExtension;

    try {
      waFiles::delete($imagePath);
      waFiles::delete($imageThumbPath);
    } catch (waException | Exception $e) {
      $this->response = ['message' => $e->getMessage(), 'status' => 'errors'];

      return;
    }
    $this->model->updateById($ratingId, ['image' => null, 'image_thumb' => null]);
    $this->response = ['message' => 'Изображение удалено', 'status' => 'success'];
  }

  protected function delete()
  {
    $id = $_REQUEST['id'];
    $ratingProductsModel = new shopSeoratingsRatingProductsModel();
    $ratingProductsModel->deleteProductsByRatingId($id);
    parent::delete();
  }

  protected function fetchData()
  {
    $model = json_decode(waRequest::post('model'), true);
    $model = $this->encodeModelData($model);

    return $model;
  }

  protected function encodeModelData($model)
  {
    $model['shop_categories'] = json_encode($model['shop_categories']);
    $model['feature_codes'] = json_encode($model['feature_codes']);
    $model['filter_codes'] = json_encode($model['filter_codes']);
    $model['related_ratings'] = json_encode($model['related_ratings']);

    return $model;
  }
}
