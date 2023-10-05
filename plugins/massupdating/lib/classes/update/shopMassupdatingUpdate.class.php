<?php

class shopMassupdatingUpdate
{
	public function __construct($params = array())
	{
		$this->params = $params;
	}
	
	public function __call($name, $arguments) {		
		$var_class = 'update_' . $name;
		if(isset($this->$var_class)) {
			return call_user_func_array(array(&$this->$var_class, 'update'), $arguments);
		} else {
			$class_name = 'shopMassupdatingUpdate' . ucfirst($name);
			
			if(class_exists($class_name)) {
				$class = new $class_name();
				foreach($this->params as $key => $value)
					$class->$key = $value;
					
				$this->$var_class = $class;
				return call_user_func_array(array(&$this->$var_class, 'update'), $arguments);
			} else
				throw new Exception('Несуществующее действие массового редактирования');
		}
    }
}