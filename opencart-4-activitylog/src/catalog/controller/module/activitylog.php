<?php
namespace Opencart\Catalog\Controller\Extension\Activitylog\Module;
/**
 * Admin Activity Log (storefront side): order status changes made from the admin go through the storefront's API.
 */
class Activitylog extends \Opencart\System\Engine\Controller {
	public function index(): string {
		return '';
	}

	/**
	 * catalog/model/checkout/order.addHistory/after: log status changes made through the API (the admin's order page).
	 */
	public function order(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_activitylog_status') || !str_starts_with((string)($this->request->get['route'] ?? ''), 'api/')) {
			return;
		}

		$order_id = (int)($args[0] ?? 0);
		$status_id = (int)($args[1] ?? 0);

		$status = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "order_status` WHERE `order_status_id` = '" . $status_id . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'")->row['name'] ?? (string)$status_id;

		// Set by the admin side (sale/order.call/before) in this same PHP request.
		$admin = (array)($GLOBALS['activitylog_admin'] ?? []);
		$user_id = (int)($admin['user_id'] ?? 0);
		$username = (string)($admin['username'] ?? '');

		$this->db->query("INSERT INTO `" . DB_PREFIX . "activitylog` SET `user_id` = '" . $user_id . "', `username` = '" . $this->db->escape($username ?: 'API') . "', `ip` = '" . $this->db->escape(oc_get_ip()) . "', `action` = 'status', `object` = 'sale/order', `object_id` = '" . $order_id . "', `name` = '" . $this->db->escape('Order #' . $order_id) . "', `method` = 'addHistory', `changes` = '" . $this->db->escape(json_encode([['order_status', '', $status], ['notify', '', !empty($args[3]) ? 'yes' : 'no'], ['comment', '', oc_substr((string)($args[2] ?? ''), 0, 120)]], JSON_UNESCAPED_UNICODE)) . "', `date_added` = NOW()");
	}
}
