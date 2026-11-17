<?php
namespace Opencart\Catalog\Model\Extension\Requestquote\Module;
/**
 * Request a Quote: saves requests.
 */
class Requestquote extends \Opencart\System\Engine\Model {
	public function addRequest(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "quote_request` SET
			`product_id` = '" . (int)$data['product_id'] . "',
			`store_id` = '" . (int)$this->config->get('config_store_id') . "',
			`customer_id` = '" . (int)$this->customer->getId() . "',
			`name` = '" . $this->db->escape($data['name']) . "',
			`email` = '" . $this->db->escape($data['email']) . "',
			`telephone` = '" . $this->db->escape($data['telephone']) . "',
			`company` = '" . $this->db->escape($data['company']) . "',
			`quantity` = '" . (int)$data['quantity'] . "',
			`message` = '" . $this->db->escape($data['message']) . "',
			`status` = '0',
			`ip` = '" . $this->db->escape(oc_get_ip()) . "',
			`date_added` = NOW()");

		return $this->db->getLastId();
	}
}
