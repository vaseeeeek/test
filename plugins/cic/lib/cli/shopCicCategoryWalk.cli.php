<?php

declare(strict_types=1);

class shopCicCategoryWalkCli extends shopCicPluginBaseCli
{
  protected function isEnabled(): bool
  {
    return true;
  }

  protected function getParameterName(): string
  {
    return "cic_category_items";
  }
  
  protected function shouldRecord(array $options): bool
  {
    return true;
  }
}