<?php

class shopArrivedModel extends waModel
{
    protected $table = 'shop_arrived';

    public function countRequestsByProductId($pid)
    {
        $sql = "SELECT COUNT(*) FROM ".$this->table." WHERE expired=0 && product_id=i:product_id && sended=i:sended";
        return $this->query($sql, array('product_id' => $pid,'sended' => 0))->fetchField();
    }

    public function deleteAllExpiried()
    {
        $sql = "UPDATE ".$this->table." SET expired='1' WHERE expiration <= s:date";
        return $this->query($sql,array('date' => date("Y-m-d H:i:s")));
    }

    public function removeById($id)
    {
        return $this->deleteById($id);
    }

    public function markAsDone($id)
    {
        $sql = "UPDATE ".$this->table." SET sended='1', date_sended=s:date WHERE id=i:id";
        return $this->query($sql,array('date' => date("Y-m-d H:i:s"), 'id' => $id));
    }

    public function getAllRequests($where,$limit)
    {
		if(!empty($limit))
			return $this->select('*')->where($where)->order('created DESC')->limit($limit)->fetchAll();
		else
			return $this->select('*')->where($where)->order('created DESC')->fetchAll();
    }

    public function getStats($where="",$limit="")
    {
		$sql = "SELECT *,COUNT(id) as count_total,SUM(sended=0 && expired=0) as count_active FROM ".$this->table;
		if(!empty($where))
			$sql .= " WHERE ".$where;
		$sql .= " GROUP BY sku_id ORDER BY count_active DESC";
		if(!empty($limit))
			$sql .= " LIMIT ".$limit;
        return $this->query($sql)->fetchAll();
    }

    public function getAllActiveRequests($fields="*",$product_id,$sku_id)
    {
        $sql = "SELECT ".$fields." FROM ".$this->table." WHERE expired=0 && product_id=i:product_id && sku_id=i:sku_id && sended=i:sended";
        return $this->query($sql, array('product_id' => $product_id,'sku_id' => $sku_id,'sended' => 0))->fetchAll();
    }
}