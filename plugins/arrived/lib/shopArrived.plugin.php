<?php

class shopArrivedPlugin extends shopPlugin {

	private static $first_load = true;

	public static function path($file, $original = false)
    {
        $path = wa()->getDataPath('plugins/arrived/' . $file, false, 'shop', true);
        if ($original) {
            return dirname(__FILE__) . '/config/' . $file;
        }
        if (!file_exists($path)) {
            waFiles::copy(dirname(__FILE__) . '/config/' . $file, $path);
        }
        return $path;
    }

	private static function productAvailible($product)
	{
		$product_available = false;
		if (isset($product['skus'])) {
			if (count($product['skus']) > 1) {
				return $product_available;
			} else {
				$sku = $product['skus'][$product['sku_id']];
				$product_available = $product['status'] && $sku['available'] && ($sku['count'] === null || $sku['count'] > 0);
			}
		} else {
			$product_available = $product['count'] === null || $product['count'] > 0;
		}
		return $product_available;
	}

	public function frontend_product($product)
	{
		$settings = include self::path('config.php');
		if(isset($product['data']))
			$product = $product['data'];
		if (!self::productAvailible($product) && $settings['enable_hook']) {
			$output = array();
			$output['cart'] = self::getFormHtml($product);
			$output['menu'] = '';
			$output['block_aux'] = '';
			$output['block'] = '';
			return $output;
		}
    }

