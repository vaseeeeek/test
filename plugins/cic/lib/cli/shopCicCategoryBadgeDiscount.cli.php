<?php

declare(strict_types=1);

class shopCicCategoryBadgeDiscountCli extends shopCicPluginBaseCli
{
  protected function getParameterName(): string
  {
    return "cic_category_has_discount_items";
  }

  protected function isEnabled(): bool
  {
    return $this->settings['is_discount_status'] !== null;
  }

  private function getMinDiscountCount(): int
  {
    return (int)$this->settings['is_discount_min'];
  }

  protected function getConditions(array $options = []): array
  {
    return [
      ...parent::getConditions(),
      'p.compare_price > 0',
    ];
  }

  protected function shouldRecord(array $options): bool
  {
    return $options['count'] > $this->getMinDiscountCount();
  }
}