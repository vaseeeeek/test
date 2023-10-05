<?php

declare(strict_types=1);

abstract class shopCicPluginBaseCli extends waCliController
{
  protected shopCicPluginBaseCommon $model;
  protected shopCategoryModel $categoryModel;
  protected array $settings;

  protected abstract function getParameterName(): string;

  protected abstract function isEnabled(): bool;

  protected abstract function shouldRecord(array $options): bool;

  public function __construct()
  {
    $this->model = new shopCicPluginBaseCommon();
    $this->categoryModel = new shopCategoryModel();
    $this->settings = $this->model->getPlugin()->getSettings();
  }

  /**
   * @throws waException
   */
  public function execute()
  {
    $this->clear();
    if ($this->isEnabled()) {
      $categories = $this->categoryModel->getTree(0, 0);
      array_walk($categories, $this->getAlgorithm());
    }
  }
  
  public function walk(array $category): array
  {
    $subCategories = $this->categoryModel->getSubcategories($category);
    $products = [];
    if (count($subCategories)) {
      foreach ($subCategories as $subCategory) {
        $products = [...$products, ...$this->walk($subCategory)];
      }
    }
    $products = [...$products, ...$this->getProducts([$category['id']], $this->getConditions())];

    $count = $this->countUnique($products);

    if ($this->shouldRecord(compact('count'))) {
      $this->addRecord((int)$category['id'], $count);
    }

    return $products;
  }

  public function walkLeafs(array $category): array
  {
    $subCategories = $this->categoryModel->getSubcategories($category);

    if (count($subCategories)) {
      foreach ($subCategories as $subCategory) {
        $this->walk($subCategory);
      }
    }
    $products = $this->getProducts([$category['id']], $this->getConditions());

    $count = $this->countUnique($products);

    if ($this->shouldRecord(compact('count'))) {
      $this->addRecord((int)$category['id'], $count);
    }

    return $products;
  }

  protected function getAlgorithm(): array {
    return [$this, 'walk'];
  }
  
  protected function getConditions(array $options = []): array
  {
    $conditions = ['p.status = 1'];
    
    if ($this->settings['drop_out_of_stock'] === '1') {
      $conditions[] = '(p.count > 0 OR p.count IS NULL)';
    }

    return $conditions;
  }

  protected function afterExecute()
  {
    echo 'complete!' . PHP_EOL;
  }

  protected function clear(): void
  {
    $this->categoryModel->exec(
      'delete from shop_category_params where name = :name',
      ['name' => $this->getParameterName()]
    );
  }

  protected function countUnique(array $products): int
  {
    return count(array_flip($products));
  }

  protected function getProducts(array $path, array $conditions = [], int $length = 1): array
  {
    return array_reduce(array_slice($path, 0, $length), function ($acc, $id) use ($conditions) {
      try {
        $collection = new shopProductsCollection("category/$id");
        foreach ($conditions as $where) {
          $collection->addWhere($where);
        }

        return array_merge($acc, array_column($collection->getProducts('p.id', 0, 1000 ** 2), 'id'));
      } catch (Exception $e) {
        return $acc;
      }
    }, []);
  }

  protected function addRecord(int $id, int $count)
  {
    try {
      $this->categoryModel->exec(
        'insert into shop_category_params values(:id, :name, :count)',
        ['id' => $id, 'name' => $this->getParameterName(), 'count' => $count]
      );
    } catch (Exception $e) {
    }
  }

  protected function addRecords(array $path, int $count, int $offset = 0)
  {
    foreach (array_slice($path, $offset) as $id) {
      $this->addRecord($id, $count);
    }
  }
}
