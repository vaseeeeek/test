<?php

declare(strict_types=1);

class shopCicPluginBaseCommon
{
  private waModel $_model;

  private shopCicPlugin $_plugin;

  /**
   * @throws waException
   */
  public function __construct()
  {
    $this->_model = new waModel();
    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    $this->_plugin = wa()->getPlugin('cic');
  }

  public function getParents(int $id): array
  {
    try {
      return $this->_model->query('
        Select sc.id from shop_category as sc 
        inner join shop_category scn on scn.left_key > sc.left_key and  scn.right_key < sc.right_key
        where scn.id = :id',
          ['id' => $id]
        )->fetchAll() + [$id];
    } catch (waDbException $e) {
      return [$id];
    }
  }

  public function getPlugin(): shopCicPlugin
  {
    return $this->_plugin;
  }
}
