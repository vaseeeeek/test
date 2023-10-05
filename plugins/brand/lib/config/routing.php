<?php

return array(
	'brand_json/add_review/' => array(
		'plugin' => 'brand',
		'module' => 'frontend',
		'action' => 'addReview',
	),
	'brand_json/instant_review_rating/' => array(
		'plugin' => 'brand',
		'module' => 'frontend',
		'action' => 'addInstantReviewRating',
	),
	'brands/' => array(
		'plugin' => 'brand',
		'module' => 'frontend',
		'action' => 'redirectBrands',
	),
	'brand/' => array(
		'plugin' => 'brand',
		'module' => 'frontend',
		'action' => 'brands',
	),
	'brand/<brand>/reviews/add/' => array(
		'plugin' => 'brand',
		'module' => 'frontend',
		'action' => 'addReviewPage',
	),
	'brand/<brand>/<brand_page>/' => array(
		'plugin' => 'brand',
		'module' => 'frontend',
		'action' => 'brandPage',
	),
	'brand/<brand>/' => array(
		'plugin' => 'brand',
		'module' => 'frontend',
		'action' => 'brandPage',
	),
);
