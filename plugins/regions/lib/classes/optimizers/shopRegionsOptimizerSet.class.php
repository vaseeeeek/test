<?php


abstract class shopRegionsOptimizerSet
{
	final public function execute()
	{
		foreach ($this->getOptimizers() as $optimizer)
		{
			if ($optimizer instanceof shopRegionsOptimizer)
			{
				$optimizer->execute();
			}
		}
	}

	protected function getOptimizers()
	{
		return array();
	}
}