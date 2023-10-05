<?php

/**
 * Class shopBrandReviewStatusEnumOptions
 *
 * @property-read string $PUBLISHED
 * @property-read string $MODERATION
 * @property-read string $DELETED
 */
class shopBrandReviewStatusEnumOptions extends shopBrandEnumOptions
{
	protected function getOptionValues()
	{
		return array(
			'PUBLISHED',
			'MODERATION',
			'DELETED',
		);
	}
}