<?php

interface shopRegionsIReplacer
{
	public function fetch($template);

	public function toSmarty($template);
}