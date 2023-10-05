<?php


class shopRegionsWindowReplaceSet extends shopRegionsReplacesSet
{
	public function getReplaces()
	{
		$replaces = array(
			new shopRegionsCityReplacesSet(),
		);

		$m_regions = new shopRegionsCityModel();
		$_regions = $m_regions->getAll();
		$regions = array();

		foreach ($_regions as $_region)
		{
			$region = shopRegionsCity::build($_region);
			$regions[] = $region->toArray(false, true);
		}

		wa()->getView()->assign('regions', $regions);
		$all_regions_path = shopRegionsViewHelper::getTemplatePath('AllRegions.html');

		$all_regions = wa()->getView()->fetch($all_regions_path);
		$replaces[] = new shopRegionsVariable('all_regions', $all_regions);

		return $replaces;
	}
}