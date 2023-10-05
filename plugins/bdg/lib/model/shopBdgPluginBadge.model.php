<?php

class shopBdgPluginBadgeModel extends waModel
{
	protected $table = 'shop_bdg_badge';
	
	public function save($badges)
	{
		if ( !empty($badges) && is_array($badges) )
		{
			$ids = $this->_getIds();
			foreach ( $badges as $data )
			{
				$data['name'] = strip_tags($data['name']);
				if ( $data['id'] )
				{
					$id = $data['id'];
					unset($data['id']);
					unset($ids[$id]);
					$this->updateById($id, $data);
				}
				else
					$this->insert($data);
			}
		}
		if ( !empty($ids) )
		{
			$model = new shopBdgPluginProductBadgeModel;
			foreach ( array_keys($ids) as $id )
			{
				$this->deleteById($id);
				$model->deleteByField('badge_id', $id);
			}
		}
	}
	
	
	private function _getIds()
	{
		$b = $this->getAll();
		$ids = array();
		if ( !empty($b) )
			foreach ( $b as $v )
				$ids[$v['id']] = 1;
		return $ids;
	}

}