<?php
namespace Opencart\Catalog\Controller\Extension\Ordertracking\Event;
/**
 * Order Tracking: "Track package" links on Account > Order History > (order).
 */
class Ordertracking extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/account/order_info/after
	 */
	public function index(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_ordertracking_status') || !$this->customer->isLogged() || empty($this->request->get['order_id'])) {
			return;
		}

		// Only the customer's own orders (the page itself checks this too).
		$query = $this->db->query("SELECT ot.* FROM `" . DB_PREFIX . "order_tracking` ot LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = ot.`order_id`) WHERE ot.`order_id` = '" . (int)$this->request->get['order_id'] . "' AND o.`customer_id` = '" . (int)$this->customer->getId() . "' ORDER BY ot.`date_added` ASC, ot.`order_tracking_id` ASC");

		if (!$query->num_rows) {
			return;
		}

		$this->load->language('extension/ordertracking/module/ordertracking');

		$data['trackings'] = [];

		foreach ($query->rows as $row) {
			$data['trackings'][] = [
				'carrier'         => $row['carrier'],
				'tracking_number' => $row['tracking_number'],
				'url'             => $row['url'],
				'date_added'      => date($this->language->get('date_format_short'), strtotime($row['date_added']))
			];
		}

		$html = $this->load->view('extension/ordertracking/module/ordertracking', $data);

		// Above the History heading; at the end of #content if a theme changed that markup.
		if (preg_match('#<h2>[^<]*</h2>\s*<div id="history">#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1], 0);
		} elseif (($position = strpos($output, '<div id="history">')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * Trigger: catalog/view/mail/order_history/after
	 * OpenCart's history email prints the comment on one line with the link as plain text.
	 * For comments that carry one of this order's tracking links, keep the line breaks and make the link clickable.
	 */
	public function mail(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_ordertracking_status') || empty($args['comment']) || empty($args['order_id'])) {
			return;
		}

		$urls = $this->db->query("SELECT DISTINCT `url` FROM `" . DB_PREFIX . "order_tracking` WHERE `order_id` = '" . (int)$args['order_id'] . "'")->rows;

		if (!$urls) {
			return;
		}

		// The template prints {{ comment }} as it is. Comments added from the admin arrive HTML-encoded
		// (OpenCart encodes request data), so look for the link in both forms.
		$comment = (string)$args['comment'];

		$html = nl2br($comment, false);
		$found = false;

		foreach ($urls as $row) {
			$escaped = htmlspecialchars($row['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

			foreach ([$escaped, $row['url']] as $candidate) {
				if (str_contains($html, $candidate)) {
					$html = str_replace($candidate, '<a href="' . $escaped . '">' . $escaped . '</a>', $html);
					$found = true;

					break;
				}
			}
		}

		if ($found && str_contains($output, $comment)) {
			$output = str_replace($comment, $html, $output);
		}
	}
}
