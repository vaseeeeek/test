<?php

/*
 * mail@shevsky.com
 */
 
class shopMassupdatingPluginFarActions extends waJsonActions
{
	public $prepared = false, $closest_symbols = 25;
	protected $products_needs_params, $products, $matches, $found;
	
	public function __construct()
	{
		$this->getResponse()->addHeader('Content-Type', 'text/event-stream');
		$this->getResponse()->sendHeaders();
		
		$product_ids = waRequest::get('product_id', array(), 'array_int');
		$hash = waRequest::get('hash', '');
		if((count($product_ids) == 0 && !$hash) || waRequest::get('_csrf') != waRequest::cookie('_csrf'))
			$this->setError('Ошибка при отправке данных');
		else {
			$this->plugin = wa('shop')->getPlugin('massupdating');
			
			$this->plugin->memoryErrorCatcher("id: error" . PHP_EOL . "data: " . json_encode(array('message' => _wp('Вы выбрали слишком много товаров, обработать их не удастся'))) . PHP_EOL . PHP_EOL);
			
			if($hash)
				$product_ids = $this->plugin->getProductIdsByHash($hash);
			
			try {
				$this->prepare($product_ids, waRequest::get());
			} catch(Exception $e) {
				$this->setError($e->getMessage());
			}
		}
	}
	
	protected function setError($error)
	{
		$this->message('error', $error);
	}
	
	protected function prepare($product_ids, $params = array())
	{
		$this->product_ids = $product_ids;
		
		$this->ie = ifset($params['ie'], false);
		
		$this->find = ifset($params['find'], null);
		$this->where = ifset($params['where'], null);
		$this->feature = ifset($params['feature'], null);
		$this->replace = ifset($params['replace'], null);
		$this->register = !empty($params['register']) ? true : false;
		$this->replaces = ifset($params['replaces'], null);
		$this->search_type = ifset($params['search_type'], 'default');
		$this->use_variables = ifset($params['use_variables'], true);
		
		if(!$this->find) {
			throw new Exception('Отсутствует значение, которое необходимо искать');
			return false;
		}
		
		if(!$this->where) {
			throw new Exception('Выберите, где искать');
			return false;
		}
		
		if(!in_array($this->where, array(
			'name',
			'summary',
			'description',
			'meta_title',
			'meta_keywords',
			'meta_description',
			'feature'
		))) {
			throw new Exception('Поиск можно производить только по Названию, Описанию, Краткому описанию, META-полям и некоторым типам характеристик.');
			return false;
		}
		
		if($this->where == 'feature' && !$this->feature) {
			throw new Exception('Выберите характеристику, в которой собираетесь производить поиск');
			return false;
		}
		
		if(!in_array($this->search_type, array(
			'default',
			'advanced',
			'regexp'
		)))
			$this->search_type = 'default';
		
		$this->product_model = new shopProductModel();
		$this->product_features_model = new shopProductFeaturesModel();
		
		function preg_quote_advanced($str, $delimiter = '', $except = null, $replace_asterisk_to_dot = false) {
			$symbols = array(
				array(
					'.'
				),
				array(
					'+',
					'*',
					'?'
				),
				array(
					'$',
					'(',
					')',
					'{',
					'}',
					'=',
					'!',
					'<',
					'>',
					'|',
					':'
				)
			);
			
			if(is_array($except)) {
				foreach($except as $symbol) {
					for($i = 0; $i <= 2; $i++)
						if(($key = array_search($symbol, $symbols[$i])) !== false)
							unset($symbols[$i][$key]);
				}
			}
			
			$regexp = '%([' . implode('', $symbols[0]) . '\\\\' . implode('', $symbols[1]) . '\\[\\^\\]';
			$regexp .= implode('', $symbols[2]) . '\\' . $delimiter . '-])%';

			$result = preg_replace($regexp, '\\\\$1', $str);
			
			if($replace_asterisk_to_dot)
			    $result = str_replace('\*', '.', $result);
			
			return $result;
		}
		
		{
			$this->regexp = '/(';
			if($this->search_type == 'advanced') {
				$this->regexp_value = preg_quote_advanced($this->find, '/', array(
					'|',
					'?'
				), true);
			} else {
				$this->regexp_value = preg_quote($this->find);
			}
			$this->regexp .= $this->regexp_value;
			$this->regexp .= ')/u';
			if(!$this->register)
				$this->regexp .= 'i';
			
		}
		
		$this->ie = waRequest::get('ie', false);
		
		$this->prepared = true;
	}
	