	public function backend_reports()
	{
		return array('menu_li' => '<li><a href="#/arrived/">Уведомления о поступлении</a>
		<script>
			$(function(){
				$.reports.arrivedAction = function () {
					this.setActiveTop("arrived");
					$("#reportscontent").load("?plugin=arrived&action=report"+this.getTimeframeParams());
				};
				$.reports.arrivedSendedAction = function () {
					this.setActiveTop("arrived");
					$("#reportscontent").load("?plugin=arrived&action=report&sended=1"+this.getTimeframeParams());
				};
				$.reports.arrivedRatingAction = function () {
					this.setActiveTop("arrived");
					$("#reportscontent").load("?plugin=arrived&action=report&rating=1"+this.getTimeframeParams());
				};
				$.reports.arrivedFilterAction = function () {
					var reportshash = location.hash.replace("#/arrived/filter/","");
					this.setActiveTop("arrived");
					$("#reportscontent").load("?plugin=arrived&action=report&filter=1&hash="+reportshash+this.getTimeframeParams());
				};
			});
		</script>
		</li>');
    }

	public function backend_product()
	{
		$pid = waRequest::request('id', 0, 'int');
		$model = new shopArrivedModel();
		$count = $model->countRequestsByProductId($pid);
		if($count>0)
			$reportlnk = '<p>&mdash; <a href="?action=reports#/arrived/filter/pid='.$pid.'" target="_blank" class="small"><i class="icon10 new-window" style="vertical-align:middle;margin-top:-2px;"></i> Активных заявок на уведомление о поступлении данного товара: '.$count.'</a></p>';
		else
			$reportlnk = '';
		$row = array('edit_basics' => '<input type="hidden" name="product[arrived_is_manual_edit]" value="1" />
		<div class="field">
			<div class="name">Уведомить о поступлении</div>
			<div class="value no-shift s-ibutton-checkbox" id="s-arrived-send-selector">
				<ul class="menu-h">
					<li>
						<input type="checkbox" id="s-arrived-send-status" name="product[arrived_send]" value="1" checked />
					</li>
					<li><span class="status" data-msg-enabled="Отправить, при наличии активных заявок по товару" data-msg-disabled="Не отправлять уведомления">Отправить, при наличии активных заявок по товару</span></li>
				</ul>
				'.$reportlnk.'
			</div>
		</div>
		<script>
			$("#s-arrived-send-status").iButton().change(function() {
				var enabled = $(this).is(":checked");
				if(enabled) {
					$("#s-arrived-send-selector span.status").text($("#s-arrived-send-selector span.status").data("msg-enabled"));
				} else {
					$("#s-arrived-send-selector span.status").text($("#s-arrived-send-selector span.status").data("msg-disabled"));
				}
			});;
		</script>');
		if($count>0)
			$row['toolbar_section'] = '<p class="highlighted block"><span class="black" style="font-size:13px;">Активных заявок на уведомление о поступлении данного товара:</span><strong class="black" style="font-size:14px;">'.$count.'</strong><br /><a href="?action=reports#/arrived/filter/pid='.$pid.'" class="small">Посмотреть заявки</a></p>';
		return $row;
    }

	public function frontend_head($product)
	{
		$url = $this->getPluginStaticUrl();
		return "<script src='{$url}js/main.js?v".$this->getVersion()."'></script>
		<script type='text/javascript'> var arrived_ignore_stock_count = ".(int)wa('shop')->getConfig()->getGeneralSettings('ignore_stock_count')."; </script>
		<link rel='stylesheet' href='{$url}css/main.css?v".$this->getVersion()."' />";
    }

	public static function getFormHtml($product)
	{
		if(!self::productAvailible($product))
		{
			$settings = include self::path('config.php');
			$settings['expiration'] = explode(",",$settings['expiration']);
			$view = wa()->getView();
			$view->assign("arrived_first_load",self::$first_load);
			if(self::$first_load) {
				self::$first_load = false;
			}
			$view->assign('arrived_settings', $settings);
			$view->assign('arrived_product', $product);
			$view->assign('arrived_link_type', "product");
			$view->assign('arrived_action_url', wa()->getRouteUrl('shop/frontend/arrivedAdd'));
			return $view->fetch(wa()->getAppPath('plugins/arrived', 'shop').'/templates/templateProduct.html');
		}
	}

	public static function getListFormHtml($product)
	{
		$product_available = false;
		$product_available = $product['count'] === null || $product['count'] > 0;
		if(!$product_available)
		{
			$settings = include self::path('config.php');
			$settings['expiration'] = explode(",",$settings['expiration']);
			$view = wa()->getView();
			$view->assign("first_load",self::$first_load);
			if(self::$first_load) {
				self::$first_load = false;
			}
			$view->assign('arrived_settings', $settings);
			$view->assign('arrived_product', $product);
			$view->assign('arrived_link_type', "list");
			$view->assign('arrived_action_url', wa()->getRouteUrl('shop/frontend/arrivedAdd'));
			return $view->fetch(wa()->getAppPath('plugins/arrived', 'shop').'/templates/templateProduct.html');
		}
	}

	public static function getRuDaysWord($count)
	{
		if($count==0)
			return "дней";
		elseif($count==1)
			return "день";
		elseif($count>=2 && $count<=4)
			return "дня";
		elseif($count>=5 && $count<=20)
			return "дней";
		
		if($count>20) {
			$count=substr($count,-1);
			return self::getRuDaysWord($count);
		}
	}

	public function product_delete($params)
	{
		$ids = (array)$params['ids'];
		$model = new shopArrivedModel();
		$model->deleteByField("product_id",$ids);
	}

	public function product_mass_update(&$params)
	{
		if(isset($params['skus_changed']) && !empty($params['skus_changed'])) {
			foreach($params['skus_changed'] as $sku) {
				if((int)$sku['count']>0 || ($sku['count']=="" && $sku['count']!="0")) {
					$this->product_save(array(
						"data" => array(),
						"instance" => new shopProduct($sku['product_id'])
					));
				}
			}
		}
	}

	public function product_save($params)
	{
		$settings = include self::path('config.php');
		$model = new shopArrivedModel();
		$model->deleteAllExpiried();
		$view = wa()->getView();
		$route_params = array();
		$product = $params['data'];
		if(!empty($params['instance']))
			$product = $params['instance'];
		if(!isset($product['arrived_is_manual_edit']) || isset($product['arrived_send']))
		{
			$route_params['product_url'] = $product['url'];
			if(!empty($product['category_id']))
			{
				$category_model = new shopCategoryModel();
				$category = $category_model->getById($product['category_id']);
			}
			foreach($product['skus'] as $sku)
			{
				if((int)$sku['count']>0 || ($sku['count']=="" && $sku['count']!="0"))
				{
					$removed=array();
					foreach($model->getAllActiveRequests("id,email,phone,domain,route_url",$sku['product_id'],$sku['id']) as $row)
					{
						$email_sended = false;
						$routing = wa()->getRouting();
						$domain_routes = $routing->getByApp('shop');
						foreach ($domain_routes as $domain => $routes) {
							if($domain == $row['domain']) {
								foreach ($routes as $r) {
									if($r['app'] == 'shop' && (empty($row['route_url']) || $r['url'] == $row['route_url'])) {
										if(isset($sku['stock']) && !empty($sku['stock'])) {
											$in_stock = false;
											if(!$r['public_stocks']) {
												$in_stock = true;
											} else {
												foreach($r['public_stocks'] as $stock_id) {
													if($sku['stock'][$stock_id] === null || $sku['stock'][$stock_id] > 0) {
														$in_stock = true;
													}
												}
											}
										} else {
											$in_stock = true;
										}
										if($in_stock) {
											$routing->setRoute($r, $domain);
											$product['category_url'] = (isset($r['url_type']) && $r['url_type'] == 1) ? $category['url'] : $category['full_url'];
											if(isset($product['category_url'])) {
												$route_params['category_url'] = $product['category_url'];
											} else {
												$route_params['category_url'] = '';
											}
											$frontend_url = $routing->getUrl('/frontend/product', $route_params, true);
											$frontend_url = str_ireplace("https://","http://",$frontend_url);
											$product['frontend_url'] = $frontend_url;
											$view->assign('product', $product);
										}
										break;
									}
								}
								break;
							}
						}
						if(isset($in_stock) && $in_stock) {
							if(isset($product['frontend_url']))
							{
								if($row['email']!="") {
									$message = new waMailMessage($view->fetch('string:'.$settings['mail_subject']), $view->fetch('string:'.$settings['templateMail']));
									$message->setFrom($settings['email'], wa('shop')->getConfig()->getGeneralSettings('name'));
									$message->setTo($row['email']);
									if($message->send()) {
										$email_sended = true;
									}
								}
								if($row['phone']!="") {
									$sms = new waSMS();
									$view->assign('email_sended', $email_sended);
									$view->assign('user_email', $row['email']);
									//$sms->send($row['phone'], $view->fetch('string:'.$settings['templateSMS']));
									$sms->send($row['phone'], $view->fetch('string:'.$settings['templateSMS']), isset($settings['sms_sender_id'][$row['domain']]) ? $settings['sms_sender_id'][$row['domain']] : null);
								}
								$model->markAsDone($row['id']);
							} else {
								$model->markAsDone($row['id']);
							}
						}
					}
				}
			}
		}
	}
}