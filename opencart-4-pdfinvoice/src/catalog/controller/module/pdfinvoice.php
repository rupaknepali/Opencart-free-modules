<?php
namespace Opencart\Catalog\Controller\Extension\Pdfinvoice\Module;
/**
 * PDF Invoice: emails the invoice when an order reaches a chosen status, and lets
 * logged-in customers download it from Account > Order History.
 */
class Pdfinvoice extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/pdfinvoice/module/pdfinvoice';

	private function invoice(): \Opencart\System\Library\Extension\Pdfinvoice\Invoice {
		$this->load->language($this->route);

		$text = array_filter($this->language->all(), fn($key) => str_starts_with($key, 'pdf_'), ARRAY_FILTER_USE_KEY);

		return new \Opencart\System\Library\Extension\Pdfinvoice\Invoice($this->registry, $text);
	}

	/**
	 * Event: catalog/model/checkout/order.addHistory/after
	 * Runs for checkout, payment callbacks and admin status changes (the admin goes through the catalog API).
	 */
	public function history(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_pdfinvoice_status')) {
			return;
		}

		$order_id = (int)($args[0] ?? 0);
		$order_status_id = (int)($args[1] ?? 0);

		if (!in_array($order_status_id, array_map('intval', (array)$this->config->get('module_pdfinvoice_send_statuses')))) {
			return;
		}

		$invoice = $this->invoice();

		// Automatic emails go out once per order. The admin button can resend.
		if ($invoice->lastSent($order_id)) {
			return;
		}

		// Never let a mail problem break checkout or a status change.
		try {
			$error = $invoice->send($order_id, 'auto');

			if ($error) {
				$this->log->write('PDF Invoice: order ' . $order_id . ' not emailed (' . $error . ')');
			}
		} catch (\Throwable $e) {
			$this->log->write('PDF Invoice: order ' . $order_id . ' not emailed: ' . $e->getMessage());
		}
	}

	/**
	 * Event: catalog/view/account/order_info/after. Adds a "Download invoice (PDF)" button.
	 */
	public function account(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_pdfinvoice_status') || !$this->config->get('module_pdfinvoice_account') || !$this->customer->isLogged() || empty($this->request->get['order_id'])) {
			return;
		}

		$order_id = (int)$this->request->get['order_id'];

		if (!$this->allowed($order_id)) {
			return;
		}

		$this->load->language($this->route);

		$data['href'] = $this->url->link($this->route . '.download', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id . '&customer_token=' . ($this->session->data['customer_token'] ?? ''));

		$html = $this->load->view('extension/pdfinvoice/module/pdfinvoice_account', $data);

		// Before the History heading; theme changes fall back to the history box.
		if (preg_match('#<h2>[^<]*</h2>\s*<div id="history">#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1], 0);
		} elseif (($position = strpos($output, '<div id="history">')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * The customer's own order, in one of the store's processing or complete statuses
	 * (so unpaid or cancelled orders don't get an invoice).
	 */
	private function allowed(int $order_id): bool {
		$this->load->model('account/order');

		$order_info = $this->model_account_order->getOrder($order_id);

		if (!$order_info) {
			return false;
		}

		$statuses = array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status')));

		return in_array((int)$order_info['order_status_id'], $statuses);
	}

	public function download(): void {
		$this->load->language($this->route);

		$order_id = (int)($this->request->get['order_id'] ?? 0);

		// Same check as the account pages: logged in, with a valid customer_token.
		if (!$this->config->get('module_pdfinvoice_status') || !$this->config->get('module_pdfinvoice_account') || !$this->load->controller('account/login.validate') || !$this->allowed($order_id)) {
			$this->session->data['redirect'] = $this->url->link('account/order', 'language=' . $this->config->get('config_language'));

			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));

			return;
		}

		$invoice = $this->invoice();

		if ($this->config->get('module_pdfinvoice_invoice_no')) {
			$invoice->ensureInvoiceNo($order_id);
		}

		$order = $invoice->getOrder($order_id);

		$this->response->addHeader('Content-Type: application/pdf');
		$this->response->addHeader('Content-Disposition: attachment; filename="' . $invoice->filename($order) . '"');
		$this->response->setOutput($invoice->build($order_id));
	}
}
