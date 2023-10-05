<?php

class shopMassupdatingUpdateVideo
{
	public function update($to_update)
	{
		$checked = shopVideo::checkVideo($to_update['video_url']);
		if($checked)
			$to_update['video_url'] = $checked;
		else {
			throw new Exception('Некорректная ссылка на видео');
		}
		
		return $to_update;
	}
}