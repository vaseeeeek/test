<?php

declare(strict_types=1);

class shopCicCategoryBadgeNewCli extends shopCicPluginBaseCli
{
  protected function isEnabled(): bool
  {
    return $this->settings['is_new_status'] !== null;
  }

  protected function getParameterName(): string
  {
    return "cic_category_has_new_items";
  }

  protected function getConditions(array $options = []): array
  {
    return [
      ...parent::getConditions(),
      'p.create_datetime > "' . $this->getMinNewDate() . '"',
    ];
  }

  protected function getMinNewDate(): string
  {
    static $date = null;
    if ($date == null) {
      $timeStamp = strtotime($this->settings['is_new']);
      $date = date('Y-m-d', $timeStamp);
    }

    return $date;
  }

  protected function getMinNewCount(): int
  {
    return (int)$this->settings['is_new_min'];
  }

  protected function shouldRecord(array $options): bool
  {
    return $options['count'] > $this->getMinNewCount();
  }
}