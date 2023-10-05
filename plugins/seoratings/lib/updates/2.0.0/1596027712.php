<?php

$model = new waModel();

/** @noinspection SqlWithoutWhere */
$alter = [
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `related_ratings` text;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `list_description` text;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `shop_categories` text;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `image` text;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `image_thumb` text;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `type` enum('product','set','entity') NOT NULL DEFAULT 'product';",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `set_id` varchar(255) DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `primary_rating` tinyint(1) DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `published` tinyint(1) DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `sibling_positions` tinyint(1) DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `base_rating` int unsigned DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `position_shift` tinyint(1) DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `created_at` date DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD COLUMN `updated_at` date DEFAULT NULL;",
  "ALTER TABLE `shop_seoratings_rating` ADD CONSTRAINT fk_base_rating_id FOREIGN KEY (base_rating) REFERENCES shop_seoratings_rating(base_rating);",
  "ALTER TABLE `shop_seoratings_rating` MODIFY `id` int unsigned auto_increment;",
  "ALTER TABLE `shop_seoratings_rating` MODIFY `description_length` int;",

  "ALTER TABLE `shop_seoratings_rating_products` MODIFY `id` int unsigned auto_increment;",
  "ALTER TABLE `shop_seoratings_rating_products` MODIFY `rating_id` int unsigned NOT NULL;",
  "ALTER TABLE `shop_seoratings_rating_products` MODIFY `sort` int NOT NULL;",

  "ALTER TABLE `shop_seoratings_templates` MODIFY `id` int unsigned auto_increment;",
  "ALTER TABLE `shop_seoratings_templates` ADD COLUMN `developer` tinyint(1) DEFAULT 0;",

  "UPDATE `shop_seoratings_rating` SET `published` = 1",
];

foreach ($alter as $sql) {
  try {
    $model->exec($sql);
  } catch (waDbException $e) {
    shopSeoratingsLogger::log($e->getMessage());
  }
}