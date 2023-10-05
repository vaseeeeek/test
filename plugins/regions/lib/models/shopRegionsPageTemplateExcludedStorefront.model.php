<?php

class shopRegionsPageTemplateExcludedStorefrontModel extends waModel
{
	const MULTIPLE_INSERT_LIMIT = 200;
	protected $table = 'shop_regions_page_template_excluded_storefront';

	public function save(array $template_storefronts)
	{
		$this->__truncate();

		$data_sets = array();
		foreach ($template_storefronts as $page_url => $storefronts)
		{
			foreach ($storefronts as $storefront)
			{
				$data_sets[] = array(
					'page_url' => $page_url,
					'storefront' => $storefront,
					'page_route_hash' => sha1($storefront . $page_url),
				);
			}

			if (count($data_sets) >= self::MULTIPLE_INSERT_LIMIT)
			{
				$this->multipleInsert($data_sets);
				$data_sets = array();
			}
		}

		if (count($data_sets))
		{
			$this->multipleInsert($data_sets);
		}
	}

	public function __truncate()
	{
		try
		{
			$this->exec("TRUNCATE {$this->table}");
		}
		catch (Exception $e)
		{
			// DB user does not have enough rights for TRUNCATE.
			// Fall back to DELETE. Slow but sure.
			$this->exec("DELETE FROM {$this->table}");
		}
	}
}