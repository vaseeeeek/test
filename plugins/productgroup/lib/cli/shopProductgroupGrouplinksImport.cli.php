<?php

class shopProductgroupGrouplinksImportCli extends waCliController
{
	public function execute()
	{
		throw new Exception('Не соответствует структуре в базе');

		$model = new waModel();

		$model->exec('DELETE FROM shop_productgroup_group');
		$model->exec('DELETE FROM shop_productgroup_product_group');
		$model->exec('DELETE FROM shop_productgroup_product_group_product');

		$groups_transfer_sql = "
INSERT INTO shop_productgroup_group
(id,name,markup_template_id,show_in_stock_only,show_on_primary_product_only,is_shown,is_shown_in_category,related_feature_id,image_size,sort)
SELECT id, name, IF(use_image = '1', 'photo_group', 'simple_group'), '1', only_from_primary, '1', '1', 0, NULL, id
FROM shop_grouplinks_group
";
		$model->exec($groups_transfer_sql);




		$product_groups_transfer_sql = '
INSERT INTO shop_productgroup_product_group
(id, group_id)
SELECT id, group_id
FROM shop_grouplinks_product_group
';
		$model->exec($product_groups_transfer_sql);





		$product_group_products_transfer_sql = '
INSERT INTO shop_productgroup_product_group_product
(product_group_id, product_id, label, is_primary, sort)
VALUES (:product_group_id, :product_id, :label, :is_primary, :sort)
';

		$product_group_products = $model->query("
SELECT product_group_id, product_id, label, is_primary
FROM shop_grouplinks_product_group_product");

		$sort = 0;
		foreach ($product_group_products as $row)
		{
			$query_params = [
				'product_group_id' => $row['product_group_id'],
				'product_id' => $row['product_id'],
				'label' => $row['label'],
				'is_primary' => $row['is_primary'],
				'sort' => $sort++,
			];

			$model->exec($product_group_products_transfer_sql, $query_params);
		}

		echo "Ok" . PHP_EOL;
	}
}