<?php

class shopMassupdatingUpdateBadge
{
	public function update($to_update, $custom = null)
	{
		if($to_update['badge'] == 'default')
			unset($to_update['badge']);
		elseif($to_update['badge'] == 'delete')
			$to_update['badge'] = null;
		elseif($to_update['badge'] == 'custom' && !empty($custom))
			$to_update['badge'] = $custom;
			
		return $to_update;
	}
}