	public function pushPreparingMessages( )
	{
		$this->message('preparing', 'Сбор товаров для поиска...', 0);
		$this->message('preparing', 'Сбор завершен, переход к поиску...', 0);
		
		return true;
	}
	
	public function preExecute()
	{
		if(!$this->prepared)
			$this->setError('Неизвестная ошибка');
		
		if($this->action == 'findAndReplace' && !$this->replace)
			$this->setError('Введите, на что заменять');
		
		$this->pushPreparingMessages();
	}
	
	public function run($params = null)
	{
		$action = $params;
        if(!$action)
			$action = 'default';
		$this->action = $action;
		
		if($this->prepared && $this->preExecute() !== false) {
			$this->execute($this->action);
			$this->postExecute();
		}
	}
	
	protected function message($id, $message, $progress = false)
	{
		if(!$this->ie) {
			$data = array(
				'progress' => $progress
			);
			
			if(gettype($message) == 'string')
				$data['message'] = _wp($message);
			elseif(gettype($message) == 'array')
				$data = array_merge($data, $message);
			
			echo "id: $id" . PHP_EOL;
			echo "data: " . json_encode($data) . PHP_EOL;
			echo PHP_EOL;
			
			@ob_flush();
			@flush();
		}
	}
	
	protected function getProductsAndParameters($all = false)
	{
		if($this->products_needs_params)
			return $this->products_needs_params;
		
		if($this->where != 'feature') {
			$query = "SELECT `id`, name, price, min_price, max_price, `{$this->where}` FROM `{$this->product_model->getTableName()}`";
			$query .= " WHERE";
			$query .= " `id` IN (s:product_ids)";
			if(!$all)
				$query .= "";
			$products = $this->product_model->query($query, array(
				'product_ids' => $this->product_ids
			))->fetchAll('id');
			$products_needs_params = array( );
			foreach($products as $product_id => $param)
				if(isset($param[$this->where]))
					$products_needs_params[$product_id] = $param[$this->where];
		} else {
			$products_needs_params = false;
			$products = false;
		}
		
		$this->products_needs_params = $products_needs_params;
		$this->products = $products;
		
		return $this->products_needs_params;
	}
	
	protected function findForMatchesInProducts($progress = 0, $percentage = 100)
	{
		if(!$this->products_needs_params)
			$this->getProductsAndParameters();
		
		$count = count($this->products_needs_params);
		$i = 0;
		$this->matches = array( );
		foreach($this->products_needs_params as $product_id => $param) {
			$function = 'preg_match' . ($this->replaces == 'all' ? '_all' : '');
			$function($this->regexp, $param, $matches, PREG_OFFSET_CAPTURE);
			
			if($matches)
				$this->matches[$product_id] = $matches[0];
			$i++;
			$this->message('processing', array(
				'message' => sprintf('Обработка товара %d на совпадения...', $product_id),
				'done' => $i,
				'from' => $count
			), $progress + $i * (($percentage - $progress) / $count));
		}
	}
	
	protected function findForMatches($progress = 0, $percentage = 100, $closest_symbols = 25)
	{
		if(!$this->matches) {
			$this->findForMatchesInProducts($progress, $percentage / 2);
			$progress = $percentage / 2;
		}
		
		$count = count($this->matches);
		$i = 0;
		$this->found = array( );
		foreach($this->matches as $product_id => $matches) {
			if($matches) {
				$this->found[$product_id] = array( );
				$this->found[$product_id]['full'] = preg_replace($this->regexp, '<b class="found">$1</b>', $this->products_needs_params[$product_id], $this->replaces == 'all' ? -1 : 1);
				$this->found[$product_id]['str'] = $this->products_needs_params[$product_id];
				$this->found[$product_id]['find'] = is_array($matches[0]) ? $matches[0][0] : $matches[0];
			}
			$i++;
			$this->message('processing', array(
				'message' => sprintf('Поиск совпадений в товаре %d...', $product_id),
				'done' => $i,
				'from' => $count
			), $progress + $i * (($percentage - $progress) / $count));
		}
		
		$this->message('processing', 'Поиск завершен... ', $percentage);
	}
	
