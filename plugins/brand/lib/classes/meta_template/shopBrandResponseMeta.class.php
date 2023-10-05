<?php

class shopBrandResponseMeta
{
	public function applyMeta(waResponse $response, shopBrandFetchedLayout $template_layout, $default_title = null)
	{
		$current_title = $response->getTitle();
		$meta_title = $template_layout->meta_title;

		if ($this->isEmpty($current_title) && $this->isEmpty($meta_title))
		{
			$meta_title = $default_title;
		}

		if (!$this->isEmpty($meta_title))
		{
			$response->setTitle($meta_title);
			if (method_exists($response, 'setOGMeta'))
			{
				$response->setOGMeta('og:title', $meta_title);
			}
		}

		if (!$this->isEmpty($template_layout->meta_keywords))
		{
			$response->setMeta('keywords', $template_layout->meta_keywords);
		}


		if (!$this->isEmpty($template_layout->meta_description))
		{
			$response->setMeta('description', $template_layout->meta_description);

			if (method_exists($response, 'setOGMeta'))
			{
				$response->setOGMeta('og:description', $template_layout->meta_description);
			}
		}
	}

	private function isEmpty($string)
	{
		return !is_string($string) || trim($string) === '';
	}
}