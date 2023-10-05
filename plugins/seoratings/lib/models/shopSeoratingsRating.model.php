<?php

class shopSeoratingsRatingModel extends waModel
{
  protected $table = 'shop_seoratings_rating';

  protected $boolean_fields = [
    'price',
    'price_old',
    'features',
    'compare',
    'favorites',
    'rating',
    'summary',
    'photo',
    'brand',
    'category',
    'stock',
    'code',
    'skus',
    'advantages',
    'advantages_fold',
    'tags',
    'reverse',
    'has_sidebar',
    'squeeze_features',
    'filter',
    'primary_rating',
    'sibling_positions',
    'position_shift',
    'published',
  ];
  /**
   * Root url of the application.
   *
   * @var string
   */
  protected $frontendUrl;

  public function __construct($type = null, $writable = false)
  {
    parent::__construct($type, $writable);
    $this->frontendUrl = shopSeoratingsPlugin::getRootFrontendUrl();
  }

  public function getAll($key = null, $normalize = false)
  {
    return $this->prepareResult(parent::getAll($key, $normalize));
  }

  public function getPublishedRatings()
  {
    $result = $this
      ->query("select * from `{$this->table}` where `published` = 1")
      ->fetchAll();

    return $this->prepareResult($result);
  }

  public function findRatingsByType($type)
  {
    $result = $this
      ->query("select * from `{$this->table}` where type = '{$type}'")
      ->fetchAll();

    return $this->prepareResult($result);
  }

  public function getRelatedRatings($ratingIds)
  {
    if (!$ratingIds) {
      return [];
    }
    $ratingIds = join(', ', $ratingIds);

    $result = $this
      ->query("select * from `{$this->table}` where id in ({$ratingIds})")
      ->fetchAll();

    return $this->prepareResult($result);
  }

  public function getCategoryRatings(int $categoryId)
  {
    $id = $categoryId;
    $result = $this->query("select * from `{$this->table}` where `shop_categories` like '%\"{$id}\"%' and published = 1")->fetchAll();

    return $this->prepareResult($result);
  }

  protected function prepareResult(array $result)
  {
    $result = array_map(function ($item) {
      $item['feature_codes'] = json_decode($item['feature_codes'], true);
      $item['filter_codes'] = json_decode($item['filter_codes'], true);
      $item['related_ratings'] = json_decode($item['related_ratings'], true);
      $item['shop_categories'] = json_decode($item['shop_categories'], true);

      return $item;
    }, $result);

    $result = array_map(function ($item) {
      foreach ($item as $key => $value) {
        if (in_array($key, $this->boolean_fields)) {
          $item[$key] = boolval($value);
        }
      }

      return $item;
    }, $result);

    array_walk($result, [$this, 'addFrontendUrl']);

    return $result;
  }

  public function getRandomRatings(int $limit = 3)
  {
    return $this->prepareResult(
      $this->query("select * from `{$this->table}` where `published` = 1 order by RAND() limit {$limit}")->fetchAll()
    );
  }

  protected function addFrontendUrl(array &$rating)
  {
    $rating['frontend_url'] = $this->frontendUrl . rtrim($rating['url'], '/') . '/';
  }

  public function getProductRatings($productId)
  {
    $query = "
        select `sr`.*, `srp`.`sort` 
        from `shop_seoratings_rating_products` as `srp` 
        join `{$this->table}` as `sr` 
        on `sr`.`id` = `srp`.`rating_id` 
        where `srp`.`product_id` in ({$productId}) and `sr`.`published` = 1
        group by `srp`.`rating_id`
    ";
    $result = $this
      ->query($query)
      ->fetchAll();

    array_walk($result, [$this, 'addFrontendUrl']);

    return $this->prepareResult($result);
  }

  public function getByField($field, $value = null, $all = false, $limit = false)
  {
    $result = parent::getByField($field, $value, $all, $limit);
    if (!$result) {
      return $result;
    }
    $preparedResult = $this->prepareResult([$result]);

    return $preparedResult[0];
  }
}