	public function prepareFindResults( )
	{
		$count = count($this->found);
		$found = $count > 100 ? array_slice($this->found, 0, 100, true) : $this->found;
		
		$this->find_results = array(
			'found_count' => $count,
			'found' => $found,
			'ignore_case' => !$this->register,
			'closest_symbols' => $this->closest_symbols,
			'replaces' => $this->replaces,
			'regexp' => $this->regexp_value
		);
		
		return $this->find_results;
	}
	
	public function replace($progress = 0, $percentage = 100)
	{
		$shop_name = wa('shop')->getConfig()->getGeneralSettings('name');
		
		$count = count($this->matches);
		$i = 0;
		foreach($this->matches as $product_id => $matches) {
			$view = wa()->getView();
			
			if($this->use_variables) {
				$view->clearAllAssign();
				$view->assign('name', $this->products[$product_id]['name']);
				$view->assign('shop', ifset($shop_name));
				$view->assign('price', round($this->products[$product_id]['price'], 2));
				$view->assign('min_price', round($this->products[$product_id]['min_price'], 2));
				$view->assign('max_price', round($this->products[$product_id]['max_price'], 2));
				
				$got_features = $this->product_features_model->getValues($product_id);
				$features = array( );
				foreach($got_features as $_key => $_value) {
					$features[$_key] = array(
						'value' => null,
						'unit' => null,
						'code' => null,
						'begin' => null,
						'end' => null
					);

					if($_value instanceof ArrayAccess) {
						$features[$_key]['value'] = $_value->offsetGet('value');
						$features[$_key]['value_base_unit'] = $_value->offsetGet('value');
						$features[$_key]['unit'] = $_value->offsetGet('unit');
						$features[$_key]['code'] = $_value->offsetGet('code');
						$features[$_key]['begin'] = $_value->offsetGet('begin');
						$features[$_key]['end'] = $_value->offsetGet('end');
					} elseif(gettype($_value) == 'array') {
						$i = 0;
						foreach($_value as $__value) {
							if($__value instanceof ArrayAccess) {
								$features[$_key]['value'][$i] = $__value->offsetGet('value');
								$features[$_key]['code'][$i] = $__value->offsetGet('code');
							} else
								$features[$_key]['value'][$i] = $__value;
								
							$i++;
						}
						
						$features[$_key]['values'] = $features[$_key]['value'];
						$features[$_key]['value'] = implode(', ', $features[$_key]['value']);
					} else
						$features[$_key]['value'] = $_value;
				}
				$view->assign('feature', $features);
			}
			
			$param = preg_replace($this->regexp, $this->use_variables ? $view->fetch('string:' . $this->replace) : $this->replace, $this->products_needs_params[$product_id], $this->replaces == 'all' ? -1 : 1);
			$this->product_model->updateById($product_id, array(
				$this->where => $param,
				'edit_datetime' => date('Y-m-d H:i:s')
			));
			$i++;
			$this->message('processing', array(
				'message' => 'Производится замена...',
				'done' => $i,
				'from' => $count
			), $progress + $i * (($percentage - $progress) / $count));
		}
	}
	
	public function findAction()
	{
		$this->findForMatches();
		$this->prepareFindResults();
		
		if(count($this->found))
			$this->message('close', array_merge(array(
				'message' => 'Поиск завершен'
			), $this->find_results), 100);
		else
			$this->message('exit', 'Поиск не дал результатов', 100);

		if($this->ie)
			echo json_encode($this->find_results);
	}
	
	public function findAndReplaceAction()
	{
		$this->findForMatches();
		$this->prepareFindResults();
		
		if(count($this->found)) {
			$this->message('next', array_merge(array(
				'message' => 'Поиск завершен',
				'action' => 'replace'
			), $this->find_results), 100);
			
			$this->replace();
			
			$this->message('close', array_merge(array(
				'message' => 'Замена произведена'
			), $this->find_results), 100);
			
			if($this->ie)
				echo json_encode($this->find_results);
		} else
			$this->message('exit', 'Поиск не дал результатов', 100);
	}
}