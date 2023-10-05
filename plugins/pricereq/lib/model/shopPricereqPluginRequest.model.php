<?php

/*
 * Class shopPricereqPluginRequestModel
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPluginRequestModel extends waModel {

	protected $table = 'shop_pricereq_request';

	public function countAll($show_done = 'off') {
		if ($show_done === 'off') {
			return $this->query("SELECT COUNT(*) FROM ".$this->table." WHERE `status` != 'done' AND `status` != 'del'")->fetchField();
		} else {
			return $this->query("SELECT COUNT(*) FROM ".$this->table." WHERE `status` != 'del'")->fetchField();
		}
	}

	public function getPriceRequests($offset = 0, $limit = null, $show_done = 'off') {
		$sql = '';

		$sql .= "SELECT * FROM `{$this->table}`";
		if ($show_done === 'off'){
			$sql .= " WHERE `status` != 'done' AND `status` != 'del'";
		} else {
			$sql .= " WHERE `status` != 'del'";
		}
		$sql .= " ORDER BY `create_datetime` DESC";
		$sql .= " LIMIT ".($offset ? $offset.',' : '').(int)$limit;

		$pricereq_requests = $this->query($sql)->fetchAll('id');

		foreach ($pricereq_requests as $id => $request) {

			$pricereq_requests[$id]['human_status'] = '';
			$pricereq_requests[$id]['contact_name'] = '';
			$pricereq_requests[$id]['contact_email'] = '';

			switch ($request['status']) {
				case 'new':
					$pricereq_requests[$id]['human_status'] = _wp('new');
					break;
				case 'done':
					$pricereq_requests[$id]['human_status'] = _wp('done');
					break;
				case 'del':
					$pricereq_requests[$id]['human_status'] = _wp('deleted');
					break;
				
				default:
					$pricereq_requests[$id]['human_status'] = _wp('no status');
					break;
			}

			$contact = new waContact($request['contact_id']);

			if ( $contact->exists() ) {
				$pricereq_requests[$id]['contact_name'] = htmlspecialchars( $contact->get('name') );
				$pricereq_requests[$id]['contact_email'] = htmlspecialchars( $contact->get('email', 'default') );
			} else {
				$pricereq_requests[$id]['contact_name'] = '';
				$pricereq_requests[$id]['contact_email'] = '';
			}

			$pricereq_requests[$id]['name'] = addslashes(htmlspecialchars( $request['name'] ));
			$pricereq_requests[$id]['phone'] = addslashes(htmlspecialchars( $request['phone'] ));
			$pricereq_requests[$id]['email'] = addslashes(htmlspecialchars( $request['email'] ));

		}

		return $pricereq_requests;
	}

	public function getNewRequestCount() {
		return $this->countByField('status', 'new');
	}

}