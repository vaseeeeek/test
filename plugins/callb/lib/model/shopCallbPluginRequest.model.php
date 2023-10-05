<?php

/*
 * Class shopCallbPluginRequestModel
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopCallbPluginRequestModel extends waModel {

	protected $table = 'shop_callb_request';

	public function countAll($show_done = 'off') {
		if ($show_done === 'off') {
			return $this->query("SELECT COUNT(*) FROM ".$this->table." WHERE `status` != 'done' AND `status` != 'del'")->fetchField();
		} else {
			return $this->query("SELECT COUNT(*) FROM ".$this->table." WHERE `status` != 'del'")->fetchField();
		}
	}

	public function getCallbRequests($offset = 0, $limit = null, $show_done = 'off') {
		$sql = '';

		$sql .= "SELECT * FROM `{$this->table}`";
		if ($show_done === 'off'){
			$sql .= " WHERE `status` != 'done' AND `status` != 'del'";
		} else {
			$sql .= " WHERE `status` != 'del'";
		}
		$sql .= " ORDER BY `create_datetime` DESC";
		$sql .= " LIMIT ".($offset ? $offset.',' : '').(int)$limit;

		$callb_requests = $this->query($sql)->fetchAll('id');

		foreach ($callb_requests as $id => $request) {

			$callb_requests[$id]['human_status'] = '';
			$callb_requests[$id]['contact_name'] = '';
			$callb_requests[$id]['contact_email'] = '';

			switch ($request['status']) {
				case 'new':
					$callb_requests[$id]['human_status'] = _wp('new');
					break;
				case 'done':
					$callb_requests[$id]['human_status'] = _wp('done');
					break;
				case 'del':
					$callb_requests[$id]['human_status'] = _wp('deleted');
					break;
				
				default:
					$callb_requests[$id]['human_status'] = _wp('no status');
					break;
			}

			$contact = new waContact($request['contact_id']);

			if ( $contact->exists() ) {
				$callb_requests[$id]['contact_name'] = htmlspecialchars( $contact->get('name') );
				$callb_requests[$id]['contact_email'] = htmlspecialchars( $contact->get('email', 'default') );
			} else {
				$callb_requests[$id]['contact_name'] = '';
				$callb_requests[$id]['contact_email'] = '';
			}

			$callb_requests[$id]['name'] = addslashes(htmlspecialchars( $request['name'] ));
			$callb_requests[$id]['phone'] = addslashes(htmlspecialchars( $request['phone'] ));

		}

		return $callb_requests;
	}

	public function getNewRequestCount() {
		return $this->countByField('status', 'new');
	}

}